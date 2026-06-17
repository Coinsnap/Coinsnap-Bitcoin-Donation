# Donation Form CPT Design

**Date:** 2026-03-23
**Status:** Approved
**Plugin:** Coinsnap Bitcoin Donation

## Summary

Replace the current 3-tab Donation Forms settings page with a Custom Post Type (CPT) system. Users can create unlimited donation forms, each with a form type (Simple Donation, Multi Amount, Shoutout) and all associated fields. Existing forms are automatically migrated to CPT posts, and legacy shortcodes continue to work.

## Custom Post Type

**Slug:** `donation-form`
**Labels:** "Donation Form" / "Donation Forms"
**Supports:** `title` only (no editor, no thumbnail)
**Show in menu:** false (submenu under `coinsnap-bitcoin-donation`)
**Publicly queryable:** false

### Post Meta Keys

All prefixed with `_coinsnap_donation_form_`. Registered via `register_post_meta()` (consistent with the existing shoutout CPT pattern).

| Meta Key | Applies To | Description |
|----------|-----------|-------------|
| `form_type` | All | `simple_donation`, `multi_amount`, `shoutout` |
| `layout` | Simple Donation, Multi Amount | `NARROW`, `WIDE` (shoutout has single layout) |
| `currency` | All | EUR, USD, SATS, BTC, etc. |
| `button_text` | All | Submit button label |
| `title_text` | All | Form heading |
| `default_amount` | All | Pre-filled amount |
| `default_message` | All | Pre-filled message |
| `redirect_url` | All | Post-payment redirect |
| `public_donors` | All | Enable donor info collection (bool) |
| `first_name` | All | optional/mandatory/hidden |
| `last_name` | All | optional/mandatory/hidden |
| `email` | All | optional/mandatory/hidden |
| `address` | All | optional/mandatory/hidden |
| `custom_field_name` | All | Label for custom field |
| `custom_field_visibility` | All | optional/mandatory/hidden |
| `snap1` | Multi Amount | Preset amount button 1 |
| `snap2` | Multi Amount | Preset amount button 2 |
| `snap3` | Multi Amount | Preset amount button 3 |
| `minimum_amount` | Shoutout | Min amount in SATS |
| `premium_amount` | Shoutout | Premium tier in SATS |

### Admin List Columns

Title, Form Type, Layout, Shortcode (copyable), Date.

## Admin Edit Screen

### Form Type Selector

Visual card picker at the top of the metabox — 3 clickable cards side by side:

- **Simple Donation** — SVG icon: single coin/heart — "A simple donation button with a fixed default amount"
- **Multi Amount** — SVG icon: stacked coins/grid — "Donation form with preset amount buttons"
- **Shoutout** — SVG icon: megaphone/star — "Public shoutout board with minimum and premium tiers"

Selected card gets a highlighted border/active state. On type change after first save: JS confirmation dialog — "Switching form type will reset fields. Are you sure?"

### Fields Area

Below the type selector, rendered using `csc-card` and `csc-field-row` components:

- Shared fields (currency, button text, title, amount, message, redirect, layout) always visible
- Type-specific fields (snap amounts for multi, minimum/premium for shoutout) appear conditionally via JS
- Donor info fields in a collapsible section toggled by `public_donors` checkbox

### Shortcode Display

Read-only box at the bottom with copy buttons:

- `[coinsnap_bitcoin_donation_form id="123"]`
- For shoutout type, also shows: `[coinsnap_donation_list id="123"]`

### Save Mechanism

Standard WP post save via `save_post_donation-form` hook into post meta.

## Shortcodes & Rendering

### New Shortcodes

- `[coinsnap_bitcoin_donation_form id="123"]` — renders any form type based on CPT post meta
- `[coinsnap_donation_list id="123"]` — renders shoutout list scoped to that form

### Unified Rendering Flow

1. Shortcode handler receives `id`, loads post meta
2. Reads `form_type` and `layout` from meta
3. Delegates to template file based on type + layout:
   - `templates/simple-donation-narrow.php`
   - `templates/simple-donation-wide.php`
   - `templates/multi-amount-narrow.php`
   - `templates/multi-amount-wide.php`
   - `templates/shoutout-form.php`
   - `templates/shoutout-list.php`

