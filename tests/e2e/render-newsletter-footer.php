<?php
/**
 * Render the real newsletter footer block (PublicController::footerForm) in a
 * dark footer like footer.php, with isolated, read-only WordPress stubs.
 * Usage: php render-newsletter-footer.php <es|en> <popup 0|1>
 */
define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['mt_fixture_language'] = ( $argv[1] ?? 'es' ) === 'en' ? 'en' : 'es';
$popup                          = ( $argv[2] ?? '0' ) === '1';

function get_option( $key, $default = false ) { return $default; }
function is_admin() { return false; }
function wp_doing_ajax() { return false; }
function is_feed() { return false; }
function get_query_var( $name, $default = '' ) { return $default; }
function wp_unslash( $value ) { return $value; }
function mt_lang() { return $GLOBALS['mt_fixture_language']; }
function mt_localized_url( $path = '' ) { return ( 'en' === mt_lang() ? '/en/' : '/' ) . trim( $path, '/' ) . ( '' !== $path ? '/' : '' ); }
function home_url( $path = '' ) { return $path; }
function admin_url( $path = '' ) { return '/wp-admin/' . $path; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $url ) { return esc_attr( $url ); }
function wp_json_encode( $value ) { return json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }

require ABSPATH . 'app/bootstrap.php';

use MeTransfers\Newsletter\PublicController;

$config = array(
	'popup'   => $popup,
	'ajaxUrl' => '/newsletter-endpoint',
	'delay'   => 0.3,
) + PublicController::config();

echo '<!doctype html><html lang="' . esc_attr( mt_lang() ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Footer newsletter</title>'
	. '<link rel="stylesheet" href="/style.css"><link rel="stylesheet" href="/assets/css/newsletter.css">'
	. '<script>window.mtNewsletter=' . wp_json_encode( $config ) . ';</script></head><body>'
	. '<main style="min-height:40vh"><h1>Traslados privados en Barcelona</h1></main>'
	. '<footer id="colophon" class="site-footer" style="background:#0B1F35;padding:48px 0 24px"><div class="footer-inner" style="max-width:1200px;margin:0 auto;padding:0 24px">'
	. PublicController::footerForm()
	. '<p>&copy; MeTransfers Barcelona</p></div></footer>'
	. '<script src="/assets/js/newsletter.js"></script></body></html>';
