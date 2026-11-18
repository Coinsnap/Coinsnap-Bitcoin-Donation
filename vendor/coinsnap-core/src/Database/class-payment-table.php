<?php
/**
 * Payment table schema management.
 *
 * @package coinsnap-core
 */

declare(strict_types=1);

namespace CoinsnapCore\Database;

use CoinsnapCore\PluginInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the payment transactions database table.
 * Each consuming plugin has its own table (via PluginInstance::table_suffix()).
 */
class PaymentTable {

    /**
     * Get the full table name for a plugin instance.
     *
     * @param PluginInstance $instance   Plugin config.
     * @param \wpdb|null     $wpdb_param Optional wpdb instance.
     * @return string Full table name with prefix.
     */
    public static function table_name( PluginInstance $instance, $wpdb_param = null ): string {
        global $wpdb;
        $db = $wpdb_param ? $wpdb_param : $wpdb;
        return $db->prefix . $instance->table_suffix();
    }

    /**
     * Create or update the table schema.
     * Call this on plugin activation from the consuming plugin.
     *
     * @param PluginInstance $instance Plugin config.
     */
    public static function activate( PluginInstance $instance ): void {
        global $wpdb;
        $table           = self::table_name( $instance, $wpdb );
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source_id BIGINT UNSIGNED NOT NULL,
            transaction_id VARCHAR(190) NOT NULL,
            invoice_number VARCHAR(190) NULL,
            customer_name VARCHAR(190) NOT NULL,
            customer_email VARCHAR(190) NOT NULL,
            customer_company VARCHAR(190) NULL,
            amount DOUBLE NOT NULL,
            currency VARCHAR(10) NOT NULL,
            description TEXT NULL,
            payment_provider VARCHAR(50) NOT NULL,
            payment_invoice_id VARCHAR(190) NOT NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
            payment_url TEXT NULL,
            ip VARCHAR(64) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY source_id (source_id),
            KEY transaction_id (transaction_id),
            KEY invoice_number (invoice_number),
            KEY customer_email (customer_email),
            KEY payment_status (payment_status),
            KEY payment_provider (payment_provider),
            UNIQUE KEY payment_invoice_id (payment_invoice_id)
        ) $charset_collate;";

        \dbDelta( $sql );
    }

    /**
     * Mark the payment row for an invoice paid, once.
     *
     * Only an 'unpaid' row, or a 'failed' one (expired, then paid late), becomes 'paid'. The database
     * does the check, so two webhooks arriving at the same moment can't both get through, and a paid
     * or refunded row never changes.
     * Fulfil (count the vote, send the e-mail, grant access) only when this returns true.
     * Call it only after the API confirmed the invoice pays this row (WebhookHelper::parse_webhook()).
     *
     * @param PluginInstance $instance   Plugin config.
     * @param string         $invoice_id Invoice id from the payment server.
     * @return bool True if this call changed the row from unpaid to paid.
     */
    public static function mark_paid_once( PluginInstance $instance, string $invoice_id ): bool {
        global $wpdb;
        if ( '' === $invoice_id ) {
            return false;
        }
        $table = self::table_name( $instance, $wpdb );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- table name comes from the plugin config.
        $changed = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET payment_status = 'paid' WHERE payment_invoice_id = %s AND payment_status IN ('unpaid', 'failed')", $invoice_id ) );
        return 1 === $changed;
    }
}
