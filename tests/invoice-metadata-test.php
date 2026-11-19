<?php
/**
 * Invoice orderId/metadata test, run inside a throwaway WordPress with the plugin active:
 *   wp eval-file wp-content/plugins/coinsnap-bitcoin-donation/tests/invoice-metadata-test.php
 *
 * For Coinsnap and BTCPay Server: starts a donation through the REST route, captures the
 * create-invoice request (orderId, metadata), then sends a signed Settled webhook and checks the
 * donation is applied. The API is faked with pre_http_request; nothing leaves the site.
 * It overwrites this plugin's settings and webhook secrets: never run it on a real site.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['t_fail']     = 0;
$GLOBALS['t_sent']     = null;
$GLOBALS['t_invoices'] = array();
function t_ok( $name, $cond ) {
	echo ( $cond ? 'PASS ' : 'FAIL ' ) . $name . "\n";
	$GLOBALS['t_fail'] += $cond ? 0 : 1;
}
function t_resp( $code, $body ) {
	return array( 'response' => array( 'code' => $code, 'message' => '' ), 'body' => wp_json_encode( $body ), 'headers' => array(), 'cookies' => array() );
}

// Fake Coinsnap / BTCPay API: create returns a New invoice, GET returns what was stored.
add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	$method = strtoupper( $args['method'] ?? 'GET' );
	if ( 'POST' === $method && preg_match( '#/stores/[^/]+/invoices$#', $url ) ) {
		$body                = json_decode( $args['body'], true );
		$GLOBALS['t_sent']   = $body;
		$id                  = 'inv' . wp_generate_password( 12, false, false );
		$meta                = $body['metadata'] ?? array();
		$is_btcpay           = 0 === strpos( $url, 'https://btc.test' );
		if ( ! $is_btcpay ) {
			// Coinsnap copies the top-level orderId into metadata, or makes its own when none is sent.
			$meta['orderId'] = $body['orderId'] ?? 'CS-20261119-0001';
		}
		$GLOBALS['t_invoices'][ $id ] = array(
			'id'               => $id,
			'status'           => 'New',
			'additionalStatus' => 'None',
			'amount'           => (string) $body['amount'],
			'invoiceAmount'    => (string) $body['amount'],
			'currency'         => $body['currency'],
			'metadata'         => $meta,
			'createdAt'        => time(),
			'checkoutLink'     => 'https://pay.test/i/' . $id,
		);
		return t_resp( 200, $GLOBALS['t_invoices'][ $id ] );
	}
	if ( 'GET' === $method && preg_match( '#/invoices/([A-Za-z0-9]+)$#', $url, $m ) && isset( $GLOBALS['t_invoices'][ $m[1] ] ) ) {
		return t_resp( 200, $GLOBALS['t_invoices'][ $m[1] ] );
	}
	return new WP_Error( 'http_request_failed', 'offline test' );
}, 10, 3 );

global $wpdb;
$core  = coinsnap_bitcoin_donation_get_core();
$table = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
update_option( 'coinsnap_donation_webhook', array(
	'coinsnap' => array( 'id' => 'wh1', 'secret' => 'test-secret-coinsnap' ),
	'btcpay'   => array( 'id' => 'wh2', 'secret' => 'test-secret-btcpay' ),
) );

$form_id = wp_insert_post( array( 'post_type' => 'donation-form', 'post_status' => 'publish', 'post_title' => 'Library fund' ) );
update_post_meta( $form_id, '_coinsnap_donation_form_form_type', 'simple_donation' );
update_post_meta( $form_id, '_coinsnap_donation_form_public_donors', '1' );
update_post_meta( $form_id, '_coinsnap_donation_form_custom_field_name', 'Company' );

foreach ( array( 'coinsnap', 'btcpay' ) as $provider ) {
	update_option( 'coinsnap_bitcoin_donation_options', array(
		'payment_provider'  => $provider,
		'coinsnap_api_key'  => 'test-key',
		'coinsnap_store_id' => 'STORE1',
		'btcpay_host'       => 'https://btc.test',
		'btcpay_api_key'    => 'test-key',
		'btcpay_store_id'   => 'STORE2',
	) );
	$GLOBALS['t_sent'] = null;

	$req = new WP_REST_Request( 'POST', '/coinsnap-bitcoin-donation/v1/payment/create' );
	$req->set_header( 'Content-Type', 'application/json' );
	$req->set_body( wp_json_encode( array(
		'amount'      => 15,
		'currency'    => 'EUR',
		'message'     => 'Keep going',
		'redirectUrl' => home_url( '/donate/' ),
		'metadata'    => array(
			'donationFormId' => $form_id,
			'donorName'      => 'Jane Doe',
			'donorEmail'     => 'jane@example.test',
			'donorAddress'   => 'Main St 1, Vienna',
			'donorMessage'   => 'For the books',
			'donorCustom'    => 'ACME',
		),
	) ) );
	$res  = rest_do_request( $req );
	$data = $res->get_data();
	$inv  = (string) ( $data['invoice_id'] ?? '' );
	t_ok( "$provider: invoice created", 200 === $res->get_status() && '' !== $inv );

	$sent = is_array( $GLOBALS['t_sent'] ) ? $GLOBALS['t_sent'] : array();
	$m    = $sent['metadata'] ?? array();
	$row  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE payment_invoice_id = %s", $inv ) );
	$txn  = $row ? (string) $row->transaction_id : '';
	t_ok( "$provider: metadata.orderId is the stored transaction_id ($txn)", '' !== $txn && ( $m['orderId'] ?? '' ) === $txn && ( $m['orderNumber'] ?? '' ) === $txn );
	if ( 'coinsnap' === $provider ) {
		t_ok( 'coinsnap: top-level orderId is the transaction_id', ( $sent['orderId'] ?? '' ) === $txn );
		t_ok( 'coinsnap: top-level buyerEmail', ( $sent['buyerEmail'] ?? '' ) === 'jane@example.test' );
		t_ok( 'coinsnap: invoice as stored by Coinsnap keeps the transaction_id', ( $GLOBALS['t_invoices'][ $inv ]['metadata']['orderId'] ?? '' ) === $txn );
	} else {
		t_ok( 'btcpay: no top-level orderId/buyerEmail (BTCPay reads metadata)', ! isset( $sent['orderId'] ) && ! isset( $sent['buyerEmail'] ) );
	}
	t_ok( "$provider: buyer fields", ( $m['buyerName'] ?? '' ) === 'Jane Doe' && ( $m['customerName'] ?? '' ) === 'Jane Doe'
		&& ( $m['buyerEmail'] ?? '' ) === 'jane@example.test' && ( $m['buyerAddress1'] ?? '' ) === 'Main St 1, Vienna'
		&& ( $m['buyerMessage'] ?? '' ) === 'For the books' );
	t_ok( "$provider: item fields", ( $m['itemDesc'] ?? '' ) === 'Keep going' && ( $m['itemCode'] ?? '' ) === "donation-form-$form_id"
		&& ( $m['formId'] ?? '' ) === (string) $form_id && ( $m['formTitle'] ?? '' ) === 'Library fund'
		&& ( $m['formFields'] ?? null ) === array( 'Company' => 'ACME' ) );
	$li = $m['lineItems'][0] ?? array();
	t_ok( "$provider: lineItems", 1 === count( $m['lineItems'] ?? array() ) && 'Library fund' === ( $li['name'] ?? '' ) && 1 === ( $li['quantity'] ?? 0 ) && 15 == ( $li['total'] ?? 0 ) );
	t_ok( "$provider: platform fields", 'wordpress' === ( $m['platform'] ?? '' ) && COINSNAP_BITCOIN_DONATION_VERSION === ( $m['pluginVersion'] ?? '' )
		&& home_url( '/donate/' ) === ( $m['pageUrl'] ?? '' ) && '' !== ( $m['language'] ?? '' ) );
	t_ok( "$provider: existing keys unchanged", 'Jane Doe' === ( $m['donorname'] ?? '' ) && 'jane@example.test' === ( $m['donoremail'] ?? '' )
		&& 'Bitcoin Donation' === ( $m['type'] ?? '' ) && 'Bitcoin Donation' === ( $m['formType'] ?? '' )
		&& '1' === ( $m['publicdonor'] ?? '' ) && (string) $form_id === ( $m['donationformid'] ?? '' ) && $form_id === ( $m['form_id'] ?? 0 ) );
	$empty = array_filter( $m, function ( $v ) { return '' === $v || null === $v; } );
	t_ok( "$provider: no empty values sent", array() === $empty );
	t_ok( "$provider: no secrets in metadata", false === strpos( wp_json_encode( $sent ), 'test-key' ) );

	// Settled webhook, signed with this plugin's secret: matched by invoice ID, applied once.
	$GLOBALS['t_invoices'][ $inv ]['status'] = 'Settled';
	$body = wp_json_encode( array( 'invoiceId' => $inv, 'type' => 'btcpay' === $provider ? 'InvoiceSettled' : 'Settled' ) );
	$wh   = new WP_REST_Request( 'POST', '/coinsnap-bitcoin-donation/v1/webhook/' . $provider );
	$wh->set_body( $body );
	if ( 'btcpay' === $provider ) {
		$wh->set_header( 'BTCPay-Sig', 'sha256=' . hash_hmac( 'sha256', $body, 'test-secret-btcpay' ) );
	} else {
		$ts = time();
		$wh->set_header( 'X-Coinsnap-Signature', 't=' . $ts . ',v1=' . hash_hmac( 'sha256', $ts . '.' . $body, 'test-secret-coinsnap' ) );
	}
	$whr    = rest_do_request( $wh );
	$status = $wpdb->get_var( $wpdb->prepare( "SELECT payment_status FROM {$table} WHERE payment_invoice_id = %s", $inv ) );
	$posts  = get_posts( array( 'post_type' => 'bitcoin-pds', 'post_status' => 'any', 'meta_key' => '_coinsnap_bitcoin_donation_payment_id', 'meta_value' => $inv, 'fields' => 'ids' ) );
	t_ok( "$provider: signed Settled webhook marks the donation paid", 200 === $whr->get_status() && 'paid' === $status && 1 === count( $posts ) );
	$again = rest_do_request( $wh );
	$posts = get_posts( array( 'post_type' => 'bitcoin-pds', 'post_status' => 'any', 'meta_key' => '_coinsnap_bitcoin_donation_payment_id', 'meta_value' => $inv, 'fields' => 'ids' ) );
	t_ok( "$provider: repeated webhook applies nothing twice", 200 === $again->get_status() && 1 === count( $posts ) );
}

echo $GLOBALS['t_fail'] ? $GLOBALS['t_fail'] . " failed\n" : "all passed\n";
if ( $GLOBALS['t_fail'] ) {
	exit( 1 );
}
