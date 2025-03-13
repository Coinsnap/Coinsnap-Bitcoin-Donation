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

    function bitcoin_donation_multi_render_shortcode_wide()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $options_general = get_option('bitcoin_donation_options');
        $options = is_array($options) ? $options : [];
        $theme_class = $options_general['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $modal_theme = $options_general['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
        $button_text = $options['multi_amount_button_text'] ?? 'Donate';
        $title_text = $options['multi_amount_title_text'] ?? 'Donate with Bitcoin';
        $snap1 = $options['multi_amount_default_snap1'] ?? '1';
        $snap2 = $options['multi_amount_default_snap2'] ?? '1';
        $snap3 = $options['multi_amount_default_snap3'] ?? '1';
        $active = $options['multi_amount_donation_active'] ?? '1';
        $first_name = $options['multi_amount_first_name'];
        $last_name = $options['multi_amount_last_name'];
        $email = $options['multi_amount_email'];
        $address = $options['multi_amount_address'];
        $message = $options['multi_amount_message'];
        $public_donors = $options['multi_amount_public_donors'];
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
        <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class);
                                                    echo " " . esc_attr($modal_theme); ?> wide-form">
            <div class="bitcoin-donation-multi-wide-wrapper">
                <div class="bitcoin-donation-title-wrapper">
                    <h3><?php echo esc_html($title_text); ?></h3>
                    <select id="bitcoin-donation-swap-multi-wide" class="currency-swapper">
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                        <option value="CAD">CAD</option>
                        <option value="JPY">JPY</option>
                        <option value="GBP">GBP</option>
                        <option value="sats">SATS</option>
                        <option value="CHF">CHF</option>
                    </select>
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

                <button class="multi-wide-button" id="bitcoin-donation-pay-multi-wide"><?php echo esc_html($button_text); ?></button>
            </div>
            <div id="bitcoin-donation-blur-overlay-multi-wide" class="blur-overlay"></div>
            <?php
            $this->get_template('bitcoin-donation-modal', [
                'prefix' => 'bitcoin-donation-',
                'sufix' => '-multi-wide',
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'address' => $address,
                'message' => $message,
                'public_donors' => $public_donors,
            ]);
            ?>
        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode_Multi_Amount_Wide();
