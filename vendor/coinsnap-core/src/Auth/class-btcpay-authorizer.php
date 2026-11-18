<?php
/**
 * BTCPay Server authorization flow.
 *
 * @package coinsnap-core
 */

declare(strict_types=1);

namespace CoinsnapCore\Auth;

use CoinsnapCore\PluginInstance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the BTCPay Server OAuth-style authorization flow.
 *
 * Provides:
 * - Building the authorization URL for BTCPay API key creation.
 * - Registering and handling the callback endpoint that receives the API key.
 * - Merging received credentials into the plugin's WordPress options.
 */
class BTCPayAuthorizer {

	/**
	 * Store permissions the core needs: create and read invoices, read the store (connection check,
	 * payment methods) and manage its webhook. Nothing that moves money (no pull payments, no
	 * invoice changes), nothing for other stores, nothing server-wide.
	 */
	public const REQUIRED_PERMISSIONS = array(
		'btcpay.store.canviewinvoices',
		'btcpay.store.cancreateinvoice',
		'btcpay.store.canviewstoresettings',
		'btcpay.store.webhooks.canmodifywebhooks',
	);

	/** No optional permissions: a key with more rights than REQUIRED_PERMISSIONS is refused. */
	public const OPTIONAL_PERMISSIONS = array();

	/**
	 * Build BTCPay authorization URL. Pure function, no side effects.
	 *
	 * @param string      $base_url          BTCPay server base URL.
	 * @param array       $permissions       Array of permission strings.
	 * @param string|null $app_name          Application name shown in BTCPay.
	 * @param bool|null   $strict            Whether to require strict permissions.
	 * @param bool|null   $selective_stores  Whether to allow store selection.
	 * @param string|null $redirect_url      URL to redirect back to after authorization.
	 * @param string|null $app_identifier    Application identifier string.
	 * @return string Fully constructed authorization URL.
	 */
	public static function get_authorize_url(
		string $base_url,
		array $permissions,
		?string $app_name = null,
		?bool $strict = null,
		?bool $selective_stores = null,
		?string $redirect_url = null,
		?string $app_identifier = null
	): string {
		$url = rtrim( $base_url, '/' ) . '/api-keys/authorize';

		$params = array(
			'permissions'           => $permissions,
			'applicationName'       => $app_name,
			'strict'                => $strict,
			'selectiveStores'       => $selective_stores,
			'redirect'              => $redirect_url,
			'applicationIdentifier' => $app_identifier,
		);

		// Remove NULL values.
		$params = array_filter(
			$params,
			function ( $value ) {
				return null !== $value;
			}
		);

		$query_params = array();
		foreach ( $params as $param => $value ) {
			if ( true === $value ) {
				$value = 'true';
			}
			if ( false === $value ) {
				$value = 'false';
			}

			if ( is_array( $value ) ) {
				foreach ( $value as $item ) {
					if ( true === $item ) {
						$item = 'true';
					}
					if ( false === $item ) {
						$item = 'false';
					}
					$query_params[] = $param . '=' . urlencode( (string) $item );
				}
			} else {
				$query_params[] = $param . '=' . urlencode( (string) $value );
			}
		}

		return $url . '?' . implode( '&', $query_params );
	}

	/**
	 * Register the BTCPay callback endpoint (rewrite + request filter + template_redirect).
	 * Call this from the consuming plugin's boot().
	 *
	 * @param PluginInstance $instance The plugin instance providing configuration.
	 */
	public static function register_callback( PluginInstance $instance ): void {
		$endpoint = $instance->get( 'btcpay_callback_endpoint' );

		add_action(
			'init',
			function () use ( $endpoint ) {
				add_rewrite_endpoint( $endpoint, EP_ROOT );
			}
		);

		add_filter(
			'request',
			function ( $vars ) use ( $endpoint ) {
				if ( isset( $vars[ $endpoint ] ) ) {
					$vars[ $endpoint ] = true;
				}
				return $vars;
			}
		);

		add_action(
			'template_redirect',
			function () use ( $instance, $endpoint ) {
				self::handle_callback( $instance, $endpoint );
			}
		);
	}

