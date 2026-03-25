<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Migration {

    const FLAG_KEY = 'coinsnap_donation_forms_migrated';
    const MAP_KEY  = 'coinsnap_donation_migrated_forms';
    const META_PREFIX = '_coinsnap_donation_form_';

    public static function maybe_migrate() {
        // Ensure CPT is registered before creating posts
        if ( ! post_type_exists( 'donation-form' ) ) {
            // Register it now — init may not have fired yet (activation, WP-CLI, etc.)
            $cpt = new Coinsnap_Bitcoin_Donation_Form_CPT();
            $cpt->register_cpt();
        }

        if ( ! post_type_exists( 'donation-form' ) ) {
            return; // Still not registered — bail
        }

        // If CPT forms already exist, nothing to do
        $existing = get_posts( array(
            'post_type'      => 'donation-form',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );
        if ( ! empty( $existing ) ) {
            update_option( self::FLAG_KEY, '1' );
            return;
        }

        // Flag set but no forms exist — previous migration failed, clear and retry
        if ( get_option( self::FLAG_KEY ) ) {
            delete_option( self::FLAG_KEY );
            delete_option( self::MAP_KEY );
        }

        $options = get_option( 'coinsnap_bitcoin_donation_forms_options', array() );
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        self::run_migration( $options );
    }

    private static function run_migration( $options ) {
        $mapping = array();

        // Migrate Simple Donation
        $simple_id = self::create_form_post( 'Simple Donation', 'simple_donation', $options, array(
            'currency'                => 'currency',
            'button_text'             => 'button_text',
            'title_text'              => 'title_text',
            'default_amount'          => 'default_amount',
            'default_message'         => 'default_message',
            'redirect_url'            => 'redirect_url',
            'form_type'               => 'layout',
            'simple_donation_public_donors' => 'public_donors',
            'simple_donation_first_name'    => 'first_name',
            'simple_donation_last_name'     => 'last_name',
            'simple_donation_email'         => 'email',
            'simple_donation_address'       => 'address',
            'simple_donation_custom_field_name'       => 'custom_field_name',
            'simple_donation_custom_field_visibility' => 'custom_field_visibility',
        ) );
        $mapping['coinsnap_bitcoin_donation']      = $simple_id;
        $mapping['coinsnap_bitcoin_donation_wide'] = $simple_id;

        // Migrate Multi Amount
        $multi_id = self::create_form_post( 'Multi Amount Donation', 'multi_amount', $options, array(
            'multi_amount_currency'      => 'currency',
            'multi_amount_button_text'   => 'button_text',
            'multi_amount_title_text'    => 'title_text',
            'multi_amount_default_amount'  => 'default_amount',
            'multi_amount_default_message' => 'default_message',
            'multi_amount_redirect_url'    => 'redirect_url',
            'multi_amount_form_type'       => 'layout',
            'multi_amount_default_snap1'   => 'snap1',
            'multi_amount_default_snap2'   => 'snap2',
            'multi_amount_default_snap3'   => 'snap3',
            'multi_amount_public_donors'   => 'public_donors',
            'multi_amount_first_name'      => 'first_name',
            'multi_amount_last_name'       => 'last_name',
            'multi_amount_email'           => 'email',
            'multi_amount_address'         => 'address',
            'multi_amount_custom_field_name'       => 'custom_field_name',
            'multi_amount_custom_field_visibility' => 'custom_field_visibility',
        ) );
        $mapping['multi_amount_donation']      = $multi_id;
        $mapping['multi_amount_donation_wide'] = $multi_id;

        // Migrate Shoutout
        $shoutout_id = self::create_form_post( 'Shoutout', 'shoutout', $options, array(
            'shoutout_currency'       => 'currency',
            'shoutout_button_text'    => 'button_text',
            'shoutout_title_text'     => 'title_text',
            'shoutout_default_amount' => 'default_amount',
            'shoutout_default_message'  => 'default_message',
            'shoutout_redirect_url'     => 'redirect_url',
            'shoutout_minimum_amount'   => 'minimum_amount',
            'shoutout_premium_amount'   => 'premium_amount',
            'shoutout_public_donors'    => 'public_donors',
            'shoutout_first_name'       => 'first_name',
            'shoutout_last_name'        => 'last_name',
            'shoutout_email'            => 'email',
            'shoutout_address'          => 'address',
            'shoutout_custom_field_name'       => 'custom_field_name',
            'shoutout_custom_field_visibility' => 'custom_field_visibility',
        ) );
        $mapping['shoutout_form'] = $shoutout_id;
        $mapping['shoutout_list'] = $shoutout_id;

        // Tag existing shoutout posts with the migrated form ID
        $shoutout_posts = get_posts( array(
            'post_type'      => 'bitcoin-shoutouts',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );
        foreach ( $shoutout_posts as $sp_id ) {
            update_post_meta( $sp_id, '_coinsnap_donation_form_id', $shoutout_id );
        }

        // Only set flag if at least one post was created successfully
        $created = array_filter( $mapping );
        if ( empty( $created ) ) {
            return; // Retry on next load
        }

        update_option( self::MAP_KEY, $mapping );
        update_option( self::FLAG_KEY, '1' );
    }

    private static function create_form_post( $title, $form_type, $old_options, $field_map ) {
        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_type'   => 'donation-form',
        ) );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return 0;
        }

        update_post_meta( $post_id, self::META_PREFIX . 'form_type', $form_type );

        foreach ( $field_map as $old_key => $new_key ) {
            $value = $old_options[ $old_key ] ?? '';
            update_post_meta( $post_id, self::META_PREFIX . $new_key, $value );
        }

        return $post_id;
    }
}
