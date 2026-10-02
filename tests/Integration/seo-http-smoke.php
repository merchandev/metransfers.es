<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'MT_SEO_INTEGRATION' ) || ! MT_SEO_INTEGRATION || 'http://127.0.0.1:8080' !== home_url() ) {
	exit( 'Disposable integration fixture required.' );
}
function mt_seo_assert( $condition, $message ) {
	if ( ! $condition ) { WP_CLI::error( $message ); }
}
function mt_seo_fetch( $path ) {
	$response = wp_remote_get( home_url( $path ), array( 'redirection' => 0, 'timeout' => 30 ) );
	mt_seo_assert( ! is_wp_error( $response ), 'HTTP request failed: ' . $path );
	return $response;
}
function mt_seo_attr( $body, $xpath, $attribute ) {
	$document = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$document->loadHTML( $body );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	$nodes = ( new DOMXPath( $document ) )->query( $xpath );
	return $nodes->length ? $nodes->item( 0 )->getAttribute( $attribute ) : '';
}
$yoast    = ( $args[0] ?? 'core' ) === 'yoast';
$salou    = get_page_by_path( 'barcelona-salou', OBJECT, 'ruta' );
$andorra  = get_page_by_path( 'barcelona-andorra', OBJECT, 'ruta' );
$cadaques = get_page_by_path( 'barcelona-cadaques', OBJECT, 'ruta' );
mt_seo_assert( $salou && $andorra && $cadaques, 'Fixtures are missing.' );

$redirect = mt_seo_fetch( '/taxis-barcelona-salou/?utm_source=contract' );
mt_seo_assert( 301 === wp_remote_retrieve_response_code( $redirect ), 'Legacy redirect must be 301.' );
mt_seo_assert( home_url( '/rutas/barcelona-salou/?utm_source=contract' ) === wp_remote_retrieve_header( $redirect, 'location' ), 'Redirect must preserve parameters and trailing slash.' );
$route = mt_seo_fetch( '/rutas/barcelona-salou/' );
$body  = wp_remote_retrieve_body( $route );
mt_seo_assert( 200 === wp_remote_retrieve_response_code( $route ), 'Redirect target must return 200 directly.' );
mt_seo_assert( home_url( '/rutas/barcelona-salou/' ) === mt_seo_attr( $body, '//link[@rel="canonical"]', 'href' ), 'Route must be self-canonical.' );
mt_seo_assert( false === strpos( mt_seo_attr( $body, '//meta[@name="robots"]', 'content' ), 'noindex' ), 'Reviewed route must be indexable.' );
mt_seo_assert( '' === mt_seo_attr( $body, '//link[@hreflang="en-US"]', 'href' ), 'Unreviewed English must not be advertised.' );

$hub = wp_remote_retrieve_body( mt_seo_fetch( '/rutas/' ) );
mt_seo_assert( false !== strpos( $hub, '/rutas/barcelona-salou/' ), 'Hub must link to approved route.' );
mt_seo_assert( false === strpos( $hub, '/rutas/barcelona-andorra/' ), 'Hub must exclude explicitly rejected route.' );
$rejected = wp_remote_retrieve_body( mt_seo_fetch( '/rutas/barcelona-andorra/' ) );
mt_seo_assert( false !== strpos( mt_seo_attr( $rejected, '//meta[@name="robots"]', 'content' ), 'noindex' ), 'Rejected route must be noindex.' );
$legacy = wp_remote_retrieve_body( mt_seo_fetch( '/rutas/barcelona-cadaques/' ) );
mt_seo_assert( false === strpos( mt_seo_attr( $legacy, '//meta[@name="robots"]', 'content' ), 'noindex' ), 'Missing legacy readiness must not trigger mass noindex.' );

