<?php
/*
 * Plugin Name:        Coinsnap Bitcoin Donation
 * Plugin URI:         https://coinsnap.io/wp-plugins/wp-bitcoin-donation/
 * Description:        Easy Bitcoin donations on a WordPress website
 * Version:            1.4.2
 * Author:             Coinsnap
 * Author URI:         https://coinsnap.io/
 * Text Domain:        coinsnap-bitcoin-donation
 * Domain Path:        /languages
 * Tested up to:       6.9
 * License:            GPL2
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:            true
 */

defined( 'ABSPATH' ) || exit;

if(!defined( 'COINSNAP_BITCOIN_DONATION_REFERRAL_CODE' ) ) { define( 'COINSNAP_BITCOIN_DONATION_REFERRAL_CODE', 'D19833' );}
if(!defined( 'COINSNAP_BITCOIN_DONATION_VERSION' ) ) { define( 'COINSNAP_BITCOIN_DONATION_VERSION', '1.4.2' );}
if(!defined( 'COINSNAP_BITCOIN_DONATION_PHP_VERSION' ) ) { define( 'COINSNAP_BITCOIN_DONATION_PHP_VERSION', '8.0' );}
if(!defined( 'COINSNAP_BITCOIN_DONATION_PLUGIN_DIR' ) ){define('COINSNAP_BITCOIN_DONATION_PLUGIN_DIR',plugin_dir_url(__FILE__));}
if(!defined('COINSNAP_CURRENCIES')){define( 'COINSNAP_CURRENCIES', array("EUR","USD","SATS","BTC","CAD","JPY","GBP","CHF","RUB") );}
if(!defined('COINSNAP_SERVER_URL')){define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );}
if(!defined('COINSNAP_API_PATH')){define( 'COINSNAP_API_PATH', '/api/v1/');}
if(!defined('COINSNAP_SERVER_PATH')){define( 'COINSNAP_SERVER_PATH', 'stores' );}

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
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-client.php';

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
        add_action('wp_ajax_coinsnap_bitcoin_donation_connection_handler', [$this, 'coinsnapConnectionHandler']);
        add_action('wp_ajax_coinsnap_bitcoin_donation_amount_check', [$this, 'coinsnapAmountCheck']);
        add_action('wp_ajax_nopriv_coinsnap_bitcoin_donation_amount_check', [$this, 'coinsnapAmountCheck']);
    }
    
    public function coinsnapAmountCheck(){
        
        $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_STRING);
        if ( !wp_verify_nonce( $_nonce, 'coinsnap-ajax-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
        
        $client = new Coinsnap_Bitcoin_Donation_Client();
        $amount = filter_input(INPUT_POST,'apiAmount',FILTER_SANITIZE_STRING);
        $currency = filter_input(INPUT_POST,'apiCurrency',FILTER_SANITIZE_STRING);
        
        try {
            $_provider = $this->getPaymentProvider();
            if($_provider === 'btcpay'){
                try {
                    $storePaymentMethods = $client->getStorePaymentMethods($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());

                    if ($storePaymentMethods['code'] === 200) {
                        if(!$storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                            $errorMessage = __( 'No payment method is configured on BTCPay server', 'coinsnap-bitcoin-donation' );
                            $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                        }
                    }
                    else {
                        $errorMessage = __( 'Error store loading. Wrong or empty Store ID', 'coinsnap-bitcoin-donation' );
                        $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                    }

                    if($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ),'bitcoin');
                    }
                    elseif($storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ),'lightning');
                    }
                }
                catch (\Throwable $e){
                    $errorMessage = __( 'API connection is not established', 'coinsnap-bitcoin-donation' );
                    $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                }
            }
            else {
                $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ));
                if($checkInvoice['error'] === 'currencyError'){
                    $checkInvoice['error'] = sprintf( 
                        /* translators: 1: Currency */
                        __( 'Currency %1$s is not supported by Coinsnap', 'coinsnap-bitcoin-donation' ), strtoupper( $currency ));
                }
                elseif($checkInvoice['error'] === 'amountError'){
                    $checkInvoice['error'] = sprintf( 
                        /* translators: 1: Amount, 2: Currency */
                        __( 'Invoice amount cannot be less than %1$s %2$s', 'coinsnap-bitcoin-donation' ), $checkInvoice['min_value'], strtoupper( $currency ));
                }
            }
        }
        catch (\Throwable $e){
            $errorMessage = __( 'API connection is not established', 'coinsnap-bitcoin-donation' );
            $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
        }
        return $this->sendJsonResponse($checkInvoice);
    }
    
    public function coinsnapConnectionHandler(){
        $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_STRING);
        if ( !wp_verify_nonce( $_nonce, 'coinsnap-ajax-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
        
        $response = [
            'result' => false,
            'message' => __('Empty gateway URL or API Key', 'coinsnap-bitcoin-donation')
        ];
        
        
        $coinsnap_bitcoin_donation_data = get_option('coinsnap_bitcoin_donation_options', []);
        
        $_provider = $this->getPaymentProvider();
        $currency = ('' !== filter_input(INPUT_POST,'apiCurrency',FILTER_SANITIZE_STRING))? filter_input(INPUT_POST,'apiCurrency',FILTER_SANITIZE_STRING) : 'EUR';
        $client = new Coinsnap_Bitcoin_Donation_Client();
        
        if($_provider === 'btcpay'){
            try {
                
                $storePaymentMethods = $client->getStorePaymentMethods($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());

                if ($storePaymentMethods['code'] === 200) {
                    if($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData(0,$currency,'bitcoin','calculation');
                    }
                    elseif($storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData(0,$currency,'lightning','calculation');
                    }
                }
            }
            catch (\Exception $e) {
                $response = [
                        'result' => false,
                        'message' => __('Coinsnap Bitcoin Donation: API connection is not established', 'coinsnap-bitcoin-donation')
                ];
                $this->sendJsonResponse($response);
            }
        }
        else {
            $checkInvoice = $client->checkPaymentData(0,$currency,'coinsnap','calculation');
        }
        
        if(isset($checkInvoice) && $checkInvoice['result']){
            $connectionData = __('Min donation amount is', 'coinsnap-bitcoin-donation') .' '. $checkInvoice['min_value'].' '.$currency;
        }
        else {
            $connectionData = __('No payment method is configured', 'coinsnap-bitcoin-donation');
        }
        
        $_message_disconnected = ($_provider !== 'btcpay')? 
            __('Coinsnap Bitcoin Donation: Coinsnap server is disconnected', 'coinsnap-bitcoin-donation') :
            __('Coinsnap Bitcoin Donation: BTCPay server is disconnected', 'coinsnap-bitcoin-donation');
        $_message_connected = ($_provider !== 'btcpay')?
            __('Coinsnap Bitcoin Donation: Coinsnap server is connected', 'coinsnap-bitcoin-donation') : 
            __('Coinsnap Bitcoin Donation: BTCPay server is connected', 'coinsnap-bitcoin-donation');
        
        if( wp_verify_nonce($_nonce,'coinsnap-ajax-nonce') ){
            $response = ['result' => false,'message' => $_message_disconnected];

            try {
                $this_store = $client->getStore($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());
                
                if ($this_store['code'] !== 200) {
                    $this->sendJsonResponse($response);
                }
                
                else {
                    $response = ['result' => true,'message' => $_message_connected.' ('.$connectionData.')'];
                    $this->sendJsonResponse($response);
                }
            }
            catch (\Exception $e) {
                $response['message'] =  __('Coinsnap Bitcoin Donation: API connection is not established', 'coinsnap-bitcoin-donation');
            }

            $this->sendJsonResponse($response);
        }            
    }
    
    public function sendJsonResponse(array $response): void {
        echo wp_json_encode($response);
        exit();
    }
    
    private function getPaymentProvider() {
        $coinsnap_bitcoin_donation_data = get_option('coinsnap_bitcoin_donation_options', []);
        return ($coinsnap_bitcoin_donation_data['provider'] === 'btcpay')? 'btcpay' : 'coinsnap';
    }

    private function getApiKey() {
        $coinsnap_bitcoin_donation_data = get_option('coinsnap_bitcoin_donation_options', []);
        return ($this->getPaymentProvider() === 'btcpay')? $coinsnap_bitcoin_donation_data['btcpay_api_key']  : $coinsnap_bitcoin_donation_data['coinsnap_api_key'];
    }
    
    private function getStoreId() {
	$coinsnap_bitcoin_donation_data = get_option('coinsnap_bitcoin_donation_options', []);
        return ($this->getPaymentProvider() === 'btcpay')? $coinsnap_bitcoin_donation_data['btcpay_store_id'] : $coinsnap_bitcoin_donation_data['coinsnap_store_id'];
    }
    
    public function getApiUrl() {
        $coinsnap_bitcoin_donation_data = get_option('coinsnap_bitcoin_donation_options', []);
        return ($this->getPaymentProvider() === 'btcpay')? $coinsnap_bitcoin_donation_data['btcpay_url'] : COINSNAP_SERVER_URL;
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
                    coinsnap_settings_update('coinsnap_bitcoin_donation_options',['btcpay_url' => $host]);

                    // Return the redirect url.
                    wp_send_json_success(['url' => $url]);
                }

                catch (\Throwable $e) {

                }
            }
            wp_send_json_error("Error processing Ajax request.");
    }
    
    

    function coinsnap_bitcoin_donation_enqueue_scripts(){
        
        global $post;
    /*
        if(
                has_shortcode($post->post_content, 'coinsnap_bitcoin_donation') || 
                has_shortcode($post->post_content, 'coinsnap_bitcoin_donation_wide') || 
                has_shortcode($post->post_content, 'multi_amount_donation') || 
                has_shortcode($post->post_content, 'multi_amount_donation_wide') || 
                has_shortcode($post->post_content, 'shoutout_form') || 
                has_shortcode($post->post_content, 'shoutout_list')
       )*/
       {
            
            wp_enqueue_style('coinsnap-bitcoin-donation-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            wp_enqueue_style('coinsnap-bitcoin-donation-style-wide', plugin_dir_url(__FILE__) . 'assets/css/style-wide.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            wp_enqueue_style('coinsnap-bitcoin-donation-shoutouts', plugin_dir_url(__FILE__) . 'assets/css/shoutouts.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            
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
                'default_amount' => 5,
                'default_message' => __('Thank you for your support!','coinsnap-bitcoin-donation'),
                'redirect_url' => home_url(),
                
                'multi_amount_currency' => 'EUR',
                'multi_amount_default_snap1' => 5,
                'multi_amount_default_snap2' => 10,
                'multi_amount_default_snap3' => 25,
                'multi_amount_default_amount' => 10,
                'multi_amount_default_message' => __('Multi-currency donation','coinsnap-bitcoin-donation'),
                'multi_amount_redirect_url' => home_url(),
                
                'shoutout_currency' => 'EUR',
                'shoutout_default_amount' => 20,
                'shoutout_minimum_amount' => 5,
                'shoutout_premium_amount' => 50,
                'shoutout_default_message' => __('Great work!','coinsnap-bitcoin-donation'),
                'shoutout_redirect_url' => home_url(),
                
            ];
            $forms_options = array_merge($forms_defaults, (array) get_option('coinsnap_bitcoin_donation_forms_options', []));

            $sharedDataArray = [
                'currency' => $forms_options['currency'],
                'provider' => $provider_options['provider'],
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
            ];
            
            if($provider_options['provider'] === 'btcpay'){
                $sharedDataArray['btcpayStoreId'] = $provider_options['btcpay_store_id'];
                $sharedDataArray['btcpayApiKey'] = $provider_options['btcpay_api_key'];
                $sharedDataArray['btcpayUrl'] = $provider_options['btcpay_url'];                
            }
            else {
                $sharedDataArray['coinsnapStoreId'] = $provider_options['coinsnap_store_id'];
                $sharedDataArray['coinsnapApiKey'] = $provider_options['coinsnap_api_key'];
                
            }

            // Localize script for sharedData
            wp_enqueue_script('coinsnap-bitcoin-donation-shared-script', plugin_dir_url(__FILE__) . 'assets/js/shared.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
            wp_localize_script('coinsnap-bitcoin-donation-shared-script', 'coinsnapDonationSharedData', $sharedDataArray);

            //Localize script for popupData
            wp_enqueue_script('coinsnap-bitcoin-donation-popup-script', plugin_dir_url(__FILE__) . 'assets/js/popup.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
            wp_localize_script('coinsnap-bitcoin-donation-popup-script', 'coinsnap_bitcoin_donation_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'  => wp_create_nonce( 'coinsnap-ajax-nonce' )
            ));
            
        }  
        /*
        if((
            has_shortcode($post->post_content, 'coinsnap_bitcoin_donation') || 
            has_shortcode($post->post_content, 'coinsnap_bitcoin_donation_wide'))
        )*/{
        
            //Localize script for donationData
            wp_enqueue_script('coinsnap-bitcoin-donation-form-script', plugin_dir_url(__FILE__) . 'assets/js/donations.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
            wp_localize_script('coinsnap-bitcoin-donation-form-script', 'coinsnapDonationFormData', [
                'currency' => $forms_options['currency'],
                'defaultAmount' => $forms_options['default_amount'],
                'defaultMessage' => $forms_options['default_message'],
                'redirectUrl' => $forms_options['redirect_url'],
            ]);
        }
        /*
        if((
            has_shortcode($post->post_content, 'multi_amount_donation') || 
            has_shortcode($post->post_content, 'multi_amount_donation_wide'))
        )*/{
            // Localize script for multiData
            wp_enqueue_script('coinsnap-bitcoin-donation-multi-script',plugin_dir_url(__FILE__).'assets/js/multi.js',['jquery'],COINSNAP_BITCOIN_DONATION_VERSION, true);
            wp_localize_script('coinsnap-bitcoin-donation-multi-script', 'coinsnapDonationMultiData', [
                'snap1Amount' => $forms_options['multi_amount_default_snap1'],
                'snap2Amount' => $forms_options['multi_amount_default_snap2'],
                'snap3Amount' => $forms_options['multi_amount_default_snap3'],
                'multiCurrency' => $forms_options['multi_amount_currency'],
                'defaultMultiAmount' => $forms_options['multi_amount_default_amount'],
                'defaultMultiMessage' => $forms_options['multi_amount_default_message'],
                'redirectUrl' => $forms_options['multi_amount_redirect_url']
            ]);
            
        }

        /*if((
            has_shortcode($post->post_content, 'shoutout_form') || 
            has_shortcode($post->post_content, 'shoutout_list'))
        )*/{
        
            // Localize script for shoutoutsData
            wp_enqueue_script('coinsnap-bitcoin-donation-shoutout-script',plugin_dir_url(__FILE__).'assets/js/shoutouts.js',['jquery'],COINSNAP_BITCOIN_DONATION_VERSION, true);
            wp_localize_script('coinsnap-bitcoin-donation-shoutout-script', 'coinsnapDonationShoutoutsData', [
                'currency' => $forms_options['shoutout_currency'],
                'defaultShoutoutAmount' => $forms_options['shoutout_default_amount'],
                'minimumShoutoutAmount' => $forms_options['shoutout_minimum_amount'],
                'premiumShoutoutAmount' => $forms_options['shoutout_premium_amount'],
                'defaultShoutoutMessage' => $forms_options['shoutout_default_message'],
                'redirectUrl' => $forms_options['shoutout_redirect_url']
            ]);
        }
    }

    function coinsnap_bitcoin_donation_enqueue_admin_styles($hook){
        
        if ($hook === 'coinsnap-bitcoin-donation_page_coinsnap-bitcoin-donation-list') {
            wp_enqueue_style('coinsnap-bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            wp_enqueue_script('coinsnap-bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        }
        elseif ($hook === 'coinsnap-bitcoin-donation_page_coinsnap-bitcoin-donation-forms') {
            wp_enqueue_style('coinsnap-bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            wp_enqueue_script('coinsnap-bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        }
        elseif ($hook === 'toplevel_page_coinsnap_bitcoin_donation') {
            wp_enqueue_style('coinsnap-bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            $options = get_option('coinsnap_bitcoin_donation_options', []);
            $ngrok_url = isset($options['ngrok_url']) ? $options['ngrok_url'] : '';
            wp_enqueue_script('coinsnap-bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        }
        wp_localize_script('coinsnap-bitcoin-donation-admin-script', 'coinsnap_bitcoin_donation_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'  => wp_create_nonce( 'coinsnap-ajax-nonce' )
        ));
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
        $vars['donation-btcpay-nonce'] = wp_create_nonce('coinsnap-bitcoin-donation-btcpay-nonce');
    }
    return $vars;
});

    if(!function_exists('coinsnap_settings_update')){
    function coinsnap_settings_update($option,$data){
        
        $form_data = get_option($option, []);
        
        foreach($data as $key => $value){
            $form_data[$key] = $value;
        }
        
        update_option($option,$form_data);
    }
}

// Adding template redirect handling for donation-btcpay-settings-callback.
add_action( 'template_redirect', function(){
    
    global $wp_query;
            
    // Only continue on a donation-btcpay-settings-callback request.    
    if (!isset( $wp_query->query_vars['donation-btcpay-settings-callback'])) {
        return;
    }
    
    if(!isset($wp_query->query_vars['donation-btcpay-nonce']) || !wp_verify_nonce($wp_query->query_vars['donation-btcpay-nonce'],'coinsnap-bitcoin-donation-btcpay-nonce')){
        return;
    }

    $CoinsnapBTCPaySettingsUrl = admin_url('/admin.php?page=coinsnap_bitcoin_donation');
    
    $client = new Coinsnap_Bitcoin_Donation_Client();

            $rawData = file_get_contents('php://input');
            $form_data = get_option('coinsnap_bitcoin_donation_options', []);

            $btcpay_server_url = $form_data['btcpay_url'];
            $btcpay_api_key  = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $request_url = $btcpay_server_url.'/api/v1/stores';
            $request_headers = ['Accept' => 'application/json','Content-Type' => 'application/json','Authorization' => 'token '.$btcpay_api_key];
            $getstores = $client->remoteRequest('GET',$request_url,$request_headers);
            
            if(!isset($getstores['error'])){
                if (count($getstores['body']) < 1) {
                    $messageAbort = __('Error on verifiying redirected API Key with stored BTCPay Server url. Aborting API wizard. Please try again or continue with manual setup.', 'coinsnap-bitcoin-donation');
                    //$notice->addNotice('error', $messageAbort);
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                }
            }
                        
            // Data does get submitted with url-encoded payload, so parse $_POST here.
            if (!empty($_POST)) {
                $data['apiKey'] = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
                if(isset($_POST['permissions'])){
                    $permissions = array_map('sanitize_text_field', wp_unslash($_POST['permissions']));
                    if(is_array($permissions)){
                        foreach ($permissions as $key => $value) {
                            $data['permissions'][$key] = sanitize_text_field($permissions[$key] ?? null);
                        }
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

                    coinsnap_settings_update(
                        'coinsnap_bitcoin_donation_options',[
                        'btcpay_api_key' => $data['apiKey'],
                        'btcpay_store_id' => explode(':', $btcpay_server_permissions[0])[1],
                        'provider' => 'btcpay'
                        ]);
                    
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                    exit();
                }
                else {
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                    exit();
                }
            }

    wp_redirect($CoinsnapBTCPaySettingsUrl);
    exit();
});
