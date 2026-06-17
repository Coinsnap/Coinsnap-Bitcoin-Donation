# Coinsnap Core — Developer Guide

A shared library for building WordPress plugins that accept Bitcoin payments via Coinsnap or BTCPay Server.

## Table of Contents

- [Quick Start](#quick-start)
- [Plugin Instance](#plugin-instance)
- [Settings Page](#settings-page)
- [Payment Providers](#payment-providers)
- [Creating Invoices](#creating-invoices)
- [Webhooks](#webhooks)
- [Database](#database)
- [Transactions Page](#transactions-page)
- [Logging](#logging)
- [Logs Page](#logs-page)
- [Exchange Rates](#exchange-rates)
- [BTCPay Authorization Flow](#btcpay-authorization-flow)
- [Admin UI Components](#admin-ui-components)
- [Full Integration Example](#full-integration-example)

---

## Quick Start

### 1. Include the library

Copy `coinsnap-core/` into your plugin's `vendor/` directory and require it:

```php
require_once plugin_dir_path( __FILE__ ) . 'vendor/coinsnap-core/coinsnap-core.php';
```

### 2. Create a Plugin Instance

```php
$core = new \CoinsnapCore\PluginInstance( array(
    'plugin_name'     => 'My Bitcoin Plugin',
    'option_key'      => 'my_plugin_options',
    'webhook_key'     => 'my_plugin_webhook',
    'table_suffix'    => 'my_plugin_payments',
    'rest_namespace'  => 'my-plugin/v1',
    'menu_slug'       => 'my-plugin',
    'referral_code'   => 'ABC123',
    'text_domain'     => 'my-plugin',
    'plugin_url'      => plugin_dir_url( __FILE__ ),
    'plugin_dir'      => plugin_dir_path( __FILE__ ),
    'plugin_icon_url' => plugin_dir_url( __FILE__ ) . 'assets/icon.svg',
) );
```

### 3. Register on the right hooks

```php
// Activation: create database table
register_activation_hook( __FILE__, function() use ( $core ) {
    \CoinsnapCore\Database\PaymentTable::activate( $core );
} );

// admin_init: register settings
add_action( 'admin_init', function() use ( $core ) {
    \CoinsnapCore\Admin\SettingsPage::register_for( $core );
} );

// plugins_loaded: register BTCPay callback
add_action( 'plugins_loaded', function() use ( $core ) {
    \CoinsnapCore\Auth\BTCPayAuthorizer::register_callback( $core );
}, 20 );
```

That's it — you now have a fully functional settings page with Coinsnap and BTCPay Server support.

---

## Plugin Instance

The `PluginInstance` object holds all configuration for your plugin. Pass it to every core class.

```php
$core = new \CoinsnapCore\PluginInstance( $config );
```

### Configuration Keys

| Key | Default | Description |
|-----|---------|-------------|
| `plugin_name` | `'Coinsnap'` | Display name in admin UI headers |
| `option_key` | `'coinsnap_settings'` | `wp_options` key for plugin settings |
| `webhook_key` | `'coinsnap_webhook'` | `wp_options` key for webhook secrets |
| `table_suffix` | `'coinsnap_payments'` | Payment table name (prefixed with `$wpdb->prefix`) |
| `rest_namespace` | `'coinsnap/v1'` | REST API namespace for webhook endpoints |
| `referral_code` | `''` | Coinsnap referral code (sent with invoices) |
| `text_domain` | `'coinsnap-core'` | Translation text domain |
| `plugin_url` | core URL | Plugin directory URL |
| `plugin_dir` | core path | Plugin directory path |
| `plugin_icon_url` | `''` | Icon URL for settings page header |
| `menu_slug` | `'coinsnap'` | Used as prefix for settings groups and page slugs |
| `log_dir_name` | `'coinsnap-logs'` | Log directory inside `wp-content/uploads/` |
| `log_file_name` | `'coinsnap.log'` | Log filename |
| `btcpay_callback_endpoint` | `'coinsnap-btcpay-callback'` | Rewrite endpoint for BTCPay OAuth |
| `btcpay_app_name` | `'Coinsnap'` | App name shown on BTCPay authorization screen |
| `source_column` | `'source_id'` | Column label for payment source in transactions |
| `help_links` | `[]` | Footer links: `array( array( 'url' => '...', 'label' => '...' ) )` |

### Public Methods

```php
$core->get( 'plugin_name' );    // Get any config value
$core->option_key();            // Shortcut for option_key
$core->webhook_key();           // Shortcut for webhook_key
$core->rest_namespace();        // Shortcut for rest_namespace
$core->table_suffix();          // Shortcut for table_suffix
$core->referral_code();         // Shortcut for referral_code
```

---

## Settings Page

The core provides a complete admin settings page with Coinsnap/BTCPay provider toggle, connection testing, and webhook management.

### Register Settings

Call on `admin_init`:

```php
\CoinsnapCore\Admin\SettingsPage::register_for( $core );
```

### Render the Page

Use as a menu page callback:

```php
add_submenu_page(
    'my-plugin',
    'Settings',
    'Settings',
    'manage_options',
    'my-plugin-settings',
    function() use ( $core ) {
        \CoinsnapCore\Admin\SettingsPage::render_page_for( $core );
    }
);
```

### Read Settings

```php
$settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );

$provider = $settings['payment_provider'];  // 'coinsnap' or 'btcpay'
$theme    = $settings['theme'];             // 'light' or 'dark'
$log_level = $settings['log_level'];        // 'error', 'warning', 'info', 'debug'
```

### Check if Configured

```php
if ( \CoinsnapCore\Admin\SettingsPage::is_configured( $core ) ) {
    // Credentials are set
}
```

### Settings Fields

The settings page automatically manages these fields:

**General:** `payment_provider`, `theme`, `log_level`

**Coinsnap:** `coinsnap_store_id`, `coinsnap_api_key`

**BTCPay:** `btcpay_host`, `btcpay_api_key`, `btcpay_store_id`

**Advanced:** `disable_webhook_verification`, `ngrok_url`

### Enqueue Admin Assets

The settings page needs core CSS/JS. Enqueue them on your admin pages:

```php
add_action( 'admin_enqueue_scripts', function( $hook ) use ( $core ) {
    // Only on your plugin pages
    $page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
    if ( stripos( $page, 'my-plugin' ) === false ) {
        return;
    }

    wp_enqueue_style( 'coinsnap-core-admin',
        plugin_dir_url( __FILE__ ) . 'vendor/coinsnap-core/assets/css/admin.css',
        array(), COINSNAP_CORE_VERSION );

    wp_enqueue_script( 'coinsnap-core-admin',
        plugin_dir_url( __FILE__ ) . 'vendor/coinsnap-core/assets/js/admin.js',
        array( 'jquery' ), COINSNAP_CORE_VERSION, true );

    wp_localize_script( 'coinsnap-core-admin', 'CoinsnapCoreAdmin', array(
        'option_key'        => $core->option_key(),
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'nonce'             => wp_create_nonce( 'coinsnap-ajax-nonce' ),
        'connection_action' => 'my_plugin_connection_handler',
        'btcpay_action'     => 'my_plugin_btcpay_apiurl_handler',
        'webhook_action'    => 'my_plugin_reregister_webhook',
    ) );
} );
```

### Register AJAX Handlers

The connection check and BTCPay URL generation need AJAX endpoints:

```php
add_action( 'wp_ajax_my_plugin_connection_handler', function() use ( $core ) {
    \CoinsnapCore\Admin\AjaxHandlers::handle_connection_check( $core );
} );

add_action( 'wp_ajax_my_plugin_btcpay_apiurl_handler', function() use ( $core ) {
    \CoinsnapCore\Admin\AjaxHandlers::handle_btcpay_url( $core );
} );
```

---

## Payment Providers

Two providers are included: Coinsnap and BTCPay Server. Both implement `PaymentProviderInterface`.

### Get a Provider

```php
// Auto-detect from settings
$provider = \CoinsnapCore\Util\ProviderFactory::create( $core );

// Force a specific provider
$provider = \CoinsnapCore\Util\ProviderFactory::create( $core, 'btcpay' );
```

### Provider Interface

```php
interface PaymentProviderInterface {
    public function create_invoice( int $form_id, int $amount, string $currency, array $invoice_data ): array;
    public function handle_webhook( array $request ): array;
    public function check_invoice_status( string $invoice_id ): array;
    public function check_webhook(): bool;
    public function register_webhook(): array;
    public function get_store(): array;
}
```

---

## Creating Invoices

```php
$provider = \CoinsnapCore\Util\ProviderFactory::create( $core );

$result = $provider->create_invoice(
    0,                          // form/source ID
    1000,                       // amount in cents (1000 = €10.00)
    'EUR',                      // currency code
    array(
        'message'     => 'Order #123',
        'redirect'    => 'https://example.com/thank-you',
        'buyer_email' => 'customer@example.com',
        'metadata'    => array(
            'order_id' => '123',
            'type'     => 'purchase',
        ),
    )
);

if ( ! empty( $result['invoice_id'] ) ) {
    $invoice_id  = $result['invoice_id'];
    $payment_url = $result['payment_url'];
    // Redirect customer or show in iframe
} else {
    $error = $result['error'] ?? 'Invoice creation failed';
}
```

### Supported Currencies

`EUR`, `USD`, `SATS`, `BTC`, `CAD`, `JPY`, `GBP`, `CHF`, `RUB`

Available via `\CoinsnapCore\Util\ExchangeRates::get_currencies()`.

---

## Webhooks

### Register Webhook Endpoints

```php
add_action( 'rest_api_init', function() use ( $core ) {
    $namespace = $core->rest_namespace();

    register_rest_route( $namespace, '/webhook/coinsnap', array(
        'methods'             => 'POST',
        'callback'            => 'handle_coinsnap_webhook',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( $namespace, '/webhook/btcpay', array(
        'methods'             => 'POST',
        'callback'            => 'handle_btcpay_webhook',
        'permission_callback' => '__return_true',
    ) );
} );
```

### Handle Webhooks

```php
function handle_coinsnap_webhook( WP_REST_Request $request ) {
    $core = my_plugin_get_core();

    // Verify signature
    if ( ! \CoinsnapCore\Rest\WebhookHelper::verify_coinsnap_signature( $core ) ) {
        return new WP_REST_Response( array( 'success' => false ), 401 );
    }

    $data   = $request->get_json_params();
    $parsed = \CoinsnapCore\Rest\WebhookHelper::parse_webhook( 'coinsnap', $data );

    if ( $parsed['paid'] ) {
        $invoice_id = $parsed['invoice_id'];
        $metadata   = $parsed['metadata'];
        // Update payment status, fulfill order, etc.
    }

    return new WP_REST_Response( array( 'success' => true ), 200 );
}
```

### Parse Webhook Response

`WebhookHelper::parse_webhook()` returns:

```php
array(
    'invoice_id' => 'abc123',
    'paid'       => true,           // true if settled/paid
    'type'       => 'InvoiceSettled',
    'metadata'   => array( ... ),
)
```

### Auto-Register Webhooks

Webhooks are automatically registered when the admin visits the settings page and a valid connection exists. You can also register manually:

```php
$provider = \CoinsnapCore\Util\ProviderFactory::create( $core );
$result   = $provider->register_webhook();

if ( isset( $result['result'] ) ) {
    // $result['result']['id'], $result['result']['secret'], $result['result']['url']
}
```

---

## Database

### Create Table on Activation

```php
register_activation_hook( __FILE__, function() use ( $core ) {
    \CoinsnapCore\Database\PaymentTable::activate( $core );
} );
```

### Table Schema

Table name: `{wp_prefix}{table_suffix}`

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Auto-increment primary key |
| `source_id` | BIGINT | Form/product ID |
| `transaction_id` | VARCHAR(190) | Your internal transaction ID |
| `invoice_number` | VARCHAR(190) | Optional invoice number |
| `customer_name` | VARCHAR(190) | Customer name |
| `customer_email` | VARCHAR(190) | Customer email |
| `amount` | DOUBLE | Payment amount |
| `currency` | VARCHAR(10) | Currency code |
| `description` | TEXT | Payment description |
| `payment_provider` | VARCHAR(50) | `coinsnap` or `btcpay` |
| `payment_invoice_id` | VARCHAR(190) | Provider invoice ID (unique) |
| `payment_status` | VARCHAR(20) | `unpaid`, `paid`, `failed`, `refunded` |
| `payment_url` | TEXT | Checkout URL |
| `ip` | VARCHAR(64) | Customer IP |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

### Insert a Payment Record

```php
global $wpdb;
$table = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );

$wpdb->insert( $table, array(
    'source_id'          => $form_id,
    'transaction_id'     => 'txn_' . wp_generate_password( 12, false ),
    'customer_name'      => 'John Doe',
    'customer_email'     => 'john@example.com',
    'amount'             => 10.00,
    'currency'           => 'EUR',
    'description'        => 'Donation',
    'payment_provider'   => 'coinsnap',
    'payment_invoice_id' => $invoice_id,
    'payment_status'     => 'unpaid',
    'payment_url'        => $payment_url,
    'created_at'         => current_time( 'mysql' ),
) );
```

### Update Payment Status

```php
$wpdb->update(
    $table,
    array( 'payment_status' => 'paid', 'updated_at' => current_time( 'mysql' ) ),
    array( 'payment_invoice_id' => $invoice_id )
);
```

---

## Transactions Page

A built-in admin page for viewing payment history with filters and pagination.

```php
add_submenu_page(
    'my-plugin',
    'Transactions',
    'Transactions',
    'manage_options',
    'my-plugin-transactions',
    function() use ( $core ) {
        \CoinsnapCore\Admin\TransactionsPage::render_page_for( $core );
    }
);
```

### Customize Source Labels

If your plugin has multiple form types, customize the source dropdown and labels:

```php
add_filter( 'coinsnap_core_transaction_sources', function( $sources ) {
    return array(
        'donation' => 'Donations',
        'invoice'  => 'Invoices',
        'shoutout' => 'Shoutouts',
    );
} );

add_filter( 'coinsnap_core_transaction_source_label', function( $label, $source_id ) {
    return 'Form #' . $source_id;
}, 10, 2 );
```

---

## Logging

### Create a Logger

```php
$logger = new \CoinsnapCore\Util\Logger(
    'my-plugin-logs',    // directory in wp-content/uploads/
    'my-plugin.log',     // filename
    'error'              // minimum level: emergency, alert, critical, error, warning, notice, info, debug
);
```

### Log Messages

```php
$logger->debug( 'Processing payment', array( 'invoice_id' => $id ) );
$logger->info( 'Payment created', array( 'amount' => 10.00 ) );
$logger->warning( 'Retry attempt', array( 'attempt' => 2 ) );
$logger->error( 'Payment failed', array( 'error' => $message ) );
$logger->critical( 'Provider unreachable' );
```

### Log File Management

```php
$logger->get_log_file_path();           // Full path
$logger->get_log_file_size();           // Size in bytes
$logger->get_recent_entries( 50 );      // Last 50 entries
$logger->clear_log();                   // Truncate
```

Logs auto-rotate at 5MB (keeps 5 rotated files).

---

## Logs Page

Built-in admin page for viewing and filtering logs:

```php
add_submenu_page(
    'my-plugin',
    'Logs',
    'Logs',
    'manage_options',
    'my-plugin-logs',
    function() use ( $core ) {
        $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
        $logger = new \CoinsnapCore\Util\Logger(
            $core->get( 'log_dir_name' ),
            $core->get( 'log_file_name' ),
            $settings['log_level'] ?? 'error'
        );
        \CoinsnapCore\Admin\LogsPage::render_page_for( $core, $logger );
    }
);
```

---

## Exchange Rates

Fetches BTC exchange rates from Kraken API with transient caching.

```php
$rates = \CoinsnapCore\Util\ExchangeRates::load_rates();
// { result: true, data: { eur: { value: 62000.50 }, usd: { value: 67500.00 }, ... } }

$currencies = \CoinsnapCore\Util\ExchangeRates::get_currencies();
// ['EUR', 'USD', 'SATS', 'BTC', 'CAD', 'JPY', 'GBP', 'CHF', 'RUB']
```

### Validate Payment Amount

```php
$check = \CoinsnapCore\Util\ExchangeRates::check_payment_data(
    5.00,         // amount
    'EUR',        // currency
    'coinsnap'    // provider: 'coinsnap', 'bitcoin', or 'lightning'
);

if ( $check['result'] === false ) {
    echo $check['error'];      // 'currencyError' or 'amountError'
    echo $check['min_value'];  // minimum allowed amount
}
```

---

## BTCPay Authorization Flow

The core handles the full BTCPay OAuth-style API key generation:

### 1. Register the Callback

```php
add_action( 'plugins_loaded', function() use ( $core ) {
    \CoinsnapCore\Auth\BTCPayAuthorizer::register_callback( $core );
}, 20 );
```

### 2. Flow

1. Admin enters BTCPay server URL on the settings page
2. Clicks "Generate API Key"
3. JS calls your AJAX handler → `AjaxHandlers::handle_btcpay_url( $core )`
4. Core generates authorization URL with required permissions
5. Browser redirects to BTCPay for authorization
6. BTCPay redirects back with API key
7. Core's callback handler saves credentials to options

### Required Permissions

```php
'btcpay.store.canviewinvoices',
'btcpay.store.cancreateinvoice',
'btcpay.store.canviewstoresettings',
'btcpay.store.canmodifyinvoices',
'btcpay.store.cancreatenonapprovedpullpayments',
'btcpay.store.webhooks.canmodifywebhooks',
```

---

## Admin UI Components

The core provides consistent CSS classes for admin pages.

### Cards

```html
<div class="csc-card">
    <div class="csc-card-header">
        <h2>Section Title</h2>
    </div>
    <div class="csc-card-body">
        <!-- content -->
    </div>
</div>
```

### Form Fields

```html
<div class="csc-field-row">
    <label for="my-field">Label</label>
    <div class="csc-field-input">
        <input type="text" id="my-field" class="regular-text">
    </div>
</div>
```

### Tabs

```html
<div class="csc-tabs">
    <button class="csc-tab is-active" data-tab="general">General</button>
    <button class="csc-tab" data-tab="advanced">Advanced</button>
</div>
<div class="csc-tab-content is-active" data-tab="general">
    <!-- General content -->
</div>
<div class="csc-tab-content" data-tab="advanced">
    <!-- Advanced content -->
</div>
```

Tab switching is handled by the core JS automatically. Active tab is persisted in `localStorage`.

### Save Bar

```html
<div class="csc-save-bar">
    <span class="csc-save-hint">Changes saved</span>
    <input type="submit" class="button button-primary" value="Save Changes">
</div>
```

### Connection Badge

```html
<div class="csc-connection-badge is-connected">
    <span class="csc-status-dot"></span>
    <span class="csc-connection-text">Connected</span>
</div>
```

States: `is-connected` (green), `is-error` (red), or no class (neutral).

### Status Badges (Transactions)

```html
<span class="csc-status csc-status-paid">Paid</span>
<span class="csc-status csc-status-unpaid">Unpaid</span>
<span class="csc-status csc-status-failed">Failed</span>
```

---

## Full Integration Example

A minimal plugin that accepts Bitcoin payments:

```php
<?php
/*
 * Plugin Name: My Bitcoin Payments
 * Version:     1.0.0
 */

defined( 'ABSPATH' ) || exit;

define( 'MBP_VERSION', '1.0.0' );
define( 'MBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load coinsnap-core
require_once MBP_PLUGIN_DIR . 'vendor/coinsnap-core/coinsnap-core.php';

// Create core instance
function mbp_get_core() {
    static $core = null;
    if ( $core === null ) {
        $core = new \CoinsnapCore\PluginInstance( array(
            'plugin_name'              => 'My Bitcoin Payments',
            'option_key'               => 'mbp_options',
            'webhook_key'              => 'mbp_webhook',
            'table_suffix'             => 'mbp_payments',
            'rest_namespace'           => 'mbp/v1',
            'menu_slug'                => 'my-bitcoin-payments',
            'referral_code'            => '',
            'text_domain'              => 'my-bitcoin-payments',
            'plugin_url'               => MBP_PLUGIN_URL,
            'plugin_dir'               => MBP_PLUGIN_DIR,
            'plugin_icon_url'          => MBP_PLUGIN_URL . 'icon.svg',
            'log_dir_name'             => 'mbp-logs',
            'log_file_name'            => 'mbp.log',
            'btcpay_callback_endpoint' => 'mbp-btcpay-callback',
            'btcpay_app_name'          => 'MyBitcoinPayments',
            'help_links'               => array(
                array( 'url' => 'https://example.com/docs', 'label' => 'Documentation' ),
            ),
        ) );
    }
    return $core;
}

// Activation
register_activation_hook( __FILE__, function() {
    \CoinsnapCore\Database\PaymentTable::activate( mbp_get_core() );
} );

// Register settings
add_action( 'admin_init', function() {
    \CoinsnapCore\Admin\SettingsPage::register_for( mbp_get_core() );
} );

// BTCPay callback
add_action( 'plugins_loaded', function() {
    \CoinsnapCore\Auth\BTCPayAuthorizer::register_callback( mbp_get_core() );
}, 20 );

// Admin menu
add_action( 'admin_menu', function() {
    $core = mbp_get_core();

    add_menu_page( 'My Bitcoin Payments', 'Bitcoin Payments',
        'manage_options', 'my-bitcoin-payments', function() use ( $core ) {
            echo '<div class="wrap"><h1>My Bitcoin Payments</h1></div>';
        }
    );

    add_submenu_page( 'my-bitcoin-payments', 'Settings', 'Settings',
        'manage_options', 'my-bitcoin-payments-settings', function() use ( $core ) {
            \CoinsnapCore\Admin\SettingsPage::render_page_for( $core );
        }
    );

    add_submenu_page( 'my-bitcoin-payments', 'Transactions', 'Transactions',
        'manage_options', 'my-bitcoin-payments-transactions', function() use ( $core ) {
            \CoinsnapCore\Admin\TransactionsPage::render_page_for( $core );
        }
    );

    add_submenu_page( 'my-bitcoin-payments', 'Logs', 'Logs',
        'manage_options', 'my-bitcoin-payments-logs', function() use ( $core ) {
            $settings = \CoinsnapCore\Admin\SettingsPage::get_settings_for( $core );
            $logger = new \CoinsnapCore\Util\Logger( 'mbp-logs', 'mbp.log',
                $settings['log_level'] ?? 'error' );
            \CoinsnapCore\Admin\LogsPage::render_page_for( $core, $logger );
        }
    );
} );

// Enqueue admin assets
add_action( 'admin_enqueue_scripts', function( $hook ) {
    $page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
    if ( stripos( $page ?? '', 'my-bitcoin-payments' ) === false ) {
        return;
    }

    $core = mbp_get_core();

    wp_enqueue_style( 'coinsnap-core-admin',
        MBP_PLUGIN_URL . 'vendor/coinsnap-core/assets/css/admin.css',
        array(), COINSNAP_CORE_VERSION );
    wp_enqueue_script( 'coinsnap-core-admin',
        MBP_PLUGIN_URL . 'vendor/coinsnap-core/assets/js/admin.js',
        array( 'jquery' ), COINSNAP_CORE_VERSION, true );

    wp_localize_script( 'coinsnap-core-admin', 'CoinsnapCoreAdmin', array(
        'option_key'        => $core->option_key(),
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'nonce'             => wp_create_nonce( 'coinsnap-ajax-nonce' ),
        'connection_action' => 'mbp_connection_handler',
        'btcpay_action'     => 'mbp_btcpay_handler',
        'webhook_action'    => 'mbp_webhook_handler',
    ) );
} );

// AJAX handlers
add_action( 'wp_ajax_mbp_connection_handler', function() {
    \CoinsnapCore\Admin\AjaxHandlers::handle_connection_check( mbp_get_core() );
} );
add_action( 'wp_ajax_mbp_btcpay_handler', function() {
    \CoinsnapCore\Admin\AjaxHandlers::handle_btcpay_url( mbp_get_core() );
} );

// Webhook endpoints
add_action( 'rest_api_init', function() {
    $core = mbp_get_core();

    register_rest_route( $core->rest_namespace(), '/webhook/coinsnap', array(
        'methods'             => 'POST',
        'callback'            => 'mbp_handle_webhook',
        'permission_callback' => '__return_true',
    ) );
    register_rest_route( $core->rest_namespace(), '/webhook/btcpay', array(
        'methods'             => 'POST',
        'callback'            => 'mbp_handle_webhook',
        'permission_callback' => '__return_true',
    ) );
} );

function mbp_handle_webhook( WP_REST_Request $request ) {
    $core = mbp_get_core();
    $data = $request->get_json_params();

    // Determine provider from the route
    $route    = $request->get_route();
    $provider = str_contains( $route, 'btcpay' ) ? 'btcpay' : 'coinsnap';

    // Verify signature
    $verified = $provider === 'btcpay'
        ? \CoinsnapCore\Rest\WebhookHelper::verify_btcpay_signature( $core )
        : \CoinsnapCore\Rest\WebhookHelper::verify_coinsnap_signature( $core );

    if ( ! $verified ) {
        return new WP_REST_Response( array( 'success' => false ), 401 );
    }

    $parsed = \CoinsnapCore\Rest\WebhookHelper::parse_webhook( $provider, $data );

    if ( $parsed['paid'] ) {
        global $wpdb;
        $table = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
        $wpdb->update(
            $table,
            array( 'payment_status' => 'paid', 'updated_at' => current_time( 'mysql' ) ),
            array( 'payment_invoice_id' => $parsed['invoice_id'] )
        );
    }

    return new WP_REST_Response( array( 'success' => true ), 200 );
}

// Example: create a payment
function mbp_create_payment( $amount, $currency, $email ) {
    $core     = mbp_get_core();
    $provider = \CoinsnapCore\Util\ProviderFactory::create( $core );

    $result = $provider->create_invoice( 0, intval( $amount * 100 ), $currency, array(
        'buyer_email' => $email,
        'redirect'    => home_url( '/thank-you/' ),
        'metadata'    => array( 'source' => 'my-plugin' ),
    ) );

    if ( ! empty( $result['invoice_id'] ) ) {
        // Store in database
        global $wpdb;
        $table = \CoinsnapCore\Database\PaymentTable::table_name( $core, $wpdb );
        $wpdb->insert( $table, array(
            'source_id'          => 0,
            'transaction_id'     => 'mbp_' . time(),
            'customer_email'     => $email,
            'amount'             => $amount,
            'currency'           => $currency,
            'payment_provider'   => $core->get( 'payment_provider' ) ?? 'coinsnap',
            'payment_invoice_id' => $result['invoice_id'],
            'payment_status'     => 'unpaid',
            'payment_url'        => $result['payment_url'],
            'created_at'         => current_time( 'mysql' ),
        ) );

        return $result;
    }

    return null;
}
```

---

## Constants

These are defined by coinsnap-core and available globally:

| Constant | Value |
|----------|-------|
| `COINSNAP_CORE_VERSION` | `'1.0.0'` |
| `COINSNAP_SERVER_URL` | `'https://app.coinsnap.io'` |
| `COINSNAP_API_PATH` | `'/api/v1/'` |
| `COINSNAP_SERVER_PATH` | `'stores'` |
| `COINSNAP_CURRENCIES` | `['EUR','USD','SATS','BTC','CAD','JPY','GBP','CHF','RUB']` |

All can be overridden by defining them before including coinsnap-core.
