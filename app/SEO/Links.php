<?php
namespace MeTransfers\SEO;

final class Links {
	public function register() {
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'menu' ), 100 );
		add_filter( 'the_content', array( __CLASS__, 'content' ), 110 );
	}

	public static function normalize( string $url ): string {
		$host = parse_url( $url, PHP_URL_HOST );
		if ( $host && parse_url( home_url(), PHP_URL_HOST ) !== $host ) {
			return $url;
		}
		$path = parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			return $url;
		}
		$query    = parse_url( $url, PHP_URL_QUERY );
		$target   = Redirects::verifiedTarget( $path . ( $query ? '?' . $query : '' ) );
		$fragment = parse_url( $url, PHP_URL_FRAGMENT );
		// Navigation can link to a published page without approving it for SEO.
		$navigation = array(
			'taxis-privado-barcelona'     => 'traslados-privados',
			'taxis-barcelona-costa-brava' => 'destinos/costa-brava',
			'taxis-barcelona-salou'       => 'rutas/barcelona-salou',
			'taxis-barcelona-girona'      => 'rutas/barcelona-girona',
		);
		$key = trim( $path, '/' );
		if ( null === $target && isset( $navigation[ $key ] ) ) {
			$post = get_page_by_path( $navigation[ $key ], OBJECT, array( 'page', 'ruta' ) );
			if ( $post && 'publish' === $post->post_status && empty( $post->post_password ) ) {
				return get_permalink( $post ) . ( $query ? '?' . $query : '' ) . ( $fragment ? '#' . $fragment : '' );
			}
		}
		return null === $target ? $url : home_url( $target ) . ( $fragment ? '#' . $fragment : '' );
	}

	public static function menu( array $attributes ): array {
		if ( isset( $attributes['href'] ) ) {
			$attributes['href'] = self::normalize( $attributes['href'] );
		}
		return $attributes;
	}

	public static function content( $content ) {
		$processor = new \WP_HTML_Tag_Processor( $content );
		while ( $processor->next_tag( array( 'tag_name' => 'A' ) ) ) {
			$href = $processor->get_attribute( 'href' );
			if ( is_string( $href ) ) {
				$processor->set_attribute( 'href', self::normalize( $href ) );
			}
		}
		return $processor->get_updated_html();
	}
}
