<?php
if (! defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Donor_List
{
    public function __construct()
    {
        add_shortcode('public_donor_wall', [$this, 'bitcoin_donation_render_shortcode']);
    }

    function bitcoin_donation_render_shortcode()
    {
        $options_general = get_option('bitcoin_donation_options');
        $theme_class = $options_general['theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $options = get_option('bitcoin_donation_forms_options');
        $active = $options['donor_wall_active'] ?? '1';
        $show_name = $options['donor_wall_name'] == 'show';
        $show_amount = $options['donor_wall_amount'] == 'show';
        $show_message = $options['donor_wall_message'] == 'show';
        $posts_per_page = $options['donor_wall_per_page'] ?? 10;

        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'post_type'      => 'bitcoin-pds',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged'         => $paged
        );

        $query = new WP_Query($args);
        $donations = array();

        if ($query->have_posts()) {
            $posts = $query->posts;

            foreach ($posts as $post) {
                $post_id = $post->ID;

                $name = $show_name ? get_post_meta($post_id, '_bitcoin_donation_donor_name', true) : 'Anonymous';
                $amount = $show_amount ? get_post_meta($post_id, '_bitcoin_donation_amount', true) : 'hidden';
                $message = $show_message ? get_post_meta($post_id, '_bitcoin_donation_message', true) : '';

                $donations[] = array(
                    'name'   => $name,
                    'amount' => $amount,
                    'message' => $message

                );
            }
        }

        ob_start();
?>
        <div class="public-donor-list-container">
            <div id="bitcoin-donation-shoutouts-wrapper">

                <?php
                if ($active) {
                    if (empty($donations)) {
                        $this->render_empty_donation_row($theme_class);
                    } else {
                        foreach ($donations as $donation) {
                            if (empty($donation['name'])) {
                                continue;
                            }
                            $this->render_donation_row($donation, $theme_class);
                        }
                    }
                ?>
            </div>
            <div class="bitcoin-donation-pagination">
                <?php
                    echo paginate_links(array(
                        'total'   => $query->max_num_pages,
                        'current' => $paged,
                        'format'  => '?paged=%#%',
                    ));
                ?>
            </div>
        <?php } else { ?>
            <div id="bitcoin-donation-shoutouts-wrapper">
                <div class="bitcoin-donation-donation-form <?php echo esc_attr($theme_class); ?>">
                    <div class="shoutout-form-wrapper"
                        style="display: flex;justify-content: center; flex-direction: column; align-items: center; margin: 0">
                        <h3>Donor List</h3>
                        <h4 style="text-align: center;">This form is not active</h4>
                    </div>
                </div>

            </div>
        <?php } ?>
        </div>
    <?php
        return ob_get_clean();
    }

    private function render_empty_donation_row($theme)
    {
        $name = "No Donors Available";
        $message = "There are no donors yet. This is just an example of how they will be displayed once there are some available.";
        $amount = "0 sats";
    ?>
        <div class="bitcoin-donation-shoutout <?php echo esc_attr($theme); ?>">
            <div class="bitcoin-donation-shoutout-top">
                <?php echo esc_html($name); ?>
                <div class="bitcoin-donation-shoutout-top-right">
                    <div class="bitcoin-donation-shoutout-top-right-amount "> <?php echo esc_html($amount); ?></div>
                </div>
            </div>
            <div class="bitcoin-donation-shoutout-bottom">
                <?php echo esc_html($message); ?>
            </div>

        </div>
    <?php
    }


    private function render_donation_row($donation, $theme)
    {
        $name = $donation['name'];
        $amount = $donation['amount'];
        $message = $donation['message'];
    ?>
        <div class="bitcoin-donation-shoutout <?php echo esc_attr($theme); ?>">
            <div class="bitcoin-donation-shoutout-top">
                <?php echo esc_html($name); ?>
                <div class="bitcoin-donation-shoutout-top-right">
                    <div class="bitcoin-donation-shoutout-top-right-amount"> <?php echo esc_html($amount); ?></div>
                    <div class="bitcoin-donation-shoutout-top-right-days"></div>
                </div>
            </div>
            <div class="bitcoin-donation-shoutout-bottom">
                <?php echo esc_html($message); ?>
            </div>
        </div>
<?php

    }
}

new Bitcoin_Donation_Donor_List();
