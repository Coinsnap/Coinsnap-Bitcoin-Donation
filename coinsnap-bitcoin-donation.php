<?php
/*
 * Plugin Name:        Coinsnap Bitcoin Donation
 * Plugin URI:         https://coinsnap.io/coinsnap-bitcoin-donation-plugin/
 * Description:        Easy Bitcoin donations on a WordPress website
 * Version:            1.2.0
 * Author:             Coinsnap
 * Author URI:         https://coinsnap.io/
 * Text Domain:        coinsnap-bitcoin-donation
 * Domain Path:        /languages
 * Tested up to:       6.8
 * License:            GPL2
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:            true
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'COINSNAP_BITCOIN_DONATION_REFERRAL_CODE' ) ) {
    define( 'COINSNAP_BITCOIN_DONATION_REFERRAL_CODE', 'D19833' );
}
if ( ! defined( 'COINSNAP_BITCOIN_DONATION_VERSION' ) ) {
    define( 'COINSNAP_BITCOIN_DONATION_VERSION', '1.2.0' );
}
if ( ! defined( 'COINSNAP_BITCOIN_DONATION_PHP_VERSION' ) ) {
    define( 'COINSNAP_BITCOIN_DONATION_PHP_VERSION', '8.0' );
}
if( ! defined( 'COINSNAP_BITCOIN_DONATION_PLUGIN_DIR' ) ){
    define('COINSNAP_BITCOIN_DONATION_PLUGIN_DIR',plugin_dir_url(__FILE__));
}

// Plugin settings
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shoutout-posts.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-public-donors.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shortcode-wide.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount-wide.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shoutouts-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shoutouts-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-webhooks.php';

register_activation_hook(__FILE__, 'coinsnap_bitcoin_donation_create_donation_payments_table');
register_deactivation_hook(__FILE__, 'coinsnap_bitcoin_donation_deactivate');

function coinsnap_bitcoin_donation_deactivate()
{
    flush_rewrite_rules();
}

function coinsnap_bitcoin_donation_create_donation_payments_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'donation_payments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        payment_id VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}


class coinsnap_bitcoin_donation
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'coinsnap_bitcoin_donation_enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'coinsnap_bitcoin_donation_enqueue_admin_styles']);
        add_action('wp_ajax_coinsnap_bitcoin_donation_btcpay_apiurl_handler', [$this, 'btcpayApiUrlHandler']);
    }
    
    function btcpayApiUrlHandler(){
            $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_STRING);
            if ( !wp_verify_nonce( $_nonce, 'coinsnap-ajax-nonce' ) ) {
                wp_die('Unauthorized!', '', ['response' => 401]);
            }

            if ( current_user_can( 'manage_options' ) ) {
                $host = filter_var(filter_input(INPUT_POST,'host',FILTER_SANITIZE_STRING), FILTER_VALIDATE_URL);

                if ($host === false || (substr( $host, 0, 7 ) !== "http://" && substr( $host, 0, 8 ) !== "https://")) {
                    wp_send_json_error("Error validating BTCPayServer URL.");
                }

                $permissions = array_merge([
                    'btcpay.store.canviewinvoices',
                    'btcpay.store.cancreateinvoice',
                    'btcpay.store.canviewstoresettings',
                    'btcpay.store.canmodifyinvoices'
                ],
                [
                    'btcpay.store.cancreatenonapprovedpullpayments',
                    'btcpay.store.webhooks.canmodifywebhooks',
                ]);

                try {
                    // Create the redirect url to BTCPay instance.
                    $url = $this->getAuthorizeUrl(
                        $host,
                        $permissions,
                        'CoinsnapBitcoinDonation',
                        true,
                        true,
                        home_url('?donation-btcpay-settings-callback'),
                        null
                    );

                    // Store the host to options before we leave the site.
                    coinsnap_settings_update(['btcpay_url' => $host]);

                    // Return the redirect url.
                    wp_send_json_success(['url' => $url]);
                }

                catch (\Throwable $e) {

                }
            }
            wp_send_json_error("Error processing Ajax request.");
    }
    
    

    function coinsnap_bitcoin_donation_enqueue_scripts(){
        wp_enqueue_style('coinsnap-bitcoin-donation-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
        wp_enqueue_style('coinsnap-bitcoin-donation-style-wide', plugin_dir_url(__FILE__) . 'assets/css/style-wide.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
        wp_enqueue_style('coinsnap-bitcoin-donation-shoutouts', plugin_dir_url(__FILE__) . 'assets/css/shoutouts.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
        wp_enqueue_script('coinsnap-bitcoin-donation-multi-script', plugin_dir_url(__FILE__) . 'assets/js/multi.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        $provider_defaults = [
            'provider' => 'coinsnap',
            'coinsnap_store_id' => '',
            'coinsnap_api_key' => '',
            'btcpay_store_id' => '',
            'btcpay_api_key' => '',
            'btcpay_url' => ''
        ];
        $provider_options = array_merge($provider_defaults, (array) get_option('coinsnap_bitcoin_donation_options', []));

        // Define defaults for forms options
        $forms_defaults = [
            'currency' => 'EUR',
            'default_amount' => 10,
            'default_message' => 'Thank you for your support!',
            'redirect_url' => home_url(),
            'multiRedirectUrl' => home_url(),
            'multi_amount_default_snap1' => 5,
            'multi_amount_default_snap2' => 10,
            'multi_amount_default_snap3' => 25,
            'multi_amount_primary_currency' => 'FIAT',
            'multi_amount_fiat_currency' => 'EUR',
            'multi_amount_default_amount' => 15,
            'multi_amount_default_message' => 'Multi-currency donation',
            'shoutout_currency' => 'EUR',
            'shoutout_default_amount' => 20,
            'shoutout_minimum_amount' => 5,
            'shoutout_premium_amount' => 50,
            'shoutout_default_message' => 'Great work!',
            'shoutout_redirect_url' => home_url(),
            'multi_amount_redirect_url' => home_url()
        ];
        $forms_options = array_merge($forms_defaults, (array) get_option('coinsnap_bitcoin_donation_forms_options', []));

        // Localize script for donationData
        wp_localize_script('coinsnap-bitcoin-donation-multi-script', 'multiData', [
            'snap1Amount' => $forms_options['multi_amount_default_snap1'],
            'snap2Amount' => $forms_options['multi_amount_default_snap2'],
            'snap3Amount' => $forms_options['multi_amount_default_snap3'],
            'multiPrimary' => $forms_options['multi_amount_primary_currency'],
            'multiFiat' => $forms_options['multi_amount_fiat_currency'],
            'defaultMultiAmount' => $forms_options['multi_amount_default_amount'],
            'defaultMultiMessage' => $forms_options['multi_amount_default_message'],
            'redirectUrl' => $forms_options['multi_amount_redirect_url']
        ]);

        // Localize script for shoutoutsData
        wp_enqueue_script('coinsnap-bitcoin-donation-shoutout-script', plugin_dir_url(__FILE__) . 'assets/js/shoutouts.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        wp_localize_script('coinsnap-bitcoin-donation-shoutout-script', 'shoutoutsData', [
            'currency' => $forms_options['shoutout_currency'],
            'defaultShoutoutAmount' => $forms_options['shoutout_default_amount'],
            'minimumShoutoutAmount' => $forms_options['shoutout_minimum_amount'],
            'premiumShoutoutAmount' => $forms_options['shoutout_premium_amount'],
            'defaultShoutoutMessage' => $forms_options['shoutout_default_message'],
            'redirectUrl' => $forms_options['shoutout_redirect_url']
        ]);

        // Localize script for sharedData
        wp_enqueue_script('coinsnap-bitcoin-donation-shared-script', plugin_dir_url(__FILE__) . 'assets/js/shared.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        wp_localize_script('coinsnap-bitcoin-donation-shared-script', 'sharedData', [
            'currency' => $forms_options['currency'],
            'provider' => $provider_options['provider'],
            'coinsnapStoreId' => $provider_options['coinsnap_store_id'],
            'coinsnapApiKey' => $provider_options['coinsnap_api_key'],
            'btcpayStoreId' => $provider_options['btcpay_store_id'],
            'btcpayApiKey' => $provider_options['btcpay_api_key'],
            'btcpayUrl' => $provider_options['btcpay_url'],
            'defaultAmount' => $forms_options['default_amount'],
            'defaultMessage' => $forms_options['default_message'],
            'defaultShoutoutAmount' => $forms_options['shoutout_default_amount'],
            'defaultShoutoutMessage' => $forms_options['shoutout_default_message'],
            'minimumShoutoutAmount' => $forms_options['shoutout_minimum_amount'],
            'premiumShoutoutAmount' => $forms_options['shoutout_premium_amount'],
            'shoutoutRedirectUrl' => $forms_options['shoutout_redirect_url'],
            'multiRedirectUrl' => $forms_options['multi_amount_redirect_url'],
            'redirectUrl' => $forms_options['redirect_url'],
            'nonce' => wp_create_nonce('wp_rest')
        ]);

        //Localize script for donationData
        wp_enqueue_script('coinsnap-bitcoin-donation-form-script', plugin_dir_url(__FILE__) . 'assets/js/donations.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        wp_localize_script('coinsnap-bitcoin-donation-form-script', 'formData', [
            'currency' => $forms_options['currency'],
            'defaultAmount' => $forms_options['default_amount'],
            'defaultMessage' => $forms_options['default_message'],
            'redirectUrl' => $forms_options['redirect_url'],
        ]);

        //Localize script for popupData
        wp_enqueue_script('coinsnap-bitcoin-donation-popup-script', plugin_dir_url(__FILE__) . 'assets/js/popup.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
    }

    function coinsnap_bitcoin_donation_enqueue_admin_styles($hook)
    {
        //error_log($hook);
        if ($hook === 'bitcoin-donations_page_coinsnap-bitcoin-donation-list') {
            wp_enqueue_style('coinsnap-bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
        } else if ($hook === 'bitcoin-donations_page_coinsnap-bitcoin-donation-forms') {
            wp_enqueue_style('coinsnap-bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            wp_enqueue_script('coinsnap-bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        } else if ($hook === 'toplevel_page_coinsnap_bitcoin_donation') {
            wp_enqueue_style('coinsnap-bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            $options = get_option('coinsnap_bitcoin_donation_options', []);
            $ngrok_url = isset($options['ngrok_url']) ? $options['ngrok_url'] : '';
            wp_enqueue_script('coinsnap-bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
            
            wp_localize_script('coinsnap-bitcoin-donation-admin-script', 'coinsnap_bitcoin_donation_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'  => wp_create_nonce( 'coinsnap-ajax-nonce' ),
            ));
        }
    }

    function coinsnap_bitcoin_donation_verify_nonce($nonce, $action)
    {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(esc_html__('Security check failed', 'coinsnap-bitcoin-donation'));
        }
    }
    
    public function getAuthorizeUrl(string $baseUrl, array $permissions, ?string $applicationName, ?bool $strict, ?bool $selectiveStores, ?string $redirectToUrlAfterCreation, ?string $applicationIdentifier): string
    {
        $url = rtrim($baseUrl, '/') . '/api-keys/authorize';

        $params = [];
        $params['permissions'] = $permissions;
        $params['applicationName'] = $applicationName;
        $params['strict'] = $strict;
        $params['selectiveStores'] = $selectiveStores;
        $params['redirect'] = $redirectToUrlAfterCreation;
        $params['applicationIdentifier'] = $applicationIdentifier;

        // Take out NULL values
        $params = array_filter($params, function ($value) {
            return $value !== null;
        });

        $queryParams = [];

        foreach ($params as $param => $value) {
            if ($value === true) {
                $value = 'true';
            }
            if ($value === false) {
                $value = 'false';
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === true) {
                        $item = 'true';
                    }
                    if ($item === false) {
                        $item = 'false';
                    }
                    $queryParams[] = $param . '=' . urlencode((string)$item);
                }
            } else {
                $queryParams[] = $param . '=' . urlencode((string)$value);
            }
        }

        $queryParams = implode("&", $queryParams);
        $url .= '?' . $queryParams;
        return $url;
    }
}
new coinsnap_bitcoin_donation();

add_action('init', function() {
    // Setting up and handling custom endpoint for api key redirect from BTCPay Server.
    add_rewrite_endpoint('donation-btcpay-settings-callback', EP_ROOT);
});

// To be able to use the endpoint without appended url segments we need to do this.
add_filter('request', function($vars) {
    if (isset($vars['donation-btcpay-settings-callback'])) {
        $vars['donation-btcpay-settings-callback'] = true;
    }
    return $vars;
});

function coinsnap_settings_update($data){
        
        $form_data = get_option('coinsnap_bitcoin_donation_options', []);
        
        foreach($data as $key => $value){
            $form_data[$key] = $value;
        }
        
        update_option('coinsnap_bitcoin_donation_options',$form_data);
    } 

function remoteRequest(string $method,string $url,array $headers = [],string $body = ''){
    
    $wpRemoteArgs = ['body' => $body, 'method' => $method, 'timeout' => 5, 'headers' => $headers];
    $response = wp_remote_request($url,$wpRemoteArgs);
    
    if(is_wp_error($response) ) {
        $errorMessage = $response->get_error_message();
        $errorCode = $response->get_error_code();
        return array('error' => ['code' => (int)esc_html($errorCode), 'message' => esc_html($errorMessage)]);
    }
    elseif(is_array($response)) {
        $status = $response['response']['code'];
        $responseHeaders = wp_remote_retrieve_headers($response)->getAll();
        $responseBody = json_decode($response['body'],true);
        return array('status' => $status, 'body' => $responseBody, 'headers' => $responseHeaders);
    }
}

// Adding template redirect handling for donation-btcpay-settings-callback.
add_action( 'template_redirect', function(){
    
    global $wp_query;
            
    // Only continue on a donation-btcpay-settings-callback request.    
    if (!isset( $wp_query->query_vars['donation-btcpay-settings-callback'])) {
        return;
    }

    $CoinsnapBTCPaySettingsUrl = admin_url('/admin.php?page=coinsnap_bitcoin_donation');

            $rawData = file_get_contents('php://input');
            $form_data = get_option('coinsnap_bitcoin_donation_options', []);

            $btcpay_server_url = $form_data['btcpay_url'];
            $btcpay_api_key  = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $request_url = $btcpay_server_url.'/api/v1/stores';
            $request_headers = ['Accept' => 'application/json','Content-Type' => 'application/json','Authorization' => 'token '.$btcpay_api_key];
            $getstores = remoteRequest('GET',$request_url,$request_headers);
            
            if(!isset($getstores['error'])){
                if (count($getstores['body']) < 1) {
                    $messageAbort = __('Error on verifiying redirected API Key with stored BTCPay Server url. Aborting API wizard. Please try again or continue with manual setup.', 'coinsnap-bitcoin-donation');
                    //$notice->addNotice('error', $messageAbort);
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                }
            }
                        
            // Data does get submitted with url-encoded payload, so parse $_POST here.
            if (!empty($_POST) || wp_verify_nonce(filter_input(INPUT_POST,'wp_nonce',FILTER_SANITIZE_FULL_SPECIAL_CHARS),'-1')) {
                $data['apiKey'] = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
                $permissions = (isset($_POST['permissions']) && is_array($_POST['permissions']))? $_POST['permissions'] : null;
                if (isset($permissions)) {
                    foreach ($permissions as $key => $value) {
                        $data['permissions'][$key] = sanitize_text_field($permissions[$key] ?? null);
                    }
                }
            }
    
            if (isset($data['apiKey']) && isset($data['permissions'])) {

                $REQUIRED_PERMISSIONS = [
                    'btcpay.store.canviewinvoices',
                    'btcpay.store.cancreateinvoice',
                    'btcpay.store.canviewstoresettings',
                    'btcpay.store.canmodifyinvoices'
                ];
                $OPTIONAL_PERMISSIONS = [
                    'btcpay.store.cancreatenonapprovedpullpayments',
                    'btcpay.store.webhooks.canmodifywebhooks',
                ];
                
                $btcpay_server_permissions = $data['permissions'];
                
                $permissions = array_reduce($btcpay_server_permissions, static function (array $carry, string $permission) {
			return array_merge($carry, [explode(':', $permission)[0]]);
		}, []);

		// Remove optional permissions so that only required ones are left.
		$permissions = array_diff($permissions, $OPTIONAL_PERMISSIONS);

		$hasRequiredPermissions = (empty(array_merge(array_diff($REQUIRED_PERMISSIONS, $permissions), array_diff($permissions, $REQUIRED_PERMISSIONS))))? true : false;
                
                $hasSingleStore = true;
                $storeId = null;
		foreach ($btcpay_server_permissions as $perms) {
                    if (2 !== count($exploded = explode(':', $perms))) { return false; }
                    if (null === ($receivedStoreId = $exploded[1])) { $hasSingleStore = false; }
                    if ($storeId === $receivedStoreId) { continue; }
                    if (null === $storeId) { $storeId = $receivedStoreId; continue; }
                    $hasSingleStore = false;
		}
                
                if ($hasSingleStore && $hasRequiredPermissions) {

                    coinsnap_settings_update([
                        'btcpay_api_key' => $data['apiKey'],
                        'btcpay_store_id' => explode(':', $btcpay_server_permissions[0])[1],
                        'provider' => 'btcpay'
                        ]);
                    
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                    exit();
                }
                else {
                    //$notice->addNotice('error', __('Please make sure you only select one store on the BTCPay API authorization page.', 'coinsnap-bitcoin-donation'));
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                    exit();
                }
            }

    //$notice->addNotice('error', __('Error processing the data from Coinsnap. Please try again.', 'coinsnap-bitcoin-donation'));
    wp_redirect($CoinsnapBTCPaySettingsUrl);
    exit();
});
