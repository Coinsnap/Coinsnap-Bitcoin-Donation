<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Coinsnap_Bitcoin_Donation_Form_CPT {

	const POST_TYPE   = 'donation-form';
	const META_PREFIX = '_coinsnap_donation_form_';

	public function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'init', array( $this, 'register_meta_fields' ) );
	}

	public function register_cpt() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name'               => __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
					'singular_name'      => __( 'Donation Form', 'coinsnap-bitcoin-donation' ),
					'menu_name'          => __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
					'add_new'            => __( 'Add New', 'coinsnap-bitcoin-donation' ),
					'add_new_item'       => __( 'Add New Donation Form', 'coinsnap-bitcoin-donation' ),
					'edit_item'          => __( 'Edit Donation Form', 'coinsnap-bitcoin-donation' ),
					'new_item'           => __( 'New Donation Form', 'coinsnap-bitcoin-donation' ),
					'view_item'          => __( 'View Donation Form', 'coinsnap-bitcoin-donation' ),
					'search_items'       => __( 'Search Donation Forms', 'coinsnap-bitcoin-donation' ),
					'not_found'          => __( 'No donation forms found', 'coinsnap-bitcoin-donation' ),
					'not_found_in_trash' => __( 'No donation forms found in Trash', 'coinsnap-bitcoin-donation' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'supports'           => array( 'title' ),
				'show_in_rest'       => true,
			)
		);
	}

	public function register_meta_fields() {
		$string_fields = array(
			'form_type',
			'layout',
			'currency',
			'button_text',
			'title_text',
			'default_amount',
			'default_message',
			'redirect_url',
			'first_name',
			'last_name',
			'email',
			'address',
			'custom_field_name',
			'custom_field_visibility',
			'snap1',
			'snap2',
			'snap3',
			'minimum_amount',
			'premium_amount',
		);

		foreach ( $string_fields as $field ) {
			register_post_meta(
				self::POST_TYPE,
				self::META_PREFIX . $field,
				array(
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => '',
				)
			);
		}

		// public_donors stored as '1' or '' (not boolean)
		register_post_meta(
			self::POST_TYPE,
			self::META_PREFIX . 'public_donors',
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => '',
			)
		);
	}
}
