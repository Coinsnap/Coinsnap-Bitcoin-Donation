<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Settings
{
    public function __construct()
    {
        // Register menus
        add_action('admin_menu', [$this, 'bitcoin_donation_add_admin_menu']);
        add_action('admin_init', [$this, 'bitcoin_donation_settings_init']);
    }

    function bitcoin_donation_add_admin_menu()
    {
        add_menu_page(
            'Bitcoin Donations',
            'Bitcoin Donations',
            'manage_options',
            'bitcoin_donation',
            [$this, 'bitcoin_donation_options_page'],
            plugin_dir_url(__FILE__) . 'assets/bitcoin.svg',

            100
        );
    }

    function bitcoin_donation_settings_init()
    {
        register_setting('bitcoin_donation_settings', 'bitcoin_donation_options', [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_options']
        ]);

        // Provider Section
        add_settings_section(
            'bitcoin_donation_provider_section',
            'General Settings',
            [$this, 'provider_section_callback'],
            'bitcoin_donation'
        );

        add_settings_field(
            'currency',
            'Currency',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_provider_section',
            [
                'label_for' => 'currency',
                'type'      => 'select',
                'options'   => [
                    "EUR" => "EUR",
                    "USD" => "USD",
                    "CAD" => "CAD",
                    "JPY" => "JPY",
                    "GBP" => "GBP",
                    "CHF" => "CHF"
                ]
            ]
        );

        add_settings_field(
            'theme',
            'Theme',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_provider_section',
            [
                'label_for' => 'theme',
                'type'      => 'select',
                'options'   => [
                    "light" => "Light",
                    "dark" => "Dark"
                ]
            ]
        );

        add_settings_field(
            'button_text',
            'Button Text',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_provider_section',
            [
                'label_for' => 'button_text',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'title_text',
            'Title Text',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_provider_section',
            [
                'label_for' => 'title_text',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'default_amount',
            'Default Amount',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_provider_section',
            [
                'label_for' => 'default_amount',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'default_message',
            'Default Message',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_provider_section',
            [
                'label_for' => 'default_message',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'redirect_url',
            'Redirect Url (Thank You Page)',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_provider_section',
            [
                'label_for' => 'redirect_url',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'provider',
            'Payment Gateway',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_provider_section',
            [
                'label_for' => 'provider',
                'type'      => 'select',
                'options'   => [
                    'coinsnap' => 'Coinsnap',
                    'btcpay'   => 'BTCPay'
                ]
            ]
        );



        // Coinsnap Section
        add_settings_section(
            'bitcoin_donation_coinsnap_section',
            'Coinsnap Settings',
            [$this, 'coinsnap_section_callback'],
            'bitcoin_donation'
        );

        add_settings_field(
            'coinsnap_store_id',
            'Coinsnap Store ID',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_coinsnap_section',
            [
                'label_for' => 'coinsnap_store_id',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'coinsnap_api_key',
            'Coinsnap API Key',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_coinsnap_section',
            [
                'label_for' => 'coinsnap_api_key',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'check_connection_coinsnap',
            'Check Connection',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_coinsnap_section',
            [
                'label_for' => 'check_connection_coinsnap',
                'type'      => 'check_connection',
                'id'        => 'check_connection_coinsnap'

            ]
        );


        // BTCPay Section
        add_settings_section(
            'bitcoin_donation_btcpay_section',
            'BTCPay Settings',
            [$this, 'btcpay_section_callback'],
            'bitcoin_donation'
        );

        add_settings_field(
            'btcpay_store_id',
            'BTCPay Store ID',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_btcpay_section',
            [
                'label_for' => 'btcpay_store_id',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'btcpay_api_key',
            'BTCPay API Key',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_btcpay_section',
            [
                'label_for' => 'btcpay_api_key',
                'type'      => 'text'
            ]
        );

        add_settings_field(
            'btcpay_url',
            'BTCPay URL',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_btcpay_section',
            [
                'label_for' => 'btcpay_url',
                'type'      => 'text'
            ]
        );
        add_settings_field(
            'check_connection_btcpay',
            'Check Connection',
            [$this, 'render_field'],
            'bitcoin_donation',
            'bitcoin_donation_btcpay_section',
            [
                'label_for' => 'check_connection_btcpay',
                'type'      => 'check_connection',
                'id'        => 'check_connection_btcpay'
            ]
        );
    }

    public function sanitize_options($options)
    {
        $sanitized = [];

        if (isset($options['provider'])) {
            $sanitized['provider'] = sanitize_text_field($options['provider']);
        }

        if (isset($options['currency'])) {
            $sanitized['currency'] = sanitize_text_field($options['currency']);
        }

        if (isset($options['theme'])) {
            $sanitized['theme'] = sanitize_text_field($options['theme']);
        }

        if (isset($options['button_text'])) {
            $sanitized['button_text'] = sanitize_text_field($options['button_text']);
        }

        if (isset($options['title_text'])) {
            $sanitized['title_text'] = sanitize_text_field($options['title_text']);
        }

        if (isset($options['default_amount'])) {
            $sanitized['default_amount'] = sanitize_text_field($options['default_amount']);
        }

        if (isset($options['default_message'])) {
            $sanitized['default_message'] = sanitize_text_field($options['default_message']);
        }

        if (isset($options['redirect_url'])) {
            $sanitized['redirect_url'] = sanitize_text_field($options['redirect_url']);
        }

        if (isset($options['coinsnap_store_id'])) {
            $sanitized['coinsnap_store_id'] = sanitize_text_field($options['coinsnap_store_id']);
        }

        if (isset($options['coinsnap_api_key'])) {
            $sanitized['coinsnap_api_key'] = sanitize_text_field($options['coinsnap_api_key']);
        }

        if (isset($options['btcpay_store_id'])) {
            $sanitized['btcpay_store_id'] = sanitize_text_field($options['btcpay_store_id']);
        }

        if (isset($options['btcpay_api_key'])) {
            $sanitized['btcpay_api_key'] = sanitize_text_field($options['btcpay_api_key']);
        }

        if (isset($options['btcpay_url'])) {
            $sanitized['btcpay_url'] = esc_url_raw($options['btcpay_url']);
        }

        // Check if provider is working
        if (isset($sanitized['provider']) && $sanitized['provider'] === 'coinsnap') {
            $this->check_coinsnap_connection($sanitized['coinsnap_store_id'], $sanitized['coinsnap_api_key']);
        } else if (isset($sanitized['provider']) && $sanitized['provider'] === 'btcpay') {
            $this->check_btcpay_connection($sanitized['btcpay_store_id'], $sanitized['btcpay_api_key'], $sanitized['btcpay_url']);
        }

        return $sanitized;
    }

    public function check_coinsnap_connection($coinsnap_store_id, $coinsnap_api_key)
    {
        $response = wp_remote_get("https://app.coinsnap.io/api/v1/stores/{$coinsnap_store_id}", [
            'headers' => [
                'X-api-key' => $coinsnap_api_key,
            ],
        ]);

        if (is_wp_error($response)) {
            add_settings_error(
                'bitcoin_donation_settings',
                'coinsnap_connection_error',
                'Error connecting to Coinsnap. Please check your API key and store ID.',
                'error'
            );
        } else {

            $response_code = wp_remote_retrieve_response_code($response);

            if ($response_code !== 200) {
                add_settings_error(
                    'bitcoin_donation_settings',
                    'coinsnap_response_error',
                    'Coinsnap responded with an error. Please verify your credentials.',
                    'error'
                );
            }
        }
    }

    public function check_btcpay_connection($btcpay_store_id, $btcpay_api_key, $btcpay_url)
    {

        $response = wp_remote_get("{$btcpay_url}/api/v1/stores/{$btcpay_store_id}/invoices", [
            'headers' => [
                'Authorization' => 'token ' . $btcpay_api_key,
            ],
        ]);


        if (is_wp_error($response)) {
            add_settings_error(
                'bitcoin_donation_settings',
                'btcpay_connection_error',
                'Error connecting to Btcpay. Please check your API key and store ID.',
                'error'
            );
        } else {

            $response_code = wp_remote_retrieve_response_code($response);

            if ($response_code !== 200) {
                add_settings_error(
                    'bitcoin_donation_settings',
                    'btcpay_response_error',
                    'Btcpay responded with an error. Please verify your credentials.',
                    'error'
                );
            }
        }
    }


    // Optional section callbacks for additional descriptions
    public function provider_section_callback()
    {
        echo esc_html_e('Select your preferred payment provider and configure its settings below.', 'bitcoin_donation');
    }

    public function coinsnap_section_callback()
    {
        echo esc_html_e('Enter your Coinsnap credentials here if you selected Coinsnap as your payment provider.', 'bitcoin_donation');
    }

    public function btcpay_section_callback()
    {
        echo esc_html_e('Enter your BTCPay credentials here if you selected BTCPay as your payment provider.', 'bitcoin_donation');
    }


    function bitcoin_donation_section_general_callback()
    {
        echo __('Configure the plugin settings below.', 'sdb');
    }

    /**
     * Renders a specific settings section manually.
     *
     * @param string $section_id The ID of the section to render.
     */
    private function render_section($section_id)
    {
        global $wp_settings_sections, $wp_settings_fields;

        if (! isset($wp_settings_sections['bitcoin_donation'][$section_id])) {
            return;
        }

        $section = $wp_settings_sections['bitcoin_donation'][$section_id];

        if ($section['title']) {
            echo '<h3>' . esc_html($section['title']) . '</h3>';
        }
        if ($section['callback']) {
            call_user_func($section['callback'], $section);
        }

        if (! empty($wp_settings_fields['bitcoin_donation'][$section_id])) {
            echo '<table class="form-table">';
            do_settings_fields('bitcoin_donation', $section_id);
            echo '</table>';
        }
    }

    public function render_field($args)
    {
        $options     = get_option('bitcoin_donation_options', []);
        $field_id    = $args['label_for'];
        $field_type  = $args['type'];
        $field_value = isset($options[$field_id]) ? $options[$field_id] : '';
        $defaults = [
            'default_message' => 'Thank you for your work',
            'default_amount'  => '5',
            'button_text'     => 'Donate',
            'title_text'      => 'Donate with Bitcoin'
        ];
        if ($field_type == 'text') {
            $field_value = isset($options[$field_id]) ? $options[$field_id] : ($defaults[$field_id] ?? '');
        }
        switch ($field_type) {
            case 'select':
                echo '<select 
                id="' . esc_attr($field_id) . '" 
                name="bitcoin_donation_options[' . esc_attr($field_id) . ']"
                class="regular-text">';
                foreach ($args['options'] as $value => $label) {
                    echo '<option value="' . esc_attr($value) . '"' .
                        selected($field_value, $value, false) . '>' .
                        esc_html($label) . '</option>';
                }
                echo '</select>';
                break;

            case 'check_connection':
                $id = isset($args['id']) ? $args['id'] : 'check_connection';

                echo '<div >' . '<button id="' . esc_attr($id) . '_button">Check</button>' . '<span style="" id="' . esc_attr($id) .  '">' . '</span>' . '</div>';
                break;


                break;

            case 'text':
                echo '<input type="text" 
                id="' . esc_attr($field_id) . '" 
                name="bitcoin_donation_options[' . esc_attr($field_id) . ']" 
                value="' . esc_attr($field_value) . '" 
                class="regular-text"' .
                    (isset($args['readonly']) && $args['readonly'] ? ' readonly' : '') .
                    (isset($args['value']) ? ' value="' . esc_attr($args['value']) . '"' : '') .
                    '>';
                break;
        }

        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }



    public function bitcoin_donation_options_page()
    {
?>
        <div class="wrap">
            <h1>Bitcoin Donation Settings</h1>
            <span class='shortcode_text_wrapper'>Use the shortcode <span class='shortcode_text'>[bitcoin_donation]</span></span>

            <!-- Display any registered settings errors -->
            <?php settings_errors('bitcoin_donation_settings'); ?>

            <form method="post" action="options.php">
                <?php
                // Render the settings fields for the Bitcoin Donation
                settings_fields('bitcoin_donation_settings');

                // Render the Provider Settings Section
                $this->render_section('bitcoin_donation_provider_section');

                // Render Coinsnap Settings inside a wrapper
                echo '<div id="coinsnap-settings-wrapper" class="provider-settings">';
                $this->render_section('bitcoin_donation_coinsnap_section');
                echo '</div>';

                // Render BTCPay Settings inside a wrapper
                echo '<div id="btcpay-settings-wrapper" class="provider-settings">';
                $this->render_section('bitcoin_donation_btcpay_section');
                echo '</div>';
                ?>
                <?php
                // Render submit button
                submit_button();
                ?>
            </form>
        </div>
<?php
    }
}
new Bitcoin_Donation_Settings();
