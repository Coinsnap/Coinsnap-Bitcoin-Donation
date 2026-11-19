<?php
/**
 * Invoice metadata builder shared by the Coinsnap and BTCPay providers.
 *
 * @package coinsnap-core
 */

declare(strict_types=1);

namespace CoinsnapCore\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the $invoice_data a consuming plugin passes to create_invoice() into the
 * order ID, buyer email and metadata sent to the payment server.
 */
final class InvoiceMetadata {

	/**
	 * Build the order ID and metadata for an invoice.
	 *
	 * $invoice_data keys read here (all optional):
	 * - order_id:     the plugin's ID of what is paid. Sent as top-level orderId (Coinsnap) and metadata.orderId.
	 * - order_number: the number the merchant sees, if it differs from order_id. Defaults to order_id.
	 * - email / buyer_email (legacy alias), name, description: buyer email, buyer name, item description.
	 * - metadata:     any further metadata (strings, numbers, nested arrays such as lineItems or formFields).
	 *                 Its keys are merged last, so they win over the ones built here.
	 *
	 * Empty values ('' and null, and arrays left empty) are left out.
	 *
	 * @param int   $form_id      Form or source ID of the consuming plugin.
	 * @param array $invoice_data Invoice data passed to create_invoice().
	 * @return array{order_id: string, buyer_email: string, metadata: array}
	 */
	public static function build( int $form_id, array $invoice_data ): array {
		$buyer_email  = self::text( $invoice_data['email'] ?? $invoice_data['buyer_email'] ?? '' );
		$buyer_name   = self::text( $invoice_data['name'] ?? '' );
		$item_desc    = self::text( $invoice_data['description'] ?? '' );
		$order_id     = self::text( $invoice_data['order_id'] ?? '' );
		$order_number = self::text( $invoice_data['order_number'] ?? '' );
		$custom_meta  = ( isset( $invoice_data['metadata'] ) && is_array( $invoice_data['metadata'] ) ) ? $invoice_data['metadata'] : array();

		$metadata = self::clean(
			array_merge(
				array(
					'form_id'      => $form_id,
					'email'        => $buyer_email,
					'buyerEmail'   => $buyer_email,
					'buyerName'    => $buyer_name,
					'customerName' => $buyer_name,
					'itemDesc'     => $item_desc,
					'orderId'      => $order_id,
					'orderNumber'  => '' !== $order_number ? $order_number : $order_id,
				),
				$custom_meta
			)
		);

		// One order ID everywhere: the top-level field and metadata.orderId carry the same value.
		$order_id = self::text( $metadata['orderId'] ?? '' );
		if ( '' !== $order_id ) {
			$metadata['orderId'] = $order_id;
		}

		return array(
			'order_id'    => $order_id,
			'buyer_email' => self::text( $metadata['buyerEmail'] ?? '' ),
			'metadata'    => $metadata,
		);
	}

	/**
	 * A scalar as a trimmed string; anything else as ''.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function text( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '';
		}
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Drop null, '' and empty arrays, also inside nested arrays. Objects are dropped too.
	 * A list (such as lineItems) stays a list.
	 *
	 * @param array $data Metadata.
	 * @return array
	 */
	private static function clean( array $data ): array {
		$is_list = array() === $data || array_keys( $data ) === range( 0, count( $data ) - 1 );
		$out     = array();
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = self::clean( $value );
				if ( array() === $value ) {
					continue;
				}
			} elseif ( null === $value || ( is_string( $value ) && '' === trim( $value ) ) || is_object( $value ) || is_resource( $value ) ) {
				continue;
			}
			$out[ $key ] = $value;
		}
		return $is_list ? array_values( $out ) : $out;
	}
}
