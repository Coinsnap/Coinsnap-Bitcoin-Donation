# Donation Form CPT Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the 3-tab Donation Forms settings page with a Custom Post Type system that supports unlimited forms with automatic migration of existing data.

**Architecture:** New `donation-form` CPT with all form settings stored as post meta. A unified renderer loads meta and delegates to template files (extracted from current inline HTML). Legacy shortcodes become thin wrappers that resolve to migrated CPT post IDs. Frontend JS switches from global `wp_localize_script()` data to `data-*` attributes on form containers for multi-form support.

**Tech Stack:** WordPress CPT API, `register_post_meta()`, jQuery (existing), `csc-*` UI components from coinsnap-core.

**Spec:** `docs/superpowers/specs/2026-03-23-donation-form-cpt-design.md`

---

## File Structure

### New Files

| File | Responsibility |
|------|---------------|
| `includes/class-coinsnap-bitcoin-donation-form-cpt.php` | CPT registration, `register_post_meta()`, metabox rendering (form type cards + fields), `save_post` handler, admin list columns, SVG icons |
| `includes/class-coinsnap-bitcoin-donation-form-renderer.php` | Unified shortcode handler — loads CPT post meta, resolves template, renders. Handles both new `[coinsnap_bitcoin_donation_form]` and `[coinsnap_donation_list]` shortcodes |
| `includes/class-coinsnap-bitcoin-donation-migration.php` | One-time migration: reads old options, creates CPT posts, maps old shortcode names to new post IDs, tags existing shoutout posts with form ID |
| `templates/simple-donation-narrow.php` | Narrow simple donation form (refactored from `class-coinsnap-bitcoin-donation-shortcode.php` inline HTML) |
| `templates/simple-donation-wide.php` | Wide simple donation form (refactored from `class-coinsnap-bitcoin-donation-shortcode-wide.php`) |
| `templates/multi-amount-narrow.php` | Narrow multi-amount form (refactored from `class-coinsnap-bitcoin-donation-shortcode-multi-amount.php`) |
| `templates/multi-amount-wide.php` | Wide multi-amount form (refactored from `class-coinsnap-bitcoin-donation-shortcode-multi-amount-wide.php`) |
| `templates/shoutout-form.php` | Shoutout form (refactored from `class-coinsnap-bitcoin-donation-shoutouts-form.php`) |
| `templates/shoutout-list.php` | Shoutout list (refactored from `class-coinsnap-bitcoin-donation-shoutouts-list.php`) |
| `assets/js/donation-form-admin.js` | Admin metabox JS: form type card switching, field show/hide, type change confirmation dialog |
| `assets/css/donation-form-admin.css` | Admin metabox CSS: form type card picker layout, active states, conditional field sections |

### Modified Files

| File | Changes |
|------|---------|
| `coinsnap-bitcoin-donation.php` | Require new classes, trigger migration on activation, update `enqueue_frontend_scripts()` to use `data-*` attributes, update `enqueue_admin_scripts()` to load on CPT screens |
| `includes/class-coinsnap-bitcoin-donation-settings.php` | Remove forms page dependency, update menu to point to CPT list, add "Add New Form" submenu |
| `includes/class-coinsnap-bitcoin-donation-webhooks.php` | In `process_webhook()`, store `_coinsnap_donation_form_id` on shoutout posts from invoice metadata |
| `includes/class-coinsnap-bitcoin-donation-shortcode.php` | Convert to thin wrapper delegating to renderer |
| `includes/class-coinsnap-bitcoin-donation-shortcode-wide.php` | Convert to thin wrapper delegating to renderer |
| `includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount.php` | Convert to thin wrapper delegating to renderer |
| `includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount-wide.php` | Convert to thin wrapper delegating to renderer |
| `includes/class-coinsnap-bitcoin-donation-shoutouts-form.php` | Convert to thin wrapper delegating to renderer |
| `includes/class-coinsnap-bitcoin-donation-shoutouts-list.php` | Convert to thin wrapper delegating to renderer |
| `uninstall.php` | Add cleanup for `donation-form` CPT posts and migration options |
| `assets/js/donations.js` | Read config from `data-*` attributes on form container instead of global `coinsnapDonationFormData` |
| `assets/js/multi.js` | Read config from `data-*` attributes instead of global `coinsnapDonationMultiData` |
| `assets/js/shoutouts.js` | Read config from `data-*` attributes instead of global `coinsnapDonationShoutoutsData` |

---

## Task 1: Register the Donation Form CPT and Post Meta

**Files:**
- Create: `includes/class-coinsnap-bitcoin-donation-form-cpt.php`
- Modify: `coinsnap-bitcoin-donation.php:29-39` (add require)

- [ ] **Step 1: Create the CPT class with registration**

Create `includes/class-coinsnap-bitcoin-donation-form-cpt.php` with:
- Class `Coinsnap_Bitcoin_Donation_Form_CPT`
- Constructor hooks `init` for `register_cpt()` and `register_meta_fields()`
- `register_cpt()`: registers `donation-form` post type with `title` support only, `public => false`, `publicly_queryable => false`, `show_ui => true`, `show_in_menu => false`, `show_in_rest => true`
- `register_meta_fields()`: calls `register_post_meta()` for all meta keys from the spec (prefixed with `_coinsnap_donation_form_`): `form_type`, `layout`, `currency`, `button_text`, `title_text`, `default_amount`, `default_message`, `redirect_url`, `public_donors`, `first_name`, `last_name`, `email`, `address`, `custom_field_name`, `custom_field_visibility`, `snap1`, `snap2`, `snap3`, `minimum_amount`, `premium_amount`

