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

    function bitcoin_donation_render_shortcode()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $options_general = get_option('bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $modal_theme = $options_general['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
        $button_text = $options['shoutout_button_text'] ?? 'Shoutout';
        $title_text = $options['shoutout_title_text'] ?? 'Bitcoin Shoutouts';
        $min_amount = (int)$options['shoutout_minimum_amount'] ?? 21;
        $premium_amount = (int)$options['shoutout_premium_amount'] ?? 21000;
        $active = $options['shoutout_donation_active'] ?? '1';
        $first_name = $options['shoutout_donation_first_name'];
        $last_name = $options['shoutout_donation_last_name'];
        $email = $options['shoutout_donation_email'];
        $address = $options['shoutout_donation_address'];
        $message = $options['shoutout_donation_message'];
        $custom = $options['shoutout_donation_custom_field_visibility'];
        $custom_name = $options['shoutout_donation_custom_field_name'];
        $public_donors = $options['shoutout_public_donors'];

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
            <div class="shoutout-form-wrapper <?php echo esc_attr($theme_class);
                                                echo " " . esc_attr($modal_theme) ?>">
                <form method="post">
                    <?php wp_nonce_field('shoutout_nonce', 'shoutout_nonce'); ?>
                    <input type="hidden" name="shoutout_submitted" value="1">

                    <div class="bitcoin-donation-title-wrapper">
                        <h3><?php echo esc_html($title_text); ?></h3>
                        <select id="bitcoin-donation-shoutout-swap" class="currency-swapper">
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                            <option value="CAD">CAD</option>
                            <option value="JPY">JPY</option>
                            <option value="GBP">GBP</option>
                            <option value="sats">SATS</option>
                            <option value="CHF">CHF</option>
                        </select>
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
                                <label for="bitcoin-donation-shoutout-amount">Amount</label>
                                <div class="amount-wrapper">
                                    <input type="text" id="bitcoin-donation-shoutout-amount">
                                    <div class="secondary-amount">
                                        <span id="bitcoin-donation-shoutout-satoshi"></span>
                                    </div>
                                </div>
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
                        <button id="bitcoin-donation-shoutout-pay" type="submit" name="submit_shoutout" onclick="return false;"><?php echo esc_html($button_text); ?></button>
                    </div>
                </form>
            </div>
            <div id="bitcoin-donation-shoutout-blur-overlay" class="blur-overlay"></div>
            <?php
            $this->get_template('bitcoin-donation-modal', [
                'prefix' => 'bitcoin-donation-shoutout-',
                'sufix' => '',
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'address' => $address,
                'message' => $message,
                'public_donors' => $public_donors,
                'custom' => $custom,
                'custom_name' => $custom_name,
            ]);
            ?>
        </div>
<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shoutouts_Form();
