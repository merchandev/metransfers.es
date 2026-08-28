<?php
namespace MeTransfers\I18n;

final class Router {
	const RULES_VERSION = 'v6-full-route-map';

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
			// Home
			'home'                          => 'front-page.php',

			// Servicios principales
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

			// Páginas SEO manuales (plantillas dedicadas)
			'taxis-privado-barcelona'       => 'page-taxis-privado-barcelona.php',
			'taxis-barcelona-port-aventura' => 'page-taxis-barcelona-port-aventura.php',
			'taxis-barcelona-salou'         => 'page-taxis-barcelona-salou.php',
			'taxis-barcelona-costa-brava'   => 'page-taxis-barcelona-costa-brava.php',
			'taxis-barcelona-girona'        => 'page-taxis-barcelona-girona.php',

			// Páginas SEO dinámicas (taxis-* y traslados-barcelona-* usan page-seo-dynamic.php)
			// Se resuelven en dispatch() por prefijo, ver lógica de isSeoPage()

			// Reservas y flujo de booking
			'reservaciones'                 => 'page-reservaciones.php',
			'seleccionar-vehiculo'          => 'page.php',
			'reservas-metransfers'          => 'page.php',
			'pago'                          => 'page.php',
			'reservas-hotel'                => 'page.php',

			// Soporte / Legales
			'contacto'                      => 'page-contacto.php',
			'gracias'                       => 'page-gracias.php',
			'faq'                           => 'page.php',
			'privacidad'                    => 'page.php',
			'terminos-y-condiciones'        => 'page.php',
			'cookies'                       => 'page.php',

