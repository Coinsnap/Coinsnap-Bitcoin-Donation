<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class coinsnap_bitcoin_donation_Webhooks {

    /** Donor details saved when the invoice is created; read back when it is paid. */
    const DONOR_OPTION_PREFIX = 'coinsnap_bitcoin_donation_donor_';

    /** Form type (form meta) => invoice type. */
    const FORM_TYPES = array(
        'simple_donation' => 'Bitcoin Donation',
        'multi_amount'    => 'Multi Amount Donation',
        'shoutout'        => 'Bitcoin Shoutout',
    );

    /** Donor fields accepted from the donation form (keys as sanitize_key() leaves them). */
    const DONOR_FIELDS = array( 'donorname', 'donoremail', 'donoraddress', 'donormessage', 'donorcustom', 'donorcustomcheckbox', 'donoroptout', 'amountfiat' );

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        $namespace = 'coinsnap-bitcoin-donation/v1';

        register_rest_route( $namespace, '/payment/create', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_payment' ),
            // Public on purpose: visitors start donations. The server creates the invoice and records its amount.
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $namespace, '/status/(?P<invoice_id>[A-Za-z0-9_-]{1,64})', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'check_payment_status' ),
            // Public on purpose: returns only paid yes/no for an invoice the visitor already knows.
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $namespace, '/webhook/coinsnap', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_coinsnap_webhook' ),
            'permission_callback' => function ( WP_REST_Request $request ) {
                return self::webhook_permission( 'coinsnap', $request );
            },
        ) );

        register_rest_route( $namespace, '/webhook/btcpay', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_btcpay_webhook' ),
            'permission_callback' => function ( WP_REST_Request $request ) {
                return self::webhook_permission( 'btcpay', $request );
            },
        ) );

        // Legacy webhook endpoint — old installations may still have this URL registered.
        // It accepts only this plugin's own secrets, never the shared coinsnap_webhook_secret.
        register_rest_route( $namespace, '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_legacy_webhook' ),
            'permission_callback' => function ( WP_REST_Request $request ) {
                return self::webhook_permission( self::legacy_provider( $request ), $request );
            },
        ) );
    }

    /**
     * Signature check for the webhook routes: timestamped X-Coinsnap-Signature or BTCPay-Sig,
     * with this plugin's own secret. Failures are logged by the core with a fixed reason.
     */
    public static function webhook_permission( string $provider, WP_REST_Request $request ): bool {
        if ( ! coinsnap_bitcoin_donation_core_ok() ) {
            return false; // An older coinsnap-core was loaded by another plugin: refuse rather than trust the body.
        }
        return \CoinsnapCore\Rest\WebhookHelper::check_request( coinsnap_bitcoin_donation_get_core(), $provider, $request );
    }

    private static function legacy_provider( WP_REST_Request $request ): string {
        return '' !== (string) $request->get_header( 'BTCPay-Sig' ) ? 'btcpay' : 'coinsnap';
    }

    public function create_payment( WP_REST_Request $request ) {
        // No nonce check — this is a public endpoint (permission_callback => __return_true).
        // Nonce verification breaks when pages are served from cache with a stale wp_rest nonce.
        // The amount and currency sent to the provider are recorded here and checked when the invoice is paid.

        $core     = coinsnap_bitcoin_donation_get_core();
        $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        $generic  = __( 'Invoice creation failed. Please try again.', 'coinsnap-bitcoin-donation' );

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

        $params = $request->get_json_params();
        $params = is_array( $params ) ? $params : array();

        $amount   = ( isset( $params['amount'] ) && is_numeric( $params['amount'] ) ) ? (float) $params['amount'] : 0;
        $currency = isset( $params['currency'] ) && is_string( $params['currency'] ) ? strtoupper( sanitize_text_field( $params['currency'] ) ) : 'SATS';
        $message  = isset( $params['message'] ) && is_string( $params['message'] ) ? self::limit( sanitize_textarea_field( $params['message'] ), 1000 ) : '';
        $redirect = isset( $params['redirectUrl'] ) && is_string( $params['redirectUrl'] ) ? esc_url_raw( $params['redirectUrl'] ) : home_url();
        $metadata = isset( $params['metadata'] ) && is_array( $params['metadata'] ) ? array_change_key_case( $params['metadata'], CASE_LOWER ) : array();

        $currencies = defined( 'COINSNAP_CURRENCIES' ) ? COINSNAP_CURRENCIES : array( 'EUR', 'USD', 'SATS', 'BTC', 'CAD', 'JPY', 'GBP', 'CHF', 'RUB' );
        if ( ! in_array( $currency, $currencies, true ) ) {
            // translators: %s: currency code (e.g. EUR)
            return new WP_REST_Response( array( 'success' => false, 'message' => sprintf( __( 'Currency %s is not supported.', 'coinsnap-bitcoin-donation' ), $currency ) ), 400 );
        }
        if ( $amount <= 0 || ! is_finite( $amount ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Invalid amount.', 'coinsnap-bitcoin-donation' ) ), 400 );
        }

        // Which form this is, and so what the payment is for, comes from the form's own settings.
        $form_post_id = absint( is_scalar( $metadata['donationformid'] ?? null ) ? $metadata['donationformid'] : 0 );
        if ( $form_post_id > 0 ) {
            $form = get_post( $form_post_id );
            if ( ! $form || 'donation-form' !== $form->post_type || 'publish' !== $form->post_status ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => $generic ), 400 );
            }
            $form_type_key = (string) get_post_meta( $form_post_id, '_coinsnap_donation_form_form_type', true );
            $form_type     = self::FORM_TYPES[ $form_type_key ] ?? self::FORM_TYPES['simple_donation'];
            $public_donor  = '1' === (string) get_post_meta( $form_post_id, '_coinsnap_donation_form_public_donors', true ) ? '1' : '0';
        } else {
            // Legacy shortcode without a form post: only the known types are accepted.
            $requested    = isset( $params['formType'] ) && is_string( $params['formType'] ) ? $params['formType'] : '';
            $form_type    = in_array( $requested, self::FORM_TYPES, true ) ? $requested : self::FORM_TYPES['simple_donation'];
            $public_donor = '1' === (string) ( is_scalar( $metadata['publicdonor'] ?? null ) ? $metadata['publicdonor'] : '' ) ? '1' : '0';
        }

        $mode = ( 'btcpay' === $provider_name ) ? 'lightning' : 'coinsnap';
        $exchange_rates = new \CoinsnapCore\Util\ExchangeRates();
        $check = $exchange_rates->check_payment_data( $amount, $currency, $mode );
        if ( isset( $check['result'] ) && $check['result'] === false ) {
            // Only block on currency/amount errors — skip if exchange rates couldn't be loaded
            if ( $check['error'] === 'currencyError' ) {
                // translators: %s: currency code (e.g. EUR)
                return new WP_REST_Response( array( 'success' => false, 'message' => sprintf( __( 'Currency %s is not supported.', 'coinsnap-bitcoin-donation' ), $currency ) ), 400 );
            } elseif ( $check['error'] === 'amountError' ) {
                // translators: %1$s: minimum amount, %2$s: currency code (e.g. EUR)
                return new WP_REST_Response( array( 'success' => false, 'message' => sprintf( __( 'Amount cannot be less than %1$s %2$s.', 'coinsnap-bitcoin-donation' ), $check['min_value'] ?? '0', $currency ) ), 400 );
            }
            // For ratesLoadingError/ratesListError — skip validation, let the provider handle it
        }

        // The core takes whole units for SATS and cents for fiat. BTC would be truncated to whole
        // bitcoins, so it is sent as the same amount in sats.
        if ( 'BTC' === $currency ) {
            $currency = 'SATS';
            $amount   = round( $amount * 100000000 );
        }
        if ( 'SATS' === $currency ) {
            $amount_minor = (int) round( $amount );
            $amount_units = (float) $amount_minor;
        } else {
            $amount_minor = (int) round( $amount * 100 );
            $amount_units = $amount_minor / 100;
        }
        if ( $amount_minor <= 0 ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Invalid amount.', 'coinsnap-bitcoin-donation' ) ), 400 );
        }
        $amount_text = self::format_amount( $amount_units, $currency );

        $donor = array();
        foreach ( self::DONOR_FIELDS as $field ) {
            $value           = $metadata[ $field ] ?? '';
            $donor[ $field ] = is_scalar( $value ) ? self::limit( sanitize_text_field( (string) $value ), 500 ) : '';
        }
        $donor['donoremail'] = sanitize_email( $donor['donoremail'] );

        // Metadata on the provider invoice (shown in its dashboard): the donor's fields plus what the server decided.
        $sanitized_metadata = $donor;
        $sanitized_metadata['modal']          = '1';
        $sanitized_metadata['formType']       = $form_type;
        $sanitized_metadata['type']           = $form_type;
        $sanitized_metadata['amount']         = $amount_text;
        $sanitized_metadata['satsamount']     = 'SATS' === $currency ? $amount_text : '';
        $sanitized_metadata['publicdonor']    = $public_donor;
        $sanitized_metadata['donationformid'] = (string) $form_post_id;

        // Generate the order reference up front so the same unique id is sent to the
        // provider (as orderId/orderNumber, used by the wallet for reconciliation)
        // and stored as the local transaction_id — keeping the two records matched 1:1.
        $transaction_id = 'donation_' . time() . '_' . wp_generate_password( 8, false );

        // Standard invoice fields (BTCPay/Coinsnap names) next to the plugin's own keys above, which
        // stay as they are. The core leaves out empty values and adds orderId, buyerName, customerName,
        // buyerEmail and itemDesc from $invoice_data below.
        $sanitized_metadata += self::standard_metadata( $donor, $message, $form_post_id, $form_type, $amount_units, $currency, $redirect );

        $invoice_data = array(
            'name'        => $donor['donorname'],
            'email'       => $donor['donoremail'],
            'buyer_email' => $donor['donoremail'], // back-compat alias
            'description' => $message,
            'redirect'    => $redirect,
            'order_id'    => $transaction_id,
            'metadata'    => $sanitized_metadata,
        );

        try {
            $provider = \CoinsnapCore\Util\ProviderFactory::create( $core );
            $result   = $provider->create_invoice( $form_post_id, $amount_minor, $currency, $invoice_data );

            if ( isset( $result['error'] ) || empty( $result['invoice_id'] ) || empty( $result['payment_url'] ) ) {
                return new WP_REST_Response( array( 'success' => false, 'message' => $generic ), 500 );
            }

            global $wpdb;
            $table_name = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
            $wpdb->insert( $table_name, array(
                'source_id'          => $form_post_id,
                'transaction_id'     => $transaction_id,
                'customer_name'      => $donor['donorname'],
                'customer_email'     => $donor['donoremail'],
                'amount'             => $amount_units,
                'currency'           => $currency,
                'description'        => $form_type,
                'payment_provider'   => $provider_name,
                'invoice_number'     => $result['invoice_id'],
                'payment_invoice_id' => $result['invoice_id'],
                'payment_status'     => 'unpaid',
                'payment_url'        => $result['payment_url'],
                'ip'                 => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
                'created_at'         => current_time( 'mysql' ),
            ) );

            // What the donor entered, kept on the server: the paid invoice's posts are made from this, never from a webhook body.
            update_option( self::donor_option( (string) $result['invoice_id'] ), array(
                'type'         => $form_type,
                'public_donor' => $public_donor,
                'form_id'      => $form_post_id,
                'amount'       => $amount_text,
                'sats_amount'  => 'SATS' === $currency ? $amount_text : '',
                'name'         => $donor['donorname'],
                'email'        => $donor['donoremail'],
                'address'      => $donor['donoraddress'],
                'message'      => $donor['donormessage'],
                'custom'       => $donor['donorcustom'],
                'checkbox'     => '1' === $donor['donorcustomcheckbox'] ? '1' : '0',
                'opt_out'      => filter_var( $donor['donoroptout'], FILTER_VALIDATE_BOOLEAN ) ? '1' : '0',
            ), false );

            return new WP_REST_Response( array(
                'success'      => true,
                'invoice_id'   => $result['invoice_id'],
                'payment_url'  => $result['payment_url'],
                'redirect_url' => $redirect,
            ), 200 );

        } catch ( \Throwable $e ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $generic ), 500 );
        }
    }

    public function check_payment_status( WP_REST_Request $request ) {
        $invoice_id = (string) $request->get_param( 'invoice_id' );
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
        // Only invoices this plugin created and that are still open are looked up.
        if ( in_array( $status, array( 'unpaid', 'failed' ), true ) ) {
            $throttle_key = 'cbd_status_poll_' . md5( $invoice_id );
            if ( false === get_transient( $throttle_key ) ) {
                set_transient( $throttle_key, 1, 10 );
                if ( 'paid' === self::reconcile_invoice( $invoice_id ) ) {
                    return new WP_REST_Response( array( 'success' => true, 'data' => array( 'paid' => true ) ), 200 );
                }
            }
        }

        return new WP_REST_Response( array( 'success' => true, 'data' => array( 'paid' => false ) ), 200 );
    }

    public function handle_coinsnap_webhook( WP_REST_Request $request ) {
        return $this->process_webhook( $request, 'coinsnap' );
    }

    public function handle_btcpay_webhook( WP_REST_Request $request ) {
        return $this->process_webhook( $request, 'btcpay' );
    }

    /**
     * Legacy /webhook endpoint: the provider is chosen by the signature header; the
     * permission callback already checked it with this plugin's own secret.
     */
    public function handle_legacy_webhook( WP_REST_Request $request ) {
        return $this->process_webhook( $request, self::legacy_provider( $request ) );
    }

    /**
     * Only the invoice id is taken from the webhook. The core asks the provider's API whether that
     * invoice is Settled with the amount and currency recorded at creation, then the donation is
     * applied once.
     */
    private function process_webhook( WP_REST_Request $request, string $provider ) {
        $data   = json_decode( (string) $request->get_body(), true );
        $data   = is_array( $data ) ? $data : array();
        $parsed = \CoinsnapCore\Rest\WebhookHelper::parse_webhook( $provider, $data, coinsnap_bitcoin_donation_get_core() );

        if ( ! empty( $parsed['retry'] ) ) {
            // The API could not be asked: the provider sends the webhook again.
            return new WP_REST_Response( array( 'success' => false ), 503 );
        }
        if ( ! empty( $parsed['paid'] ) ) {
            self::apply_paid_invoice( (string) $parsed['invoice_id'], $provider );
        }
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Ask the payment provider for the live status of an invoice and sync it locally.
     *
     * This is the fallback for the webhook-only design: the local row is only flipped to
     * 'paid' when an inbound webhook arrives, but the provider cannot reach local-dev hosts
     * (e.g. *.ddev.site) and webhooks can be lost/misconfigured in production. Both the
     * status endpoint (frontend poll) and the admin "Check status" action call this.
     * Paid means what the core decides from the API: Settled, with the recorded amount and currency.
     *
     * @param string $invoice_id    Provider invoice id.
     * @param string $provider_name Optional provider override; defaults to the stored row's provider.
     * @return string Resulting local payment_status ('paid', 'failed', 'error', or the unchanged status).
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

        if ( ! $row ) {
            return 'unpaid'; // Not an invoice this plugin created.
        }
        if ( 'paid' === $row->payment_status ) {
            return 'paid';
        }
        if ( ! coinsnap_bitcoin_donation_core_ok() ) {
            return 'error';
        }

        if ( '' === $provider_name ) {
            $provider_name = ! empty( $row->payment_provider ) ? (string) $row->payment_provider : 'coinsnap';
        }

        $provider_obj = \CoinsnapCore\Util\ProviderFactory::create( $core, $provider_name );
        $result       = $provider_obj->check_invoice_status( $invoice_id );

        if ( ! empty( $result['paid'] ) ) {
            self::apply_paid_invoice( $invoice_id, $provider_name );
            return 'paid';
        }
        if ( ! empty( $result['retry'] ) ) {
            return 'error';
        }

        // Reflect terminal non-paid states so the admin list isn't stuck showing "unpaid".
        // Only an unpaid row changes: Expired or Invalid never move a paid or refunded one.
        $status = isset( $result['status'] ) ? (string) $result['status'] : '';
        if ( in_array( $status, array( 'Expired', 'Invalid' ), true ) ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$table_name} SET payment_status = 'failed', updated_at = %s WHERE payment_invoice_id = %s AND payment_status = 'unpaid'",
                current_time( 'mysql' ),
                $invoice_id
            ) );
            return 'unpaid' === $row->payment_status ? 'failed' : (string) $row->payment_status;
        }

        return (string) $row->payment_status;
    }

    /**
     * Mark an invoice paid and create the donor/shoutout posts, once.
     *
     * Call only after the provider's API confirmed the invoice (WebhookHelper::parse_webhook() or
     * check_invoice_status()). The row moves from unpaid (or failed, for a late payment) to paid in
     * one SQL statement, so a webhook and a status poll racing on the same invoice can't both get
     * through. The posts are made from what the server saved when it created the invoice.
     *
     * @param string $invoice_id Provider invoice id.
     * @param string $provider   'coinsnap' or 'btcpay'.
     * @return bool True if this call performed the unpaid->paid transition.
     */
    public static function apply_paid_invoice( string $invoice_id, string $provider ) {
        if ( '' === $invoice_id || ! coinsnap_bitcoin_donation_core_ok() ) {
            return false;
        }

        $core = coinsnap_bitcoin_donation_get_core();
        if ( ! \CoinsnapCore\Database\PaymentTable::mark_paid_once( $core, $invoice_id ) ) {
            return false; // Already paid (or refunded, or not ours): its side effects already ran.
        }

        global $wpdb;
        $table_name = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
        $row        = $wpdb->get_row( $wpdb->prepare(
            "SELECT source_id, customer_name, customer_email, amount, currency FROM {$table_name} WHERE payment_invoice_id = %s",
            $invoice_id
        ) );

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

        $donor = get_option( self::donor_option( $invoice_id ) );
        if ( ! is_array( $donor ) ) {
            $donor = self::legacy_donor_record( $invoice_id, $provider, $row );
        }
        // The amount is the one this plugin invoiced (and the core checked), never one sent by the browser.
        if ( $row ) {
            $donor['amount']      = self::format_amount( (float) $row->amount, (string) $row->currency );
            $donor['sats_amount'] = 'SATS' === strtoupper( (string) $row->currency ) ? $donor['amount'] : '';
        }

        $type    = (string) ( $donor['type'] ?? '' );
        $form_id = absint( $donor['form_id'] ?? 0 );
        $name    = sanitize_text_field( (string) ( $donor['name'] ?? '' ) );
        $message = sanitize_text_field( (string) ( $donor['message'] ?? '' ) );
        $amount  = sanitize_text_field( (string) ( $donor['amount'] ?? '' ) );

        if ( '1' === (string) ( $donor['public_donor'] ?? '' )
            && ! self::post_exists_for_invoice( 'bitcoin-pds', '_coinsnap_bitcoin_donation_payment_id', $invoice_id ) ) {

            $post_id = wp_insert_post( array(
                'post_title'   => $name,
                'post_status'  => 'publish',
                'post_type'    => 'bitcoin-pds',
                'post_content' => $message,
            ) );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_donor_name', $name );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_amount', $amount );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_message', $message );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_form_type', sanitize_text_field( $type ) );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_dont_show', '1' === (string) ( $donor['opt_out'] ?? '' ) ? '1' : '0' );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_email', sanitize_email( (string) ( $donor['email'] ?? '' ) ) );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_address', sanitize_text_field( (string) ( $donor['address'] ?? '' ) ) );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_payment_id', $invoice_id );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_custom_field', sanitize_text_field( (string) ( $donor['custom'] ?? '' ) ) );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_custom_checkbox', '1' === (string) ( $donor['checkbox'] ?? '' ) ? '1' : '0' );
                if ( $form_id ) {
                    update_post_meta( $post_id, '_coinsnap_donation_form_id', $form_id );
                }
            }
        }

        if ( self::FORM_TYPES['shoutout'] === $type
            && ! self::post_exists_for_invoice( 'bitcoin-shoutouts', '_coinsnap_bitcoin_donation_shoutouts_invoice_id', $invoice_id ) ) {

            $post_id = wp_insert_post( array(
                'post_title'  => 'Shoutout from ' . ( '' !== $name ? $name : 'Anonymous' ),
                'post_status' => 'publish',
                'post_type'   => 'bitcoin-shoutouts',
            ) );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_name', $name );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_amount', $amount );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_sats_amount', sanitize_text_field( (string) ( $donor['sats_amount'] ?? '' ) ) );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_invoice_id', $invoice_id );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_message', $message );
                update_post_meta( $post_id, '_coinsnap_bitcoin_donation_shoutouts_provider', $provider );
                if ( $form_id ) {
                    update_post_meta( $post_id, '_coinsnap_donation_form_id', $form_id );
                }
            }
        }

        delete_option( self::donor_option( $invoice_id ) );
        return true;
    }

    /**
     * Donor details for an invoice created before this version, which saved none on the server:
     * read from the invoice as the provider's API returns it (the metadata this plugin sent), never
     * from the webhook body. The core accepts such invoices only for a day after the upgrade.
     */
    private static function legacy_donor_record( string $invoice_id, string $provider, $row ): array {
        $core   = coinsnap_bitcoin_donation_get_core();
        $result = \CoinsnapCore\Util\ProviderFactory::create( $core, $provider )->check_invoice_status( $invoice_id );
        $meta   = ( isset( $result['metadata']['metadata'] ) && is_array( $result['metadata']['metadata'] ) ) ? $result['metadata']['metadata'] : array();
        $m      = array_change_key_case( $meta, CASE_LOWER );
        $get    = function ( $key ) use ( $m ) {
            return is_scalar( $m[ $key ] ?? null ) ? (string) $m[ $key ] : '';
        };
        $form_id = $row ? absint( $row->source_id ) : 0;
        $type    = $get( 'type' );
        if ( $form_id ) {
            $type = self::FORM_TYPES[ (string) get_post_meta( $form_id, '_coinsnap_donation_form_form_type', true ) ] ?? $type;
        }
        return array(
            'type'         => in_array( $type, self::FORM_TYPES, true ) ? $type : self::FORM_TYPES['simple_donation'],
            'public_donor' => '1' === $get( 'publicdonor' ) ? '1' : '0',
            'form_id'      => $form_id,
            'name'         => $row ? (string) $row->customer_name : $get( 'donorname' ),
            'email'        => $row ? (string) $row->customer_email : $get( 'donoremail' ),
            'address'      => $get( 'donoraddress' ),
            'message'      => $get( 'donormessage' ),
            'custom'       => $get( 'donorcustom' ),
            'checkbox'     => '1' === $get( 'donorcustomcheckbox' ) ? '1' : '0',
            'opt_out'      => filter_var( $get( 'donoroptout' ), FILTER_VALIDATE_BOOLEAN ) ? '1' : '0',
        );
    }

    /**
     * Standard invoice metadata for a donation: what the form is, what is paid, and the donor's
     * fields under the BTCPay/Coinsnap names. Plain values; the core drops empty ones.
     */
    private static function standard_metadata( array $donor, string $message, int $form_post_id, string $form_type, float $amount_units, string $currency, string $redirect ): array {
        $form_title  = $form_post_id > 0 ? trim( (string) get_post_field( 'post_title', $form_post_id, 'raw' ) ) : '';
        $item_name   = '' !== $form_title ? $form_title : $form_type;
        $form_fields = array();
        if ( $form_post_id > 0 && '' !== $donor['donorcustom'] ) {
            $label = (string) get_post_meta( $form_post_id, '_coinsnap_donation_form_custom_field_name', true );
            $form_fields[ '' !== $label ? $label : 'custom' ] = $donor['donorcustom'];
        }
        if ( $form_post_id > 0 && '1' === $donor['donorcustomcheckbox'] ) {
            $label = (string) get_post_meta( $form_post_id, '_coinsnap_donation_form_custom_checkbox_label', true );
            $form_fields[ '' !== $label ? $label : 'checkbox' ] = '1';
        }
        $page_url = wp_validate_redirect( $redirect, '' );

        return array(
            'buyerAddress1' => $donor['donoraddress'],
            'buyerMessage'  => '' !== $donor['donormessage'] ? $donor['donormessage'] : $message,
            'itemCode'      => $form_post_id > 0 ? 'donation-form-' . $form_post_id : '',
            'lineItems'     => array(
                array(
                    'name'     => $item_name,
                    'sku'      => $form_post_id > 0 ? 'donation-form-' . $form_post_id : '',
                    'quantity' => 1,
                    'price'    => $amount_units,
                    'total'    => $amount_units,
                    'currency' => $currency,
                ),
            ),
            'formId'        => $form_post_id > 0 ? (string) $form_post_id : '',
            'formTitle'     => $form_title,
            'formFields'    => $form_fields,
            'pageUrl'       => is_string( $page_url ) ? $page_url : '',
            'platform'      => 'wordpress',
            'plugin'        => 'coinsnap-bitcoin-donation',
            'pluginVersion' => defined( 'COINSNAP_BITCOIN_DONATION_VERSION' ) ? COINSNAP_BITCOIN_DONATION_VERSION : '',
            'language'      => (string) get_locale(),
        );
    }

    private static function donor_option( string $invoice_id ): string {
        return self::DONOR_OPTION_PREFIX . md5( $invoice_id );
    }

    private static function format_amount( float $amount, string $currency ): string {
        if ( 'SATS' === strtoupper( $currency ) ) {
            return (string) (int) round( $amount );
        }
        return rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' );
    }

    private static function limit( string $value, int $length ): string {
        return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
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
