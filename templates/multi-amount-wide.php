<?php if (!defined('ABSPATH')){ exit; } ?>
<div class="coinsnap-donation-form-instance coinsnap-bitcoin-donation-form wide-form <?php echo esc_attr($theme_class); ?>"
    data-form-id="<?php echo esc_attr($form_id); ?>"
    data-form-type="multi_amount"
    data-layout="WIDE"
    data-currency="<?php echo esc_attr($default_currency); ?>"
    data-default-amount="<?php echo esc_attr($default_amount); ?>"
    data-default-message="<?php echo esc_attr($default_message); ?>"
    data-redirect-url="<?php echo esc_attr($redirect_url); ?>"
    data-snap1="<?php echo esc_attr($snap1); ?>"
    data-snap2="<?php echo esc_attr($snap2); ?>"
    data-snap3="<?php echo esc_attr($snap3); ?>">
    <div id="coinsnap-bitcoin-donation-multi-wide-<?php echo esc_attr($form_id); ?>" data-name="<?php echo esc_attr($title_text); ?>" class="<?php echo esc_attr($modal_theme); ?>">


        <div class="coinsnap-bitcoin-donation-multi-wide-wrapper">
            <div class="coinsnap-bitcoin-donation-title-wrapper">
                <h3><?php echo esc_html($title_text); ?></h3>
                <select id="coinsnap-bitcoin-donation-swap-multi-wide-<?php echo esc_attr($form_id); ?>" class="currency-swapper"><?php
            foreach($coinsnapCurrencies as $coinsnapCurrency){
                echo '<option value="'.esc_html($coinsnapCurrency).'" data-min="" data-rate="';
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

            <input type="hidden" id="coinsnap-bitcoin-donation-email-multi-wide-<?php echo esc_attr($form_id); ?>" name="bitcoin-email" aria-hidden="true">

            <div class="coinsnap-bitcoin-donation-wide-up">
                <div class="mulit-wide-label-left">
                    <label for="coinsnap-bitcoin-donation-amount-multi-wide-<?php echo esc_attr($form_id); ?>"><?php echo esc_html__('Amount', 'coinsnap-bitcoin-donation');?></label>
                    <div class="amount-wrapper">
                        <input type="text" id="coinsnap-bitcoin-donation-amount-multi-wide-<?php echo esc_attr($form_id); ?>">
                        <span class="donation-amount-currency" id="coinsnap-bitcoin-donation-currency-label-multi-wide-<?php echo esc_attr($form_id); ?>"><?php echo esc_html($default_currency); ?></span>
                        <div class="secondary-amount">
                            <span id="coinsnap-bitcoin-donation-satoshi-multi-wide-<?php echo esc_attr($form_id); ?>"></span>
                        </div>
                    </div>
                </div>
                <div class="mulit-wide-label-right">

                    <label for="coinsnap-bitcoin-donation-message-multi-wide-<?php echo esc_attr($form_id); ?>"><?php echo esc_html__('Message:', 'coinsnap-bitcoin-donation');?></label>
                    <textarea id="coinsnap-bitcoin-donation-message-multi-wide-<?php echo esc_attr($form_id); ?>" class="coinsnap-bitcoin-donation-message" rows="1"></textarea>
                </div>

            </div>

            <div class="snap-title-container">
                <h4><?php echo esc_html__('Snap Donations', 'coinsnap-bitcoin-donation');?></h4>

            </div>
            <div class="snap-container">
                <button id="coinsnap-bitcoin-donation-pay-multi-snap1-wide-<?php echo esc_attr($form_id); ?>" class="snap-button">
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap1-primary-wide-<?php echo esc_attr($form_id); ?>" class="snap-primary-amount" data-default-value="<?php echo esc_html($snap1);?>">
                        <?php echo esc_html($snap1 . ' '. $default_currency); ?>
                    </span>
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap1-secondary-wide-<?php echo esc_attr($form_id); ?>" class="snap-secondary-amount"></span>
                </button>
                <button id="coinsnap-bitcoin-donation-pay-multi-snap2-wide-<?php echo esc_attr($form_id); ?>" class="snap-button">
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap2-primary-wide-<?php echo esc_attr($form_id); ?>" class="snap-primary-amount" data-default-value="<?php echo esc_html($snap2);?>">
                        <?php echo esc_html($snap2 . ' '. $default_currency); ?>
                    </span>
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap2-secondary-wide-<?php echo esc_attr($form_id); ?>" class="snap-secondary-amount"></span>
                </button>
                <button id="coinsnap-bitcoin-donation-pay-multi-snap3-wide-<?php echo esc_attr($form_id); ?>" class="snap-button">
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap3-primary-wide-<?php echo esc_attr($form_id); ?>" class="snap-primary-amount" data-default-value="<?php echo esc_html($snap3);?>">
                        <?php echo esc_html($snap3 . ' '. $default_currency); ?>
                    </span>
                    <span id="coinsnap-bitcoin-donation-pay-multi-snap3-secondary-wide-<?php echo esc_attr($form_id); ?>" class="snap-secondary-amount"></span>
                </button>
            </div>

            <button class="multi-wide-button" id="coinsnap-bitcoin-donation-pay-multi-wide-<?php echo esc_attr($form_id); ?>"><?php echo esc_html($button_text); ?></button>
        </div>
        <div id="coinsnap-bitcoin-donation-blur-overlay-multi-wide-<?php echo esc_attr($form_id); ?>" class="blur-overlay coinsnap-bitcoin-donation"></div>
        <?php
        $prefix = 'coinsnap-bitcoin-donation-';
        $sufix = '-' . $form_id;
        include COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'templates/coinsnap-bitcoin-donation-modal.php';
        ?>
    </div>
</div>
