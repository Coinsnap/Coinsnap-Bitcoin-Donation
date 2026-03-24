<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Form_Renderer {

    const META_PREFIX = '_coinsnap_donation_form_';

    public function __construct() {
        add_shortcode( 'coinsnap_bitcoin_donation_form', array( $this, 'render_form' ) );
        add_shortcode( 'coinsnap_donation_list', array( $this, 'render_list' ) );
    }

    public static function render_form( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0, 'layout' => '' ), $atts );
        $post_id = absint( $atts['id'] );

        if ( ! $post_id || get_post_type( $post_id ) !== 'donation-form' ) {
            return '';
        }

        $meta = self::load_meta( $post_id );
        $form_type = $meta['form_type'] ?? 'simple_donation';
        // Allow layout override from legacy wide shortcodes
        $layout = ! empty( $atts['layout'] ) ? $atts['layout'] : ( $meta['layout'] ?: 'NARROW' );

        $template_map = array(
            'simple_donation_NARROW' => 'simple-donation-narrow',
            'simple_donation_WIDE'   => 'simple-donation-wide',
            'multi_amount_NARROW'    => 'multi-amount-narrow',
            'multi_amount_WIDE'      => 'multi-amount-wide',
            'shoutout'               => 'shoutout-form',
        );

        $key = ( $form_type === 'shoutout' ) ? 'shoutout' : $form_type . '_' . $layout;
        $template_name = $template_map[ $key ] ?? 'simple-donation-narrow';

        return self::render_template( $template_name, $meta, $post_id );
    }

    public static function render_list( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts );
        $post_id = absint( $atts['id'] );

        if ( ! $post_id || get_post_type( $post_id ) !== 'donation-form' ) {
            return '';
        }

        $meta = self::load_meta( $post_id );

        // Query shoutout posts scoped to this form
        $query_args = array(
            'post_type'      => 'bitcoin-shoutouts',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_coinsnap_donation_form_id',
                    'value' => $post_id,
                ),
            ),
        );
        $query = new WP_Query( $query_args );
        $shoutouts = array();

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $shoutouts[] = array(
                    'date'        => $post->post_date,
                    'name'        => get_post_meta( $post->ID, '_coinsnap_bitcoin_donation_shoutouts_name', true ),
                    'amount'      => get_post_meta( $post->ID, '_coinsnap_bitcoin_donation_shoutouts_amount', true ),
                    'sats_amount' => get_post_meta( $post->ID, '_coinsnap_bitcoin_donation_shoutouts_sats_amount', true ),
                    'message'     => get_post_meta( $post->ID, '_coinsnap_bitcoin_donation_shoutouts_message', true ),
                );
            }
        }
        wp_reset_postdata();

        return self::render_template( 'shoutout-list', $meta, $post_id, $shoutouts );
    }

    public static function resolve_legacy_shortcode( $shortcode_name ) {
        $mapping = get_option( 'coinsnap_donation_migrated_forms', array() );
        return isset( $mapping[ $shortcode_name ] ) ? absint( $mapping[ $shortcode_name ] ) : 0;
    }

    private static function load_meta( $post_id ) {
        $fields = array(
            'form_type', 'layout', 'currency', 'button_text', 'title_text',
            'default_amount', 'default_message', 'redirect_url', 'public_donors',
            'first_name', 'last_name', 'email', 'address',
            'custom_field_name', 'custom_field_visibility',
            'donor_notice', 'custom_checkbox_label',
            'snap1', 'snap2', 'snap3', 'minimum_amount', 'premium_amount',
        );

        $meta = array();
        foreach ( $fields as $field ) {
            $meta[ $field ] = get_post_meta( $post_id, self::META_PREFIX . $field, true );
        }
        return $meta;
    }

    private static function render_template( $template_name, $meta, $form_id, $shoutouts = array() ) {
        $core = coinsnap_bitcoin_donation_get_core();
        $core_settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        $theme = $core_settings['theme'] ?? 'light';
        $theme_class = $theme === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme' : 'coinsnap-bitcoin-donation-light-theme';
        $modal_theme = $theme === 'dark' ? 'dark-theme' : 'light-theme';

        $coinsnapCurrencies = defined( 'COINSNAP_CURRENCIES' ) ? COINSNAP_CURRENCIES : array( 'EUR', 'USD', 'SATS', 'BTC', 'CAD', 'JPY', 'GBP', 'CHF', 'RUB' );
        $exchange = new \CoinsnapCore\Util\ExchangeRates();
        $rates = $exchange->load_rates();

        // Extract meta to template variables
        $title_text      = $meta['title_text'] ?: __( 'Donate with Bitcoin', 'coinsnap-bitcoin-donation' );
        $button_text     = $meta['button_text'] ?: __( 'Donate', 'coinsnap-bitcoin-donation' );
        $default_currency = $meta['currency'] ?: 'EUR';
        $first_name      = $meta['first_name'] ?: 'hidden';
        $last_name       = $meta['last_name'] ?: 'hidden';
        $email           = $meta['email'] ?: 'hidden';
        $address         = $meta['address'] ?: 'hidden';
        $public_donors   = $meta['public_donors'] ?: '';
        $custom          = $meta['custom_field_visibility'] ?: 'hidden';
        $custom_name     = $meta['custom_field_name'] ?: '';
        $donor_notice          = $meta['donor_notice'] ?: '';
        $custom_checkbox_label = $meta['custom_checkbox_label'] ?: '';
        $default_amount  = $meta['default_amount'] ?: '5';
        $default_message = $meta['default_message'] ?: __( 'Thank you for your support!', 'coinsnap-bitcoin-donation' );
        $redirect_url    = $meta['redirect_url'] ?: home_url();
        $snap1           = $meta['snap1'] ?: '50';
        $snap2           = $meta['snap2'] ?: '100';
        $snap3           = $meta['snap3'] ?: '200';
        $min_amount      = (float) ( $meta['minimum_amount'] ?: 500 );
        $premium_amount  = (float) ( $meta['premium_amount'] ?: 10000 );

        $template_path = COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'templates/' . $template_name . '.php';

        if ( ! file_exists( $template_path ) ) {
            return '';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }
}
