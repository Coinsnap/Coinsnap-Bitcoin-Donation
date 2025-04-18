<?php
if (! defined('ABSPATH')) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Shortcode_Multi_Amount
{
    public function __construct()
    {
        add_shortcode('multi_amount_donation', [$this, 'coinsnap_bitcoin_donation_multi_render_shortcode']);
    }

    private function get_template($template_name, $args = [])
    {
        if ($args && is_array($args)) {
            extract($args);
        }

        $template = plugin_dir_path(__FILE__) . '../templates/' . $template_name . '.php';

        if (file_exists($template)) {
            include $template;
        }
    }

    function coinsnap_bitcoin_donation_multi_render_shortcode()
    {
        $options = get_option('coinsnap_bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $options_general = get_option('coinsnap_bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme' : 'coinsnap-bitcoin-donation-light-theme';
        $modal_theme = $options_general['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
        $button_text = $options['multi_amount_button_text'] ?? 'Donate';
        $title_text = $options['multi_amount_title_text'] ?? 'Donate with Bitcoin';
        $snap1 = $options['multi_amount_default_snap1'] ?? '1';
        $snap2 = $options['multi_amount_default_snap2'] ?? '1';
        $snap3 = $options['multi_amount_default_snap3'] ?? '1';
        $active = $options['multi_amount_donation_active'] ?? '1';
        $first_name = $options['multi_amount_first_name'];
        $last_name = $options['multi_amount_last_name'];
        $email = $options['multi_amount_email'];
        $address = $options['multi_amount_address'];
        $custom = $options['multi_amount_custom_field_visibility'];
        $custom_name = $options['multi_amount_custom_field_name'];
        $public_donors = $options['multi_amount_public_donors'];
        if (!$active) {
            ob_start();
?>
            <div class="coinsnap-bitcoin-donation-form <?php echo esc_attr($theme_class); ?> narrow-form">
                <div class="coinsnap-bitcoin-donation-title-wrapper"
                    style="display: flex;justify-content: center; flex-direction: column; align-items: center; margin: 0">
                    <h3><?php echo esc_html($title_text); ?></h3>
                </div>
                <h4 style="text-align: center;">This form is not active</h4>

            </div>
        <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div class="coinsnap-bitcoin-donation-form <?php echo esc_attr($theme_class);
                                                    echo " " . esc_attr($modal_theme); ?> multi-form">

            <div class="coinsnap-bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>
                <select id="coinsnap-bitcoin-donation-swap-multi" class="currency-swapper">
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                    <option value="JPY">JPY</option>
                    <option value="GBP">GBP</option>
                    <option value="sats">SATS</option>
                    <option value="CHF">CHF</option>
                </select>

            </div>

            <input type="text" id="coinsnap-bitcoin-donation-email-multi" name="bitcoin-email" style="display: none;" aria-hidden="true">

            <label for="coinsnap-bitcoin-donation-amount-multi">Amount</label>
            <div class="amount-wrapper">
                <input type="text" id="coinsnap-bitcoin-donation-amount-multi">
                <div class="secondary-amount">
                    <span id="coinsnap-bitcoin-donation-satoshi-multi"></span>
                </div>
            </div>
            <div class="snap-title-container">
                <h4>Snap Donations</h4>

            </div>
            <div class="snap-container">
                <button id="coinsnap-bitcoin-donation-pay-multi-snap1" class="snap-button">
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap1-primary" class="snap-primary-amount">
                        <?php echo esc_html($snap1); ?>
                    </span>
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap1-secondary" class="snap-secondary-amount"></span>
                </button>
                <button id="coinsnap-bitcoin-donation-pay-multi-snap2" class="snap-button">
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap2-primary" class="snap-primary-amount">
                        <?php echo esc_html($snap2); ?>
                    </span>
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap2-secondary" class="snap-secondary-amount"></span>
                </button>
                <button id="coinsnap-bitcoin-donation-pay-multi-snap3" class="snap-button">
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap3-primary" class="snap-primary-amount">
                        <?php echo esc_html($snap3); ?>
                    </span>
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap3-secondary" class="snap-secondary-amount"></span>
                </button>
            </div>

            <label for="coinsnap-bitcoin-donation-message-multi">Message:</label>
            <textarea id="coinsnap-bitcoin-donation-message-multi" class="coinsnap-bitcoin-donation-message" rows="2"></textarea>

            <button id="coinsnap-bitcoin-donation-pay-multi"><?php echo esc_html($button_text); ?></button>
            <div id="coinsnap-bitcoin-donation-blur-overlay-multi" class="blur-overlay"></div>
            <?php
            $this->get_template('coinsnap-bitcoin-donation-modal', [
                'prefix' => 'coinsnap-bitcoin-donation-',
                'sufix' => '-multi',
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'address' => $address,
                'public_donors' => $public_donors,
                'custom' => $custom,
                'custom_name' => $custom_name,
            ]);
            ?>

        </div>

<?php

        return ob_get_clean();
    }
}

new Coinsnap_Bitcoin_Donation_Shortcode_Multi_Amount();
