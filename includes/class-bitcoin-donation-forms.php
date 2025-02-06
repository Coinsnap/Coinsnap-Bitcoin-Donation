<?php
class Bitcoin_Donation_Forms
{

	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_donations_submenu'));
	}

	public function add_donations_submenu()
	{
		add_submenu_page(
			'bitcoin_donation',
			'Donation Forms',
			'Donation Forms',
			'manage_options',
			'bitcoin-donation-donation-forms',
			array($this, 'render_donation_forms_page')
		);
	}

	public function render_donation_forms_page()
	{
		// Verify user capabilities
		if (!current_user_can('manage_options')) {
			return;
		}

?>
		<div class="bitcoin-donation-forms-wrapper">
			<div class="forms-wrapper">
				<div class="template-pair">
					<div class="form-template">
						<div class="form-image-container">
							<img src="<?php echo plugin_dir_url(__FILE__) . 'assets/shoutout_form.png'; ?>" alt="Shoutout Form">
						</div>
						<div class="form-shorcode">[shoutout_form]
						</div>
					</div>
					<div class="form-template">
						<div class="form-image-container">
							<img src="<?php echo plugin_dir_url(__FILE__) . 'assets/shoutout_list.png'; ?>" alt="Shoutout List">
						</div>
						<div class="form-shorcode">[shoutout_list]
						</div>
					</div>
				</div>
				<div class="template-pair">
					<div class="form-template">
						<div class="form-image-container">
							<img src="<?php echo plugin_dir_url(__FILE__) . 'assets/bitcoin_donation.png'; ?>" alt="Shoutout Form">
						</div>
						<div class="form-shorcode">[bitcoin_donation]</div>
					</div>
					<div class="form-template">
						<div class="form-image-container">
							<img src="<?php echo plugin_dir_url(__FILE__) . 'assets/bitcoin_donation_wide.png'; ?>" alt="Shoutout List">
						</div>
						<div class="form-shorcode">[bitcoin_donation_wide]</div>
					</div>
				</div>


			</div>


	<?php
	}
}
new Bitcoin_Donation_Forms();
