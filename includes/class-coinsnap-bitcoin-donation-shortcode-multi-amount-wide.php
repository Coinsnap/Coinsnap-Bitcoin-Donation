<?php
if (! defined('ABSPATH')) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Shortcode_Multi_Amount_Wide
{
    public function __construct()
    {
        add_shortcode('multi_amount_donation_wide', [$this, 'coinsnap_bitcoin_donation_multi_render_shortcode_wide']);
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

    function coinsnap_bitcoin_donation_multi_render_shortcode_wide()
    {
        $options = get_option('coinsnap_bitcoin_donation_forms_options');
        $options_general = get_option('coinsnap_bitcoin_donation_options');
        $options = is_array($options) ? $options : [];
        $theme_class = $options_general['theme'] === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme' : 'coinsnap-bitcoin-donation-light-theme';
        $modal_theme = $options_general['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
        $button_text = $options['multi_amount_button_text'] ?? __('Donate', 'coinsnap-bitcoin-donation');
        $title_text = $options['multi_amount_title_text'] ?? __('Donate with Bitcoin', 'coinsnap-bitcoin-donation');
        $snap1 = $options['multi_amount_default_snap1'] ?? '1';
        $snap2 = $options['multi_amount_default_snap2'] ?? '1';
        $snap3 = $options['multi_amount_default_snap3'] ?? '1';
        $active = $options['multi_amount_donation_active'] ?? '1';
        $first_name = $options['multi_amount_first_name'];
        $last_name = $options['multi_amount_last_name'];
        $email = $options['multi_amount_email'];
        $address = $options['multi_amount_address'];
        $custom = $options['multi_amount_custom_field_visibility'];
        $custom_name = $options['multi_amount_custom_field_name'];
        $public_donors = $options['multi_amount_public_donors'];
        $default_currency = $options['multi_amount_currency'];
        
        if (!$active) {
            ob_start();
?>
            <div style="padding: 30px;" class="coinsnap-bitcoin-donation-form <?php echo esc_attr($theme_class); ?> wide-form">
                <div class="coinsnap-bitcoin-donation-title-wrapper"
                    style="display: flex;justify-content: center; flex-direction: column; align-items: center; margin: 0">
                    <h3><?php echo esc_html($title_text); ?></h3>
                </div>
                <h4 style="text-align: center;"><?php esc_html_e('This form is not active', 'coinsnap-bitcoin-donation');?></h4>

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
        <div id="coinsnap-bitcoin-donation-multi-wide" data-name="<?php echo esc_attr($title_text); ?>" class="<?php echo esc_attr($modal_theme);?>">

        
            <div class="coinsnap-bitcoin-donation-multi-wide-wrapper">
                <div class="coinsnap-bitcoin-donation-title-wrapper">
                    <h3><?php echo esc_html($title_text); ?></h3>
                    <select id="coinsnap-bitcoin-donation-swap-multi-wide" class="currency-swapper"><?php
                foreach($coinsnapCurrencies as $coinsnapCurrency){
                    echo '<option value="'.esc_html($coinsnapCurrency).'" date-min="" data-rate="';
                    if(isset($rates['data'][strtolower($coinsnapCurrency)])){
                        echo esc_attr(1/$rates['data'][strtolower($coinsnapCurrency)]['value']*100000000);
                    }
                    echo '"';
                    selected($default_currency, $coinsnapCurrency);
                    echo '>'.esc_html($coinsnapCurrency).'</option>';
                }
                ?>
                    </select>
                </div>

                <input type="hidden" id="coinsnap-bitcoin-donation-email-multi-wide" name="bitcoin-email" aria-hidden="true">

                <div class="coinsnap-bitcoin-donation-wide-up">
                    <div class="mulit-wide-label-left">
                        <label for="coinsnap-bitcoin-donation-amount-multi-wide"><?php echo esc_html__('Amount', 'coinsnap-bitcoin-donation');?></label>
                        <div class="amount-wrapper">
                            <input type="text" id="coinsnap-bitcoin-donation-amount-multi-wide">
                            <div class="secondary-amount">
                                <span id="coinsnap-bitcoin-donation-satoshi-multi-wide"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mulit-wide-label-right">

                        <label for="coinsnap-bitcoin-donation-message-multi-wide"><?php echo esc_html__('Message:', 'coinsnap-bitcoin-donation');?></label>
                        <textarea id="coinsnap-bitcoin-donation-message-multi-wide" class="coinsnap-bitcoin-donation-message" rows="1"></textarea>
                    </div>

                </div>

                <div class="snap-title-container">
                    <h4><?php echo esc_html__('Snap Donations', 'coinsnap-bitcoin-donation');?></h4>

                </div>
                <div class="snap-container">
                    <button id="coinsnap-bitcoin-donation-pay-multi-snap1-wide" class="snap-button">
                        <span id="coinsnap-bitcoin-donation-pay-multi-snap1-primary-wide" class="snap-primary-amount" data-default-value="<?php echo esc_html($snap1);?>">
                            <?php echo esc_html($snap1 . ' '. $default_currency); ?> 
                        </span>
                        <span id="coinsnap-bitcoin-donation-pay-multi-snap1-secondary-wide" class="snap-secondary-amount"></span>
                    </button>
                    <button id="coinsnap-bitcoin-donation-pay-multi-snap2-wide" class="snap-button">
                        <span id="coinsnap-bitcoin-donation-pay-multi-snap2-primary-wide" class="snap-primary-amount" data-default-value="<?php echo esc_html($snap2);?>">
                            <?php echo esc_html($snap2 . ' '. $default_currency); ?>
                        </span>
                        <span id="coinsnap-bitcoin-donation-pay-multi-snap2-secondary-wide" class="snap-secondary-amount"></span>
                    </button>
                    <button id="coinsnap-bitcoin-donation-pay-multi-snap3-wide" class="snap-button">
                        <span id="coinsnap-bitcoin-donation-pay-multi-snap3-primary-wide" class="snap-primary-amount" data-default-value="<?php echo esc_html($snap3);?>">
                            <?php echo esc_html($snap3 . ' '. $default_currency); ?>
                        </span>
                        <span id="coinsnap-bitcoin-donation-pay-multi-snap3-secondary-wide" class="snap-secondary-amount"></span>
                    </button>
                </div>

                <button class="multi-wide-button" id="coinsnap-bitcoin-donation-pay-multi-wide"><?php echo esc_html($button_text); ?></button>
            </div>
            <div id="coinsnap-bitcoin-donation-blur-overlay-multi-wide" class="blur-overlay coinsnap-bitcoin-donation"></div>
            <?php
            $this->get_template('coinsnap-bitcoin-donation-modal', [
                'prefix' => 'coinsnap-bitcoin-donation-',
                'sufix' => '-multi-wide',
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

new Coinsnap_Bitcoin_Donation_Shortcode_Multi_Amount_Wide();
