<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

global $wpdb;

// Drop both old and new tables
$tables = array(
    $wpdb->prefix . 'donation_payments',
    $wpdb->prefix . 'coinsnap_donation_payments',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// Delete all options
$options = array(
    'coinsnap_bitcoin_donation_options',
    'coinsnap_bitcoin_donation_forms_options',
    'coinsnap_webhook_secret',
    'coinsnap_donation_webhook',
    'coinsnap_donation_db_version',
);

foreach ( $options as $option ) {
    delete_option( $option );
}
