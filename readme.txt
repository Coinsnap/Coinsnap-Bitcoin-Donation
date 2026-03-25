=== Coinsnap Bitcoin Donation ===

Contributors: coinsnap
Tags: Lightning, SATS, bitcoin, donation, BTCPay
Tested up to: 6.9
Stable tag: 1.5.5
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let visitors donate Bitcoin anywhere on your WordPress site. Simple setup, optional shoutouts, and display messages beside or below the donation form

== Description ==

= Accept Bitcoin Donations with Coinsnap Bitcoin Donation! =

Enable the visitors on your WordPress website to make donations with Bitcoin wherever you want. Make it extremely simple to donate, or even let them make a shoutout and display all shoutouts next to the donation form or on a dedicated website. If you wish, you can also gather your donors’ contact data for later interactions.

With Coinsnap Bitcoin Donation for WordPress you can accept Bitcoin donations on your website in three ways:

* **Bitcoin Donation button**: For a simple Bitcoin donation with a message to the site owner.
* **Multi Amount Bitcoin Donation**: With three predefined Bitcoin amounts to choose from and a message to the site operator.
* **Shoutout**: The Bitcoin donation with a comment that will be published on the website.

Coinsnap Bitcoin Donation works with Coinsnap or your own BTCPay Server.

= Requirements: =

