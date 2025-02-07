<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Shoutouts_List
{
    public function __construct()
    {
        add_shortcode('shoutout_list', [$this, 'bitcoin_donation_render_shortcode']);
    }

    function bitcoin_donation_render_shortcode()
    {
        $options = get_option('bitcoin_donation_forms_options');
        $theme_class = isset($options['shoutout_theme']) && $options['shoutout_theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $args = array(
            'post_type'      => 'bitcoin-shoutouts',
            'post_status'    => 'publish',
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $posts = $query->posts;

            foreach ($posts as $post) {
                $post_id = $post->ID;
                $shoutout_name = get_post_meta($post_id, '_bitcoin_donation_shoutouts_name', true);
                $shoutout_amount = get_post_meta($post_id, '_bitcoin_donation_shoutouts_amount', true);
                $shoutout_message = get_post_meta($post_id, '_bitcoin_donation_shoutouts_message', true);

                $shoutouts[] = array(
                    'date'   => $post->post_date,
                    'name'   => $shoutout_name,
                    'amount' => $shoutout_amount,
                    'message' => $shoutout_message

                );
            }
        }

        ob_start();
?>
        <div id="bitcoin-donation-shoutouts-wrapper">

            <?php
            if (empty($shoutouts)) {
                echo '<tr><td colspan="3">No donations found.</td></tr>';
            } else {
                foreach ($shoutouts as $shoutout) {
                    $this->render_donation_row($shoutout, $theme_class);
                }
            }
            ?>


        </div>

    <?php

        return ob_get_clean();
    }

    private function render_donation_row($donation, $theme)
    {
        $options = get_option('bitcoin_donation_forms_options');
        $name = $donation['name'];
        $amount = $donation['amount'];
        $message = $donation['message'];
        $highlightAmount = $options['shoutout_premium_amount'] ?? '21000';
        $highlight = (int)$amount >= (int)$highlightAmount;
        $date =  $donation['date'];
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
        <div class="bitcoin-donation-shoutout <?php echo esc_attr($theme); ?> <?php echo $highlight ? 'highlight-shoutout' : ''; ?>">
            <div class="bitcoin-donation-shoutout-top">
                <?php echo esc_html($name); ?>
                <div class="bitcoin-donation-shoutout-top-right">
                    <div class="bitcoin-donation-shoutout-top-right-amount <?php echo $highlight ? 'highlight' : ''; ?>"> <?php echo esc_html($amount); ?> sats</div>
                    <div class="bitcoin-donation-shoutout-top-right-days"> <?php echo esc_html($daysAgo); ?></div>

                </div>
            </div>
            <div class="bitcoin-donation-shoutout-bottom">
                <?php echo esc_html($message); ?>
            </div>
        </div>


<?php

    }
}

new Bitcoin_Donation_Shoutouts_List();