	/**
	 * Handle the BTCPay callback redirect.
	 *
	 * Validates the incoming request, verifies the API key against the BTCPay
	 * server, checks permissions, and saves credentials to the plugin options.
	 *
	 * @param PluginInstance $instance The plugin instance providing configuration.
	 * @param string         $endpoint The rewrite endpoint name.
	 */
	private static function handle_callback( PluginInstance $instance, string $endpoint ): void {
		global $wp_query;

		if ( ! isset( $wp_query->query_vars[ $endpoint ] ) ) {
			return;
		}

		// BTCPay posts here from the admin's browser; only the state handed out with the authorize URL is accepted, once.
		$state      = isset( $_GET['state'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_GET['state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state_host = ( '' !== $state ) ? get_transient( self::state_key( $state ) ) : false;
		if ( '' !== $state ) {
			delete_transient( self::state_key( $state ) );
		}
		if ( ! is_string( $state_host ) || '' === $state_host ) {
			status_header( 403 );
			exit();
		}

		$settings_url   = admin_url( '/admin.php?page=' . $instance->get( 'menu_slug' ) . '-settings' );
		$option_key     = $instance->option_key();
		$btcpay_host    = $state_host;
		$btcpay_api_key = isset( $_POST['apiKey'] ) ? sanitize_text_field( wp_unslash( $_POST['apiKey'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the one-time state above authenticates this request.

		// The key must work on that server, or nothing is saved.
		$response   = wp_remote_request(
			rtrim( $btcpay_host, '/' ) . '/api/v1/stores',
			array(
				'method'  => 'GET',
				'headers' => array(
					'Authorization' => 'token ' . $btcpay_api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 20,
			)
		);
		$code       = wp_remote_retrieve_response_code( $response );
		$get_stores = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( '' === $btcpay_api_key || $code < 200 || $code >= 300 || ! is_array( $get_stores ) || count( $get_stores ) < 1 ) {
			wp_redirect( $settings_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit();
		}

		// Permissions and store come from BTCPay itself, not from the posted form.
		$data     = array();
		$key_info = wp_remote_request(
			rtrim( $btcpay_host, '/' ) . '/api/v1/api-keys/current',
			array(
				'method'  => 'GET',
				'headers' => array( 'Authorization' => 'token ' . $btcpay_api_key ),
				'timeout' => 20,
			)
		);
		$key_body = json_decode( wp_remote_retrieve_body( $key_info ), true );
		if ( 200 === wp_remote_retrieve_response_code( $key_info ) && is_array( $key_body['permissions'] ?? null ) ) {
			$data['apiKey']      = $btcpay_api_key;
			$data['permissions'] = array_values( array_filter( array_map( 'strval', $key_body['permissions'] ) ) );
		}

		$store_id = isset( $data['permissions'] ) ? self::acceptable_key_store( $data['permissions'] ) : '';
		if ( '' !== $store_id ) {
			self::update_settings(
				$option_key,
				array(
					'btcpay_host'      => $btcpay_host,
					'btcpay_api_key'   => $data['apiKey'],
					'btcpay_store_id'  => $store_id,
					'payment_provider' => 'btcpay',
				)
			);
		}

		wp_redirect( $settings_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit();
	}

	/**
	 * The store a BTCPay key is limited to, or '' when the key must be refused.
	 *
	 * Accepted only if every permission is a store permission for one and the same store, each is
	 * in REQUIRED_PERMISSIONS + OPTIONAL_PERMISSIONS, and every required one is present. Unrestricted,
	 * server-wide, user-level or multi-store keys are refused. Pass permissions read from BTCPay
	 * (GET /api/v1/api-keys/current), never ones posted by the browser.
	 *
	 * @param array $permissions Permissions as BTCPay reports them, e.g. "btcpay.store.canviewinvoices:STOREID".
	 */
	public static function acceptable_key_store( array $permissions ): string {
		if ( array() === $permissions ) {
			return '';
		}
		$allowed  = array_merge( self::REQUIRED_PERMISSIONS, self::OPTIONAL_PERMISSIONS );
		$store_id = null;
		$policies = array();
		foreach ( $permissions as $permission ) {
			$parts = explode( ':', (string) $permission, 2 );
			if ( 2 !== count( $parts ) || '' === $parts[1] || ! in_array( $parts[0], $allowed, true ) ) {
				return '';
			}
			if ( null !== $store_id && $store_id !== $parts[1] ) {
				return '';
			}
			$store_id   = $parts[1];
			$policies[] = $parts[0];
		}
		foreach ( self::REQUIRED_PERMISSIONS as $needed ) {
			if ( ! in_array( $needed, $policies, true ) ) {
				return '';
			}
		}
		return (string) $store_id;
	}

	/**
	 * Transient name for a one-time authorization state.
	 */
	public static function state_key( string $state ): string {
		return 'coinsnap_core_btcpay_state_' . $state;
	}

	/**
	 * Merge settings into an existing WordPress option.
	 *
	 * @param string $option_key The WordPress option name.
	 * @param array  $data       Key-value pairs to merge into the option.
	 */
	public static function update_settings( string $option_key, array $data ): void {
		$existing = get_option( $option_key, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		foreach ( $data as $key => $value ) {
			$existing[ $key ] = $value;
		}
		update_option( $option_key, $existing );
	}
}