### Legacy Shortcode Handling

All existing shortcodes remain registered: `[coinsnap_bitcoin_donation]`, `[coinsnap_bitcoin_donation_wide]`, `[multi_amount_donation]`, `[multi_amount_donation_wide]`, `[shoutout_form]`, `[shoutout_list]`.

Each resolves its migrated CPT post ID from `coinsnap_donation_migrated_forms` option, then calls the unified rendering logic. If no migrated post found, falls back to old rendering from options array as safety net.

### Shoutout List Scoping

- When a shoutout payment is created, the `donation-form` post ID is stored on the shoutout CPT entry (meta key: `_coinsnap_donation_form_id`)
- The form ID is passed through the payment flow: frontend form includes `donation_form_id` in invoice metadata, webhook handler reads it and stores on the shoutout post
- `[coinsnap_donation_list id="123"]` queries shoutout posts filtered by that form ID
- Legacy `[shoutout_list]` shows all shoutouts (no filter) for backwards compat

## Frontend JS Data Strategy

Currently `wp_localize_script()` passes one global object per form type. With multiple CPT forms on one page, this must change. Each form's HTML container will include `data-*` attributes for its settings (form ID, currency, amount, etc.). The JS reads configuration from the DOM element rather than a global variable. This allows multiple forms of the same type on one page with different settings.

## Migration

### Trigger

Runs on both plugin activation (`register_activation_hook`) and `admin_init`. A flag option `coinsnap_donation_forms_migrated` prevents re-running.

### Steps

1. Read existing `coinsnap_bitcoin_donation_forms_options` array
2. Create all 3 CPT posts (default values are valid configurations):
   - "Simple Donation" — `form_type: simple_donation`, status: `publish`
   - "Multi Amount Donation" — `form_type: multi_amount`, status: `publish`
   - "Shoutout" — `form_type: shoutout`, status: `publish`
3. Map old option keys to new post meta keys and save per the mapping below
4. Read old `form_type` / `multi_amount_form_type` field (NARROW/WIDE) and store as `layout` meta

### Option-to-Meta Key Mapping

**Simple Donation** (old prefix: none / `simple_donation_`):

| Old Option Key | New Meta Key |
|---|---|
| `currency` | `currency` |
| `button_text` | `button_text` |
| `title_text` | `title_text` |
| `default_amount` | `default_amount` |
| `default_message` | `default_message` |
| `redirect_url` | `redirect_url` |
| `form_type` | `layout` |
| `simple_donation_public_donors` | `public_donors` |
| `simple_donation_first_name` | `first_name` |
| `simple_donation_last_name` | `last_name` |
| `simple_donation_email` | `email` |
| `simple_donation_address` | `address` |
| `simple_donation_custom_field_name` | `custom_field_name` |
| `simple_donation_custom_field_visibility` | `custom_field_visibility` |

**Multi Amount** (old prefix: `multi_amount_`):

| Old Option Key | New Meta Key |
|---|---|
| `multi_amount_currency` | `currency` |
| `multi_amount_button_text` | `button_text` |
| `multi_amount_title_text` | `title_text` |
| `multi_amount_default_amount` | `default_amount` |
| `multi_amount_default_message` | `default_message` |
| `multi_amount_redirect_url` | `redirect_url` |
| `multi_amount_form_type` | `layout` |
| `multi_amount_default_snap1` | `snap1` |
| `multi_amount_default_snap2` | `snap2` |
| `multi_amount_default_snap3` | `snap3` |
| `multi_amount_public_donors` | `public_donors` |
| `multi_amount_first_name` | `first_name` |
| `multi_amount_last_name` | `last_name` |
| `multi_amount_email` | `email` |
| `multi_amount_address` | `address` |
| `multi_amount_custom_field_name` | `custom_field_name` |
| `multi_amount_custom_field_visibility` | `custom_field_visibility` |

**Shoutout** (old prefix: `shoutout_`):

