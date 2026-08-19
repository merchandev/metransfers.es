<?php

define(
    'MT_LANGS',
    array(
        'es' => array( 'label' => 'ES', 'name' => 'Español', 'google_code' => 'es' ),
        'en' => array( 'label' => 'EN', 'name' => 'English', 'google_code' => 'en' ),
        'zh' => array( 'label' => 'ZH', 'name' => '中文', 'google_code' => 'zh-CN' ),
    )
);
define( 'MT_ACTIVE_LANGS', array( 'es', 'en', 'zh' ) );
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$GLOBALS['mt_i18n_hooks'] = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['mt_i18n_hooks'][] = array( 'action', $hook, $priority );
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['mt_i18n_hooks'][] = array( 'filter', $hook, $priority );
}

function wp_unslash( $value ) {
    return $value;
}

function home_url( $path = '' ) {
    return 'https://example.test' . $path;
}

function assert_i18n_routing( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

require_once __DIR__ . '/../app/I18n/Language.php';
require_once __DIR__ . '/../app/I18n/Router.php';
require_once __DIR__ . '/../app/I18n/Seo.php';
require_once __DIR__ . '/../app/I18n/Translation.php';
require_once __DIR__ . '/../app/I18n/Switcher.php';
require_once __DIR__ . '/../app/I18n/Admin.php';

use MeTransfers\I18n\Language;
use MeTransfers\I18n\Router;
use MeTransfers\I18n\Seo;

assert_i18n_routing( 'es' === Language::detectFromUri( '/', MT_ACTIVE_LANGS ), 'The unprefixed home must remain Spanish.' );
assert_i18n_routing( 'en' === Language::detectFromUri( '/en/rutas/barcelona-salou/?utm=test', MT_ACTIVE_LANGS ), 'A supported language prefix must be detected from the path only.' );
assert_i18n_routing( 'es' === Language::detectFromUri( '/es/pago/', MT_ACTIVE_LANGS ), 'Spanish must not use a public prefix.' );
assert_i18n_routing( 'es' === Language::detectFromUri( '/xx/pago/', MT_ACTIVE_LANGS ), 'Unsupported language prefixes must not enter translated routing.' );

$home = Router::matchRequest( '/en/', MT_ACTIVE_LANGS );
$nested = Router::matchRequest( '/en/rutas/barcelona-salou/', MT_ACTIVE_LANGS );
assert_i18n_routing( array( 'language' => 'en', 'page' => 'home' ) === $home, 'The translated home route must resolve explicitly.' );
assert_i18n_routing( array( 'language' => 'en', 'page' => 'rutas/barcelona-salou' ) === $nested, 'Nested translated routes must preserve their canonical Spanish path.' );
assert_i18n_routing( null === Router::matchRequest( '/es/rutas/', MT_ACTIVE_LANGS ), 'The router must reject prefixed Spanish URLs.' );
assert_i18n_routing( 'archive-ruta.php' === Router::fixedTemplate( 'rutas' ), 'The translated rutas archive must have an explicit template contract.' );
assert_i18n_routing( null === Router::fixedTemplate( 'ruta-inexistente' ), 'Unknown virtual routes must not receive a fallback template.' );

Language::set( 'en' );
assert_i18n_routing( 'rutas/barcelona-salou' === Language::pathWithoutLanguage( '/en/rutas/barcelona-salou/?x=1' ), 'Canonical slug extraction must remove one language prefix and the query string.' );
assert_i18n_routing( 'https://example.test/en/pago/' === Language::url( 'pago' ), 'Localized URLs must preserve the active prefix.' );

assert_i18n_routing(
    'https://example.test/en/rutas/barcelona-salou/' === Seo::canonicalForRequest( 'https://wrong.test/', '/en/rutas/barcelona-salou/?utm=test', 'en' ),
    'Yoast canonical filtering must use the translated request path without query parameters.'
);
assert_i18n_routing(
    'https://example.test/rutas/barcelona-salou/' === Seo::canonicalForRequest( 'https://example.test/rutas/barcelona-salou/', '/rutas/barcelona-salou/', 'es' ),
    'Yoast canonical filtering must leave Spanish canonical URLs unchanged.'
);
$alternates = Seo::alternatesForRequest( '/en/rutas/barcelona-salou/', array( 'es', 'en', 'zh' ) );
assert_i18n_routing( 'https://example.test/rutas/barcelona-salou/' === $alternates['es'], 'Spanish hreflang must be unprefixed.' );
assert_i18n_routing( 'https://example.test/zh/rutas/barcelona-salou/' === $alternates['zh-Hans'], 'Chinese hreflang must use zh-Hans with the zh URL prefix.' );
assert_i18n_routing( $alternates['es'] === $alternates['x-default'], 'x-default must point to the Spanish canonical.' );

$root = dirname( __DIR__ );
$facade = file_get_contents( $root . '/includes/i18n.php' );
$router = file_get_contents( $root . '/app/I18n/Router.php' );
$translation = file_get_contents( $root . '/app/I18n/Translation.php' );
$admin = file_get_contents( $root . '/app/I18n/Admin.php' );
$functions = file_get_contents( $root . '/functions.php' );
assert_i18n_routing( false === strpos( $facade, '<style' ) && false === strpos( $facade, '<script' ) && false === strpos( $facade, 'style=' ), 'The i18n facade must not emit inline CSS or JavaScript.' );
assert_i18n_routing( file_exists( $root . '/assets/css/i18n-switcher.css' ) && file_exists( $root . '/assets/js/i18n-switcher.js' ), 'The language switcher must use versioned asset files.' );
assert_i18n_routing( false !== strpos( $router, "'template_include'" ) && false === strpos( $router, 'include $full_path' ), 'Translated routing must use the WordPress template filter instead of include-and-exit.' );
assert_i18n_routing( false === strpos( $functions, "add_filter( 'parse_request'" ), 'The translated rutas archive must be owned by the modular router.' );
assert_i18n_routing( false !== strpos( $translation, "'X-Goog-Api-Key'" ) && false === strpos( $translation, 'translate/v2?key=' ), 'Translation secrets must travel in a header rather than the URL.' );
assert_i18n_routing( false !== strpos( $admin, 'type="password"' ) && false === strpos( $admin, 'name="mt_google_api_key" value="<?php' ), 'The translation API key must be write-only in admin HTML.' );

require_once $root . '/includes/i18n.php';
$registered_hooks = array_map( static function( $hook ) { return $hook[1]; }, $GLOBALS['mt_i18n_hooks'] );
assert_i18n_routing( function_exists( 'mt_lang' ) && function_exists( 'mt_localized_url' ) && function_exists( 'gct_render_language_switcher' ), 'The modular facade must preserve public theme functions.' );
assert_i18n_routing( in_array( 'template_redirect', $registered_hooks, true ) && in_array( 'wpseo_canonical', $registered_hooks, true ) && in_array( 'wp_enqueue_scripts', $registered_hooks, true ), 'The facade must register router, Yoast and switcher services.' );

echo "I18n router and Yoast tests passed.\n";
