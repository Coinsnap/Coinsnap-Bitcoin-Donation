<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class-coinsnap-bitcoin-donation-forms.php';

class Coinsnap_Bitcoin_Donation_Settings {

    private $donation_forms;

    public function __construct() {
        $this->donation_forms = new Coinsnap_Bitcoin_Donation_Forms();
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
    }

    public function register_admin_menu() {
        $core = coinsnap_bitcoin_donation_get_core();

        $render_settings = function () use ( $core ) {
            \CoinsnapCore\Admin\SettingsPage::render_page_for( $core );
        };

        $render_forms = array( $this->donation_forms, 'render_donation_forms_page' );

        // Parent menu opens Donation Forms (plugin-specific content first)
        add_menu_page(
            __( 'Coinsnap Bitcoin Donation', 'coinsnap-bitcoin-donation' ),
            __( 'Coinsnap Bitcoin Donation', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'coinsnap-bitcoin-donation',
            $render_forms,
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/bitcoin.svg',
            100
        );

        // --- Plugin-specific pages first ---

        // First submenu replaces auto-generated parent label
        add_submenu_page(
            'coinsnap-bitcoin-donation',
            __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
            __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'coinsnap-bitcoin-donation'
        );

        $options = get_option( 'coinsnap_bitcoin_donation_forms_options', array() );
        $shoutout_active = isset( $options['shoutout_donation_active'] ) ? $options['shoutout_donation_active'] : false;

        if ( $shoutout_active ) {
            add_submenu_page(
                'coinsnap-bitcoin-donation',
                __( 'Shoutouts', 'coinsnap-bitcoin-donation' ),
                __( 'Shoutouts', 'coinsnap-bitcoin-donation' ),
                'manage_options',
                'edit.php?post_type=bitcoin-shoutouts'
            );
        }

        add_submenu_page(
            'coinsnap-bitcoin-donation',
            __( 'Donor Information', 'coinsnap-bitcoin-donation' ),
            __( 'Donor Information', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'edit.php?post_type=bitcoin-pds'
        );

        // --- Core pages ---

        add_submenu_page(
            'coinsnap-bitcoin-donation',
            __( 'Transactions', 'coinsnap-bitcoin-donation' ),
            __( 'Transactions', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'coinsnap-bitcoin-donation-transactions',
            function () use ( $core ) {
                \CoinsnapCore\Admin\TransactionsPage::render_page_for( $core );
            }
        );

        add_submenu_page(
            'coinsnap-bitcoin-donation',
            __( 'Settings', 'coinsnap-bitcoin-donation' ),
            __( 'Settings', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'coinsnap-bitcoin-donation-settings',
            $render_settings
        );

        add_submenu_page(
            'coinsnap-bitcoin-donation',
            __( 'Logs', 'coinsnap-bitcoin-donation' ),
            __( 'Logs', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'coinsnap-bitcoin-donation-logs',
            function () use ( $core ) {
                $logger = new \CoinsnapCore\Util\Logger(
                    'donation-logs',
                    'donation.log',
                    \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core )['log_level'] ?? 'error'
                );
                \CoinsnapCore\Admin\LogsPage::render_page_for( $core, $logger );
            }
        );
    }
}

new Coinsnap_Bitcoin_Donation_Settings();
