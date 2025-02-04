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
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shoutouts-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shoutouts-form.php';

class Bitcoin_Donation
{
    public function __construct()
    {

        add_action('wp_enqueue_scripts', [$this, 'bitcoin_donation_enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'bitcoin_donation_enqueue_admin_styles']);
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
    }

    function bitcoin_donation_enqueue_scripts()
    {
        wp_enqueue_style('bitcoin-donation-style', plugin_dir_url(__FILE__) . 'styles/style.css', [], '1.0.0');
        wp_enqueue_style('bitcoin-donation-style-wide', plugin_dir_url(__FILE__) . 'styles/style-wide.css', [], '1.0.0');
        wp_enqueue_style('bitcoin-donation-shoutouts', plugin_dir_url(__FILE__) . 'styles/shoutouts.css', [], '1.0.0');

        wp_enqueue_script('bitcoin-donation-script', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery'], '1.0.0', true);
        $options = get_option('bitcoin_donation_options');
        wp_localize_script('bitcoin-donation-script', 'donationData', [
            'currency' => $options['currency'],
            'defaultAmount' => $options['default_amount'],
            'defaultMessage' => $options['default_message'],
            'redirectUrl' => $options['redirect_url']

        ]);
        wp_enqueue_script('bitcoin-donation-shoutout-script', plugin_dir_url(__FILE__) . 'js/shoutouts.js', ['jquery'], '1.0.0', true);
        wp_localize_script('bitcoin-donation-shoutout-script', 'shoutoutsData', [
            'currency' => $options['currency'],
            'defaultShoutoutAmount' => $options['shoutout_default_amount'],
            'minimumShoutoutAmount' => $options['shoutout_minimum_amount'],
            'premiumShoutoutAmount' => $options['shoutout_premium_amount'],
            'defaultShoutoutMessage' => $options['shoutout_default_message'],
            'shoutoutRedirectUrl' => $options['shoutout_redirect_url'],
        ]);
        wp_enqueue_script('bitcoin-donation-shared-script', plugin_dir_url(__FILE__) . 'js/shared.js', ['jquery'], '1.0.0', true);
        wp_localize_script('bitcoin-donation-shared-script', 'sharedData', [
            'currency' => $options['currency'],
            'provider' => $options['provider'],
            'coinsnapStoreId' => $options['coinsnap_store_id'],
            'coinsnapApiKey' => $options['coinsnap_api_key'],
            'btcpayStoreId' => $options['btcpay_store_id'],
            'btcpayApiKey' => $options['btcpay_api_key'],
            'btcpayUrl' => $options['btcpay_url'],
            'defaultAmount' => $options['default_amount'],
            'defaultMessage' => $options['default_message'],
            'defaultShoutoutAmount' => $options['shoutout_default_amount'],
            'defaultShoutoutMessage' => $options['shoutout_default_message'],
            'minimumShoutoutAmount' => $options['shoutout_minimum_amount'],
            'premiumShoutoutAmount' => $options['shoutout_premium_amount'],
            'shoutoutRedirectUrl' => $options['shoutout_redirect_url'],
            'redirectUrl' => $options['redirect_url'],
            'nonce' => wp_create_nonce('wp_rest')
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

    public function register_webhook_endpoint()
    {
        register_rest_route('bitcoin-donation/v1', '/webhook', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_webhook'], // Use class method
            'permission_callback' => '__return_true' // Add security later
        ]);
    }

    public function handle_webhook(WP_REST_Request $request)
    {
        $secret = 'topsecret';
        $signature_header = $request->get_header('X-Coinsnap-Sig');

        if (empty($signature_header)) {
            error_log('Bitcoin Donation: Missing signature header');
            return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->get_body();
        $computed_signature = hash_hmac('sha256', $payload, $secret);
        $computed_signature = 'sha256=' . $computed_signature; // Prefix the computed_signature with 'sha256='
        if (!hash_equals($computed_signature, $signature_header)) {
            error_log('Bitcoin Donation: Invalid signature');
            return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        }

        $payload_data = $request->get_json_params();

        // Log the payload for testing
        file_put_contents(
            WP_CONTENT_DIR . '/uploads/webhook_log.txt',
            "Verified webhook: " . print_r($payload_data, true) . "\n",
            FILE_APPEND
        );


        return new WP_REST_Response(['status' => 'success'], 200);
    }
}
new Bitcoin_Donation();
