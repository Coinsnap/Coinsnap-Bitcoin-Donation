<?php if (!defined('ABSPATH')){ exit; } ?>
<div class="shoutouts-list-container" data-form-id="<?php echo esc_attr($form_id); ?>">
    <div id="coinsnap-bitcoin-donation-shoutouts-wrapper-<?php echo esc_attr($form_id); ?>">

        <?php
        if (empty($shoutouts)) {
            $highlight = false;
            $empty_name = __("No Shoutouts Available", 'coinsnap-bitcoin-donation');
            $empty_message = __("There are no shoutouts yet. This is just an example of how they will be displayed once there are some available.", 'coinsnap-bitcoin-donation');
            $empty_amount = __("0 sats", 'coinsnap-bitcoin-donation');
            $empty_daysAgo = __("Today", 'coinsnap-bitcoin-donation');
            ?>
            <div class="coinsnap-bitcoin-donation-shoutout <?php echo esc_attr($theme_class); ?> <?php echo $highlight ? 'highlight-shoutout' : ''; ?>">
                <div class="coinsnap-bitcoin-donation-shoutout-top">
                    <?php echo esc_html($empty_name); ?>
                    <div class="coinsnap-bitcoin-donation-shoutout-top-right">
                        <div class="coinsnap-bitcoin-donation-shoutout-top-right-amount <?php echo $highlight ? 'highlight' : ''; ?>"> <?php echo esc_html($empty_amount); ?></div>
                        <div class="coinsnap-bitcoin-donation-shoutout-top-right-days"> <?php echo esc_html($empty_daysAgo); ?></div>

                    </div>
                </div>
                <div class="coinsnap-bitcoin-donation-shoutout-bottom">
                    <?php echo esc_html($empty_message); ?>
                </div>

            </div>
            <?php
        } else {
            foreach ($shoutouts as $donation) {
                $name = $donation['name'];
                $amount = $donation['amount'];
                $sats_amount = !empty($donation['sats_amount']) ? $donation['sats_amount'] . ' sats' : '';
                $message = $donation['message'];
                $highlight = (int)$amount >= (int)$premium_amount || (int)$sats_amount >= (int)$premium_amount;
                $date = $donation['date'];
                $donationDate = new DateTime($date);
                $now = new DateTime();
                $interval = $donationDate->diff($now);
                if ($interval->days === 0) {
                    $daysAgo = 'Today';
                } elseif ($interval->days === 1) {
                    $daysAgo = '1 day ago';
                } else {
                    $daysAgo = $interval->days . ' days ago';
                }
                ?>
                <div class="coinsnap-bitcoin-donation-shoutout <?php echo esc_attr($theme_class); ?> <?php echo $highlight ? 'highlight-shoutout' : ''; ?>">
                    <div class="coinsnap-bitcoin-donation-shoutout-top">
                        <?php echo esc_html($name); ?>
                        <div class="coinsnap-bitcoin-donation-shoutout-top-right">
                            <div class="coinsnap-bitcoin-donation-shoutout-top-right-amount <?php echo $highlight ? 'highlight' : ''; ?>"> <?php echo esc_html($amount); ?></div>
                            <div class="coinsnap-bitcoin-donation-shoutout-top-right-days"> <?php echo esc_html($daysAgo); ?></div>

                        </div>
                    </div>
                    <div class="coinsnap-bitcoin-donation-shoutout-bottom">
                        <?php echo esc_html($message); ?>
                    </div>
                </div>
                <?php
            }
        }
        ?>

    </div>
</div>
