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
        $options_general = get_option('bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
        $active = get_post_meta($poll_id, '_bitcoin_donation_polls_active', true);
        $one_vote = get_post_meta($poll_id, '_bitcoin_donation_polls_one_vote', true);
        // Check if poll is active
        if (!$active) {
            ob_start();
?>
            <div id="bitcoin-voting-form" class="bitcoin-voting-form  <?php echo esc_attr($theme_class); ?>" data-one-vote="<?php echo esc_attr($one_vote) ?>">
                <div class="bitcoin-voting-form-container">
                    <h3><?php echo esc_html($title ?:  'Bitcoin Voting'); ?></h3>
                    <p><?php echo esc_html($description ?: 'What would you like to see more of on our blog?'); ?></p>
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

        ob_start();

        $now = current_time('timestamp');
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        if ($now < $start_timestamp) {
            $time_until_start = human_time_diff($now, $start_timestamp);
        ?>
            <div id="bitcoin-voting-form" class="bitcoin-voting-form  <?php echo esc_attr($theme_class); ?>" data-one-vote="<?php echo esc_attr($one_vote) ?>">
                <div class="bitcoin-voting-form-container">
                    <h3><?php echo esc_html($title ?:  'Bitcoin Voting'); ?></h3>
                    <p><?php echo esc_html($description ?: 'What would you like to see more of on our blog?'); ?></p>
                    <h4>Poll starting in: <?php echo $time_until_start; ?></h4>
                </div>
            </div>
        <?php
        } elseif ($now > $end_timestamp) {
        ?>
            <div id="bitcoin-voting-form" class="bitcoin-voting-form  <?php echo esc_attr($theme_class); ?>" data-one-vote="<?php echo esc_attr($one_vote) ?>">
                <div class="bitcoin-voting-form-container">
                    <h3><?php echo esc_html($title ?:  'Voting Poll'); ?></h3>
                    <p><?php echo esc_html($description ?: ''); ?></p>
                    <div id="poll-results" class="poll-results" data-end-date="<?php echo $end_date; ?>" data-poll-id="<?php echo $poll_id; ?>">
                        <?php
                        for ($i = 1; $i <= min(4, $num_options ?: 4); $i++):
                            if (isset($options[$i])):
                        ?>
                                <div class="poll-result">
                                    <div class="poll-result-title">
                                        <span>
                                            <?php echo esc_html($options[$i]); ?> (<span class="vote-count" data-option="<?php echo $i; ?>">0</span> votes)
                                        </span>
                                        <span class="voting-progress-percentage" data-option="<?php echo $i; ?>"></span>
                                    </div>
                                    <div class="voting-progress">
                                        <div class="voting-progress-bar" data-option="<?php echo $i; ?>"></div>
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

                <div class="blur-overlay"></div>
                <div class="bitcoin-voting-form-container">
                    <div class="title-container">
                        <h3><?php echo esc_html($title ?:  'Bitcoin Voting'); ?></h3>
                        <button id="return-button" style="display: none;" class="return-button">&#8592;</button>
                    </div>
                    <p><?php echo esc_html($description ?: 'What would you like to see more of on our blog?'); ?></p>
                    <div class="poll-options">
                        <?php
                        for ($i = 1; $i <= min(4, $num_options ?: 4); $i++):
                            if (isset($options[$i])):
                        ?>
                                <button class="poll-option" data-option="<?php echo $i; ?>">
                                    <?php echo esc_html($options[$i]); ?>
                                </button>
                        <?php endif;
                        endfor; ?>
                        <div class="poll-total-votes">
                            <button id="check-results" data-poll-id="<?php echo $poll_id; ?>" class="check-results">Check results</button>
                            <div class="end-text">Ends in: <?php echo $time_until_end; ?></div>
                        </div>

                    </div>
                    <div class="poll-results" style="display: none;">
                        <?php
                        for ($i = 1; $i <= min(4, $num_options ?: 4); $i++):
                            if (isset($options[$i])):
                        ?>
                                <div class="poll-result">
                                    <div class="poll-result-title">
                                        <span>
                                            <?php echo esc_html($options[$i]); ?> (<span class="vote-count" data-option="<?php echo $i; ?>">0</span> votes)
                                        </span>
                                        <span class="voting-progress-percentage" data-option="<?php echo $i; ?>"></span>
                                    </div>
                                    <div class="voting-progress">
                                        <div class="voting-progress-bar" data-option="<?php echo $i; ?>"></div>
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
            </div>
            <div id="qr-payment-container" class="qr-container">
                <div id="qr-spinner" class="loader qr-spinner"></div>
                <div class="close-popup">×</div>
                <h4 class="qr-amount" id="qr-amount"></h4>
                <p class="qr-fiat" id="qr-fiat"></p>
                <div style="position: relative;">
                    <img class="qr-code" style="display: none;" id="qrCode" alt="QR Code">
                    <img class="qr-code-btc" id="qrCodeBtc" alt="QR Code Btc" src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/bitcoinqr.svg'; ?>">
                </div>
                <details open class="qr-details">
                    <summary id="qr-summary" class="qr-summary">Details <span class="qr-dropdown">&#9660;</span></summary>
                    <div class="qr-address-wrapper" id="lightning-wrapper" style="display: none; margin-top:8px">
                        <div class="qr-address-title">
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
                    <div id="btc-wrapper" style="display: none; margin-top:12px" class="qr-address-wrapper">
                        <div class="qr-address-title">
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
            </div>
            <div id="thank-you-popup" class="qr-container">
                <div class="close-popup">×</div>
                <img class="checkmark-img" id="checkmark" alt="Checkmark" src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/checkmark.svg'; ?>">
                <h3 style="margin: 10px 0 0 0;">Your vote has been recorded.</h3>
                <p style="margin: 0px;"><?php echo esc_html($thank_you) ?></p>
            </div>

<?php
        }

        return ob_get_clean();
    }
}

new Bitcoin_Donation_Shortcode_Voting();
