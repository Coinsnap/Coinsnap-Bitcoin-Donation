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

    function coinsnap_bitcoin_donation_render_shortcode_wide()
    {
        $options = get_option('coinsnap_bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $options_general = get_option('coinsnap_bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme' : 'coinsnap-bitcoin-donation-light-theme';
        $modal_theme = $options_general['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
        $button_text = $options['button_text'] ?? __('Donate', 'coinsnap-bitcoin-donation');
        $title_text = $options['title_text'] ?? __('Donate with Bitcoin', 'coinsnap-bitcoin-donation');
        $active = $options['simple_donation_active'] ?? '1';
        $first_name = $options['simple_donation_first_name'];
        $last_name = $options['simple_donation_last_name'];
        $email = $options['simple_donation_email'];
        $address = $options['simple_donation_address'];
        $custom = $options['simple_donation_custom_field_visibility'];
        $custom_name = $options['simple_donation_custom_field_name'];
        $public_donors = $options['simple_donation_public_donors'];
        if (!$active) {
            ob_start();
?>
            <div style="padding: 30px;" class="coinsnap-bitcoin-donation-form <?php echo esc_attr($theme_class); ?> wide-form">
                <div class="coinsnap-bitcoin-donation-title-wrapper"
                    style="display: flex;justify-content: center; flex-direction: column; align-items: center; margin: 0">
                    <h3><?php echo esc_html($title_text); ?></h3>
                </div>
                <h4 style="text-align: center;"><?php __('This form is not active', 'coinsnap-bitcoin-donation');?></h4>

            </div>
        <?php
            return ob_get_clean();
        }

        ob_start();
        $client = new Coinsnap_Bitcoin_Donation_Client();
        $coinsnapCurrencies = $client->getCurrencies();
        $rates = $client->loadExchangeRates();
        ?>
        <div class="coinsnap-bitcoin-donation-form wide-form <?php echo esc_attr($theme_class);?>">
            <div id="coinsnap-bitcoin-donation-form-wide" data-name="<?php echo esc_attr($title_text); ?>" class="<?php echo esc_attr($modal_theme);?>">
                <div class="coinsnap-bitcoin-donation-title-wrapper">
                    <h3><?php echo esc_html($title_text); ?></h3>
                    <select style="max-width: 172px;" id="coinsnap-bitcoin-donation-swap-wide" class="currency-swapper"><?php
                    foreach($coinsnapCurrencies as $coinsnapCurrency){
                        echo '<option value="'.esc_html($coinsnapCurrency).'" date-min="" data-rate="';
                        if(isset($rates['data'][strtolower($coinsnapCurrency)])){
                            echo esc_attr(1/$rates['data'][strtolower($coinsnapCurrency)]['value']*100000000);
                        }
                        echo '"';
                        //selected($default_currency, $coinsnapCurrencies[$j]);
                        echo '>'.esc_html($coinsnapCurrency).'</option>';
                    }
                    ?>
                    </select>
                </div>
                <input type="hidden" id="coinsnap-bitcoin-donation-email-wide" name="bitcoin-email" aria-hidden="true">
                <div class="coinsnap-bitcoin-donation-wide-field-wrapper">
                    <div class="coinsnap-bitcoin-donation-wide-up">

                        <div class="shoutout-input-label">
                            <label for="coinsnap-bitcoin-donation-amount-wide"><?php esc_html_e('Amount', 'coinsnap-bitcoin-donation');?></label>
                            <div class="amount-wrapper">
                                <input type="text" id="coinsnap-bitcoin-donation-amount-wide">
                                <div class="secondary-amount">
                                    <span id="coinsnap-bitcoin-donation-satoshi-wide"></span>
                                </div>
                            </div>
                        </div>

                        <button class="wide-form-button" id="coinsnap-bitcoin-donation-pay-wide"><?php echo esc_html($button_text); ?></button>
                    </div>
                    <div class="coinsnap-bitcoin-donation-wide-down">
                        <label for="coinsnap-bitcoin-donation-message-wide"><?php esc_html_e('Message:', 'coinsnap-bitcoin-donation');?></label>
                        <textarea id="coinsnap-bitcoin-donation-message-wide" class="coinsnap-bitcoin-donation-message wide-message-text-area" required name="message" rows="2"></textarea>
                    </div>
                </div>
                <div id="coinsnap-bitcoin-donation-blur-overlay-wide" class="blur-overlay coinsnap-bitcoin-donation"></div>

                <?php
                $this->get_template('coinsnap-bitcoin-donation-modal', [
                    'prefix' => 'coinsnap-bitcoin-donation-',
                    'sufix' => '-wide',
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'address' => $address,
                    'public_donors' => $public_donors,
                    'custom' => $custom,
                    'custom_name' => $custom_name,
                ]);
                ?>

            </div>
        </div>
<?php

        return ob_get_clean();
    }
}

new Coinsnap_Bitcoin_Donation_Shortcode_Wide();
