<?php
if (! defined('ABSPATH')) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Shortcode
{
    public function __construct()
    {
        add_shortcode('coinsnap_bitcoin_donation', [$this, 'coinsnap_bitcoin_donation_render_shortcode']);
    }

    function coinsnap_bitcoin_donation_render_shortcode()
    {
        $options = get_option('coinsnap_bitcoin_donation_options');
        $options = is_array($options) ? $options : [];
        $theme_class = isset($options['theme']) && $options['theme'] === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme' : 'coinsnap-bitcoin-donation-light-theme';
        $currency = $options['currency'] ?? 'USD';
        $butoon_text = $options['button_text'] ?? 'Donate';
        $title_text = $options['title_text'] ?? 'Donate with Bitcoin';
        ob_start();
?>
        <div id="coinsnap-bitcoin-donation-form" class="<?php echo esc_attr($theme_class); ?>">
            <div class="coinsnap-bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>

            </div>
            <label for="coinsnap-bitcoin-donation-amount">Amount (in <?php echo esc_html($currency); ?>):</label>
            <input type="number" id="coinsnap-bitcoin-donation-amount" step="0.01">

            <label for="coinsnap-bitcoin-donation-satoshi">Satoshi:</label>
            <input type="number" id="coinsnap-bitcoin-donation-satoshi">

            <label for="coinsnap-bitcoin-donation-message">Message:</label>
            <textarea id="coinsnap-bitcoin-donation-message" rows="2"></textarea>

            <button id="coinsnap-bitcoin-donation-pay"><?php echo esc_html($butoon_text); ?></button>
        </div>

<?php

        return ob_get_clean();
    }
}

new Coinsnap_Bitcoin_Donation_Shortcode();
