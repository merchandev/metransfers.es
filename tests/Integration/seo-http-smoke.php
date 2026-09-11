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

$sitemap = wp_remote_retrieve_body( mt_seo_fetch( $yoast ? '/ruta-sitemap.xml' : '/wp-sitemap-posts-ruta-1.xml' ) );
mt_seo_assert( false !== strpos( $sitemap, '/rutas/barcelona-salou/' ), 'Sitemap must include ready route.' );
mt_seo_assert( false === strpos( $sitemap, '/rutas/barcelona-andorra/' ), 'Sitemap must exclude rejected route.' );
if ( $yoast ) {
	mt_seo_assert( false !== strpos( $body, 'Transfer Barcelona - Salou | MeTransfers' ), 'Yoast route title must be concise.' );
	mt_seo_assert( '' !== mt_seo_attr( $body, '//meta[@name="description"]', 'content' ), 'Yoast route description is required.' );
}

update_post_meta( $salou->ID, '_mt_seo_variant_en', array( 'translated_reviewed' => true, 'http_status' => 200, 'canonical' => home_url( '/en/rutas/barcelona-salou/' ), 'source_hash' => \MeTransfers\SEO\Variants::fingerprint( $salou ) ) );
$english      = mt_seo_fetch( '/en/rutas/barcelona-salou/' );
$english_body = wp_remote_retrieve_body( $english );
mt_seo_assert( 200 === wp_remote_retrieve_response_code( $english ), 'Approved English must exist.' );
mt_seo_assert( false === strpos( mt_seo_attr( $english_body, '//meta[@name="robots"]', 'content' ), 'noindex' ), 'Approved English must be indexable.' );
mt_seo_assert( home_url( '/en/rutas/barcelona-salou/' ) === mt_seo_attr( $english_body, '//link[@rel="canonical"]', 'href' ), 'English must be self-canonical.' );
mt_seo_assert( home_url( '/rutas/barcelona-salou/' ) === mt_seo_attr( $english_body, '//link[@hreflang="es-ES"]', 'href' ), 'English must reciprocate Spanish.' );
$spanish = wp_remote_retrieve_body( mt_seo_fetch( '/rutas/barcelona-salou/' ) );
mt_seo_assert( home_url( '/en/rutas/barcelona-salou/' ) === mt_seo_attr( $spanish, '//link[@hreflang="en-US"]', 'href' ), 'Spanish must reciprocate approved US English.' );
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
WP_CLI::success( 'SEO HTTP contracts passed with ' . ( $yoast ? 'Yoast' : 'WordPress core' ) . '.' );