| Old Option Key | New Meta Key |
|---|---|
| `shoutout_currency` | `currency` |
| `shoutout_button_text` | `button_text` |
| `shoutout_title_text` | `title_text` |
| `shoutout_default_amount` | `default_amount` |
| `shoutout_default_message` | `default_message` |
| `shoutout_redirect_url` | `redirect_url` |
| `shoutout_minimum_amount` | `minimum_amount` |
| `shoutout_premium_amount` | `premium_amount` |
| `shoutout_public_donors` | `public_donors` |
| `shoutout_first_name` | `first_name` |
| `shoutout_last_name` | `last_name` |
| `shoutout_email` | `email` |
| `shoutout_address` | `address` |
| `shoutout_custom_field_name` | `custom_field_name` |
| `shoutout_custom_field_visibility` | `custom_field_visibility` |
5. Store mapping in `coinsnap_donation_migrated_forms` option:
   ```php
   [
     'coinsnap_bitcoin_donation'      => 101,
     'coinsnap_bitcoin_donation_wide' => 101,
     'multi_amount_donation'          => 102,
     'multi_amount_donation_wide'     => 102,
     'shoutout_form'                  => 103,
     'shoutout_list'                  => 103,
   ]
   ```
6. Update existing `bitcoin-shoutouts` CPT entries — add `_coinsnap_donation_form_id` meta pointing to the migrated shoutout form post ID
7. Do NOT delete `coinsnap_bitcoin_donation_forms_options` — keep as fallback
8. Set `coinsnap_donation_forms_migrated` to `true`

### Rollback Safety

Old options preserved. If migration flag is manually deleted, migration re-runs without data loss (creates new posts).

## Menu & Navigation

### New Structure

- **Donation Forms** — `edit.php?post_type=donation-form` (CPT list)
- **Add New Form** — `post-new.php?post_type=donation-form`
- Shoutouts (unchanged)
- Donor Information (unchanged)
- Transactions (unchanged)
- Settings (unchanged)
- Logs (unchanged)

The old `render_donation_forms_page()` and `Coinsnap_Bitcoin_Donation_Forms` settings registration are removed.

## Uninstall Cleanup

On plugin uninstall (`uninstall.php`), in addition to existing cleanup:

- Delete all `donation-form` CPT posts and their post meta
- Delete `coinsnap_donation_migrated_forms` option
- Delete `coinsnap_donation_forms_migrated` option

## Files

### New Files

- `includes/class-coinsnap-bitcoin-donation-form-cpt.php` — CPT registration, metabox rendering, save logic, admin columns, SVG icons
- `includes/class-coinsnap-bitcoin-donation-form-renderer.php` — Unified rendering class for new and legacy shortcodes
- `includes/class-coinsnap-bitcoin-donation-migration.php` — One-time migration logic
- `templates/simple-donation-narrow.php` — refactored from inline HTML in shortcode class to accept form meta as variables
- `templates/simple-donation-wide.php` — refactored from inline HTML in wide shortcode class
- `templates/multi-amount-narrow.php` — refactored from inline HTML in multi-amount shortcode class
- `templates/multi-amount-wide.php` — refactored from inline HTML in multi-amount wide shortcode class
- `templates/shoutout-form.php` — refactored from inline HTML in shoutout form class
- `templates/shoutout-list.php` — refactored from inline HTML in shoutout list class
- `assets/js/donation-form-admin.js` — Form type switcher, field show/hide, type change confirmation
- `assets/css/donation-form-admin.css` — Form type card picker styling, metabox layout

### Modified Files

- `coinsnap-bitcoin-donation.php` — Register new CPT class, new shortcodes, trigger migration on activation, update admin script enqueuing for CPT screens
- `includes/class-coinsnap-bitcoin-donation-settings.php` — Update menu to point to CPT list
- `includes/class-coinsnap-bitcoin-donation-webhooks.php` — Store `_coinsnap_donation_form_id` on shoutout posts from invoice metadata
- `uninstall.php` — Add cleanup for donation-form CPT posts and migration options
- Existing shortcode classes — Become thin wrappers delegating to unified renderer

### Kept but No Longer Loaded

- `includes/class-coinsnap-bitcoin-donation-forms.php` — Old settings-API forms page, kept as fallback reference
