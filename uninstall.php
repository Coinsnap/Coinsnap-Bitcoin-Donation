<?php
if (!defined('ABSPATH')){ exit; }
if (!defined('WP_UNINSTALL_PLUGIN')){ exit; }

global $wpdb;
$coinsnap_bitcoin_donation_tables = array(
    $wpdb->prefix . 'donation_payments'
);

foreach ($coinsnap_bitcoin_donation_tables as $coinsnap_bitcoin_donation_table) {
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s",$coinsnap_bitcoin_donation_table));
}

$coinsnap_bitcoin_donation_options = array(
    'coinsnap_bitcoin_donation_options',
    'coinsnap_bitcoin_donation_forms_options',
    'coinsnap_webhook_secret'
);

foreach ($coinsnap_bitcoin_donation_options as $coinsnap_bitcoin_donation_option) {
    delete_option($coinsnap_bitcoin_donation_option);
}