			// Blog / Rutas
			'blog'                          => 'index.php',
			'noticias'                      => 'index.php',
			'rutas'                         => 'archive-ruta.php',
		);

		// Páginas SEO dinámicas: taxis-barcelona-* y traslados-barcelona-*
		// usan la plantilla dinámica unificada.
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

	public static function registerQueryVars( $variables ) {
		$variables[] = 'mt_lang';
		$variables[] = 'mt_page';
		return array_values( array_unique( $variables ) );
	}

	public static function maybeFlushRules() {
		if ( self::RULES_VERSION === get_option( 'mt_i18n_rules_flushed' ) ) {
			return;
		}
		flush_rewrite_rules();
		update_option( 'mt_i18n_rules_flushed', self::RULES_VERSION, false );
	}

	public static function dispatch() {
		$language = (string) get_query_var( 'mt_lang' );
		if ( ! $language || ! in_array( $language, MT_ACTIVE_LANGS, true ) || 'es' === $language ) {
			return;
		}
		Language::set( $language );
		$page          = trim( (string) get_query_var( 'mt_page', 'home' ), '/' );
		$page          = '' !== $page ? $page : 'home';
		$template      = self::fixedTemplate( $page );
		$original_post = null;

		// Páginas del flujo de booking: buscar el post real en WordPress por slug
		// para que booking_phase() pueda leer el post_content y detectar shortcodes.
		$booking_flow_pages = array(
			'seleccionar-vehiculo',
			'reservas-metransfers',
			'pago',
			'reservas-hotel',
			'gracias',
			'faq',
			'privacidad',
			'terminos-y-condiciones',
			'cookies',
			'contacto',
		);

		if ( in_array( $page, array( 'blog', 'noticias' ), true ) ) {
			self::hydrateArchive( 'post' );
		} elseif ( 'rutas' === $page ) {
			self::hydrateArchive( 'ruta' );
		} elseif ( in_array( $page, $booking_flow_pages, true ) || null === $template ) {
			// Intentar primero obtener el post real por slug exacto
			$post_id = url_to_postid( home_url( '/' . $page . '/' ) );
			if ( ! $post_id ) {
				// Fallback: buscar por post_name directamente
				$found = get_page_by_path( $page, 'OBJECT', array( 'page', 'post' ) );
				if ( $found ) {
					$post_id = $found->ID;
				}
			}
			if ( $post_id ) {
				$original_post = get_post( $post_id );
				if ( is_array( $original_post ) ) {
					$original_post = new \WP_Post( (object) $original_post );
				}
				if ( null === $template ) {
					$template = self::templateForPost( $original_post, $post_id );
				}
			}
		}

		if ( null === $template ) {
			self::setNotFound();
			return;
		}

		$full_path = get_template_directory() . '/' . $template;
		if ( ! file_exists( $full_path ) ) {
			self::setNotFound();
			return;
		}

		if ( $original_post ) {
			self::hydrateSingular( $original_post );
		} elseif ( ! in_array( $page, array( 'blog', 'noticias', 'rutas' ), true ) ) {
			self::hydrateVirtualPage( $page );
		}

		status_header( 200 );
		add_filter(
			'template_include',
			static function () use ( $full_path ) {
				return $full_path;
			},
			99
		);
	}


	public static function localizeMenuLink( $attributes, $menu_item = null, $args = null ) {
		if ( ! Language::isTranslated() || empty( $attributes['href'] ) ) {
			return $attributes;
		}
		$href      = $attributes['href'];
		$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$href_host = wp_parse_url( $href, PHP_URL_HOST );
		if ( $href_host && $home_host && strtolower( $href_host ) !== strtolower( $home_host ) ) {
			return $attributes;
		}

		$path               = Language::pathWithoutLanguage( $href );
		$localized          = Language::url( $path );
		$fragment           = wp_parse_url( $href, PHP_URL_FRAGMENT );
		$attributes['href'] = $fragment ? $localized . '#' . $fragment : $localized;
		return $attributes;
	}

	public static function translateMenuTitle( $title, $item = null, $args = null, $depth = 0 ) {
		return Translation::translate( $title );
	}

	private static function templateForPost( $post, $post_id ) {
		if ( ! $post ) {
			return null;
		}
		$custom = get_page_template_slug( $post_id );
		if ( $custom && file_exists( get_template_directory() . '/' . $custom ) ) {
			return $custom;
		}
		if ( 'ruta' === $post->post_type ) {
			return 'single-ruta.php';
		}
		return 'post' === $post->post_type ? 'single.php' : 'page.php';
	}

	private static function hydrateArchive( $post_type ) {
		global $wp_query;
		$paged                          = max( 1, (int) get_query_var( 'paged', 1 ) );
		$query                          = new \WP_Query(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'paged'       => $paged,
			)
		);
		$wp_query->posts                = $query->posts;
		$wp_query->post_count           = $query->post_count;
		$wp_query->found_posts          = $query->found_posts;
		$wp_query->max_num_pages        = $query->max_num_pages;
		$wp_query->current_post         = -1;
		$wp_query->is_404               = false;
		$wp_query->is_home              = 'post' === $post_type;
		$wp_query->is_archive           = 'ruta' === $post_type;
		$wp_query->is_post_type_archive = 'ruta' === $post_type;
		$wp_query->is_singular          = false;
		$wp_query->is_single            = false;
		$wp_query->is_page              = false;
		$wp_query->is_front_page        = false;

		if ( 'post' === $post_type ) {
			$blog_id = (int) get_option( 'page_for_posts' );
			if ( $blog_id ) {
				$blog_post                   = get_post( $blog_id );
				$wp_query->queried_object    = $blog_post;
				$wp_query->queried_object_id = $blog_id;
			}
		} else {
			$wp_query->queried_object = get_post_type_object( 'ruta' );
		}
	}

	private static function hydrateSingular( $original_post ) {
		global $post, $wp_query;
		$post                           = $original_post;
		$wp_query->queried_object       = $original_post;
		$wp_query->queried_object_id    = $original_post->ID;
		$wp_query->post                 = $original_post;
		$wp_query->posts                = array( $original_post );
		$wp_query->post_count           = 1;
		$wp_query->found_posts          = 1;
		$wp_query->current_post         = -1;
		$wp_query->is_404               = false;
		$wp_query->is_home              = false;
		$wp_query->is_archive           = false;
		$wp_query->is_post_type_archive = false;
		$wp_query->is_front_page        = false;
		$wp_query->is_page              = 'page' === $original_post->post_type;
		$wp_query->is_singular          = true;
		$wp_query->is_single            = 'page' !== $original_post->post_type;
		setup_postdata( $post );
	}

	private static function hydrateVirtualPage( $page ) {
		$fallback = get_page_by_path( $page );
		if ( is_array( $fallback ) ) {
			$fallback = new \WP_Post( (object) $fallback );
		}
		if ( ! $fallback ) {
			$fallback = self::virtualPost( $page );
		}
		self::hydrateSingular( $fallback );
		global $wp_query;
		$wp_query->is_home       = 'home' === $page;
		$wp_query->is_front_page = 'home' === $page;
	}

	private static function virtualPost( $page ) {
		$now = current_time( 'mysql' );
		return new \WP_Post(
			(object) array(
				'ID'                    => 0,
				'post_author'           => 1,
				'post_date'             => $now,
				'post_date_gmt'         => current_time( 'mysql', true ),
				'post_content'          => '',
				'post_title'            => ucfirst( str_replace( '-', ' ', $page ) ),
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_password'         => '',
				'post_name'             => $page,
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => $now,
				'post_modified_gmt'     => current_time( 'mysql', true ),
				'post_content_filtered' => '',
				'post_parent'           => 0,
				'guid'                  => home_url( '/' . $page . '/' ),
				'menu_order'            => 0,
				'post_type'             => 'page',
				'post_mime_type'        => '',
				'comment_count'         => 0,
				'filter'                => 'raw',
			)
		);
	}

	private static function setNotFound() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
