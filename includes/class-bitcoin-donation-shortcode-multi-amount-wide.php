<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Shortcode_Multi_Amount_Wide
{
    public function __construct()
    {
        add_shortcode('multi_amount_donation_wide', [$this, 'bitcoin_donation_multi_render_shortcode_wide']);
    }

    function bitcoin_donation_multi_render_shortcode_wide()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $theme_class = $options['multi_amount_theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $butoon_text = $options['multi_amount_button_text'] ?? 'Donate';
        $title_text = $options['multi_amount_title_text'] ?? 'Donate with Bitcoin';
        $snap1 = $options['multi_amount_default_snap1'] ?? '1';
        $snap2 = $options['multi_amount_default_snap2'] ?? '1';
        $snap3 = $options['multi_amount_default_snap3'] ?? '1';

        ob_start();
?>
        <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> wide-form">
            <div class="bitcoin-donation-multi-wide-wrapper">

                <select id="bitcoin-donation-multi-swap-wide" class="currency-swapper">
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                    <option value="JPY">JPY</option>
                    <option value="GBP">GBP</option>
                    <option value="sats">SATS</option>
                    <option value="CHF">CHF</option>
                </select>

                <div class="bitcoin-donation-title-wrapper">
                    <h3><?php echo esc_html($title_text); ?></h3>
                </div>

                <input type="text" id="bitcoin-donation-email-multi-wide" name="bitcoin-email" style="display: none;" aria-hidden="true">

                <div class="bitcoin-donation-wide-up">
                    <div class="mulit-wide-label-left">
                        <label for="bitcoin-donation-amount-multi">Amount</label>
                        <div class="amount-wrapper">
                            <input type="text" id="bitcoin-donation-amount-multi-wide">
                            <div class="secondary-amount">
                                <span id="bitcoin-donation-satoshi-multi-wide"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mulit-wide-label-right">

                        <label for="bitcoin-donation-message-multi">Message:</label>
                        <textarea id="bitcoin-donation-message-multi-wide" class="bitcoin-donation-message" rows="1"></textarea>
                    </div>

                </div>

                <div class="snap-title-container">
                    <h4>Snap Donations</h4>

                </div>
                <div class="snap-container">
                    <button id="bitcoin-donation-pay-multi-snap1-wide" class="snap-button">
                        <span id="bitcoin-donation-pay-multi-snap1-primary-wide" class="snap-primary-amount">
                            <?php echo esc_html($snap1); ?>
                        </span>
                        <span id="bitcoin-donation-pay-multi-snap1-secondary-wide" class="snap-secondary-amount"></span>
                    </button>
                    <button id="bitcoin-donation-pay-multi-snap2-wide" class="snap-button">
                        <span id="bitcoin-donation-pay-multi-snap2-primary-wide" class="snap-primary-amount">
                            <?php echo esc_html($snap2); ?>
                        </span>
                        <span id="bitcoin-donation-pay-multi-snap2-secondary-wide" class="snap-secondary-amount"></span>
                    </button>
                    <button id="bitcoin-donation-pay-multi-snap3-wide" class="snap-button">
                        <span id="bitcoin-donation-pay-multi-snap3-primary-wide" class="snap-primary-amount">
                            <?php echo esc_html($snap3); ?>
                        </span>
                        <span id="bitcoin-donation-pay-multi-snap3-secondary-wide" class="snap-secondary-amount"></span>
                    </button>
                </div>


                <button class="multi-wide-button" id="bitcoin-donation-pay-multi-wide"><?php echo esc_html($butoon_text); ?></button>
            </div>
        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode_Multi_Amount_Wide();
