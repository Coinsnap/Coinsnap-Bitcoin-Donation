<?php
class Bitcoin_Donation_List
{

	public function __construct()
	{
		// Add menu item under plugin settings
		add_action('admin_menu', array($this, 'add_donations_submenu'));

		// Ajax handlers for refreshing donation data
		add_action('wp_ajax_refresh_donations', array($this, 'refresh_donations_ajax'));
	}

	public function add_donations_submenu()
	{
		add_submenu_page(
			'bitcoin_donation',          // Parent slug
			'Donation List',                    // Page title
			'Donation List',                    // Menu title
			'manage_options',                   // Capability
			'bitcoin-donation-donation-list',               // Menu slug
			array($this, 'render_donation_page') // Callback function
		);
	}

	public function render_donation_page()
	{
		// Verify user capabilities
		if (!current_user_can('manage_options')) {
			return;
		}

?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<h4>Check <a href="https://app.coinsnap.io/transactions" target="_blank" rel="noopener noreferrer">Coinsnap app</a> for a detailed overview</h4>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Date</th>
						<th>Amount</th>
						<th>Message</th>
						<th>Invoice ID</th>
					</tr>
				</thead>
				<tbody id="donation-list-body">
					<?php
					if (empty($donations)) {
						echo '<tr><td colspan="5">No donations found.</td></tr>';
					} else {
						foreach ($donations as $donation) {
							$this->render_donation_row($donation);
						}
					}
					?>
				</tbody>
			</table>
		</div>

		<script>
			jQuery(document).ready(function($) {
				function refreshDonations() {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'refresh_donations',
							nonce: '<?php echo esc_html(wp_create_nonce('refresh_donations_nonce')); ?>'
						},
						success: function(response) {
							if (response.success) {
								$('#donation-list-body').html(response.data.html);
							} else {
								alert('Failed to refresh donations: ' + response.data.message);
							}
						},
						error: function() {

							alert('Failed to refresh donations. Please try again.');
						},
					});
				}
				$('#refresh-donations').on('click', refreshDonations);

				refreshDonations();
			});
		</script>
	<?php
	}

	public function refresh_donations_ajax()
	{
		check_ajax_referer('refresh_donations_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Unauthorized access'));
			return;
		}

		try {
			$donations = $this->fetch_donations_from_api();

			ob_start();
			foreach ($donations as $donation) {
				$this->render_donation_row($donation);
			}
			$html = ob_get_clean();

			wp_send_json_success(array('html' => $html));
		} catch (Exception $e) {
			wp_send_json_error(array('message' => $e->getMessage()));
		}
	}

	private function fetch_donations_from_api()
	{
		$options = get_option('bitcoin_donation_options');
		$provider = $options['provider'];
		switch ($provider) {
			case 'coinsnap':
				return $this->fetch_from_coinsnap($options);
			case 'btcpay':
				return $this->fetch_from_btcpay($options);
			default:
				throw new Exception('Please setup the payment gateway first');
		}
	}

	private function fetch_from_coinsnap($options)
	{
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

		// Filter the invoices where metadata.referralCode equals BITCOIN_DONATION_REFERRAL_CODE
		$filtered_invoices = array_filter($invoices, function ($invoice) {
			return isset($invoice['metadata']['referralCode'])
				&& $invoice['metadata']['referralCode'] === BITCOIN_DONATION_REFERRAL_CODE
				&& $invoice['status'] === 'Settled';
		});
		// error_log('Coinsnap Response Body: ' . print_r($filtered_invoices, true));

		usort($filtered_invoices, function ($a, $b) {
			return $b['createdAt'] <=> $a['createdAt'];
		});

		return array_values($filtered_invoices);
	}

	private function fetch_from_btcpay($options)
	{
		// Implementation for BTCPay API call
		$api_key = $options['btcpay_api_key'];
		$store_id = $options['btcpay_store_id'];
		$base_url = $options['btcpay_url'];

		$response = wp_remote_get(
			"{$base_url}/api/v1/stores/{$store_id}/invoices",
			array(
				'headers' => array(
					'Authorization' => 'token ' . $api_key,
					'Content-Type' => 'application/json'
				)
			)
		);

		if (is_wp_error($response)) {
			throw new Exception(esc_html($response->get_error_message()));
		}

		$body = wp_remote_retrieve_body($response);

		$invoices = json_decode($body, true); // Decode as associative array

		if (!is_array($invoices)) {
			throw new Exception('Invalid API response');
		}

		// Filter the invoices where metadata.referralCode equals BITCOIN_DONATION_REFERRAL_CODE
		$filtered_invoices = array_filter($invoices, function ($invoice) {
			return isset($invoice['metadata']['referralCode'])
				&& $invoice['metadata']['referralCode'] === BITCOIN_DONATION_REFERRAL_CODE
				&& $invoice['status'] === 'Settled';
		});
		// error_log('BTCPay Response Body: ' . print_r($filtered_invoices, true));

		return array_values($filtered_invoices); // Re-index the filtered array
	}


	private function render_donation_row($donation){
        // Check if we're rendering from database or API response
            $is_db_record = isset($donation->created_at);
            $invoice_id = $is_db_record ? $donation->invoice_id : $donation['id'];
            $options = get_option('bitcoin_donation_options');
            $provider = $options['provider'];
            $isBtcpay = $provider === 'btcpay';
            $href = ($isBtcpay)
                ? "https://btcpay.coincharge.io/invoices/" . esc_html($invoice_id)
		: "https://app.coinsnap.io/td/" . esc_html($invoice_id);?>
		<tr><td><?php echo esc_html($is_db_record ?
                            $donation->created_at :
                            gmdate('Y-m-d H:i:s', (int)$donation[$isBtcpay ? 'createdTime' :  'createdAt'])); ?></td>
                    <td><?php $amount = $is_db_record ? $donation->amount : $donation['amount'];
			$currency = $is_db_record ? $donation->currency : $donation['currency'];
			echo esc_html(number_format($amount, $isBtcpay ? 2 : 0) . ' ' . ($isBtcpay ? $currency : 'sats'));
			?></td>
                    <td><?php echo esc_html($is_db_record ? $donation->message : (isset($donation['metadata']['orderNumber']) ? $donation['metadata']['orderNumber'] : ''));?></td>
                    <td><a href="<?php echo esc_html($href); ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer"><?php echo esc_html($invoice_id); ?></a></td>
		</tr>
<?php
	}
}
new Bitcoin_Donation_List();
