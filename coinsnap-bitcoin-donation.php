<?php
/*
 * Plugin Name:        Coinsnap Bitcoin Donation
 * Plugin URI:         https://coinsnap.io
 * Description:        Easy Bitcoin donations on a WordPress website
 * Version:            1.0.0
 * Author:             Coinsnap
 * Author URI:         https://coinsnap.io/
 * Text Domain:        coinsnap-bitcoin-donation
 * Domain Path:         /languages
 * Tested up to:        6.7
 * License:             GPL2
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:             true
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'COINSNAP_BITCOIN_DONATION_REFERRAL_CODE' ) ) {
    define( 'COINSNAP_BITCOIN_DONATION_REFERRAL_CODE', 'D19833' );
}
if ( ! defined( 'COINSNAP_BITCOIN_DONATION_VERSION' ) ) {
    define( 'COINSNAP_BITCOIN_DONATION_VERSION', '1.0.0' );
}
if ( ! defined( 'COINSNAP_BITCOIN_DONATION_PHP_VERSION' ) ) {
    define( 'COINSNAP_BITCOIN_DONATION_PHP_VERSION', '8.0' );
}
if( ! defined( 'COINSNAP_BITCOIN_DONATION_PLUGIN_DIR' ) ){
    define('COINSNAP_BITCOIN_DONATION_PLUGIN_DIR',plugin_dir_url(__FILE__));
}

// Plugin settings
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-shortcode-wide.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-donation-list.php';

class Coinsnap_Bitcoin_Donation {
    
    public function __construct()
    {

        add_action('wp_enqueue_scripts', [$this, 'coinsnap_bitcoin_donation_enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'coinsnap_bitcoin_donation_enqueue_admin_styles']);
    }

    function coinsnap_bitcoin_donation_enqueue_scripts()
    {
        wp_enqueue_style('coinsnap-bitcoin-donation-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
        wp_enqueue_style('coinsnap-bitcoin-donation-style-wide', plugin_dir_url(__FILE__) . 'assets/css/style-wide.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
        wp_enqueue_script('coinsnap-bitcoin-donation-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        $options = get_option('coinsnap_bitcoin_donation_options');
        wp_localize_script('coinsnap-bitcoin-donation-script', 'bitcoinDonationData', [
            'currency' => $options['currency'],
            'provider' => $options['provider'],
            'coinsnapStoreId' => $options['coinsnap_store_id'],
            'coinsnapApiKey' => $options['coinsnap_api_key'],
            'btcpayStoreId' => $options['btcpay_store_id'],
            'btcpayApiKey' => $options['btcpay_api_key'],
            'btcpayUrl' => $options['btcpay_url'],
            'defaultAmount' => $options['default_amount'],
            'defaultMessage' => $options['default_message'],
            'redirectUrl' => $options['redirect_url']
        ]);
    }
    function coinsnap_bitcoin_donation_enqueue_admin_styles($hook)
    {
        // Only load on the settings page for the plugin
        if ($hook === 'coinsnap-donations_page_coinsnap-donation-donation-list') {
            wp_enqueue_style('coinsnap-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
        }

        if ($hook === 'toplevel_page_coinsnap_bitcoin_donation') {
            wp_enqueue_style('coinsnap-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_DONATION_VERSION);
            wp_enqueue_script('coinsnap-donation-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], COINSNAP_BITCOIN_DONATION_VERSION, true);
        }
    }

    function coinsnap_donation_verify_nonce($nonce, $action)
    {
        if (!wp_verify_nonce($nonce, $action)) {
                wp_die(esc_html__('Security check failed', 'coinsnap-bitcoin-donation'));
        }
    }
}
new Coinsnap_Bitcoin_Donation();
