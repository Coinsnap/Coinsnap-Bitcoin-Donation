<?php if (!defined('ABSPATH')){ exit; } ?>

<div id="<?php echo esc_attr($prefix); ?>qr-container<?php echo esc_attr($sufix); ?>" class="qr-container coinsnap-bitcoin-donation" data-public-donors="<?php echo esc_attr($public_donors); ?>">
    <div class="close-popup">&times;</div>
    <div id="<?php echo esc_attr($prefix); ?>public-donor-popup<?php echo esc_attr($sufix); ?>" class="public-donor-popup">
        <form class="public-donor-form">
            <?php if ($first_name !== 'hidden' || $last_name !== 'hidden' || $email !== 'hidden'): ?>
            <label><?php esc_html_e('Donor Information', 'coinsnap-bitcoin-donation'); ?></label>
            <div class="person-grid">
                <?php if ($first_name !== 'hidden'): ?>
                <div class="person-cell">
                    <input <?php echo $first_name === 'mandatory' ? 'required="required"' : ''; ?> type="text"
                        id="<?php echo esc_attr($prefix); ?>first-name<?php echo esc_attr($sufix); ?>"
                        placeholder="<?php esc_html_e('First Name', 'coinsnap-bitcoin-donation'); echo ($first_name === 'mandatory') ? '*' : ''; ?>">
                </div>
                <?php endif; ?>
                <?php if ($last_name !== 'hidden'): ?>
                <div class="person-cell">
                    <input <?php echo $last_name === 'mandatory' ? 'required' : ''; ?> type="text"
                        id="<?php echo esc_attr($prefix); ?>last-name<?php echo esc_attr($sufix); ?>"
                        placeholder="<?php esc_html_e('Last Name', 'coinsnap-bitcoin-donation'); echo ($last_name === 'mandatory') ? '*' : ''; ?>">
                </div>
                <?php endif; ?>
                <?php if ($email !== 'hidden'): ?>
                <div class="person-cell">
                    <input <?php echo $email === 'mandatory' ? 'required' : ''; ?> type="email"
                        id="<?php echo esc_attr($prefix); ?>donor-email<?php echo esc_attr($sufix); ?>"
                        placeholder="<?php esc_html_e('Email', 'coinsnap-bitcoin-donation'); echo ($email === 'mandatory') ? '*' : ''; ?>">
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($address !== 'hidden'): ?>
            <label><?php esc_html_e('Address', 'coinsnap-bitcoin-donation'); ?></label>
            <div class="address-grid">
                <div class="address-row">
                    <div class="address-cell half">
                        <input <?php echo $address === 'mandatory' ? 'required' : ''; ?> type="text"
                            id="<?php echo esc_attr($prefix); ?>street<?php echo esc_attr($sufix); ?>"
                            placeholder="<?php esc_html_e('Street', 'coinsnap-bitcoin-donation'); echo ($address === 'mandatory') ? '*' : ''; ?>" />
                    </div>
                    <div class="address-cell quart">
                        <input <?php echo $address === 'mandatory' ? 'required' : ''; ?> type="text"
                            id="<?php echo esc_attr($prefix); ?>house-number<?php echo esc_attr($sufix); ?>"
                            placeholder="<?php esc_html_e('No.', 'coinsnap-bitcoin-donation'); echo ($address === 'mandatory') ? '*' : ''; ?>" />
                    </div>
                    <div class="address-cell quart">
                        <input <?php echo $address === 'mandatory' ? 'required' : ''; ?> type="text"
                            id="<?php echo esc_attr($prefix); ?>postal<?php echo esc_attr($sufix); ?>"
                            placeholder="<?php esc_html_e('ZIP', 'coinsnap-bitcoin-donation'); echo ($address === 'mandatory') ? '*' : ''; ?>" />
                    </div>
                </div>
                <div class="address-row">
                    <div class="address-cell half">
                        <input <?php echo $address === 'mandatory' ? 'required' : ''; ?> type="text"
                            id="<?php echo esc_attr($prefix); ?>town<?php echo esc_attr($sufix); ?>"
                            placeholder="<?php esc_html_e('Town', 'coinsnap-bitcoin-donation'); echo ($address === 'mandatory') ? '*' : ''; ?>" />
                    </div>
                    <div class="address-cell half">
                        <input <?php echo $address === 'mandatory' ? 'required' : ''; ?> type="text"
                            id="<?php echo esc_attr($prefix); ?>country<?php echo esc_attr($sufix); ?>"
                            placeholder="<?php esc_html_e('Country', 'coinsnap-bitcoin-donation'); echo ($address === 'mandatory') ? '*' : ''; ?>" />
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($custom !== 'hidden'): ?>
            <label id="<?php echo esc_attr($prefix); ?>custom-name<?php echo esc_attr($sufix); ?>" for="<?php echo esc_attr($prefix); ?>custom<?php echo esc_attr($sufix); ?>"><?php echo esc_html($custom_name); ?></label>
            <input <?php echo $custom === 'mandatory' ? 'required' : ''; ?> type="text"
                id="<?php echo esc_attr($prefix); ?>custom<?php echo esc_attr($sufix); ?>"
                placeholder="<?php echo esc_html($custom_name); echo $custom === 'mandatory' ? '*' : ''; ?>" />
            <?php endif; ?>
            <button type="submit" id="<?php echo esc_attr($prefix); ?>public-donors-pay<?php echo esc_attr($sufix); ?>"><?php esc_html_e('Pay with Bitcoin', 'coinsnap-bitcoin-donation'); ?></button>
        </form>
    </div>
    <div id="<?php echo esc_attr($prefix); ?>payment-loading<?php echo esc_attr($sufix); ?>" class="payment-loading">
        <div class="loader qr-spinner"></div>
    </div>
    <div id="<?php echo esc_attr($prefix); ?>thank-you-popup<?php echo esc_attr($sufix); ?>" class="thank-you-popup">
        <img class="checkmark-img" alt="Checkmark" src="<?php echo esc_url(COINSNAP_BITCOIN_DONATION_PLUGIN_DIR . 'assets/images/checkmark.svg'); ?>">
        <h3 style="margin: 10px 0 0 0;"><?php esc_html_e('Your payment was successful.', 'coinsnap-bitcoin-donation'); ?></h3>
    </div>
</div>