Use `register_post_meta()` which is a cleaner wrapper around `register_meta()` used in the shoutout-posts class.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Form_CPT {

    const POST_TYPE = 'donation-form';
    const META_PREFIX = '_coinsnap_donation_form_';

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'init', array( $this, 'register_meta_fields' ) );
    }

    public function register_cpt() {
        register_post_type( self::POST_TYPE, array(
            'labels' => array(
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
        ) );
    }

    public function register_meta_fields() {
        $string_fields = array(
            'form_type', 'layout', 'currency', 'button_text', 'title_text',
            'default_amount', 'default_message', 'redirect_url',
            'first_name', 'last_name', 'email', 'address',
            'custom_field_name', 'custom_field_visibility',
            'snap1', 'snap2', 'snap3', 'minimum_amount', 'premium_amount',
        );

        foreach ( $string_fields as $field ) {
            register_post_meta( self::POST_TYPE, self::META_PREFIX . $field, array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
            ) );
        }

        // Register public_donors as string ('1' or '') to match save handler pattern
        register_post_meta( self::POST_TYPE, self::META_PREFIX . 'public_donors', array(
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => '',
        ) );
    }
}
```

- [ ] **Step 2: Require the new class in the main plugin file**

In `coinsnap-bitcoin-donation.php`, after line 39 (the webhooks require), add:

```php
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-form-cpt.php';
```

And at the bottom of the file (before the `plugins_loaded` hook at line 344), instantiate:

```php
new Coinsnap_Bitcoin_Donation_Form_CPT();
```

- [ ] **Step 3: Verify CPT appears in WP admin**

Navigate to the WordPress admin. The CPT won't have a menu item yet (that's Task 3), but confirm it's registered by checking that `post_type_exists('donation-form')` returns true. You can verify by visiting `wp-admin/edit.php?post_type=donation-form` directly.

- [ ] **Step 4: Commit**

```bash
git add includes/class-coinsnap-bitcoin-donation-form-cpt.php coinsnap-bitcoin-donation.php
git commit -m "feat: register donation-form CPT with post meta fields"
```

---

## Task 2: Add Admin Metabox with Form Type Selector and Fields

**Files:**
- Modify: `includes/class-coinsnap-bitcoin-donation-form-cpt.php`
- Create: `assets/js/donation-form-admin.js`
- Create: `assets/css/donation-form-admin.css`
- Modify: `coinsnap-bitcoin-donation.php` (enqueue admin assets)

- [ ] **Step 1: Add metabox registration and rendering to the CPT class**

In `Coinsnap_Bitcoin_Donation_Form_CPT`, add to the constructor:

```php
add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
```

Add the `add_metaboxes()` method that registers a single metabox `coinsnap_donation_form_settings` with title "Form Settings", rendering in `normal` context with `high` priority. Use `remove_meta_box('submitdiv', ...)` and re-add it to the side to keep a clean layout.

Add `render_metabox( $post )` method that outputs:
1. Nonce field (`coinsnap_donation_form_nonce`)
2. **Form Type Card Picker** — 3 cards with inline SVG icons, radio-button-backed selection. Each card has class `donation-form-type-card`, a `data-type` attribute, and contains the SVG icon + title + description. The currently selected card gets `active` class.
3. **Shared Fields Section** — currency (select from `COINSNAP_CURRENCIES`), button_text, title_text, default_amount, default_message, redirect_url, layout (select: NARROW/WIDE — wrapped in a div with `data-show-for="simple_donation multi_amount"`)
4. **Multi Amount Fields** — snap1, snap2, snap3 (wrapped in div with `data-show-for="multi_amount"`)
5. **Shoutout Fields** — minimum_amount, premium_amount (wrapped in div with `data-show-for="shoutout"`)
6. **Donor Info Section** — public_donors checkbox, then first_name/last_name/email/address/custom_field_name/custom_field_visibility fields inside a collapsible div toggled by the checkbox
7. **Shortcode Display** — read-only inputs showing `[coinsnap_bitcoin_donation_form id="X"]` with copy button. For shoutout type, also show `[coinsnap_donation_list id="X"]`.

Use `csc-card`, `csc-field-row`, `csc-field-input` classes to match existing admin UI. Render fields using `get_post_meta()` for values.

SVG icons (inline, ~24x24 viewbox):
- Simple Donation: heart with bitcoin symbol
- Multi Amount: three stacked coins
- Shoutout: megaphone

- [ ] **Step 2: Add save_meta() handler**

```php
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
        'custom_field_name', 'custom_field_visibility',
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
}
```

- [ ] **Step 3: Create admin JS for the metabox**

Create `assets/js/donation-form-admin.js`:
- On load, read the hidden `_coinsnap_donation_form_form_type` input value
- Add click handlers to `.donation-form-type-card` elements
- On card click: if post is already saved (has ID) and current type differs, show `confirm()` dialog "Switching form type will reset type-specific fields. Are you sure?"
- If confirmed (or first save), update the hidden input, toggle `active` class, show/hide field sections based on `data-show-for` attributes
- Toggle donor info fields visibility based on `public_donors` checkbox
- Copy-to-clipboard handler for shortcode display using `csc-shortcode-copy` pattern
- Show/hide the shoutout list shortcode based on selected type

- [ ] **Step 4: Create admin CSS for the metabox**

Create `assets/css/donation-form-admin.css`:
- `.donation-form-type-cards` — flexbox row with gap
- `.donation-form-type-card` — border, padding, border-radius, cursor pointer, transition
- `.donation-form-type-card.active` — highlighted border (#f7931a bitcoin orange), subtle background
- `.donation-form-type-card svg` — 48x48, centered
- `.donation-form-type-card h4` — form type title
- `.donation-form-type-card p` — description text, muted color
- `.donation-form-conditional` — hidden by default, shown when matching type selected
- `.donation-form-donor-fields` — collapsible section for donor info

- [ ] **Step 5: Update admin script enqueuing**

In `coinsnap-bitcoin-donation.php`, modify `enqueue_admin_scripts()` to also load on CPT edit screens. The current check at line 316 only matches `coinsnap-bitcoin-donation` in the `page` query param. Add a check for `$hook === 'post.php' || $hook === 'post-new.php'` with `get_post_type() === 'donation-form'`:

```php
$is_cpt_screen = in_array( $hook, array( 'post.php', 'post-new.php' ), true )
    && get_post_type() === 'donation-form';

