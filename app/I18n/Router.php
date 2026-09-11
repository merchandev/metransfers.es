<?php
namespace MeTransfers\I18n;

final class Router {
	const RULES_VERSION = 'v7-es-en-us-only';

	public function register() {
		add_action( 'init', array( __CLASS__, 'registerRewriteRules' ), 5 );
		add_filter( 'query_vars', array( __CLASS__, 'registerQueryVars' ) );
		add_action( 'after_switch_theme', 'flush_rewrite_rules' );
		add_action( 'init', array( __CLASS__, 'maybeFlushRules' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'dispatch' ), 1 );
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'localizeMenuLink' ), 20, 3 );
		add_filter( 'nav_menu_item_title', array( __CLASS__, 'translateMenuTitle' ), 10, 4 );
	}

	public static function matchRequest( $request_uri, array $active_languages ) {
		$path = trim( (string) parse_url( (string) $request_uri, PHP_URL_PATH ), '/' );
		if ( '' === $path ) {
			return null;
		}
		$segments = explode( '/', $path );
		$language = strtolower( array_shift( $segments ) );
		if ( 'es' === $language || ! in_array( $language, $active_languages, true ) ) {
			return null;
		}

		return array(
			'language' => $language,
			'page'     => empty( $segments ) ? 'home' : implode( '/', $segments ),
		);
	}

	public static function fixedTemplate( $page ) {
		$templates = array(
			'home'                          => 'front-page.php',
			'aeropuerto-barcelona'          => 'template-servicio.php',
			'traslados-aeropuerto'          => 'template-servicio.php',
			'puerto-barcelona'              => 'template-servicio.php',
			'traslados-puerto'              => 'template-servicio.php',
			'conductor-privado'             => 'template-servicio.php',
			'chofer-por-horas'              => 'template-servicio.php',
			'traslados-corporativos'        => 'template-servicio.php',
			'corporativo-y-eventos'         => 'template-servicio.php',
			'tours-privados'                => 'template-tours.php',
			'bodas-eventos'                 => 'template-servicio.php',
			'grupos'                        => 'template-servicio.php',
			'flota'                         => 'template-flota.php',
			'taxis-privado-barcelona'       => 'page-taxis-privado-barcelona.php',
			'traslados-privados'            => 'page-traslados-privados.php',
			'taxis-barcelona-port-aventura' => 'page-taxis-barcelona-port-aventura.php',
			'taxis-barcelona-salou'         => 'page-taxis-barcelona-salou.php',
			'taxis-barcelona-costa-brava'   => 'page-taxis-barcelona-costa-brava.php',
			'taxis-barcelona-girona'        => 'page-taxis-barcelona-girona.php',
			'reservaciones'                 => 'page-reservaciones.php',
			'seleccionar-vehiculo'          => 'page.php',
			'reservas-metransfers'          => 'page.php',
			'pago'                          => 'page.php',
			'reservas-hotel'                => 'page.php',
			'contacto'                      => 'page-contacto.php',
			'gracias'                       => 'page-gracias.php',
			'faq'                           => 'page.php',
			'privacidad'                    => 'page.php',
			'terminos-y-condiciones'        => 'page.php',
			'cookies'                       => 'page.php',
			'blog'                          => 'index.php',
			'noticias'                      => 'index.php',
			'rutas'                         => 'archive-ruta.php',
		);

		if ( isset( $templates[ $page ] ) ) {
			return $templates[ $page ];
		}
		if ( 0 === strpos( $page, 'taxis-barcelona-' ) || 0 === strpos( $page, 'traslados-barcelona-' ) ) {
			$seo_tpl = get_template_directory() . '/page-seo-dynamic.php';
			if ( file_exists( $seo_tpl ) ) {
				return 'page-seo-dynamic.php';
			}
		}

		return null;
	}

	public static function registerRewriteRules() {
		$languages = array_values(
			array_filter(
				MT_ACTIVE_LANGS,
				static function ( $language ) {
					return 'es' !== $language;
				}
			)
		);
		if ( empty( $languages ) ) {
			return;
		}
		$pattern = implode(
			'|',
			array_map(
				static function ( $language ) {
					return preg_quote( $language, '#' );
				},
				$languages
			)
		);

		add_rewrite_rule( '^(' . $pattern . ')/?$', 'index.php?mt_lang=$matches[1]&mt_page=home', 'top' );
		add_rewrite_rule( '^(' . $pattern . ')/(.+?)/?$', 'index.php?mt_lang=$matches[1]&mt_page=$matches[2]', 'top' );
	}

	public static function maybeFlushRules() {
		if ( self::RULES_VERSION === get_option( 'mt_i18n_rules_version' ) ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'mt_i18n_rules_version', self::RULES_VERSION, false );
	}

	public static function registerQueryVars( $variables ) {
		$variables[] = 'mt_lang';
		$variables[] = 'mt_page';
		return array_values( array_unique( $variables ) );
	}

	public static function dispatch() {
		$language = sanitize_key( (string) get_query_var( 'mt_lang' ) );
		$page     = trim( (string) get_query_var( 'mt_page' ), '/' );
		if ( ! $language || ! $page || ! defined( 'MT_ACTIVE_LANGS' ) || ! in_array( $language, MT_ACTIVE_LANGS, true ) || 'es' === $language ) {
			return;
		}

		Language::set( $language );
		if ( 'home' === $page ) {
			self::hydrateFrontPage();
			return;
		}

		if ( 'rutas' === $page ) {
			self::hydrateRouteArchive();
			return;
		}

		$post = self::postForTranslatedPath( $page );
		if ( $post && self::isPublicPost( $post ) ) {
			self::hydratePost( $post );
			return;
		}

		global $wp_query;
		if ( $wp_query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
	}

	public static function isPublicPost( $post ): bool {
		if ( ! $post || 'publish' !== ( $post->post_status ?? '' ) || ! empty( $post->post_password ) ) {
			return false;
		}
		if ( function_exists( 'is_post_publicly_viewable' ) ) {
			return is_post_publicly_viewable( $post );
		}
		return ! isset( $post->publicly_viewable ) || (bool) $post->publicly_viewable;
	}

	private static function postForTranslatedPath( string $page ) {
		$id = url_to_postid( home_url( '/' . trim( $page, '/' ) . '/' ) );
		if ( $id ) {
			return get_post( $id );
		}
		return get_page_by_path( trim( $page, '/' ), OBJECT, array( 'page', 'post', 'ruta' ) );
	}

	private static function hydrateFrontPage(): void {
		$page_id = (int) get_option( 'page_on_front' );
		$post    = $page_id ? get_post( $page_id ) : null;
		if ( $post && self::isPublicPost( $post ) ) {
			self::hydratePost( $post, true );
		}
	}

	private static function hydrateRouteArchive(): void {
		global $wp_query;
		if ( ! $wp_query ) {
			return;
		}
		$wp_query->is_404               = false;
		$wp_query->is_archive           = true;
		$wp_query->is_post_type_archive = true;
		$wp_query->is_singular          = false;
		$wp_query->set( 'post_type', 'ruta' );
		status_header( 200 );
	}

	private static function hydratePost( $post, bool $front_page = false ): void {
		global $wp_query, $wp_the_query;
		if ( ! $wp_query ) {
			return;
		}
		$wp_query->posts             = array( $post );
		$wp_query->post              = $post;
		$wp_query->post_count        = 1;
		$wp_query->found_posts       = 1;
		$wp_query->max_num_pages     = 1;
		$wp_query->is_404            = false;
		$wp_query->is_singular       = true;
		$wp_query->is_page           = 'page' === $post->post_type;
		$wp_query->is_single         = 'page' !== $post->post_type;
		$wp_query->is_home           = false;
		$wp_query->is_front_page     = $front_page;
		$wp_query->queried_object    = $post;
		$wp_query->queried_object_id = (int) $post->ID;
		$wp_query->set( 'p', (int) $post->ID );
		$wp_query->set( 'page_id', 'page' === $post->post_type ? (int) $post->ID : 0 );
		$wp_query->set( 'post_type', $post->post_type );
		$wp_the_query    = $wp_query;
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		status_header( 200 );
	}

	public static function localizeMenuLink( $atts, $menu_item, $args ) {
		if ( ! Language::isTranslated() || empty( $atts['href'] ) || ! is_string( $atts['href'] ) ) {
			return $atts;
		}
		$host = (string) parse_url( $atts['href'], PHP_URL_HOST );
		if ( $host && strtolower( $host ) !== strtolower( (string) parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return $atts;
		}
		$path         = trim( (string) parse_url( $atts['href'], PHP_URL_PATH ), '/' );
		$atts['href'] = Language::urlForLanguage( Language::get(), $path );
		return $atts;
	}

	public static function translateMenuTitle( $title, $menu_item = null, $args = null, $depth = null ) {
		return Translation::translate( $title );
	}
}
