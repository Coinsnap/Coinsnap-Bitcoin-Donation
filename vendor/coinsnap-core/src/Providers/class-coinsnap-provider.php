<?php
/**
 * Coinsnap payment provider.
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
 * CoinSnap payment provider implementation.
 */
class CoinsnapProvider implements PaymentProviderInterface {

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
	 * Get store data from Coinsnap server.
	 *
	 * @return array
	 */
	public function get_store(): array {
		$settings = $this->get_settings();
		$api_base = rtrim( $settings['coinsnap_api_base'] ? $settings['coinsnap_api_base'] : CoinsnapConstants::DEFAULT_API_BASE, '/' );
		$api_key  = (string) $settings['coinsnap_api_key'];
		$store    = (string) $settings['coinsnap_store_id'];

		if ( ! $api_base || ! $api_key || ! $store ) {
			return array( 'error' => true, 'message' => __( 'Connection credentials error', 'coinsnap-core' ) );
		}

		$url = $api_base . sprintf( CoinsnapConstants::COINSNAP_STORE_ENDPOINT, rawurlencode( $store ) );

		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'X-Api-Key'     => $api_key,
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
				'accept'        => 'application/json',
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
			return array( 'error' => true, 'message' => __( 'Coinsnap server request error', 'coinsnap-core' ), 'code' => $code, 'result' => $result );
		}
	}

	/**
	 * Check existing webhook on Coinsnap server.
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
		$settings      = $this->get_settings();

		$api_base = rtrim( $settings['coinsnap_api_base'] ? $settings['coinsnap_api_base'] : CoinsnapConstants::DEFAULT_API_BASE, '/' );
		$api_key  = (string) $settings['coinsnap_api_key'];
		$store    = (string) $settings['coinsnap_store_id'];

		if ( ! $api_base || ! $api_key || ! $store ) {
			return false;
		}

		$webhookOption = get_option( $this->instance->webhook_key() );
		if ( ! is_array( $webhookOption ) || empty( $webhookOption['coinsnap'] ) ) {
			return false;
		}
		$storedWebhook = $webhookOption['coinsnap'];

		// Our site's callback URL that Coinsnap should send webhooks to.
		$site_webhook_url = rtrim( (string) get_home_url(), '/' ) . '/wp-json/' . $this->instance->rest_namespace() . '/webhook/coinsnap';
		// Coinsnap API base for webhook management.
		$api_webhooks_url = $api_base . sprintf( CoinsnapConstants::COINSNAP_WEBHOOKS_V1, rawurlencode( $store ) );

		$url  = $api_webhooks_url;
		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'X-Api-Key'     => $api_key,
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
				'accept'        => 'application/json',
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
				if ( strpos( $webhook['url'], $site_webhook_url ) !== false ) {

					// Return TRUE if stored webhook ID equals registered webhook ID.
					if ( $webhook['id'] === $storedWebhook['id'] ) {
						$webhookExists = true;
					} else {
						// If not - we delete this webhook from server.
						$delete_url  = $api_webhooks_url . '/' . rawurlencode( $webhook['id'] );
						$delete_args = array(
							'method'  => 'DELETE',
							'headers' => array(
								'X-Api-Key'     => $api_key,
								'Authorization' => 'token ' . $api_key,
								'Content-Type'  => 'application/json',
								'accept'        => 'application/json',
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

	/**
	 * Webhook registration on Coinsnap Server.
	 *
	 * @return array { id: string, secret: string, url: string }
	 */
	public function register_webhook(): array {

		$settings = $this->get_settings();
		$api_base = rtrim( $settings['coinsnap_api_base'] ? $settings['coinsnap_api_base'] : CoinsnapConstants::DEFAULT_API_BASE, '/' );
		$api_key  = (string) $settings['coinsnap_api_key'];
		$store    = (string) $settings['coinsnap_store_id'];

		if ( ! $api_base || ! $api_key || ! $store ) {
			return array( 'error' => true, 'message' => __( 'Connection credentials error', 'coinsnap-core' ) );
		}

		$ngrok_url = ! empty( $settings['ngrok_url'] ) ? rtrim( $settings['ngrok_url'], '/' ) : '';
		$base_url  = $ngrok_url ? $ngrok_url : rtrim( (string) get_home_url(), '/' );
		$webhook_url = $base_url . '/wp-json/' . $this->instance->rest_namespace() . '/webhook/coinsnap';

		$data = array(
			'url'              => $webhook_url,
			'authorizedEvents' => array(
				'everything'     => false,
				'specificEvents' => array( 'New', 'Expired', 'Settled', 'Processing' ),
			),
		);

		$url = $api_base . sprintf( CoinsnapConstants::COINSNAP_WEBHOOKS_V1, rawurlencode( $store ) );

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'X-Api-Key'     => $api_key,
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
				'accept'        => 'application/json',
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
			return array( 'error' => true, 'message' => __( 'Coinsnap server request error', 'coinsnap-core' ), 'code' => $code, 'result' => $result );
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
		$settings = $this->get_settings();
		$api_key  = $settings['coinsnap_api_key'];
		$store_id = $settings['coinsnap_store_id'];
		$api_base = rtrim( $settings['coinsnap_api_base'] ? $settings['coinsnap_api_base'] : CoinsnapConstants::DEFAULT_API_BASE, '/' );

		if ( ! $api_key || ! $store_id ) {
			return array();
		}

		$endpoints = array(
			$api_base . sprintf( CoinsnapConstants::COINSNAP_INVOICES_V1, rawurlencode( $store_id ) ),
			$api_base . sprintf( CoinsnapConstants::COINSNAP_INVOICES_ALT, rawurlencode( $store_id ) ),
		);

		// Convert amount from cents back to currency units for CoinSnap API.
		// SATS/BTC are already in whole units — pass through as-is.
		if ( in_array( strtoupper( $currency ), array( 'SATS', 'BTC' ), true ) ) {
			$amount_in_currency = $amount;
		} else {
			$amount_in_currency = $amount / 100;
		}

		// Validate currency code.
		$supported_currencies = COINSNAP_CURRENCIES;
		if ( ! in_array( $currency, $supported_currencies, true ) ) {
			return array();
		}

		// Order ID, buyer email and metadata as the consuming plugin passed them (see InvoiceMetadata::build()).
		// The top-level orderId is what Coinsnap shows and searches; it also copies it into metadata.orderId.
		$invoice = \CoinsnapCore\Util\InvoiceMetadata::build( $form_id, $invoice_data );

		$payload = array(
			'amount'       => $amount_in_currency,
			'currency'     => $currency,
			'referralCode' => $this->instance->referral_code(),
			'metadata'     => $invoice['metadata'],
			'checkout'     => array(
				'defaultPaymentMethod' => 'LightningNetwork',
			),
		);
		if ( '' !== $invoice['order_id'] ) {
			$payload['orderId'] = $invoice['order_id'];
		}
		if ( '' !== $invoice['buyer_email'] ) {
			$payload['buyerEmail'] = $invoice['buyer_email'];
		}

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'X-Api-Key'     => $api_key,
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
				'accept'        => 'application/json',
			),
			'timeout' => 20,
			'body'    => wp_json_encode( $payload ),
		);

		foreach ( $endpoints as $url ) {
			$res = wp_remote_request( $url, $args );
			/** Action: on Coinsnap response (raw). */
			do_action( 'wpbn_coinsnap_response', $res, $form_id );
			if ( is_wp_error( $res ) ) {
				continue;
			}
			$code = wp_remote_retrieve_response_code( $res );
			$body = json_decode( wp_remote_retrieve_body( $res ), true );
			// The server read the request and refused it (e.g. an amount under the on-chain dust
			// threshold on a store without Lightning): pass that on instead of trying the legacy
			// path, which only 404s. The message is plain text, or '' when the server sent none.
			if ( 400 === $code || 422 === $code ) {
				$message = ( is_array( $body ) && isset( $body['message'] ) && is_string( $body['message'] ) ) ? trim( sanitize_text_field( $body['message'] ) ) : '';
				return array(
					'error'   => 'invoice_refused',
					'status'  => $code,
					'message' => mb_substr( $message, 0, 300 ),
				);
			}
			if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
				$invoice_id  = isset( $body['id'] ) ? (string) $body['id'] : '';
				$payment_url = isset( $body['checkoutLink'] ) ? (string) $body['checkoutLink'] : '';
				if ( $invoice_id && $payment_url ) {
					\CoinsnapCore\Webhook\InvoiceCheck::record( $this->instance, $invoice_id, number_format( $amount_in_currency, 8, '.', '' ), $currency );
					return array(
						'invoice_id'  => $invoice_id,
						'payment_url' => $payment_url,
					);
				}
			}
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
		do_action( 'wpbn_coinsnap_webhook_received', $request );
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
	 * Check invoice payment status with Coinsnap API.
	 *
	 * @param string $invoice_id Invoice ID to check.
	 * @return array { invoice_id: string, paid: bool, status: string, metadata: array }
	 */
	public function check_invoice_status( string $invoice_id ): array {
		$settings = $this->get_settings();
		$api_key  = $settings['coinsnap_api_key'];
		$store_id = $settings['coinsnap_store_id'];
		$api_base = rtrim( $settings['coinsnap_api_base'] ? $settings['coinsnap_api_base'] : CoinsnapConstants::DEFAULT_API_BASE, '/' );

		if ( ! $api_key || ! $store_id ) {
			return array(
				'invoice_id' => $invoice_id,
				'paid'       => false,
				'status'     => 'error',
				'metadata'   => array( 'error' => 'Missing API credentials' ),
			);
		}

		$endpoints = array(
			$api_base . sprintf( '/api/v1/stores/%s/invoices/%s', rawurlencode( $store_id ), rawurlencode( $invoice_id ) ),
			$api_base . sprintf( '/api/stores/%s/invoices/%s', rawurlencode( $store_id ), rawurlencode( $invoice_id ) ),
		);

		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'X-Api-Key'     => $api_key,
				'Authorization' => 'token ' . $api_key,
				'Content-Type'  => 'application/json',
				'accept'        => 'application/json',
			),
			'timeout' => 20,
		);

		// "Unknown invoice" only when an endpoint answered with a client error and none failed: a network
		// error or 5xx on any endpoint means the API could not be asked, so the caller should ask for a retry.
		$not_found = false;
		$failed    = false;
		foreach ( $endpoints as $url ) {
			$res = wp_remote_request( $url, $args );
			do_action( 'wpbn_coinsnap_response', $res, 0 );

			if ( is_wp_error( $res ) ) {
				$failed = true;
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $res );
			$body = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( $code >= 400 && $code < 500 ) {
				$not_found = true;
			} elseif ( 0 === $code || $code >= 500 ) {
				$failed = true;
			}

			if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
				$status = isset( $body['status'] ) ? (string) $body['status'] : 'unknown';
				$paid   = \CoinsnapCore\Webhook\InvoiceCheck::is_paid( $this->instance, $invoice_id, $body, false );

				return array(
					'invoice_id' => $invoice_id,
					'paid'       => $paid,
					'status'     => $status,
					'metadata'   => $body,
				);
			}
		}

		return array(
			'invoice_id' => $invoice_id,
			'paid'       => false,
			'status'     => 'error',
			'retry'      => $failed || ! $not_found,
			'metadata'   => array( 'error' => 'All API endpoints failed' ),
		);
	}

	/**
	 * Verify webhook HMAC signature.
	 *
	 * @return bool True if signature valid.
	 */
	public function verify_signature(): bool {
		$webhook_option = get_option( $this->instance->webhook_key() );
		$secret         = ( is_array( $webhook_option ) && ! empty( $webhook_option['coinsnap']['secret'] ) ) ? (string) $webhook_option['coinsnap']['secret'] : '';
		// X-Coinsnap-Signature: t=<unix time>,v1=<HMAC of "t.body">, with a 5 minute window.
		$header  = isset( $_SERVER['HTTP_X_COINSNAP_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_COINSNAP_SIGNATURE'] ) ) : '';
		$payload = (string) file_get_contents( 'php://input' );
		return '' !== $payload && null === \CoinsnapCore\Webhook\Signature::coinsnapProblem( $header, $payload, $secret, time() );
	}
}
