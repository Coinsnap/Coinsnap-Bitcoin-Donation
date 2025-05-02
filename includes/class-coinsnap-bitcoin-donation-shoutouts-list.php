<?php
if (! defined('ABSPATH')) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Shoutouts_List
{
    public function __construct()
    {
        add_shortcode('shoutout_list', [$this, 'coinsnap_bitcoin_donation_render_shortcode']);
    }

    function coinsnap_bitcoin_donation_render_shortcode()
    {
        $options_general = get_option('coinsnap_bitcoin_donation_options');
        $options = get_option('coinsnap_bitcoin_donation_forms_options');

        $theme_class = $options_general['theme'] === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme' : 'coinsnap-bitcoin-donation-light-theme';
        $args = array(
            'post_type'      => 'bitcoin-shoutouts',
            'post_status'    => 'publish',
        );
        $active = $options['shoutout_donation_active'] ?? '1';

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $posts = $query->posts;

            foreach ($posts as $post) {
                $post_id = $post->ID;
                // error_log(print_r(get_post_meta($post_id), true));
                $shoutout_name = get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_name', true);
                $shoutout_amount = get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_amount', true);
                $shoutout_message = get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_message', true);

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
        <div class="shoutouts-list-container">
            <div id="coinsnap-bitcoin-donation-shoutouts-wrapper">

                <?php
                if ($active) {
                    if (empty($shoutouts)) {
                        $this->render_empty_donation_row($theme_class);
                    } else {
                        foreach ($shoutouts as $shoutout) {
                            $this->render_donation_row($shoutout, $theme_class);
                        }
                    }
                } else {
                ?>
                    <div class="coinsnap-bitcoin-donation-form <?php echo esc_attr($theme_class); ?>">
                        <div class="shoutout-form-wrapper"
                            style="display: flex;justify-content: center; flex-direction: column; align-items: center; margin: 0">
                            <h3>Shoutouts List</h3>
                            <h4 style="text-align: center;">This form is not active</h4>
                        </div>
                    </div>
                <?php
                }
                ?>

            </div>
        </div>

    <?php

        return ob_get_clean();
    }

    private function render_empty_donation_row($theme)
    {

        $highlight = false;
        $name = "No Shoutouts Available";
        $message = "There are no shoutouts yet. This is just an example of how they will be displayed once there are some available.";
        $amount = "0 sats";
        $daysAgo = "Today";
    ?>
        <div class="coinsnap-bitcoin-donation-shoutout <?php echo esc_attr($theme); ?> <?php echo $highlight ? 'highlight-shoutout' : ''; ?>">
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

    private function render_donation_row($donation, $theme)
    {
        $options = get_option('coinsnap_bitcoin_donation_forms_options');
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
        <div class="coinsnap-bitcoin-donation-shoutout <?php echo esc_attr($theme); ?> <?php echo $highlight ? 'highlight-shoutout' : ''; ?>">
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

new Coinsnap_Bitcoin_Donation_Shoutouts_List();