if ( $is_cpt_screen ) {
    wp_enqueue_style( 'coinsnap-core-admin', ... ); // same as existing
    wp_enqueue_script( 'coinsnap-core-admin', ... ); // same as existing
    wp_enqueue_style( 'coinsnap-donation-form-admin', plugin_dir_url( __FILE__ ) . 'assets/css/donation-form-admin.css', array(), COINSNAP_BITCOIN_DONATION_VERSION );
    wp_enqueue_script( 'coinsnap-donation-form-admin', plugin_dir_url( __FILE__ ) . 'assets/js/donation-form-admin.js', array( 'jquery' ), COINSNAP_BITCOIN_DONATION_VERSION, true );
}
```

- [ ] **Step 6: Test creating a form in admin**

Go to `wp-admin/post-new.php?post_type=donation-form`, verify:
- Form type cards render with SVG icons
- Clicking a card shows/hides appropriate fields
- Saving a form stores all meta values correctly
- Shortcode display shows correct ID after save

- [ ] **Step 7: Commit**

```bash
git add includes/class-coinsnap-bitcoin-donation-form-cpt.php assets/js/donation-form-admin.js assets/css/donation-form-admin.css coinsnap-bitcoin-donation.php
git commit -m "feat: add admin metabox with form type selector and fields"
```

---

## Task 3: Update Admin Menu to Use CPT List

**Files:**
- Modify: `includes/class-coinsnap-bitcoin-donation-settings.php`
- Modify: `includes/class-coinsnap-bitcoin-donation-form-cpt.php` (admin columns)

- [ ] **Step 1: Update the menu in settings class**

In `class-coinsnap-bitcoin-donation-settings.php`:
- Remove `require_once` for `class-coinsnap-bitcoin-donation-forms.php` (line 6)
- Remove `$this->donation_forms` property and its constructor assignment (lines 10, 13)
- Change the parent menu page callback from `$render_forms` to `null` (it will redirect to CPT list)
- Change the first submenu page (lines 40-46) to point to `edit.php?post_type=donation-form`
- Add a new "Add New Form" submenu page pointing to `post-new.php?post_type=donation-form`, inserted after the Donation Forms submenu

```php
// First submenu replaces auto-generated parent label
add_submenu_page(
    'coinsnap-bitcoin-donation',
    __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
    __( 'Donation Forms', 'coinsnap-bitcoin-donation' ),
    'manage_options',
    'edit.php?post_type=donation-form'
);

