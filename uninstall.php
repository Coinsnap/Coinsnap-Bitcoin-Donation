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
    'coinsnap_donation_webhook',
    'coinsnap_donation_db_version',
    'coinsnap_donation_migrated_forms',
    'coinsnap_donation_forms_migrated',
    'coinsnap_donation_defaults_created',
    'coinsnap_donation_webhook_rotated',
);
// coinsnap_webhook_secret is shared with other Coinsnap plugins and is left alone.

foreach ( $options as $option ) {
    delete_option( $option );
}

// Donor details saved for invoices that were never paid.
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'coinsnap_bitcoin_donation_donor_' ) . '%' ) );

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
