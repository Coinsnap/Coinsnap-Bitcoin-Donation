<?php
/*
 * Plugin Name:        Coinsnap Bitcoin Donation
 * Plugin URI:         https://coinsnap.io
 * Description:        Easy Bitcoin donations on a WordPress website
 * Version:            1.1.0
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
    define( 'COINSNAP_BITCOIN_DONATION_VERSION', '1.1.0' );
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
    }

    function coinsnap_bitcoin_donation_enqueue_scripts()
    {
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
            wp_localize_script('coinsnap-bitcoin-donation-admin-script', 'adminData', [ 'ngrokUrl' => $ngrok_url]);
        }
    }

    function coinsnap_bitcoin_donation_verify_nonce($nonce, $action)
    {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(esc_html__('Security check failed', 'coinsnap-bitcoin-donation'));
        }
    }
}
new coinsnap_bitcoin_donation();
