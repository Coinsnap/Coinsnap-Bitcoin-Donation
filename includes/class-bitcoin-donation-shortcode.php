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
        $button_text = $options['button_text'] ?? 'Donate';
        $title_text = $options['title_text'] ?? 'Donate with Bitcoin';
        $first_name = $options['simple_donation_first_name'];
        $last_name = $options['simple_donation_last_name'];
        $email = $options['simple_donation_email'];
        $address = $options['simple_donation_address'];
        $message = $options['simple_donation_message'];
        $public_donors = $options['simple_donation_public_donors'];
        $custom = $options['simple_donation_custom_field_visibility'];
        $custom_name = $options['simple_donation_custom_field_name'];
        $active = $options['simple_donation_active'] ?? '1';
        if (!$active) {
            ob_start();
?>
            <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?> narrow-form">
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
                                                    echo " " . esc_attr($modal_theme) ?> narrow-form">
            <div class="bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>
                <select id="bitcoin-donation-swap" class="currency-swapper">
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                    <option value="JPY">JPY</option>
                    <option value="GBP">GBP</option>
                    <option value="sats">SATS</option>
                    <option value="CHF">CHF</option>
                </select>
            </div>

            <input type="text" id="bitcoin-donation-email" name="bitcoin-email" style="display: none;" aria-hidden="true">

            <label for="bitcoin-donation-amount">Amount</label>
            <div class="amount-wrapper">
                <input type="text" id="bitcoin-donation-amount">
                <div class="secondary-amount">
                    <span id="bitcoin-donation-satoshi"></span>
                </div>
            </div>

            <label for="bitcoin-donation-message">Message:</label>
            <textarea id="bitcoin-donation-message" class="bitcoin-donation-message" rows="2"></textarea>
            <button id="bitcoin-donation-pay"><?php echo esc_html($button_text); ?></button>
            <div id="bitcoin-donation-blur-overlay" class="blur-overlay"></div>
            <?php
            $this->get_template('bitcoin-donation-modal', [
                'prefix' => 'bitcoin-donation-',
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

new Bitcoin_Donation_Shortcode();
