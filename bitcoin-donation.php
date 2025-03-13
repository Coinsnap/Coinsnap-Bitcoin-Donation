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
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shoutout-posts.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-voting-polls.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-crowdfundings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-public-donors.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode-wide.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode-voting.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode-multi-amount.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shortcode-multi-amount-wide.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shoutouts-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-shoutouts-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-public-donors-wall.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bitcoin-donation-webhooks.php';

register_activation_hook(__FILE__, 'bitcoin_donation_create_voting_payments_table');
register_activation_hook(__FILE__, 'bitcoin_donation_create_donation_payments_table');
register_deactivation_hook(__FILE__, 'bitcoin_donation_deactivate');

function bitcoin_donation_deactivate()
{
    flush_rewrite_rules();
}

function bitcoin_donation_create_voting_payments_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'voting_payments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        payment_id VARCHAR(255) NOT NULL,
        poll_id VARCHAR(255) NOT NULL,
        option_id INT(4) NOT NULL,
        option_title VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function bitcoin_donation_create_donation_payments_table()
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

        wp_enqueue_script('bitcoin-donation-voting-script', plugin_dir_url(__FILE__) . 'js/voting.js', ['jquery'], '1.0.0', true);

        wp_enqueue_script('bitcoin-donation-multi-script', plugin_dir_url(__FILE__) . 'js/multi.js', ['jquery'], '1.0.0', true);
        $provider_defaults = [
            'provider' => 'coinsnap',
            'coinsnap_store_id' => '',
            'coinsnap_api_key' => '',
            'btcpay_store_id' => '',
            'btcpay_api_key' => '',
            'btcpay_url' => ''
        ];
        $provider_options = array_merge($provider_defaults, (array) get_option('bitcoin_donation_options', []));

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
        $forms_options = array_merge($forms_defaults, (array) get_option('bitcoin_donation_forms_options', []));

        // Localize script for donationData
        wp_localize_script('bitcoin-donation-multi-script', 'multiData', [
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
        wp_enqueue_script('bitcoin-donation-shoutout-script', plugin_dir_url(__FILE__) . 'js/shoutouts.js', ['jquery'], '1.0.0', true);
        wp_localize_script('bitcoin-donation-shoutout-script', 'shoutoutsData', [
            'currency' => $forms_options['shoutout_currency'],
            'defaultShoutoutAmount' => $forms_options['shoutout_default_amount'],
            'minimumShoutoutAmount' => $forms_options['shoutout_minimum_amount'],
            'premiumShoutoutAmount' => $forms_options['shoutout_premium_amount'],
            'defaultShoutoutMessage' => $forms_options['shoutout_default_message'],
            'redirectUrl' => $forms_options['shoutout_redirect_url']
        ]);

        // Localize script for sharedData
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
            'multiRedirectUrl' => $forms_options['multi_amount_redirect_url'],
            'redirectUrl' => $forms_options['redirect_url'],
            'nonce' => wp_create_nonce('wp_rest')
        ]);

        //Localize script for donationData
        wp_enqueue_script('bitcoin-donation-form-script', plugin_dir_url(__FILE__) . 'js/donations.js', ['jquery'], '1.0.0', true);
        wp_localize_script('bitcoin-donation-form-script', 'formData', [
            'currency' => $forms_options['currency'],
            'defaultAmount' => $forms_options['default_amount'],
            'defaultMessage' => $forms_options['default_message'],
            'redirectUrl' => $forms_options['redirect_url'],
        ]);

        //Localize script for popupData
        wp_enqueue_script('bitcoin-donation-popup-script', plugin_dir_url(__FILE__) . 'js/popup.js', ['jquery'], '1.0.0', true);
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
            $options = get_option('bitcoin_donation_options', []);
            $ngrok_url = isset($options['ngrok_url']) ? $options['ngrok_url'] : '';
            wp_enqueue_script('bitcoin-donation-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], '1.0.0', true);
            wp_localize_script('bitcoin-donation-admin-script', 'adminData', ['webhookSecret' => $secret, 'ngrokUrl' => $ngrok_url]);
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
