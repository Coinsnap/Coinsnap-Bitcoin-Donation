<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Shortcode_Wide
{
    public function __construct()
    {
        add_shortcode('bitcoin_donation_wide', [$this, 'bitcoin_donation_render_shortcode_wide']);
    }

    function bitcoin_donation_render_shortcode_wide()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $theme_class = isset($options['theme']) && $options['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $currency = $options['currency'] ?? 'USD';
        $butoon_text = $options['button_text'] ?? 'Donate';
        $title_text = $options['title_text'] ?? 'Donate with Bitcoin';

        ob_start();
?>
        <div id="bitcoin-donation-form-wide" class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> wide-form">
            <div class="bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>

            </div>
            <input type="text" id="bitcoin-donation-email-wide" name="bitcoin-email" style="display: none;" aria-hidden="true">
            <div class="bitcoin-donation-wide-field-wrapper">
                <div class="bitcoin-donation-wide-up">

                    <div class="shoutout-input-label">
                        <label for="bitcoin-donation-amount">Amount (in <?php echo esc_html($currency); ?>)</label>
                        <input type="text" id="bitcoin-donation-amount-wide" step="0.01">
                    </div>

                    <div class="shoutout-input-label">
                        <label for="bitcoin-donation-satoshi">Satoshi</label>
                        <input type="text" id="bitcoin-donation-satoshi-wide">
                    </div>
                    <button class="wide-form-button" id="bitcoin-donation-pay-wide"><?php echo esc_html($butoon_text); ?></button>
                </div>
                <div class="bitcoin-donation-wide-down">
                    <label for="bitcoin-donation-message">Message</label>
                    <textarea id="bitcoin-donation-message-wide" class="bitcoin-donation-message wide-message-text-area" required name="message" rows="2"></textarea>
                </div>

            </div>
        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode_Wide();
