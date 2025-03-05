<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Shortcode_Multi_Amount
{
    public function __construct()
    {
        add_shortcode('multi_amount_donation', [$this, 'bitcoin_donation_multi_render_shortcode']);
    }

    function bitcoin_donation_multi_render_shortcode()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $options_general = get_option('bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $button_text = $options['multi_amount_button_text'] ?? 'Donate';
        $title_text = $options['multi_amount_title_text'] ?? 'Donate with Bitcoin';
        $snap1 = $options['multi_amount_default_snap1'] ?? '1';
        $snap2 = $options['multi_amount_default_snap2'] ?? '1';
        $snap3 = $options['multi_amount_default_snap3'] ?? '1';
        $active = $options['multi_amount_donation_active'] ?? '1';
        if (!$active) {
            ob_start();
?>
            <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> narrow-form">
                <div class="bitcoin-donation-title-wrapper"
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
        <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> multi-form">

            <div class="bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>
                <select id="bitcoin-donation-multi-swap" class="currency-swapper">
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                    <option value="JPY">JPY</option>
                    <option value="GBP">GBP</option>
                    <option value="sats">SATS</option>
                    <option value="CHF">CHF</option>
                </select>

            </div>

            <input type="text" id="bitcoin-donation-email-multi" name="bitcoin-email" style="display: none;" aria-hidden="true">

            <label for="bitcoin-donation-amount-multi">Amount</label>
            <div class="amount-wrapper">
                <input type="text" id="bitcoin-donation-amount-multi">
                <div class="secondary-amount">
                    <span id="bitcoin-donation-satoshi-multi"></span>
                </div>
            </div>
            <div class="snap-title-container">
                <h4>Snap Donations</h4>

            </div>
            <div class="snap-container">
                <button id="bitcoin-donation-pay-multi-snap1" class="snap-button">
                    <span id="bitcoin-donation-pay-multi-snap1-primary" class="snap-primary-amount">
                        <?php echo esc_html($snap1); ?>
                    </span>
                    <span id="bitcoin-donation-pay-multi-snap1-secondary" class="snap-secondary-amount"></span>
                </button>
                <button id="bitcoin-donation-pay-multi-snap2" class="snap-button">
                    <span id="bitcoin-donation-pay-multi-snap2-primary" class="snap-primary-amount">
                        <?php echo esc_html($snap2); ?>
                    </span>
                    <span id="bitcoin-donation-pay-multi-snap2-secondary" class="snap-secondary-amount"></span>
                </button>
                <button id="bitcoin-donation-pay-multi-snap3" class="snap-button">
                    <span id="bitcoin-donation-pay-multi-snap3-primary" class="snap-primary-amount">
                        <?php echo esc_html($snap3); ?>
                    </span>
                    <span id="bitcoin-donation-pay-multi-snap3-secondary" class="snap-secondary-amount"></span>
                </button>
            </div>

            <label for="bitcoin-donation-message-multi">Message:</label>
            <textarea id="bitcoin-donation-message-multi" class="bitcoin-donation-message" rows="2"></textarea>

            <button id="bitcoin-donation-pay-multi"><?php echo esc_html($button_text); ?></button>
        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode_Multi_Amount();
