<?php
class coinsnap_bitcoin_donation_Webhooks
{

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
        add_action('rest_api_init', [$this, 'register_check_payment_endpoint']);
        add_action('rest_api_init', [$this, 'register_get_wh_secret_endpoint']);
    }

    public function register_check_payment_endpoint()
    {
        register_rest_route('coinsnap-bitcoin-donation/v1', '/check-payment-status/(?P<payment_id>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_check_payment_status'],
            'permission_callback' => '__return_true', // TODO: Add proper permissions later
            'args' => [
                'payment_id' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return !empty($param);
                    }
                ]
            ]
        ]);
    }
    public function register_get_wh_secret_endpoint()
    {
        register_rest_route('coinsnap-bitcoin-donation/v1', '/get-wh-secret', [
            'methods' => 'GET',
            'callback' => [$this, 'get_wh_secret'],
            'permission_callback' => '__return_true', // TODO: Add proper permissions later
        ]);
    }

    function get_wh_secret()
    {
        return $this->get_webhook_secret();
    }

    function get_check_payment_status($request)
    {
        $payment_id = $request['payment_id'];
        $start_time = time();
        $timeout = 5;

        while (time() - $start_time < $timeout) {
            global $wpdb;
            $status = $wpdb->get_var($wpdb->prepare(
                "SELECT status FROM {$wpdb->prefix}donation_payments WHERE payment_id = %s",
                $payment_id
            ));
            if ($status === 'completed') {
                return ['status' => 'completed'];
            }
            sleep(1);
        }
        // Timeout
        return ['status' => 'pending'];
    }


    private function get_webhook_secret()
    {
        $option_name = 'coinsnap_webhook_secret';
        $secret = get_option($option_name);

        if (!$secret) {
            $secret = bin2hex(random_bytes(16));
            add_option($option_name, $secret, '', false);
        }

        return $secret;
    }

    public function register_webhook_endpoint()
    {
        register_rest_route('coinsnap-bitcoin-donation/v1', 'webhook', [
            'methods'  => ['POST'],
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => [$this, 'verify_webhook_request']
        ]);
    }

    function verify_webhook_request($request)
    {
        $secret = $this->get_webhook_secret();

        $coinsnap_sig = $request->get_header('X-Coinsnap-Sig');
        $btcpay_sig = $request->get_header('btcpay_sig');
        $signature_header = !empty($coinsnap_sig) ? $coinsnap_sig : $btcpay_sig;
        if (empty($signature_header)) {
            return false;
        }

        $payload = $request->get_body();

        $computed_signature = hash_hmac('sha256', $payload, $secret);
        $computed_signature = 'sha256=' . $computed_signature; // Prefix the computed_signature with 'sha256='
        if (!hash_equals($computed_signature, $signature_header)) {
            return false;
        }
        return true;
    }

    public function handle_webhook(WP_REST_Request $request)
    {
        $payload_data = $request->get_json_params();

        if (isset($payload_data['type']) && ($payload_data['type'] === 'Settled' || $payload_data['type'] === 'InvoiceSettled')) {

            if (isset($payload_data['metadata']['modal'])) {
                global $wpdb;
                $invoiceId = $payload_data['invoiceId'];
                $wpdb->insert(
                    "{$wpdb->prefix}donation_payments",
                    [
                        'payment_id' => $invoiceId,
                        'status'     => 'completed'
                    ],
                    ['%s', '%s']
                );
                // Public donor
                if (isset($payload_data['metadata']['publicDonor']) && $payload_data['metadata']['publicDonor'] == '1') {

                    $name = $payload_data['metadata']['donorName'];
                    $email = $payload_data['metadata']['donorEmail'];
                    $address = $payload_data['metadata']['donorAddress'];
                    $message = $payload_data['metadata']['donorMessage'];
                    $opt_out = $payload_data['metadata']['donorOptOut'];
                    $custom = $payload_data['metadata']['donorCustom'];
                    $type = $payload_data['metadata']['formType'];
                    $amount = $payload_data['metadata']['amount'];
                    $opt_out_value = filter_var($opt_out, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                    $post_data = array(
                        'post_title'    => $name,
                        'post_status'   => 'publish',
                        'post_type'     => 'bitcoin-pds',
                        'post_content'  => $message
                    );

                    $post_id = wp_insert_post($post_data);

                    if ($post_id) {
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_donor_name', sanitize_text_field($name));
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_amount', sanitize_text_field($amount));
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_message', sanitize_text_field($message));
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_form_type', sanitize_text_field($type));
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_dont_show', $opt_out_value);
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_email', sanitize_email($email));
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_address', sanitize_text_field($address));
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_payment_id', sanitize_text_field($invoiceId));
                        update_post_meta($post_id, '_coinsnap_bitcoin_donation_custom_field', sanitize_text_field($custom));
                    }
                }
                // Shoutouts
            }
            if (isset($payload_data['metadata']['type']) && $payload_data['metadata']['type'] == "Bitcoin Shoutout") {
                $invoiceId = $payload_data['invoiceId'];
                //error_log(print_r($payload_data, true));
                $name = $payload_data['metadata']['name'];
                $message = $payload_data['metadata']['orderNumber'];
                $amount = $payload_data['metadata']['amount'];
                $provider = $payload_data['metadata']['provider'];

                // Get sats amount from payload if available, otherwise set to empty
                $sats_amount = isset($payload_data['metadata']['satsAmount']) ? $payload_data['metadata']['satsAmount'] : '';
                error_log(print_r($payload_data, true));
                $post_data = array(
                    'post_title'    => 'Shoutout from ' . $name,
                    'post_status'   => 'publish',
                    'post_type'     => 'bitcoin-shoutouts',
                );
                $post_id = wp_insert_post($post_data);

                if ($post_id) {
                    update_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_name', sanitize_text_field($name));
                    update_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_amount', sanitize_text_field($amount));
                    update_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_sats_amount', sanitize_text_field($sats_amount));
                    update_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_invoice_id', sanitize_text_field($invoiceId));
                    update_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_message', sanitize_text_field($message));
                    update_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_provider', sanitize_text_field($provider));
                }
            }
        }

        return new WP_REST_Response('Webhook type not handled.', 200);
    }
}
new coinsnap_bitcoin_donation_Webhooks();