foreach ( array(
	'/taxis-barcelona-vielha/'  => '/rutas/barcelona-vielha/',
	'/costa-brava-taxis/'       => '/rutas/barcelona-costa-brava/',
	'/reus-taxis/'              => '/rutas/barcelona-reus/',
	'/montserrat-traslados/'    => '/rutas/barcelona-montserrat/',
) as $source => $target ) {
	$response = mt_seo_fetch( $source );
	mt_seo_assert( 301 === wp_remote_retrieve_response_code( $response ), 'Legacy source must redirect: ' . $source );
	mt_seo_assert( home_url( $target ) === wp_remote_retrieve_header( $response, 'location' ), 'Legacy source must point to its canonical route: ' . $source );
}

foreach ( array(
	'/aeropuerto-barcelona/'   => '/transfer-aeropuerto-barcelona/',
	'/puerto-barcelona/'       => '/traslados-puerto/',
	'/conductor-privado/'      => '/chofer-por-horas/',
	'/traslados-corporativos/' => '/corporativo-y-eventos/',
	'/faq/'                    => '/preguntas-frecuentes/',
	'/noticias/'               => '/blog/',
	'/bodas-eventos/'          => '/grupos/',
) as $source => $target ) {
	$response = mt_seo_fetch( $source );
	mt_seo_assert( 301 === wp_remote_retrieve_response_code( $response ), 'Fixed historical alias must redirect: ' . $source );
	mt_seo_assert( home_url( $target ) === wp_remote_retrieve_header( $response, 'location' ), 'Fixed historical alias has wrong target: ' . $source );
}

mt_seo_assert( 404 === wp_remote_retrieve_response_code( mt_seo_fetch( '/esta-url-no-debe-existir-2026/' ) ), 'Unknown URLs must remain real 404s.' );

// The public English pages must work with an empty translation cache.
$airport_alias = mt_seo_fetch( '/en/traslados-aeropuerto/?utm_source=contract' );
mt_seo_assert( 301 === wp_remote_retrieve_response_code( $airport_alias ), 'English airport alias must redirect.' );
mt_seo_assert( home_url( '/en/transfer-aeropuerto-barcelona/?utm_source=contract' ) === wp_remote_retrieve_header( $airport_alias, 'location' ), 'English airport alias must retain the language and query.' );
$airport_en = mt_seo_fetch( '/en/transfer-aeropuerto-barcelona/' );
$airport_body = wp_remote_retrieve_body( $airport_en );
mt_seo_assert( 200 === wp_remote_retrieve_response_code( $airport_en ), 'English airport must return 200.' );
mt_seo_assert( false !== strpos( $airport_body, 'From El Prat Airport to your hotel' ), 'Airport English copy must not depend on database cache.' );
mt_seo_assert( false !== strpos( $airport_body, 'Request a quote' ) && false !== strpos( $airport_body, 'Get a price and book online' ), 'Quote and booking must have distinct calls to action.' );
mt_seo_assert( false !== strpos( $airport_body, home_url( '/en/' ) . '#panel' ), 'Online booking must keep the existing localized home calculator destination.' );
mt_seo_assert( false !== strpos( $airport_body, 'name="extra_fecha"' ) && false !== strpos( $airport_body, 'data-service="aeropuerto"' ), 'Form field names and service code must survive translation.' );
mt_seo_assert( false !== strpos( mt_seo_attr( $airport_body, '//meta[@name="robots"]', 'content' ), 'noindex' ), 'Routing must not auto-approve translated SEO variants.' );
$about_en = wp_remote_retrieve_body( mt_seo_fetch( '/en/sobre-nosotros/' ) );
mt_seo_assert( false !== strpos( $about_en, 'Our story and values' ) && false === strpos( $about_en, 'Nuestra historia y valores' ), 'About page must have reviewed English main copy.' );
preg_match( '#<title>([^<]*)</title>#si', $about_en, $about_title );
mt_seo_assert( false !== strpos( $about_title[1] ?? '', 'About MeTransfers Barcelona' ), 'About document title must be English with core and Yoast.' );
if ( $yoast ) {
	mt_seo_assert( false !== strpos( mt_seo_attr( $about_en, '//meta[@name="description"]', 'content' ), 'Meet MeTransfers Barcelona' ), 'About Yoast description must be English.' );
}
$routes_en = wp_remote_retrieve_body( mt_seo_fetch( '/en/rutas/' ) );
mt_seo_assert( false !== strpos( $routes_en, 'available routes with a private' ), 'Route count and intro must be translated without changing the query.' );

