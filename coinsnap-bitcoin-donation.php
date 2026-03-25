<?php
/*
 * Plugin Name:        Coinsnap Bitcoin Donation
 * Plugin URI:         https://coinsnap.io/wp-plugins/wp-bitcoin-donation/
 * Description:        Easy Bitcoin donations on a WordPress website
 * Version:            1.5.3
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

if ( ! defined( 'COINSNAP_BITCOIN_DONATION_REFERRAL_CODE' ) ) { define( 'COINSNAP_BITCOIN_DONATION_REFERRAL_CODE', 'D19833' ); }
if ( ! defined( 'COINSNAP_BITCOIN_DONATION_VERSION' ) ) { define( 'COINSNAP_BITCOIN_DONATION_VERSION', '1.5.3' ); }
if ( ! defined( 'COINSNAP_BITCOIN_DONATION_PHP_VERSION' ) ) { define( 'COINSNAP_BITCOIN_DONATION_PHP_VERSION', '8.0' ); }
if ( ! defined( 'COINSNAP_BITCOIN_DONATION_PLUGIN_DIR' ) ) { define( 'COINSNAP_BITCOIN_DONATION_PLUGIN_DIR', plugin_dir_url( __FILE__ ) ); }
if ( ! defined( 'COINSNAP_BITCOIN_DONATION_PLUGIN_PATH' ) ) { define( 'COINSNAP_BITCOIN_DONATION_PLUGIN_PATH', plugin_dir_path( __FILE__ ) ); }

// Load coinsnap-core
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'vendor/coinsnap-core/coinsnap-core.php';

// Load plugin classes
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-shoutout-posts.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-public-donors.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-settings.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-shortcode.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-shortcode-wide.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount-wide.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-shoutouts-list.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-shoutouts-form.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-webhooks.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-form-cpt.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-form-renderer.php';
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-migration.php';

register_activation_hook( __FILE__, 'coinsnap_bitcoin_donation_activate' );
register_deactivation_hook( __FILE__, 'coinsnap_bitcoin_donation_deactivate' );

function coinsnap_bitcoin_donation_activate() {
    $core = coinsnap_bitcoin_donation_get_core();
    \CoinsnapCore\Database\PaymentTable::activate( $core );
    coinsnap_bitcoin_donation_run_upgrade();

    // Register CPT before migration — init has already fired during activation
    $cpt = new Coinsnap_Bitcoin_Donation_Form_CPT();
    $cpt->register_cpt();

    Coinsnap_Bitcoin_Donation_Migration::maybe_migrate();
    flush_rewrite_rules();
}

function coinsnap_bitcoin_donation_deactivate() {
    flush_rewrite_rules();
}

function coinsnap_bitcoin_donation_get_core() {
    static $core = null;
    if ( $core === null ) {
        $core = new \CoinsnapCore\PluginInstance( array(
            'plugin_name'              => 'Bitcoin Donation',
            'option_key'               => 'coinsnap_bitcoin_donation_options',
            'webhook_key'              => 'coinsnap_donation_webhook',
            'table_suffix'             => 'coinsnap_donation_payments',
            'rest_namespace'           => 'coinsnap-bitcoin-donation/v1',
            'referral_code'            => 'D19833',
            'text_domain'              => 'coinsnap-bitcoin-donation',
            'plugin_url'               => plugin_dir_url( __FILE__ ),
            'plugin_dir'               => plugin_dir_path( __FILE__ ),
            'plugin_icon_url'          => plugin_dir_url( __FILE__ ) . 'assets/images/plugin-icon.svg',
            'menu_slug'                => 'coinsnap-bitcoin-donation',
            'log_dir_name'             => 'donation-logs',
            'log_file_name'            => 'donation.log',
            'btcpay_callback_endpoint' => 'donation-btcpay-settings-callback',
            'btcpay_app_name'          => 'CoinsnapBitcoinDonation',
            'source_column'            => 'form_type',
            'help_links'               => array(
                array( 'url' => 'https://coinsnap.io/en/coinsnap-documentation/', 'label' => 'Documentation' ),
                array( 'url' => 'https://coinsnap.io/en/support/', 'label' => 'Support' ),
                array( 'url' => 'https://app.coinsnap.io', 'label' => 'Coinsnap Dashboard' ),
            ),
        ) );
    }
    return $core;
}

function coinsnap_bitcoin_donation_run_upgrade() {
    $version_key = 'coinsnap_donation_db_version';
    $current_version = get_option( $version_key, '0' );

    if ( version_compare( $current_version, '1.5.3', '>=' ) ) {
        return;
    }

    $options = get_option( 'coinsnap_bitcoin_donation_options', array() );
    $changed = false;

    if ( isset( $options['provider'] ) && ! isset( $options['payment_provider'] ) ) {
        $options['payment_provider'] = $options['provider'];
        unset( $options['provider'] );
        $changed = true;
    }

    if ( isset( $options['btcpay_url'] ) && ! isset( $options['btcpay_host'] ) ) {
        $options['btcpay_host'] = $options['btcpay_url'];
        unset( $options['btcpay_url'] );
        $changed = true;
    }

    if ( $changed ) {
        update_option( 'coinsnap_bitcoin_donation_options', $options );
    }

    $old_secret = get_option( 'coinsnap_webhook_secret', '' );
    if ( $old_secret ) {
        $provider = $options['payment_provider'] ?? 'coinsnap';
        $webhook_data = get_option( 'coinsnap_donation_webhook', array() );
        if ( empty( $webhook_data[ $provider ]['secret'] ) ) {
            $webhook_data[ $provider ] = array( 'secret' => $old_secret );
            update_option( 'coinsnap_donation_webhook', $webhook_data );
        }
    }

    update_option( $version_key, '1.5.3' );
}

class coinsnap_bitcoin_donation {

    public function __construct() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        if ( is_admin() ) {
            add_action( 'wp_ajax_coinsnap_bitcoin_donation_btcpay_apiurl_handler', array( $this, 'btcpayApiUrlHandler' ) );
            add_action( 'wp_ajax_coinsnap_bitcoin_donation_connection_handler', array( $this, 'coinsnapConnectionHandler' ) );
            add_action( 'wp_ajax_coinsnap_bitcoin_donation_reregister_webhook', array( $this, 'reregisterWebhook' ) );
        }

        $core = coinsnap_bitcoin_donation_get_core();
        add_action( 'admin_init', function () use ( $core ) {
            \CoinsnapCore\Admin\SettingsPage::register_for( $core );
        } );

        add_action( 'admin_notices', array( $this, 'maybe_register_webhooks' ) );
        add_action( 'admin_notices', function () use ( $core ) {
            \CoinsnapCore\Admin\SettingsPage::maybe_show_setup_notice( $core );
        } );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'coinsnap-bitcoin-donation', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function btcpayApiUrlHandler() {
        \CoinsnapCore\Admin\AjaxHandlers::handle_btcpay_url( coinsnap_bitcoin_donation_get_core() );
    }

    public function coinsnapConnectionHandler() {
        \CoinsnapCore\Admin\AjaxHandlers::handle_connection_check( coinsnap_bitcoin_donation_get_core() );
    }

    public function reregisterWebhook() {
        $nonce = filter_input( INPUT_POST, 'apiNonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( ! wp_verify_nonce( $nonce, 'coinsnap-ajax-nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $core = coinsnap_bitcoin_donation_get_core();
        $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        $provider_name = $settings['payment_provider'] ?? 'coinsnap';

        // Clear stored webhook and transients
        delete_option( $core->webhook_key() );
        $webhook_transient = 'coinsnap_donation_wh_' . md5( $provider_name . ( $settings['coinsnap_store_id'] ?? '' ) );
        delete_transient( $webhook_transient );

        try {
            $provider = \CoinsnapCore\Util\ProviderFactory::create( $core );
            $result = $provider->register_webhook();

            if ( ! isset( $result['error'] ) && isset( $result['result'] ) ) {
                $stored = array();
                $stored[ $provider_name ] = array(
                    'id'     => $result['result']['id'],
                    'secret' => $result['result']['secret'],
                    'url'    => $result['result']['url'],
                );
                update_option( $core->webhook_key(), $stored );
                wp_send_json_success( array(
                    'message' => 'Webhook registered successfully',
                    'url'     => $result['result']['url'],
                    'id'      => $result['result']['id'],
                ) );
            } else {
                wp_send_json_error( $result['message'] ?? 'Registration failed' );
            }
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    public function maybe_register_webhooks() {
        $core = coinsnap_bitcoin_donation_get_core();
        $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        $provider_name = $settings['payment_provider'] ?? 'coinsnap';
        $api_key = ( 'btcpay' === $provider_name ) ? ( $settings['btcpay_api_key'] ?? '' ) : ( $settings['coinsnap_api_key'] ?? '' );
        $store_id = ( 'btcpay' === $provider_name ) ? ( $settings['btcpay_store_id'] ?? '' ) : ( $settings['coinsnap_store_id'] ?? '' );

        if ( empty( $api_key ) || empty( $store_id ) ) {
            return;
        }

        // Check webhook separately — use its own transient so it's not blocked by connection check
        $webhook_transient = 'coinsnap_donation_wh_' . md5( $provider_name . $store_id );
        if ( false === get_transient( $webhook_transient ) ) {
            try {
                $provider = \CoinsnapCore\Util\ProviderFactory::create( $core );
                $store = $provider->get_store();

                if ( isset( $store['code'] ) && $store['code'] === 200 ) {
                    if ( ! $provider->check_webhook() ) {
                        $result = $provider->register_webhook();
                        if ( ! isset( $result['error'] ) && isset( $result['result'] ) ) {
                            $stored = get_option( $core->webhook_key(), array() );
                            $stored[ $provider_name ] = array(
                                'id'     => $result['result']['id'],
                                'secret' => $result['result']['secret'],
                                'url'    => $result['result']['url'],
                            );
                            update_option( $core->webhook_key(), $stored );
                        }
                    }
                    // Webhook checked/registered — don't check again for 1 hour
                    set_transient( $webhook_transient, 'checked', HOUR_IN_SECONDS );
                }
            } catch ( \Exception $e ) {
                // Retry in 5 minutes on error
                set_transient( $webhook_transient, 'error', 5 * MINUTE_IN_SECONDS );
            }
        }
    }

    public function enqueue_frontend_scripts() {
        $core = coinsnap_bitcoin_donation_get_core();
        $core_settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );

        wp_enqueue_style( 'coinsnap-bitcoin-donation-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), COINSNAP_BITCOIN_DONATION_VERSION );
        wp_enqueue_style( 'coinsnap-bitcoin-donation-style-wide', plugin_dir_url( __FILE__ ) . 'assets/css/style-wide.css', array(), COINSNAP_BITCOIN_DONATION_VERSION );
        wp_enqueue_style( 'coinsnap-bitcoin-donation-shoutouts', plugin_dir_url( __FILE__ ) . 'assets/css/shoutouts.css', array(), COINSNAP_BITCOIN_DONATION_VERSION );

        $shared_data = array(
            'restUrl' => esc_url_raw( get_rest_url( null, 'coinsnap-bitcoin-donation/v1/' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'theme'   => $core_settings['theme'] ?? 'light',
        );

        wp_enqueue_script( 'coinsnap-bitcoin-donation-shared-script', plugin_dir_url( __FILE__ ) . 'assets/js/shared.js', array( 'jquery' ), COINSNAP_BITCOIN_DONATION_VERSION, true );
        wp_localize_script( 'coinsnap-bitcoin-donation-shared-script', 'coinsnapDonationSharedData', $shared_data );

        wp_enqueue_script( 'coinsnap-bitcoin-donation-popup-script', plugin_dir_url( __FILE__ ) . 'assets/js/popup.js', array( 'jquery', 'coinsnap-bitcoin-donation-shared-script' ), COINSNAP_BITCOIN_DONATION_VERSION, true );

        // Form-specific config is now provided via data-* attributes on each form container.
        wp_enqueue_script( 'coinsnap-bitcoin-donation-form-script', plugin_dir_url( __FILE__ ) . 'assets/js/donations.js', array( 'jquery', 'coinsnap-bitcoin-donation-popup-script' ), COINSNAP_BITCOIN_DONATION_VERSION, true );
        wp_enqueue_script( 'coinsnap-bitcoin-donation-multi-script', plugin_dir_url( __FILE__ ) . 'assets/js/multi.js', array( 'jquery', 'coinsnap-bitcoin-donation-popup-script' ), COINSNAP_BITCOIN_DONATION_VERSION, true );
        wp_enqueue_script( 'coinsnap-bitcoin-donation-shoutout-script', plugin_dir_url( __FILE__ ) . 'assets/js/shoutouts.js', array( 'jquery', 'coinsnap-bitcoin-donation-popup-script' ), COINSNAP_BITCOIN_DONATION_VERSION, true );
    }

    public function enqueue_admin_scripts( $hook ) {
        // Load assets on all donation-form CPT screens (list + edit + new).
        $is_cpt_edit = in_array( $hook, array( 'post.php', 'post-new.php' ), true )
            && get_post_type() === 'donation-form';
        $is_cpt_list = $hook === 'edit.php' && get_post_type() === 'donation-form';

        if ( $is_cpt_edit || $is_cpt_list ) {
            wp_register_style( 'coinsnap-core-admin', plugin_dir_url( __FILE__ ) . 'vendor/coinsnap-core/assets/css/admin.css', array(), COINSNAP_CORE_VERSION );
            wp_enqueue_style( 'coinsnap-core-admin' );
            wp_enqueue_style( 'coinsnap-donation-form-admin', plugin_dir_url( __FILE__ ) . 'assets/css/donation-form-admin.css', array( 'coinsnap-core-admin' ), COINSNAP_BITCOIN_DONATION_VERSION );
        }

        if ( $is_cpt_edit ) {
            wp_enqueue_script( 'coinsnap-donation-form-admin', plugin_dir_url( __FILE__ ) . 'assets/js/donation-form-admin.js', array( 'jquery' ), COINSNAP_BITCOIN_DONATION_VERSION, true );
        }

        if ( $is_cpt_edit || $is_cpt_list ) {
            return; // Don't load the other admin scripts on CPT screens.
        }

        $page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $page = is_string( $page ) ? $page : '';

        if ( stripos( $page, 'coinsnap-bitcoin-donation' ) === false && stripos( $page, 'coinsnap_bitcoin_donation' ) === false ) {
            return;
        }

        $core = coinsnap_bitcoin_donation_get_core();

        wp_register_style( 'coinsnap-core-admin', plugin_dir_url( __FILE__ ) . 'vendor/coinsnap-core/assets/css/admin.css', array(), COINSNAP_CORE_VERSION );
        wp_register_script( 'coinsnap-core-admin', plugin_dir_url( __FILE__ ) . 'vendor/coinsnap-core/assets/js/admin.js', array( 'jquery' ), COINSNAP_CORE_VERSION, true );

        wp_localize_script( 'coinsnap-core-admin', 'CoinsnapCoreAdmin', array(
            'option_key'        => $core->option_key(),
            'ajax_url'          => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'coinsnap-ajax-nonce' ),
            'connection_action' => 'coinsnap_bitcoin_donation_connection_handler',
            'btcpay_action'     => 'coinsnap_bitcoin_donation_btcpay_apiurl_handler',
            'webhook_action'    => 'coinsnap_bitcoin_donation_reregister_webhook',
        ) );

        wp_enqueue_style( 'coinsnap-core-admin' );
        wp_enqueue_script( 'coinsnap-core-admin' );

        wp_enqueue_style( 'coinsnap-bitcoin-donation-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css', array(), COINSNAP_BITCOIN_DONATION_VERSION );
        wp_enqueue_script( 'coinsnap-bitcoin-donation-admin-script', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array( 'jquery' ), COINSNAP_BITCOIN_DONATION_VERSION, true );
    }
}

new coinsnap_bitcoin_donation();
new Coinsnap_Bitcoin_Donation_Form_CPT();
new Coinsnap_Bitcoin_Donation_Form_Renderer();

add_action( 'plugins_loaded', function () {
    $core = coinsnap_bitcoin_donation_get_core();
    \CoinsnapCore\Auth\BTCPayAuthorizer::register_callback( $core );
}, 20 );

add_action( 'admin_init', function () {
    coinsnap_bitcoin_donation_run_upgrade();
} );

add_action( 'admin_init', function () {
    Coinsnap_Bitcoin_Donation_Migration::maybe_migrate();
} );
