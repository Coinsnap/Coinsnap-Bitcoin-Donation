<?php
if (!defined('ABSPATH')) {
	exit;
}

class coinsnap_bitcoin_donation_Shoutout_Metabox
{
	public function __construct()
	{
		add_action('init', [$this, 'register_shoutouts_post_type']);
		add_action('init', [$this, 'register_custom_meta_fields']);

		add_action('add_meta_boxes', [$this, 'add_shoutouts_metaboxes']);

		add_action('save_post', [$this, 'save_shoutouts_meta'], 10, 2);

		add_filter('manage_bitcoin-shoutouts_posts_columns', [$this, 'add_custom_columns']);
		add_action('manage_bitcoin-shoutouts_posts_custom_column', [$this, 'populate_custom_columns'], 10, 2);
	}

	public function register_shoutouts_post_type()
	{
		register_post_type('bitcoin-shoutouts', [
			'labels' => [
				'name'               => 'Shoutouts',
				'singular_name'      => 'Shoutout',
				'menu_name'          => 'Shoutouts',
				'add_new'            => 'Add New',
				'add_new_item'       => 'Add New Shoutout',
				'edit_item'          => 'Edit Shoutout',
				'new_item'           => 'New Shoutout',
				'view_item'          => 'View Shoutout',
				'search_items'       => 'Search Shoutouts',
				'not_found'          => 'No shoutouts found',
				'not_found_in_trash' => 'No shoutouts found in Trash',
			],
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => ['slug' => 'bitcoin-shoutouts'],
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => ['title'],
			'show_in_rest' 		 => true
		]);
	}
	public function register_custom_meta_fields()
	{
		register_meta('post', '_coinsnap_bitcoin_donation_shoutouts_name', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);

		register_meta('post', '_coinsnap_bitcoin_donation_shoutouts_amount', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);

		register_meta('post', '_coinsnap_bitcoin_donation_shoutouts_sats_amount', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);

		register_meta('post', '_coinsnap_bitcoin_donation_shoutouts_invoice_id', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);

		register_meta('post', '_coinsnap_bitcoin_donation_shoutouts_message', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);

		register_meta('post', '_coinsnap_bitcoin_donation_shoutouts_provider', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);
	}

	public function add_shoutouts_metaboxes()
	{
		add_meta_box(
			'coinsnap_bitcoin_donation_shoutouts_details',
			'Shoutouts Details',
			[$this, 'render_shoutouts_metabox'],
			'bitcoin-shoutouts',
			'normal',
			'high'
		);
	}

	public function render_shoutouts_metabox($post)
	{
		// Add nonce for security
		wp_nonce_field('coinsnap_bitcoin_donation_shoutouts_nonce', 'coinsnap_bitcoin_donation_shoutouts_nonce');

		// Retrieve existing meta values
		$name = get_post_meta($post->ID, '_coinsnap_bitcoin_donation_shoutouts_name', true);
		$message = get_post_meta($post->ID, '_coinsnap_bitcoin_donation_shoutouts_message', true);
		$amount = get_post_meta($post->ID, '_coinsnap_bitcoin_donation_shoutouts_amount', true);
		$sats_amount = get_post_meta($post->ID, '_coinsnap_bitcoin_donation_shoutouts_sats_amount', true);
		$invoice_id = get_post_meta($post->ID, '_coinsnap_bitcoin_donation_shoutouts_invoice_id', true);
		$provider = get_post_meta($post->ID, '_coinsnap_bitcoin_donation_shoutouts_provider', true);

?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="coinsnap_bitcoin_donation_shoutouts_name"><?php echo esc_html_e('Name', 'coinsnap-bitcoin-donation') ?></label>
				</th>
				<td>
					<input
						type="text"
						id="coinsnap_bitcoin_donation_shoutouts_name"
						name="coinsnap_bitcoin_donation_shoutouts_name"
						class="regular-text"
						value="<?php echo esc_attr($name); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="coinsnap_bitcoin_donation_shoutouts_amount"><?php echo esc_html_e('Amount', 'coinsnap-bitcoin-donation') ?></label>
				</th>
				<td>
					<input
						type="text"
						id="coinsnap_bitcoin_donation_shoutouts_amount"
						name="coinsnap_bitcoin_donation_shoutouts_amount"
						class="regular-text"
						value="<?php echo esc_attr($amount); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="coinsnap_bitcoin_donation_shoutouts_sats_amount"><?php echo esc_html_e('Sats Amount', 'coinsnap-bitcoin-donation') ?></label>
				</th>
				<td>
					<input
						type="text"
						id="coinsnap_bitcoin_donation_shoutouts_sats_amount"
						name="coinsnap_bitcoin_donation_shoutouts_sats_amount"
						class="regular-text"
						value="<?php echo esc_attr($sats_amount); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="coinsnap_bitcoin_donation_shoutouts_invoice_id"><?php echo esc_html_e('Invoice Id', 'coinsnap-bitcoin-donation') ?></label>
				</th>
				<td>
					<input
						type="text"
						id="coinsnap_bitcoin_donation_shoutouts_invoice_id"
						name="coinsnap_bitcoin_donation_shoutouts_invoice_id"
						class="regular-text"
						value="<?php echo esc_attr($invoice_id); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="coinsnap_bitcoin_donation_shoutouts_message"><?php echo esc_html_e('Message', 'coinsnap-bitcoin-donation') ?></label>
				</th>
				<td>
					<textarea
						id="coinsnap_bitcoin_donation_shoutouts_message"
						name="coinsnap_bitcoin_donation_shoutouts_message"
						class="regular-text"
						rows="5"
						cols="50"><?php echo esc_textarea($message); ?></textarea>

				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="coinsnap_bitcoin_donation_shoutouts_provider"><?php echo esc_html_e('Provider', 'coinsnap-bitcoin-donation') ?></label>
				</th>
				<td>
					<input
						type="text"
						id="coinsnap_bitcoin_donation_shoutouts_provider"
						name="coinsnap_bitcoin_donation_shoutouts_provider"
						class="regular-text"
						readonly
						value="<?php echo esc_attr($provider); ?>">
				</td>
			</tr>
		</table>
<?php
	}

