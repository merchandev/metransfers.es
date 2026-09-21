<?php
namespace MeTransfers\SEO;

/**
 * 301s a corrected blog post slug from its old, unrelated one (see
 * tools/fix-blog-slugs.php). Deliberately independent of LegacyUrlMap:
 * the mapping is generated data, not a fixed pattern, and only fires on an
 * actual 404 so it is safe regardless of whether this code deploys before
 * or after the WP-CLI script renames the posts — a URL that still resolves
 * to a real post is never intercepted.
 */
final class BlogSlugRedirects {
	const OPTION = 'mt_blog_slug_redirects';

	public function register() {
		add_action( 'template_redirect', array( __CLASS__, 'maybeRedirect' ), 5 );
	}

	public static function maybeRedirect() {
		if ( ! is_404() ) {
			return;
		}
		$map = get_option( self::OPTION, array() );
		if ( empty( $map ) || ! is_array( $map ) ) {
			return;
		}
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path    = trim( (string) parse_url( $request, PHP_URL_PATH ), '/' );
		if ( '' === $path || ! isset( $map[ $path ] ) ) {
			return;
		}
		$query  = parse_url( $request, PHP_URL_QUERY );
		$target = home_url( '/' . trim( (string) $map[ $path ], '/' ) . '/' );
		wp_safe_redirect( $target . ( is_string( $query ) && '' !== $query ? '?' . $query : '' ), 301 );
		exit;
	}
}
