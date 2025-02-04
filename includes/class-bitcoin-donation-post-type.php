<?php
if (!defined('ABSPATH')) {
	exit;
}

class Bitcoin_Donation_Metabox
{
	public function __construct()
	{
		// Register custom post type
		add_action('init', [$this, 'register_shoutouts_post_type']);
		add_action('init', [$this, 'register_custom_meta_fields']);

		// Add meta boxes
		add_action('add_meta_boxes', [$this, 'add_shoutouts_metaboxes']);

		// Save meta box data
		add_action('save_post', [$this, 'save_shoutouts_meta'], 10, 2);

		// Add custom columns to admin list
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
		register_meta('post', '_bitcoin_donation_shoutouts_name', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);

		register_meta('post', '_bitcoin_donation_shoutouts_amount', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'number',
			'single' => true,
			'show_in_rest' => true,
		]);

		register_meta('post', '_bitcoin_donation_shoutouts_invoice_id', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);

		register_meta('post', '_bitcoin_donation_shoutouts_message', [
			'object_subtype' => 'bitcoin-shoutouts',
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		]);
	}

	public function add_shoutouts_metaboxes()
	{
		add_meta_box(
			'bitcoin_donation_shoutouts_details',
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
		wp_nonce_field('bitcoin_donation_shoutouts_nonce', 'bitcoin_donation_shoutouts_nonce');

		// Retrieve existing meta values
		$name = get_post_meta($post->ID, '_bitcoin_donation_shoutouts_name', true);
		$message = get_post_meta($post->ID, '_bitcoin_donation_shoutouts_message', true);
		$amount = get_post_meta($post->ID, '_bitcoin_donation_shoutouts_amount', true);
		$invoice_id = get_post_meta($post->ID, '_bitcoin_donation_shoutouts_invoice_id', true);
		error_log($name);
?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="bitcoin_donation_shoutouts_name"><?php echo esc_html_e('Name', 'bitcoin-donation-shoutouts') ?></label>
				</th>
				<td>
					<input
						type="text"
						id="bitcoin_donation_shoutouts_name"
						name="bitcoin_donation_shoutouts_name"
						class="regular-text"
						value="<?php echo esc_attr($name); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bitcoin_donation_shoutouts_amount"><?php echo esc_html_e('Amount', 'bitcoin-donation-shoutouts') ?></label>
				</th>
				<td>
					<input
						type="number"
						id="bitcoin_donation_shoutouts_amount"
						name="bitcoin_donation_shoutouts_amount"
						class="regular-text"
						step="0.01"
						min="0"
						value="<?php echo esc_attr($amount); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bitcoin_donation_shoutouts_invoice_id"><?php echo esc_html_e('Invoice Id', 'bitcoin-donation-shoutouts') ?></label>
				</th>
				<td>
					<input
						type="text"
						id="bitcoin_donation_shoutouts_invoice_id"
						name="bitcoin_donation_shoutouts_invoice_id"
						class="regular-text"
						value="<?php echo esc_attr($invoice_id); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bitcoin_donation_shoutouts_message"><?php echo esc_html_e('Message', 'bitcoin-donation-shoutouts') ?></label>
				</th>
				<td>
					<input
						type="text"
						id="bitcoin_donation_shoutouts_message"
						name="bitcoin_donation_shoutouts_message"
						class="regular-text"
						value="<?php echo esc_attr($message); ?>">
				</td>
			</tr>
		</table>
<?php
	}

	public function save_shoutouts_meta($post_id, $post)
	{
		// if (defined('REST_REQUEST') && REST_REQUEST) return;

		// Check if this is an autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		error_log("START");
		// Check nonce for security
		// if (
		// 	null === filter_input(INPUT_POST, 'bitcoin_donation_shoutouts_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ||
		// 	!wp_verify_nonce(filter_input(INPUT_POST, 'bitcoin_donation_shoutouts_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'bitcoin_donation_shoutouts_nonce')
		// ) {
		// 	return;
		// }

		// // Check user permissions
		// if (!current_user_can('edit_post', $post_id)) {
		// 	return;
		// }

		// // Check post type
		// if ($post->post_type !== 'bitcoin-shoutouts') {
		// 	return;
		// }

		// Sanitize and save meta fields
		$meta_fields = [
			'name'       => 'bitcoin_donation_shoutouts_name',
			'amount'     => 'bitcoin_donation_shoutouts_amount',
			'invoice_id' => 'bitcoin_donation_shoutouts_invoice_id',
			'message'    => 'bitcoin_donation_shoutouts_message'
		];

		$meta_fields_types = [
			$meta_fields['name']        => 'FILTER_SANITIZE_FULL_SPECIAL_CHARS',
			$meta_fields['amount']      => 'FILTER_SANITIZE_FULL_SPECIAL_CHARS',
			$meta_fields['invoice_id']  => 'FILTER_SANITIZE_FULL_SPECIAL_CHARS',
			$meta_fields['message']  => 'FILTER_SANITIZE_FULL_SPECIAL_CHARS'

		];

		$post_array_filtered = filter_input_array(INPUT_POST, $meta_fields_types);
		error_log(var_export($post_array_filtered, true));
		foreach ($meta_fields as $key => $field) {
			if (isset($post_array_filtered[$field])) {
				$value = match ($key) {
					'name' 		 => sanitize_textarea_field($post_array_filtered[$field]),
					'amount'     => sanitize_text_field($post_array_filtered[$field]),
					'invoice_id' => sanitize_text_field($post_array_filtered[$field]),
					'message' => sanitize_text_field($post_array_filtered[$field]),
					default      => sanitize_text_field($post_array_filtered[$field])
				};

				update_post_meta($post_id, '_' . $field, $value);
			}
		}
	}

	public function add_custom_columns($columns)
	{
		$new_columns = [];
		foreach ($columns as $key => $title) {
			$new_columns[$key] = $title;
			if ($key === 'title') {
				$new_columns['name'] = 'Name';
				$new_columns['amount'] = 'Amount';
				$new_columns['invoice_id'] = 'Invoice id';
				$new_columns['message'] = 'Message';
			}
		}
		return $new_columns;
	}

	public function populate_custom_columns($column, $post_id)
	{
		switch ($column) {
			case 'name':
				echo esc_html(get_post_meta($post_id, '_bitcoin_donation_shoutouts_name', true) ?: 'Anonymous');
				break;
			case 'amount':
				echo esc_html(get_post_meta($post_id, '_bitcoin_donation_shoutouts_amount', true) ?: '0');
				break;
			case 'invoice_id':
				echo esc_html(get_post_meta($post_id, '_bitcoin_donation_shoutouts_invoice_id', true) ?: '');
				break;
			case 'message':
				echo esc_html(get_post_meta($post_id, '_bitcoin_donation_shoutouts_message', true) ?: '');
				break;
		}
	}
}

// Initialize the class
new Bitcoin_Donation_Metabox();
