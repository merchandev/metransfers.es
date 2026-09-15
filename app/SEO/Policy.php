<?php
namespace MeTransfers\SEO;

final class Policy {
	public function register() {
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 100 );
		add_filter( 'wpseo_robots_array', array( __CLASS__, 'yoastRobots' ), 100 );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( __CLASS__, 'excludedPosts' ), 100 );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'sitemapQuery' ), 100, 2 );
	}

	public static function robots( array $robots ): array {
		if ( ! Indexability::isIndexableRequest() ) {
			unset( $robots['index'] );
			$robots['noindex'] = true;
			if ( ! Indexability::isProduction() ) {
				unset( $robots['follow'] );
				$robots['nofollow']  = true;
				$robots['noarchive'] = true;
			} elseif ( empty( $robots['nofollow'] ) ) {
				$robots['follow'] = true;
			}
		}
		return $robots;
	}

	public static function yoastRobots( array $robots ): array {
		if ( ! Indexability::isIndexableRequest() ) {
			$robots['index'] = 'noindex';
			if ( ! Indexability::isProduction() ) {
				$robots['follow']  = 'nofollow';
				$robots['archive'] = 'noarchive';
			}
		}
		return $robots;
	}

	public static function excludedPosts( array $excluded, array $types = array( 'page', 'post', 'ruta' ) ): array {
		$posts = get_posts(
			array(
				'post_type'      => $types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);
		foreach ( $posts as $post ) {
			if ( ! Indexability::isIndexable( $post ) ) {
				$excluded[] = (int) $post->ID;
			}
		}
		return array_values( array_unique( $excluded ) );
	}

	public static function sitemapQuery( array $args, string $post_type ): array {
		$args['post__not_in'] = self::excludedPosts( $args['post__not_in'] ?? array(), array( $post_type ) );
		return $args;
	}
}
