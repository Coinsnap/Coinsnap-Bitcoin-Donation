<?php
/**
 * Webhook verification and parsing helpers.
 *
 * @package coinsnap-core
 */

declare(strict_types=1);

namespace CoinsnapCore\Rest;

use CoinsnapCore\PluginInstance;
use CoinsnapCore\Providers\CoinsnapProvider;
use CoinsnapCore\Providers\BTCPayProvider;
use CoinsnapCore\Admin\SettingsPage;
use CoinsnapCore\Util\Logger;
use CoinsnapCore\Webhook\Signature;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Static utilities for webhook signature verification and data parsing.
 * Consuming plugins call these in their own REST route handlers.
 */
class WebhookHelper {

    /**
     * Verify Coinsnap webhook signature from php://input. Prefer check_request() in a REST route.
     *
     * @param PluginInstance $instance Plugin config (for reading webhook secret).
     * @return bool True if signature is valid.
     */
    public static function verify_coinsnap_signature( PluginInstance $instance ): bool {
        $provider = new CoinsnapProvider( $instance );
        $ok       = $provider->verify_signature();
        if ( ! $ok ) {
            self::log( $instance, 'security', 'bad signature' );
        }
        return $ok;
    }

    /**
     * Verify BTCPay webhook signature from php://input. Prefer check_request() in a REST route.
     *
     * @param PluginInstance $instance Plugin config (for reading webhook secret).
     * @return bool True if signature is valid.
     */
    public static function verify_btcpay_signature( PluginInstance $instance ): bool {
        $provider = new BTCPayProvider( $instance );
        $ok       = $provider->verify_signature();
        if ( ! $ok ) {
            self::log( $instance, 'security', 'bad signature' );
        }
        return $ok;
    }

    /**
     * Check a webhook request's signature, for use as the REST route's permission_callback.
     *
     * Reads the raw body from the request (not php://input), refuses an empty secret, and logs every
     * rejection with a fixed reason ("bad signature", "stale timestamp"), at most 10 a minute.
     *
     *     'permission_callback' => fn ( $r ) => WebhookHelper::check_request( $core, 'coinsnap', $r ),
     *
     * @param PluginInstance   $instance Plugin config.
     * @param string           $provider 'coinsnap' or 'btcpay'.
     * @param \WP_REST_Request $request  The webhook request.
     * @return bool True only for a valid signature.
     */
    public static function check_request( PluginInstance $instance, string $provider, $request ): bool {
        // WordPress may call a route's permission_callback more than once per request (e.g. for the
        // Allow header); check and log once.
        // SplObjectStorage holds the request object itself, so a freed request's id can't be reused.
        static $checked = null;
        $checked = $checked ?? new \SplObjectStorage();
        $memo    = $instance->option_key() . '|' . $provider;
        $done    = isset( $checked[ $request ] ) ? $checked[ $request ] : array();
        if ( ! isset( $done[ $memo ] ) ) {
            $done[ $memo ]       = self::check_request_once( $instance, $provider, $request );
            $checked[ $request ] = $done;
        }
        return $done[ $memo ];
    }

    private static function check_request_once( PluginInstance $instance, string $provider, $request ): bool {
        $body   = (string) $request->get_body();
        $secret = self::webhook_secret( $instance, $provider );
        if ( 'btcpay' === $provider ) {
            $problem = Signature::btcpayProblem( (string) $request->get_header( 'BTCPay-Sig' ), $body, $secret );
        } else {
            $problem = Signature::coinsnapProblem( (string) $request->get_header( 'X-Coinsnap-Signature' ), $body, $secret, time() );
        }
        if ( '' === $body && null === $problem ) {
            $problem = Signature::BAD_SIGNATURE;
        }
        if ( null !== $problem ) {
            self::log( $instance, 'security', $problem );
            return false;
        }
        return true;
    }

    /**
     * The webhook secret this plugin stored for a provider ('' if none, which rejects every webhook).
     */
    private static function webhook_secret( PluginInstance $instance, string $provider ): string {
        $option = get_option( $instance->webhook_key() );
        $key    = ( 'btcpay' === $provider ) ? 'btcpay' : 'coinsnap';
        return ( is_array( $option ) && ! empty( $option[ $key ]['secret'] ) ) ? (string) $option[ $key ]['secret'] : '';
    }

