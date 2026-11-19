<?php
/**
 * BTCPay payment provider.
 *
 * @package coinsnap-core
 */

declare(strict_types=1);

namespace CoinsnapCore\Providers;

use CoinsnapCore\PluginInstance;
use CoinsnapCore\Interfaces\PaymentProviderInterface;
use CoinsnapCore\CoinsnapConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BTCPay Server payment provider implementation.
 */
class BTCPayProvider implements PaymentProviderInterface {

	/** @var PluginInstance */
	private $instance;

	/**
	 * @param PluginInstance $instance Per-plugin configuration.
	 */
	public function __construct( PluginInstance $instance ) {
		$this->instance = $instance;
	}

	/**
	 * Get merged settings with defaults.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		$defaults = array(
			'payment_provider'             => 'coinsnap',
			'coinsnap_store_id'            => '',
			'coinsnap_api_key'             => '',
			'coinsnap_api_base'            => CoinsnapConstants::DEFAULT_API_BASE,
			'coinsnap_webhook_secret'      => '',
			'btcpay_host'                  => '',
			'btcpay_api_key'               => '',
			'btcpay_store_id'              => '',
			'btcpay_webhook_secret'        => '',
			'log_level'                    => 'error',
			'disable_webhook_verification' => false,
		);
		$opts = get_option( $this->instance->option_key(), array() );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}
		return array_merge( $defaults, $opts );
	}

	/**
	 * Get store data from BTCPay server.
	 *
	 * @return array
	 */
	public function get_store(): array {
		$s       = $this->get_settings();
		$host    = rtrim( (string) $s['btcpay_host'], '/' );
		$api_key = (string) $s['btcpay_api_key'];
		$store   = (string) $s['btcpay_store_id'];

		if ( ! $host || ! $api_key || ! $store ) {
			return array( 'error' => true, 'message' => __( 'Connection credentials error', 'coinsnap-core' ) );
		}

		$url = $host . sprintf( CoinsnapConstants::BTCPAY_STORE, rawurlencode( $store ) );

		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 20,
		);

		$res = wp_remote_request( $url, $args );

		if ( is_wp_error( $res ) ) {
			return array( 'error' => true, 'message' => __( 'Store data error', 'coinsnap-core' ) );
		}

