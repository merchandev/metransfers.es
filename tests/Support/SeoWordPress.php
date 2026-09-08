<?php
namespace MeTransfers\SEO;

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
