<?php if (!defined('ABSPATH')){ exit; } ?>
<div id="coinsnap-bitcoin-donation-shoutouts-form-<?php echo esc_attr($form_id); ?>" class="coinsnap-donation-form-instance coinsnap-bitcoin-donation-form <?php echo esc_attr($theme_class); ?>"
    data-form-id="<?php echo esc_attr($form_id); ?>"
    data-form-type="shoutout"
    data-currency="<?php echo esc_attr($default_currency); ?>"
    data-default-amount="<?php echo esc_attr($default_amount); ?>"
    data-default-message="<?php echo esc_attr($default_message); ?>"
    data-redirect-url="<?php echo esc_attr($redirect_url); ?>"
    data-minimum-amount="<?php echo esc_attr($min_amount); ?>"
    data-premium-amount="<?php echo esc_attr($premium_amount); ?>">
    <div class="shoutout-form-wrapper <?php echo esc_attr($modal_theme); ?>">
        <form method="post">
            <?php wp_nonce_field('shoutout_nonce', 'shoutout_nonce'); ?>
            <input type="hidden" name="shoutout_submitted" value="1">

            <div class="coinsnap-bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>
                <select id="coinsnap-bitcoin-donation-shoutout-swap-<?php echo esc_attr($form_id); ?>" class="currency-swapper"><?php
                foreach($coinsnapCurrencies as $coinsnapCurrency){
                    echo '<option value="'.esc_html($coinsnapCurrency).'" data-min="" data-rate="';
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
                        <label for="coinsnap-bitcoin-donation-shoutout-name-<?php echo esc_attr($form_id); ?>"><?php esc_html_e('Name', 'coinsnap-bitcoin-donation');?></label>
                        <input type="text" id="coinsnap-bitcoin-donation-shoutout-name-<?php echo esc_attr($form_id); ?>" name="name" placeholder="Anonymous">
                    </div>

                    <!-- Honeypot field -->
                    <input type="text" id="coinsnap-bitcoin-donation-shoutout-email-<?php echo esc_attr($form_id); ?>" name="email" style="display: none;" aria-hidden="true">
                    <div class="shoutout-input-label">
                        <label for="coinsnap-bitcoin-donation-shoutout-amount-<?php echo esc_attr($form_id); ?>"><?php esc_html_e('Amount', 'coinsnap-bitcoin-donation');?></label>
                        <div class="amount-wrapper">
                            <input type="text" id="coinsnap-bitcoin-donation-shoutout-amount-<?php echo esc_attr($form_id); ?>">
                            <span class="donation-amount-currency" id="coinsnap-bitcoin-donation-shoutout-currency-label-<?php echo esc_attr($form_id); ?>"><?php echo esc_html($default_currency); ?></span>
                            <div class="secondary-amount">
                                <span id="coinsnap-bitcoin-donation-shoutout-satoshi-<?php echo esc_attr($form_id); ?>"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="coinsnap-bitcoin-donation-shoutout-help">
                    <p id="coinsnap-bitcoin-donation-shoutout-help-info-<?php echo esc_attr($form_id); ?>">
                        <?php
                        $rate_value = isset($rates['data'][strtolower($default_currency)]['value']) ? $rates['data'][strtolower($default_currency)]['value'] : 0;
                        esc_html_e('Minimum shoutout amount is', 'coinsnap-bitcoin-donation');?>
                        <span id="coinsnap-bitcoin-donation-shoutout-help-minimum-amount-<?php echo esc_attr($form_id); ?>"><?php if ($rate_value > 0) { echo esc_html(round($min_amount*$rate_value/10000000,2).' '.$default_currency); } ?></span>
                        <?php esc_html_e('and Premium shoutout amount', 'coinsnap-bitcoin-donation');?>
                        <span id="coinsnap-bitcoin-donation-shoutout-help-premium-amount-<?php echo esc_attr($form_id); ?>"><?php if ($rate_value > 0) { echo esc_html(round($premium_amount*$rate_value/10000000,2).' '.$default_currency); } ?></span>
                    </p>
                    <p id="coinsnap-bitcoin-donation-shoutout-help-premium-<?php echo esc_attr($form_id); ?>">
                        <?php esc_html_e('Selected amount will grant a premium shoutout', 'coinsnap-bitcoin-donation');?></p>
                    <p id="coinsnap-bitcoin-donation-shoutout-help-minimum-<?php echo esc_attr($form_id); ?>">
                        <?php esc_html_e('Selected amount is less than minimum allowed for a shoutout', 'coinsnap-bitcoin-donation');?></p>
                </div>

                <div class="shoutout-form-right">
                    <label for="coinsnap-bitcoin-donation-shoutout-message-<?php echo esc_attr($form_id); ?>"><?php esc_html_e('Shoutout Text:', 'coinsnap-bitcoin-donation');?></label>
                    <textarea id="coinsnap-bitcoin-donation-shoutout-message-<?php echo esc_attr($form_id); ?>" class="coinsnap-bitcoin-donation-shoutout-message" required name="message" rows="2"></textarea>
                </div>
            </div>
            <div class="shoutout-button-container">
                <button id="coinsnap-bitcoin-donation-shoutout-pay-<?php echo esc_attr($form_id); ?>" type="submit" name="submit_shoutout" onclick="return false;"><?php echo esc_html($button_text); ?></button>
            </div>
        </form>
    </div>
    <div id="coinsnap-bitcoin-donation-shoutout-blur-overlay-<?php echo esc_attr($form_id); ?>" class="blur-overlay coinsnap-bitcoin-donation"></div>
    <?php
    $prefix = 'coinsnap-bitcoin-donation-shoutout-';
    $sufix = '-' . $form_id;
    include COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'templates/coinsnap-bitcoin-donation-modal.php';
    ?>
</div>