		$code   = wp_remote_retrieve_response_code( $res );
		$result = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 200 && $code < 300 && is_array( $result ) ) {
			return array( 'code' => $code, 'result' => $result );
		} else {
			return array( 'error' => true, 'message' => __( 'BTCPay server request error', 'coinsnap-core' ), 'code' => $code, 'result' => $result );
		}
	}

	/**
	 * Check existing webhook on BTCPay server.
	 *
	 * False only when the server answered and our stored webhook is not there, so the caller
	 * should register a new one. When the server can't be asked (network error, 5xx) this
	 * returns true: an API error is never "no webhook", or every glitch would create a new
	 * webhook and replace the secret.
	 *
	 * @return bool
	 */
	public function check_webhook(): bool {

		$webhookExists = false;
		$s             = $this->get_settings();

		$host    = rtrim( (string) $s['btcpay_host'], '/' );
		$api_key = (string) $s['btcpay_api_key'];
		$store   = (string) $s['btcpay_store_id'];
		$webhook_url = rtrim( (string) get_home_url(), '/' ) . '/wp-json/' . $this->instance->rest_namespace() . '/webhook/btcpay';

		if ( ! $host || ! $api_key || ! $store ) {
			return false;
		}

		$webhookOption = get_option( $this->instance->webhook_key() );
		if ( ! is_array( $webhookOption ) || empty( $webhookOption['btcpay'] ) ) {
			return false;
		}
		$storedWebhook = $webhookOption['btcpay'];

		if ( $storedWebhook ) {
			$url  = $host . sprintf( CoinsnapConstants::BTCPAY_WEBHOOKS, rawurlencode( $store ) );
			$args = array(
				'method'  => 'GET',
				'headers' => array(
					'Authorization' => 'token ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 20,
			);

			$res = wp_remote_request( $url, $args );

			$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
			if ( 0 === $code || $code >= 500 ) {
				return true; // Unknown, not missing: keep the stored webhook and secret.
			}

			$storeWebhooks = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( $code >= 200 && $code < 300 && is_array( $storeWebhooks ) ) {

				// Registered webhooks analysis.
				foreach ( $storeWebhooks as $webhook ) {

					// If webhook is registered for this site.
					if ( strpos( $webhook['url'], $webhook_url ) !== false ) {

						// Return TRUE if stored webhook ID equals registered webhook ID.
						if ( $webhook['id'] === $storedWebhook['id'] ) {
							$webhookExists = true;
						} else {
							// If not - we delete this webhook from server.
							$delete_url  = $host . sprintf( CoinsnapConstants::BTCPAY_WEBHOOKS, rawurlencode( $store ) ) . '/' . $webhook['id'];
							$delete_args = array(
								'method'  => 'DELETE',
								'headers' => array(
									'Authorization' => 'token ' . $api_key,
									'Content-Type'  => 'application/json',
								),
								'timeout' => 20,
							);
							wp_remote_request( $delete_url, $delete_args );
						}
					}
				}

				return $webhookExists;
			}
			return false;
		}
		return false;
	}

	/**
	 * Webhook registration on BTCPay Server.
	 *
	 * @return array { id: string, secret: string, url: string }
	 */
	public function register_webhook(): array {

		$s       = $this->get_settings();
		$host    = rtrim( (string) $s['btcpay_host'], '/' );
		$api_key = (string) $s['btcpay_api_key'];
		$store   = (string) $s['btcpay_store_id'];
		$webhook_url = rtrim( (string) get_home_url(), '/' ) . '/wp-json/' . $this->instance->rest_namespace() . '/webhook/btcpay';

		if ( ! $host || ! $api_key || ! $store ) {
			return array( 'error' => true, 'message' => __( 'Connection credentials error', 'coinsnap-core' ) );
		}

		$data = array(
			'url'              => $webhook_url,
			'authorizedEvents' => array(
				'everything'     => false,
				'specificEvents' => array( 'InvoiceCreated', 'InvoiceExpired', 'InvoiceSettled', 'InvoiceProcessing' ),
			),
		);

		$url = $host . sprintf( CoinsnapConstants::BTCPAY_WEBHOOKS, rawurlencode( $store ) );

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 20,
			'body'    => wp_json_encode( $data ),
		);

		$res = wp_remote_request( $url, $args );

		if ( is_wp_error( $res ) ) {
			return array( 'error' => true, 'message' => __( 'Webhook creation error', 'coinsnap-core' ) );
		}

		$code   = wp_remote_retrieve_response_code( $res );
		$result = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 200 && $code < 300 && is_array( $result ) ) {
			return array( 'code' => $code, 'result' => $result );
		} else {
			return array( 'error' => true, 'message' => __( 'BTCPay server request error', 'coinsnap-core' ), 'code' => $code, 'result' => $result );
		}
	}

	/**
	 * Create a payment invoice.
	 *
	 * @param int    $form_id      Form ID.
	 * @param int    $amount       Amount.
	 * @param string $currency     Currency code.
	 * @param array  $invoice_data Invoice data.
	 * @return array { invoice_id: string, payment_url: string, expires_at?: int }
	 */
	public function create_invoice( int $form_id, int $amount, string $currency, array $invoice_data ): array {
		$s       = $this->get_settings();
		$host    = rtrim( (string) $s['btcpay_host'], '/' );
		$api_key = (string) $s['btcpay_api_key'];
		$store   = (string) $s['btcpay_store_id'];

		if ( ! $host || ! $api_key || ! $store ) {
			return array();
		}

		$url = $host . sprintf( CoinsnapConstants::BTCPAY_INVOICES, rawurlencode( $store ) );

		// Convert from minor units to API expected units.
		// Fiat amounts are stored in cents (e.g., 1000 = €10.00) — divide by 100.
		// SATS/BTC are already in whole units — pass through as-is.
		if ( in_array( strtoupper( $currency ), array( 'SATS', 'BTC' ), true ) ) {
			$api_amount = $amount;
		} else {
			$api_amount = $amount / 100;
		}

		// Order ID, buyer email and metadata as the consuming plugin passed them (see InvoiceMetadata::build()).
		// BTCPay's create-invoice request has no top-level orderId or buyerEmail: BTCPay reads both from
		// metadata (metadata.orderId, metadata.buyerEmail), where InvoiceMetadata puts them.
		$invoice = \CoinsnapCore\Util\InvoiceMetadata::build( $form_id, $invoice_data );

		$payload = array(
			'amount'   => (string) $api_amount,
			'currency' => $currency,
			'metadata' => $invoice['metadata'],
		);

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 20,
			'body'    => wp_json_encode( $payload ),
		);

		$res = wp_remote_request( $url, $args );
		do_action( 'wpbn_btcpay_response', $res, $form_id );

		if ( is_wp_error( $res ) ) {
			return array();
		}

		$code = wp_remote_retrieve_response_code( $res );
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
			$invoice_id  = isset( $body['id'] ) ? (string) $body['id'] : '';
			$payment_url = isset( $body['checkoutLink'] ) ? (string) $body['checkoutLink'] : '';
			if ( ! $invoice_id || ! $payment_url ) {
				return array();
			}
			\CoinsnapCore\Webhook\InvoiceCheck::record( $this->instance, $invoice_id, (string) $api_amount, $currency );
			return array(
				'invoice_id'  => $invoice_id,
				'payment_url' => $payment_url,
			);
		}
		return array();
	}

	/**
	 * Validate webhook or callback.
	 *
	 * @param array $request Parsed request body.
	 * @return array { invoice_id: string, paid: bool, metadata: array }
	 */
	public function handle_webhook( array $request ): array {
		do_action( 'wpbn_btcpay_webhook_received', $request );
		$invoice_id = isset( $request['invoiceId'] ) ? (string) $request['invoiceId'] : '';
		// Only the invoice id is taken from the request; whether it is paid comes from the API.
		$status = '' !== $invoice_id ? $this->check_invoice_status( $invoice_id ) : array( 'paid' => false );
		return array(
			'invoice_id' => $invoice_id,
			'paid'       => ! empty( $status['paid'] ),
			'retry'      => ! empty( $status['retry'] ),
			'metadata'   => $request,
		);
	}

	/**
	 * Check invoice payment status with BTCPay API.
	 *
	 * @param string $invoice_id Invoice ID to check.
	 * @return array { invoice_id: string, paid: bool, status: string, metadata: array }
	 */
	public function check_invoice_status( string $invoice_id ): array {
		$s       = $this->get_settings();
		$host    = rtrim( (string) $s['btcpay_host'], '/' );
		$api_key = (string) $s['btcpay_api_key'];
		$store   = (string) $s['btcpay_store_id'];

		if ( ! $host || ! $api_key || ! $store ) {
			return array(
				'invoice_id' => $invoice_id,
				'paid'       => false,
				'status'     => 'error',
				'metadata'   => array( 'error' => 'Missing API credentials' ),
			);
		}

		$url  = $host . sprintf( '/api/v1/stores/%s/invoices/%s', rawurlencode( $store ), rawurlencode( $invoice_id ) );
		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 20,
		);

		$res = wp_remote_request( $url, $args );
		do_action( 'wpbn_btcpay_response', $res, 0 );

		if ( is_wp_error( $res ) ) {
			return array(
				'invoice_id' => $invoice_id,
				'paid'       => false,
				'status'     => 'error',
				'retry'      => true,
				'metadata'   => array( 'error' => 'BTCPay Server could not be reached' ),
			);
		}

		$code = wp_remote_retrieve_response_code( $res );
		$body = json_decode( wp_remote_retrieve_body( $res ), true );

		if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
			$status = isset( $body['status'] ) ? (string) $body['status'] : 'unknown';
			$paid   = \CoinsnapCore\Webhook\InvoiceCheck::is_paid( $this->instance, $invoice_id, $body, true );

			return array(
				'invoice_id' => $invoice_id,
				'paid'       => $paid,
				'status'     => $status,
				'metadata'   => $body,
			);
		}

		return array(
			'invoice_id' => $invoice_id,
			'paid'       => false,
			'status'     => 'error',
			// Server errors are worth a retry; an unknown invoice is not.
			'retry'      => ! ( $code >= 400 && $code < 500 ),
			'metadata'   => array( 'error' => 'Invalid API response' ),
		);
	}

	/**
	 * Verify BTCPay webhook signature.
	 *
	 * @return bool True if valid.
	 */
	public function verify_signature(): bool {
		$webhook_option = get_option( $this->instance->webhook_key() );
		$secret         = ( is_array( $webhook_option ) && ! empty( $webhook_option['btcpay']['secret'] ) ) ? (string) $webhook_option['btcpay']['secret'] : '';
		// BTCPay sends the header as "BTCPay-Sig: sha256=<HMAC of body>".
		$header  = isset( $_SERVER['HTTP_BTCPAY_SIG'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_BTCPAY_SIG'] ) ) : '';
		$payload = (string) file_get_contents( 'php://input' );
		return '' !== $payload && null === \CoinsnapCore\Webhook\Signature::btcpayProblem( $header, $payload, $secret );
	}
}
