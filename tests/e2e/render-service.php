<?php
/** Render the real service template with isolated, read-only WordPress stubs. */
define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
define( 'MT_ACTIVE_LANGS', array( 'es', 'en' ) );
define( 'MT_LANGS', array( 'es' => array(), 'en' => array() ) );
require ABSPATH . 'app/bootstrap.php';
require ABSPATH . 'includes/services.php';
\MeTransfers\I18n\Language::set( ( $argv[1] ?? 'es' ) === 'en' ? 'en' : 'es' );
$post = (object) array( 'post_type' => 'page', 'post_name' => 'transfer-aeropuerto-barcelona' );
function add_action( $hook, $callback ) {}
function mt_translate( $text ) { return \MeTransfers\I18n\Translation::translate( $text ); }
function wp_cache_get( $key, $group ) { return false; }
function get_option( $key, $default = false ) { return $default; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_attr( $text ); }
function esc_js( $text ) { return addslashes( $text ); }
function wp_json_encode( $text ) { return json_encode( $text ); }
function home_url( $path ) { return $path; }
function me_transfers_get_section_url( $section ) {
	return ( 'en' === \MeTransfers\I18n\Language::get() ? '/en' : '' ) . '/reservaciones/';
}
function get_header() {
	echo '<!doctype html><html lang="' . \MeTransfers\I18n\Language::get() . '"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/style.css"></head><body>';
	echo '<script>window.mtAjax={nonce:"isolated-fixture-nonce",ajaxurl:"/wp-admin/admin-ajax.php"};</script>';
}
function get_footer() { echo '</body></html>'; }
require ABSPATH . 'template-servicio.php';
