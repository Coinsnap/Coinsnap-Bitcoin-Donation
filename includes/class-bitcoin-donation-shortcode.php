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
        $theme_class = isset($options['theme']) && $options['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $currency = $options['currency'] ?? 'USD';
        $butoon_text = $options['button_text'] ?? 'Donate';
        $title_text = $options['title_text'] ?? 'Donate with Bitcoin';
        ob_start();
?>
        <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> narrow-form">
            <div class="bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>
            </div>

            <input type="text" id="bitcoin-donation-email" name="bitcoin-email" style="display: none;" aria-hidden="true">

            <label for="bitcoin-donation-amount">Amount (in <?php echo esc_html($currency); ?>):</label>
            <input type="text" id="bitcoin-donation-amount" step="0.01">

            <label for="bitcoin-donation-satoshi">Satoshi:</label>
            <input type="text" id="bitcoin-donation-satoshi">

            <label for="bitcoin-donation-message">Message:</label>
            <textarea id="bitcoin-donation-message" class="bitcoin-donation-message" rows="2"></textarea>

            <button id="bitcoin-donation-pay"><?php echo esc_html($butoon_text); ?></button>
        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode();
