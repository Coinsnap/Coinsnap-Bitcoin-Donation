<?php
class Bitcoin_Donation_Forms
{

	public function __construct()
	{
		add_action('admin_init', [$this, 'bitcoin_donation_forms_settings_init']);
	}

	function bitcoin_donation_forms_settings_init()
	{
		register_setting('bitcoin_donation_forms_settings', 'bitcoin_donation_forms_options', [
			'type'              => 'array',
			'sanitize_callback' => [$this, 'sanitize_forms_options']
		]);

		// Simple Donation Section
		add_settings_section(
			'bitcoin_donation_simple_donation_section',
			'Simple Donation Settings',
			[$this, 'simple_donation_section_callback'],
			'bitcoin_donation'
		);

		add_settings_field(
			'simple_donation_active',
			'Active',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'simple_donation_active',
				'type'      => 'checkbox'
			]
		);

		add_settings_field(
			'currency',
			'Currency',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'currency',
				'type'      => 'select',
				'options'   => [
					"EUR" => "EUR",
					"USD" => "USD",
					"CAD" => "CAD",
					"JPY" => "JPY",
					"GBP" => "GBP",
					"CHF" => "CHF"
				]
			]
		);

		add_settings_field(
			'button_text',
			'Button Text',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'button_text',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'title_text',
			'Title Text',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'title_text',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'default_amount',
			'Default Amount in Fiat',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'default_amount',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'default_message',
			'Default Message',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'default_message',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'redirect_url',
			'Redirect Url (Thank You Page)',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'redirect_url',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'simple_donation_public_donors',
			'Public Donors',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'simple_donation_public_donors',
				'type'      => 'checkbox'
			]
		);

		add_settings_field(
			'simple_donation_first_name',
			'First Name',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'simple_donation_first_name',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field simple-donation'
			]
		);

		add_settings_field(
			'simple_donation_last_name',
			'Last Name',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'simple_donation_last_name',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field simple-donation'
			]
		);

		add_settings_field(
			'simple_donation_email',
			'Email',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'simple_donation_email',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field simple-donation'
			]
		);

		add_settings_field(
			'simple_donation_address',
			'Address',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'simple_donation_address',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field simple-donation'
			]
		);

		//Shoutout Section
		add_settings_section(
			'bitcoin_donation_shoutout_donation_section',
			'Shoutout Donation Settings',
			[$this, 'shoutout_donation_section_callback'],
			'bitcoin_donation'
		);

		add_settings_field(
			'shoutout_donation_active',
			'Active',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_donation_active',
				'type'      => 'checkbox'
			]
		);

		add_settings_field(
			'shoutout_currency',
			'Currency',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_currency',
				'type'      => 'select',
				'options'   => [
					"EUR" => "EUR",
					"USD" => "USD",
					"CAD" => "CAD",
					"JPY" => "JPY",
					"GBP" => "GBP",
					"CHF" => "CHF"
				]
			]
		);

		add_settings_field(
			'shoutout_button_text',
			'Button Text',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_button_text',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'shoutout_title_text',
			'Title Text',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_title_text',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'shoutout_default_amount',
			'Default Amount in Fiat',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_default_amount',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'shoutout_default_message',
			'Default Message',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_default_message',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'shoutout_minimum_amount',
			'Minimum Shoutout Amount in sats',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_minimum_amount',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'shoutout_premium_amount',
			'Premium Shoutout Amount in sats',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_premium_amount',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'shoutout_redirect_url',
			'Shoutout List Page Url',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_redirect_url',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'shoutout_public_donors',
			'Public Donors',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_public_donors',
				'type'      => 'checkbox'
			]
		);

		add_settings_field(
			'shoutout_first_name',
			'First Name',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_first_name',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field shoutout'
			]
		);

		add_settings_field(
			'shoutout_last_name',
			'Last Name',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_last_name',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field shoutout'
			]
		);

		add_settings_field(
			'shoutout_email',
			'Email',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_email',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field shoutout'
			]
		);

		add_settings_field(
			'shoutout_address',
			'Address',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_address',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field shoutout'
			]
		);

		// Multi Amount
		add_settings_section(
			'bitcoin_donation_multi_amount_section',
			'Multi Amount Donation Settings',
			[$this, 'multi_amount_section_callback'],
			'bitcoin_donation'
		);

		add_settings_field(
			'multi_amount_donation_active',
			'Active',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_donation_active',
				'type'      => 'checkbox'
			]
		);

		add_settings_field(
			'multi_amount_primary_currency',
			'Primary Currency',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_primary_currency',
				'type'      => 'select',
				'options'   => [
					"SATS" => "SATS",
					"FIAT" => "FIAT"
				]
			]
		);

		add_settings_field(
			'multi_amount_fiat_currency',
			'Fiat Currency',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_fiat_currency',
				'type'      => 'select',
				'options'   => [
					"EUR" => "EUR",
					"USD" => "USD",
					"CAD" => "CAD",
					"JPY" => "JPY",
					"GBP" => "GBP",
					"CHF" => "CHF"
				]
			]
		);

		add_settings_field(
			'multi_amount_button_text',
			'Button Text',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_button_text',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'multi_amount_title_text',
			'Title Text',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_title_text',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'multi_amount_default_amount',
			'Default Amount in Primary Curency',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_default_amount',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'multi_amount_default_message',
			'Default Message',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_default_message',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'multi_amount_redirect_url',
			'Redirect Url (Thank You Page)',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_redirect_url',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'multi_amount_default_snap1',
			'Default Amount Field 1',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_default_snap1',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'multi_amount_default_snap2',
			'Default Amount Field 2',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_default_snap2',
				'type'      => 'text',
				'required' => true
			]
		);

		add_settings_field(
			'multi_amount_default_snap3',
			'Default Amount Field 3',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_default_snap3',
				'type'      => 'text',
				'required' => true
			]
		);

		// Add public donors checkbox for multi amount
		add_settings_field(
			'multi_amount_public_donors',
			'Public Donors',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_public_donors',
				'type'      => 'checkbox'
			]
		);

		add_settings_field(
			'multi_amount_first_name',
			'First Name',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_first_name',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field multi-amount'
			]
		);
		add_settings_field(
			'multi_amount_last_name',
			'Last Name',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_last_name',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field multi-amount'
			]
		);
		add_settings_field(
			'multi_amount_email',
			'Email',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_email',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field multi-amount'
			]
		);
		add_settings_field(
			'multi_amount_address',
			'Address',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_address',
				'type'      => 'select',
				'options'   => [
					'optional' => 'Optional',
					'mandatory' => 'Mandatory'
				],
				'class' => 'public-donor-field multi-amount'
			]
		);
	}

	public function sanitize_forms_options($options)
	{
		$sanitized = [];
		
		$sections = ['simple_donation', 'shoutout', 'multi_amount'];
		$public_donor_fields = ['first_name', 'last_name', 'email', 'address'];
		$simple_donation_fields = ['currency', 'button_text', 'title_text', 'default_amount', 'default_message', 'redirect_url'];
		$shoutout_fields = ['currency', 'button_text', 'title_text', 'default_amount', 'default_message', 'minimum_amount', 'premium_amount', 'redirect_url'];
		$multi_amount_fields = ['primary_currency', 'fiat_currency', 'button_text', 'title_text', 'default_amount', 'default_message', 'redirect_url', 'default_snap1', 'default_snap2', 'default_snap3'];

		foreach ($simple_donation_fields as $field) {
			$field_name = "{$field}";
			if (isset($options[$field_name])) {
				$sanitized[$field_name] = sanitize_text_field($options[$field_name]);
			}
		}

		foreach ($shoutout_fields as $field) {
			$field_name = "shoutout_{$field}";
			if (isset($options[$field_name])) {
				$sanitized[$field_name] = sanitize_text_field($options[$field_name]);
			}
		}

		foreach ($multi_amount_fields as $field) {
			$field_name = "multi_amount_{$field}";
			if (isset($options[$field_name])) {
				$sanitized[$field_name] = sanitize_text_field($options[$field_name]);
			}
		}

		foreach ($sections as $section) {
			foreach ($public_donor_fields as $field) {
				$field_name = "{$section}_{$field}";
				if (isset($options[$field_name])) {
					$sanitized[$field_name] = sanitize_text_field($options[$field_name]);
				}
			}
		}

		$sanitized['simple_donation_public_donors'] = isset($options['simple_donation_public_donors']) ? true : false;
		$sanitized['simple_donation_active'] = isset($options['simple_donation_active']) ? true : false;
		$sanitized['shoutout_public_donors'] = isset($options['shoutout_public_donors']) ? true : false;
		$sanitized['shoutout_donation_active'] = isset($options['shoutout_donation_active']) ? true : false;
		$sanitized['multi_amount_public_donors'] = isset($options['multi_amount_public_donors']) ? true : false;
		$sanitized['multi_amount_donation_active'] = isset($options['multi_amount_donation_active']) ? true : false;

		return $sanitized;
	}

	private function render_shortcode_row($name, $shortcode)
	{
		echo "<tr>";
		echo "<th>";
		echo $name;
		echo '</th>';
		echo "<td>";
		echo "<input type='text' id='shortcode' name='shortcode' class='regular-text' readonly value='[$shortcode]'>";
		echo '</td>';
		echo '</tr>';
	}

	private function render_shortcode_section($section_id)
	{
		if ($section_id == 'bitcoin_donation_simple_donation_section') {
			$this->render_shortcode_row('Shortcode narrow form', 'bitcoin_donation');
			$this->render_shortcode_row('Shortcode wide form', 'bitcoin_donation_wide');
		} elseif ($section_id == 'bitcoin_donation_shoutout_donation_section') {
			$this->render_shortcode_row('Shortcode form', 'shoutout_form');
			$this->render_shortcode_row('Shortcode list', 'shoutout_list');
		} elseif ($section_id == 'bitcoin_donation_multi_amount_section') {
			$this->render_shortcode_row('Shortcode narrow form', 'multi_amount_donation');
			$this->render_shortcode_row('Shortcode wide form', 'multi_amount_donation_wide');
		}
	}

	/**
	 * Renders a specific settings section manually.
	 *
	 * @param string $section_id The ID of the section to render.
	 */
	private function render_section($section_id)
	{
		global $wp_settings_sections, $wp_settings_fields;
		if (! isset($wp_settings_sections['bitcoin_donation'][$section_id])) {
			return;
		}

		$section = $wp_settings_sections['bitcoin_donation'][$section_id];

		if ($section['title']) {
			echo '<h3>' . esc_html($section['title']) . '</h3>';
		}
		if ($section['callback']) {
			call_user_func($section['callback'], $section);
		}

		if (! empty($wp_settings_fields['bitcoin_donation'][$section_id])) {
			echo '<table class="form-table">';
			do_settings_fields('bitcoin_donation', $section_id);
			$this->render_shortcode_section($section_id);
			echo '</table>';
		}
	}

	public function simple_donation_section_callback()
	{
		echo esc_html_e('Configure simple donation form.', 'bitcoin_donation');
	}

	public function shoutout_donation_section_callback()
	{
		echo esc_html_e('Configure shoutout donation form.', 'bitcoin_donation');
	}

	public function multi_amount_section_callback()
	{
		echo esc_html_e('Configure multi amount donation form.', 'bitcoin_donation');
	}

	public function render_field($args)
	{
		$options     = get_option('bitcoin_donation_forms_options', []);
		$field_id    = $args['label_for'];
		$field_type  = $args['type'];
		$field_value = isset($options[$field_id]) ? $options[$field_id] : '';
		$defaults = [
			'default_message' => 'Thank you for your work',
			'default_amount'  => '5',
			'button_text'     => 'Donate',
			'title_text'      => 'Donate with Bitcoin',
			'shoutout_default_message' => 'Thank you!',
			'shoutout_default_amount'  => '5',
			'shoutout_button_text'     => 'Shoutout',
			'shoutout_title_text'      => 'Bitcoin Shoutouts',
			'shoutout_minimum_amount'  => '21',
			'shoutout_premium_amount'  => '21000',
			'multi_amount_default_message' => 'Thank you for your work',
			'multi_amount_default_amount'  => '5',
			'multi_amount_button_text'     => 'Donate',
			'multi_amount_title_text'      => 'Donate with Bitcoin',
			'multi_amount_default_snap1'      => '1000',
			'multi_amount_default_snap2'      => '5000',
			'multi_amount_default_snap3'      => '10000',
			'simple_donation_active' => true,
			'shoutout_donation_active' => true,
			'multi_amount_donation_active' => true,
		];
		if ($field_type == 'text') {
			$field_value = isset($options[$field_id]) ? $options[$field_id] : ($defaults[$field_id] ?? '');
		}
		switch ($field_type) {
			case 'select':
				echo '<select 
                id="' . esc_attr($field_id) . '" 
                name="bitcoin_donation_forms_options[' . esc_attr($field_id) . ']"
                class="regular-text">';
				foreach ($args['options'] as $value => $label) {
					echo '<option value="' . esc_attr($value) . '"' .
						selected($field_value, $value, false) . '>' .
						esc_html($label) . '</option>';
				}
				echo '</select>';
				break;

			case 'check_connection':
				$id = isset($args['id']) ? $args['id'] : 'check_connection';

				echo '<div >' . '<button id="' . esc_attr($id) . '_button">Check</button>' . '<span style="" id="' . esc_attr($id) .  '">' . '</span>' . '</div>';
				break;

			case 'text':
				echo '<input type="text" 
                id="' . esc_attr($field_id) . '" 
                name="bitcoin_donation_forms_options[' . esc_attr($field_id) . ']" 
                value="' . esc_attr($field_value) . '" 
                class="regular-text"' .
					(isset($args['readonly']) && $args['readonly'] ? ' readonly' : '') .
					(isset($args['value']) ? ' value="' . esc_attr($args['value']) . '"' : '') .
					(isset($args['required']) && $args['required'] ? ' required' : '') .
					'>';
				break;

			case 'checkbox':
				$checked = isset($options[$field_id]) ? $options[$field_id] : ($defaults[$field_id] ?? false);
				echo '<input type="checkbox" 
                    id="' . esc_attr($field_id) . '" 
                    name="bitcoin_donation_forms_options[' . esc_attr($field_id) . ']" 
                    value="1"' .
					checked($checked, true, false) . '>';
				break;
		}

		if (isset($args['description'])) {
			echo '<p class="description">' . esc_html($args['description']) . '</p>';
		}
	}

	public function render_donation_forms_page()
	{
?>
		<div class="wrap">
			<h1>Bitcoin Donation Settings</h1>

			<!-- Display any registered settings errors -->
			<?php settings_errors('bitcoin_donation_settings'); ?>

			<!-- Tab Navigation -->
			<h2 class="nav-tab-wrapper">
				<a href="#coinsnap" class="nav-tab" data-tab="simple-donation">Donation Button</a>
				<a href="#btcpay" class="nav-tab" data-tab="shoutout-donation">Shoutout Donation</a>
				<a href="#multi" class="nav-tab" data-tab="multi-amount-donation">Multi Amount Donation</a>
			</h2>

			<form method="post" action="options.php">
				<?php

				// Render the settings fields for the Bitcoin Donation
				settings_fields('bitcoin_donation_forms_settings');

				echo '<div id="simple-donation" class="tab-content">';
				$this->render_section('bitcoin_donation_simple_donation_section');
				echo '</div>';
				echo '<div id="shoutout-donation" class="tab-content">';
				$this->render_section('bitcoin_donation_shoutout_donation_section');
				echo '</div>';
				echo '<div id="multi-amount-donation" class="tab-content">';
				$this->render_section('bitcoin_donation_multi_amount_section');
				echo '</div>';
				?>
				<?php
				// Render submit button
				submit_button();
				?>
			</form>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				function togglePublicDonorFields(section) {
					var isChecked = $('#' + section + '_public_donors').is(':checked');
					$('.public-donor-field.' + section.replace(/_/g, '-')).closest('tr').toggle(isChecked);
				}

				// Initial state
				togglePublicDonorFields('simple_donation');
				togglePublicDonorFields('shoutout');
				togglePublicDonorFields('multi_amount');

				// Change handlers
				$('#simple_donation_public_donors').change(function() {
					togglePublicDonorFields('simple_donation');
				});
				$('#shoutout_public_donors').change(function() {
					togglePublicDonorFields('shoutout');
				});
				$('#multi_amount_public_donors').change(function() {
					togglePublicDonorFields('multi_amount');
				});
			});
		</script>
<?php
	}
}
