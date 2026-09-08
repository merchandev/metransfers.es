<?php
namespace MeTransfers\SEO;

final class Redirects {
	public function register() {
		add_action( 'template_redirect', array( $this, 'processRedirects' ), 0 );
	}

	public static function targetForRequest( string $request_uri, array $languages ): ?string {
		$path     = trim( (string) parse_url( $request_uri, PHP_URL_PATH ), '/' );
		$segments = explode( '/', $path );
		$language = 'es';
		if ( in_array( $segments[0], $languages, true ) ) {
			$language = array_shift( $segments );
		}
		$slug   = implode( '/', $segments );
		$target = LegacyUrlMap::getTarget( $slug );
		$seen   = array( $slug );
		while ( null !== $target ) {
			if ( in_array( $target, $seen, true ) ) {
				return null;
			}
			$seen[] = $target;
			$next   = LegacyUrlMap::getTarget( $target );
			if ( null === $next ) {
				$target = rtrim( '/' . ( 'es' === $language ? '' : $language . '/' ) . trim( $target, '/' ), '/' ) . '/';
				$query  = parse_url( $request_uri, PHP_URL_QUERY );
				return $target . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
			}
			$target = $next;
		}
		return null;
	}

	public function processRedirects() {
		if ( is_admin() || wp_doing_ajax() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) {
			return;
		}
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$target  = self::targetForRequest( $request, defined( 'MT_ACTIVE_LANGS' ) ? MT_ACTIVE_LANGS : array( 'es' ) );
		if ( null !== $target ) {
			wp_safe_redirect( home_url( $target ), 301 );
			exit;
		}
	}
}