* A WordPress website
* The Coinsnap Bitcoin Donation plugin
* A [Coinsnap account](https://app.coinsnap.io/register) or your own BTCPay Server

= Features & functions: =

* **Customisable donation buttons**:
	* Freely selectable or preset donation amount
	* Optional: Message from the donor or shoutout function
	* Selection of fields for donor data (name, e-mail, address, etc.)
* **Easy integration via shortcodes** - donation buttons can be placed anywhere on your website: in the content, in the sidebar or in the footer, by pasting the shortcode at the appropriate place.
* **Receive payments directly into your Bitcoin wallet** - either via Coinsnap or your own BTCPay Server.

= Quick setup: =

* Install plugin directly via the WordPress plugin directory
* Configure with just a few clicks
* And that's it!

= Two operating modes: =

* Use Coinsnap (no technical know-how required)
* Or use your own BTCPay server (for advanced users)

= Why Coinsnap Bitcoin Donation? =

* Open source and free in the WordPress Plugin Directory
* No programming knowledge required
* Immediate credit to your Bitcoin wallet
* GDPR-friendly: no unnecessary data storage
* Continuous further development
* Strong support through our support team, accessible in your Coinsnap account


= More information =

* Live demo: [https://donation.coinsnap.org/](https://donation.coinsnap.org/)
* Product page: [https://coinsnap.io/modules/bitcoin-donation/](https://coinsnap.io/modules/bitcoin-donation/)
* Installation Guide: [https://coinsnap.io/modules/bitcoin-donation/bitcoin-donation-plugin-installation-guide/](https://coinsnap.io/modules/bitcoin-donation/bitcoin-donation-plugin-installation-guide/)
* Github plugin page: [https://github.com/Coinsnap/Coinsnap-bitcoin-donation/](https://github.com/Coinsnap/Coinsnap-bitcoin-donation/)


= Documentation: =

* [Coinsnap API (1.0) documentation](https://docs.coinsnap.io/)
* [Frequently Asked Questions](https://coinsnap.io/help/coinsnap-faq/)
* [Terms and Conditions](https://coinsnap.io/info/general-terms-and-conditions/)
* [Privacy Policy](https://coinsnap.io/info/privacy-policy/)


== Installation ==

= 1. Install the Coinsnap Bitcoin Donation plugin from the WordPress plugin repository =

The Coinsnap Bitcoin Donation plugin can be searched and installed in the WordPress plugin directory.

You can easily find the Coinsnap Bitcoin Donation plugin under **Plugins/Install new plugin** if you enter Coinsnap Bitcoin Donation in the search field. Simply click on **Install now** in the Coinsnap plugin and WordPress will install it for you.

Now WordPress will offer you to **Activate** the plugin – click the button and you are set to go!

Next, you will connect the plugin with your Coinsnap account.


= 1.1. Coinsnap Bitcoin Donation Settings =

After you have installed and activated the Coinsnap Bitcoin Donation plugin, you need to configure the Coinsnap settings. Go to **Bitcoin Donations -> Settings** [1] in the black sidebar on the left.

Now choose your payment gateway **Coinsnap** [1]. (You can also choose BTCPay server if you are using one, and then fill in the respective information.)
Then you’ll have to enter your **Coinsnap Store ID** and your **Coinsnap API Key**. [2] (See below to learn how to retrieve these from your Coinsnap account.)

As soon as you’ve pasted the Store ID and the API Key into their fields, click on **check**. If you see a green message next to it saying **Connection successful**, your plugin is ready to accept Bitcoin donations and credit them to your Lightning wallet.

Don’t forget to klick on **Save changes** before you start configuring your donation form(s)!


= 1.2. Enter Store ID and API Key in your Coinsnap Bitcoin Donation Settings =

Go to the **Settings** menu item in your Coinsnap merchant admin backend [https://app.coinsnap.io/login](https://app.coinsnap.io/login). Then click on **Store** and you will see your Coinsnap **Store ID** and the Coinsnap **API Key** in the **Store** section.

**Copy** these two strings and **paste** them into the matching fields in the **Coinsnap Bitcoin Donation settings** in your WordPress backend.

Click on the “**Save changes**” button at the bottom of the page to apply and save the settings. You are ready to start selling for Bitcoin now: Just create a donation form and place it via the shortcode on your website.

= YOU ARE SET TO SELL FOR BITCOIN NOW! To be sure all works fine, you should now... =


= 1.3. Test the payment method in a Coinsnap Bitcoin Donation form on your website =

After all settings have been made, a test transaction should be carried out.

Choose an amount you want to donate in your test donation and click the payment button. Fill in the information you decided and configured to gather.

You will now be redirected to the Bitcoin-Lightning payment page to make your contribution by scanning the displayed QR code and authorizing the payment. After successful payment, you will see a confirmation that the payment has been transferred.


= 2. Install the Coinsnap Bitcoin Donation plugin from our Github page =

If you don’t want to install Coinsnap Bitcoin Donation plugin directly from your WordPress backend, download the Coinsnap Bitcoin Donation plugin from the [Coinsnap Github page here](https://github.com/Coinsnap/Coinsnap-bitcoin-donation/).

Find the green button labeled **Code**. When you click on it, the menu opens and Download ZIP appears. Here you can download the latest version of the Coinsnap plugin to your computer.

Then use the “**Upload plugin**” function to install it. Click on “**Install now**” and the Coinsnap Bitcoin Donation plugin will be added to your WordPress website. It can then be connected to the Coinsnap payment gateway or BTCPay server.

As soon as the Coinsnap Bitcoin Donation plugin is installed and activated, a message will appear asking you to configure the plugin settings.

From here on you can follow 1.1 to 1.3 and you will be set to sell for Bitcoin in no time at all!


=== Upgrade Notice ===

Follow updates on plugin's GitHub page:

[https://github.com/Coinsnap/Coinsnap-Bitcoin-Donation](https://github.com/Coinsnap/Coinsnap-Bitcoin-Donation)

=== Frequently Asked Questions ===

Plugin's page on Coinsnap website: [https://coinsnap.io/modules/bitcoin-donation/](https://coinsnap.io/modules/bitcoin-donation/)

=== Screenshots ===

1. Coinsnap Bitcoin Donation plugin with plugin search installation
2. Plugin settings for payment with Coinsnap payment gateway
3. Plugin settings for payment with BTCPay payment gateway
4. Donation form
5. Donations list
6. QR code
7. Registration in Cooinsnap
8. Email address confirmation
9. Setting up website at Coinsnap

=== Changelog ===

= 1.0.0 :: 2025-03-15 =
* Initial release.

= 1.1.0 :: 2025-04-30 =
* Updated Wordpress backend interface
* Updated plugin menu pages
* Added 2 more forms (Shoutout and Multi Amount)
* Added Donor Information collection option
* Added settings tabs for new forms and Donor Information
* Updated frontend interface
* Added QR popup payment
* Added currency selector
* Updated input fields with separators and currency
* Added conversion rate fetching from coinsnap
* Added cleanup on plugin uninstall
* Compatibility with Wordpress 6.8.1 is tested.

= 1.1.1 :: 2025-05-25 =
* Fixed bug for shoutouts
* Update: Expanded the shoutouts so that they remember sats amount too and highlight based on that

= 1.1.2 :: 2025-06-04 =
* Fixed bug for checkRequiredFieds constant in popup.js file

= 1.2.0 :: 2025-06-18 =
* Update: BTCPay setup wizard is added in BTCPay server settings.

= 1.3.0 :: 2025-12-09 =
* Update: Added payment gateway client class.
* Update: Added support for all the Coinsnap currencies.
* Update: Minimum order amount is added to connection status notice.
* Update: Plugin name is changed in Wordpress backend.
* Added QR-code generator for BTCPay server invoice.
* Compatibility with Wordpress 6.9 is tested.

= 1.3.1 :: 2026-02-04 =
* Updated interface of donor information form.
* Compatibility with Wordpress 6.9.1 is tested.

= 1.3.2 :: 2026-02-15 =
* Update: Added order ID and all the standard fields for invoice request.
* Update: Added plugin isolation from other Coinsnap plugins in backend.
* Update: Deleted currency exchange check on frontend.
* Update: Added plugin isolation from other plugins in backend.

= 1.4.0 :: 2026-03-11 =
* Update: Fiat/Crypto amount calculation in frontend.
* Update: Minimum and premium donation amounts in Shoutout form.
* Udpate: Name and message values in shoutout list in frontend.
* Compatibility with Wordpress 6.9.4 is tested.

= 1.4.1 :: 2026-03-12 =
* Fixed: CSS and JS files enqueue conditions.
* Fixed: Class Coinsnap_Bitcoin_Donation_Client() call during payment amount check.
* Update: Donation widget theme application.

= 1.4.2 :: 2026-03-13 =
* Update: Shortcode check on CSS and JS files enqueue conditions is temporary removed.

= 1.5.0 :: 2026-03-20 =
* Major: Migrated to coinsnap-core shared library for payment providers, settings, and webhooks.
* New: Server-side payment creation via WordPress REST API — API keys no longer exposed in frontend JavaScript.
* New: Iframe checkout modal replaces custom QR code popup for consistent payment experience.
* New: Dual webhook endpoints (Coinsnap and BTCPay) with proper signature verification using WP_REST_Request body.
* New: Legacy webhook endpoint for backward compatibility with existing registrations.
* New: Modern card-based admin settings page with connection badge and BTCPay wizard.
* New: Donation Forms admin page redesigned with tabbed card layout matching core design.
* New: Transactions page for viewing payment history.
* New: Logs page for debugging.
* New: Toast notification on settings save across all admin pages.
* New: Click-to-copy shortcodes in admin with visual feedback.
* New: Theme setting (Light/Dark) in shared core settings — supported on frontend forms.
* New: Ngrok URL field in Advanced settings for local webhook testing.
* New: Automatic webhook registration on admin page load.
* Update: Frontend donation forms completely redesigned — modern styling, Bitcoin orange (#f7931a) accents, system font stack, clean input focus states.
* Update: Amount field uses CSS-positioned currency label instead of embedded text — fixes cursor jumping issue.
* Update: Plugin icon updated to dedicated SVG.
* Update: Settings key migration (provider → payment_provider, btcpay_url → btcpay_host) for core compatibility.
* Update: Webhook signature verification uses $request->get_body() instead of php://input for reliability.
* Update: Webhook secrets managed by core with auto-registration.
* Update: Replaced Coingecko API with Kraken API for fiat conversion.
* Fixed: Inverted nonce check in donor meta save — donor edits now save correctly from admin.
* Fixed: Missing null coalescing on form options — no more PHP warnings on fresh installs.
* Fixed: PHP operator precedence bug in shoutout min/premium amounts.
* Fixed: Hardcoded BTCPay URL replaced with dynamic setting from core.
* Fixed: Webhook signature failure now returns 401 instead of 200.
* Fixed: XSS vector in error message display.
* Fixed: Missing wp_reset_postdata() after WP_Query in shoutouts list.
* Fixed: Exchange rate null check prevents crashes when API is unavailable.
* Fixed: JS null errors on pages with only one form type (guard checks for specific elements).
* Fixed: Amount/currency mismatch when paying in fiat currencies.
* Fixed: Duplicate settings page rendering removed.
* New: Re-register Webhook button in Debug Tools (visible when Log Level is Debug).
* New: Disable Webhook Verification toggle for debugging.
* New: Admin notice when payment gateway credentials are not configured.
* New: Payment endpoint returns clear error when gateway is not configured.
* New: Debug Tools section separated from Advanced settings, only visible in Debug mode.
* New: German translation (de_DE) with POT template for additional languages.
* New: load_plugin_textdomain support for translations.
* Update: Submenu reordered — plugin-specific pages first (Donation Forms, Shoutouts, Donor Info), core pages after (Transactions, Settings, Logs).
* Update: Removed Active/Enabled checkbox from all form types — forms are always active.
* Update: Shoutouts submenu always visible (no longer conditional on active checkbox).
* Fixed: Iframe modal not cleaned up on close — donate button stayed disabled after closing modal.
* Fixed: Exchange rate API failure (CoinGecko rate limiting) no longer blocks payment creation.

= 1.5.3 :: 2026-03-25 =
* Fixed: Shortcode "Copied!" text always visible on list table.
* Fixed: Shortcode copy button not working on list table.
* Fixed: Shortcode column overlapping date column in list table.
* Fixed: Old admin URL (admin.php?page=coinsnap-bitcoin-donation) now redirects correctly.

= 1.5.2 :: 2026-03-25 =
* New: Empty state page with "Create Your First Form" button when no forms exist.
* New: Admin notice after default forms are created prompting to review settings.
* New: 3 default forms (Simple, Multi Amount, Shoutout) auto-created on first visit.
* Update: "Snap Donations" renamed to "Choose an amount" (German: "Wähle einen Betrag aus").
* Fixed: Self-healing migration — recovers from failed migration attempts automatically.
* Fixed: Old admin URL redirect — cached bookmarks to the previous menu page now redirect correctly.
* Fixed: Menu now links directly to CPT list — no redirect needed.

= 1.5.1 :: 2026-03-24 =
* Major: Donation Forms now use a Custom Post Type — create unlimited forms instead of the previous 3-tab limit.
* New: Visual form type selector with SVG icon cards (Simple Donation, Multi Amount, Shoutout).
* New: Unified shortcode `[coinsnap_bitcoin_donation_form id="123"]` renders any form type based on its settings.
* New: Shoutout list shortcode `[coinsnap_donation_list id="123"]` scoped per form.
* New: Multiple forms of the same type on one page — each with independent settings.
* New: Donor Notice text field — display informational text in the donor popup (e.g. tax deduction info).
* New: Custom Checkbox field — configurable checkbox in the donor popup (e.g. "I need a donation receipt").
* New: Translatable default values for form fields (Button Text, Title, Message) — pre-filled based on site language.
* New: Placeholder text on all admin form fields showing expected values.
* New: Admin list table with Form Type, Layout, and copyable Shortcode columns.
* New: "Add New Form" submenu item for quick form creation.
* New: Automatic migration from old settings to CPT posts on plugin update — existing forms and shortcodes continue to work.
* Update: Legacy shortcodes (`[coinsnap_bitcoin_donation]`, `[multi_amount_donation]`, `[shoutout_form]`, `[shoutout_list]`, and wide variants) remain fully functional via migration mapping.
* Update: Frontend JavaScript refactored to use per-form `data-*` attributes instead of global variables — enables multi-form pages.
* Update: Default snap amounts changed to 50 / 100 / 200.
* Update: Default shoutout minimum 500 SATS, premium 10,000 SATS.
* Update: Webhook handler stores donation form ID on shoutout and donor posts for per-form scoping.
* Update: Uninstall cleanup includes CPT posts and migration options.
* Fixed: Wide layout shortcodes force correct layout regardless of stored meta value.