    /**
     * Parse webhook data into a standard format.
     *
     * Only the invoice id is taken from the request. Pass the plugin instance so 'paid' is answered by the
     * payment server's API; without it 'paid' is always false, since the request body proves nothing.
     * With the instance, the outcome is logged with a fixed reason: "unknown invoice", "status <name>",
     * "retry: API unavailable", and "forged status" (SECURITY) when the body claims a payment the API denies.
     *
     * @param string              $provider Provider name ('coinsnap' or 'btcpay').
     * @param array               $data     Raw webhook JSON data.
     * @param PluginInstance|null $instance Plugin config, used to ask the API.
     * @return array { invoice_id: string, paid: bool, retry: bool, type: string, metadata: array }
     */
    public static function parse_webhook( string $provider, array $data, ?PluginInstance $instance = null ): array {
        $invoice_id = ( isset( $data['invoiceId'] ) && is_string( $data['invoiceId'] ) ) ? $data['invoiceId'] : '';
        $type       = isset( $data['type'] ) && is_string( $data['type'] ) ? $data['type'] : '';
        $paid       = false;
        $retry      = false;

        if ( null === $instance ) {
            _doing_it_wrong( __METHOD__, 'Pass the PluginInstance so the invoice status comes from the API.', '1.1.0' );
        } elseif ( '' !== $invoice_id ) {
            $client = ( 'btcpay' === $provider ) ? new BTCPayProvider( $instance ) : new CoinsnapProvider( $instance );
            $status = $client->check_invoice_status( $invoice_id );
            $paid   = ! empty( $status['paid'] );
            $retry  = ! empty( $status['retry'] );

            $claims_paid = in_array( $type, array( 'Settled', 'InvoiceSettled', 'InvoicePaymentSettled' ), true );
            $api_status  = (string) ( $status['status'] ?? '' );
            if ( $retry ) {
                self::log( $instance, 'info', 'retry: API unavailable', $invoice_id );
            } elseif ( 'error' === $api_status ) {
                self::log( $instance, 'info', 'unknown invoice', $invoice_id );
            } elseif ( ! $paid && $claims_paid && 'Marked' !== ( $status['metadata']['additionalStatus'] ?? '' ) ) {
                self::log( $instance, 'security', 'forged status', $invoice_id );
            } elseif ( ! $paid ) {
                self::log( $instance, 'info', 'status ' . preg_replace( '/[^A-Za-z]/', '', $api_status ), $invoice_id );
            }
        }

        return array(
            'invoice_id' => $invoice_id,
            'paid'       => $paid,
            // The API could not be asked: reply 503 so the payment server sends the webhook again.
            'retry'      => $retry,
            'type'       => $type,
            // The raw request body. Never trust it: use only invoice_id, and your own records.
            'metadata'   => $data,
        );
    }

    /**
     * Log a webhook outcome: a fixed reason and the cleaned invoice id, never the body, headers,
     * store id or customer data. SECURITY lines are written at "error" level (always kept), at most
     * 10 a minute per plugin; INFO lines follow the plugin's log level.
     *
     * @param PluginInstance $instance   Plugin config.
     * @param string         $level      'security' or 'info'.
     * @param string         $reason     Fixed reason.
     * @param string         $invoice_id Invoice id from the request, if any.
     */
    public static function log( PluginInstance $instance, string $level, string $reason, string $invoice_id = '' ): void {
        $key = 'coinsnap_core_seclog_' . md5( $instance->option_key() ) . '_' . gmdate( 'YmdHi' );
        if ( 'security' === $level ) {
            $count = (int) get_transient( $key ) + 1;
            set_transient( $key, $count, 2 * MINUTE_IN_SECONDS );
            if ( $count > 11 ) {
                return;
            }
            if ( 11 === $count ) {
                $reason = 'more rejections this minute, not logged';
                $invoice_id = '';
            }
        }
        $id     = substr( (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $invoice_id ), 0, 64 );
        $logger = new Logger(
            (string) $instance->get( 'log_dir_name' ),
            (string) $instance->get( 'log_file_name' ),
            (string) ( SettingsPage::get_settings_for( $instance )['log_level'] ?? 'error' )
        );
        $line = sprintf( '%s webhook reason=%s invoice=%s', strtoupper( $level ), $reason, '' !== $id ? $id : '-' );
        if ( 'security' === $level ) {
            $logger->error( $line );
        } else {
            $logger->info( $line );
        }
    }
}
