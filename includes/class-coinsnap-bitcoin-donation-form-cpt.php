<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Coinsnap_Bitcoin_Donation_Form_CPT {

	const POST_TYPE   = 'donation-form';
	const META_PREFIX = '_coinsnap_donation_form_';

	public function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'init', array( $this, 'register_meta_fields' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'parent_file', array( $this, 'fix_parent_menu' ) );
		add_filter( 'submenu_file', array( $this, 'fix_submenu_highlight' ) );
		add_action( 'admin_footer', array( $this, 'render_empty_state' ) );
		add_action( 'load-edit.php', array( $this, 'maybe_create_default_forms' ) );
	}

	public function register_cpt() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name'               => __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
					'singular_name'      => __( 'Donation Form', 'coinsnap-bitcoin-donation' ),
					'menu_name'          => __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
					'add_new'            => __( 'Add New', 'coinsnap-bitcoin-donation' ),
					'add_new_item'       => __( 'Add New Donation Form', 'coinsnap-bitcoin-donation' ),
					'edit_item'          => __( 'Edit Donation Form', 'coinsnap-bitcoin-donation' ),
					'new_item'           => __( 'New Donation Form', 'coinsnap-bitcoin-donation' ),
					'view_item'          => __( 'View Donation Form', 'coinsnap-bitcoin-donation' ),
					'search_items'       => __( 'Search Donation Forms', 'coinsnap-bitcoin-donation' ),
					'not_found'          => __( 'No donation forms found', 'coinsnap-bitcoin-donation' ),
					'not_found_in_trash' => __( 'No donation forms found in Trash', 'coinsnap-bitcoin-donation' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'supports'           => array( 'title' ),
				'show_in_rest'       => true,
			)
		);
	}

	public function register_meta_fields() {
		$string_fields = array(
			'form_type',
			'layout',
			'currency',
			'button_text',
			'title_text',
			'default_amount',
			'default_message',
			'redirect_url',
			'first_name',
			'last_name',
			'email',
			'address',
			'custom_field_name',
			'custom_field_visibility',
			'donor_notice',
			'custom_checkbox_label',
			'snap1',
			'snap2',
			'snap3',
			'minimum_amount',
			'premium_amount',
		);

		foreach ( $string_fields as $field ) {
			register_post_meta(
				self::POST_TYPE,
				self::META_PREFIX . $field,
				array(
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => '',
				)
			);
		}

		// public_donors stored as '1' or '' (not boolean)
		register_post_meta(
			self::POST_TYPE,
			self::META_PREFIX . 'public_donors',
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => '',
			)
		);
	}

	public function add_metaboxes() {
		add_meta_box(
			'coinsnap_donation_form_settings',
			__( 'Form Settings', 'coinsnap-bitcoin-donation' ),
			array( $this, 'render_metabox' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render_metabox( $post ) {
		wp_nonce_field( 'coinsnap_donation_form_nonce', 'coinsnap_donation_form_nonce' );

		$currencies = defined( 'COINSNAP_CURRENCIES' ) ? COINSNAP_CURRENCIES : array( 'EUR', 'USD', 'SATS', 'BTC', 'CAD', 'JPY', 'GBP', 'CHF', 'RUB' );

		// Retrieve all meta values with defaults.
		$meta = array(
			'form_type'               => get_post_meta( $post->ID, self::META_PREFIX . 'form_type', true ),
			'layout'                  => get_post_meta( $post->ID, self::META_PREFIX . 'layout', true ),
			'currency'                => get_post_meta( $post->ID, self::META_PREFIX . 'currency', true ),
			'button_text'             => get_post_meta( $post->ID, self::META_PREFIX . 'button_text', true ),
			'title_text'              => get_post_meta( $post->ID, self::META_PREFIX . 'title_text', true ),
			'default_amount'          => get_post_meta( $post->ID, self::META_PREFIX . 'default_amount', true ),
			'default_message'         => get_post_meta( $post->ID, self::META_PREFIX . 'default_message', true ),
			'redirect_url'            => get_post_meta( $post->ID, self::META_PREFIX . 'redirect_url', true ),
			'snap1'                   => get_post_meta( $post->ID, self::META_PREFIX . 'snap1', true ),
			'snap2'                   => get_post_meta( $post->ID, self::META_PREFIX . 'snap2', true ),
			'snap3'                   => get_post_meta( $post->ID, self::META_PREFIX . 'snap3', true ),
			'minimum_amount'          => get_post_meta( $post->ID, self::META_PREFIX . 'minimum_amount', true ),
			'premium_amount'          => get_post_meta( $post->ID, self::META_PREFIX . 'premium_amount', true ),
			'public_donors'           => get_post_meta( $post->ID, self::META_PREFIX . 'public_donors', true ),
			'first_name'              => get_post_meta( $post->ID, self::META_PREFIX . 'first_name', true ),
			'last_name'               => get_post_meta( $post->ID, self::META_PREFIX . 'last_name', true ),
			'email'                   => get_post_meta( $post->ID, self::META_PREFIX . 'email', true ),
			'address'                 => get_post_meta( $post->ID, self::META_PREFIX . 'address', true ),
			'custom_field_name'       => get_post_meta( $post->ID, self::META_PREFIX . 'custom_field_name', true ),
			'custom_field_visibility' => get_post_meta( $post->ID, self::META_PREFIX . 'custom_field_visibility', true ),
			'donor_notice'            => get_post_meta( $post->ID, self::META_PREFIX . 'donor_notice', true ),
			'custom_checkbox_label'   => get_post_meta( $post->ID, self::META_PREFIX . 'custom_checkbox_label', true ),
		);

		$is_new = get_post_status( $post->ID ) === 'auto-draft';

		$form_type = ! empty( $meta['form_type'] ) ? $meta['form_type'] : 'simple_donation';
		$layout    = ! empty( $meta['layout'] ) ? $meta['layout'] : 'NARROW';
		$currency  = ! empty( $meta['currency'] ) ? $meta['currency'] : 'EUR';

		// Translatable defaults — used as placeholders and pre-filled on new posts
		$defaults = array(
			'button_text'     => __( 'Donate', 'coinsnap-bitcoin-donation' ),
			'title_text'      => __( 'Donate with Bitcoin', 'coinsnap-bitcoin-donation' ),
			'default_amount'  => '5',
			'default_message' => __( 'Thank you for your support!', 'coinsnap-bitcoin-donation' ),
			'redirect_url'    => home_url(),
			'snap1'           => '50',
			'snap2'           => '100',
			'snap3'           => '200',
			'minimum_amount'  => '500',
			'premium_amount'  => '10000',
		);

		// Pre-fill values for new posts
		if ( $is_new ) {
			foreach ( $defaults as $key => $default ) {
				if ( empty( $meta[ $key ] ) ) {
					$meta[ $key ] = $default;
				}
			}
		}

		$visibility_options = array(
			'optional'  => __( 'Optional', 'coinsnap-bitcoin-donation' ),
			'mandatory' => __( 'Mandatory', 'coinsnap-bitcoin-donation' ),
			'hidden'    => __( 'Hidden', 'coinsnap-bitcoin-donation' ),
		);
		?>

		<!-- Form Type Card Picker -->
		<div class="donation-form-type-cards">
			<div class="donation-form-type-card <?php echo $form_type === 'simple_donation' ? 'active' : ''; ?>" data-type="simple_donation">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
				<h4><?php esc_html_e( 'Simple Donation', 'coinsnap-bitcoin-donation' ); ?></h4>
				<p><?php esc_html_e( 'A simple donation button with a fixed default amount', 'coinsnap-bitcoin-donation' ); ?></p>
			</div>
			<div class="donation-form-type-card <?php echo $form_type === 'multi_amount' ? 'active' : ''; ?>" data-type="multi_amount">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect></svg>
				<h4><?php esc_html_e( 'Multi Amount', 'coinsnap-bitcoin-donation' ); ?></h4>
				<p><?php esc_html_e( 'Preset amount buttons for quick selection', 'coinsnap-bitcoin-donation' ); ?></p>
			</div>
			<div class="donation-form-type-card <?php echo $form_type === 'shoutout' ? 'active' : ''; ?>" data-type="shoutout">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 18.5L3 22V7l9-4 9 4v15l-9-3.5z"></path><path d="M12 2v16.5"></path><path d="M18 7.5L6 12.5"></path></svg>
				<h4><?php esc_html_e( 'Shoutout', 'coinsnap-bitcoin-donation' ); ?></h4>
				<p><?php esc_html_e( 'Donors leave public messages with their donation', 'coinsnap-bitcoin-donation' ); ?></p>
			</div>
		</div>
		<input type="hidden" name="<?php echo esc_attr( self::META_PREFIX . 'form_type' ); ?>" id="<?php echo esc_attr( self::META_PREFIX . 'form_type' ); ?>" value="<?php echo esc_attr( $form_type ); ?>">

		<!-- Shared Fields -->
		<div class="csc-card">
			<div class="csc-card-header">
				<h2><?php esc_html_e( 'General Settings', 'coinsnap-bitcoin-donation' ); ?></h2>
			</div>
			<div class="csc-card-body">
				<div class="csc-field-row">
					<label for="<?php echo esc_attr( self::META_PREFIX . 'currency' ); ?>"><?php esc_html_e( 'Currency', 'coinsnap-bitcoin-donation' ); ?></label>
					<div class="csc-field-input">
						<select id="<?php echo esc_attr( self::META_PREFIX . 'currency' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'currency' ); ?>" class="regular-text">
							<?php foreach ( $currencies as $c ) : ?>
								<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $currency, $c ); ?>><?php echo esc_html( $c ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="csc-field-row">
					<label for="<?php echo esc_attr( self::META_PREFIX . 'button_text' ); ?>"><?php esc_html_e( 'Button Text', 'coinsnap-bitcoin-donation' ); ?></label>
					<div class="csc-field-input">
						<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'button_text' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'button_text' ); ?>" value="<?php echo esc_attr( $meta['button_text'] ); ?>" placeholder="<?php echo esc_attr( $defaults['button_text'] ); ?>" class="regular-text">
					</div>
				</div>
				<div class="csc-field-row">
					<label for="<?php echo esc_attr( self::META_PREFIX . 'title_text' ); ?>"><?php esc_html_e( 'Title Text', 'coinsnap-bitcoin-donation' ); ?></label>
					<div class="csc-field-input">
						<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'title_text' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'title_text' ); ?>" value="<?php echo esc_attr( $meta['title_text'] ); ?>" placeholder="<?php echo esc_attr( $defaults['title_text'] ); ?>" class="regular-text">
					</div>
				</div>
				<div class="csc-field-row">
					<label for="<?php echo esc_attr( self::META_PREFIX . 'default_amount' ); ?>"><?php esc_html_e( 'Default Amount', 'coinsnap-bitcoin-donation' ); ?></label>
					<div class="csc-field-input">
						<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'default_amount' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'default_amount' ); ?>" value="<?php echo esc_attr( $meta['default_amount'] ); ?>" placeholder="5" class="regular-text">
					</div>
				</div>
				<div class="csc-field-row">
					<label for="<?php echo esc_attr( self::META_PREFIX . 'default_message' ); ?>"><?php esc_html_e( 'Default Message', 'coinsnap-bitcoin-donation' ); ?></label>
					<div class="csc-field-input">
						<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'default_message' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'default_message' ); ?>" value="<?php echo esc_attr( $meta['default_message'] ); ?>" placeholder="<?php echo esc_attr( $defaults['default_message'] ); ?>" class="regular-text">
					</div>
				</div>
				<div class="csc-field-row">
					<label for="<?php echo esc_attr( self::META_PREFIX . 'redirect_url' ); ?>"><?php esc_html_e( 'Redirect URL (Thank You Page)', 'coinsnap-bitcoin-donation' ); ?></label>
					<div class="csc-field-input">
						<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'redirect_url' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'redirect_url' ); ?>" value="<?php echo esc_attr( $meta['redirect_url'] ); ?>" placeholder="<?php echo esc_attr( home_url() ); ?>" class="regular-text">
					</div>
				</div>
				<div class="donation-form-conditional" data-show-for="simple_donation multi_amount">
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'layout' ); ?>"><?php esc_html_e( 'Layout', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<select id="<?php echo esc_attr( self::META_PREFIX . 'layout' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'layout' ); ?>" class="regular-text">
								<option value="NARROW" <?php selected( $layout, 'NARROW' ); ?>><?php esc_html_e( 'Narrow', 'coinsnap-bitcoin-donation' ); ?></option>
								<option value="WIDE" <?php selected( $layout, 'WIDE' ); ?>><?php esc_html_e( 'Wide', 'coinsnap-bitcoin-donation' ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Multi Amount Fields -->
		<div class="donation-form-conditional" data-show-for="multi_amount">
			<div class="csc-card">
				<div class="csc-card-header">
					<h2><?php esc_html_e( 'Preset Amounts', 'coinsnap-bitcoin-donation' ); ?></h2>
				</div>
				<div class="csc-card-body">
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'snap1' ); ?>"><?php esc_html_e( 'Snap Amount 1', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'snap1' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'snap1' ); ?>" value="<?php echo esc_attr( $meta['snap1'] ); ?>" placeholder="50" class="regular-text">
						</div>
					</div>
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'snap2' ); ?>"><?php esc_html_e( 'Snap Amount 2', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'snap2' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'snap2' ); ?>" value="<?php echo esc_attr( $meta['snap2'] ); ?>" placeholder="100" class="regular-text">
						</div>
					</div>
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'snap3' ); ?>"><?php esc_html_e( 'Snap Amount 3', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'snap3' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'snap3' ); ?>" value="<?php echo esc_attr( $meta['snap3'] ); ?>" placeholder="200" class="regular-text">
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Shoutout Fields -->
		<div class="donation-form-conditional" data-show-for="shoutout">
			<div class="csc-card">
				<div class="csc-card-header">
					<h2><?php esc_html_e( 'Shoutout Settings', 'coinsnap-bitcoin-donation' ); ?></h2>
				</div>
				<div class="csc-card-body">
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'minimum_amount' ); ?>"><?php esc_html_e( 'Minimum Amount (SATS)', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'minimum_amount' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'minimum_amount' ); ?>" value="<?php echo esc_attr( $meta['minimum_amount'] ); ?>" placeholder="500" class="regular-text">
						</div>
					</div>
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'premium_amount' ); ?>"><?php esc_html_e( 'Premium Amount (SATS)', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'premium_amount' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'premium_amount' ); ?>" value="<?php echo esc_attr( $meta['premium_amount'] ); ?>" placeholder="10000" class="regular-text">
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Donor Information -->
		<div class="csc-card">
			<div class="csc-card-header">
				<h2><?php esc_html_e( 'Donor Information', 'coinsnap-bitcoin-donation' ); ?></h2>
			</div>
			<div class="csc-card-body">
				<div class="csc-field-row">
					<div class="donation-form-checkbox-row">
						<input type="checkbox" id="<?php echo esc_attr( self::META_PREFIX . 'public_donors' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'public_donors' ); ?>" value="1" <?php checked( $meta['public_donors'], '1' ); ?>>
						<label for="<?php echo esc_attr( self::META_PREFIX . 'public_donors' ); ?>"><?php esc_html_e( 'Collect donor information', 'coinsnap-bitcoin-donation' ); ?></label>
					</div>
				</div>
				<div class="donation-form-donor-fields <?php echo $meta['public_donors'] === '1' ? 'visible' : ''; ?>">
					<?php
					$donor_fields = array(
						'first_name'              => __( 'First Name', 'coinsnap-bitcoin-donation' ),
						'last_name'               => __( 'Last Name', 'coinsnap-bitcoin-donation' ),
						'email'                   => __( 'Email', 'coinsnap-bitcoin-donation' ),
						'address'                 => __( 'Address', 'coinsnap-bitcoin-donation' ),
					);
					foreach ( $donor_fields as $field_key => $field_label ) :
						$field_val = ! empty( $meta[ $field_key ] ) ? $meta[ $field_key ] : 'optional';
						?>
						<div class="csc-field-row">
							<label for="<?php echo esc_attr( self::META_PREFIX . $field_key ); ?>"><?php echo esc_html( $field_label ); ?></label>
							<div class="csc-field-input">
								<select id="<?php echo esc_attr( self::META_PREFIX . $field_key ); ?>" name="<?php echo esc_attr( self::META_PREFIX . $field_key ); ?>" class="regular-text">
									<?php foreach ( $visibility_options as $opt_val => $opt_label ) : ?>
										<option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $field_val, $opt_val ); ?>><?php echo esc_html( $opt_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					<?php endforeach; ?>
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'custom_field_name' ); ?>"><?php esc_html_e( 'Custom Field Name', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'custom_field_name' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'custom_field_name' ); ?>" value="<?php echo esc_attr( $meta['custom_field_name'] ); ?>" class="regular-text">
						</div>
					</div>
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'custom_field_visibility' ); ?>"><?php esc_html_e( 'Custom Field Visibility', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<select id="<?php echo esc_attr( self::META_PREFIX . 'custom_field_visibility' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'custom_field_visibility' ); ?>" class="regular-text">
								<?php
								$cf_vis = ! empty( $meta['custom_field_visibility'] ) ? $meta['custom_field_visibility'] : 'hidden';
								foreach ( $visibility_options as $opt_val => $opt_label ) :
									?>
									<option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $cf_vis, $opt_val ); ?>><?php echo esc_html( $opt_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'donor_notice' ); ?>"><?php esc_html_e( 'Donor Notice', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<textarea id="<?php echo esc_attr( self::META_PREFIX . 'donor_notice' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'donor_notice' ); ?>" class="large-text" rows="4" placeholder="<?php esc_attr_e( 'Optional notice displayed to donors (e.g. tax deduction info)', 'coinsnap-bitcoin-donation' ); ?>"><?php echo esc_textarea( $meta['donor_notice'] ); ?></textarea>
						</div>
					</div>
					<div class="csc-field-row">
						<label for="<?php echo esc_attr( self::META_PREFIX . 'custom_checkbox_label' ); ?>"><?php esc_html_e( 'Custom Checkbox Label', 'coinsnap-bitcoin-donation' ); ?></label>
						<div class="csc-field-input">
							<input type="text" id="<?php echo esc_attr( self::META_PREFIX . 'custom_checkbox_label' ); ?>" name="<?php echo esc_attr( self::META_PREFIX . 'custom_checkbox_label' ); ?>" value="<?php echo esc_attr( $meta['custom_checkbox_label'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. I need a donation receipt', 'coinsnap-bitcoin-donation' ); ?>">
							<p class="description"><?php esc_html_e( 'Leave empty to hide. When set, a checkbox with this label appears in the donor form.', 'coinsnap-bitcoin-donation' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php
		// Shortcode display — only for saved posts.
		if ( $post->ID && get_post_status( $post->ID ) !== 'auto-draft' ) :
			$sc_form = '[coinsnap_bitcoin_donation_form id="' . $post->ID . '"]';
			$sc_list = '[coinsnap_donation_list id="' . $post->ID . '"]';
			?>
			<div class="donation-form-shortcode-section">
				<div class="csc-shortcode-group">
					<div class="csc-shortcode-row">
						<span class="csc-shortcode-label"><?php esc_html_e( 'Form', 'coinsnap-bitcoin-donation' ); ?></span>
						<button type="button" class="csc-shortcode-copy" data-shortcode="<?php echo esc_attr( $sc_form ); ?>" title="<?php esc_attr_e( 'Click to copy', 'coinsnap-bitcoin-donation' ); ?>">
							<code class="csc-shortcode-code"><?php echo esc_html( $sc_form ); ?></code>
							<span class="csc-shortcode-icon">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
							</span>
							<span class="csc-shortcode-copied"><?php esc_html_e( 'Copied!', 'coinsnap-bitcoin-donation' ); ?></span>
						</button>
					</div>
					<div class="csc-shortcode-row donation-form-shortcode-shoutout-list" style="<?php echo $form_type !== 'shoutout' ? 'display:none;' : ''; ?>">
						<span class="csc-shortcode-label"><?php esc_html_e( 'Shoutout List', 'coinsnap-bitcoin-donation' ); ?></span>
						<button type="button" class="csc-shortcode-copy" data-shortcode="<?php echo esc_attr( $sc_list ); ?>" title="<?php esc_attr_e( 'Click to copy', 'coinsnap-bitcoin-donation' ); ?>">
							<code class="csc-shortcode-code"><?php echo esc_html( $sc_list ); ?></code>
							<span class="csc-shortcode-icon">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
							</span>
							<span class="csc-shortcode-copied"><?php esc_html_e( 'Copied!', 'coinsnap-bitcoin-donation' ); ?></span>
						</button>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php
	}

	public function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$nonce = filter_input( INPUT_POST, 'coinsnap_donation_form_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'coinsnap_donation_form_nonce' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$text_fields = array(
			'form_type', 'layout', 'currency', 'button_text', 'title_text',
			'default_amount', 'default_message', 'redirect_url',
			'first_name', 'last_name', 'email', 'address',
			'custom_field_name', 'custom_field_visibility', 'custom_checkbox_label',
			'snap1', 'snap2', 'snap3', 'minimum_amount', 'premium_amount',
		);
		foreach ( $text_fields as $field ) {
			$key = self::META_PREFIX . $field;
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
		$public_donors = isset( $_POST[ self::META_PREFIX . 'public_donors' ] ) ? '1' : '';
		update_post_meta( $post_id, self::META_PREFIX . 'public_donors', $public_donors );

		// Textarea field — preserve line breaks
		$donor_notice_key = self::META_PREFIX . 'donor_notice';
		if ( isset( $_POST[ $donor_notice_key ] ) ) {
			update_post_meta( $post_id, $donor_notice_key, sanitize_textarea_field( wp_unslash( $_POST[ $donor_notice_key ] ) ) );
		}
	}

	public function add_columns( $columns ) {
		return array(
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'form_type' => __( 'Form Type', 'coinsnap-bitcoin-donation' ),
			'layout'    => __( 'Layout', 'coinsnap-bitcoin-donation' ),
			'shortcode' => __( 'Shortcode', 'coinsnap-bitcoin-donation' ),
			'date'      => $columns['date'],
		);
	}

	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'form_type':
				$type = get_post_meta( $post_id, self::META_PREFIX . 'form_type', true );
				$labels = array(
					'simple_donation' => __( 'Simple Donation', 'coinsnap-bitcoin-donation' ),
					'multi_amount'    => __( 'Multi Amount', 'coinsnap-bitcoin-donation' ),
					'shoutout'        => __( 'Shoutout', 'coinsnap-bitcoin-donation' ),
				);
				echo esc_html( $labels[ $type ] ?? $type );
				break;

			case 'layout':
				$type = get_post_meta( $post_id, self::META_PREFIX . 'form_type', true );
				if ( $type === 'shoutout' ) {
					echo '—';
				} else {
					$layout = get_post_meta( $post_id, self::META_PREFIX . 'layout', true );
					echo esc_html( $layout ?: 'NARROW' );
				}
				break;

			case 'shortcode':
				$shortcode = '[coinsnap_bitcoin_donation_form id="' . $post_id . '"]';
				echo '<span class="csc-shortcode-copy" data-shortcode="' . esc_attr( $shortcode ) . '">';
				echo '<span class="csc-shortcode-code">' . esc_html( $shortcode ) . '</span>';
				echo '<span class="csc-shortcode-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></span>';
				echo '<span class="csc-shortcode-copied">' . esc_html__( 'Copied!', 'coinsnap-bitcoin-donation' ) . '</span>';
				echo '</span>';
				break;
		}
	}

	public function fix_parent_menu( $parent_file ) {
		$screen = get_current_screen();
		if ( $screen && $screen->post_type === self::POST_TYPE ) {
			return 'coinsnap-bitcoin-donation';
		}
		return $parent_file;
	}

	public function fix_submenu_highlight( $submenu_file ) {
		$screen = get_current_screen();
		if ( $screen && $screen->post_type === self::POST_TYPE ) {
			return 'coinsnap-bitcoin-donation';
		}
		return $submenu_file;
	}

	public function render_empty_state() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== self::POST_TYPE || $screen->base !== 'edit' ) {
			return;
		}

		$count = wp_count_posts( self::POST_TYPE );
		$total = ( $count->publish ?? 0 ) + ( $count->draft ?? 0 ) + ( $count->trash ?? 0 );
		if ( $total > 0 ) {
			return;
		}

		$new_url = admin_url( 'post-new.php?post_type=' . self::POST_TYPE );
		?>
		<style>
			.donation-form-empty-state {
				text-align: center;
				padding: 60px 20px;
			}
			.donation-form-empty-state svg {
				width: 64px;
				height: 64px;
				color: #dcdcde;
				margin-bottom: 16px;
			}
			.donation-form-empty-state h2 {
				font-size: 20px;
				font-weight: 400;
				color: #1d2327;
				margin: 0 0 8px;
			}
			.donation-form-empty-state p {
				color: #787c82;
				font-size: 14px;
				margin: 0 0 24px;
			}
			.donation-form-empty-state .button {
				font-size: 14px;
				padding: 8px 24px;
				height: auto;
			}
		</style>
		<script>
			jQuery(function($) {
				var $table = $('.wp-list-table');
				var $noItems = $('.no-items');
				if ($noItems.length) {
					$noItems.closest('tr').hide();
					$table.after($('#donation-form-empty-state'));
					$('#donation-form-empty-state').show();
				}
			});
		</script>
		<div id="donation-form-empty-state" class="donation-form-empty-state" style="display:none;">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
				<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
				<polyline points="14 2 14 8 20 8"></polyline>
				<line x1="12" y1="18" x2="12" y2="12"></line>
				<line x1="9" y1="15" x2="15" y2="15"></line>
			</svg>
			<h2><?php esc_html_e( 'No donation forms yet', 'coinsnap-bitcoin-donation' ); ?></h2>
			<p><?php esc_html_e( 'Create your first donation form to start accepting Bitcoin payments.', 'coinsnap-bitcoin-donation' ); ?></p>
			<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary">
				<?php esc_html_e( 'Create Your First Form', 'coinsnap-bitcoin-donation' ); ?>
			</a>
		</div>
		<?php
	}

	public function maybe_create_default_forms() {
		$post_type = filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( $post_type !== self::POST_TYPE ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Already have forms? Nothing to do.
		$existing = get_posts( array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		) );
		if ( ! empty( $existing ) ) {
			return;
		}

		// Create the 3 default forms
		$defaults = array(
			array(
				'title'     => __( 'Simple Donation', 'coinsnap-bitcoin-donation' ),
				'form_type' => 'simple_donation',
				'meta'      => array(
					'layout'          => 'NARROW',
					'currency'        => 'EUR',
					'button_text'     => __( 'Donate', 'coinsnap-bitcoin-donation' ),
					'title_text'      => __( 'Donate with Bitcoin', 'coinsnap-bitcoin-donation' ),
					'default_amount'  => '5',
					'default_message' => __( 'Thank you for your support!', 'coinsnap-bitcoin-donation' ),
					'redirect_url'    => home_url(),
				),
			),
			array(
				'title'     => __( 'Multi Amount Donation', 'coinsnap-bitcoin-donation' ),
				'form_type' => 'multi_amount',
				'meta'      => array(
					'layout'          => 'NARROW',
					'currency'        => 'EUR',
					'button_text'     => __( 'Donate', 'coinsnap-bitcoin-donation' ),
					'title_text'      => __( 'Donate with Bitcoin', 'coinsnap-bitcoin-donation' ),
					'default_amount'  => '10',
					'default_message' => __( 'Thank you for your support!', 'coinsnap-bitcoin-donation' ),
					'redirect_url'    => home_url(),
					'snap1'           => '50',
					'snap2'           => '100',
					'snap3'           => '200',
				),
			),
			array(
				'title'     => __( 'Shoutout', 'coinsnap-bitcoin-donation' ),
				'form_type' => 'shoutout',
				'meta'      => array(
					'currency'        => 'EUR',
					'button_text'     => __( 'Shoutout', 'coinsnap-bitcoin-donation' ),
					'title_text'      => __( 'Bitcoin Shoutouts', 'coinsnap-bitcoin-donation' ),
					'default_amount'  => '20',
					'default_message' => __( 'Great work!', 'coinsnap-bitcoin-donation' ),
					'redirect_url'    => home_url(),
					'minimum_amount'  => '500',
					'premium_amount'  => '10000',
				),
			),
		);

		$mapping = array();

		foreach ( $defaults as $form ) {
			$post_id = wp_insert_post( array(
				'post_title'  => $form['title'],
				'post_status' => 'publish',
				'post_type'   => self::POST_TYPE,
			) );

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, self::META_PREFIX . 'form_type', $form['form_type'] );
			foreach ( $form['meta'] as $key => $value ) {
				update_post_meta( $post_id, self::META_PREFIX . $key, $value );
			}

			// Map legacy shortcodes
			if ( $form['form_type'] === 'simple_donation' ) {
				$mapping['coinsnap_bitcoin_donation']      = $post_id;
				$mapping['coinsnap_bitcoin_donation_wide'] = $post_id;
			} elseif ( $form['form_type'] === 'multi_amount' ) {
				$mapping['multi_amount_donation']      = $post_id;
				$mapping['multi_amount_donation_wide'] = $post_id;
			} elseif ( $form['form_type'] === 'shoutout' ) {
				$mapping['shoutout_form'] = $post_id;
				$mapping['shoutout_list'] = $post_id;
			}
		}

		if ( ! empty( $mapping ) ) {
			update_option( 'coinsnap_donation_migrated_forms', $mapping );
			update_option( 'coinsnap_donation_forms_migrated', '1' );
		}

		// Redirect to reload the page with forms visible
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) );
		exit;
	}
}