add_submenu_page(
    'coinsnap-bitcoin-donation',
    __( 'Add New Form', 'coinsnap-bitcoin-donation' ),
    __( 'Add New Form', 'coinsnap-bitcoin-donation' ),
    'manage_options',
    'post-new.php?post_type=donation-form'
);
```

Also update the parent `add_menu_page` callback to redirect to the CPT list:

```php
add_menu_page(
    __( 'Coinsnap Bitcoin Donation', 'coinsnap-bitcoin-donation' ),
    __( 'Coinsnap Bitcoin Donation', 'coinsnap-bitcoin-donation' ),
    'manage_options',
    'coinsnap-bitcoin-donation',
    function() {
        wp_redirect( admin_url( 'edit.php?post_type=donation-form' ) );
        exit;
    },
    plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/bitcoin.svg',
    100
);
```

- [ ] **Step 2: Add admin list columns to the CPT class**

In `Coinsnap_Bitcoin_Donation_Form_CPT` constructor, add:

```php
add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_columns' ) );
add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
```

Implement `add_columns()`:
```php
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
```

Implement `render_column()`:
- `form_type`: Display human-readable label (Simple Donation / Multi Amount / Shoutout)
- `layout`: Display NARROW / WIDE or "—" for shoutout
- `shortcode`: Render a copyable `[coinsnap_bitcoin_donation_form id="X"]` using `csc-shortcode-copy` markup

- [ ] **Step 3: Fix CPT parent menu highlighting**

Add a filter to set the correct parent menu for the CPT screens:

```php
add_filter( 'parent_file', array( $this, 'fix_parent_menu' ) );
add_filter( 'submenu_file', array( $this, 'fix_submenu_highlight' ) );
```

```php
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
        if ( $screen->base === 'post' ) {
            return 'edit.php?post_type=donation-form';
        }
        return 'edit.php?post_type=donation-form';
    }
    return $submenu_file;
}
```

- [ ] **Step 4: Verify menu works**

Navigate WP admin and confirm:
- "Donation Forms" menu item opens the CPT list
- "Add New Form" submenu item opens the new form editor
- Menu highlighting is correct on all CPT screens
- List table shows Form Type, Layout, and Shortcode columns

- [ ] **Step 5: Commit**

```bash
git add includes/class-coinsnap-bitcoin-donation-settings.php includes/class-coinsnap-bitcoin-donation-form-cpt.php
git commit -m "feat: update admin menu to use CPT list with custom columns"
```

---

## Task 4: Extract Templates from Inline Shortcode HTML

**Files:**
- Create: `templates/simple-donation-narrow.php`
- Create: `templates/simple-donation-wide.php`
- Create: `templates/multi-amount-narrow.php`
- Create: `templates/multi-amount-wide.php`
- Create: `templates/shoutout-form.php`
- Create: `templates/shoutout-list.php`

- [ ] **Step 1: Extract simple-donation-narrow template**

Take the HTML from `class-coinsnap-bitcoin-donation-shortcode.php` lines 55-103. Refactor it to accept variables passed from the renderer:
- `$form_id` — the CPT post ID (used to make element IDs unique: append `-{$form_id}`)
- `$theme_class`, `$modal_theme`
- `$title_text`, `$button_text`, `$default_currency`
- `$first_name`, `$last_name`, `$email`, `$address`, `$public_donors`, `$custom`, `$custom_name`
- `$rates`, `$coinsnapCurrencies`

Key changes from original:
- All element IDs get a `-{$form_id}` suffix for uniqueness (e.g., `coinsnap-bitcoin-donation-amount-{$form_id}`)
- Add `data-*` attributes on the form container div for JS config: `data-form-id`, `data-currency`, `data-default-amount`, `data-default-message`, `data-redirect-url`
- Include the modal template at the end via `include` with appropriate prefix/suffix variables

- [ ] **Step 2: Extract simple-donation-wide template**

Same approach as Step 1 but from `class-coinsnap-bitcoin-donation-shortcode-wide.php` lines 58-114. The HTML structure is the wide layout variant. Use `wide-form` class and wide-specific element IDs with `$form_id` suffix.

- [ ] **Step 3: Extract multi-amount-narrow template**

From `class-coinsnap-bitcoin-donation-shortcode-multi-amount.php` lines 63-138. Additional variables needed: `$snap1`, `$snap2`, `$snap3`. Add `data-snap1`, `data-snap2`, `data-snap3` attributes on the container.

- [ ] **Step 4: Extract multi-amount-wide template**

From `class-coinsnap-bitcoin-donation-shortcode-multi-amount-wide.php` lines 62-146. Same snap variables + wide layout structure.

- [ ] **Step 5: Extract shoutout-form template**

From `class-coinsnap-bitcoin-donation-shoutouts-form.php` lines 60-139. Additional variables: `$min_amount`, `$premium_amount`. Add `data-minimum-amount`, `data-premium-amount` attributes. Also add `data-form-id` for the donation_form_id metadata propagation.

- [ ] **Step 6: Extract shoutout-list template**

From `class-coinsnap-bitcoin-donation-shoutouts-list.php` lines 53-67. This template receives `$shoutouts` array and `$theme_class`. Also receives `$premium_amount` for highlight logic. The `render_donation_row` and `render_empty_donation_row` logic moves into this template file as inline PHP.

- [ ] **Step 7: Verify all templates are syntactically valid PHP**

Run `php -l templates/*.php` on each new template file to check for syntax errors.

```bash
for f in templates/simple-donation-narrow.php templates/simple-donation-wide.php templates/multi-amount-narrow.php templates/multi-amount-wide.php templates/shoutout-form.php templates/shoutout-list.php; do php -l "$f"; done
```

- [ ] **Step 8: Commit**

```bash
git add templates/simple-donation-narrow.php templates/simple-donation-wide.php templates/multi-amount-narrow.php templates/multi-amount-wide.php templates/shoutout-form.php templates/shoutout-list.php
git commit -m "feat: extract form templates from inline shortcode HTML"
```

---

## Task 5: Create Unified Form Renderer

**Files:**
- Create: `includes/class-coinsnap-bitcoin-donation-form-renderer.php`
- Modify: `coinsnap-bitcoin-donation.php` (require + register shortcodes)

- [ ] **Step 1: Create the renderer class**

Create `includes/class-coinsnap-bitcoin-donation-form-renderer.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Form_Renderer {

    const META_PREFIX = '_coinsnap_donation_form_';

    public function __construct() {
        add_shortcode( 'coinsnap_bitcoin_donation_form', array( $this, 'render_form' ) );
        add_shortcode( 'coinsnap_donation_list', array( $this, 'render_list' ) );
    }

    public static function render_form( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0, 'layout' => '' ), $atts );
        $post_id = absint( $atts['id'] );

        if ( ! $post_id || get_post_type( $post_id ) !== 'donation-form' ) {
            return '';
        }

        $meta = self::load_meta( $post_id );
        $form_type = $meta['form_type'] ?? 'simple_donation';
        // Allow layout override from legacy wide shortcodes
        $layout = ! empty( $atts['layout'] ) ? $atts['layout'] : ( $meta['layout'] ?: 'NARROW' );

        $template_map = array(
            'simple_donation_NARROW' => 'simple-donation-narrow',
            'simple_donation_WIDE'   => 'simple-donation-wide',
            'multi_amount_NARROW'    => 'multi-amount-narrow',
            'multi_amount_WIDE'      => 'multi-amount-wide',
            'shoutout'               => 'shoutout-form',
        );

        $key = ( $form_type === 'shoutout' ) ? 'shoutout' : $form_type . '_' . $layout;
        $template_name = $template_map[ $key ] ?? 'simple-donation-narrow';

        return $this->render_template( $template_name, $meta, $post_id );
    }

    public static function render_list( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts );
        $post_id = absint( $atts['id'] );

        if ( ! $post_id || get_post_type( $post_id ) !== 'donation-form' ) {
            return '';
        }

        $meta = self::load_meta( $post_id );

        // Query shoutout posts scoped to this form
        $query_args = array(
            'post_type'      => 'bitcoin-shoutouts',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_coinsnap_donation_form_id',
                    'value' => $post_id,
                ),
            ),
        );
        $query = new WP_Query( $query_args );
        $shoutouts = array();

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $shoutouts[] = array(
                    'date'        => $post->post_date,
                    'name'        => get_post_meta( $post->ID, '_coinsnap_bitcoin_donation_shoutouts_name', true ),
                    'amount'      => get_post_meta( $post->ID, '_coinsnap_bitcoin_donation_shoutouts_amount', true ),
                    'sats_amount' => get_post_meta( $post->ID, '_coinsnap_bitcoin_donation_shoutouts_sats_amount', true ),
                    'message'     => get_post_meta( $post->ID, '_coinsnap_bitcoin_donation_shoutouts_message', true ),
                );
            }
        }
        wp_reset_postdata();

        // Pass $shoutouts to the template (available as variable in scope)
        return self::render_template( 'shoutout-list', $meta, $post_id, $shoutouts );
    }

    private static function load_meta( $post_id ) {
        $fields = array(
            'form_type', 'layout', 'currency', 'button_text', 'title_text',
            'default_amount', 'default_message', 'redirect_url', 'public_donors',
            'first_name', 'last_name', 'email', 'address',
            'custom_field_name', 'custom_field_visibility',
            'snap1', 'snap2', 'snap3', 'minimum_amount', 'premium_amount',
        );

        $meta = array();
        foreach ( $fields as $field ) {
            $meta[ $field ] = get_post_meta( $post_id, self::META_PREFIX . $field, true );
        }
        return $meta;
    }

    private static function render_template( $template_name, $meta, $form_id, $shoutouts = array() ) {
        $core = coinsnap_bitcoin_donation_get_core();
        $core_settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        $theme = $core_settings['theme'] ?? 'light';
        $theme_class = $theme === 'dark' ? 'coinsnap-bitcoin-donation-dark-theme' : 'coinsnap-bitcoin-donation-light-theme';
        $modal_theme = $theme === 'dark' ? 'dark-theme' : 'light-theme';

        $coinsnapCurrencies = defined( 'COINSNAP_CURRENCIES' ) ? COINSNAP_CURRENCIES : array( 'EUR', 'USD', 'SATS', 'BTC', 'CAD', 'JPY', 'GBP', 'CHF', 'RUB' );
        $exchange = new \CoinsnapCore\Util\ExchangeRates();
        $rates = $exchange->load_rates();

        // Extract meta to template variables
        $title_text      = $meta['title_text'] ?: __( 'Donate with Bitcoin', 'coinsnap-bitcoin-donation' );
        $button_text     = $meta['button_text'] ?: __( 'Donate', 'coinsnap-bitcoin-donation' );
        $default_currency = $meta['currency'] ?: 'EUR';
        $first_name      = $meta['first_name'] ?: 'hidden';
        $last_name       = $meta['last_name'] ?: 'hidden';
        $email           = $meta['email'] ?: 'hidden';
        $address         = $meta['address'] ?: 'hidden';
        $public_donors   = $meta['public_donors'] ?: '';
        $custom          = $meta['custom_field_visibility'] ?: 'hidden';
        $custom_name     = $meta['custom_field_name'] ?: '';
        $default_amount  = $meta['default_amount'] ?: '5';
        $default_message = $meta['default_message'] ?: __( 'Thank you for your support!', 'coinsnap-bitcoin-donation' );
        $redirect_url    = $meta['redirect_url'] ?: home_url();
        $snap1           = $meta['snap1'] ?: '1000';
        $snap2           = $meta['snap2'] ?: '5000';
        $snap3           = $meta['snap3'] ?: '10000';
        $min_amount      = (float) ( $meta['minimum_amount'] ?: 5 );
        $premium_amount  = (float) ( $meta['premium_amount'] ?: 50 );

        $template_path = COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'templates/' . $template_name . '.php';

        if ( ! file_exists( $template_path ) ) {
            return '';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }
}
```

- [ ] **Step 2: Require and instantiate in the main plugin file**

In `coinsnap-bitcoin-donation.php`, after the form CPT require:

```php
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-form-renderer.php';
```

At the bottom (next to the CPT instantiation):

```php
new Coinsnap_Bitcoin_Donation_Form_Renderer();
```

- [ ] **Step 3: Test the new shortcodes**

Create a test donation form CPT post via admin. Then use `[coinsnap_bitcoin_donation_form id="X"]` in a page. Verify it renders the correct form type and layout with correct field values.

- [ ] **Step 4: Commit**

```bash
git add includes/class-coinsnap-bitcoin-donation-form-renderer.php coinsnap-bitcoin-donation.php
git commit -m "feat: add unified form renderer with new shortcodes"
```

---

## Task 6: Update Frontend JS for Per-Form Data Attributes

**Files:**
- Modify: `assets/js/donations.js`
- Modify: `assets/js/multi.js`
- Modify: `assets/js/shoutouts.js`
- Modify: `coinsnap-bitcoin-donation.php` (enqueue_frontend_scripts)

- [ ] **Step 1: Update donations.js**

Refactor to find forms by container class/data attribute instead of hard-coded element IDs. For each `.coinsnap-donation-form-instance[data-form-type="simple_donation"]` on the page:
- Read `data-currency`, `data-default-amount`, `data-default-message`, `data-redirect-url` from the container
- Use the `data-form-id` suffix for all element ID lookups
- Replace references to the global `coinsnapDonationFormData` with container data attributes
- Keep the same jQuery event binding pattern but scope it per form instance

The key pattern change:
```javascript
// OLD:
var selectedCurrency = coinsnapDonationFormData.currency;
document.getElementById('coinsnap-bitcoin-donation-amount').value = coinsnapDonationFormData.defaultAmount;

// NEW:
document.querySelectorAll('.coinsnap-donation-form-instance[data-form-type="simple_donation"]').forEach(function(container) {
    var formId = container.dataset.formId;
    var selectedCurrency = container.dataset.currency;
    document.getElementById('coinsnap-bitcoin-donation-amount-' + formId).value = container.dataset.defaultAmount;
});
```

- [ ] **Step 2: Update multi.js**

Same refactoring pattern as donations.js. Find all `.coinsnap-donation-form-instance[data-form-type="multi_amount"]` containers. Read snap amounts from `data-snap1`, `data-snap2`, `data-snap3`. Use `data-form-id` for element ID suffixes.

- [ ] **Step 3: Update shoutouts.js**

Same pattern. Find all `.coinsnap-donation-form-instance[data-form-type="shoutout"]` containers. Read minimum/premium amounts from `data-minimum-amount`, `data-premium-amount`. Use `data-form-id` for element ID suffixes.

- [ ] **Step 4: Update enqueue_frontend_scripts()**

In `coinsnap-bitcoin-donation.php`, the `enqueue_frontend_scripts()` method currently passes global config via `wp_localize_script()`. After migration, the config comes from `data-*` attributes on the template HTML, so we can simplify:

- Keep the shared data localization (`coinsnapDonationSharedData` with `restUrl`, `nonce`, `theme`) — this is still global
- Remove the `coinsnapDonationFormData`, `coinsnapDonationMultiData`, and `coinsnapDonationShoutoutsData` localization calls — this data now comes from `data-*` attributes
- Keep all CSS and JS enqueues (the JS files still need to load)

- [ ] **Step 5: Test multiple forms on one page**

Create 2 simple donation forms with different currencies/amounts. Place both shortcodes on one page. Verify each form initializes independently with its own settings and submits payments correctly.

- [ ] **Step 6: Commit**

```bash
git add assets/js/donations.js assets/js/multi.js assets/js/shoutouts.js coinsnap-bitcoin-donation.php
git commit -m "feat: update frontend JS to use per-form data attributes"
```

---

## Task 7: Create Migration Logic

**Files:**
- Create: `includes/class-coinsnap-bitcoin-donation-migration.php`
- Modify: `coinsnap-bitcoin-donation.php` (trigger migration)

- [ ] **Step 1: Create the migration class**

Create `includes/class-coinsnap-bitcoin-donation-migration.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Migration {

    const FLAG_KEY = 'coinsnap_donation_forms_migrated';
    const MAP_KEY  = 'coinsnap_donation_migrated_forms';
    const META_PREFIX = '_coinsnap_donation_form_';

    public static function maybe_migrate() {
        if ( get_option( self::FLAG_KEY ) ) {
            return;
        }
        self::run_migration();
    }

    private static function run_migration() {
        $options = get_option( 'coinsnap_bitcoin_donation_forms_options', array() );
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $mapping = array();

        // Migrate Simple Donation
        $simple_id = self::create_form_post( 'Simple Donation', 'simple_donation', $options, array(
            'currency'                => 'currency',
            'button_text'             => 'button_text',
            'title_text'              => 'title_text',
            'default_amount'          => 'default_amount',
            'default_message'         => 'default_message',
            'redirect_url'            => 'redirect_url',
            'form_type'               => 'layout',
            'simple_donation_public_donors' => 'public_donors',
            'simple_donation_first_name'    => 'first_name',
            'simple_donation_last_name'     => 'last_name',
            'simple_donation_email'         => 'email',
            'simple_donation_address'       => 'address',
            'simple_donation_custom_field_name'       => 'custom_field_name',
            'simple_donation_custom_field_visibility' => 'custom_field_visibility',
        ) );
        $mapping['coinsnap_bitcoin_donation']      = $simple_id;
        $mapping['coinsnap_bitcoin_donation_wide'] = $simple_id;

        // Migrate Multi Amount
        $multi_id = self::create_form_post( 'Multi Amount Donation', 'multi_amount', $options, array(
            'multi_amount_currency'      => 'currency',
            'multi_amount_button_text'   => 'button_text',
            'multi_amount_title_text'    => 'title_text',
            'multi_amount_default_amount'  => 'default_amount',
            'multi_amount_default_message' => 'default_message',
            'multi_amount_redirect_url'    => 'redirect_url',
            'multi_amount_form_type'       => 'layout',
            'multi_amount_default_snap1'   => 'snap1',
            'multi_amount_default_snap2'   => 'snap2',
            'multi_amount_default_snap3'   => 'snap3',
            'multi_amount_public_donors'   => 'public_donors',
            'multi_amount_first_name'      => 'first_name',
            'multi_amount_last_name'       => 'last_name',
            'multi_amount_email'           => 'email',
            'multi_amount_address'         => 'address',
            'multi_amount_custom_field_name'       => 'custom_field_name',
            'multi_amount_custom_field_visibility' => 'custom_field_visibility',
        ) );
        $mapping['multi_amount_donation']      = $multi_id;
        $mapping['multi_amount_donation_wide'] = $multi_id;

        // Migrate Shoutout
        $shoutout_id = self::create_form_post( 'Shoutout', 'shoutout', $options, array(
            'shoutout_currency'       => 'currency',
            'shoutout_button_text'    => 'button_text',
            'shoutout_title_text'     => 'title_text',
            'shoutout_default_amount' => 'default_amount',
            'shoutout_default_message'  => 'default_message',
            'shoutout_redirect_url'     => 'redirect_url',
            'shoutout_minimum_amount'   => 'minimum_amount',
            'shoutout_premium_amount'   => 'premium_amount',
            'shoutout_public_donors'    => 'public_donors',
            'shoutout_first_name'       => 'first_name',
            'shoutout_last_name'        => 'last_name',
            'shoutout_email'            => 'email',
            'shoutout_address'          => 'address',
            'shoutout_custom_field_name'       => 'custom_field_name',
            'shoutout_custom_field_visibility' => 'custom_field_visibility',
        ) );
        $mapping['shoutout_form'] = $shoutout_id;
        $mapping['shoutout_list'] = $shoutout_id;

        // Tag existing shoutout posts with the migrated form ID
        $shoutout_posts = get_posts( array(
            'post_type'      => 'bitcoin-shoutouts',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );
        foreach ( $shoutout_posts as $sp_id ) {
            update_post_meta( $sp_id, '_coinsnap_donation_form_id', $shoutout_id );
        }

        update_option( self::MAP_KEY, $mapping );
        update_option( self::FLAG_KEY, '1' );
    }

    private static function create_form_post( $title, $form_type, $old_options, $field_map ) {
        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_type'   => 'donation-form',
        ) );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return 0;
        }

        update_post_meta( $post_id, self::META_PREFIX . 'form_type', $form_type );

        foreach ( $field_map as $old_key => $new_key ) {
            $value = $old_options[ $old_key ] ?? '';
            update_post_meta( $post_id, self::META_PREFIX . $new_key, $value );
        }

        return $post_id;
    }
}
```

- [ ] **Step 2: Require and trigger migration**

In `coinsnap-bitcoin-donation.php`:

Add require after the renderer require:
```php
require_once COINSNAP_BITCOIN_DONATION_PLUGIN_PATH . 'includes/class-coinsnap-bitcoin-donation-migration.php';
```

Add to activation hook (`coinsnap_bitcoin_donation_activate()`):
```php
Coinsnap_Bitcoin_Donation_Migration::maybe_migrate();
```

Add to the existing `admin_init` hook at line 349:
```php
add_action( 'admin_init', function () {
    Coinsnap_Bitcoin_Donation_Migration::maybe_migrate();
} );
```

- [ ] **Step 3: Test migration with existing data**

Set up test data in `coinsnap_bitcoin_donation_forms_options` with values for all 3 form types. Run the migration (deactivate/reactivate plugin or visit admin). Verify:
- 3 CPT posts created with correct form types
- All meta values mapped correctly
- `coinsnap_donation_migrated_forms` option has correct shortcode-to-post-ID mapping
- Existing shoutout posts got `_coinsnap_donation_form_id` meta

- [ ] **Step 4: Commit**

```bash
git add includes/class-coinsnap-bitcoin-donation-migration.php coinsnap-bitcoin-donation.php
git commit -m "feat: add automatic migration from options to CPT posts"
```

---

## Task 8: Convert Legacy Shortcodes to Thin Wrappers

**Files:**
- Modify: `includes/class-coinsnap-bitcoin-donation-shortcode.php`
- Modify: `includes/class-coinsnap-bitcoin-donation-shortcode-wide.php`
- Modify: `includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount.php`
- Modify: `includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount-wide.php`
- Modify: `includes/class-coinsnap-bitcoin-donation-shoutouts-form.php`
- Modify: `includes/class-coinsnap-bitcoin-donation-shoutouts-list.php`

- [ ] **Step 1: Create a shared legacy resolver method**

Each legacy shortcode needs to: look up the migrated CPT post ID from the mapping option, then call the unified renderer. Add a static helper to the renderer class (or as a standalone function):

In `class-coinsnap-bitcoin-donation-form-renderer.php`, add:

```php
public static function resolve_legacy_shortcode( $shortcode_name ) {
    $mapping = get_option( 'coinsnap_donation_migrated_forms', array() );
    return isset( $mapping[ $shortcode_name ] ) ? absint( $mapping[ $shortcode_name ] ) : 0;
}
```

- [ ] **Step 2: Convert coinsnap_bitcoin_donation shortcode**

Replace the body of `class-coinsnap-bitcoin-donation-shortcode.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Shortcode {

    public function __construct() {
        add_shortcode( 'coinsnap_bitcoin_donation', array( $this, 'render' ) );
    }

    public function render( $atts ) {
        $post_id = Coinsnap_Bitcoin_Donation_Form_Renderer::resolve_legacy_shortcode( 'coinsnap_bitcoin_donation' );
        if ( $post_id ) {
            return Coinsnap_Bitcoin_Donation_Form_Renderer::render_form( array( 'id' => $post_id ) );
        }
        // Fallback: render from old options (safety net)
        return $this->render_legacy();
    }

    private function render_legacy() {
        // Keep original render method as fallback — copy existing code here
        // (only used if migration hasn't run yet)
        // ... existing inline HTML rendering ...
    }
}

new Coinsnap_Bitcoin_Donation_Shortcode();
```

- [ ] **Step 3: Convert remaining 5 shortcode classes**

Apply the same pattern to:
- `class-coinsnap-bitcoin-donation-shortcode-wide.php` — resolves `coinsnap_bitcoin_donation_wide`, **forces layout override to WIDE** via `render_form( array( 'id' => $post_id, 'layout' => 'WIDE' ) )`
- `class-coinsnap-bitcoin-donation-shortcode-multi-amount.php` — resolves `multi_amount_donation`
- `class-coinsnap-bitcoin-donation-shortcode-multi-amount-wide.php` — resolves `multi_amount_donation_wide`, **forces layout override to WIDE** via `render_form( array( 'id' => $post_id, 'layout' => 'WIDE' ) )`
- `class-coinsnap-bitcoin-donation-shoutouts-form.php` — resolves `shoutout_form`
- `class-coinsnap-bitcoin-donation-shoutouts-list.php` — resolves `shoutout_list`, calls `render_list()` instead of `render_form()`

**Important:** The wide legacy shortcodes (`_wide` variants) must override the layout to `WIDE` regardless of what's stored in meta. This ensures existing sites using `[coinsnap_bitcoin_donation_wide]` always get the wide layout even if the migrated CPT post has `layout=NARROW`. Update `render_form()` to accept an optional `layout` attribute that overrides the meta value:

```php
public static function render_form( $atts ) {
    $atts = shortcode_atts( array( 'id' => 0, 'layout' => '' ), $atts );
    // ...
    $layout = ! empty( $atts['layout'] ) ? $atts['layout'] : ( $meta['layout'] ?? 'NARROW' );
}
```

Each wrapper keeps the original render method as `render_legacy()` fallback.

- [ ] **Step 4: Test legacy shortcodes still work**

Place the original shortcodes (`[coinsnap_bitcoin_donation]`, `[multi_amount_donation]`, `[shoutout_form]`, `[shoutout_list]`) on pages. After migration has run, verify they render using the CPT data and the unified templates.

- [ ] **Step 5: Commit**

```bash
git add includes/class-coinsnap-bitcoin-donation-shortcode.php includes/class-coinsnap-bitcoin-donation-shortcode-wide.php includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount.php includes/class-coinsnap-bitcoin-donation-shortcode-multi-amount-wide.php includes/class-coinsnap-bitcoin-donation-shoutouts-form.php includes/class-coinsnap-bitcoin-donation-shoutouts-list.php includes/class-coinsnap-bitcoin-donation-form-renderer.php
git commit -m "feat: convert legacy shortcodes to thin wrappers over CPT renderer"
```

---

## Task 9: Update Webhook Handler for Form ID Propagation

**Files:**
- Modify: `includes/class-coinsnap-bitcoin-donation-webhooks.php`

- [ ] **Step 1: Store donation_form_id on shoutout posts**

In `process_webhook()` method, at lines 394-401 where shoutout post meta is saved, add after the existing `update_post_meta` calls:

```php
if ( ! empty( $metadata['donationFormId'] ) ) {
    update_post_meta( $post_id, '_coinsnap_donation_form_id', absint( $metadata['donationFormId'] ) );
}
```

- [ ] **Step 2: Also store form ID on public donor posts**

In the public donors section (lines 369-378), add:

```php
if ( ! empty( $metadata['donationFormId'] ) ) {
    update_post_meta( $post_id, '_coinsnap_donation_form_id', absint( $metadata['donationFormId'] ) );
}
```

- [ ] **Step 3: Pass form ID in frontend metadata**

In the templates (shoutout-form.php and all donation templates), the `data-form-id` attribute is already on the container. In `popup.js`, the `addDonationPopupListener` function builds metadata object at line 226. The form ID needs to be read from the container's `data-form-id` attribute and included:

In `assets/js/popup.js`, in the metadata object construction (around line 226), add:

```javascript
const formContainer = document.getElementById(`${prefix}form${suffix}`)
    || document.querySelector(`[data-form-id]`);
const donationFormId = formContainer ? formContainer.dataset.formId : '';

const metadata = {
    // ... existing fields ...
    donationFormId: donationFormId,
};
```

Actually, more precisely — the popup.js `addDonationPopupListener` function receives the prefix/suffix. We need to find the nearest container with `data-form-id`. Update `popup.js` to read the form ID from the closest parent container with the attribute.

- [ ] **Step 4: Test shoutout scoping**

1. Create a shoutout form via CPT
2. Submit a shoutout via that form
3. Verify the `bitcoin-shoutouts` post gets `_coinsnap_donation_form_id` meta
4. Verify `[coinsnap_donation_list id="X"]` only shows shoutouts from that form

- [ ] **Step 5: Commit**

```bash
git add includes/class-coinsnap-bitcoin-donation-webhooks.php assets/js/popup.js
git commit -m "feat: propagate donation form ID through payment flow to webhook"
```

---

## Task 10: Update Uninstall and Final Cleanup

**Files:**
- Modify: `uninstall.php`
- Modify: `coinsnap-bitcoin-donation.php` (remove old forms class loading)

- [ ] **Step 1: Update uninstall.php**

Add cleanup for the new CPT and migration data. After the existing options deletion:

```php
// Delete all donation-form CPT posts
$donation_forms = get_posts( array(
    'post_type'      => 'donation-form',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
) );
foreach ( $donation_forms as $form_id ) {
    wp_delete_post( $form_id, true );
}

// Delete migration options
delete_option( 'coinsnap_donation_migrated_forms' );
delete_option( 'coinsnap_donation_forms_migrated' );
```

Also add to the options array:

```php
$options = array(
    // ... existing ...
    'coinsnap_donation_migrated_forms',
    'coinsnap_donation_forms_migrated',
);
```

- [ ] **Step 2: Stop loading the old forms class**

In `class-coinsnap-bitcoin-donation-settings.php`, the old `class-coinsnap-bitcoin-donation-forms.php` was already removed in Task 3. Verify it's no longer required anywhere. The file itself stays on disk as a fallback reference but should not be loaded.

- [ ] **Step 3: Bump plugin version**

In `coinsnap-bitcoin-donation.php`, update the version constant and header:

```php
// Line 7: Version: 1.6.0
// Line 21: define( 'COINSNAP_BITCOIN_DONATION_VERSION', '1.6.0' );
```

Update the upgrade function version check to `1.6.0`.

- [ ] **Step 4: Full end-to-end test**

Test the complete flow:
1. Fresh install: no migration data exists, creating forms via CPT works
2. Upgrade from existing: migration creates 3 CPT posts, legacy shortcodes resolve correctly
3. Multiple forms on one page with different settings
4. Shoutout list scoping works
5. Admin: CPT list shows correct columns, edit screen works, type switching with confirmation
6. Payment flow works end-to-end (create payment → webhook → shoutout/donor post created with form ID)
7. Uninstall cleans up all data

- [ ] **Step 5: Commit**

```bash
git add uninstall.php coinsnap-bitcoin-donation.php
git commit -m "feat: update uninstall cleanup and bump version to 1.6.0"
```

---

## Task Summary

| # | Task | Dependencies |
|---|------|-------------|
| 1 | Register CPT and post meta | None |
| 2 | Add admin metabox with form type selector | Task 1 |
| 3 | Update admin menu to CPT list | Task 1 |
| 4 | Extract templates from inline HTML | None |
| 5 | Create unified form renderer | Tasks 1, 4 |
| 6 | Update frontend JS for data attributes | Tasks 4, 5 |
| 7 | Create migration logic | Task 1 |
| 8 | Convert legacy shortcodes to wrappers | Tasks 5, 7 |
| 9 | Update webhook for form ID propagation | Tasks 4, 5, 6 |
| 10 | Uninstall cleanup and final polish | All above |
