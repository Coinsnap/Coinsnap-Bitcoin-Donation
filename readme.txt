=== Coinsnap Bitcoin Donation ===

Contributors: coinsnap
Tags: bitcoin, lightning, donation, bitcoin donations, sats, btcpay, btcpay server
Requires at least: 5.2
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.5.9
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept Bitcoin and Lightning donations on WordPress with unlimited donation forms, shoutouts, Coinsnap or your own BTCPay Server.

== Description ==

Coinsnap Bitcoin Donation is a WordPress plugin for accepting voluntary Bitcoin contributions directly on your website.

Visitors can donate with Bitcoin via Lightning or Bitcoin on-chain. You can use Coinsnap for a simple payment setup or connect your own BTCPay Server.

= Donation forms =

Create unlimited donation forms and place them anywhere with shortcodes.

The plugin includes three form types:

* **Simple Donation** — visitors choose their own contribution amount.
* **Multi Amount Donation** — offer predefined donation amounts.
* **Shoutout Donation** — supporters donate and publish a paid message after successful payment.

Forms support wide and narrow layouts, multiple forms on the same page, configurable donor fields, donor notices and custom checkboxes.

= Payment providers =

* **Coinsnap** — accept Bitcoin and Lightning without running your own payment infrastructure.
* **BTCPay Server** — connect your own BTCPay Server for a self-hosted payment setup.

= Key features =

* Bitcoin Lightning and on-chain payments
* Unlimited donation forms
* Simple, Multi Amount and Shoutout form types
* Shortcode-based placement
* Multiple forms per page
* Optional donor information
* Public paid shoutouts
* Fiat and Bitcoin amount calculation
* Transactions and payment history in WordPress
* Automatic webhook registration
* Webhook signature verification
* Light and dark themes
* German translation included
* Non-custodial / self-custody payment model

= Requirements =

* WordPress 5.2 or newer
* PHP 8.0 or newer
* A Coinsnap account or your own BTCPay Server

= Shortcodes =

Each current donation form has its own shortcode:

[coinsnap_bitcoin_donation_form id="123"]

Shoutout lists can be displayed with:

[coinsnap_donation_list id="123"]

Legacy shortcodes remain supported for backward compatibility.

= More information =

* Product page: https://coinsnap.io/wp-plugins/wp-bitcoin-donation/
* Live demo: https://donation.coinsnap.org/
* GitHub: https://github.com/Coinsnap/Coinsnap-Bitcoin-Donation
* Coinsnap Wallet: https://coinsnap.io/wallet/
* Coinsnap API: https://docs.coinsnap.io/
* Coinsnap account: https://app.coinsnap.io/register

Coinsnap is Bitcoin payment software for merchants and a brand of Onlineshop24 d.o.o., Serbia.

== Installation ==

= Install from WordPress.org =

1. In WordPress, go to **Plugins → Add New**.
2. Search for **Coinsnap Bitcoin Donation**.
3. Click **Install Now** and activate the plugin.
4. Go to **Bitcoin Donations → Settings**.
5. Select Coinsnap or BTCPay Server and enter the required credentials.
6. Create a donation form.
7. Copy its shortcode into a page, post, sidebar or other shortcode-compatible area.
8. Make a test payment.

= Install from GitHub =

1. Download this repository as a ZIP archive.
2. Upload it through **Plugins → Add New → Upload Plugin**.
3. Install and activate the plugin.
4. Configure Coinsnap or BTCPay Server.

== Frequently Asked Questions ==

= Can visitors pay with both Lightning and Bitcoin on-chain? =

Yes. The plugin supports both Lightning and Bitcoin on-chain donations.

= Can I use my own BTCPay Server? =

Yes. BTCPay Server is supported as an alternative to Coinsnap.

= How many donation forms can I create? =

You can create unlimited donation forms.

= Can I use multiple donation forms on the same page? =

Yes. Multiple forms can be used independently on one page.

= Can I collect donor information? =

Yes. Donor information fields, a donor notice and a custom checkbox can be configured.

= Do I need my own Lightning node? =

No. When using Coinsnap, you do not need to operate your own Lightning node or Bitcoin payment server.

== Screenshots ==

1. Coinsnap Bitcoin Donation plugin installation
2. Coinsnap payment settings
3. BTCPay Server payment settings
4. Donation form
5. Donation and transaction list
6. Bitcoin and Lightning payment
7. Donation form configuration
8. Shoutout form
9. Shoutout list

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

= 1.5.6 :: 2026-03-27 =
* Fixed: SATS/BTC amounts divided by 100 when creating invoices via BTCPay and Coinsnap providers.

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

= 1.5.7 :: 2026-06-02 =
* Fixed: Payment status now syncs even when the provider webhook cannot reach the site (local dev or lost webhooks) — the status check and a new admin "Check status" action query Coinsnap/BTCPay directly.
* Fixed: Shoutout name entered in the form is now saved — previously every shoutout was recorded as "Anonymous".
* Fixed: Donor and shoutout records created from a confirmed payment read invoice metadata case-insensitively, so name, message and amounts are no longer blank.
* New: Invoice creation sends the buyer's email, name and description to Coinsnap/BTCPay (buyerEmail, buyerName, itemDesc) and the donation form id.
* New: Transactions list now shows Source (donation form), Invoice Number and Customer, with a per-row "Check status" action and a Source filter.

= 1.5.8 :: 2026-06-02 =
* Fixed: The "Invoice Id" link on the Shoutouts list linked to the wrong Coinsnap URL (/td/) — it now opens the transaction detail page (/transactions/).

= 1.5.9 :: 2026-06-02 =
* New: Invoice creation now sends orderId and orderNumber in the metadata (set to the unique transaction reference) so the paying wallet can reconcile the payment.
* Fixed: Shoutout list now matches the Shoutout form width (centered 645px card) with spacing between entries — the list layout previously did not apply to per-form (CPT) shoutout lists.
