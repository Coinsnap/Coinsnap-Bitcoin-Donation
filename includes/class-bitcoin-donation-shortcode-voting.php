<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Shortcode_Voting
{
    public function __construct()
    {
        add_shortcode('bitcoin_voting', [$this, 'bitcoin_donation_render_shortcode_voting']);
    }

    function bitcoin_donation_render_shortcode_voting($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'bitcoin_voting');

        $poll_id = intval($atts['id']);
        // Check if poll_id is valid and post exists
        if (!$poll_id || get_post_type($poll_id) !== 'bitcoin-polls') {
            return '<p>Invalid or missing poll ID.</p>';
        }

        // Retrieve poll meta data
        $title = get_the_title($poll_id);
        $thank_you = get_post_meta($poll_id, '_bitcoin_donation_polls_thank_you_message', true);
        $description = get_post_meta($poll_id, '_bitcoin_donation_polls_description', true);
        $amount = get_post_meta($poll_id, '_bitcoin_donation_polls_amount', true);
        $start_date = get_post_meta($poll_id, '_bitcoin_donation_polls_starting_date', true);
        $end_date = get_post_meta($poll_id, '_bitcoin_donation_polls_ending_date', true);
        $num_options = 0;
        $options = array();
        $dark_mode = get_post_meta($poll_id, '_bitcoin_donation_polls_dark_mode', true);
        $theme_class = $dark_mode == true ? "dark-theme" : "light-theme";
        $active = get_post_meta($poll_id, '_bitcoin_donation_polls_active', true);
        $one_vote = get_post_meta($poll_id, '_bitcoin_donation_polls_one_vote', true);

        // Check if poll is active
        if (!$active) {
            ob_start();
?>
            <div id="bitcoin-voting-form" class="bitcoin-voting-form  <?php echo esc_attr($theme_class); ?>">
                <div class="bitcoin-voting-form-container">
                    <h3><?php echo esc_html($title ? $title : 'Bitcoin Voting'); ?></h3>
                    <p><?php echo esc_html($description ? $description : 'What would you like to see more of on our blog?'); ?></p>
                    <h4>This poll is not active</h4>
                </div>
            </div>
        <?php
            return ob_get_clean();
        }

        // Fetch options from meta data
        for ($i = 1; $i <= 4; $i++) {
            $option = get_post_meta($poll_id, "_bitcoin_donation_polls_option_{$i}", true);
            if (!empty($option)) {
                $options[$i] = $option;
                $num_options++;
            }
        }

        $options2 = get_option('bitcoin_donation_forms_options');
        $options2 = is_array($options2) ? $options2 : [];
        ob_start();

        $now = current_time('timestamp');
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        error_log($now);
        error_log($start_timestamp);
        error_log($end_timestamp);
        if ($now < $start_timestamp) {
            $time_until_start = human_time_diff($now, $start_timestamp);
        ?>
            <div id="bitcoin-voting-form" class="bitcoin-voting-form  <?php echo esc_attr($theme_class); ?>">
                <div class="bitcoin-voting-form-container">
                    <h3><?php echo esc_html($title ? $title : 'Bitcoin Voting'); ?></h3>
                    <p><?php echo esc_html($description ? $description : 'What would you like to see more of on our blog?'); ?></p>
                    <h4>Poll starting in: <?php echo $time_until_start; ?></h4>
                </div>
            </div>
        <?php
        } elseif ($now > $end_timestamp) {
        ?>
            <div id="bitcoin-voting-form" class="bitcoin-voting-form  <?php echo esc_attr($theme_class); ?>">
                <div class="bitcoin-voting-form-container">
                    <h3><?php echo esc_html($title ? $title : 'Bitcoin Voting'); ?></h3>
                    <p><?php echo esc_html($description ? $description : 'What would you like to see more of on our blog?'); ?></p>
                    <div id="poll-results" class="poll-results" data-end-date="<?php echo $end_date; ?>" data-poll-id="<?php echo $poll_id; ?>">
                        <?php
                        for ($i = 1; $i <= min(4, $num_options ? $num_options : 4); $i++):
                            if (isset($options[$i])):
                        ?>
                                <div class="poll-result">
                                    <span><?php echo esc_html($options[$i]); ?> (<span class="vote-count" data-option="<?php echo $i; ?>">0</span> votes)</span>
                                    <div class="progress">
                                        <div class="progress-bar" data-option="<?php echo $i; ?>"></div>
                                    </div>
                                </div>
                        <?php endif;
                        endfor; ?>
                        <div class="poll-total-votes">
                            <div class="poll-total-wrapper">
                                Total votes:
                                <div id="total-votes">
                                </div>
                            </div>
                            <div class="end-text"><?php echo '<div>Poll closed</div>'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        } else {
            $time_until_end = human_time_diff($now, $end_timestamp);
        ?>
            <div id="bitcoin-voting-form" class="bitcoin-voting-form <?php echo esc_attr($theme_class); ?>"
                data-poll-id="<?php echo esc_attr($poll_id); ?>"
                data-poll-amount="<?php echo esc_attr($amount ?: '0'); ?>"
                data-one-vote="<?php echo esc_attr($one_vote) ?>">

                <div class="bitcoin-voting-form-container">
                    <div class="title-container">
                        <h3><?php echo esc_html($title ? $title : 'Bitcoin Voting'); ?></h3>
                        <button id="return-button" style="display: none;" class="return-button">&#8592;</button>
                    </div>
                    <p><?php echo esc_html($description ? $description : 'What would you like to see more of on our blog?'); ?></p>
                    <div class="poll-options">
                        <?php
                        // Dynamically output poll options
                        for ($i = 1; $i <= min(4, $num_options ? $num_options : 4); $i++):
                            if (isset($options[$i])):
                        ?>
                                <button style="font-weight: bold;" class="poll-option" data-option="<?php echo $i; ?>">
                                    <?php echo esc_html($options[$i]); ?>
                                </button>
                        <?php endif;
                        endfor; ?>
                        <div class="qr-container">
                            <div id="qr-spinner" class="loader qr-spinner"></div>
                            <div class="close-popup">×</div>
                            <h4 class="qr-amount" id="qr-amount"></h4>
                            <p class="qr-fiat" id="qr-fiat"></p>
                            <div style="position: relative;">
                                <img class="qr-code" style="display: none;" id="qrCode" alt="QR Code">
                                <img class="qr-code-btc" style="display: none;width:42px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);" id="qrCodeBtc" alt="QR Code Btc" src="<?php echo plugin_dir_url(__FILE__) . 'assets/bitcoinqr.svg'; ?>">
                            </div>
                            <details open class="qr-details">
                                <summary id="qr-summary" class="qr-summary">Details <span class="qr-dropdown" style="font-size: 8px;">&#9660;</span></summary>
                                <div class="qr-address-wrapper" id="lightning-wrapper" style="display: none;margin-top:8px">
                                    <div style="text-align: left;">
                                        Lightning:
                                    </div>
                                    <div id="qr-lightning-container" style="display: none;" class="qr-lightning-container">
                                        <div class="qr-lightning" id="qr-lightning"></div>
                                        <svg class="qr-copy-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8f979e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div id="btc-wrapper" style="display: none;margin-top:12px" class="qr-address-wrapper">
                                    <div style="text-align: left;">
                                        Address:
                                    </div>
                                    <div id="qr-btc-container" style="display: none;" class="qr-lightning-container">
                                        <div class="qr-lightning" id="qr-btc"></div>
                                        <svg class="qr-copy-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8f979e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                    </div>
                                </div>

                            </details>
                            <button id="pay-in-wallet" class="qr-pay-in-wallet">Pay in wallet</button>
                            <!-- <div id="qr-help-text" style="display: none;" class="qr-help-text">Scan the qr code via lightning wallet app or copy the address </div> -->
                        </div>
                        <div class="poll-total-votes">
                            <button id="check-results" data-poll-id="<?php echo $poll_id; ?>" class="check-results">Check results</button>
                            <div class="end-text">Ends in: <?php echo $time_until_end; ?></div>
                        </div>

                    </div>
                    <div class="poll-results" style="display: none;">
                        <?php
                        for ($i = 1; $i <= min(4, $num_options ? $num_options : 4); $i++):
                            if (isset($options[$i])):
                        ?>
                                <div class="poll-result">
                                    <span><?php echo esc_html($options[$i]); ?> (<span class="vote-count" data-option="<?php echo $i; ?>">0</span> votes)</span>
                                    <div class="progress">
                                        <div class="progress-bar" data-option="<?php echo $i; ?>">
                                        </div>
                                        <span class="progress-percentage" data-option="<?php echo $i; ?>"></span>

                                    </div>
                                </div> <?php endif;
                                endfor; ?>
                        <div class="poll-total-votes">
                            <div class="poll-total-wrapper">
                                Total votes:
                                <div id="total-votes">
                                </div>
                            </div>
                            <div class="end-text">Ends in: <?php echo $time_until_end; ?></div>
                        </div>
                    </div>
                </div>
                <div id="thank-you-popup" style="display: none; flex-direction:column; justify-content:center;" class="qr-container">
                    <div class="close-popup">×</div>
                    <img class="checkmark-img" style="display: flex; width:120px;" id="checkmark" alt="Checkmark" src="<?php echo plugin_dir_url(__FILE__) . 'assets/checkmark.svg'; ?>">
                    <h3 style="margin: 10px 0 0 0;">Your vote has been recorded.</h3>
                    <p style="margin: 0px;"><?php echo esc_html($thank_you) ?></p>
                </div>
            </div>
<?php
        }

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode_Voting();
