<?php
/*
 * Plugin Name:        Bitcoin Donation
 * Plugin URI:         https://coinsnap.io
 * Description:        Easy Bitcoin donations on a WordPress website
 * Version:            1.0.0
 * Author:             Coinsnap
 * Author URI:         https://coinsnap.io/
 * Text Domain:        bitcoin-donation
 * Domain Path:         /languages
 * Tested up to:        6.7
 * License:             GPL2
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:             true
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BITCOIN_DONATION_REFERRAL_CODE' ) ) {
    define( 'BITCOIN_DONATION_REFERRAL_CODE', 'D19833' );
}
if ( ! defined( 'BITCOIN_DONATION_VERSION' ) ) {
    define( 'BITCOIN_DONATION_VERSION', '1.0.0' );
}
if ( ! defined( 'BITCOIN_DONATION_PHP_VERSION' ) ) {
    define( 'BITCOIN_DONATION_PHP_VERSION', '8.0' );
}
if( ! defined( 'BITCOIN_DONATION_PLUGIN_DIR' ) ){
    define('BITCOIN_DONATION_PLUGIN_DIR',plugin_dir_url(__FILE__));
}

// Plugin settings
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode-wide.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-list.php';

class Bitcoin_Donation {
    
    public function __construct()
    {

        add_action('wp_enqueue_scripts', [$this, 'bitcoin_donation_enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'bitcoin_donation_enqueue_admin_styles']);
    }

    function bitcoin_donation_enqueue_scripts()
    {
        wp_enqueue_style('bitcoin-donation-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], BITCOIN_DONATION_VERSION);
        wp_enqueue_style('bitcoin-donation-style-wide', plugin_dir_url(__FILE__) . 'assets/css/style-wide.css', [], BITCOIN_DONATION_VERSION);
        wp_enqueue_script('bitcoin-donation-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], BITCOIN_DONATION_VERSION, true);
        $options = get_option('bitcoin_donation_options');
        wp_localize_script('bitcoin-donation-script', 'bitcoinDonationData', [
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
    function bitcoin_donation_enqueue_admin_styles($hook)
    {
        // Only load on the settings page for the plugin
        if ($hook === 'bitcoin-donations_page_bitcoin-donation-donation-list') {
            wp_enqueue_style('bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], BITCOIN_DONATION_VERSION);
        }

        if ($hook === 'toplevel_page_bitcoin_donation') {
            wp_enqueue_style('bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], BITCOIN_DONATION_VERSION);
            wp_enqueue_script('bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], BITCOIN_DONATION_VERSION, true);
        }
    }

    function bitcoin_donation_verify_nonce($nonce, $action)
    {
        if (!wp_verify_nonce($nonce, $action)) {
                wp_die(esc_html__('Security check failed', 'bitcoin-donation'));
        }
    }
}
new Bitcoin_Donation();
