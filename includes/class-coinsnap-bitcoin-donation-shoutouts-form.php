<?php
if (! defined('ABSPATH')) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Shoutouts_Form
{
    public function __construct()
    {
        add_shortcode('shoutout_form', [$this, 'coinsnap_bitcoin_donation_render_shortcode']);
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

    function coinsnap_bitcoin_donation_render_shortcode()
    {
        $options = get_option('coinsnap_bitcoin_donation_forms_options');
        $options = is_array($options) ? $options : [];
        $options_general = get_option('coinsnap_bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme' : 'coinsnap-bitcoin-donation-light-theme';
        $modal_theme = $options_general['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
        $button_text = $options['shoutout_button_text'] ?? __('Shoutout', 'coinsnap-bitcoin-donation');
        $title_text = $options['shoutout_title_text'] ?? __('Bitcoin Shoutouts', 'coinsnap-bitcoin-donation');
        $min_amount = (float)$options['shoutout_minimum_amount'] ?? 20;
        $premium_amount = (float)$options['shoutout_premium_amount'] ?? 2000;
        $active = $options['shoutout_donation_active'] ?? '1';
        $first_name = $options['shoutout_first_name'];
        $last_name = $options['shoutout_last_name'];
        $email = $options['shoutout_email'];
        $address = $options['shoutout_address'];
        $custom = $options['shoutout_custom_field_visibility'];
        $custom_name = $options['shoutout_custom_field_name'];
        $public_donors = $options['shoutout_public_donors'];
        $default_currency = $options['shoutout_currency'];
        if (!$active) {
            ob_start();
?>
            <div class="coinsnap-bitcoin-donation-form <?php echo esc_attr($theme_class); ?>">
                <div class="shoutout-form-wrapper"
                    style="display: flex;justify-content: center; flex-direction: column; align-items: center; margin: 0">
                    <h3><?php echo esc_html($title_text); ?></h3>
                    <h4 style="text-align: center;"><?php esc_html_e('This form is not active', 'coinsnap-bitcoin-donation');?></h4>
                </div>

            </div>
        <?php
            return ob_get_clean();
        }

        ob_start();
        $client = new Coinsnap_Bitcoin_Donation_Client();
        $coinsnapCurrencies = $client->getCurrencies();
        $rates = $client->loadExchangeRates();
        ?>
        <div id="coinsnap-bitcoin-donation-shoutouts-form" class="coinsnap-bitcoin-donation-form <?php echo esc_attr($theme_class);?>">
            <div class="shoutout-form-wrapper <?php echo esc_attr($modal_theme) ?>">
                <form method="post">
                    <?php wp_nonce_field('shoutout_nonce', 'shoutout_nonce'); ?>
                    <input type="hidden" name="shoutout_submitted" value="1">

                    <div class="coinsnap-bitcoin-donation-title-wrapper">
                        <h3><?php echo esc_html($title_text); ?></h3>
                        <select id="coinsnap-bitcoin-donation-shoutout-swap" class="currency-swapper"><?php
                        foreach($coinsnapCurrencies as $coinsnapCurrency){
                            echo '<option value="'.esc_html($coinsnapCurrency).'" date-min="" data-rate="';
                            if(isset($rates['data'][strtolower($coinsnapCurrency)])){
                                echo esc_attr(1/$rates['data'][strtolower($coinsnapCurrency)]['value']*100000000);
                            }
                            echo '"';
                            selected($default_currency, $coinsnapCurrency);
                            echo '>'.esc_html($coinsnapCurrency). '</option>';
                        }
                        ?>
                        </select>
                    </div>
                    <div class="shoutout-form-container">
                        <div class="shoutout-form-left">
                            <div class="shoutout-input-label">
                                <label for="coinsnap-bitcoin-donation-shoutout-name"><?php esc_html_e('Name', 'coinsnap-bitcoin-donation');?></label>
                                <input type="text" id="coinsnap-bitcoin-donation-shoutout-name" name="name" placeholder="Anonymous">
                            </div>

                            <!-- Honeypot field -->
                            <input type="text" id="coinsnap-bitcoin-donation-shoutout-email" name="email" style="display: none;" aria-hidden="true">
                            <div class="shoutout-input-label">
                                <label for="coinsnap-bitcoin-donation-shoutout-amount"><?php esc_html_e('Amount', 'coinsnap-bitcoin-donation');?></label>
                                <div class="amount-wrapper">
                                    <input type="text" id="coinsnap-bitcoin-donation-shoutout-amount">
                                    <div class="secondary-amount">
                                        <span id="coinsnap-bitcoin-donation-shoutout-satoshi"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="coinsnap-bitcoin-donation-shoutout-help">
                            <p id="coinsnap-bitcoin-donation-shoutout-help-info">
                                <?php esc_html_e('Minimum shoutout amount is', 'coinsnap-bitcoin-donation');?>
                                <span id="coinsnap-bitcoin-donation-shoutout-help-minimum-amount"><?php echo esc_html(round($min_amount*$rates['data'][strtolower($default_currency)]['value']/10000000,2).' '.$default_currency); ?></span>
                                <?php esc_html_e('and Premium shoutout amount', 'coinsnap-bitcoin-donation');?>
                                <span id="coinsnap-bitcoin-donation-shoutout-help-premium-amount"><?php echo esc_html(round($premium_amount*$rates['data'][strtolower($default_currency)]['value']/10000000,2).' '.$default_currency); ?></span>
                            </p>
                            <p id="coinsnap-bitcoin-donation-shoutout-help-premium">
                                <?php esc_html_e('Selected amount will grant a premium shoutout', 'coinsnap-bitcoin-donation');?></p>
                            <p id="coinsnap-bitcoin-donation-shoutout-help-minimum">
                                <?php esc_html_e('Selected amount is less than minimum allowed for a shoutout', 'coinsnap-bitcoin-donation');?></p>
                        </div>

                        <div class="shoutout-form-right">
                            <label for="coinsnap-bitcoin-donation-shoutout-message"><?php esc_html_e('Shoutout Text:', 'coinsnap-bitcoin-donation');?></label>
                            <textarea id="coinsnap-bitcoin-donation-shoutout-message" class="coinsnap-bitcoin-donation-shoutout-message" required name="message" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="shoutout-button-container">
                        <button id="coinsnap-bitcoin-donation-shoutout-pay" type="submit" name="submit_shoutout" onclick="return false;"><?php echo esc_html($button_text); ?></button>
                    </div>
                </form>
            </div>
            <div id="coinsnap-bitcoin-donation-shoutout-blur-overlay" class="blur-overlay coinsnap-bitcoin-donation"></div>
            <?php
            $this->get_template('coinsnap-bitcoin-donation-modal', [
                'prefix' => 'coinsnap-bitcoin-donation-shoutout-',
                'sufix' => '',
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
<?php

        return ob_get_clean();
    }
}

new Coinsnap_Bitcoin_Donation_Shoutouts_Form();
