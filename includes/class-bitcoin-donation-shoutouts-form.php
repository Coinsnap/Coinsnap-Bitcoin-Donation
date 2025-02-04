<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Shoutouts_Form
{
    public function __construct()
    {
        // add_action('init', [$this, 'handle_form_submission']);
        add_shortcode('shoutout_form', [$this, 'bitcoin_donation_render_shortcode']);
    }

    // function handle_form_submission()
    // {
    //     if (isset($_POST['shoutout_submitted']) && $_POST['shoutout_submitted'] === '1') {
    //         // Verify nonce
    //         if (!wp_verify_nonce($_POST['shoutout_nonce'], 'shoutout_nonce')) {
    //             wp_die('Security check failed');
    //         }

    //         // Check honeypot field
    //         if (!empty($_POST['email'])) {
    //             wp_die('Bot detected');
    //         }

    //         // Sanitize input data
    //         $name = sanitize_text_field($_POST['name'] ?? '');
    //         $satoshi = intval($_POST['satoshi'] ?? 0);
    //         $message = sanitize_textarea_field($_POST['message'] ?? '');
    //         $invoice_id = 'asdasd'; // TODO
    //         // Create post
    //         $post_id = wp_insert_post([
    //             'post_title' => $name ?: esc_html__('Anonymous', 'bitcoin-donation-shoutouts'),
    //             'post_content' => $message,
    //             'post_type' => 'bitcoin-shoutouts',
    //             'post_status' => 'pending',
    //             'post_date' => current_time('mysql')
    //         ]);

    //         if ($post_id && !is_wp_error($post_id)) {
    //             // Save meta data using your existing meta keys
    //             update_post_meta($post_id, '_bitcoin_donation_shoutouts_name', $name);
    //             update_post_meta($post_id, '_bitcoin_donation_shoutouts_invoice_id', $invoice_id);
    //             update_post_meta($post_id, '_bitcoin_donation_shoutouts_amount', $satoshi);
    //             update_post_meta($post_id, '_bitcoin_donation_shoutouts_message', $message);
    //             // update_post_meta($post_id, '_bitcoin_donation_shoutouts_status', 'pending');
    //         }

    //         // if ($post_id && !is_wp_error($post_id)) {
    //         //     // Save meta data
    //         //     update_post_meta($post_id, 'amount', $data['amount']);
    //         //     update_post_meta($post_id, 'satoshi', $data['satoshi']);
    //         //     update_post_meta($post_id, 'status', 'pending');

    //         //     // Redirect to avoid resubmission
    //         //     wp_redirect(add_query_arg('shoutout_success', '1', wp_get_referer()));
    //         //     exit;
    //         // } else {
    //         //     wp_die('Error creating shoutout');
    //         // }
    //     }
    // }


    function bitcoin_donation_render_shortcode()
    {
        $options = get_option('bitcoin_donation_options');
        $options = is_array($options) ? $options : [];
        $theme_class = isset($options['shoutout_theme']) && $options['shoutout_theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $currency = $options['shoutout_currency'] ?? 'USD';
        $butoon_text = $options['shoutout_button_text'] ?? 'Shoutout';
        $title_text = $options['shoutout_title_text'] ?? 'Bitcoin Shoutouts';
        $min_amount = (int)$options['shoutout_minimum_amount'] ?? 21;
        $premium_amount = (int)$options['shoutout_premium_amount'] ?? 21000;
        ob_start();
?>
        <div id="bitcoin-donation-donation-form" class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?>">
            <form method="post">
                <?php wp_nonce_field('shoutout_nonce', 'shoutout_nonce'); ?>
                <input type="hidden" name="shoutout_submitted" value="1">

                <div class="bitcoin-donation-title-wrapper">
                    <h3><?php echo esc_html($title_text); ?></h3>
                </div>
                <div class="shoutout-form-container">
                    <div class="shoutout-form-left">
                        <div>

                            <label for="bitcoin-donation-shoutout-name">Name</label>
                            <input type="text" id="bitcoin-donation-shoutout-name" name="name" placeholder="Anonymous">
                        </div>

                        <!-- Honeypot field -->
                        <input type="text" id="bitcoin-donation-shoutout-email" name="email" style="display: none;" aria-hidden="true">
                        <div>

                            <label for="bitcoin-donation-shoutout-amount">Amount (in <?php echo esc_html($currency); ?>):</label>
                            <input type="number" id="bitcoin-donation-shoutout-amount" name="amount" step="0.00000001">
                        </div>

                        <div>
                            <label for="bitcoin-donation-shoutout-satoshi">Satoshi:</label>
                            <input type="number" id="bitcoin-donation-shoutout-satoshi" name="satoshi" min="<?php echo esc_attr($min_amount); ?>">
                        </div>
                    </div>
                    <div class="bitcoin-donation-shoutout-help">
                        <p id="bitcoin-donation-shoutout-help-info">*Minimum shoutout amount is <?php echo esc_html($min_amount); ?> and Premium shoutout amount <?php echo esc_html($premium_amount); ?></p>
                        <p id="bitcoin-donation-shoutout-help-premium">*Selected amount will grant a premium shoutout</p>
                        <p id="bitcoin-donation-shoutout-help-minimum">*Selected amount is less than minimum allowed for a shoutout</p>
                    </div>

                    <div class="shoutout-form-right">

                        <label for="bitcoin-donation-shoutout-message">Message:</label>
                        <textarea id="bitcoin-donation-shoutout-message" class="bitcoin-donation-shoutout-message" name="message" rows="2"></textarea>
                    </div>
                </div>
                <!-- <button type="submit" name="submit_shoutout"><?php echo esc_html($butoon_text); ?></button> -->
                <button id="bitcoin-donation-shout" type="submit" name="submit_shoutout" onclick="return false;"><?php echo esc_html($butoon_text); ?></button>
            </form>
        </div>

<?php

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shoutouts_Form();
