<?php
if (! defined('ABSPATH')) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Shortcode_Wide
{
    public function __construct()
    {
        add_shortcode('coinsnap_bitcoin_donation_wide', [$this, 'coinsnap_bitcoin_donation_render_shortcode_wide']);
    }

    function coinsnap_bitcoin_donation_render_shortcode_wide()
    {
        $options = get_option('coinsnap_bitcoin_donation_options');
        $options = is_array($options) ? $options : [];
        $theme_class = isset($options['theme']) && $options['theme'] === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme-wide' : 'coinsnap-bitcoin-donation-light-theme-wide';
        $currency = $options['currency'] ?? 'USD';
        $butoon_text = $options['button_text'] ?? 'Donate';
        $title_text = $options['title_text'] ?? 'Donate with Bitcoin';

        ob_start();
?>
        <div id="coinsnap-bitcoin-donation-form-wide" class="<?php echo esc_attr($theme_class); ?>">
            <div class="coinsnap-bitcoin-donation-title-wrapper-wide">
                <h3><?php echo esc_html($title_text); ?></h3>

            </div>
            <div class="bitcoin-donation-wide-field-wrapper">
                <input class="amount-field" type="number" id="coinsnap-bitcoin-donation-amount" step="0.01" placeholder="Amount (in <?php echo esc_html($currency); ?>)">
                <input class="amount-field" type="number" id="coinsnap-bitcoin-donation-satoshi" placeholder="Satoshi">
                <input class="coinsnap-bitcoin-donation-message-wide" id="coinsnap-bitcoin-donation-message" placeholder="Message"></input>
                <button id="coinsnap-bitcoin-donation-pay"><?php echo esc_html($butoon_text); ?></button>
            </div>
        </div>

<?php

        return ob_get_clean();
    }
}

new Coinsnap_Bitcoin_Donation_Shortcode_Wide();
