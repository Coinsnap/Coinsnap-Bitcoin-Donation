<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Shoutouts_Form
{
    public function __construct()
    {
        add_shortcode('shoutout_form', [$this, 'bitcoin_donation_render_shortcode']);
    }

    function bitcoin_donation_render_shortcode()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $options_general = get_option('bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $currency = $options['shoutout_currency'] ?? 'USD';
        $button_text = $options['shoutout_button_text'] ?? 'Shoutout';
        $title_text = $options['shoutout_title_text'] ?? 'Bitcoin Shoutouts';
        $min_amount = (int)$options['shoutout_minimum_amount'] ?? 21;
        $premium_amount = (int)$options['shoutout_premium_amount'] ?? 21000;
        $active = $options['shoutout_donation_active'] ?? '1';
        if (!$active) {
            ob_start();
?>
            <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?>">
                <div class="shoutout-form-wrapper"
                    style="display: flex;justify-content: center; flex-direction: column; align-items: center; margin: 0">
                    <h3><?php echo esc_html($title_text); ?></h3>
                    <h4 style="text-align: center;">This form is not active</h4>
                </div>

            </div>
        <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div id="bitcoin-donation-shoutouts-form" class="bitcoin-donation-donation-form">
            <div class="shoutout-form-wrapper <?php echo esc_attr($theme_class); ?>">
                <form method="post">
                    <?php wp_nonce_field('shoutout_nonce', 'shoutout_nonce'); ?>
                    <input type="hidden" name="shoutout_submitted" value="1">

                    <div class="bitcoin-donation-title-wrapper">
                        <h3><?php echo esc_html($title_text); ?></h3>
                    </div>
                    <div class="shoutout-form-container">
                        <div class="shoutout-form-left">
                            <div class="shoutout-input-label">
                                <label for="bitcoin-donation-shoutout-name">Name</label>
                                <input type="text" id="bitcoin-donation-shoutout-name" name="name" placeholder="Anonymous">
                            </div>

                            <!-- Honeypot field -->
                            <input type="text" id="bitcoin-donation-shoutout-email" name="email" style="display: none;" aria-hidden="true">
                            <div class="shoutout-input-label">
                                <label for="bitcoin-donation-shoutout-amount">Amount (in <?php echo esc_html($currency); ?>):</label>
                                <input type="text" id="bitcoin-donation-shoutout-amount" name="amount" step="0.00000001">
                            </div>

                            <div class="shoutout-input-label">
                                <label for="bitcoin-donation-shoutout-satoshi">Satoshi:</label>
                                <input type="text" id="bitcoin-donation-shoutout-satoshi" name="satoshi" min="<?php echo esc_attr($min_amount); ?>">
                            </div>
                        </div>
                        <div class="bitcoin-donation-shoutout-help">
                            <p id="bitcoin-donation-shoutout-help-info">Minimum shoutout amount is <?php echo esc_html($min_amount); ?> sats and Premium shoutout amount <?php echo esc_html($premium_amount); ?> sats</p>
                            <p id="bitcoin-donation-shoutout-help-premium">Selected amount will grant a premium shoutout</p>
                            <p id="bitcoin-donation-shoutout-help-minimum">Selected amount is less than minimum allowed for a shoutout</p>
                        </div>

                        <div class="shoutout-form-right">

                            <label for="bitcoin-donation-shoutout-message">Shoutout Text:</label>
                            <textarea id="bitcoin-donation-shoutout-message" class="bitcoin-donation-shoutout-message" required name="message" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="shoutout-button-container">
                        <button id="bitcoin-donation-shout" type="submit" name="submit_shoutout" onclick="return false;"><?php echo esc_html($button_text); ?></button>
                    </div>
                </form>
            </div>
        </div>
<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shoutouts_Form();
