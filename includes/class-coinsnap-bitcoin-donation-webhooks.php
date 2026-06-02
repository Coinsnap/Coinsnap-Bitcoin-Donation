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
        // No nonce check — this is a public endpoint (permission_callback => __return_true).
        // Nonce verification breaks when pages are served from cache with a stale wp_rest nonce.
        // Security is enforced by the payment provider (Coinsnap/BTCPay).

        $core     = coinsnap_bitcoin_donation_get_core();
        $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );

        // Check provider credentials before processing
        $provider_name = $settings['payment_provider'] ?? 'coinsnap';
        if ( 'btcpay' === $provider_name ) {
            if ( empty( $settings['btcpay_api_key'] ) || empty( $settings['btcpay_store_id'] ) || empty( $settings['btcpay_host'] ) ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Payment gateway is not configured. Please contact the site administrator.', 'coinsnap-bitcoin-donation' ) ), 503 );
            }
        } else {
            if ( empty( $settings['coinsnap_api_key'] ) || empty( $settings['coinsnap_store_id'] ) ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Payment gateway is not configured. Please contact the site administrator.', 'coinsnap-bitcoin-donation' ) ), 503 );
            }
        }

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

        $provider_name = $settings['payment_provider'] ?? 'coinsnap';
        $mode = ( 'btcpay' === $provider_name ) ? 'lightning' : 'coinsnap';
        $exchange_rates = new \CoinsnapCore\Util\ExchangeRates();
        $check = $exchange_rates->check_payment_data( $amount, $currency, $mode );
        if ( isset( $check['result'] ) && $check['result'] === false ) {
            // Only block on currency/amount errors — skip if exchange rates couldn't be loaded
            if ( $check['error'] === 'currencyError' ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => sprintf( __( 'Currency %s is not supported.', 'coinsnap-bitcoin-donation' ), $currency ) ), 400 );
            } elseif ( $check['error'] === 'amountError' ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => sprintf( __( 'Amount cannot be less than %s %s.', 'coinsnap-bitcoin-donation' ), $check['min_value'] ?? '0', $currency ) ), 400 );
            }
            // For ratesLoadingError/ratesListError — skip validation, let the provider handle it
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

        // Surface the form's data on the provider invoice. Custom donor fields already
        // travel inside 'metadata'; these top-level keys let the provider also populate
        // the standard buyer name / email / description fields (shown in the dashboard).
        $donor_name   = $sanitized_metadata['donorname'] ?? '';
        $donor_email  = isset( $sanitized_metadata['donoremail'] ) ? sanitize_email( $sanitized_metadata['donoremail'] ) : '';
        $form_post_id = absint( $sanitized_metadata['donationformid'] ?? 0 );

        $invoice_data = array(
            'name'        => $donor_name,
            'email'       => $donor_email,
            'buyer_email' => $donor_email, // back-compat alias
            'description' => $message,
            'redirect'    => $redirect,
            'metadata'    => $sanitized_metadata,
        );

        try {
            $provider = \CoinsnapCore\Util\ProviderFactory::create( $core );
            $result   = $provider->create_invoice( $form_post_id, $amount_cents, $currency, $invoice_data );

            if ( isset( $result['error'] ) || empty( $result['invoice_id'] ) ) {
                $error_msg = $result['error'] ?? $result['message'] ?? __( 'Invoice creation failed. Please try again.', 'coinsnap-bitcoin-donation' );
                return new WP_REST_Response( array( 'success' => false, 'message' => $error_msg ), 500 );
            }

            global $wpdb;
            $table_name = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
            $wpdb->insert( $table_name, array(
                'source_id'          => $form_post_id,
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

        // Webhook fallback: the local row only flips to 'paid' when an inbound webhook
        // arrives, which never happens on hosts the provider can't reach (local dev) and
        // can be lost in production. Actively ask the provider, but throttle to ~1 remote
        // call per 10s per invoice so the 1s frontend poll loop can't hammer the API.
        $throttle_key = 'cbd_status_poll_' . md5( $invoice_id );
        if ( false === get_transient( $throttle_key ) ) {
            set_transient( $throttle_key, 1, 10 );
            if ( 'paid' === self::reconcile_invoice( $invoice_id ) ) {
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
        $parsed = \CoinsnapCore\Rest\WebhookHelper::parse_webhook( $provider, $data );

        if ( ! $parsed['paid'] ) {
            return new WP_REST_Response( array( 'success' => true ), 200 );
        }

        $metadata = isset( $data['metadata'] ) && is_array( $data['metadata'] ) ? $data['metadata'] : array();
        self::apply_paid_invoice( $parsed['invoice_id'], $metadata, $provider );

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Ask the payment provider for the live status of an invoice and sync it locally.
     *
     * This is the fallback for the webhook-only design: the local row is only flipped to
     * 'paid' when an inbound webhook arrives, but the provider cannot reach local-dev hosts
     * (e.g. *.ddev.site) and webhooks can be lost/misconfigured in production. Both the
     * status endpoint (frontend poll) and the admin "Check status" action call this.
     *
     * @param string $invoice_id    Provider invoice id.
     * @param string $provider_name Optional provider override; defaults to the stored row's provider.
     * @return string Resulting local payment_status ('paid', 'failed', or the unchanged status).
     */
    public static function reconcile_invoice( string $invoice_id, string $provider_name = '' ) {
        if ( '' === $invoice_id ) {
            return 'unpaid';
        }

        $core = coinsnap_bitcoin_donation_get_core();

        global $wpdb;
        $table_name = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT payment_provider, payment_status FROM {$table_name} WHERE payment_invoice_id = %s",
            $invoice_id
        ) );

        if ( $row && 'paid' === $row->payment_status ) {
            return 'paid';
        }

        if ( '' === $provider_name ) {
            $provider_name = ( $row && ! empty( $row->payment_provider ) ) ? (string) $row->payment_provider : '';
        }

        $provider_obj = \CoinsnapCore\Util\ProviderFactory::create( $core, $provider_name );
        $result       = $provider_obj->check_invoice_status( $invoice_id );

        if ( ! empty( $result['paid'] ) ) {
            // check_invoice_status() returns the full invoice body under 'metadata';
            // the custom metadata we set on creation lives at metadata.metadata.
            $invoice_meta = array();
            if ( isset( $result['metadata']['metadata'] ) && is_array( $result['metadata']['metadata'] ) ) {
                $invoice_meta = $result['metadata']['metadata'];
            }
            self::apply_paid_invoice( $invoice_id, $invoice_meta, $provider_name ?: 'coinsnap' );
            return 'paid';
        }

        // Reflect terminal non-paid states so the admin list isn't stuck showing "unpaid".
        $status = isset( $result['status'] ) ? (string) $result['status'] : '';
        if ( in_array( $status, array( 'Expired', 'Invalid' ), true ) && $row && 'failed' !== $row->payment_status ) {
            $wpdb->update(
                $table_name,
                array( 'payment_status' => 'failed', 'updated_at' => current_time( 'mysql' ) ),
                array( 'payment_invoice_id' => $invoice_id )
            );
            return 'failed';
        }

        return $row ? (string) $row->payment_status : 'unpaid';
    }

    /**
     * Mark an invoice paid in the local store and create the donor/shoutout posts.
     *
     * Idempotent: side effects run only on the unpaid->paid transition (guarded by the
     * current row status), and donor/shoutout posts are de-duplicated by invoice id so a
     * webhook and a status-poll racing on the same invoice cannot create duplicates.
     *
     * @param string $invoice_id Provider invoice id.
     * @param array  $metadata   Invoice custom metadata (keys may be camelCase or lowercased).
     * @param string $provider   'coinsnap' or 'btcpay'.
     * @return bool True if this call performed the unpaid->paid transition.
     */
    public static function apply_paid_invoice( string $invoice_id, array $metadata, string $provider ) {
        if ( '' === $invoice_id ) {
            return false;
        }

        $core = coinsnap_bitcoin_donation_get_core();

        global $wpdb;
        $table_name = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );

        // Idempotency guard: if the row is already paid, its side effects already ran.
        $current = $wpdb->get_var( $wpdb->prepare(
            "SELECT payment_status FROM {$table_name} WHERE payment_invoice_id = %s",
            $invoice_id
        ) );
        if ( 'paid' === $current ) {
            return false;
        }

        $wpdb->update(
            $table_name,
            array( 'payment_status' => 'paid', 'updated_at' => current_time( 'mysql' ) ),
            array( 'payment_invoice_id' => $invoice_id )
        );

        $old_table    = $wpdb->prefix . 'donation_payments';
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

        // Normalize metadata keys to lower case. The frontend sends camelCase, but
        // create_payment() runs every key through sanitize_key() (which lowercases)
        // before they are stored on the provider invoice, so the values read back are
        // lowercased. Lowercasing here makes lookups match regardless of source
        // (webhook payload vs. status-poll response) and fixes blank donor/shoutout
        // records that the previous camelCase lookups produced.
        $m = array_change_key_case( $metadata, CASE_LOWER );

        if ( '1' === (string) ( $m['publicdonor'] ?? '' )
            && ! self::post_exists_for_invoice( 'bitcoin-pds', '_coinsnap_bitcoin_donation_payment_id', $invoice_id ) ) {

            $name    = sanitize_text_field( $m['donorname'] ?? '' );
            $email   = sanitize_email( $m['donoremail'] ?? '' );
            $address = sanitize_text_field( $m['donoraddress'] ?? '' );
            $message = sanitize_text_field( $m['donormessage'] ?? '' );
            $opt_out = filter_var( $m['donoroptout'] ?? false, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0';
            $custom  = sanitize_text_field( $m['donorcustom'] ?? '' );
            $type    = sanitize_text_field( $m['formtype'] ?? '' );
            $amount  = sanitize_text_field( $m['amount'] ?? '' );

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
                $custom_checkbox = sanitize_text_field( $m['donorcustomcheckbox'] ?? '0' );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_custom_checkbox', $custom_checkbox );
                if ( ! empty( $m['donationformid'] ) ) {
                    update_post_meta( $post_id, '_coinsnap_donation_form_id', absint( $m['donationformid'] ) );
                }
            }
        }

        if ( 'Bitcoin Shoutout' === (string) ( $m['type'] ?? '' )
            && ! self::post_exists_for_invoice( 'bitcoin-shoutouts', '_coinsnap_bitcoin_donation_shoutouts_invoice_id', $invoice_id ) ) {

            $name        = sanitize_text_field( $m['donorname'] ?? '' );
            $message     = sanitize_text_field( $m['donormessage'] ?? '' );
            $amount      = sanitize_text_field( $m['amount'] ?? '' );
            $sats_amount = sanitize_text_field( $m['satsamount'] ?? '' );

            $post_id = wp_insert_post( array(
                'post_title'  => 'Shoutout from ' . ( '' !== $name ? $name : 'Anonymous' ),
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
                if ( ! empty( $m['donationformid'] ) ) {
                    update_post_meta( $post_id, '_coinsnap_donation_form_id', absint( $m['donationformid'] ) );
                }
            }
        }

        return true;
    }

    /**
     * Whether a post of the given type already records the given invoice id.
     *
     * @param string $post_type  CPT slug.
     * @param string $meta_key   Meta key holding the invoice id.
     * @param string $invoice_id Provider invoice id.
     * @return bool
     */
    private static function post_exists_for_invoice( string $post_type, string $meta_key, string $invoice_id ) {
        $found = get_posts( array(
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'meta_key'       => $meta_key,
            'meta_value'     => $invoice_id,
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ) );
        return ! empty( $found );
    }
}

new coinsnap_bitcoin_donation_Webhooks();
