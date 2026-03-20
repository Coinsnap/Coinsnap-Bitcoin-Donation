<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class coinsnap_bitcoin_donation_Webhooks {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        $namespace = 'coinsnap-bitcoin-donation/v1';

        register_rest_route( $namespace, '/payment/create', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_payment' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $namespace, '/status/(?P<invoice_id>[a-zA-Z0-9]+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'check_payment_status' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'invoice_id' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        register_rest_route( $namespace, '/webhook/coinsnap', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_coinsnap_webhook' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $namespace, '/webhook/btcpay', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_btcpay_webhook' ),
            'permission_callback' => '__return_true',
        ) );

        // Legacy webhook endpoint — old installations may still have this URL registered
        register_rest_route( $namespace, '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_legacy_webhook' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function create_payment( WP_REST_Request $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid nonce.' ), 403 );
        }

        $core     = coinsnap_bitcoin_donation_get_core();
        $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        $params   = $request->get_json_params();

        $amount      = isset( $params['amount'] ) ? floatval( $params['amount'] ) : 0;
        $currency    = isset( $params['currency'] ) ? strtoupper( sanitize_text_field( $params['currency'] ) ) : 'SATS';
        $message     = isset( $params['message'] ) ? sanitize_textarea_field( $params['message'] ) : '';
        $form_type   = isset( $params['formType'] ) ? sanitize_text_field( $params['formType'] ) : 'Bitcoin Donation';
        $redirect    = isset( $params['redirectUrl'] ) ? esc_url_raw( $params['redirectUrl'] ) : home_url();
        $metadata    = isset( $params['metadata'] ) ? $params['metadata'] : array();

        if ( $amount <= 0 ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Invalid amount.', 'coinsnap-bitcoin-donation' ) ), 400 );
        }

        $exchange_rates = new \CoinsnapCore\Util\ExchangeRates();
        $provider_name = $settings['payment_provider'] ?? 'coinsnap';
        $mode = ( 'btcpay' === $provider_name ) ? 'lightning' : 'coinsnap';
        $check = $exchange_rates->check_payment_data( $amount, $currency, $mode );
        if ( isset( $check['result'] ) && $check['result'] === false ) {
            $error_msg = '';
            if ( $check['error'] === 'currencyError' ) {
                $error_msg = sprintf( __( 'Currency %s is not supported.', 'coinsnap-bitcoin-donation' ), $currency );
            } elseif ( $check['error'] === 'amountError' ) {
                $error_msg = sprintf( __( 'Amount cannot be less than %s %s.', 'coinsnap-bitcoin-donation' ), $check['min_value'], $currency );
            }
            return new WP_REST_Response( array( 'success' => false, 'message' => $error_msg ), 400 );
        }

        $amount_cents = intval( round( $amount * 100 ) );
        if ( strtoupper( $currency ) === 'SATS' ) {
            $amount_cents = intval( $amount );
        }

        $sanitized_metadata = array();
        foreach ( $metadata as $key => $val ) {
            $sanitized_metadata[ sanitize_key( $key ) ] = sanitize_text_field( $val );
        }
        $sanitized_metadata['modal']     = '1';
        $sanitized_metadata['formType']  = $form_type;

        $invoice_data = array(
            'message'     => $message,
            'redirect'    => $redirect,
            'metadata'    => $sanitized_metadata,
            'buyer_email' => isset( $sanitized_metadata['donoremail'] ) ? sanitize_email( $sanitized_metadata['donoremail'] ) : '',
        );

        try {
            $provider = \CoinsnapCore\Util\ProviderFactory::create( $core );
            $result   = $provider->create_invoice( 0, $amount_cents, $currency, $invoice_data );

            if ( isset( $result['error'] ) || empty( $result['invoice_id'] ) ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => $result['error'] ?? 'Invoice creation failed.' ), 500 );
            }

            global $wpdb;
            $table_name = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
            $wpdb->insert( $table_name, array(
                'source_id'          => 0,
                'transaction_id'     => 'donation_' . time() . '_' . wp_generate_password( 8, false ),
                'customer_name'      => $sanitized_metadata['donorname'] ?? '',
                'customer_email'     => $sanitized_metadata['donoremail'] ?? '',
                'amount'             => $amount,
                'currency'           => $currency,
                'description'        => $form_type,
                'payment_provider'   => $provider_name,
                'payment_invoice_id' => $result['invoice_id'],
                'payment_status'     => 'unpaid',
                'payment_url'        => $result['payment_url'],
                'ip'                 => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
                'created_at'         => current_time( 'mysql' ),
            ) );

            return new WP_REST_Response( array(
                'success'      => true,
                'invoice_id'   => $result['invoice_id'],
                'payment_url'  => $result['payment_url'],
                'redirect_url' => $redirect,
            ), 200 );

        } catch ( \Exception $e ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $e->getMessage() ), 500 );
        }
    }

    public function check_payment_status( WP_REST_Request $request ) {
        $invoice_id = $request->get_param( 'invoice_id' );
        $core       = coinsnap_bitcoin_donation_get_core();

        global $wpdb;

        $table_name = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
        $status = $wpdb->get_var( $wpdb->prepare(
            "SELECT payment_status FROM {$table_name} WHERE payment_invoice_id = %s",
            $invoice_id
        ) );

        if ( $status === 'paid' ) {
            return new WP_REST_Response( array( 'success' => true, 'data' => array( 'paid' => true ) ), 200 );
        }

        $old_table = $wpdb->prefix . 'donation_payments';
        $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $old_table ) );

        if ( $table_exists ) {
            $old_status = $wpdb->get_var( $wpdb->prepare(
                "SELECT status FROM {$old_table} WHERE payment_id = %s",
                $invoice_id
            ) );

            if ( $old_status === 'completed' ) {
                return new WP_REST_Response( array( 'success' => true, 'data' => array( 'paid' => true ) ), 200 );
            }
        }

        return new WP_REST_Response( array( 'success' => true, 'data' => array( 'paid' => false ) ), 200 );
    }

    public function handle_coinsnap_webhook( WP_REST_Request $request ) {
        $core = coinsnap_bitcoin_donation_get_core();
        $data = $request->get_json_params();

        // Skip signature verification if disabled in settings (for debugging)
        $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        if ( empty( $settings['disable_webhook_verification'] ) ) {
            // Verify using request body from WP_REST_Request (php://input may be empty)
            $webhook_option = get_option( $core->webhook_key() );
            $secret = '';
            if ( is_array( $webhook_option ) && ! empty( $webhook_option['coinsnap']['secret'] ) ) {
                $secret = (string) $webhook_option['coinsnap']['secret'];
            }

            $sig_headers = array(
                $request->get_header( 'btcpay-signature' ) ?? '',
                $request->get_header( 'x-coinsnap-signature' ) ?? '',
                $request->get_header( 'x-signature' ) ?? '',
                $request->get_header( 'x-coinsnap-sig' ) ?? '',
            );

            $body = $request->get_body();
            $verified = false;

            if ( $secret && $body ) {
                $raw = hash_hmac( 'sha256', $body, $secret );
                $with_prefix = 'sha256=' . $raw;
                foreach ( $sig_headers as $sig ) {
                    if ( $sig && ( hash_equals( $raw, $sig ) || hash_equals( $with_prefix, $sig ) ) ) {
                        $verified = true;
                        break;
                    }
                }
            }

            if ( ! $verified ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid signature.' ), 401 );
            }
        }

        return $this->process_webhook( $data, 'coinsnap' );
    }

    public function handle_btcpay_webhook( WP_REST_Request $request ) {
        $core = coinsnap_bitcoin_donation_get_core();
        $data = $request->get_json_params();

        $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        if ( empty( $settings['disable_webhook_verification'] ) ) {
            $webhook_option = get_option( $core->webhook_key() );
            $secret = '';
            if ( is_array( $webhook_option ) && ! empty( $webhook_option['btcpay']['secret'] ) ) {
                $secret = (string) $webhook_option['btcpay']['secret'];
            }

            $sig = $request->get_header( 'btcpay-signature' ) ?? '';
            $body = $request->get_body();
            $verified = false;

            if ( $secret && $body && $sig ) {
                $computed = 'sha256=' . hash_hmac( 'sha256', $body, $secret );
                $verified = hash_equals( $computed, $sig );
            }

            if ( ! $verified ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid signature.' ), 401 );
            }
        }

        return $this->process_webhook( $data, 'btcpay' );
    }

    /**
     * Handle legacy /webhook endpoint — tries both signature methods, also checks old secret.
     */
    public function handle_legacy_webhook( WP_REST_Request $request ) {
        $core = coinsnap_bitcoin_donation_get_core();
        $data = $request->get_json_params();

        $provider = 'coinsnap';
        if ( ! \CoinsnapCore\Rest\WebhookHelper::verify_coinsnap_signature( $core ) ) {
            if ( ! \CoinsnapCore\Rest\WebhookHelper::verify_btcpay_signature( $core ) ) {
                if ( ! $this->verify_legacy_signature() ) {
                    return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid signature.' ), 401 );
                }
            } else {
                $provider = 'btcpay';
            }
        }

        return $this->process_webhook( $data, $provider );
    }

    /**
     * Verify using the old coinsnap_webhook_secret option.
     */
    private function verify_legacy_signature() {
        $secret = get_option( 'coinsnap_webhook_secret', '' );
        if ( ! $secret ) {
            return false;
        }

        $payload = file_get_contents( 'php://input' );
        if ( ! $payload ) {
            return false;
        }

        $computed = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );

        $headers = array(
            isset( $_SERVER['HTTP_X_COINSNAP_SIG'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_COINSNAP_SIG'] ) ) : '',
            isset( $_SERVER['HTTP_BTCPAY_SIG'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_BTCPAY_SIG'] ) ) : '',
            isset( $_SERVER['HTTP_X_COINSNAP_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_COINSNAP_SIGNATURE'] ) ) : '',
            isset( $_SERVER['HTTP_BTCPAY_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_BTCPAY_SIGNATURE'] ) ) : '',
            isset( $_SERVER['HTTP_X_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_SIGNATURE'] ) ) : '',
        );

        foreach ( $headers as $sig ) {
            if ( $sig && hash_equals( $computed, $sig ) ) {
                return true;
            }
        }
        return false;
    }

    private function process_webhook( array $data, string $provider ) {
        $core   = coinsnap_bitcoin_donation_get_core();
        $parsed = \CoinsnapCore\Rest\WebhookHelper::parse_webhook( $provider, $data );

        if ( ! $parsed['paid'] ) {
            return new WP_REST_Response( array( 'success' => true ), 200 );
        }

        $invoice_id = $parsed['invoice_id'];
        $metadata   = isset( $data['metadata'] ) && is_array( $data['metadata'] ) ? $data['metadata'] : array();

        global $wpdb;
        $table_name = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
        $wpdb->update(
            $table_name,
            array( 'payment_status' => 'paid', 'updated_at' => current_time( 'mysql' ) ),
            array( 'payment_invoice_id' => $invoice_id )
        );

        $old_table = $wpdb->prefix . 'donation_payments';
        $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $old_table ) );

        if ( $table_exists ) {
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$old_table} WHERE payment_id = %s",
                $invoice_id
            ) );
            if ( ! $exists ) {
                $wpdb->insert( $old_table, array(
                    'payment_id' => $invoice_id,
                    'status'     => 'completed',
                ), array( '%s', '%s' ) );
            }
        }

        if ( isset( $metadata['publicDonor'] ) && $metadata['publicDonor'] == '1' ) {
            $name    = sanitize_text_field( $metadata['donorName'] ?? '' );
            $email   = sanitize_email( $metadata['donorEmail'] ?? '' );
            $address = sanitize_text_field( $metadata['donorAddress'] ?? '' );
            $message = sanitize_text_field( $metadata['donorMessage'] ?? '' );
            $opt_out = filter_var( $metadata['donorOptOut'] ?? false, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0';
            $custom  = sanitize_text_field( $metadata['donorCustom'] ?? '' );
            $type    = sanitize_text_field( $metadata['formType'] ?? '' );
            $amount  = sanitize_text_field( $metadata['amount'] ?? '' );

            $post_id = wp_insert_post( array(
                'post_title'   => $name,
                'post_status'  => 'publish',
                'post_type'    => 'bitcoin-pds',
                'post_content' => $message,
            ) );

            if ( $post_id ) {
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_donor_name', $name );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_amount', $amount );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_message', $message );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_form_type', $type );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_dont_show', $opt_out );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_email', $email );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_address', $address );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_payment_id', $invoice_id );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_custom_field', $custom );
            }
        }

        if ( isset( $metadata['type'] ) && $metadata['type'] === 'Bitcoin Shoutout' ) {
            $name        = sanitize_text_field( $metadata['donorName'] ?? '' );
            $message     = sanitize_text_field( $metadata['donorMessage'] ?? '' );
            $amount      = sanitize_text_field( $metadata['amount'] ?? '' );
            $sats_amount = sanitize_text_field( $metadata['satsAmount'] ?? '' );

            $post_id = wp_insert_post( array(
                'post_title'  => 'Shoutout from ' . $name,
                'post_status' => 'publish',
                'post_type'   => 'bitcoin-shoutouts',
            ) );

            if ( $post_id ) {
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_name', $name );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_amount', $amount );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_sats_amount', $sats_amount );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_invoice_id', $invoice_id );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_message', $message );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_provider', $provider );
            }
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}

new coinsnap_bitcoin_donation_Webhooks();
