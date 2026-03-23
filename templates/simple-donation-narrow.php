<?php if (!defined('ABSPATH')){ exit; } ?>
<div class="coinsnap-donation-form-instance coinsnap-bitcoin-donation-form narrow-form <?php echo esc_attr($theme_class); ?>"
    data-form-id="<?php echo esc_attr($form_id); ?>"
    data-form-type="simple_donation"
    data-layout="NARROW"
    data-currency="<?php echo esc_attr($default_currency); ?>"
    data-default-amount="<?php echo esc_attr($default_amount); ?>"
    data-default-message="<?php echo esc_attr($default_message); ?>"
    data-redirect-url="<?php echo esc_attr($redirect_url); ?>">
    <div id="coinsnap-bitcoin-donation-form-<?php echo esc_attr($form_id); ?>" data-name="<?php echo esc_attr($title_text); ?>" class="<?php echo esc_attr($modal_theme); ?>">
        <div class="coinsnap-bitcoin-donation-title-wrapper">
            <h3><?php echo esc_html($title_text); ?></h3>
            <select id="coinsnap-bitcoin-donation-swap-<?php echo esc_attr($form_id); ?>" class="currency-swapper" data-default-currency="<?php echo esc_attr($default_currency); ?>"><?php
            foreach($coinsnapCurrencies as $coinsnapCurrency){
                echo '<option value="'.esc_html($coinsnapCurrency).'" data-rate="';
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

        <input type="hidden" id="coinsnap-bitcoin-donation-email-<?php echo esc_attr($form_id); ?>" name="bitcoin-email" aria-hidden="true">

        <label for="coinsnap-bitcoin-donation-amount-<?php echo esc_attr($form_id); ?>"><?php esc_html_e('Amount', 'coinsnap-bitcoin-donation');?></label>
        <div class="amount-wrapper">
            <input type="text" id="coinsnap-bitcoin-donation-amount-<?php echo esc_attr($form_id); ?>">
            <span class="donation-amount-currency" id="coinsnap-bitcoin-donation-currency-label-<?php echo esc_attr($form_id); ?>"><?php echo esc_html($default_currency); ?></span>
            <div class="secondary-amount">
                <span id="coinsnap-bitcoin-donation-satoshi-<?php echo esc_attr($form_id); ?>"></span>
            </div>
        </div>

        <label for="coinsnap-bitcoin-donation-message-<?php echo esc_attr($form_id); ?>"><?php esc_html_e('Message:', 'coinsnap-bitcoin-donation');?></label>
        <textarea id="coinsnap-bitcoin-donation-message-<?php echo esc_attr($form_id); ?>" class="coinsnap-bitcoin-donation-message" rows="2"></textarea>
        <button id="coinsnap-bitcoin-donation-pay-<?php echo esc_attr($form_id); ?>"><?php echo esc_html($button_text); ?></button>
        <div id="coinsnap-bitcoin-donation-blur-overlay-<?php echo esc_attr($form_id); ?>" class="blur-overlay coinsnap-bitcoin-donation"></div>
        <?php
        $prefix = 'coinsnap-bitcoin-donation-';
        $sufix = '-' . $form_id;
        include COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'templates/coinsnap-bitcoin-donation-modal.php';
        ?>
    </div>
</div>
