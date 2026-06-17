<?php if (!defined('ABSPATH')){ exit; } ?>
<div class="shoutouts-list-container" data-form-id="<?php echo esc_attr($form_id); ?>">
    <div id="coinsnap-bitcoin-donation-shoutouts-wrapper-<?php echo esc_attr($form_id); ?>">

        <?php
        if (empty($shoutouts)) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- local template variables, not true globals.
            $highlight = false;
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
            $empty_name = __('No Shoutouts Available', 'coinsnap-bitcoin-donation');
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
            $empty_message = __('There are no shoutouts yet. This is just an example of how they will be displayed once there are some available.', 'coinsnap-bitcoin-donation');
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
            $empty_amount = __('0 sats', 'coinsnap-bitcoin-donation');
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
            $empty_daysAgo = __('Today', 'coinsnap-bitcoin-donation');
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
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- loop variable inside included template, not a true global.
            foreach ($shoutouts as $donation) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- local loop variables inside included template, not true globals.
                $name = $donation['name'];
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $amount = $donation['amount'];
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $sats_amount = !empty($donation['sats_amount']) ? $donation['sats_amount'] . ' sats' : '';
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $message = $donation['message'];
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $highlight = (int)$amount >= (int)$premium_amount || (int)$sats_amount >= (int)$premium_amount;
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $date = $donation['date'];
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $donationDate = new DateTime($date);
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $now = new DateTime();
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $interval = $donationDate->diff($now);
                if ($interval->days === 0) {
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                    $daysAgo = 'Today';
                } elseif ($interval->days === 1) {
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                    $daysAgo = '1 day ago';
                } else {
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
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
