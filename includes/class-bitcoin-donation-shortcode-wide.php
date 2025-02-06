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
        $options = get_option('bitcoin_donation_options');
        $options = is_array($options) ? $options : [];
        $theme_class = isset($options['theme']) && $options['theme'] === 'dark' ? 'bitcoin-donation-dark-theme-wide' : 'bitcoin-donation-light-theme-wide';
        $currency = $options['currency'] ?? 'USD';
        $butoon_text = $options['button_text'] ?? 'Donate';
        $title_text = $options['title_text'] ?? 'Donate with Bitcoin';

        ob_start();
?>
        <div id="bitcoin-donation-donation-form-wide" class="<?php echo esc_attr($theme_class); ?>">
            <div class="bitcoin-donation-title-wrapper-wide">
                <h3><?php echo esc_html($title_text); ?></h3>

            </div>
            <input type="text" id="bitcoin-donation-email" name="bitcoin-email" style="display: none;" aria-hidden="true">
            <div class="bitcoin-donation-wide-field-wrapper">
                <div class="bitcoin-donation-wide-up">

                    <div class="shoutout-input-label">
                        <label for="bitcoin-donation-amount">Amount (in <?php echo esc_html($currency); ?>)</label>
                        <input type="number" id="bitcoin-donation-amount" step="0.01">
                    </div>

                    <div class="shoutout-input-label">
                        <label for="bitcoin-donation-satoshi">Satoshi</label>
                        <input type="number" id="bitcoin-donation-satoshi">
                    </div>
                    <button id="bitcoin-donation-pay"><?php echo esc_html($butoon_text); ?></button>
                </div>
                <div class="bitcoin-donation-wide-down">
                    <label for="bitcoin-donation-message">Message</label>
                    <textarea id="bitcoin-donation-message" class="bitcoin-donation-message-wide" required name="message" rows="2"></textarea>
                </div>

            </div>
        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode_Wide();
