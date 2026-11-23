<?php
/**
 * Coinsnap refusals shown to the donor, run inside a WordPress with the plugin active:
 *   wp eval-file wp-content/plugins/coinsnap-bitcoin-donation/tests/invoice-refusal-test.php
 *
 * Starts donations through the REST route with the Coinsnap provider. Settings come from a
 * pre_option filter and the Kraken and Coinsnap APIs are faked with pre_http_request, so nothing
 * leaves the site and nothing is written: every case is refused before a payment row is saved.
 *
 * Coinsnap refuses amounts under the on-chain dust threshold with a 400 when the store has no
 * Lightning. The donor used to get "Invoice creation failed. Please try again."; now they get
 * Coinsnap's message, or a translated "may be too small" when the 400 has none.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['t_fail']  = 0;
$GLOBALS['t_posts'] = 0;
$GLOBALS['t_reply'] = null;
function t_ok( $name, $cond ) {
	echo ( $cond ? 'PASS ' : 'FAIL ' ) . $name . "\n";
	$GLOBALS['t_fail'] += $cond ? 0 : 1;
}
function t_resp( $code, $body ) {
	return array(
		'response' => array( 'code' => $code, 'message' => '' ),
		'body'     => is_string( $body ) ? $body : wp_json_encode( $body ),
		'headers'  => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary( array() ),
		'cookies'  => array(),
	);
}

// On a site with several Coinsnap plugins another plugin's coinsnap-core copy may be loaded instead.
$loaded = ( new ReflectionClass( '\CoinsnapCore\Providers\CoinsnapProvider' ) )->getFileName();
echo 'coinsnap-core loaded from ' . str_replace( WP_CONTENT_DIR, 'wp-content', $loaded ) . "\n";
t_ok( "this plugin's coinsnap-core copy is the one loaded", 0 === strpos( $loaded, COINSNAP_BITCOIN_DONATION_PLUGIN_PATH ) );

$core = coinsnap_bitcoin_donation_get_core();
add_filter( 'pre_option_' . $core->option_key(), function () {
	return array( 'payment_provider' => 'coinsnap', 'coinsnap_api_key' => 'test-key', 'coinsnap_store_id' => 'test-store', 'coinsnap_api_base' => 'https://coinsnap.test' );
} );
add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	if ( 0 === strpos( $url, 'https://api.kraken.com/' ) ) {
		return t_resp( 200, array( 'error' => array(), 'result' => array( 'XXBTZEUR' => array( 'c' => array( '100000.0' ) ), 'XXBTZUSD' => array( 'c' => array( '110000.0' ) ) ) ) );
	}
	if ( 0 === strpos( $url, 'https://coinsnap.test/' ) ) {
		$GLOBALS['t_posts']++;
		if ( false === strpos( $url, '/api/v1/' ) ) {
			return t_resp( 404, '<pre>Cannot POST</pre>' );
		}
		return $GLOBALS['t_reply'];
	}
	return new WP_Error( 'test', 'Blocked by test: ' . $url );
}, 10, 3 );

function t_donate( $amount, $currency ) {
	$GLOBALS['t_posts'] = 0;
	$req = new WP_REST_Request( 'POST', '/coinsnap-bitcoin-donation/v1/payment/create' );
	$req->set_header( 'Content-Type', 'application/json' );
	$req->set_body( wp_json_encode( array( 'amount' => $amount, 'currency' => $currency, 'message' => 'test', 'redirectUrl' => home_url(), 'metadata' => array( 'donorName' => 'Test' ) ) ) );
	$res = rest_do_request( $req );
	return array( $res->get_status(), (string) ( $res->get_data()['message'] ?? '' ) );
}

$dust = 'Amount is below the on-chain dust threshold. Enable Lightning on this store to accept smaller amounts.';

$GLOBALS['t_reply'] = t_resp( 400, array( 'message' => $dust ) );
list( $code, $msg ) = t_donate( 100, 'SATS' );
t_ok( '100 SATS refused by Coinsnap: its message is shown (got ' . $code . ' "' . $msg . '")', 400 === $code && $dust === $msg );
t_ok( '100 SATS: only the v1 endpoint was called (got ' . $GLOBALS['t_posts'] . ')', 1 === $GLOBALS['t_posts'] );

$GLOBALS['t_reply'] = t_resp( 400, array( 'error' => 'x' ) );
list( $code, $msg ) = t_donate( 100, 'SATS' );
t_ok( '400 without message: translated "may be too small" (got ' . $code . ' "' . $msg . '")', 400 === $code && 'The payment provider refused this amount; it may be too small.' === $msg );

// Auth and server errors still give the generic message (no API text to visitors).
foreach ( array( array( 401, array( 'message' => 'Invalid API key' ) ), array( 500, array( 'message' => 'boom' ) ) ) as $case ) {
	$GLOBALS['t_reply'] = t_resp( $case[0], $case[1] );
	list( $code, $msg ) = t_donate( 1000, 'SATS' );
	t_ok( 'Coinsnap ' . $case[0] . ': generic message (got ' . $code . ' "' . $msg . '")', 500 === $code && 'Invoice creation failed. Please try again.' === $msg );
}

// The plugin's own minimum is unchanged (Coinsnap decides about small amounts); its message stays readable.
list( $code, $msg ) = t_donate( 0.5, 'SATS' );
t_ok( '0.5 SATS: "Amount cannot be less than 1 SATS." (got "' . $msg . '")', 400 === $code && 'Amount cannot be less than 1 SATS.' === $msg );
list( $code, $msg ) = t_donate( 0.0000001, 'BTC' );
t_ok( '0.0000001 BTC: minimum written as 0.000001, not 1.0E-6 (got "' . $msg . '")', 400 === $code && 'Amount cannot be less than 0.000001 BTC.' === $msg );

echo $GLOBALS['t_fail'] ? "\n" . $GLOBALS['t_fail'] . " failed\n" : "\nall passed\n";
