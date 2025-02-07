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
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-post-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode-wide.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shoutouts-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shoutouts-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-webhooks.php';

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
        wp_enqueue_style('bitcoin-donation-shoutouts', plugin_dir_url(__FILE__) . 'styles/shoutouts.css', [], '1.0.0');

        wp_enqueue_script('bitcoin-donation-script', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery'], '1.0.0', true);
        $provider_options = get_option('bitcoin_donation_options');
        $forms_options = get_option('bitcoin_donation_forms_options');
        wp_localize_script('bitcoin-donation-script', 'donationData', [
            'currency' => $forms_options['currency'],
            'defaultAmount' => $forms_options['default_amount'],
            'defaultMessage' => $forms_options['default_message'],
            'redirectUrl' => $forms_options['redirect_url']

        ]);
        wp_enqueue_script('bitcoin-donation-shoutout-script', plugin_dir_url(__FILE__) . 'js/shoutouts.js', ['jquery'], '1.0.0', true);
        wp_localize_script('bitcoin-donation-shoutout-script', 'shoutoutsData', [
            'currency' => $forms_options['shoutout_currency'],
            'defaultShoutoutAmount' => $forms_options['shoutout_default_amount'],
            'minimumShoutoutAmount' => $forms_options['shoutout_minimum_amount'],
            'premiumShoutoutAmount' => $forms_options['shoutout_premium_amount'],
            'defaultShoutoutMessage' => $forms_options['shoutout_default_message'],
            'shoutoutRedirectUrl' => $forms_options['shoutout_redirect_url'],
        ]);
        wp_enqueue_script('bitcoin-donation-shared-script', plugin_dir_url(__FILE__) . 'js/shared.js', ['jquery'], '1.0.0', true);
        wp_localize_script('bitcoin-donation-shared-script', 'sharedData', [
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
            'redirectUrl' => $forms_options['redirect_url'],
            'nonce' => wp_create_nonce('wp_rest')
        ]);
    }

    private function get_webhook_secret()
    {
        $option_name = 'coinsnap_webhook_secret';
        $secret = get_option($option_name);

        if (!$secret) {
            $secret = bin2hex(random_bytes(16));
            add_option($option_name, $secret, '', false);
        }

        return $secret;
    }


    function bitcoin_donation_enqueue_admin_styles($hook)
    {
        if ($hook === 'bitcoin-donations_page_bitcoin-donation-donation-list') {
            wp_enqueue_style('bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'styles/admin-style.css', [], '1.0.0');
        } else if ($hook === 'bitcoin-donations_page_bitcoin-donation-donation-forms') {
            wp_enqueue_style('bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'styles/admin-style.css', [], '1.0.0');
            wp_enqueue_script('bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], '1.0.0', true);
        } else if ($hook === 'toplevel_page_bitcoin_donation') {
            wp_enqueue_style('bitcoin-donation-admin-style', plugin_dir_url(__FILE__) . 'styles/admin-style.css', [], '1.0.0');
            $secret = $this->get_webhook_secret();
            wp_enqueue_script('bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], '1.0.0', true);
            wp_localize_script('bitcoin-donation-admin-script', 'adminData', ['webhookSecret' => $secret]);
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
