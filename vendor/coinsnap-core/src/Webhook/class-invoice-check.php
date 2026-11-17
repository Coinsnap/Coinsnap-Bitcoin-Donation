<?php
/**
 * Whether an invoice, as the payment server's API reports it, pays one of this plugin's payments.
 *
 * @package coinsnap-core
 */

declare(strict_types=1);

namespace CoinsnapCore\Webhook;

use CoinsnapCore\Database\PaymentTable;
use CoinsnapCore\PluginInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class InvoiceCheck {

    /**
     * Settled, not marked paid by hand in BTCPay, and created through this plugin (it has a payment row).
     *
     * @param PluginInstance $instance   Plugin config.
     * @param string         $invoice_id Invoice id.
     * @param array          $invoice    Invoice as returned by the API.
     * @param bool           $is_btcpay  Whether the invoice comes from BTCPay Server.
     */
    public static function is_paid( PluginInstance $instance, string $invoice_id, array $invoice, bool $is_btcpay ): bool {
        if ( 'Settled' !== ( $invoice['status'] ?? '' ) ) {
            return false;
        }
        if ( $is_btcpay && 'Marked' === ( $invoice['additionalStatus'] ?? '' ) ) {
            return false;
        }
        if ( ! self::created_here( $instance, $invoice_id ) ) {
            return false;
        }
        $expected = get_option( self::record_key( $instance, $invoice_id ) );
        if ( ! is_array( $expected ) ) {
            // No record: only invoices open at the upgrade to 1.1.0, for one day after it.
            $since = (int) get_option( 'coinsnap_core_records_since', 0 );
            return $since > 0 && time() - $since < DAY_IN_SECONDS;
        }
        $paid = $is_btcpay ? ( $invoice['amount'] ?? null ) : ( $invoice['invoiceAmount'] ?? $invoice['fiatAmount'] ?? null );
        return self::same_amount( $paid, (string) ( $invoice['currency'] ?? '' ), (string) ( $expected['amount'] ?? '' ), (string) ( $expected['currency'] ?? '' ) );
    }

    /**
     * Remember the amount and currency an invoice was created for, for this plugin only.
     */
    public static function record( PluginInstance $instance, string $invoice_id, string $amount, string $currency ): void {
        // An option, not a transient: an object cache may evict transients, and a missing record refuses the payment.
        update_option( self::record_key( $instance, $invoice_id ), array( 'amount' => $amount, 'currency' => $currency ), false );
    }

    // Keyed by plugin as well, so another Coinsnap plugin on the same site and store never counts this plugin's invoices as its own (6.4).
    private static function record_key( PluginInstance $instance, string $invoice_id ): string {
        return 'coinsnap_core_inv_' . md5( $instance->option_key() . '|' . $invoice_id );
    }

    private static function same_amount( $paid, string $paid_currency, string $expected, string $expected_currency ): bool {
        $paid_units     = self::to_minor_units( $paid );
        $expected_units = self::to_minor_units( $expected );
        if ( null === $paid_units || null === $expected_units || $expected_units <= 0 ) {
            return false;
        }
        if ( strtoupper( $paid_currency ) !== strtoupper( $expected_currency ) ) {
            return false;
        }
        // One unit of the 8th decimal for BTC, a cent otherwise.
        $tolerance = 'BTC' === strtoupper( $expected_currency ) ? 1 : 1000000;
        return abs( $paid_units - $expected_units ) <= $tolerance;
    }

    /**
     * Amount as an integer of 10^-8 units, parsed from the decimal string so it is never compared as a float.
     */
    public static function to_minor_units( $value ): ?int {
        if ( is_int( $value ) || is_float( $value ) ) {
            $value = number_format( (float) $value, 8, '.', '' );
        }
        if ( ! is_string( $value ) || ! preg_match( '/^(\d+)(?:\.(\d+))?$/', trim( $value ), $match ) ) {
            return null;
        }
        $fraction = str_pad( $match[2] ?? '', 9, '0' );
        return (int) $match[1] * 100000000 + (int) substr( $fraction, 0, 8 ) + ( (int) $fraction[8] >= 5 ? 1 : 0 );
    }

    /**
     * An invoice with no payment row was not created by this plugin. Plugins that keep no table rely on the amount record.
     */
    public static function created_here( PluginInstance $instance, string $invoice_id ): bool {
        global $wpdb;
        $table = PaymentTable::table_name( $instance, $wpdb );
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            return true;
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name comes from the plugin config.
        return null !== $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE payment_invoice_id = %s", $invoice_id ) );
    }
}
