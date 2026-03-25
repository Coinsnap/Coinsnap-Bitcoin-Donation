<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
    }

    public function register_admin_menu() {
        $core = coinsnap_bitcoin_donation_get_core();

        $render_settings = function () use ( $core ) {
            \CoinsnapCore\Admin\SettingsPage::render_page_for( $core );
        };

        $cpt_url = 'edit.php?post_type=donation-form';

        // Parent menu opens CPT list directly
        add_menu_page(
            __( 'Coinsnap Bitcoin Donation', 'coinsnap-bitcoin-donation' ),
            __( 'Coinsnap Bitcoin Donation', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            $cpt_url,
            '',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/bitcoin.svg',
            100
        );

        // --- Plugin-specific pages first ---

        // First submenu replaces auto-generated parent label
        add_submenu_page(
            $cpt_url,
            __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
            __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            $cpt_url
        );

        add_submenu_page(
            $cpt_url,
            __( 'Add New Form', 'coinsnap-bitcoin-donation' ),
            __( 'Add New Form', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'post-new.php?post_type=donation-form'
        );

        add_submenu_page(
            $cpt_url,
            __( 'Shoutouts', 'coinsnap-bitcoin-donation' ),
            __( 'Shoutouts', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'edit.php?post_type=bitcoin-shoutouts'
        );

        add_submenu_page(
            $cpt_url,
            __( 'Donor Information', 'coinsnap-bitcoin-donation' ),
            __( 'Donor Information', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'edit.php?post_type=bitcoin-pds'
        );

        // --- Core pages ---

        add_submenu_page(
            $cpt_url,
            __( 'Transactions', 'coinsnap-bitcoin-donation' ),
            __( 'Transactions', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'coinsnap-bitcoin-donation-transactions',
            function () use ( $core ) {
                \CoinsnapCore\Admin\TransactionsPage::render_page_for( $core );
            }
        );

        add_submenu_page(
            $cpt_url,
            __( 'Settings', 'coinsnap-bitcoin-donation' ),
            __( 'Settings', 'coinsnap-bitcoin-donation' ),
            'manage_options',
            'coinsnap-bitcoin-donation-settings',
            $render_settings
        );

        add_submenu_page(
            $cpt_url,
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

        // Hidden page for old URL — redirects to CPT list
        // WordPress checks page exists before admin_init, so we must register it
        add_submenu_page(
            null, // hidden, no parent
            '',
            '',
            'manage_options',
            'coinsnap-bitcoin-donation',
            function () {
                wp_safe_redirect( admin_url( 'edit.php?post_type=donation-form' ) );
                exit;
            }
        );
    }
}

new Coinsnap_Bitcoin_Donation_Settings();
