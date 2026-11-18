<?php
/**
 * Admin menu test (PHP CLI, WordPress stubs): php tests/admin-menu-test.php
 *
 * A null parent slug in add_submenu_page() makes WordPress pass null to plugin_basename(),
 * which logs two "Deprecated" notices on every admin page under PHP 8.1+.
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['submenus'] = array();
$GLOBALS['actions'] = array();
function add_action( $hook, $cb = null, ...$a ) { $GLOBALS['actions'][ $hook ][] = $cb; }
function add_filter( ...$a ) {}
function __( $s ) { return $s; }
function plugin_dir_url( $f ) { return 'https://example.test/wp-content/plugins/coinsnap-bitcoin-donation/'; }
function add_menu_page( ...$a ) {}
function add_submenu_page( $parent, $page_title, $menu_title, $cap, $slug, $cb = null ) {
	$GLOBALS['submenus'][] = array( 'parent' => $parent, 'slug' => $slug );
	return ( '' === $parent ? 'admin_page_' : 'donation_page_' ) . $slug; // like get_plugin_page_hookname()
}
function coinsnap_bitcoin_donation_get_core() { return null; }

require dirname( __DIR__ ) . '/includes/class-coinsnap-bitcoin-donation-settings.php';
( new Coinsnap_Bitcoin_Donation_Settings() )->register_admin_menu();

$fail = 0;
foreach ( $GLOBALS['submenus'] as $m ) {
	$ok = is_string( $m['parent'] );
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . 'submenu ' . $m['slug'] . ' has a string parent slug' . "\n";
	$fail += $ok ? 0 : 1;
}
$hidden = array_values( array_filter( $GLOBALS['submenus'], function ( $m ) { return 'coinsnap-bitcoin-donation' === $m['slug']; } ) );
$ok = 1 === count( $hidden ) && '' === $hidden[0]['parent'];
echo ( $ok ? 'PASS ' : 'FAIL ' ) . "old slug coinsnap-bitcoin-donation is still registered as a hidden page\n";
$fail += $ok ? 0 : 1;
// The redirect must run on load-<hook>, before the admin header is printed; from the page callback it is too late.
$ok = ! empty( $GLOBALS['actions']['load-admin_page_coinsnap-bitcoin-donation'] ) && is_callable( $GLOBALS['actions']['load-admin_page_coinsnap-bitcoin-donation'][0] );
echo ( $ok ? 'PASS ' : 'FAIL ' ) . "old slug redirects on load-admin_page_coinsnap-bitcoin-donation (before headers are sent)\n";
$fail += $ok ? 0 : 1;
echo $fail ? "$fail failed\n" : "all passed\n";
exit( $fail ? 1 : 0 );
