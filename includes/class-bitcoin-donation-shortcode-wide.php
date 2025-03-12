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

    function bitcoin_donation_render_shortcode_wide()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $options_general = get_option('bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $currency = $options['currency'] ?? 'USD';
        $button_text = $options['button_text'] ?? 'Donate';
        $title_text = $options['title_text'] ?? 'Donate with Bitcoin';
        $active = $options['simple_donation_active'] ?? '1';
        $first_name = $options['simple_donation_first_name'];
        $last_name = $options['simple_donation_last_name'];
        $email = $options['simple_donation_email'];
        $address = $options['simple_donation_address'];
        $message = $options['simple_donation_message'];
        $public_donors = $options['simple_donation_public_donors'];
        if (!$active) {
            ob_start();
?>
            <div style="padding: 30px;" class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> wide-form">
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
        <div id="bitcoin-donation-form-wide" class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> wide-form">
            <div class="bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>
                <select style="max-width: 172px;" id="bitcoin-donation-swap-wide" class="currency-swapper">
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                    <option value="JPY">JPY</option>
                    <option value="GBP">GBP</option>
                    <option value="sats">SATS</option>
                    <option value="CHF">CHF</option>
                </select>
            </div>
            <input type="text" id="bitcoin-donation-email-wide" name="bitcoin-email" style="display: none;" aria-hidden="true">
            <div class="bitcoin-donation-wide-field-wrapper">
                <div class="bitcoin-donation-wide-up">

                    <div class="shoutout-input-label">
                        <label for="bitcoin-donation-amount-wide">Amount (in <?php echo esc_html($currency); ?>)</label>
                        <div class="amount-wrapper">
                            <input type="text" id="bitcoin-donation-amount-wide">
                            <div class="secondary-amount">
                                <span id="bitcoin-donation-satoshi-wide"></span>
                            </div>
                        </div>
                    </div>

                    <button class="wide-form-button" id="bitcoin-donation-pay-wide"><?php echo esc_html($button_text); ?></button>
                </div>
                <div class="bitcoin-donation-wide-down">
                    <label for="bitcoin-donation-message-wide">Message</label>
                    <textarea id="bitcoin-donation-message-wide" class="bitcoin-donation-message wide-message-text-area" required name="message" rows="2"></textarea>
                </div>
            </div>
            <div id="bitcoin-donation-blur-overlay-wide" class="blur-overlay"></div>

            <?php
            $this->get_template('bitcoin-donation-modal', [
                'prefix' => 'bitcoin-donation-',
                'sufix' => '-wide',
                'first_name' => $first_name == 'mandatory' ? true : false,
                'last_name' => $last_name == 'mandatory' ? true : false,
                'email' => $email == 'mandatory' ? true : false,
                'address' => $address == 'mandatory' ? true : false,
                'message' => $message == 'mandatory' ? true : false,
                'public_donors' => $public_donors,
            ]);
            ?>

        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode_Wide();