	public function save_shoutouts_meta($post_id, $post)
	{
		// Bail out if this is an autosave.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check nonce for security
		if (defined('REST_REQUEST') && REST_REQUEST) {
			$expected_nonce = 'wp_rest';
			$nonce = (null !== filter_input(INPUT_SERVER, 'HTTP_X_WP_NONCE', FILTER_SANITIZE_FULL_SPECIAL_CHARS)) ? sanitize_text_field(filter_input(INPUT_SERVER, 'HTTP_X_WP_NONCE', FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
		} else {
			$expected_nonce = 'coinsnap_bitcoin_donation_shoutouts_nonce';
			$nonce = filter_input(INPUT_POST, $expected_nonce, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		}
		if (empty($nonce) || !wp_verify_nonce($nonce, $expected_nonce)) {
			return;
		}

		// Check user permissions
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Check post type
		if ($post->post_type !== 'bitcoin-shoutouts') {
			return;
		}

		$fields = [
			'coinsnap_bitcoin_donation_shoutouts_name'     => 'text',
			'coinsnap_bitcoin_donation_shoutouts_amount'   => 'text',
			'coinsnap_bitcoin_donation_shoutouts_sats_amount' => 'text',
			'coinsnap_bitcoin_donation_shoutouts_provider' => 'text',
			'coinsnap_bitcoin_donation_shoutouts_invoice_id' => 'text',
			'coinsnap_bitcoin_donation_shoutouts_message'    => 'text',
		];

		// If this is a REST request, get the JSON payload.
		if (defined('REST_REQUEST') && REST_REQUEST) {
			$json_body = file_get_contents('php://input');
			$data = json_decode($json_body, true);

			// Check if meta is set and is an array.
			if (isset($data['meta']) && is_array($data['meta'])) {
				foreach ($fields as $field => $type) {
					$json_key = '_' . $field;
					if (isset($data['meta'][$json_key])) {
						$value = $data['meta'][$json_key];
						// Sanitize the value according to its type.
						if ($type === 'number') {
							$value = floatval($value);
						} else {
							$value = sanitize_text_field($value);
						}
						update_post_meta($post_id, $json_key, $value);
					}
				}
			}
			return;
		}
		foreach ($fields as $field => $type) {
			if (null !== filter_input(INPUT_POST, $field, FILTER_SANITIZE_FULL_SPECIAL_CHARS)) {
				$value = filter_input(INPUT_POST, $field, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

				if ($type === 'number') {
					$value = floatval($value);
				} else {
					$value = sanitize_text_field($value);
				}

				update_post_meta($post_id, '_' . $field, $value); // The stored meta keys have a leading underscore.
			}
		}
	}

	public function add_custom_columns($columns)
	{
		$new_columns = [
			'cb' => $columns['cb'],
			'title' => $columns['title'],
			'name' => 'Name',
			'amount' => 'Amount',
			'sats_amount' => 'Sats Amount',
			'invoice_id' => 'Invoice id',
			'message' => 'Message'
		];

		return $new_columns;
	}

	public function populate_custom_columns($column, $post_id)
	{
		switch ($column) {
			case 'name':
				echo esc_html(get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_name', true) ?: 'Anonymous');
				break;
			case 'amount':
				echo esc_html(get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_amount', true) ?: '');
				break;
			case 'sats_amount':
				echo esc_html(get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_sats_amount', true) ?: '');
				break;
			case 'invoice_id':
				$invoice_id = get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_invoice_id', true) ?: '';
				if (!empty($invoice_id)) {
					$provider = get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_provider', true) ?: '';
					$url = $provider === 'btcpay' ? 'https://btcpay.coincharge.io/invoices/' : 'https://app.coinsnap.io/td/';
					$href = $url . esc_attr($invoice_id);
					echo '<a href="' . esc_url($href) . '" class="button button-small" target="_blank" rel="noopener noreferrer">' .
						esc_html($invoice_id) . '</a>';
				}
				break;
			case 'message':
				$message = get_post_meta($post_id, '_coinsnap_bitcoin_donation_shoutouts_message', true) ?: '';
				$message = strlen($message) > 150 ? substr($message, 0, 150) . ' ...' : $message;
				echo esc_html($message);
				break;
		}
	}
}

new coinsnap_bitcoin_donation_Shoutout_Metabox();
