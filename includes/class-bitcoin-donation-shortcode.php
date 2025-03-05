<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Shortcode
{
    public function __construct()
    {
        add_shortcode('bitcoin_donation', [$this, 'bitcoin_donation_render_shortcode']);
    }

    function bitcoin_donation_render_shortcode()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $options_general = get_option('bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $currency = $options['currency'] ?? 'USD';
        $button_text = $options['button_text'] ?? 'Donate';
        $title_text = $options['title_text'] ?? 'Donate with Bitcoin';
        $active = $options['simple_donation_active'] ?? '1';
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
        <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> narrow-form">
            <div class="bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>
                <select id="bitcoin-donation-swap" class="currency-swapper">
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                    <option value="JPY">JPY</option>
                    <option value="GBP">GBP</option>
                    <option value="sats">SATS</option>
                    <option value="CHF">CHF</option>
                </select>
            </div>

            <input type="text" id="bitcoin-donation-email" name="bitcoin-email" style="display: none;" aria-hidden="true">

            <label for="bitcoin-donation-amount">Amount</label>
            <div class="amount-wrapper">
                <input type="text" id="bitcoin-donation-amount">
                <div class="secondary-amount">
                    <span id="bitcoin-donation-satoshi"></span>
                </div>
            </div>

            <!-- <label for="bitcoin-donation-amount">Amount (in <?php echo esc_html($currency); ?>):</label>
            <input type="text" id="bitcoin-donation-amount" step="0.01"> -->

            <!-- <label for="bitcoin-donation-satoshi">Satoshi:</label>
            <input type="text" id="bitcoin-donation-satoshi"> -->

            <label for="bitcoin-donation-message">Message:</label>
            <textarea id="bitcoin-donation-message" class="bitcoin-donation-message" rows="2"></textarea>

            <button id="bitcoin-donation-pay"><?php echo esc_html($button_text); ?></button>
        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode();
