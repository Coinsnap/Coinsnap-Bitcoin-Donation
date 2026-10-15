![Coinsnap Bitcoin Donation](https://coinsnap.io/wp-content/uploads/2025/12/Wordpress-Bitcoin-Donation.svg)

# Coinsnap Bitcoin Donation

**Bitcoin donation plugin for WordPress — accept Bitcoin and Lightning contributions with Coinsnap or your own BTCPay Server.**

Coinsnap Bitcoin Donation is an open-source WordPress plugin for creators, publishers, communities, nonprofits and website owners who want to accept voluntary Bitcoin contributions directly on their own website.

Visitors can donate with **Bitcoin via Lightning or on-chain**. The plugin supports **Coinsnap** for a simple hosted payment setup or **BTCPay Server** for merchants and creators who prefer to run their own payment infrastructure.

Coinsnap is **Bitcoin payment software for merchants** and a brand of **Onlineshop24 d.o.o., Serbia**.

## Key features

- Accept **Bitcoin and Lightning donations** in WordPress
- Support for **Lightning and Bitcoin on-chain**
- Support for **Coinsnap** and **BTCPay Server**
- Create **unlimited donation forms**
- Three form types:
  - Simple Donation
  - Multi Amount Donation
  - Shoutout Donation
- Wide and narrow layouts
- Shortcode-based placement anywhere in WordPress
- Multiple forms of the same type on one page
- Optional donor information fields
- Optional donor notice and custom checkbox
- Public paid shoutouts with per-form shoutout lists
- Fiat and Bitcoin amount calculation
- Transactions view and payment history in WordPress
- Automatic webhook registration and signature verification
- Light and dark form themes
- German translation included
- No own Lightning node required when using Coinsnap
- **Non-custodial / self-custody** payment model

## Requirements

Current plugin version: **1.5.9**

- WordPress **5.2 or newer**
- PHP **8.0 or newer**
- A **Coinsnap merchant account**, or
- Your own **BTCPay Server**

For settlement with Coinsnap, you can use a compatible Bitcoin or Lightning wallet. One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/).

## Donation form types

### Simple Donation

Let supporters enter the amount they want to contribute and optionally leave information or a message.

### Multi Amount Donation

Offer predefined contribution amounts so visitors can choose from several suggested donation levels.

### Shoutout Donation

Let supporters donate and publish a paid message or shoutout on your website after successful payment.

## Unlimited donation forms

Since version 1.5.1, donation forms are stored as a WordPress Custom Post Type. You can create unlimited forms instead of being limited to one form of each type.

Each form has its own settings and shortcode.

Example:

`[coinsnap_bitcoin_donation_form id="123"]`

A shoutout list can be displayed with:

`[coinsnap_donation_list id="123"]`

Legacy shortcodes remain supported for backward compatibility.

## Coinsnap or BTCPay Server

The plugin supports two payment-provider modes:

- **Coinsnap** — for users who want Bitcoin and Lightning payments without operating their own payment infrastructure.
- **BTCPay Server** — for users who want to connect their own self-hosted BTCPay Server.

The plugin includes a BTCPay setup wizard and webhook handling for both providers.

## How it works

1. Install and activate Coinsnap Bitcoin Donation.
2. Open **Bitcoin Donations → Settings**.
3. Select **Coinsnap** or **BTCPay Server**.
4. Enter the required payment-provider credentials.
5. Create one or more donation forms.
6. Copy the generated shortcode into a page, post, sidebar or other shortcode-compatible area.
7. Make a test donation before publishing the form.

## Installation

### Install from WordPress.org

1. In WordPress, go to **Plugins → Add New**.
2. Search for **Coinsnap Bitcoin Donation**.
3. Click **Install Now**.
4. Activate the plugin.
5. Open **Bitcoin Donations → Settings** and connect your payment provider.

### Install from GitHub

1. Download this repository as a ZIP archive.
2. Upload it through **Plugins → Add New → Upload Plugin**.
3. Install and activate the plugin.
4. Configure Coinsnap or BTCPay Server in the plugin settings.

## Payment and settlement

Supporters can pay with Bitcoin using Lightning or Bitcoin on-chain.

Coinsnap follows a non-custodial approach and does not hold merchant Bitcoin. Payments are settled to the configured payment destination. Depending on the Coinsnap setup and supported partner integrations, settlement to a bank account can also be available.

## Resources

