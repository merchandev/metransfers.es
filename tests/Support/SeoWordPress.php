<?php
namespace MeTransfers\SEO;

if ( ! defined( 'OBJECT' ) ) { define( 'OBJECT', 'OBJECT' ); }
if ( ! defined( 'MT_ACTIVE_LANGS' ) ) { define( 'MT_ACTIVE_LANGS', array( 'es', 'en', 'zh' ) ); }
if ( ! defined( 'MT_SEO_LANGS' ) ) { define( 'MT_SEO_LANGS', array( 'es', 'en' ) ); }
function apply_filters( $hook, $value ) { return $value; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function home_url( $path = '' ): string { return 'https://metransfers.es' . $path; }
function url_to_postid( $url ): int { return 0; }
function get_page_by_path( $path, $output = null, $types = null ) {
	foreach ( $GLOBALS['mt_seo_posts'] ?? array() as $post ) {
		if ( trim( $path, '/' ) === ( 'ruta' === $post->post_type ? 'rutas/' : '' ) . $post->post_name ) { return $post; }
	}
	return null;
}
function post_type_exists( $type ): bool { return 'ruta' === $type; }
function get_template_directory(): string { return dirname( __DIR__, 2 ); }

function get_post( $post = null ) {
	return is_object( $post ) ? $post : ( $GLOBALS['mt_seo_posts'][ $post ?? 1 ] ?? null );
}

function get_permalink( $post ): string {
	$post = get_post( $post );
	return 'https://example.test/' . ( 'ruta' === $post->post_type ? 'rutas/' : '' ) . $post->post_name . '/';
}

function get_option( $key, $default = false ) {
	return $GLOBALS['mt_seo_options'][ $key ] ?? $default;
}

function wp_get_environment_type(): string {
	return $GLOBALS['mt_seo_environment'] ?? 'production';
}

function is_404(): bool {
	return $GLOBALS['mt_seo_404'] ?? false;
}

function is_search(): bool { return false; }
function is_tag(): bool { return false; }
function is_author(): bool { return false; }
function is_date(): bool { return false; }
function is_attachment(): bool { return false; }
function is_singular(): bool { return true; }
function get_queried_object_id(): int { return 1; }
