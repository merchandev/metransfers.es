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
		if ( ! is_404() || is_admin() || wp_doing_ajax()
			|| ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) {
			return;
		}
		$map = get_option( self::OPTION, array() );
		if ( empty( $map ) || ! is_array( $map ) ) {
			return;
		}
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$target  = self::targetForRequest( $request, $map );
		if ( null === $target ) {
			return;
		}
		wp_safe_redirect( home_url( $target ), 301 );
		exit;
	}

	public static function targetForRequest( string $request, array $map ): ?string {
		$path = \MeTransfers\I18n\Language::pathWithoutLanguage( $request );
		if ( '' === $path || ! isset( $map[ $path ] ) || ! is_string( $map[ $path ] ) ) {
			return null;
		}
		$target = trim( $map[ $path ], '/' );
		$post   = UrlPolicy::postForPath( $target );
		// A public article remains a valid redirect destination even when its
		// owner set noindex or a manual canonical. Those SEO choices must not
		// break inbound links after a slug correction.
		if ( $target === $path || ! $post || 'post' !== $post->post_type
			|| 'publish' !== $post->post_status || '' !== (string) ( $post->post_password ?? '' ) ) {
			return null;
		}
		$language = \MeTransfers\I18n\Language::detectFromUri( $request );
		$prefix   = 'en' === $language && UrlPolicy::eligibleTarget( $target, 'en' ) ? '/en/' : '/';
		$query    = parse_url( $request, PHP_URL_QUERY );
		return $prefix . $target . '/' . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
	}
}
