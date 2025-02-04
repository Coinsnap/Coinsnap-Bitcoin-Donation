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

    private function fetch_from_coinsnap()
    {
        $options = get_option('bitcoin_donation_options');
        // Implementation for Coinsnap API call
        $api_key = $options['coinsnap_api_key'];
        $store_id = $options['coinsnap_store_id'];

        $response = wp_remote_get(
            "https://app.coinsnap.io/api/v1/stores/{$store_id}/invoices",
            array(
                'headers' => array(
                    'x-api-key' => $api_key,
                    'Content-Type' => 'application/json'
                )
            )
        );

        $body = wp_remote_retrieve_body($response);

        $invoices = json_decode($body, true); // Decode as associative array

        if (!is_array($invoices)) {
            throw new Exception('Invalid API response');
        }

        // Filter the invoices where metadata.referralCode equals "D19833"
        $filtered_invoices = array_filter($invoices, function ($invoice) {
            return isset($invoice['metadata']['type'])
                && $invoice['metadata']['type'] === "Bitcoin Shoutout"
                && $invoice['status'] === 'Settled';
        });
        // error_log('Coinsnap Response Body: ' . print_r($filtered_invoices, true));

        usort($filtered_invoices, function ($a, $b) {
            return $b['createdAt'] <=> $a['createdAt'];
        });

        return array_values($filtered_invoices);
    }

    function bitcoin_donation_render_shortcode()
    {
        $options = get_option('bitcoin_donation_options');
        $theme_class = isset($options['shoutout_theme']) && $options['shoutout_theme'] === 'dark' ? 'bitcoin-donation-dark-theme' : 'bitcoin-donation-light-theme';
        $highlightAmount = $options['shoutout_premium_amount'] ?? '21000';
        // $highlighted = [];
        // $normal = [];
        $shoutouts = $this->fetch_from_coinsnap();

        // foreach ($shoutouts as $shoutout) {
        //     if ((int) $shoutout['amount'] >= (int)$highlightAmount) {

        //         $highlighted[] = $shoutout;
        //     } else {
        //         $normal[] = $shoutout;
        //     }
        // }
        // $sorted_shoutouts = array_merge($highlighted, $normal);

        ob_start();
?>
        <div id="bitcoin-donation-shoutouts-wrapper">
            <!-- <div id="bitcoin-donation-shoutouts-list" class="<?php echo esc_attr($theme_class); ?>"> -->
            <!-- <div class="bitcoin-shoutouts-title-wrapper">
                <h3>SHOUTOUTS</h3>
            </div> -->

            <!-- <table class="wp-table widefat striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody id="donation-list-body">
                    <?php
                    /**if (empty($sorted_shoutouts)) {
                        echo '<tr><td colspan="3">No donations found.</td></tr>';
                    } else {
                        foreach ($sorted_shoutouts as $shoutout) {
                            $this->render_donation_row($shoutout);
                        }
                    }*/
                    ?>
                </tbody>
            </table> -->


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
        // Check if we're rendering from database or API response
        $is_db_record = isset($donation->created_at);
        $invoice_id = $is_db_record ? $donation->invoice_id : $donation['id'];
        $name = $is_db_record ? $donation->metadata->name : $donation['metadata']['name'];
        $options = get_option('bitcoin_donation_options');
        $provider = $options['provider'];
        $highlightAmount = $options['shoutout_premium_amount'] ?? '21000';
        $isBtcpay = $provider === 'btcpay';
        $href = ($isBtcpay)
            ? "https://btcpay.coincharge.io/invoices/" . esc_html($invoice_id)
            : "https://app.coinsnap.io/td/" . esc_html($invoice_id);
        $amount = $is_db_record ? $donation->amount : $donation['amount'];
        $highlight = (int)$amount >= (int)$highlightAmount;
        $star = $highlight ? ' ⭐' : '';
        $message = ($is_db_record ?
            $donation->message : (isset($donation['metadata']['orderNumber']) ? $donation['metadata']['orderNumber'] : ''));
        $date = $is_db_record ?
            $donation->created_at :
            date('Y-m-d H:i:s', (int)$donation[$isBtcpay ? 'createdTime' :  'createdAt']);
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

    private function render_donation_row_old($donation)
    {
        // Check if we're rendering from database or API response
        $is_db_record = isset($donation->created_at);
        $invoice_id = $is_db_record ? $donation->invoice_id : $donation['id'];
        $name = $is_db_record ? $donation->metadata->name : $donation['metadata']['name'];
        $options = get_option('bitcoin_donation_options');
        $provider = $options['provider'];
        $highlightAmount = $options['shoutout_premium_amount'] ?? '21000';
        $isBtcpay = $provider === 'btcpay';
        $href = ($isBtcpay)
            ? "https://btcpay.coincharge.io/invoices/" . esc_html($invoice_id)
            : "https://app.coinsnap.io/td/" . esc_html($invoice_id);
        $amount = $is_db_record ? $donation->amount : $donation['amount'];
        $highlight = (int)$amount >= (int)$highlightAmount;
        $star = $highlight ? ' ⭐' : '';


    ?>
        <tr class="<?php echo $highlight ? 'highlight' : ''; ?>">
            <!-- <td><?php echo esc_html($is_db_record ?
                            $donation->created_at :
                            date('Y-m-d H:i:s', (int)$donation[$isBtcpay ? 'createdTime' :  'createdAt'])); ?>
            </td> -->
            <td>
                <?php
                echo esc_html($name . $star);
                ?>
            </td>

            <td><?php
                $currency = $is_db_record ? $donation->currency : $donation['currency'];
                echo esc_html(number_format($amount, $isBtcpay ? 2 : 0) . ' ' . ($isBtcpay ? $currency : 'sats'));
                ?></td>
            <td class="<?php echo $highlight ? 'highlight-message' : ''; ?>"><?php echo esc_html($is_db_record ?
                                                                                    $donation->message : (isset($donation['metadata']['orderNumber']) ? $donation['metadata']['orderNumber'] : '')); ?></td>
        </tr>
<?php
    }
}

new Bitcoin_Donation_Shoutouts_List();
