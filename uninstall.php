<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- uninstall.php is a standalone script; all variables are local to this file, not plugin globals.
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

global $wpdb;

// Drop both old and new tables
$tables = array(
    $wpdb->prefix . 'donation_payments',
    $wpdb->prefix . 'coinsnap_donation_payments',
);

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- DROP TABLE in uninstall.php is intentional schema cleanup; no WP API exists for this; caching is irrelevant; table name from $wpdb->prefix.
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// Delete all options
$options = array(
    'coinsnap_bitcoin_donation_options',
    'coinsnap_bitcoin_donation_forms_options',
    'coinsnap_webhook_secret',
    'coinsnap_donation_webhook',
    'coinsnap_donation_db_version',
    'coinsnap_donation_migrated_forms',
    'coinsnap_donation_forms_migrated',
    'coinsnap_donation_defaults_created',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Delete all donation-form CPT posts
$donation_forms = get_posts( array(
    'post_type'      => 'donation-form',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
) );
foreach ( $donation_forms as $form_id ) {
    wp_delete_post( $form_id, true );
}
