<?php
namespace MeTransfers\SEO;

final class LanguageSitemap {
	public function register() {
		add_action( 'init', array( __CLASS__, 'rewrite' ), 5 );
		add_filter( 'query_vars', array( __CLASS__, 'queryVars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render' ), 0 );
		add_filter( 'wpseo_sitemap_index', array( __CLASS__, 'yoastIndex' ) );
		add_action( 'init', array( __CLASS__, 'nativeProvider' ), 20 );
	}

	public static function rewrite() {
		add_rewrite_rule( '^mt-language-sitemap\.xml$', 'index.php?mt_language_sitemap=1', 'top' );
	}

	public static function queryVars( array $vars ): array {
		$vars[] = 'mt_language_sitemap';
		return $vars;
	}

	/** Include only published, eligible, reviewed variants; never translated fallbacks. */
	public static function urls(): array {
		if ( ! Indexability::isProduction() || '0' === (string) get_option( 'blog_public', '1' ) ) {
			return array();
		}
		$urls      = array();
		$languages = defined( 'MT_SEO_LANGS' ) ? MT_SEO_LANGS : array();
		$posts     = get_posts(
			array(
				'post_type'      => array( 'page', 'post', 'ruta' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'has_password'   => false,
			)
		);
		foreach ( $posts as $post ) {
			if ( ! Indexability::isIndexable( $post ) ) {
				continue;
			}
			foreach ( $languages as $language ) {
				if ( 'es' !== $language && Variants::isApproved( $post, $language ) ) {
					$urls[] = array( 'loc' => Variants::canonical( $post, $language ) );
				}
			}
		}
		return $urls;
	}

	public static function yoastIndex( string $xml ): string {
		if ( self::urls() ) {
			$xml .= '<sitemap><loc>' . esc_xml( home_url( '/mt-language-sitemap.xml' ) ) . '</loc></sitemap>' . "\n";
		}
		return $xml;
	}

	public static function nativeProvider() {
		if ( ! defined( 'WPSEO_VERSION' ) && function_exists( 'wp_register_sitemap_provider' ) ) {
			wp_register_sitemap_provider( 'mtlanguages', new NativeLanguageSitemap() );
		}
	}

	public static function render() {
		if ( ! get_query_var( 'mt_language_sitemap' ) ) {
			return;
		}
		$urls = self::urls();
		if ( ! $urls ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}
		status_header( 200 );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $urls as $url ) {
			echo '<url><loc>' . esc_xml( $url['loc'] ) . '</loc></url>' . "\n";
		}
		echo '</urlset>';
		exit;
	}
}
