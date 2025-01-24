<?php
/*
Plugin Name: Bitcoin Donation
Description: Easy Bitcoin donations on a WordPress website
Version: 0.1
Author: Coinsnap Dev
*/

if (!defined('ABSPATH')) {
    exit;
}

// Plugin settings
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode-wide.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-list.php';

class Bitcoin_Donation
{
    public function __construct()
    {

        add_action('wp_enqueue_scripts', [$this, 'bitcoin_donation_enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'bitcoin_donation_enqueue_admin_styles']);
    }

    function bitcoin_donation_enqueue_scripts()
    {
        wp_enqueue_style('bitcoin-donation-style', plugin_dir_url(__FILE__) . 'styles/style.css', [], '1.0.0');
        wp_enqueue_style('bitcoin-donation-style-wide', plugin_dir_url(__FILE__) . 'styles/style-wide.css', [], '1.0.0');
        wp_enqueue_script('bitcoin-donation-script', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery'], '1.0.0', true);
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
            wp_enqueue_style('bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'styles/admin-style.css', [], '1.0.0');
        }

        if ($hook === 'toplevel_page_bitcoin_donation') {
            wp_enqueue_style('bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'styles/admin-style.css', [], '1.0.0');
            wp_enqueue_script('bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], '1.0.0', true);
        }
    }

    function bitcoin_donation_verify_nonce($nonce, $action)
    {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(__('Security check failed', 'bitcoin_donation'));
        }
    }
}
new Bitcoin_Donation();