$sitemap = wp_remote_retrieve_body( mt_seo_fetch( $yoast ? '/ruta-sitemap.xml' : '/wp-sitemap-posts-ruta-1.xml' ) );
mt_seo_assert( false !== strpos( $sitemap, '/rutas/barcelona-salou/' ), 'Sitemap must include ready route.' );
mt_seo_assert( false === strpos( $sitemap, '/rutas/barcelona-andorra/' ), 'Sitemap must exclude rejected route.' );
if ( $yoast ) {
	mt_seo_assert( false !== strpos( $body, 'Transfer Barcelona - Salou | MeTransfers' ), 'Yoast route title must be concise.' );
	mt_seo_assert( '' !== mt_seo_attr( $body, '//meta[@name="description"]', 'content' ), 'Yoast route description is required.' );
}

$batch = \MeTransfers\SEO\VariantApproval::prepare( array( array( 'id' => $salou->ID, 'language' => 'en', 'editorial_approved' => true ) ) );
mt_seo_assert( ! $batch['errors'] && ! \MeTransfers\SEO\VariantApproval::apply( $batch ), 'Reviewed variant approval must succeed on a real database.' );
$english      = mt_seo_fetch( '/en/rutas/barcelona-salou/' );
$english_body = wp_remote_retrieve_body( $english );
mt_seo_assert( 200 === wp_remote_retrieve_response_code( $english ), 'Approved English must exist.' );
mt_seo_assert( false === strpos( mt_seo_attr( $english_body, '//meta[@name="robots"]', 'content' ), 'noindex' ), 'Approved English must be indexable.' );
mt_seo_assert( home_url( '/en/rutas/barcelona-salou/' ) === mt_seo_attr( $english_body, '//link[@rel="canonical"]', 'href' ), 'English must be self-canonical.' );
mt_seo_assert( home_url( '/rutas/barcelona-salou/' ) === mt_seo_attr( $english_body, '//link[@hreflang="es-ES"]', 'href' ), 'English must reciprocate Spanish.' );
$spanish = wp_remote_retrieve_body( mt_seo_fetch( '/rutas/barcelona-salou/' ) );
mt_seo_assert( home_url( '/en/rutas/barcelona-salou/' ) === mt_seo_attr( $spanish, '//link[@hreflang="en-US"]', 'href' ), 'Spanish must reciprocate approved US English.' );
$language_sitemap = wp_remote_retrieve_body( mt_seo_fetch( '/mt-language-sitemap.xml' ) );
mt_seo_assert( false !== strpos( $language_sitemap, home_url( '/en/rutas/barcelona-salou/' ) ), 'Language sitemap must include approved variant.' );
mt_seo_assert( false === strpos( $language_sitemap, '/en/sobre-nosotros/' ), 'Language sitemap must exclude unreviewed variants.' );
$sitemap_index = wp_remote_retrieve_body( mt_seo_fetch( $yoast ? '/sitemap_index.xml' : '/wp-sitemap.xml' ) );
mt_seo_assert( false !== strpos( $sitemap_index, $yoast ? '/mt-language-sitemap.xml' : 'wp-sitemap-mtlanguages-' ), 'Active sitemap index must discover reviewed variants.' );
delete_post_meta( $salou->ID, '_mt_seo_variant_en' );

// Retired languages must collapse to Spanish without exposing old language trees.
foreach ( array( 'fr', 'de', 'pt', 'ca', 'ru' ) as $retired ) {
	$response = mt_seo_fetch( '/' . $retired . '/taxis-barcelona-salou/' );
	mt_seo_assert( 301 === wp_remote_retrieve_response_code( $response ), 'Retired language must redirect: ' . $retired );
	mt_seo_assert( home_url( '/rutas/barcelona-salou/' ) === wp_remote_retrieve_header( $response, 'location' ), 'Retired language must consolidate to Spanish canonical.' );
}