- [Coinsnap Bitcoin Donation product page](https://coinsnap.io/wp-plugins/wp-bitcoin-donation/)
- [WordPress Plugin Directory](https://wordpress.org/plugins/coinsnap-bitcoin-donation/)
- [Live demo](https://donation.coinsnap.org/)
- [Coinsnap Wallet](https://coinsnap.io/wallet/)
- [Coinsnap API documentation](https://docs.coinsnap.io/)
- [Coinsnap merchant account](https://app.coinsnap.io/register)
- [Coinsnap website](https://coinsnap.io/)
- [Telegram community and support](https://t.me/coinsnap)

> **Brand note:** Coinsnap is Bitcoin payment software for merchants and is not the provider of the similarly named coin identifier app "CoinSnap".

## Frequently Asked Questions

### Can visitors donate with both Lightning and Bitcoin on-chain?

Yes. The plugin supports Bitcoin donations via both Lightning and Bitcoin on-chain.

### Can I use my own BTCPay Server?

Yes. BTCPay Server is supported as an alternative to Coinsnap.

### How many donation forms can I create?

You can create unlimited donation forms. Each form receives its own settings and shortcode.

### Can I place more than one donation form on a page?

Yes. Multiple forms of the same or different types can be used independently on one page.

### What is a Shoutout Donation?

A Shoutout Donation combines a Bitcoin contribution with a public message. After the payment is confirmed, the shoutout can be displayed on your website.

### Can I collect donor information?

Yes. The plugin supports configurable donor information fields, an optional donor notice and a custom checkbox.

### Which wallets can I use?

You can use a compatible Bitcoin or Lightning wallet for settlement. One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/). Other compatible wallets may include solutions such as Wallet of Satoshi, Blink and Zeus, depending on your settlement setup.

### Does Coinsnap hold my Bitcoin?

Coinsnap follows a non-custodial approach and does not hold merchant Bitcoin.

### Do I need my own Lightning node?

No. When using Coinsnap, you do not need to operate your own Lightning node or Bitcoin payment server.

### Is the plugin open source?

Yes. The plugin is open source and available free of charge.

# Changelog #

#### 1.0.0 :: 2024-07-08
* Initial release. 

#### 1.1.0 :: 2025-04-30
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

#### 1.1.1 :: 2025-05-25
* Fixed bug for shoutouts
* Update: Expanded the shoutouts so that they remember sats amount too and highlight based on that

#### 1.1.2 :: 2025-06-04
* Fixed bug for checkRequiredFieds constant in popup.js file

#### 1.2.0 :: 2025-06-18
* Update: BTCPay setup wizard is added in BTCPay server settings.

#### 1.3.0 :: 2025-12-09
* Update: Added payment gateway client class. 
* Update: Added support for all the Coinsnap currencies.
* Update: Minimum order amount is added to connection status notice.
* Update: Plugin name is changed in Wordpress backend.
* Added QR-code generator for BTCPay server invoice.
* Compatibility with Wordpress 6.9 is tested.

#### 1.3.1 :: 2026-02-04
* Updated interface of donor information form.
* Compatibility with Wordpress 6.9.1 is tested.

#### 1.3.2 :: 2026-02-15
* Update: Added order ID and all the standard fields for invoice request.
* Update: Added plugin isolation from other Coinsnap plugins in backend.
* Update: Deleted currency exchange check on frontend.
* Update: Added plugin isolation from other plugins in backend.

#### 1.4.0 :: 2026-03-11
* Update: Fiat/Crypto amount calculation in frontend.
* Update: Minimum and premium donation amounts in Shoutout form.
* Udpate: Name and message values in shoutout list in frontend.
* Compatibility with Wordpress 6.9.4 is tested.

#### 1.4.1 :: 2026-03-12
* Fixed: CSS and JS files enqueue conditions.
* Fixed: Class Coinsnap_Bitcoin_Donation_Client() call during payment amount check. 
* Update: Donation widget theme application.

#### 1.4.2 :: 2026-03-13
* Update: Shortcode check on CSS and JS files enqueue conditions is temporary removed.

#### 1.5.0 :: 2026-03-20
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

#### 1.5.6 :: 2026-03-27
* Fixed: SATS/BTC amounts divided by 100 when creating invoices via BTCPay and Coinsnap providers.

#### 1.5.3 :: 2026-03-25
* Fixed: Shortcode "Copied!" text always visible on list table.
* Fixed: Shortcode copy button not working on list table.
* Fixed: Shortcode column overlapping date column in list table.
* Fixed: Old admin URL (admin.php?page=coinsnap-bitcoin-donation) now redirects correctly.

#### 1.5.2 :: 2026-03-25
* New: Empty state page with "Create Your First Form" button when no forms exist.
* New: Admin notice after default forms are created prompting to review settings.
* New: 3 default forms (Simple, Multi Amount, Shoutout) auto-created on first visit.
* Update: "Snap Donations" renamed to "Choose an amount" (German: "Wähle einen Betrag aus").
* Fixed: Self-healing migration — recovers from failed migration attempts automatically.
* Fixed: Old admin URL redirect — cached bookmarks to the previous menu page now redirect correctly.
* Fixed: Menu now links directly to CPT list — no redirect needed.

#### 1.5.1 :: 2026-03-24
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
