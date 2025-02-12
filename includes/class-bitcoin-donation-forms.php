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
			'theme',
			'Theme',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_simple_donation_section',
			[
				'label_for' => 'theme',
				'type'      => 'select',
				'options'   => [
					"light" => "Light",
					"dark" => "Dark"
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
				'type'      => 'text'
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
				'type'      => 'text'
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
				'type'      => 'text'
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

		//Shoutout Section
		add_settings_section(
			'bitcoin_donation_shoutout_donation_section',
			'Shoutout Donation Settings',
			[$this, 'shoutout_donation_section_callback'],
			'bitcoin_donation'
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
			'shoutout_theme',
			'Theme',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_shoutout_donation_section',
			[
				'label_for' => 'shoutout_theme',
				'type'      => 'select',
				'options'   => [
					"light" => "Light",
					"dark" => "Dark"
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
				'type'      => 'text'
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
				'type'      => 'text'
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
				'type'      => 'text'
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
				'type'      => 'text'
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
				'type'      => 'text'
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

		// Multi Amount
		add_settings_section(
			'bitcoin_donation_multi_amount_section',
			'Multi Amount Donation Settings',
			[$this, 'multi_amount_section_callback'],
			'bitcoin_donation'
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
			'multi_amount_theme',
			'Theme',
			[$this, 'render_field'],
			'bitcoin_donation',
			'bitcoin_donation_multi_amount_section',
			[
				'label_for' => 'multi_amount_theme',
				'type'      => 'select',
				'options'   => [
					"light" => "Light",
					"dark" => "Dark"
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
				'type'      => 'text'
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
				'type'      => 'text'
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
				'type'      => 'text'
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
				'type'      => 'text'
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
				'type'      => 'text'
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
				'type'      => 'text'
			]
		);
	}

	public function sanitize_forms_options($options)
	{
		$sanitized = [];

		if (isset($options['currency'])) {
			$sanitized['currency'] = sanitize_text_field($options['currency']);
		}

		if (isset($options['theme'])) {
			$sanitized['theme'] = sanitize_text_field($options['theme']);
		}

		if (isset($options['button_text'])) {
			$sanitized['button_text'] = sanitize_text_field($options['button_text']);
		}

		if (isset($options['title_text'])) {
			$sanitized['title_text'] = sanitize_text_field($options['title_text']);
		}

		if (isset($options['default_amount'])) {
			$sanitized['default_amount'] = sanitize_text_field($options['default_amount']);
		}

		if (isset($options['default_message'])) {
			$sanitized['default_message'] = sanitize_text_field($options['default_message']);
		}

		if (isset($options['redirect_url'])) {
			$sanitized['redirect_url'] = sanitize_text_field($options['redirect_url']);
		}

		if (isset($options['shoutout_currency'])) {
			$sanitized['shoutout_currency'] = sanitize_text_field($options['shoutout_currency']);
		}

		if (isset($options['shoutout_theme'])) {
			$sanitized['shoutout_theme'] = sanitize_text_field($options['shoutout_theme']);
		}

		if (isset($options['shoutout_button_text'])) {
			$sanitized['shoutout_button_text'] = sanitize_text_field($options['shoutout_button_text']);
		}

		if (isset($options['shoutout_title_text'])) {
			$sanitized['shoutout_title_text'] = sanitize_text_field($options['shoutout_title_text']);
		}

		if (isset($options['shoutout_default_amount'])) {
			$sanitized['shoutout_default_amount'] = sanitize_text_field($options['shoutout_default_amount']);
		}

		if (isset($options['shoutout_default_message'])) {
			$sanitized['shoutout_default_message'] = sanitize_text_field($options['shoutout_default_message']);
		}

		if (isset($options['shoutout_minimum_amount'])) {
			$sanitized['shoutout_minimum_amount'] = sanitize_text_field($options['shoutout_minimum_amount']);
		}

		if (isset($options['shoutout_premium_amount'])) {
			$sanitized['shoutout_premium_amount'] = sanitize_text_field($options['shoutout_premium_amount']);
		}

		if (isset($options['shoutout_redirect_url'])) {
			$sanitized['shoutout_redirect_url'] = sanitize_text_field($options['shoutout_redirect_url']);
		}

		if (isset($options['multi_amount_primary_currency'])) {
			$sanitized['multi_amount_primary_currency'] = sanitize_text_field($options['multi_amount_primary_currency']);
		}

		if (isset($options['multi_amount_fiat_currency'])) {
			$sanitized['multi_amount_fiat_currency'] = sanitize_text_field($options['multi_amount_fiat_currency']);
		}

		if (isset($options['multi_amount_theme'])) {
			$sanitized['multi_amount_theme'] = sanitize_text_field($options['multi_amount_theme']);
		}

		if (isset($options['multi_amount_button_text'])) {
			$sanitized['multi_amount_button_text'] = sanitize_text_field($options['multi_amount_button_text']);
		}

		if (isset($options['multi_amount_title_text'])) {
			$sanitized['multi_amount_title_text'] = sanitize_text_field($options['multi_amount_title_text']);
		}

		if (isset($options['multi_amount_default_amount'])) {
			$sanitized['multi_amount_default_amount'] = sanitize_text_field($options['multi_amount_default_amount']);
		}

		if (isset($options['multi_amount_default_message'])) {
			$sanitized['multi_amount_default_message'] = sanitize_text_field($options['multi_amount_default_message']);
		}

		if (isset($options['multi_amount_redirect_url'])) {
			$sanitized['multi_amount_redirect_url'] = sanitize_text_field($options['multi_amount_redirect_url']);
		}

		if (isset($options['multi_amount_default_snap1'])) {
			$sanitized['multi_amount_default_snap1'] = sanitize_text_field($options['multi_amount_default_snap1']);
		}

		if (isset($options['multi_amount_default_snap2'])) {
			$sanitized['multi_amount_default_snap2'] = sanitize_text_field($options['multi_amount_default_snap2']);
		}

		if (isset($options['multi_amount_default_snap3'])) {
			$sanitized['multi_amount_default_snap3'] = sanitize_text_field($options['multi_amount_default_snap3']);
		}

		return $sanitized;
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


				break;

			case 'text':
				echo '<input type="text" 
                id="' . esc_attr($field_id) . '" 
                name="bitcoin_donation_forms_options[' . esc_attr($field_id) . ']" 
                value="' . esc_attr($field_value) . '" 
                class="regular-text"' .
					(isset($args['readonly']) && $args['readonly'] ? ' readonly' : '') .
					(isset($args['value']) ? ' value="' . esc_attr($args['value']) . '"' : '') .
					'>';
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
			<div id='simple_donation_shortcodes' class='shortcode_text_wrapper'>Use the shortcode <span class='shortcode_text'>[bitcoin_donation]</span> or <span class='shortcode_text'> [bitcoin_donation_wide]</span></div>
			<div id='shoutout_donation_shortcodes' class='shortcode_text_wrapper shortcode_text_wrapper_disabled'>Use the shortcodes <span class='shortcode_text'>[shoutout_form]</span> and <span class='shortcode_text'> [shoutout_list]</span></div>
			<div id='multi_amount_shortcodes' class='shortcode_text_wrapper shortcode_text_wrapper_disabled'>Use the shortcodes <span class='shortcode_text'>[multi_amount_donation]</span> or <span class='shortcode_text'> [multi_amount_donation_wide]</span></div>

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
<?php
	}
}