// Exercise real-post dispatch anonymously.
foreach ( array( 'mt-router-visibility', 'contacto' ) as $slug ) {
	$existing = get_page_by_path( $slug );
	$id       = $existing ? $existing->ID : wp_insert_post( array( 'post_type' => 'page', 'post_name' => $slug, 'post_title' => $slug, 'post_status' => 'publish' ) );
	foreach ( array( 'draft', 'private', 'trash', 'publish' ) as $status ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => $status, 'post_password' => '', 'post_content' => 'MT_PRIVATE_CONTENT_SENTINEL' ) );
		$response = mt_seo_fetch( '/en/' . $slug . '/' );
		mt_seo_assert( ( 'publish' === $status ? 200 : 404 ) === wp_remote_retrieve_response_code( $response ), 'Router visibility: ' . $slug . ' ' . $status );
		if ( 'publish' !== $status ) {
			mt_seo_assert( false === strpos( wp_remote_retrieve_body( $response ), 'MT_PRIVATE_CONTENT_SENTINEL' ), 'Router must not leak protected content.' );
		}
	}
	wp_update_post( array( 'ID' => $id, 'post_password' => 'integration-secret' ) );
	mt_seo_assert( 404 === wp_remote_retrieve_response_code( mt_seo_fetch( '/en/' . $slug . '/' ) ), 'Password-protected page must not be hydrated.' );
	wp_update_post( array( 'ID' => $id, 'post_password' => '' ) );
}
mt_seo_assert( 404 === wp_remote_retrieve_response_code( mt_seo_fetch( '/en/taxis-barcelona-mt-nonexistent-fixture/' ) ), 'Missing SEO page must not become a virtual landing.' );

$home_body = wp_remote_retrieve_body( mt_seo_fetch( '/' ) );
$blog_body = wp_remote_retrieve_body( mt_seo_fetch( '/en/blog/' ) );
mt_seo_assert( mt_seo_attr( $home_body, '//meta[@name="description"]', 'content' ) !== mt_seo_attr( $blog_body, '//meta[@name="description"]', 'content' ), 'Home and Blog need distinct descriptions.' );

// Follow the English category and pagination links that previously returned 404.
$posts_per_page = get_option( 'posts_per_page' );
update_option( 'posts_per_page', 2 );
$category = get_term_by( 'name', 'MT archive fixture', 'category' );
foreach ( array( '/en/blog/page/2/', '/en/category/' . $category->slug . '/', '/en/category/' . $category->slug . '/page/2/' ) as $path ) {
	$response = mt_seo_fetch( $path );
	mt_seo_assert( 200 === wp_remote_retrieve_response_code( $response ), 'Translated archive must work: ' . $path );
	$html = wp_remote_retrieve_body( $response );
	mt_seo_assert( false !== strpos( $html, 'MT_ARCHIVE_PUBLIC_' ), 'Translated archive must list actual public posts: ' . $path );
	mt_seo_assert( false === strpos( $html, 'MT_ARCHIVE_PROTECTED_SENTINEL' ), 'Archives must not leak draft, private or password-protected posts.' );
}
mt_seo_assert( 404 === wp_remote_retrieve_response_code( mt_seo_fetch( '/en/blog/page/9999/' ) ), 'Out-of-range pagination must remain 404.' );
mt_seo_assert( 404 === wp_remote_retrieve_response_code( mt_seo_fetch( '/en/category/nonexistent-mt-category/' ) ), 'Missing categories must remain 404.' );
update_option( 'posts_per_page', $posts_per_page );
WP_CLI::success( 'SEO HTTP contracts passed with ' . ( $yoast ? 'Yoast' : 'WordPress core' ) . '.' );
