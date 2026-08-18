<?php
/**
 * Me Transfers functions and definitions
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


require_once get_template_directory() . '/includes/i18n.php';
require_once get_template_directory() . '/includes/destinations.php';
require_once get_template_directory() . '/includes/faq.php';
require_once get_template_directory() . '/includes/legal-pages.php';

// sync version 7.3 (Fix reservations and contact styles)
require_once get_template_directory() . '/includes/seo-page-titles.php';
require_once get_template_directory() . '/includes/tours.php';
require_once get_template_directory() . '/includes/services.php';
require_once get_template_directory() . '/includes/request-cpt.php'; // Updated to trigger sync v6
require_once get_template_directory() . '/includes/tour-bookings.php';

// DESACTIVADO: mt_update_all_page_titles_once() anteponÃ­a "MeTransfers Barcelona -" a TODOS
// los tÃ­tulos editoriales de las pÃ¡ginas, mezclando el tÃ­tulo interno con el tÃ­tulo SEO.
// Los tres conceptos deben estar separados: tÃ­tulo interno, H1 visible y <title> SEO.
// Si necesitas renombrar pÃ¡ginas, hazlo manualmente desde el panel de WordPress.
// add_action( 'admin_init', 'mt_update_all_page_titles_once' );
function mt_update_all_page_titles_once() {
    // FunciÃ³n conservada por si se necesita referenciar el historial de migraciÃ³n.
    // No conectada a ningÃºn hook. No se ejecuta automÃ¡ticamente.
}



require_once get_template_directory() . '/includes/rutas-cpt.php';
require_once get_template_directory() . '/includes/leads-cpt.php';

// Herramienta de administraciÃ³n: repoblar post_content desde los catÃ¡logos PHP.
// Disponible en: Herramientas â†’ Repoblar Contenido
if ( is_admin() && defined( 'ME_TRANSFERS_ENABLE_MIGRATIONS' ) && ME_TRANSFERS_ENABLE_MIGRATIONS ) {
    require_once get_template_directory() . '/includes/admin-content-repopulate.php';
    require_once get_template_directory() . '/includes/auto-migration-v5.php';
}

add_action( 'template_redirect', function() {

    if (
        is_404()
        && 'destinos' === trim(
            wp_parse_url(
                $_SERVER['REQUEST_URI'] ?? '',
                PHP_URL_PATH
            ),
            '/'
        )
    ) {
        wp_safe_redirect(
            home_url( '/#destinos' ),
            301
        );

        exit;
    }

} );

// Migration safety switch â€” set to false once initial migration is done.
if ( ! defined( 'ME_TRANSFERS_ENABLE_MIGRATIONS' ) ) {
	define( 'ME_TRANSFERS_ENABLE_MIGRATIONS', false );
}

// Centralized Versioning
if ( ! defined( 'ME_TRANSFERS_VERSION' ) ) {
	define( 'ME_TRANSFERS_VERSION', '4.1.5' );
}



/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function me_transfers_setup() {
	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	// Let WordPress manage the document title.
	add_theme_support( 'title-tag' );

	// Ensure classic WordPress menu management stays available.
	add_theme_support( 'menus' );

	// Enable support for Post Thumbnails on posts and pages.
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'me-transfers' ),
			'footer' => esc_html__( 'Footer Menu', 'me-transfers' ),
		)
	);

	// Switch default core markup for search form, comment form, and comments
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Add support for core custom logo.
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'me_transfers_setup' );

/**
 * Unregister default WordPress sidebar widget areas so they don't
 * render on blog/archive pages.
 *
 * @return void
 */
function me_transfers_unregister_sidebars() {
	unregister_sidebar( 'sidebar-1' );
	unregister_sidebar( 'sidebar-2' );
	unregister_sidebar( 'sidebar-3' );
}
add_action( 'widgets_init', 'me_transfers_unregister_sidebars', 20 );

/**
 * Removido: me_transfers_prefix_document_title 
 * Para evitar "Keyword Stuffing" y permitir que WordPress (y el usuario en Ajustes) manejen el tÃ­tulo limpiamente.
 */



/**
 * Fallback menu renderer.
 *
 * If no menu is assigned to the location, render the first native WP menu
 * with items so custom Navigation Labels are respected.
 * Final fallback is a basic page list.
 *
 * @param array $args Nav menu arguments.
 * @return void
 */
function me_transfers_fallback_menu( $args ) {
	$menu_class = ! empty( $args['menu_class'] ) ? $args['menu_class'] : 'nav-menu';
	$menu_id    = ! empty( $args['menu_id'] ) ? (string) $args['menu_id'] : '';
	$depth      = isset( $args['depth'] ) ? absint( $args['depth'] ) : 1;

	$menus = wp_get_nav_menus();

	if ( ! empty( $menus ) && ! is_wp_error( $menus ) ) {
		foreach ( $menus as $menu_obj ) {
			$menu_items = wp_get_nav_menu_items( $menu_obj->term_id );

			if ( empty( $menu_items ) || is_wp_error( $menu_items ) ) {
				continue;
			}

			wp_nav_menu(
				array(
					'menu'        => (int) $menu_obj->term_id,
					'menu_id'     => $menu_id,
					'menu_class'  => $menu_class,
					'container'   => false,
					'fallback_cb' => false,
					'depth'       => $depth,
				)
			);
			return;
		}
	}

	$menu_id_attr = '' !== $menu_id ? sprintf( ' id="%s"', esc_attr( $menu_id ) ) : '';

	echo '<ul' . $menu_id_attr . ' class="' . esc_attr( $menu_class ) . '">';
	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
		)
	);
	echo '</ul>';
}

/**
 * Remove deprecated shortcodes that should no longer render in content.
 *
 * @param string $content Post content.
 * @return string
 */
function me_transfers_strip_deprecated_shortcodes( $content ) {
	if ( ! is_string( $content ) || false === stripos( $content, '[mt_hero_card' ) ) {
		return $content;
	}

	return preg_replace( '/\[\/?mt_hero_card[^\]]*\]/i', '', $content );
}
add_filter( 'the_content', 'me_transfers_strip_deprecated_shortcodes', 1 );
add_filter( 'get_the_excerpt', 'me_transfers_strip_deprecated_shortcodes', 1 );


/**
 * Enqueue scripts and styles.
 */
function me_transfers_scripts() {
	// Enqueue Google Fonts (Outfit and Inter)
	wp_enqueue_style( 'me-transfers-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap', array(), null );

	// Main stylesheet
	$style_path    = get_stylesheet_directory() . '/style.css';
	$style_version = file_exists($style_path) ? filemtime($style_path) : ME_TRANSFERS_VERSION;
	wp_enqueue_style( 'me-transfers-style', get_stylesheet_uri(), array(), $style_version );

	// Base dependencies for main.js
	$main_deps = array();

	// GSAP Library (Condicional para performance)
	if ( is_front_page() || is_page_template( 'template-tours.php' ) || is_singular( 'tour' ) || is_singular( 'ruta' ) ) {
		wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), '3.12.5', true );
		wp_enqueue_script( 'gsap-scroll-trigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js', array('gsap'), '3.12.5', true );
		$main_deps = array('gsap', 'gsap-scroll-trigger');
	}

	// Theme custom scripts
	wp_enqueue_script( 'me-transfers-main-js', get_template_directory_uri() . '/assets/js/main.js', $main_deps, ME_TRANSFERS_VERSION, true );
	
	// Localize script for AJAX requests
	wp_localize_script( 'me-transfers-main-js', 'mtAjax', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'mt_lead_nonce' )
	) );

	// Localize AJAX script
	$ajax_config = array(
		'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
		'tourBookingNonce'  => wp_create_nonce( 'mt_tour_booking_nonce' ),
		'contactNonce'      => wp_create_nonce( 'mt_contact_request' ),
	);

	wp_localize_script( 'me-transfers-main-js', 'meTransfers', $ajax_config );
	wp_localize_script( 'me-transfers-main-js', 'meTransfersPublic', $ajax_config );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'me_transfers_scripts' );

// Add DEFER/ASYNC attributes to selected scripts.
add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
	if ( in_array( $handle, array( 'gsap', 'gsap-scroll-trigger', 'me-transfers-main-js' ), true ) ) {
		if ( false === strpos( $tag, ' defer' ) ) {
			$tag = str_replace( ' src', ' defer src', $tag );
		}
	}

	if ( is_string( $src ) && false !== strpos( $src, 'maps.googleapis.com/maps/api/js' ) ) {
		if ( false === strpos( $tag, ' async' ) ) {
			$tag = str_replace( ' src', ' async src', $tag );
		}
		if ( false === strpos( $tag, ' defer' ) ) {
			$tag = str_replace( ' src', ' defer src', $tag );
		}
	}

	return $tag;
}, 10, 3 );

/**
 * Ensure Maps JS API URL includes loading=async when enqueued by plugins.
 *
 * @param string $src Script source URL.
 * @return string
 */
function me_transfers_maps_async_query_arg( $src ) {
	if ( ! is_string( $src ) || '' === $src ) {
		return $src;
	}

	if ( false === strpos( $src, 'maps.googleapis.com/maps/api/js' ) ) {
		return $src;
	}

	if ( false !== strpos( $src, 'loading=' ) ) {
		return $src;
	}

	return add_query_arg( 'loading', 'async', $src );
}
add_filter( 'script_loader_src', 'me_transfers_maps_async_query_arg', 20 );

/**
 * Returns a section URL that works both on the front page and inner templates.
 *
 * @param string $section Section ID without the leading #.
 * @return string
 */
function me_transfers_get_section_url( $section = 'search' ) {
	$section = sanitize_title_with_dashes( ltrim( (string) $section, "# \t\n\r\0\x0B/" ) );

	if ( '' === $section ) {
		return home_url( '/' );
	}

	if ( is_front_page() ) {
		return '#' . $section;
	}

	return home_url( '/#' . $section );
}




/* ==========================================================================
   ROL PERSONALIZADO: CHECKHOTELES Y RESTRICCION DE MENUS
   ========================================================================== */

// 1. Crear el nuevo rol
add_action( 'after_switch_theme', 'me_transfers_create_checkhoteles_role' );

// Fallback: crear rol si no existe aun (primera instalacion sin cambio de tema),
// protegido por transient de larga duracion para no repetirse en cada request.
add_action( 'init', function() {
    if ( ! get_role( 'check_hoteles' ) && ! get_transient( 'me_transfers_role_created' ) ) {
        me_transfers_create_checkhoteles_role();
        set_transient( 'me_transfers_role_created', true, DAY_IN_SECONDS * 365 );
    }
} );
function me_transfers_create_checkhoteles_role() {
    $role = get_role( 'check_hoteles' );
    if ( ! $role ) {
        $role = add_role( 'check_hoteles', 'CheckHoteles', array(
            'read' => true,
        ));
    }

    if ( $role ) {
        // Eliminar permisos excesivos
        $role->remove_cap( 'manage_options' );
        $role->remove_cap( 'edit_posts' );
        $role->remove_cap( 'edit_others_posts' );
        $role->remove_cap( 'edit_published_posts' );
        $role->remove_cap( 'publish_posts' );
        
        // AÃ±adir capacidades especÃ­ficas
        $role->add_cap( 'read_transfer_requests' );
        $role->add_cap( 'edit_transfer_requests' );
        $role->add_cap( 'read_tour_bookings' );
        $role->add_cap( 'export_transfer_requests' );
    }
}

// 2. Ocultar menÃºs no deseados en el panel izquierdo
add_action('admin_menu', 'me_transfers_hide_menus_checkhoteles', 999);
function me_transfers_hide_menus_checkhoteles() {
    $user = wp_get_current_user();
    if ( in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
        global $menu;
        
        // Palabras clave de los menÃºs permitidos
        $allowed_menus = array(
            'index.php', // Escritorio
            'edit.php?post_type=hotel_partner', // Hoteles QR
            'edit.php?post_type=mt_request', // Solicitudes
            'edit.php?post_type=gyg_review', // GYG Reviews (si es CPT)
            'gyg-reviews', // GYG Reviews (si es plugin/pÃ¡gina)
            'agente-ia', // Agente IA
            'wp-agente-ia',
            'sg-cachepress', // Speed Optimizer
            'sg-security', // Security Optimizer
            'profile.php' // Perfil de usuario
        );
        
        foreach ( $menu as $key => $item ) {
            $menu_slug = $item[2];
            $is_allowed = false;
            foreach ( $allowed_menus as $allowed ) {
                if ( strpos( $menu_slug, $allowed ) !== false ) {
                    $is_allowed = true;
                    break;
                }
            }
            if ( ! $is_allowed ) {
                remove_menu_page( $menu_slug );
            }
        }
    }
}

// 3. Bloquear acceso directo por URL a pÃ¡ginas no permitidas
add_action('admin_init', 'me_transfers_restrict_checkhoteles_access');
function me_transfers_restrict_checkhoteles_access() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }
    
    $user = wp_get_current_user();
    if ( in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
        global $pagenow;
        
        $allowed_pages = array( 'index.php', 'profile.php', 'admin-ajax.php', 'admin-post.php' );
        $is_allowed = in_array( $pagenow, $allowed_pages );
        
        // Permitir listas de Custom Post Types
        if ( $pagenow === 'edit.php' && isset($_GET['post_type']) ) {
            $allowed_cpts = array('hotel_partner', 'mt_request', 'gyg_review');
            if ( in_array( sanitize_key( wp_unslash( $_GET['post_type'] ) ), $allowed_cpts, true ) ) {
                $is_allowed = true;
            }
        }
        
        // Permitir ediciÃ³n de Custom Post Types
        if ( ($pagenow === 'post.php' || $pagenow === 'post-new.php') ) {
            $post_type = '';
            if ( isset($_GET['post']) ) {
                $post_type = get_post_type( (int) $_GET['post'] );
            } elseif ( isset($_POST['post_ID']) ) {
                $post_type = get_post_type($_POST['post_ID']);
            } elseif ( isset($_GET['post_type']) ) {
                $post_type = $_GET['post_type'];
            } elseif ( isset($_POST['post_type']) ) {
                $post_type = $_POST['post_type'];
            }
            if ( in_array($post_type, array('hotel_partner', 'mt_request', 'gyg_review')) ) {
                $is_allowed = true;
            }
        }
        
        // Permitir pÃ¡ginas de plugins
        if ( isset($_GET['page']) ) {
            $requested_page  = sanitize_key( wp_unslash( $_GET['page'] ) );
            $allowed_plugins = array('sg-cachepress', 'sg-security', 'agente-ia', 'wp-agente-ia', 'gyg-reviews');
            foreach ($allowed_plugins as $plugin) {
                if ( $requested_page === $plugin || str_starts_with( $requested_page, $plugin ) ) {
                    $is_allowed = true;
                    break;
                }
            }
        }
        
        // Si no estÃ¡ permitido, redirigir al escritorio
        if ( ! $is_allowed ) {
            wp_redirect( admin_url( 'index.php' ) );
            exit;
        }
    }
}

// 4. Limitar visualizaciÃ³n de Hoteles QR a los creados por el usuario
add_action('pre_get_posts', 'me_transfers_restrict_hotel_partner_view');
function me_transfers_restrict_hotel_partner_view($query) {
    if ( is_admin() && $query->is_main_query() ) {
        $user = wp_get_current_user();
        if ( in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
            if ( $query->get('post_type') === 'hotel_partner' ) {
                $query->set('author', $user->ID);
            }
        }
    }
}

// Ocultar recuentos (Todo | Publicados) para el rol CheckHoteles
add_filter( 'views_edit-hotel_partner', 'me_transfers_hide_hotel_partner_counts' );
function me_transfers_hide_hotel_partner_counts( $views ) {
    $user = wp_get_current_user();
    if ( in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
        return array(); 
    }
    return $views;
}

// Ocultar widgets por defecto de WP en el escritorio para CheckHoteles
add_action( 'wp_dashboard_setup', 'me_transfers_remove_default_dashboard_widgets', 999 );
function me_transfers_remove_default_dashboard_widgets() {
    $user = wp_get_current_user();
    if ( in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
        remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' ); // Actividad
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' ); // Eventos y Noticias
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' ); // Borrador rapido
        remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' ); // De un vistazo
    }
}

/**
 * One-off migration: Copy hardcoded content to post_content for SEO & Editing
 */
function me_transfers_migrate_content_to_editor() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( get_option( 'me_transfers_content_migrated_v1' ) ) {
        return;
    }
    // Transient lock: prevents simultaneous execution in race conditions.
    if ( get_transient( 'me_transfers_migrating_content_v1' ) ) {
        return;
    }
    set_transient( 'me_transfers_migrating_content_v1', true, MINUTE_IN_SECONDS * 5 );

    // Hub Page
    $hub = get_page_by_path( 'destinos', OBJECT, 'page' );
    if ( $hub && empty( trim( $hub->post_content ) ) ) {
        wp_update_post( array(
            'ID' => $hub->ID,
            'post_content' => 'Explora los destinos mÃ¡s solicitados y accede a una ficha rÃ¡pida para pedir informaciÃ³n de traslados privados, recogidas en aeropuerto, hoteles, puertos y rutas personalizadas.'
        ) );
    }

    // Destinations
    $destinations = me_transfers_get_destination_catalog();
    foreach ( $destinations as $dest ) {
        $page = get_page_by_path( $dest['slug'], OBJECT, 'page' );
        if ( ! $page ) {
            $page = get_page_by_path( 'destinos/' . $dest['slug'], OBJECT, 'page' );
        }
        if ( $page && empty( trim( $page->post_content ) ) ) {
            $content = '<p>' . esc_html( $dest['travel_note'] ) . '</p>';
            $content .= '<p>' . esc_html( sprintf( 'Si estÃ¡s organizando un traslado hacia %s, podemos prepararte una propuesta adaptada al punto de recogida, nÃºmero de pasajeros, fecha estimada y tipo de servicio que necesites.', $dest['title'] ) ) . '</p>';
            $content .= '<ul>';
            foreach ( $dest['highlights'] as $highlight ) {
                $content .= '<li>' . esc_html( $highlight ) . '</li>';
            }
            $content .= '</ul>';
            
            wp_update_post( array(
                'ID' => $page->ID,
                'post_content' => $content
            ) );
        }
    }

    // Tours
    $tours = me_transfers_get_tour_catalog();
    foreach ( $tours as $slug => $tour ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $page && empty( trim( $page->post_content ) ) ) {
            $paragraphs = isset( $tour['full_desc'] ) ? explode( "\n\n", $tour['full_desc'] ) : array( $tour['desc'] );
            $content = '';
            foreach ( $paragraphs as $p ) {
                $p = trim( $p );
                if ( $p ) {
                    $content .= '<p>' . esc_html( $p ) . '</p>';
                }
            }
            
            wp_update_post( array(
                'ID' => $page->ID,
                'post_content' => $content
            ) );
        }
    }

    update_option( 'me_transfers_content_migrated_v1', true );
}
if ( defined( 'ME_TRANSFERS_ENABLE_MIGRATIONS' ) && ME_TRANSFERS_ENABLE_MIGRATIONS ) {
	add_action( 'admin_init', 'me_transfers_migrate_content_to_editor' );
}

/**
 * Migration v2: Copy hardcoded content to post_content for Services
 */
function me_transfers_migrate_services_to_editor() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( get_option( 'me_transfers_content_migrated_services' ) ) {
        return;
    }
    // Transient lock: prevents simultaneous execution in race conditions.
    if ( get_transient( 'me_transfers_migrating_services' ) ) {
        return;
    }
    set_transient( 'me_transfers_migrating_services', true, MINUTE_IN_SECONDS * 5 );

    $services = me_transfers_get_service_catalog();
    foreach ( $services as $slug => $service ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $page && empty( trim( $page->post_content ) ) ) {
            wp_update_post( array(
                'ID' => $page->ID,
                'post_content' => '<p>' . esc_html( $service['full_desc'] ) . '</p>'
            ) );
        }
    }

    update_option( 'me_transfers_content_migrated_services', true );
}
if ( defined( 'ME_TRANSFERS_ENABLE_MIGRATIONS' ) && ME_TRANSFERS_ENABLE_MIGRATIONS ) {
	add_action( 'admin_init', 'me_transfers_migrate_services_to_editor' );
}

/**
 * Migration v3: Move Legal Pages from hardcoded to database
 */
function me_transfers_migrate_legal_to_editor() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( get_option( 'me_transfers_content_migrated_legal' ) ) {
        return;
    }
    // Transient lock: prevents simultaneous execution in race conditions.
    if ( get_transient( 'me_transfers_migrating_legal' ) ) {
        return;
    }
    set_transient( 'me_transfers_migrating_legal', true, MINUTE_IN_SECONDS * 5 );

    $pages = array(
        'privacidad' => '<h2>1. IdentificaciÃ³n del Responsable del Tratamiento</h2>
<p><strong>RazÃ³n Social:</strong> METRANSFERS GESTION SL</p>
<p><strong>NIF:</strong> B22522353</p>
<p><strong>Domicilio Fiscal:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÃ &ndash; (BARCELONA)</p>
<p><strong>Contacto Privacidad:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p>
<h2>2. AceptaciÃ³n Vinculante</h2>
<p>Al utilizar nuestros servicios, navegar por nuestra plataforma o completar el proceso de configuraciÃ³n de una reserva, usted reconoce haber leÃ­do, comprendido y aceptado sin reservas que sus datos personales sean tratados conforme a los tÃ©rminos aquÃ­ expuestos. La formalizaciÃ³n de una reserva constituye un contrato entre las partes, legitimando el tratamiento de los datos necesarios para la ejecuciÃ³n del servicio.</p>
<h2>3. Datos Objeto de Tratamiento</h2>
<p>Recopilamos los datos estrictamente necesarios para la prestaciÃ³n del servicio:</p>
<ul>
<li><strong>Datos de Reserva:</strong> Nombre, apellidos, telÃ©fono, correo electrÃ³nico y detalles del trayecto/servicio solicitado.</li>
<li><strong>Datos de FacturaciÃ³n:</strong> DirecciÃ³n postal y NIF/DNI (segÃºn los datos de registro fiscal de la entidad).</li>
<li><strong>Datos de NavegaciÃ³n:</strong> DirecciÃ³n IP, cookies y metadatos para garantizar la seguridad del sitio.</li>
</ul>
<h2>4. Finalidad del Tratamiento</h2>
<p>Sus datos serÃ¡n tratados con el fin de:</p>
<ul>
<li><strong>GestiÃ³n de Reservas:</strong> Tramitar, confirmar y ejecutar los servicios de transporte o gestiÃ³n contratados.</li>
<li><strong>AtenciÃ³n al Cliente:</strong> Resolver dudas y proporcionar soporte a travÃ©s del punto Ãºnico de contacto <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</li>
<li><strong>Cumplimiento Legal:</strong> Emitir facturas y cumplir con las obligaciones tributarias ante la AEAT.</li>
<li><strong>Seguridad:</strong> Prevenir fraudes y usos no autorizados de la plataforma.</li>
</ul>
<h2>5. LegitimaciÃ³n</h2>
<p>La base legal para el tratamiento es:</p>
<ul>
<li><strong>EjecuciÃ³n Contractual:</strong> Necesaria para procesar su reserva y prestarle el servicio solicitado.</li>
<li><strong>ObligaciÃ³n Legal:</strong> Derivada de la normativa fiscal y mercantil vigente en EspaÃ±a.</li>
<li><strong>Consentimiento:</strong> Otorgado explÃ­citamente al marcar la casilla de aceptaciÃ³n en nuestros formularios.</li>
</ul>
<h2>6. ConservaciÃ³n y Destinatarios</h2>
<p><strong>Plazos:</strong> Los datos se conservarÃ¡n durante el tiempo que dure la relaciÃ³n comercial y, posteriormente, durante los plazos legales de prescripciÃ³n (generalmente 6 aÃ±os para documentos contables segÃºn el CÃ³digo de Comercio).</p>
<p><strong>Cesiones:</strong> No se cederÃ¡n datos a terceros ajenos a la operativa del servicio, salvo obligaciÃ³n legal ante autoridades competentes.</p>
<h2>7. Derechos del Interesado</h2>
<p>Usted puede ejercer sus derechos de Acceso, RectificaciÃ³n, SupresiÃ³n, LimitaciÃ³n, Portabilidad y OposiciÃ³n enviando una comunicaciÃ³n escrita acompaÃ±ada de copia de su DNI a: <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p>
<p>Asimismo, tiene derecho a retirar su consentimiento en cualquier momento y a presentar una reclamaciÃ³n ante la Agencia EspaÃ±ola de ProtecciÃ³n de Datos (AEPD) si considera que sus derechos han sido vulnerados.</p>',
        'cookie' => '<h2>1. Responsable del sitio web</h2>
<p><strong>RazÃ³n social:</strong> METRANSFERS GESTION SL</p>
<p><strong>NIF:</strong> B22522353</p>
<p><strong>Domicilio:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÃ (BARCELONA)</p>
<p><strong>Correo electrÃ³nico:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p>
<h2>2. QuÃ© son las cookies</h2>
<p>Las cookies son pequeÃ±os archivos que se descargan en su dispositivo al acceder a determinadas pÃ¡ginas web. Permiten, entre otras cosas, reconocer su navegador, mantener la sesiÃ³n, recordar preferencias, reforzar la seguridad o facilitar determinadas funcionalidades tÃ©cnicas del sitio.</p>
<h2>3. Tipos de cookies</h2>
<p>Las cookies pueden clasificarse, entre otros criterios, del siguiente modo:</p>
<ul>
<li><strong>SegÃºn la entidad que las gestione:</strong> cookies propias y cookies de terceros.</li>
<li><strong>SegÃºn su finalidad:</strong> cookies tÃ©cnicas o necesarias, de preferencias o personalizaciÃ³n, de anÃ¡lisis, y de publicidad o publicidad comportamental.</li>
<li><strong>SegÃºn el tiempo que permanecen activas:</strong> cookies de sesiÃ³n y cookies persistentes.</li>
</ul>
<h2>4. Cookies utilizadas en metransfers.es</h2>
<p>Con carÃ¡cter general, este sitio utiliza o puede utilizar cookies tÃ©cnicas, de sesiÃ³n y de preferencia estrictamente relacionadas con el funcionamiento de la web y la prestaciÃ³n del servicio solicitado por el usuario. Entre ellas se incluyen, cuando proceda:</p>
<ul>
<li><strong>Cookies tÃ©cnicas de navegaciÃ³n y seguridad:</strong> necesarias para cargar la web, proteger formularios, prevenir usos abusivos y garantizar el funcionamiento bÃ¡sico del sitio.</li>
<li><strong>Cookies asociadas al proceso de reserva o contacto:</strong> necesarias para gestionar solicitudes enviadas por el usuario, mantener datos temporales de sesiÃ³n y completar procesos esenciales vinculados al servicio contratado.</li>
<li><strong>Cookies de preferencias:</strong> destinadas a recordar opciones expresamente solicitadas por el usuario, como el idioma o determinadas configuraciones de visualizaciÃ³n, cuando estas funcionalidades estÃ©n habilitadas.</li>
<li><strong>Cookies tÃ©cnicas de terceros vinculadas al servicio:</strong> determinados proveedores externos integrados en la web, como herramientas de traducciÃ³n, mapas, contenidos embebidos o pasarelas de pago seguras, pueden instalar sus propias cookies cuando el usuario interactÃºa con dichas funcionalidades.</li>
</ul>
<p>Este tema no instala por sÃ­ mismo cookies de publicidad comportamental. Si en el futuro se incorporan herramientas analÃ­ticas no exentas, servicios de personalizaciÃ³n avanzada o soluciones publicitarias que requieran consentimiento, se informarÃ¡ al usuario de forma previa y se recabarÃ¡ la autorizaciÃ³n correspondiente antes de su activaciÃ³n.</p>
<h2>5. Base jurÃ­dica</h2>
<p>Las cookies tÃ©cnicas o estrictamente necesarias pueden utilizarse sin consentimiento previo cuando resultan imprescindibles para prestar el servicio solicitado por el usuario o para posibilitar la navegaciÃ³n segura por el sitio web. Las cookies no necesarias solo podrÃ¡n utilizarse cuando exista una base jurÃ­dica adecuada y, en los casos exigidos por la normativa, tras obtener el consentimiento informado del usuario.</p>
<h2>6. Plazo de conservaciÃ³n</h2>
<p>Las cookies de sesiÃ³n permanecen activas Ãºnicamente mientras el usuario navega por el sitio y se eliminan al cerrar el navegador. Las cookies persistentes, cuando existan, se conservarÃ¡n durante el tiempo estrictamente necesario para cumplir su finalidad o hasta que el usuario las elimine manualmente desde la configuraciÃ³n de su navegador o del servicio correspondiente.</p>
<h2>7. GestiÃ³n, configuraciÃ³n y desactivaciÃ³n</h2>
<p>El usuario puede permitir, bloquear o eliminar las cookies instaladas en su dispositivo mediante la configuraciÃ³n de su navegador. Debe tener en cuenta que la desactivaciÃ³n de cookies tÃ©cnicas o necesarias puede afectar al correcto funcionamiento del sitio, del proceso de reserva o de determinadas funcionalidades esenciales.</p>
<ul>
<li><a href="https://support.google.com/chrome/answer/95647?hl=es" target="_blank" rel="noopener">Configurar cookies en Google Chrome</a></li>
<li><a href="https://support.mozilla.org/es/kb/proteccion-antirrastreo-mejorada-firefox-escritorio" target="_blank" rel="noopener">Configurar cookies en Mozilla Firefox</a></li>
<li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Configurar cookies en Safari</a></li>
<li><a href="https://support.microsoft.com/es-es/microsoft-edge/administrar-cookies-en-microsoft-edge-ver-permitir-bloquear-eliminar-y-usar-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener">Configurar cookies en Microsoft Edge</a></li>
</ul>
<h2>8. Cookies de terceros</h2>
<p>La aceptaciÃ³n, configuraciÃ³n y uso de cookies de terceros se rige por las polÃ­ticas propias de dichos proveedores. METRANSFERS GESTION SL no puede controlar en todo momento las actualizaciones que esos terceros realicen en sus polÃ­ticas, por lo que se recomienda al usuario revisar directamente sus condiciones cuando interactÃºe con herramientas externas integradas o enlazadas desde la web.</p>
<h2>9. InformaciÃ³n adicional y contacto</h2>
<p>Para obtener mÃ¡s informaciÃ³n sobre el tratamiento de datos personales, puede consultar nuestra <a href="' . home_url( '/politica-de-privacidad/' ) . '">PolÃ­tica de Privacidad</a>. Si necesita aclaraciones sobre el uso de cookies en este sitio web, puede escribir a <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p>
<p>La presente PolÃ­tica de Cookies podrÃ¡ actualizarse cuando se produzcan cambios normativos, tÃ©cnicos o funcionales en el sitio web. Se recomienda revisarla periÃ³dicamente.</p>',
        'terminos-y-condiciones' => '<h2>1. MARCO LEGAL APLICABLE</h2>
<p>El presente contrato se rige por lo dispuesto en la legislaciÃ³n espaÃ±ola vigente, especÃ­ficamente:</p>
<ul>
<li>Ley 16/1987, de 30 de julio, de OrdenaciÃ³n de los Transportes Terrestres (LOTT) y su Reglamento (ROTT).</li>
<li>Ley 34/2002 (LSSI-CE) sobre servicios de la sociedad de la informaciÃ³n.</li>
<li>Real Decreto Legislativo 1/2007, por el que se aprueba el texto refundido de la Ley General para la Defensa de los Consumidores y Usuarios.</li>
<li>Reglamento (UE) 2016/679 (RGPD) en materia de protecciÃ³n de datos.</li>
</ul>
<h2>2. IDENTIFICACIÃ“N DE LAS PARTES</h2>
<p><strong>El Prestador:</strong> METRANSFERS GESTION SL, con NIF B22522353 y domicilio social en AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÃ (BARCELONA).</p>
<p><strong>El Cliente:</strong> Persona fÃ­sica o jurÃ­dica que formaliza la reserva y garantiza tener capacidad legal para contratar.</p>
<h2>3. OBLIGACIÃ“N DE NOTIFICACIÃ“N Y REQUISITOS DEL SERVICIO</h2>
<p>Para garantizar la seguridad y legalidad del transporte, el Cliente tiene la obligaciÃ³n inexcusable de declarar las siguientes necesidades en el formulario de reserva:</p>
<h3>3.1. Sistemas de RetenciÃ³n Infantil (SRI)</h3>
<p>Conforme al ArtÃ­culo 117 del Reglamento General de CirculaciÃ³n, es obligatorio el uso de sillas homologadas para menores de estatura igual o inferior a 135 cm. El Cliente debe seleccionar el nÃºmero y tipo de sillas necesarias en el formulario. La omisiÃ³n de este dato facultarÃ¡ al conductor a denegar el servicio por razones de seguridad, sin derecho a reembolso.</p>
<h3>3.2. Equipaje Extraordinario</h3>
<p>La capacidad del vehÃ­culo estÃ¡ limitada por su ficha tÃ©cnica. El transporte de maletas adicionales, material deportivo (golf, esquÃ­) o bultos voluminosos debe ser notificado. EL PRESTADOR se reserva el derecho de cobrar suplementos o denegar el transporte si el volumen excede la capacidad del maletero del vehÃ­culo contratado.</p>
<h3>3.3. Transporte de Mascotas</h3>
<p>El transporte de animales domÃ©sticos estÃ¡ sujeto a notificaciÃ³n previa y debe realizarse en trasportines homologados proporcionados por el cliente, salvo acuerdo en contrario. Los perros guÃ­a viajarÃ¡n sin coste adicional conforme a la normativa vigente.</p>
<h2>4. PASARELA DE PAGO Y SEGURIDAD (REDSYS)</h2>
<p>El pago de los servicios se efectuarÃ¡ mediante tarjeta de crÃ©dito o dÃ©bito a travÃ©s de la pasarela de pago segura Redsys.</p>
<ul>
<li><strong>Seguridad:</strong> El sistema utiliza protocolos de encriptaciÃ³n SSL y autenticaciÃ³n 3D Secure (Verified by Visa / Mastercard ID Check).</li>
<li><strong>ConfirmaciÃ³n:</strong> El contrato se perfecciona en el momento en que EL PRESTADOR recibe la confirmaciÃ³n de la autorizaciÃ³n de pago por parte de la entidad bancaria.</li>
<li><strong>Fraude:</strong> EL PRESTADOR se reserva el derecho de anular cualquier transacciÃ³n ante sospechas de uso fraudulento de tarjetas.</li>
</ul>
<h2>5. DERECHO DE DESISTIMIENTO Y POLÃTICA DE CANCELACIÃ“N</h2>
<p>En virtud del ArtÃ­culo 103 l) del Real Decreto Legislativo 1/2007, el derecho de desistimiento no serÃ¡ aplicable a los servicios de transporte de pasajeros si el contrato prevÃ© una fecha o un periodo de ejecuciÃ³n especÃ­ficos. No obstante, EL PRESTADOR ofrece las siguientes condiciones comerciales:</p>
<ul>
<li><strong>CancelaciÃ³n con &gt;24 horas:</strong> DevoluciÃ³n del 100% del importe mediante el mismo sistema de pago (Redsys).</li>
<li><strong>CancelaciÃ³n con &lt;24 horas o No-Show:</strong> PenalizaciÃ³n del 100% del valor de la reserva.</li>
<li><strong>Retrasos de vuelos:</strong> EL PRESTADOR monitoriza los vuelos. No obstante, si el retraso excede los 90 minutos sobre la hora prevista, el servicio quedarÃ¡ sujeto a disponibilidad de flota, pudiendo incurrir en gastos de espera adicionales.</li>
</ul>
<h2>6. RESPONSABILIDAD LIMITADA</h2>
<p>EL PRESTADOR no serÃ¡ responsable por incumplimientos derivados de:</p>
<ul>
<li>Fuerza mayor o causas fortuitas (cortes de carretera, condiciones climÃ¡ticas adversas, huelgas generales).</li>
<li>Errores en los datos facilitados por el cliente en el formulario de reserva (ej. fecha errÃ³nea o nÃºmero de telÃ©fono incorrecto).</li>
<li>Incumplimiento de las normas de seguridad por parte de los pasajeros (uso de cinturÃ³n, comportamiento disruptivo).</li>
</ul>
<h2>7. JURISDICCIÃ“N Y LEY APLICABLE</h2>
<p>Para la resoluciÃ³n de cualquier litigio derivado de la interpretaciÃ³n o ejecuciÃ³n de este contrato, las partes se someten a la legislaciÃ³n espaÃ±ola. En caso de controversia, se recurrirÃ¡ a los Juzgados y Tribunales de Barcelona, salvo que el cliente ostente la condiciÃ³n de consumidor, en cuyo caso se atenderÃ¡ a la competencia territorial establecida por ley.</p>',
        'aviso-legal' => '<h2>1. INFORMACIÃ“N IDENTIFICATIVA</h2>
<p>En cumplimiento con el deber de informaciÃ³n recogido en el artÃ­culo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la InformaciÃ³n y del Comercio ElectrÃ³nico (LSSI-CE), a continuaciÃ³n se reflejan los siguientes datos:</p>
<p><strong>Titular del sitio web:</strong> METRANSFERS GESTION SL</p>
<p><strong>NIF:</strong> B22522353</p>
<p><strong>Domicilio Social:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÃ &ndash; (BARCELONA)</p>
<p><strong>Correo electrÃ³nico de contacto:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p>
<p><strong>Actividad:</strong> Transporte de viajeros y gestiÃ³n de servicios turÃ­sticos.</p>
<h2>2. CONDICIONES DE USO</h2>
<p>El acceso y/o uso de este portal atribuye la condiciÃ³n de USUARIO, que acepta, desde dicho acceso y/o uso, las Condiciones Generales de Uso aquÃ­ reflejadas.</p>
<p>El sitio web proporciona acceso a informaciones, servicios o datos (en adelante, &ldquo;los contenidos&rdquo;) en Internet pertenecientes a METRANSFERS GESTION SL. El USUARIO asume la responsabilidad del uso del portal. Dicha responsabilidad se extiende al registro que fuese necesario para acceder a determinados servicios o contenidos (como el formulario de reservas).</p>
<h2>3. PROPIEDAD INTELECTUAL E INDUSTRIAL</h2>
<p>METRANSFERS GESTION SL es titular de todos los derechos de propiedad intelectual e industrial de su pÃ¡gina web, asÃ­ como de los elementos contenidos en la misma (a tÃ­tulo enunciativo: imÃ¡genes, sonido, audio, vÃ­deo, software o textos; marcas o logotipos, combinaciones de colores, estructura y diseÃ±o, selecciÃ³n de materiales usados, programas de ordenador necesarios para su funcionamiento, acceso y uso, etc.).</p>
<p>En virtud de lo dispuesto en los artÃ­culos 8 y 32.1, pÃ¡rrafo segundo, de la Ley de Propiedad Intelectual, quedan expresamente prohibidas la reproducciÃ³n, la distribuciÃ³n y la comunicaciÃ³n pÃºblica, incluida su modalidad de puesta a disposiciÃ³n, de la totalidad o parte de los contenidos de esta pÃ¡gina web, con fines comerciales, en cualquier soporte y por cualquier medio tÃ©cnico, sin la autorizaciÃ³n de METRANSFERS GESTION SL.</p>
<h2>4. EXCLUSIÃ“N DE GARANTÃAS Y RESPONSABILIDAD</h2>
<p>EL PRESTADOR no se hace responsable, en ningÃºn caso, de los daÃ±os y perjuicios de cualquier naturaleza que pudieran ocasionar, a tÃ­tulo enunciativo: errores u omisiones en los contenidos, falta de disponibilidad del portal o la transmisiÃ³n de virus o programas maliciosos o lesivos en los contenidos, a pesar de haber adoptado todas las medidas tecnolÃ³gicas necesarias para evitarlo.</p>
<h2>5. MODIFICACIONES</h2>
<p>METRANSFERS GESTION SL se reserva el derecho de efectuar sin previo aviso las modificaciones que considere oportunas en su portal, pudiendo cambiar, suprimir o aÃ±adir tanto los contenidos y servicios que se presten a travÃ©s de la misma como la forma en la que Ã©stos aparezcan presentados o localizados en su portal.</p>
<h2>6. ENLACES (LINKS)</h2>
<p>En el caso de que en el sitio web se dispusiesen enlaces o hipervÃ­nculos hacia otros sitios de Internet, METRANSFERS GESTION SL no ejercerÃ¡ ningÃºn tipo de control sobre dichos sitios y contenidos. En ningÃºn caso asumirÃ¡ responsabilidad alguna por los contenidos de algÃºn enlace perteneciente a un sitio web ajeno.</p>
<h2>7. DERECHO DE EXCLUSIÃ“N</h2>
<p>METRANSFERS GESTION SL se reserva el derecho a denegar o retirar el acceso al portal y/o los servicios ofrecidos sin necesidad de preaviso, a instancia propia o de un tercero, a aquellos usuarios que incumplan las presentes Condiciones Generales de Uso.</p>
<h2>8. PROTECCIÃ“N DE DATOS</h2>
<p>Todo lo relativo a la polÃ­tica de protecciÃ³n de datos se encuentra recogido en el documento de PolÃ­tica de Privacidad de la entidad, conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley OrgÃ¡nica 3/2018 (LOPDGDD).</p>
<h2>9. LEGISLACIÃ“N APLICABLE Y JURISDICCIÃ“N</h2>
<p>La relaciÃ³n entre METRANSFERS GESTION SL y el USUARIO se regirÃ¡ por la normativa espaÃ±ola vigente y cualquier controversia se someterÃ¡ a los Juzgados y Tribunales de la ciudad de Barcelona.</p>'
    );

    foreach ( $pages as $slug => $content ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $page && empty( trim( $page->post_content ) ) ) {
            wp_update_post( array(
                'ID' => $page->ID,
                'post_content' => $content
            ) );
        }
    }

    update_option( 'me_transfers_content_migrated_legal', true );
}
if ( defined( 'ME_TRANSFERS_ENABLE_MIGRATIONS' ) && ME_TRANSFERS_ENABLE_MIGRATIONS ) {
	add_action( 'admin_init', 'me_transfers_migrate_legal_to_editor' );
}


/**
 * Migration v4 â€” Populate post_content from PHP catalog so pages are editable
 * in the WordPress block editor.
 *
 * This runs automatically once when any admin page loads.
 * It only updates pages whose post_content is still empty.
 * Protected with: option flag + transient lock (anti-race-condition).
 *
 * Version key: me_transfers_editor_populated_v4
 * To force a re-run: delete that option from the DB.
 */
function me_transfers_populate_editor_content_v4(): void {
    if ( get_option( 'me_transfers_editor_populated_v4' ) ) {
        return;
    }
    if ( get_transient( 'me_transfers_populating_content_v4' ) ) {
        return;
    }
    set_transient( 'me_transfers_populating_content_v4', true, MINUTE_IN_SECONDS * 5 );

    // â”€â”€ 1. DESTINOS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    $destinations = me_transfers_get_destination_catalog();
    foreach ( $destinations as $dest ) {
        // Search by child slug (/destinos/slug/) first, then direct slug.
        $page = get_page_by_path( 'destinos/' . $dest['slug'], OBJECT, 'page' );
        if ( ! $page ) {
            $page = get_page_by_path( $dest['slug'], OBJECT, 'page' );
        }
        if ( ! $page || ! empty( trim( $page->post_content ) ) ) {
            continue; // Skip: not found or already has content.
        }

        $content  = '<p>' . esc_html( $dest['travel_note'] ) . '</p>' . "\n\n";
        $content .= '<p>' . esc_html(
            sprintf(
                'Si estÃ¡s organizando un traslado hacia %s, podemos prepararte una propuesta adaptada al punto de recogida, nÃºmero de pasajeros, fecha estimada y tipo de servicio que necesites.',
                $dest['title']
            )
        ) . '</p>' . "\n\n";
        $content .= '<ul>' . "\n";
        foreach ( $dest['highlights'] as $highlight ) {
            $content .= '<li>' . esc_html( $highlight ) . '</li>' . "\n";
        }
        $content .= '</ul>';

        wp_update_post( array(
            'ID'           => $page->ID,
            'post_content' => $content,
        ) );
    }

    // â”€â”€ 2. TOURS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    $tours = me_transfers_get_tour_catalog();
    foreach ( $tours as $slug => $tour ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page || ! empty( trim( $page->post_content ) ) ) {
            continue;
        }

        $paragraphs = ! empty( $tour['full_desc'] )
            ? explode( "\n\n", $tour['full_desc'] )
            : array( $tour['desc'] );

        $content = '';
        foreach ( $paragraphs as $p ) {
            $p = trim( $p );
            if ( $p ) {
                $content .= '<p>' . esc_html( $p ) . '</p>' . "\n\n";
            }
        }

        // Itinerary list.
        if ( ! empty( $tour['itinerary'] ) ) {
            $content .= '<h3>Itinerario del tour</h3>' . "\n<ul>\n";
            foreach ( $tour['itinerary'] as $step ) {
                $content .= '<li>' . esc_html( $step ) . '</li>' . "\n";
            }
            $content .= '</ul>' . "\n\n";
        }

        // Inclusions list.
        if ( ! empty( $tour['includes'] ) ) {
            $content .= '<h3>El tour incluye</h3>' . "\n<ul>\n";
            foreach ( $tour['includes'] as $item ) {
                $content .= '<li>' . esc_html( $item ) . '</li>' . "\n";
            }
            $content .= '</ul>';
        }

        wp_update_post( array(
            'ID'           => $page->ID,
            'post_content' => $content,
        ) );
    }

    // â”€â”€ 3. SERVICIOS PRINCIPALES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    $services = me_transfers_get_service_catalog();
    foreach ( $services as $slug => $service ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page || ! empty( trim( $page->post_content ) ) ) {
            continue;
        }

        $content  = '<p>' . esc_html( $service['hero_desc'] ) . '</p>' . "\n\n";
        if ( ! empty( $service['desc_long'] ) ) {
            $paragraphs = explode( "\n\n", $service['desc_long'] );
            foreach ( $paragraphs as $p ) {
                $p = trim( $p );
                if ( $p ) {
                    $content .= '<p>' . esc_html( $p ) . '</p>' . "\n\n";
                }
            }
        }
        // Features list.
        if ( ! empty( $service['features'] ) ) {
            $content .= '<h3>CaracterÃ­sticas del servicio</h3>' . "\n<ul>\n";
            foreach ( $service['features'] as $feature ) {
                $label = is_array( $feature ) ? ( $feature['label'] ?? '' ) : $feature;
                if ( $label ) {
                    $content .= '<li>' . esc_html( $label ) . '</li>' . "\n";
                }
            }
            $content .= '</ul>';
        }

        wp_update_post( array(
            'ID'           => $page->ID,
            'post_content' => $content,
        ) );
    }

    update_option( 'me_transfers_editor_populated_v4', true );
    delete_transient( 'me_transfers_populating_content_v4' );
}
// [MIGRACIÃ“N MANUAL â€” no conectar en producciÃ³n] Descomentar y ejecutar una sola vez si se necesita repoblar contenido:
// add_action( 'admin_init', 'me_transfers_populate_editor_content_v4' );




// 1. OptimizaciÃ³n WPO: Forzar WebP como formato de salida y forzar lazy load
add_filter( 'image_editor_output_format', function( $formats ) {
    $formats['image/jpeg'] = 'image/webp';
    $formats['image/png']  = 'image/webp';
    return $formats;
});

add_filter( 'wp_get_attachment_image_attributes', function( $attr, $attachment, $size ) {
    if ( ! isset( $attr['loading'] ) ) {
        $attr['loading'] = 'lazy';
    }
    return $attr;
}, 10, 3 );

// 2. Metadatos home: propietario Ãºnico â†’ filtros wpseo_title / wpseo_metadesc del bloque
// Â«YOAST SEO: OptimizaciÃ³n de CTRÂ» (ver mÃ¡s abajo). El filtro pre_get_document_title y el
// bloque if(WPSEO_VERSION) se eliminaron para evitar conflictos y tÃ­tulos duplicados.

add_action( 'wp_head', function() {
    // Solo emitir meta description si Yoast SEO NO estÃ¡ activo (evita etiqueta duplicada).
    if ( ( is_front_page() || is_home() ) && ! defined( 'WPSEO_VERSION' ) && ! function_exists( 'the_seo_framework' ) ) {
        echo '<meta name="description" content="Reserva tu transfer privado desde o hacia el Aeropuerto de Barcelona, centro, hotel o puerto. ChÃ³fer profesional, precio cerrado y atenciÃ³n personalizada 24/7.">' . "\n";
    }
    // NOTA: La protecciÃ³n noindex de staging se gestiona exclusivamente vÃ­a filtro wp_robots.
    // No emitir <meta robots> aquÃ­ para evitar doble directiva conflictiva.
}, 1 );

// 3. Motor de Redirecciones 301 y 410 (SEO URL Recovery)
add_action( 'template_redirect', 'me_transfers_custom_redirects', 1 );
function me_transfers_custom_redirects() {
    if ( ! is_admin() ) {
        $path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
        $path = trailingslashit( '/' . trim( $path, '/' ) );

        // -----------------------------------------------------------------
        // 301 â€” URL antigua tiene sustituto semÃ¡nticamente equivalente.
        // Se ejecuta ANTES de comprobar is_404() para interceptar URLs que aÃºn devuelven 200
        // -----------------------------------------------------------------
        $redirects_301 = array(
            // El redirect de Tax Free se ha retirado temporalmente
            // porque en producciÃ³n entraba en conflicto semÃ¡ntico.

            // Antiguas landings /taxis-* y /traslados-*
            '/transporte-en-barcelona-para-grupos-grandes-y-equipaje-extra-la-solucion-mercedes-clase-v/' => '/grupos/',
            '/taxis-privado-barcelona/'                                                   => '/traslados-privados/',
            '/taxis-barcelona-port-aventura/'                                             => '/rutas/barcelona-portaventura/',
            '/taxis-barcelona-salou/'                                                     => '/rutas/barcelona-salou/',
            '/taxis-barcelona-costa-brava/'                                               => '/destinos/costa-brava/',
            '/taxis-barcelona-girona/'                                                    => '/rutas/barcelona-girona/',
            '/taxis-barcelona-tossa-de-mar/'                                              => '/rutas/barcelona-tossa-de-mar/',
            '/traslados-barcelona-tossa-de-mar/'                                          => '/rutas/barcelona-tossa-de-mar/',
            '/traslados-barcelona-andorra/'                                               => '/rutas/barcelona-andorra/',
            '/taxis-barcelona-andorra/'                                                   => '/rutas/barcelona-andorra/',
            '/taxis-barcelona-cadaques/'                                                  => '/rutas/barcelona-cadaques/',
            '/traslados-barcelona-cadaques/'                                              => '/rutas/barcelona-cadaques/',

            // Antiguas URLs WooCommerce con sustituto equivalente
            '/tienda-barcelona-tours-transfers/transfers/traslado-a-andorra/'             => '/rutas/barcelona-andorra/',
            '/tienda-barcelona-tours-transfers/transfers/transfer-privado-portaventura/'  => '/rutas/barcelona-portaventura/',
            '/tienda-barcelona-tours-transfers/transfers/transfer-privado-a-portaventura/'=> '/rutas/barcelona-portaventura/',
            '/tienda-barcelona-tours-transfers/transfers/transfer-privado-salou/'         => '/rutas/barcelona-salou/',
            '/tienda-barcelona-tours-transfers/transfers/transfer-privado-girona/'        => '/rutas/barcelona-girona/',
            '/tienda-barcelona-tours-transfers/transfers/'                                => '/rutas/',
            '/tienda-barcelona-tours-transfers/'                                          => '/',
        );

        if ( isset( $redirects_301[ $path ] ) ) {
            wp_safe_redirect( home_url( $redirects_301[ $path ] ), 301 );
            exit;
        }

        // -----------------------------------------------------------------
        // 410 PatrÃ³n wildcard: URL WooCommerce sin sustituto equivalente
        // Se ejecuta ANTES de comprobar is_404() para evitar soft 404
        // -----------------------------------------------------------------
        if ( str_starts_with( $path, '/tienda-barcelona-tours-transfers/' ) ) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 410 );
            nocache_headers();
            return;
        }

        // -----------------------------------------------------------------
        // 410 â€” Contenido eliminado sin sustituto directo.
        // Google lo procesa mÃ¡s rÃ¡pido que un 404 para limpiar el Ã­ndice.
        // Se ejecuta ANTES de comprobar is_404()
        // -----------------------------------------------------------------
        $gone_urls = array(
            '/taxis-barcelona-taull/',
            '/taxis-barcelona-vielha/',
            '/taxis-barcelona-besalu/',
            '/taxis-barcelona-bagur/',
            '/taxis-barcelona-delta-del-ebro/',
            '/taxis-barcelona-peniscola/',
            '/taxis-barcelona-morella/',
            '/taxis-barcelona-altea/',
            '/taxis-barcelona-valderrobres/',
            '/taxis-barcelona-alquezar/',
            '/taxis-barcelona-colliure/',
            '/taxis-barcelona-carcasona/',
            '/traslados-barcelona-taull/',
            '/traslados-barcelona-vielha/',
            '/traslados-barcelona-besalu/',
            '/traslados-barcelona-bagur/',
            '/traslados-barcelona-delta-del-ebro/',
            '/traslados-barcelona-peniscola/',
            '/traslados-barcelona-morella/',
            '/traslados-barcelona-altea/',
            '/traslados-barcelona-valderrobres/',
            '/traslados-barcelona-alquezar/',
            '/traslados-barcelona-colliure/',
            '/traslados-barcelona-carcasona/',
        );

        if ( in_array( $path, $gone_urls, true ) ) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 410 );
            nocache_headers();
            return;
        }

        // NOTA: El smart redirect automÃ¡tico fue eliminado para evitar 301 incorrectos.
        // Para recuperar una URL especÃ­fica, aÃ±ade la redirecciÃ³n manual en $redirects_301.
    }
}

// ==========================================
// 1. Theme Support & Setup
// ==========================================
require_once get_template_directory() . '/mt-seo-importer.php';
// No ejecutar automÃ¡ticamente.
// add_action( 'admin_init', 'mt_run_seo_importer_phase_1' );

// Herramienta nativa para construir y publicar rutas de la Fase 1
require_once get_template_directory() . '/includes/admin-route-builder.php';

// ==========================================
// 4. AJAX Handlers for Leads (Form & WhatsApp)
// ==========================================
add_action( 'wp_ajax_mt_save_lead', 'mt_ajax_save_lead' );
add_action( 'wp_ajax_nopriv_mt_save_lead', 'mt_ajax_save_lead' );

// ELIMINADO: El bloque AUTO-CREAR PÃGINAS ESENCIALES re-publicaba /contacto y /reservaciones
// en cada carga de WordPress, incluso si se habÃ­an dejado como borrador o papelera intencionalmente.
// Las pÃ¡ginas esenciales deben gestionarse manualmente desde el panel de WordPress.
// Si las pÃ¡ginas no existen, crÃ©alas una vez desde PÃ¡ginas > AÃ±adir nueva.

function mt_ajax_save_lead() {
    check_ajax_referer( 'mt_lead_nonce', 'security' );

    $origen   = isset( $_POST['origen'] )   ? sanitize_text_field( $_POST['origen'] )      : 'formulario';
    $nombre   = isset( $_POST['nombre'] )   ? sanitize_text_field( $_POST['nombre'] )      : '';
    $email    = isset( $_POST['email'] )    ? sanitize_email( $_POST['email'] )            : '';
    $telefono = isset( $_POST['telefono'] ) ? sanitize_text_field( $_POST['telefono'] )    : '';
    $servicio = isset( $_POST['servicio'] ) ? sanitize_text_field( $_POST['servicio'] )    : '';
    $mensaje  = isset( $_POST['mensaje'] )  ? sanitize_textarea_field( $_POST['mensaje'] ) : '';
    $gdpr     = isset( $_POST['gdpr_aceptado'] ) && '1' === $_POST['gdpr_aceptado'] ? '1' : '0';

    // Fecha del servidor â€” no confiar en el reloj del navegador
    $gdpr_fecha_servidor = current_time( 'c' );
    // VersiÃ³n de la polÃ­tica activa (incrementar manualmente al actualizar la polÃ­tica)
    $gdpr_version = '2025-01-01';

    // ValidaciÃ³n: nombre obligatorio
    if ( empty( trim( $nombre ) ) ) {
        wp_send_json_error( array( 'message' => 'El nombre es obligatorio.' ) );
        return;
    }

    // ValidaciÃ³n: email o telÃ©fono segÃºn el origen
    if ( $origen === 'whatsapp' ) {
        if ( empty( trim( $telefono ) ) ) {
            wp_send_json_error( array( 'message' => 'El telÃ©fono es obligatorio.' ) );
            return;
        }
    } else {
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Introduce un correo electrÃ³nico vÃ¡lido.' ) );
            return;
        }
    }

    // ValidaciÃ³n: longitudes mÃ¡ximas para prevenir abuso
    if ( mb_strlen( $nombre ) > 120 || mb_strlen( $mensaje ) > 3000 || mb_strlen( $telefono ) > 30 ) {
        wp_send_json_error( array( 'message' => 'AlgÃºn campo supera la longitud permitida.' ) );
        return;
    }

    // ValidaciÃ³n: consentimiento GDPR obligatorio
    if ( '1' !== $gdpr ) {
        wp_send_json_error( array( 'message' => 'Debes aceptar la polÃ­tica de privacidad.' ) );
        return;
    }

    $title = $nombre . ' - ' . date_i18n( 'd/m/Y H:i' );

    $post_data = array(
        'post_title'  => $title,
        'post_type'   => 'mensaje',
        'post_status' => 'private', // Privado: no accesible pÃºblicamente, visible sÃ³lo para admins
    );

    $post_id = wp_insert_post( $post_data );

    if ( $post_id && ! is_wp_error( $post_id ) ) {
        update_post_meta( $post_id, '_mt_mensaje_origen',   $origen );
        update_post_meta( $post_id, '_mt_mensaje_nombre',   $nombre );
        update_post_meta( $post_id, '_mt_mensaje_email',    $email );
        update_post_meta( $post_id, '_mt_mensaje_telefono', $telefono );
        update_post_meta( $post_id, '_mt_mensaje_servicio',  $servicio );
        update_post_meta( $post_id, '_mt_mensaje_texto',     $mensaje );
        update_post_meta( $post_id, '_mt_gdpr_aceptado',     '1' );
        update_post_meta( $post_id, '_mt_gdpr_fecha',        $gdpr_fecha_servidor ); // Fecha del servidor
        update_post_meta( $post_id, '_mt_gdpr_version',      $gdpr_version );

        // Enviar notificaciÃ³n por email
        $to      = get_option( 'admin_email', 'info@metransfers.es' );
        $subject = 'Nueva consulta web: ' . $nombre;

        $body  = "Nueva consulta desde la web de MeTransfers.\n\n";
        $body .= "Nombre: {$nombre}\n";
        $body .= "Email: {$email}\n";
        $body .= "TelÃ©fono: {$telefono}\n";
        $body .= "Servicio: {$servicio}\n";
        $body .= "Origen: {$origen}\n\n";
        $body .= "Mensaje:\n{$mensaje}\n\n";
        $body .= "GDPR: Aceptado (servidor: {$gdpr_fecha_servidor}, polÃ­tica v{$gdpr_version})\n";
        $body .= "Gestionar: " . admin_url( 'edit.php?post_type=mensaje' ) . "\n";

        $headers    = array( 'Reply-To: ' . $nombre . ' <' . $email . '>' );
        $mail_sent  = wp_mail( $to, $subject, $body, $headers );

        // Registrar si el correo fallÃ³ (sin bloquear la respuesta al cliente)
        if ( ! $mail_sent ) {
            update_post_meta( $post_id, '_mt_email_fallido', '1' );
        }

        wp_send_json_success( array( 'message' => 'Â¡Solicitud recibida correctamente! Te responderemos muy pronto.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Error al guardar la solicitud. Por favor, intÃ©ntalo de nuevo.' ) );
    }
}

// ==========================================
// 5. Force-assign template-servicio.php to all service pages
//    and create missing pages (chofer-por-horas, grupos, etc.)
// ==========================================
// DESACTIVADO: mt_ensure_service_pages_and_templates() creaba y publicaba pÃ¡ginas de servicios
// automÃ¡ticamente cada 24h, sin revisiÃ³n editorial. Las pÃ¡ginas de servicios deben crearse
// manualmente desde el panel de WordPress con contenido propio.
// add_action( 'admin_init', 'mt_ensure_service_pages_and_templates' );

function mt_ensure_service_pages_and_templates() {
    // Only run once per day to avoid overhead.
    if ( get_transient( 'mt_service_pages_synced' ) ) {
        return;
    }

    if ( ! function_exists( 'me_transfers_get_service_catalog' ) ) {
        return;
    }

    $catalog       = me_transfers_get_service_catalog();
    $template_slug = 'template-servicio.php';

    foreach ( $catalog as $slug => $service ) {
        $page    = get_page_by_path( $slug );
        $trashed = get_page_by_path( $slug . '__trashed' );

        if ( ! $page && ! $trashed ) {
            // Create the page if it doesn't exist.
            $page_id = wp_insert_post( array(
                'post_title'     => $service['title'],
                'post_name'      => $slug,
                'post_content'   => '',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'ping_status'    => 'closed',
                'comment_status' => 'closed',
            ) );
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_post_meta( $page_id, '_wp_page_template', $template_slug );
            }
        } elseif ( $page ) {
            // Ensure the correct template is assigned.
            $current = get_post_meta( $page->ID, '_wp_page_template', true );
            if ( $current !== $template_slug ) {
                update_post_meta( $page->ID, '_wp_page_template', $template_slug );
            }
        }
    }

	// Cache for 24 hours.
	set_transient( 'mt_service_pages_synced', true, DAY_IN_SECONDS );
}

// DESACTIVADO: mt_ensure_seo_pages() generaba automÃ¡ticamente 35 pÃ¡ginas /taxis-* y /traslados-*
// que competÃ­an directamente con el CPT /rutas/* y /destinos/*.
// Las URLs antiguas se gestionan mediante redirecciones 301 (ver me_transfers_custom_redirects).
// add_action( 'admin_init', 'mt_ensure_seo_pages' );
function mt_ensure_seo_pages() {
    $seo_pages = array(
        array(
            'slug'  => 'taxis-privado-barcelona',
            'title' => 'MeTransfers Barcelona - Taxis Privado Barcelona',
            'template' => 'page-taxis-privado-barcelona.php'
        ),
        array(
            'slug'  => 'taxis-barcelona-port-aventura',
            'title' => 'MeTransfers Barcelona - Taxis Barcelona a Port Aventura',
            'template' => 'page-taxis-barcelona-port-aventura.php'
        ),
        array(
            'slug'  => 'taxis-barcelona-salou',
            'title' => 'MeTransfers Barcelona - Taxis Barcelona a Salou',
            'template' => 'page-taxis-barcelona-salou.php'
        ),
        array(
            'slug'  => 'taxis-barcelona-costa-brava',
            'title' => 'MeTransfers Barcelona - Taxis Barcelona a Costa Brava',
            'template' => 'page-taxis-barcelona-costa-brava.php'
        ),
        array(
            'slug'  => 'taxis-barcelona-girona',
            'title' => 'MeTransfers Barcelona - Taxis Barcelona a Girona',
            'template' => 'page-taxis-barcelona-girona.php'
        )
    );
    // GeneraciÃ³n dinÃ¡mica de 30 landings (15 taxis, 15 traslados)
    $destinos_dinamicos = array(
        'Andorra',
        'TaÃ¼ll',
        'Vielha',
        'Tossa de Mar',
        'CadaquÃ©s',
        'BesalÃº',
        'Bagur',
        'Delta del Ebro',
        'PeÃ±Ã­scola',
        'Morella',
        'Altea',
        'Valderrobres',
        'AlquÃ©zar',
        'Colliure',
        'Carcasona'
    );
    
    foreach( $destinos_dinamicos as $destino ) {
        $slug_base = sanitize_title( $destino );
        
        // 1. Taxis
        $seo_pages[] = array(
            'slug'     => 'taxis-barcelona-' . $slug_base,
            'title'    => 'MeTransfers Barcelona - Taxis Barcelona a ' . $destino,
            'template' => 'page-seo-dynamic.php',
            'meta'     => array( '_seo_destino' => $destino, '_seo_tipo' => 'Taxis' )
        );
        
        // 2. Traslados
        $seo_pages[] = array(
            'slug'     => 'traslados-barcelona-' . $slug_base,
            'title'    => 'MeTransfers Barcelona - Traslado privado a ' . $destino . ' desde Barcelona',
            'template' => 'page-seo-dynamic.php',
            'meta'     => array( '_seo_destino' => $destino, '_seo_tipo' => 'Traslados' )
        );
    }

    foreach ( $seo_pages as $seo_page ) {
        $slug = $seo_page['slug'];
        $template_slug = $seo_page['template'];
        
        $page = get_page_by_path( $slug );
        $trashed = get_page_by_path( $slug . '__trashed' );

        if ( ! $page && ! $trashed ) {
            $page_id = wp_insert_post( array(
                'post_title'     => $seo_page['title'],
                'post_name'      => $slug,
                'post_content'   => '',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'ping_status'    => 'closed',
                'comment_status' => 'closed',
            ) );
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_post_meta( $page_id, '_wp_page_template', $template_slug );
                if ( isset( $seo_page['meta'] ) ) {
                    foreach ( $seo_page['meta'] as $meta_key => $meta_value ) {
                        update_post_meta( $page_id, $meta_key, $meta_value );
                    }
                }
            }
        } elseif ( $page ) {
            $current = get_post_meta( $page->ID, '_wp_page_template', true );
            if ( $current !== $template_slug ) {
                update_post_meta( $page->ID, '_wp_page_template', $template_slug );
            }
            if ( isset( $seo_page['meta'] ) ) {
                foreach ( $seo_page['meta'] as $meta_key => $meta_value ) {
                    update_post_meta( $page->ID, $meta_key, $meta_value );
                }
            }
        }
    }
}

/**
 * SEO Title fallback cuando Yoast NO estÃ¡ activo.
 */
if ( ! defined( 'WPSEO_VERSION' ) ) {
    add_filter( 'document_title_parts', function( $title ) {

        if ( isset( $title['site'] ) ) {
            $title['site'] = str_replace(
                'Me Transfers',
                'MeTransfers',
                $title['site']
            );
        }

        if ( isset( $title['title'] ) ) {
            $title['title'] = str_replace(
                'Me Transfers',
                'MeTransfers',
                $title['title']
            );
        }

        if ( is_singular( 'ruta' ) ) {
            $post_id = get_the_ID();
            $origen  = get_post_meta( $post_id, '_mt_ruta_origen', true );
            $destino = get_post_meta( $post_id, '_mt_ruta_destino', true );

            if ( $origen && $destino ) {
                $title['title'] = sprintf(
                    'Transfer privado %sâ€“%s',
                    $origen,
                    $destino
                );
            }

            $title['site'] = 'MeTransfers';
        }

        return $title;
    }, 99 );
}

add_filter( 'wpseo_title', function( $title ) {

    if ( is_front_page() || is_home() ) {
        return 'Transfer Aeropuerto Barcelona y Traslados Privados | MeTransfers';
    }

    return $title;

} );

add_filter( 'option_blogname', function( $name ) {
	return str_replace( 'Me Transfers', 'MeTransfers', $name );
} );

/**
 * Inject SEO 10/10 Structured Data
 */
add_action( 'wp_head', function() {
	$schema = array();

	// 1. Organization (Solo en la portada si Yoast no estÃ¡ activo)
	if ( is_front_page() ) {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			$schema[] = array(
				'@context' => 'https://schema.org',
				'@type' => 'Organization',
				'@id' => home_url( '/#organization' ),
				'name' => 'MeTransfers',
				'legalName' => 'METRANSFERS GESTION SL',
				'url' => home_url( '/' ),
				'logo' => array(
					'@type' => 'ImageObject',
					'url' => get_template_directory_uri() . '/assets/img/logo-dark.svg',
				),
				'telephone' => '+34662024136',
				'email' => 'info@metransfers.es',
				'contactPoint' => array(
					'@type' => 'ContactPoint',
					'telephone' => '+34662024136',
					'contactType' => 'customer service',
					'availableLanguage' => array( 'es', 'en' ),
				),
				'areaServed' => array( 'Barcelona', 'CataluÃ±a', 'EspaÃ±a', 'Andorra' ),
			);
		}

		// Preload LCP image for front page
		echo '<link rel="preload" as="image" href="https://metransfers.es/wp-content/uploads/2026/07/airport-transfer-me-tranfers-me-tranfers-barcelona-espana.webp" fetchpriority="high">' . "\n";
	}

	// 2. Breadcrumbs & Service (PÃ¡ginas de servicio, tours, destinos, rutas)
	if ( ( is_page() && ! is_front_page() ) || is_singular( 'ruta' ) ) {
		$current_post = get_post();
		$breadcrumbs = array(
			array(
				'@type' => 'ListItem',
				'position' => 1,
				'name' => 'Inicio',
				'item' => home_url( '/' ),
			),
		);

		if ( is_singular( 'ruta' ) ) {
			// Service Schema para Ruta
			$origen  = get_post_meta( get_the_ID(), '_mt_ruta_origen', true );
			$destino = get_post_meta( get_the_ID(), '_mt_ruta_destino', true );
			$area_served = array();
			if ( $origen ) $area_served[] = array( '@type' => 'City', 'name' => $origen );
			if ( $destino ) $area_served[] = array( '@type' => 'City', 'name' => $destino );
			if ( empty( $area_served ) ) {
				$area_served = array( '@type' => 'City', 'name' => 'Barcelona' );
			}

			$schema[] = array(
				'@context' => 'https://schema.org',
				'@type' => 'Service',
				'@id' => get_permalink() . '#service',
				'name' => get_the_title(),
				'serviceType' => 'Traslado privado con chÃ³fer',
				'provider' => array( '@id' => home_url( '/#organization' ) ),
				'areaServed' => $area_served,
				'url' => get_permalink(),
			);

			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 2,
				'name' => 'Rutas',
				'item' => get_post_type_archive_link( 'ruta' ) ?: home_url( '/rutas/' ),
			);
			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 3,
				'name' => get_the_title(),
				'item' => get_permalink(),
			);
		} elseif ( $service = me_transfers_get_current_service( $current_post ) ) {
			// Service Schema
			$schema[] = array(
				'@context' => 'https://schema.org',
				'@type' => 'Service',
				'@id' => get_permalink() . '#service',
				'name' => get_the_title(),
				'serviceType' => 'Traslado privado con chÃ³fer',
				'provider' => array( '@id' => home_url( '/#organization' ) ),
				'areaServed' => array( '@type' => 'City', 'name' => 'Barcelona' ),
				'url' => get_permalink(),
			);
			
			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 2,
				'name' => 'Servicios',
				'item' => home_url( '/#servicios' ),
			);
			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 3,
				'name' => get_the_title(),
				'item' => get_permalink(),
			);
		} elseif ( $tour = me_transfers_get_current_tour( $current_post ) ) {
			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 2,
				'name' => 'Tours',
				'item' => me_transfers_get_section_url( 'tours' ),
			);
			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 3,
				'name' => get_the_title(),
				'item' => get_permalink(),
			);
		} elseif ( $destination = me_transfers_get_current_destination( $current_post ) ) {
			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 2,
				'name' => 'Destinos',
				'item' => me_transfers_get_destinations_hub_url(),
			);
			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 3,
				'name' => get_the_title(),
				'item' => get_permalink(),
			);
		} else {
			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 2,
				'name' => get_the_title(),
				'item' => get_permalink(),
			);
		}

		// BreadcrumbList: solo si Yoast NO estÃ¡ activo (evita JSON-LD duplicado).
		// Yoast genera su propio BreadcrumbList cuando estÃ¡ habilitado.
		if ( count( $breadcrumbs ) > 1 && ! defined( 'WPSEO_VERSION' ) ) {
			$schema[] = array(
				'@context' => 'https://schema.org',
				'@type' => 'BreadcrumbList',
				'itemListElement' => $breadcrumbs,
			);
		}
	}

	if ( ! empty( $schema ) ) {
		foreach ( $schema as $s ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		}
	}
} );

// ==========================================
// ROBOTS: Controlar indexaciÃ³n por entorno
// En wp-config.php staging: define('WP_ENVIRONMENT_TYPE','staging');
// En wp-config.php producciÃ³n: define('WP_ENVIRONMENT_TYPE','production');
// ==========================================
add_filter( 'wp_robots', static function ( array $robots ): array {
	$prod_hosts = [ 'metransfers.es', 'www.metransfers.es' ];
	$current_host = wp_parse_url( home_url(), PHP_URL_HOST );
	
	// 1. Staging / Environment check
	if ( ! in_array( $current_host, $prod_hosts, true ) || ( function_exists( 'wp_get_environment_type' ) && wp_get_environment_type() !== 'production' ) ) {
		return array_merge( $robots, [ 'noindex' => true, 'nofollow' => true, 'noarchive' => true ] );
	}
	
	// 2. Archivos contaminantes (tags, search, author, date, attachment)
	if ( is_tag() || is_search() || is_author() || is_date() || is_attachment() ) {
		return array_merge( $robots, [ 'noindex' => true, 'follow' => true ] );
	}
	
	// 3. InternacionalizaciÃ³n incompleta (todo lo que no sea 'es')
	if (
	    function_exists( 'mt_lang' )
	    && defined( 'MT_SEO_LANGS' )
	    && ! in_array( mt_lang(), MT_SEO_LANGS, true )
	) {
		return array_merge( $robots, [ 'noindex' => true, 'follow' => true ] );
	}

	// 4. Umbral de calidad para rutas
	if ( is_singular( 'ruta' ) ) {
		// _mt_seo_ready=1 â†’ indexar. Si no estÃ¡ a 1, no indexar. Es el Ãºnico control de calidad.
		$seo_ready = get_post_meta( get_the_ID(), '_mt_seo_ready', true );
		if ( '1' !== $seo_ready ) {
			return array_merge( $robots, [ 'noindex' => true, 'follow' => true ] );
		}
	}

	// 5. Destinos genÃ©ricos (sin contenido diferenciado) â†’ noindex temporal.
	// Solo se indexan destinos con contenido curado especÃ­fico (salou, lloret-de-mar).
	// Ampliar la lista $specific_destinations cuando un destino tenga contenido real Ãºnico.
	if ( is_page() && ! is_front_page() ) {
		$destination = me_transfers_get_current_destination( get_post() );
		if ( $destination ) {
			$specific_destinations = [ 'salou', 'lloret-de-mar' ];
			if ( ! in_array( $destination['slug'], $specific_destinations, true ) ) {
				return array_merge( $robots, [ 'noindex' => true, 'follow' => true ] );
			}
		}
	}

	return $robots;
}, 99 );

// ==========================================
// YOAST SEO: Excluir rutas de baja calidad y destinos genÃ©ricos del sitemap
// ==========================================
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function( $excluded ) {
	// 1. Excluir rutas que no estÃ¡n listas para SEO
	$args = array(
		'post_type'      => 'ruta',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => '_mt_seo_ready',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_mt_seo_ready',
				'value'   => '1',
				'compare' => '!=',
			),
		),
	);
	$poor_routes = get_posts( $args );
	
	if ( ! empty( $poor_routes ) ) {
		$excluded = array_merge( $excluded, $poor_routes );
	}

	// 2. Excluir destinos genÃ©ricos
	$args_pages = array(
		'post_type'      => 'page',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);
	$pages = get_posts( $args_pages );
	
	$specific_destinations = [ 'salou', 'lloret-de-mar' ];
	$generic_destination_ids = array();
	
	foreach ( $pages as $page_id ) {
		$destination = me_transfers_get_current_destination( $page_id );
		if ( $destination && ! in_array( $destination['slug'], $specific_destinations, true ) ) {
			$generic_destination_ids[] = $page_id;
		}
	}

	if ( ! empty( $generic_destination_ids ) ) {
		$excluded = array_merge( $excluded, $generic_destination_ids );
	}
	
	return $excluded;
} );

// Excluir taxonomÃ­as y tipos de contenido irrelevantes del Sitemap
add_filter( 'wpseo_sitemap_exclude_taxonomy', function( $exclude, $taxonomy ) {
	// Excluir etiquetas (tags) y categorÃ­as vacÃ­as si las hay
	if ( $taxonomy === 'post_tag' ) {
		return true;
	}
	return $exclude;
}, 10, 2 );

add_filter( 'wpseo_sitemap_exclude_post_type', function( $exclude, $post_type ) {
	// Excluir attachments
	if ( $post_type === 'attachment' ) {
		return true;
	}
	return $exclude;
}, 10, 2 );

add_filter( 'wpseo_sitemap_exclude_author', '__return_true' );



add_filter( 'wpseo_metadesc', function( $description ) {

    if ( is_front_page() || is_home() ) {
        return 'Reserva tu transfer privado desde o hacia el Aeropuerto de Barcelona, centro, hotel o puerto. ChÃ³fer profesional, precio cerrado y atenciÃ³n personalizada 24/7.';
    }

    return $description;

} );

/**
 * Full restoration of legal pages (Title, Slug, Content) to fix 404s and empty content.
 */
// DESACTIVADO: mt_full_restore_legal_pages_once() sobreescribÃ­a el contenido de las pÃ¡ginas
// legales con texto hardcodeado en functions.php en cada instalaciÃ³n nueva.
// Las pÃ¡ginas legales deben editarse desde el panel de WordPress. El contenido de esta
// funciÃ³n se conserva como referencia pero NO debe re-activarse: cualquier actualizaciÃ³n del
// tema sobreescribirÃ­a cambios legales aprobados.
// add_action( 'admin_init', 'mt_full_restore_legal_pages_once' );
function mt_full_restore_legal_pages_once() {
    if ( get_transient( 'mt_full_restored_legal_pages_v1' ) ) {
        return;
    }
    
    // The exact content from the original XML for the Spanish pages
    $legal_pages = array(
        'politica-de-privacidad' => array(
            'title' => 'PolÃ­tica de privacidad',
            'content' => '<h2>1. Identificaci&oacute;n del Responsable del Tratamiento</h2><p><strong>Raz&oacute;n Social:</strong> METRANSFERS GESTION SL</p><p><strong>NIF:</strong> B22522353</p><p><strong>Domicilio Fiscal:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESP&Iacute; &ndash; (BARCELONA)</p><p><strong>Contacto Privacidad:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p><h2>2. Aceptaci&oacute;n Vinculante</h2><p>Al utilizar nuestros servicios, navegar por nuestra plataforma o completar el proceso de configuraci&oacute;n de una reserva, usted reconoce haber le&iacute;do, comprendido y aceptado sin reservas que sus datos personales sean tratados conforme a los t&eacute;rminos aqu&iacute; expuestos. La formalizaci&oacute;n de una reserva constituye un contrato entre las partes, legitimando el tratamiento de los datos necesarios para la ejecuci&oacute;n del servicio.</p><h2>3. Datos Objeto de Tratamiento</h2><p>Recopilamos los datos estrictamente necesarios para la prestaci&oacute;n del servicio:</p><ul><li><strong>Datos de Reserva:</strong> Nombre, apellidos, tel&eacute;fono, correo electr&oacute;nico y detalles del trayecto/servicio solicitado.</li><li><strong>Datos de Facturaci&oacute;n:</strong> Direcci&oacute;n postal y NIF/DNI (seg&uacute;n los datos de registro fiscal de la entidad).</li><li><strong>Datos de Navegaci&oacute;n:</strong> Direcci&oacute;n IP, cookies y metadatos para garantizar la seguridad del sitio.</li></ul><h2>4. Finalidad del Tratamiento</h2><p>Sus datos ser&aacute;n tratados con el fin de:</p><ul><li><strong>Gesti&oacute;n de Reservas:</strong> Tramitar, confirmar y ejecutar los servicios de transporte o gesti&oacute;n contratados.</li><li><strong>Atenci&oacute;n al Cliente:</strong> Resolver dudas y proporcionar soporte a trav&eacute;s del punto &uacute;nico de contacto <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</li><li><strong>Cumplimiento Legal:</strong> Emitir facturas y cumplir con las obligaciones tributarias ante la AEAT.</li><li><strong>Seguridad:</strong> Prevenir fraudes y usos no autorizados de la plataforma.</li></ul><h2>5. Legitimaci&oacute;n</h2><p>La base legal para el tratamiento es:</p><ul><li><strong>Ejecuci&oacute;n Contractual:</strong> Necesaria para procesar su reserva y prestarle el servicio solicitado.</li><li><strong>Obligaci&oacute;n Legal:</strong> Derivada de la normativa fiscal y mercantil vigente en Espa&ntilde;a.</li><li><strong>Consentimiento:</strong> Otorgado expl&iacute;citamente al marcar la casilla de aceptaci&oacute;n en nuestros formularios.</li></ul><h2>6. Conservaci&oacute;n y Destinatarios</h2><p><strong>Plazos:</strong> Los datos se conservar&aacute;n durante el tiempo que dure la relaci&oacute;n comercial y, posteriormente, durante los plazos legales de prescripci&oacute;n (generalmente 6 a&ntilde;os para documentos contables seg&uacute;n el C&oacute;digo de Comercio).</p><p><strong>Cesiones:</strong> No se ceder&aacute;n datos a terceros ajenos a la operativa del servicio, salvo obligaci&oacute;n legal ante autoridades competentes.</p><h2>7. Derechos del Interesado</h2><p>Usted puede ejercer sus derechos de Acceso, Rectificaci&oacute;n, Supresi&oacute;n, Limitaci&oacute;n, Portabilidad y Oposici&oacute;n enviando una comunicaci&oacute;n escrita acompa&ntilde;ada de copia de su DNI a: <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p><p>Asimismo, tiene derecho a retirar su consentimiento en cualquier momento y a presentar una reclamaci&oacute;n ante la Agencia Espa&ntilde;ola de Protecci&oacute;n de Datos (AEPD) si considera que sus derechos han sido vulnerados.</p>'
        ),
        'politica-de-cookies' => array(
            'title' => 'PolÃ­tica de Cookies',
            'content' => '<h2>1. Responsable del sitio web</h2><p><strong>Raz&oacute;n social:</strong> METRANSFERS GESTION SL</p><p><strong>NIF:</strong> B22522353</p><p><strong>Domicilio:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESP&Iacute; (BARCELONA)</p><p><strong>Correo electr&oacute;nico:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p><h2>2. Qu&eacute; son las cookies</h2><p>Las cookies son peque&ntilde;os archivos que se descargan en su dispositivo al acceder a determinadas p&aacute;ginas web. Permiten, entre otras cosas, reconocer su navegador, mantener la sesi&oacute;n, recordar preferencias, reforzar la seguridad o facilitar determinadas funcionalidades t&eacute;cnicas del sitio.</p><h2>3. Tipos de cookies</h2><p>Las cookies pueden clasificarse, entre otros criterios, del siguiente modo:</p><ul><li><strong>Seg&uacute;n la entidad que las gestione:</strong> cookies propias y cookies de terceros.</li><li><strong>Seg&uacute;n su finalidad:</strong> cookies t&eacute;cnicas o necesarias, de preferencias o personalizaci&oacute;n, de an&aacute;lisis, y de publicidad o publicidad comportamental.</li><li><strong>Seg&uacute;n el tiempo que permanecen activas:</strong> cookies de sesi&oacute;n y cookies persistentes.</li></ul><h2>4. Cookies utilizadas en metransfers.es</h2><p>Con car&aacute;cter general, este sitio utiliza o puede utilizar cookies t&eacute;cnicas, de sesi&oacute;n y de preferencia estrictamente relacionadas con el funcionamiento de la web y la prestaci&oacute;n del servicio solicitado por el usuario. Entre ellas se incluyen, cuando proceda:</p><ul><li><strong>Cookies t&eacute;cnicas de navegaci&oacute;n y seguridad:</strong> necesarias para cargar la web, proteger formularios, prevenir usos abusivos y garantizar el funcionamiento b&aacute;sico del sitio.</li><li><strong>Cookies asociadas al proceso de reserva o contacto:</strong> necesarias para gestionar solicitudes enviadas por el usuario, mantener datos temporales de sesi&oacute;n y completar procesos esenciales vinculados al servicio contratado.</li><li><strong>Cookies de preferencias:</strong> destinadas a recordar opciones expresamente solicitadas por el usuario, como el idioma o determinadas configuraciones de visualizaci&oacute;n, cuando estas funcionalidades est&eacute;n habilitadas.</li><li><strong>Cookies t&eacute;cnicas de terceros vinculadas al servicio:</strong> determinados proveedores externos integrados en la web, como herramientas de traducci&oacute;n, mapas, contenidos embebidos o pasarelas de pago seguras, pueden instalar sus propias cookies cuando el usuario interact&uacute;a con dichas funcionalidades.</li></ul><p>Este tema no instala por s&iacute; mismo cookies de publicidad comportamental. Si en el futuro se incorporan herramientas anal&iacute;ticas no exentas, servicios de personalizaci&oacute;n avanzada o soluciones publicitarias que requieran consentimiento, se informar&aacute; al usuario de forma previa y se recabar&aacute; la autorizaci&oacute;n correspondiente antes de su activaci&oacute;n.</p><h2>5. Base jur&iacute;dica</h2><p>Las cookies t&eacute;cnicas o estrictamente necesarias pueden utilizarse sin consentimiento previo cuando resultan imprescindibles para prestar el servicio solicitado por el usuario o para posibilitar la navegaci&oacute;n segura por el sitio web. Las cookies no necesarias solo podr&aacute;n utilizarse cuando exista una base jur&iacute;dica adecuada y, en los casos exigidos por la normativa, tras obtener el consentimiento informado del usuario.</p><h2>6. Plazo de conservaci&oacute;n</h2><p>Las cookies de sesi&oacute;n permanecen activas &uacute;nicamente mientras el usuario navega por el sitio y se eliminan al cerrar el navegador. Las cookies persistentes, cuando existan, se conservar&aacute;n durante el tiempo estrictamente necesario para cumplir su finalidad o hasta que el usuario las elimine manualmente desde la configuraci&oacute;n de su navegador o del servicio correspondiente.</p><h2>7. Gesti&oacute;n, configuraci&oacute;n y desactivaci&oacute;n</h2><p>El usuario puede permitir, bloquear o eliminar las cookies instaladas en su dispositivo mediante la configuraci&oacute;n de su navegador. Debe tener en cuenta que la desactivaci&oacute;n de cookies t&eacute;cnicas o necesarias puede afectar al correcto funcionamiento del sitio, del proceso de reserva o de determinadas funcionalidades esenciales.</p><ul><li><a href="https://support.google.com/chrome/answer/95647?hl=es" target="_blank" rel="noopener">Configurar cookies en Google Chrome</a></li><li><a href="https://support.mozilla.org/es/kb/proteccion-antirrastreo-mejorada-firefox-escritorio" target="_blank" rel="noopener">Configurar cookies en Mozilla Firefox</a></li><li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Configurar cookies en Safari</a></li><li><a href="https://support.microsoft.com/es-es/microsoft-edge/administrar-cookies-en-microsoft-edge-ver-permitir-bloquear-eliminar-y-usar-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener">Configurar cookies en Microsoft Edge</a></li></ul><h2>8. Cookies de terceros</h2><p>La aceptaci&oacute;n, configuraci&oacute;n y uso de cookies de terceros se rige por las pol&iacute;ticas propias de dichos proveedores. METRANSFERS GESTION SL no puede controlar en todo momento las actualizaciones que esos terceros realicen en sus pol&iacute;ticas, por lo que se recomienda al usuario revisar directamente sus condiciones cuando interact&uacute;e con herramientas externas integradas o enlazadas desde la web.</p><h2>9. Informaci&oacute;n adicional y contacto</h2><p>Para obtener m&aacute;s informaci&oacute;n sobre el tratamiento de datos personales, puede consultar nuestra <a href="https://metransfers.es/privacidad">Pol&iacute;tica de Privacidad</a>. Si necesita aclaraciones sobre el uso de cookies en este sitio web, puede escribir a <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p><p>La presente Pol&iacute;tica de Cookies podr&aacute; actualizarse cuando se produzcan cambios normativos, t&eacute;cnicos o funcionales en el sitio web. Se recomienda revisarla peri&oacute;dicamente.</p>'
        ),
        'aviso-legal' => array(
            'title' => 'Aviso Legal',
            'content' => '<h2>1. INFORMACI&Oacute;N IDENTIFICATIVA</h2><p>En cumplimiento con el deber de informaci&oacute;n recogido en el art&iacute;culo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Informaci&oacute;n y del Comercio Electr&oacute;nico (LSSI-CE), a continuaci&oacute;n se reflejan los siguientes datos:</p><p><strong>Titular del sitio web:</strong> METRANSFERS GESTION SL</p><p><strong>NIF:</strong> B22522353</p><p><strong>Domicilio Social:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESP&Iacute; &ndash; (BARCELONA)</p><p><strong>Correo electr&oacute;nico de contacto:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p><p><strong>Actividad:</strong> Transporte de viajeros y gesti&oacute;n de servicios tur&iacute;sticos.</p><h2>2. CONDICIONES DE USO</h2><p>El acceso y/o uso de este portal atribuye la condici&oacute;n de USUARIO, que acepta, desde dicho acceso y/o uso, las Condiciones Generales de Uso aqu&iacute; reflejadas.</p><p>El sitio web proporciona acceso a informaciones, servicios o datos (en adelante, &ldquo;los contenidos&rdquo;) en Internet pertenecientes a METRANSFERS GESTION SL. El USUARIO asume la responsabilidad del uso del portal. Dicha responsabilidad se extiende al registro que fuese necesario para acceder a determinados servicios o contenidos (como el formulario de reservas).</p><h2>3. PROPIEDAD INTELECTUAL E INDUSTRIAL</h2><p>METRANSFERS GESTION SL es titular de todos los derechos de propiedad intelectual e industrial de su p&aacute;gina web, as&iacute; como de los elementos contenidos en la misma (a t&iacute;tulo enunciativo: im&aacute;genes, sonido, audio, v&iacute;deo, software o textos; marcas o logotipos, combinaciones de colores, estructura y dise&ntilde;o, selecci&oacute;n de materiales usados, programas de ordenador necesarios para su funcionamiento, acceso y uso, etc.).</p><p>En virtud de lo dispuesto en los art&iacute;culos 8 y 32.1, p&aacute;rrafo segundo, de la Ley de Propiedad Intelectual, quedan expresamente prohibidas la reproducci&oacute;n, la distribuci&oacute;n y la comunicaci&oacute;n p&uacute;blica, incluida su modalidad de puesta a disposici&oacute;n, de la totalidad o parte de los contenidos de esta p&aacute;gina web, con fines comerciales, en cualquier soporte y por cualquier medio t&eacute;cnico, sin la autorizaci&oacute;n de METRANSFERS GESTION SL.</p><h2>4. EXCLUSI&Oacute;N DE GARANT&Iacute;AS Y RESPONSABILIDAD</h2><p>EL PRESTADOR no se hace responsable, en ning&uacute;n caso, de los da&ntilde;os y perjuicios de cualquier naturaleza que pudieran ocasionar, a t&iacute;tulo enunciativo: errores u omisiones en los contenidos, falta de disponibilidad del portal o la transmisi&oacute;n de virus o programas maliciosos o lesivos en los contenidos, a pesar de haber adoptado todas las medidas tecnol&oacute;gicas necesarias para evitarlo.</p><h2>5. MODIFICACIONES</h2><p>METRANSFERS GESTION SL se reserva el derecho de efectuar sin previo aviso las modificaciones que considere oportunas en su portal, pudiendo cambiar, suprimir o a&ntilde;adir tanto los contenidos y servicios que se presten a trav&eacute;s de la misma como la forma en la que &eacute;stos aparezcan presentados o localizados en su portal.</p><h2>6. ENLACES (LINKS)</h2><p>En el caso de que en el sitio web se dispusiesen enlaces o hiperv&iacute;nculos hac&iacute;a otros sitios de Internet, METRANSFERS GESTION SL no ejercer&aacute; ning&uacute;n tipo de control sobre dichos sitios y contenidos. En ning&uacute;n caso asumir&aacute; responsabilidad alguna por los contenidos de alg&uacute;n enlace perteneciente a un sitio web ajeno.</p><h2>7. DERECHO DE EXCLUSI&Oacute;N</h2><p>METRANSFERS GESTION SL se reserva el derecho a denegar o retirar el acceso al portal y/o los servicios ofrecidos sin necesidad de preaviso, a instancia propia o de un tercero, a aquellos usuarios que incumplan las presentes Condiciones Generales de Uso.</p><h2>8. PROTECCI&Oacute;N DE DATOS</h2><p>Todo lo relativo a la pol&iacute;tica de protecci&oacute;n de datos se encuentra recogido en el documento de Pol&iacute;tica de Privacidad de la entidad, conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley Org&aacute;nica 3/2018 (LOPDGDD).</p><h2>9. LEGISLACI&Oacute;N APLICABLE Y JURISDICCI&Oacute;N</h2><p>La relaci&oacute;n entre METRANSFERS GESTION SL y el USUARIO se regir&aacute; por la normativa espa&ntilde;ola vigente y cualquier controversia se someter&aacute; a los Juzgados y Tribunales de la ciudad de Barcelona.</p>'
        ),
        'terminos-y-condiciones' => array(
            'title' => 'TÃ©rminos y Condiciones regulan la contrataciÃ³n',
            'content' => '<h2>1. MARCO LEGAL APLICABLE</h2><p>El presente contrato se rige por lo dispuesto en la legislaci&oacute;n espa&ntilde;ola vigente, espec&iacute;ficamente:</p><ul><li>Ley 16/1987, de 30 de julio, de Ordenaci&oacute;n de los Transportes Terrestres (LOTT) y su Reglamento (ROTT).</li><li>Ley 34/2002 (LSSI-CE) sobre servicios de la sociedad de la informaci&oacute;n.</li><li>Real Decreto Legislativo 1/2007, por el que se aprueba el texto refundido de la Ley General para la Defensa de los Consumidores y Usuarios.</li><li>Reglamento (UE) 2016/679 (RGPD) en materia de protecci&oacute;n de datos.</li></ul><h2>2. IDENTIFICACI&Oacute;N DE LAS PARTES</h2><p><strong>El Prestador:</strong> METRANSFERS GESTION SL, con NIF B22522353 y domicilio social en AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESP&Iacute; (BARCELONA).</p><p><strong>El Cliente:</strong> Persona f&iacute;sica o jur&iacute;dica que formaliza la reserva y garantiza tener capacidad legal para contratar.</p><h2>3. OBLIGACI&Oacute;N DE NOTIFICACI&Oacute;N Y REQUISITOS DEL SERVICIO</h2><p>Para garantizar la seguridad y legalidad del transporte, el Cliente tiene la obligaci&oacute;n inexcusable de declarar las siguientes necesidades en el formulario de reserva:</p><h3>3.1. Sistemas de Retenci&oacute;n Infantil (SRI)</h3><p>Conforme al Art&iacute;culo 117 del Reglamento General de Circulaci&oacute;n, es obligatorio el uso de sillas homologadas para menores de estatura igual o inferior a 135 cm. El Cliente debe seleccionar el n&uacute;mero y tipo de sillas necesarias en el formulario. La omisi&oacute;n de este dato facultar&aacute; al conductor a denegar el servicio por razones de seguridad, sin derecho a reembolso.</p><h3>3.2. Equipaje Extraordinario</h3><p>La capacidad del veh&iacute;culo est&aacute; limitada por su ficha t&eacute;cnica. El transporte de maletas adicionales, material deportivo (golf, esqu&iacute;) o bultos voluminosos debe ser notificado. EL PRESTADOR se reserva el derecho de cobrar suplementos o denegar el transporte si el volumen excede la capacidad del maletero del veh&iacute;culo contratado.</p><h3>3.3. Transporte de Mascotas</h3><p>El transporte de animales dom&eacute;sticos est&aacute; sujeto a notificaci&oacute;n previa y debe realizarse en trasportines homologados proporcionados por el cliente, salvo acuerdo en contrario. Los perros gu&iacute;a viajar&aacute;n sin coste adicional conforme a la normativa vigente.</p><h2>4. PASARELA DE PAGO Y SEGURIDAD (REDSYS)</h2><p>El pago de los servicios se efectuar&aacute; mediante tarjeta de cr&eacute;dito o d&eacute;bito a trav&eacute;s de la pasarela de pago segura Redsys.</p><ul><li><strong>Seguridad:</strong> El sistema utiliza protocolos de encriptaci&oacute;n SSL y autenticaci&oacute;n 3D Secure (Verified by Visa / Mastercard ID Check).</li><li><strong>Confirmaci&oacute;n:</strong> El contrato se perfecciona en el momento en que EL PRESTADOR recibe la confirmaci&oacute;n de la autorizaci&oacute;n de pago por parte de la entidad bancaria.</li><li><strong>Fraude:</strong> EL PRESTADOR se reserva el derecho de anular cualquier transacci&oacute;n ante sospechas de uso fraudulento de tarjetas.</li></ul><h2>5. DERECHO DE DESISTIMIENTO Y POL&Iacute;TICA DE CANCELACI&Oacute;N</h2><p>En virtud del Art&iacute;culo 103 l) del Real Decreto Legislativo 1/2007, el derecho de desistimiento no ser&aacute; aplicable a los servicios de transporte de pasajeros si el contrato prev&eacute; una fecha o un periodo de ejecuci&oacute;n espec&iacute;ficos. No obstante, EL PRESTADOR ofrece las siguientes condiciones comerciales:</p><ul><li><strong>Cancelaci&oacute;n con &gt;24 horas:</strong> Devoluci&oacute;n del 100% del importe mediante el mismo sistema de pago (Redsys).</li><li><strong>Cancelaci&oacute;n con &lt;24 horas o No-Show:</strong> Penalizaci&oacute;n del 100% del valor de la reserva.</li><li><strong>Retrasos de vuelos:</strong> EL PRESTADOR monitoriza los vuelos. No obstante, si el retraso excede los 90 minutos sobre la hora prevista, el servicio quedar&aacute; sujeto a disponibilidad de flota, pudiendo incurrir en gastos de espera adicionales.</li></ul><h2>6. RESPONSABILIDAD LIMITADA</h2><p>EL PRESTADOR no ser&aacute; responsable por incumplimientos derivados de:</p><ul><li>Fuerza mayor o causas fortuitas (cortes de carretera, condiciones clim&aacute;ticas adversas, huelgas generales).</li><li>Errores en los datos facilitados por el cliente en el formulario de reserva (ej. fecha err&oacute;nea o n&uacute;mero de tel&eacute;fono incorrecto).</li><li>Incumplimiento de las normas de seguridad por parte de los pasajeros (uso de cintur&oacute;n, comportamiento disruptivo).</li></ul><h2>7. JURISDICCI&Oacute;N Y LEY APLICABLE</h2><p>Para la resoluci&oacute;n de cualquier litigio derivado de la interpretaci&oacute;n o ejecuci&oacute;n de este contrato, las partes se someten a la legislaci&oacute;n espa&ntilde;ola. En caso de controversia, se recurrir&aacute; a los Juzgados y Tribunales de Barcelona, salvo que el cliente ostente la condici&oacute;n de consumidor, en cuyo caso se atender&aacute; a la competencia territorial establecida por ley.</p>'
        )
    );
    
    foreach ( $legal_pages as $slug => $data ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        
        if ( $page instanceof WP_Post ) {
            wp_update_post( array(
                'ID'           => $page->ID,
                'post_title'   => $data['title'],
                'post_content' => $data['content'],
                'post_status'  => 'publish'
            ) );
            update_post_meta( $page->ID, '_me_transfers_page_role', 'legal' );
        } else {
            // Create if it somehow doesn't exist
            $new_page_id = wp_insert_post( array(
                'post_title'   => $data['title'],
                'post_content' => $data['content'],
                'post_status'  => 'publish',
                'post_name'    => $slug,
                'post_type'    => 'page'
            ) );
            if ( ! is_wp_error( $new_page_id ) ) {
                update_post_meta( $new_page_id, '_me_transfers_page_role', 'legal' );
            }
        }
    }
    
    set_transient( 'mt_full_restored_legal_pages_v1', true, DAY_IN_SECONDS * 365 );
}

// add_action( 'admin_init', 'mt_fix_legal_pages_slugs_and_content_v1' );
function mt_fix_legal_pages_slugs_and_content_v1() {
    if ( get_option( 'mt_legal_pages_fixed_v1' ) ) {
        return;
    }

    // 1. Renombrar privacidad -> politica-de-privacidad
    $privacidad = get_page_by_path( 'privacidad', OBJECT, 'page' );
    if ( $privacidad instanceof WP_Post ) {
        wp_update_post( array(
            'ID' => $privacidad->ID,
            'post_name' => 'politica-de-privacidad',
            'post_title' => 'PolÃ­tica de privacidad'
        ) );
    }

    // 2. Renombrar cookie -> politica-de-cookies
    $cookie = get_page_by_path( 'cookie', OBJECT, 'page' );
    if ( $cookie instanceof WP_Post ) {
        wp_update_post( array(
            'ID' => $cookie->ID,
            'post_name' => 'politica-de-cookies',
            'post_title' => 'PolÃ­tica de Cookies'
        ) );
    }

    // 3. Actualizar contenido de aviso-legal
    $aviso = get_page_by_path( 'aviso-legal', OBJECT, 'page' );
    if ( $aviso instanceof WP_Post ) {
        $aviso_content = '<h2>1. InformaciÃ³n Identificativa del Aviso Legal</h2><p>En primer lugar, detallamos los datos del responsable del sitio web.<br><strong>Titular:</strong> METRANSFERS GESTION SL<br><strong>NIF:</strong> B22522353<br><strong>Domicilio:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÃ â€“ (BARCELONA)<br><strong>Correo:</strong> info@metransfers.es<br><strong>Actividad:</strong> Transporte de viajeros y gestiÃ³n turÃ­stica.<br><strong>Datos Registrales:</strong> Inscrita en el Registro Mercantil de Barcelona, Tomo [Completar], Folio [Completar], SecciÃ³n [Completar], Hoja [Completar].<br><strong>AutorizaciÃ³n:</strong> Licencia de transporte [Completar], regulada por el <a href="https://www.transportes.gob.es/">Ministerio de Transportes y Movilidad Sostenible</a>.</p><h2>2. Condiciones de Uso de este Aviso Legal</h2><p>El acceso a este portal te da la condiciÃ³n de USUARIO. Por consiguiente, aceptas las Condiciones Generales de Uso de este Aviso Legal. El sitio web ofrece varios servicios de METRANSFERS GESTION SL. Como resultado, el USUARIO asume la responsabilidad del uso del portal. AdemÃ¡s, esto incluye el registro necesario para ciertos servicios. Un ejemplo claro es nuestro sistema de reservas.</p><p>Por otro lado, los precios mostrados incluyen el IVA. TambiÃ©n incluyen otros impuestos vigentes en EspaÃ±a. Esto aplica siempre, salvo que se indique otra cosa en la reserva.</p><h2>3. Propiedad Intelectual e Industrial</h2><p>METRANSFERS GESTION SL es dueÃ±a de todos los derechos de la web. En consecuencia, posee los derechos legales de todos los elementos del sitio. Esto incluye imÃ¡genes, sonido, vÃ­deo, software y textos. TambiÃ©n abarca marcas, colores, diseÃ±o y programas de ordenador.</p><p>Por lo tanto, la ley prohÃ­be copiar o compartir estos contenidos. En especial, no se permite su uso comercial de ninguna forma. Para hacerlo, necesitas un permiso previo de METRANSFERS GESTION SL.</p><h2>4. ExclusiÃ³n de GarantÃ­as y Responsabilidad</h2><p>El creador no se hace responsable de posibles daÃ±os. Por ejemplo, no responde por errores en los textos o caÃ­das de la web. De igual forma, no asume la culpa por virus en el sistema. Sin embargo, hemos tomado todas las medidas tecnolÃ³gicas para evitar estos problemas.</p><h2>5. Modificaciones al Aviso Legal</h2><p>METRANSFERS GESTION SL puede cambiar este Aviso Legal sin aviso previo. AdemÃ¡s, puede modificar todo su portal web. En resumen, puede cambiar o borrar servicios y contenidos de la pÃ¡gina libremente.</p><h2>6. Enlaces de Terceros y ResoluciÃ³n de Conflictos</h2><p>A veces, el sitio web tiene enlaces a otras pÃ¡ginas. En estos casos, METRANSFERS GESTION SL no controla esos sitios. Por lo tanto, no asume ninguna responsabilidad por ellos.</p><p>AdemÃ¡s, informamos sobre la <a href="https://ec.europa.eu/consumers/odr/">plataforma de resoluciÃ³n de litigios en lÃ­nea</a>. La ComisiÃ³n Europea facilita esta Ãºtil web. Su principal fin es resolver problemas de comercio por internet.</p><h2>7. Derecho de ExclusiÃ³n</h2><p>METRANSFERS GESTION SL puede quitar el acceso al portal. TambiÃ©n puede retirar los servicios ofrecidos sin avisar. En efecto, esto aplica a quienes no cumplan las normas de este Aviso Legal.</p><h2>8. ProtecciÃ³n de Datos</h2><p>En primer lugar, cuidamos los datos personales de nuestros clientes. Todo el proceso se explica en nuestro documento sobre privacidad. AdemÃ¡s, cumplimos con el RGPD y la LOPDGDD. Para saber mÃ¡s, visita <a href="https://metransfers.es/politica-de-privacidad/">https://metransfers.es/politica-de-privacidad/</a>.</p><h2>9. LegislaciÃ³n Aplicable y JurisdicciÃ³n</h2><p>Por Ãºltimo, la relaciÃ³n con el USUARIO sigue la ley espaÃ±ola. Por lo tanto, cualquier problema sobre este Aviso Legal irÃ¡ a los tribunales. En concreto, se tratarÃ¡ en los Juzgados de la ciudad de Barcelona.</p>';
        
        wp_update_post( array(
            'ID' => $aviso->ID,
            'post_content' => $aviso_content
        ) );
    }

    // Actualizar opciÃ³n de legal sync tambiÃ©n para crear de 0 si alguna fue borrada manualmente
    update_option( 'me_transfers_legal_pages_sync_version', '2026-08-18' );

    update_option( 'mt_legal_pages_fixed_v1', true );
}

// add_action( 'admin_init', 'mt_fix_legal_pages_terms_and_conditions_v2' );
function mt_fix_legal_pages_terms_and_conditions_v2() {
    if ( get_option( 'mt_legal_pages_fixed_v2' ) ) {
        return;
    }

    $terminos = get_page_by_path( 'terminos-y-condiciones', OBJECT, 'page' );
    if ( $terminos instanceof WP_Post ) {
        $terminos_content = '<h2>TÃ‰RMINOS Y CONDICIONES DE CONTRATACIÃ“N</h2><p><strong>Ãšltima actualizaciÃ³n: 18 de agosto de 2026</strong></p><p>Los presentes TÃ©rminos y Condiciones regulan la contrataciÃ³n de los servicios ofrecidos por METRANSFERS GESTION SL, en adelante â€œMeTransfersâ€, incluyendo servicios de traslados privados, transfers, servicios de aeropuerto, puerto y hotel, transporte corporativo, tours, excursiones, servicios personalizados y transporte de encomiendas u objetos previamente aceptados por MeTransfers.</p><p>La contrataciÃ³n de cualquiera de nuestros servicios implica la lectura, comprensiÃ³n y aceptaciÃ³n de estos TÃ©rminos y Condiciones.</p><p>Al realizar una reserva, solicitar un servicio, efectuar un pago o marcar la casilla de aceptaciÃ³n correspondiente, el cliente declara expresamente que ha leÃ­do y acepta las condiciones aplicables al servicio contratado.</p><h2>1. IDENTIFICACIÃ“N DE METRANSFERS</h2><ul><li><strong>Titular:</strong> METRANSFERS GESTION SL</li><li><strong>NIF:</strong> B22522353</li><li><strong>Domicilio social:</strong> Avda. Mare de DÃ©u de Montserrat, nÃºm. 18, planta 5, puerta 2, 08970 Sant Joan DespÃ­, Barcelona, EspaÃ±a.</li><li><strong>Correo electrÃ³nico:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></li><li><strong>TelÃ©fono de contacto:</strong> +34 662 02 41 36</li><li><strong>Actividad:</strong> transporte de viajeros y gestiÃ³n de servicios turÃ­sticos.</li></ul><h2>2. OBJETO DE ESTAS CONDICIONES</h2><p>Estas condiciones regulan la relaciÃ³n entre MeTransfers y cualquier persona fÃ­sica o jurÃ­dica que contrate alguno de sus servicios.</p><p>Se aplican, entre otros, a:</p><ul><li>Traslados privados.</li><li>Traslados desde o hacia aeropuertos.</li><li>Traslados desde o hacia puertos.</li><li>Traslados entre hoteles, viviendas, empresas y otras direcciones.</li><li>Servicios de chÃ³fer.</li><li>Traslados corporativos.</li><li>Traslados de grupos.</li><li>Tours privados.</li><li>Excursiones.</li><li>Servicios turÃ­sticos personalizados.</li><li>Servicios para eventos.</li><li>Servicios de transporte de encomiendas u objetos previamente aceptados.</li><li>Cualquier otro servicio de transporte o movilidad ofrecido y confirmado expresamente por MeTransfers.</li></ul><p>Las condiciones particulares indicadas durante el proceso de reserva, en un presupuesto, por correo electrÃ³nico, WhatsApp o en la confirmaciÃ³n del servicio forman parte del contrato y prevalecerÃ¡n sobre estas condiciones cuando regulen de manera especÃ­fica un servicio concreto.</p><h2>3. ACEPTACIÃ“N DE LOS TÃ‰RMINOS Y CONDICIONES</h2><p>Antes de finalizar una contrataciÃ³n, el cliente tendrÃ¡ acceso a estos TÃ©rminos y Condiciones.</p><p>Al marcar la casilla:</p><blockquote>â€œHe leÃ­do y acepto los TÃ©rminos y Condiciones de contrataciÃ³n de MeTransfersâ€</blockquote><p>y continuar con la reserva o el pago, el cliente manifiesta de forma expresa que:</p><ul><li>Ha leÃ­do estas condiciones.</li><li>Las comprende.</li><li>Las acepta.</li><li>Los datos introducidos en la reserva son correctos.</li><li>Tiene capacidad legal suficiente para contratar.</li><li>Entiende las caracterÃ­sticas y condiciones del servicio solicitado.</li><li>Conoce el precio mostrado o el presupuesto aceptado.</li><li>Acepta las condiciones de modificaciÃ³n, cancelaciÃ³n, espera y no presentaciÃ³n que correspondan al servicio.</li><li>Autoriza a MeTransfers a organizar el servicio contratado.</li></ul><p>La aceptaciÃ³n electrÃ³nica tendrÃ¡ los mismos efectos contractuales que la aceptaciÃ³n realizada por otros medios admitidos legalmente.</p><p>Cuando una persona realiza una reserva para otros pasajeros, declara que estÃ¡ autorizada para actuar en nombre del grupo y se compromete a comunicar a todos los pasajeros las condiciones aplicables.</p><p>La persona que realiza la reserva serÃ¡ considerada interlocutor principal de MeTransfers.</p><h2>4. PROCESO DE RESERVA</h2><p>Para solicitar un servicio, el cliente deberÃ¡ facilitar la informaciÃ³n necesaria para su correcta organizaciÃ³n.</p><p>Dependiendo del tipo de servicio, podrÃ¡ solicitarse:</p><ul><li>Nombre y apellidos.</li><li>TelÃ©fono.</li><li>Correo electrÃ³nico.</li><li>Fecha del servicio.</li><li>Hora.</li><li>Lugar de recogida.</li><li>Lugar de destino.</li><li>NÃºmero de pasajeros.</li><li>Cantidad aproximada de equipaje.</li><li>NÃºmero de vuelo, tren o crucero.</li><li>Hotel o alojamiento.</li><li>InformaciÃ³n sobre menores.</li><li>Necesidad de silla infantil o elevador.</li><li>Necesidades especiales de movilidad.</li><li>Tipo de vehÃ­culo solicitado.</li><li>InformaciÃ³n relacionada con una encomienda.</li><li>Cualquier dato razonablemente necesario para ejecutar el servicio.</li></ul><p>El cliente es responsable de introducir informaciÃ³n correcta.</p><p>MeTransfers no serÃ¡ responsable de incidencias producidas como consecuencia de direcciones incorrectas, fechas equivocadas, horarios errÃ³neos, nÃºmeros de vuelo incorrectos, telÃ©fonos no operativos u otros datos facilitados incorrectamente por el cliente, sin perjuicio de los derechos que legalmente correspondan al consumidor.</p><h2>5. CONFIRMACIÃ“N DE LA RESERVA</h2><p>El envÃ­o de un formulario o solicitud no implica necesariamente que el servicio estÃ© confirmado.</p><p>Una reserva se considerarÃ¡ confirmada cuando el cliente reciba una confirmaciÃ³n expresa de MeTransfers, y cuando corresponda, el pago o autorizaciÃ³n de pago haya sido procesado correctamente.</p><p>La confirmaciÃ³n podrÃ¡ enviarse por:</p><ul><li>Correo electrÃ³nico.</li><li>WhatsApp.</li><li>Sistema automÃ¡tico de reservas.</li><li>Otro medio electrÃ³nico facilitado por el cliente.</li></ul><p>La confirmaciÃ³n podrÃ¡ incluir:</p><ul><li>NÃºmero o referencia de reserva.</li><li>Fecha.</li><li>Hora.</li><li>Origen.</li><li>Destino.</li><li>VehÃ­culo.</li><li>NÃºmero de pasajeros.</li><li>Precio.</li><li>Servicios adicionales.</li><li>Condiciones particulares.</li></ul><p>El cliente deberÃ¡ revisar la confirmaciÃ³n inmediatamente y comunicar cualquier error tan pronto como sea posible.</p><h2>6. PRECIOS</h2><p>El precio aplicable serÃ¡ el mostrado durante el proceso de contrataciÃ³n o el indicado expresamente en el presupuesto aceptado por el cliente.</p><p>Cuando un servicio no pueda calcularse automÃ¡ticamente, MeTransfers podrÃ¡ preparar un presupuesto personalizado.</p><p>El precio podrÃ¡ variar en funciÃ³n de factores como:</p><ul><li>Origen y destino.</li><li>Fecha y horario.</li><li>Distancia.</li><li>Tipo de vehÃ­culo.</li><li>NÃºmero de vehÃ­culos.</li><li>NÃºmero de pasajeros.</li><li>Equipaje.</li><li>DuraciÃ³n del servicio.</li><li>Tiempo de espera.</li><li>Servicios adicionales.</li><li>Peajes.</li><li>Aparcamientos.</li><li>Entradas o servicios de terceros.</li><li>Recogidas adicionales.</li><li>Paradas extraordinarias.</li><li>Requerimientos especiales.</li></ul><p>Antes de realizar el pago se informarÃ¡ al cliente del precio aplicable y, cuando corresponda, de los suplementos conocidos.</p><p>Un cambio solicitado por el cliente despuÃ©s de confirmar la reserva podrÃ¡ modificar el precio.</p><h2>7. FORMAS DE PAGO</h2><p>Los mÃ©todos de pago disponibles serÃ¡n los mostrados durante el proceso de contrataciÃ³n.</p><p>La disponibilidad de determinados mÃ©todos de pago podrÃ¡ depender de la pasarela de pago utilizada, el tipo de reserva, el paÃ­s, la moneda o las caracterÃ­sticas del servicio.</p><p>MeTransfers no garantiza permanentemente la disponibilidad de un mÃ©todo de pago concreto.</p><p>La reserva podrÃ¡ requerir:</p><ul><li>Pago completo anticipado.</li><li>Pago parcial.</li><li>DepÃ³sito.</li><li>AutorizaciÃ³n previa.</li><li>Pago segÃºn las condiciones especÃ­ficas indicadas en el presupuesto.</li></ul><p>Cuando corresponda, el cliente serÃ¡ redirigido a una plataforma de pago segura gestionada por un proveedor especializado.</p><p>MeTransfers no almacena los datos completos de las tarjetas bancarias cuando el pago es procesado directamente por una pasarela externa.</p><h2>8. OBLIGACIÃ“N DE PAGO</h2><p>Cuando la contrataciÃ³n implique el pago de un importe, el cliente serÃ¡ informado de ello antes de confirmar el pedido.</p><p>Al pulsar el botÃ³n utilizado para completar la reserva y realizar el pago, el cliente reconoce expresamente que la contrataciÃ³n implica una obligaciÃ³n de pago.</p><p>La aceptaciÃ³n de estos TÃ©rminos y Condiciones no sustituye la autorizaciÃ³n de pago correspondiente.</p><h2>9. TRASLADOS PRIVADOS</h2><p>El servicio de traslado consiste en transportar al cliente entre los puntos indicados en la reserva.</p><p>El cliente deberÃ¡ encontrarse preparado en el punto de recogida a la hora acordada.</p><p>Los tiempos de viaje publicados en la web son orientativos.</p><p>La duraciÃ³n real puede verse afectada por:</p><ul><li>TrÃ¡fico.</li><li>Accidentes.</li><li>Obras.</li><li>Controles.</li><li>Condiciones meteorolÃ³gicas.</li><li>Manifestaciones.</li><li>Eventos.</li><li>Restricciones de circulaciÃ³n.</li><li>Estado de las carreteras.</li><li>Paradas necesarias.</li><li>Otras circunstancias ajenas al control razonable de MeTransfers.</li></ul><p>Por este motivo, MeTransfers no garantiza una duraciÃ³n exacta de cada trayecto.</p><p>Cuando el cliente necesite llegar a un aeropuerto, estaciÃ³n, puerto, evento o cita con horario determinado, deberÃ¡ reservar con un margen de seguridad suficiente.</p><h2>10. RECOGIDAS EN AEROPUERTOS</h2><p>Cuando el cliente facilite correctamente su nÃºmero de vuelo, MeTransfers podrÃ¡ utilizar esta informaciÃ³n para coordinar la recogida.</p><p>La monitorizaciÃ³n de un vuelo no implica un tiempo de espera ilimitado.</p><p>El tiempo de cortesÃ­a o espera incluido serÃ¡ el indicado en la reserva, presupuesto o confirmaciÃ³n del servicio.</p><p>Una vez superado el tiempo de espera incluido, podrÃ¡n aplicarse cargos adicionales si el vehÃ­culo y el conductor pueden continuar esperando.</p><p>Si el cliente prevÃ© una demora por recogida de equipaje, controles fronterizos, incidencias en el aeropuerto u otra circunstancia, deberÃ¡ contactar con MeTransfers lo antes posible.</p><p>Un retraso del vuelo no garantiza por sÃ­ mismo la disponibilidad indefinida del vehÃ­culo originalmente asignado.</p><p>MeTransfers realizarÃ¡ esfuerzos razonables para adaptar el servicio cuando exista informaciÃ³n suficiente y disponibilidad operativa.</p><h2>11. RECOGIDAS EN PUERTOS, HOTELES, DOMICILIOS Y OTROS PUNTOS</h2><p>El cliente deberÃ¡ encontrarse en el lugar indicado a la hora acordada.</p><p>Si existe dificultad para localizar al cliente, MeTransfers podrÃ¡ intentar contactar utilizando el telÃ©fono facilitado en la reserva.</p><p>Es responsabilidad del cliente proporcionar un nÃºmero de telÃ©fono operativo durante el servicio.</p><p>Cuando existan restricciones de acceso, calles peatonales, zonas cerradas al trÃ¡fico o puntos donde legalmente no sea posible detener el vehÃ­culo, la recogida podrÃ¡ realizarse en el punto autorizado mÃ¡s cercano.</p><h2>12. TIEMPOS DE ESPERA</h2><p>Los tiempos de espera incluidos pueden variar segÃºn el tipo de servicio y serÃ¡n los indicados durante la contrataciÃ³n o en la confirmaciÃ³n.</p><p>Una vez finalizado el periodo incluido, MeTransfers podrÃ¡:</p><ul><li>Aplicar un suplemento de espera.</li><li>Proponer una nueva hora.</li><li>Reasignar el servicio.</li><li>Considerar el servicio como no presentado cuando no exista comunicaciÃ³n con el cliente y no resulte razonable continuar esperando.</li></ul><p>Cualquier cargo adicional deberÃ¡ corresponder al servicio adicional efectivamente solicitado o generado y serÃ¡ comunicado al cliente cuando resulte posible.</p><h2>13. RETRASOS DEL CLIENTE</h2><p>El cliente deberÃ¡ informar inmediatamente si sabe que llegarÃ¡ tarde.</p><p>La posibilidad de mantener el vehÃ­culo esperando dependerÃ¡ de:</p><ul><li>Disponibilidad del conductor.</li><li>Servicios posteriores.</li><li>Tiempo de retraso.</li><li>Normativa aplicable.</li><li>Condiciones del lugar de recogida.</li></ul><p>MeTransfers intentarÃ¡ ofrecer una soluciÃ³n, pero no puede garantizar la disponibilidad del mismo vehÃ­culo cuando el cliente no se presenta dentro del periodo acordado.</p><h2>14. NO PRESENTACIÃ“N â€“ NO SHOW</h2><p>PodrÃ¡ considerarse no show cuando el cliente:</p><ul><li>No se presenta en el lugar acordado.</li><li>No responde a los intentos razonables de contacto.</li><li>Facilita un lugar, fecha u hora incorrectos que impiden realizar el servicio.</li><li>Abandona el punto de encuentro sin informar.</li><li>No se encuentra disponible una vez finalizado el tiempo de espera correspondiente.</li></ul><p>Cuando un servicio sea considerado no show, podrÃ¡n aplicarse las condiciones econÃ³micas de cancelaciÃ³n o pÃ©rdida del importe previstas para la reserva, siempre de acuerdo con la informaciÃ³n facilitada al contratar y la normativa aplicable.</p><h2>15. NÃšMERO DE PASAJEROS</h2><p>El cliente deberÃ¡ indicar correctamente el nÃºmero total de pasajeros, incluidos niÃ±os y bebÃ©s.</p><p>NingÃºn vehÃ­culo podrÃ¡ transportar un nÃºmero de pasajeros superior a su capacidad legal autorizada.</p><p>La capacidad dependerÃ¡ del vehÃ­culo finalmente contratado.</p><p>Cuando un grupo supere la capacidad de un solo vehÃ­culo, MeTransfers podrÃ¡ ofrecer:</p><ul><li>Varios vehÃ­culos.</li><li>Un vehÃ­culo de mayor capacidad.</li><li>Una soluciÃ³n gestionada mediante un colaborador autorizado.</li></ul><p>El cliente deberÃ¡ informar correctamente del tamaÃ±o del grupo antes de confirmar la reserva.</p><h2>16. EQUIPAJE</h2><p>Cada vehÃ­culo tiene una capacidad limitada de equipaje.</p><p>El cliente deberÃ¡ informar previamente si transportarÃ¡:</p><ul><li>Maletas de gran tamaÃ±o.</li><li>Equipaje especialmente voluminoso.</li><li>Bicicletas.</li><li>Equipamiento deportivo.</li><li>Cochecitos infantiles.</li><li>Sillas de ruedas.</li><li>Instrumentos.</li><li>Material profesional.</li><li>Objetos de dimensiones especiales.</li></ul><p>La expresiÃ³n â€œequipajeâ€ no implica capacidad ilimitada.</p><p>Si el equipaje supera la capacidad segura del vehÃ­culo reservado, podrÃ¡ ser necesario contratar un vehÃ­culo adicional o modificar la categorÃ­a del servicio.</p><p>Los pasajeros no podrÃ¡n colocar equipaje de forma que comprometa la seguridad del vehÃ­culo.</p><h2>17. SILLAS INFANTILES Y MENORES</h2><p>Los niÃ±os y bebÃ©s cuentan como pasajeros.</p><p>Cuando se requiera un sistema de retenciÃ³n infantil, deberÃ¡ solicitarse durante la reserva.</p><p>La disponibilidad y posible coste del sistema solicitado se indicarÃ¡n antes de confirmar el servicio o en la comunicaciÃ³n correspondiente.</p><p>El cliente deberÃ¡ indicar correctamente la edad o caracterÃ­sticas necesarias para seleccionar el sistema adecuado.</p><p>MeTransfers actuarÃ¡ conforme a la normativa de seguridad vial aplicable.</p><p>Los menores deberÃ¡n viajar acompaÃ±ados por una persona adulta responsable salvo que MeTransfers haya aceptado expresamente y por escrito un servicio diferente permitido por la legislaciÃ³n aplicable.</p><h2>18. PERSONAS CON MOVILIDAD REDUCIDA</h2><p>Las personas que necesiten asistencia especial deberÃ¡n comunicarlo antes de la reserva.</p><p>DeberÃ¡n indicarse especialmente:</p><ul><li>Uso de silla de ruedas.</li><li>Tipo de silla.</li><li>Necesidad de rampa.</li><li>Necesidad de vehÃ­culo adaptado.</li><li>Equipamiento mÃ©dico.</li><li>Necesidades de asistencia.</li></ul><p>La aceptaciÃ³n del servicio dependerÃ¡ de que MeTransfers disponga directamente o mediante colaboradores de un vehÃ­culo adecuado a las necesidades comunicadas.</p><h2>19. TOURS Y EXCURSIONES</h2><p>Los tours y excursiones podrÃ¡n incluir transporte y otros servicios expresamente indicados en la descripciÃ³n o confirmaciÃ³n.</p><p>Salvo que se indique expresamente, no se entenderÃ¡n incluidos automÃ¡ticamente:</p><ul><li>Entradas.</li><li>Comidas.</li><li>Bebidas.</li><li>GuÃ­as oficiales.</li><li>Actividades externas.</li><li>Aparcamientos.</li><li>Servicios de terceros.</li></ul><p>Los itinerarios y horarios podrÃ¡n sufrir ajustes razonables por:</p><ul><li>TrÃ¡fico.</li><li>Clima.</li><li>Cierres.</li><li>Festividades.</li><li>Restricciones de acceso.</li><li>Aforo.</li><li>Decisiones de autoridades.</li><li>Circunstancias extraordinarias.</li></ul><p>Cuando un servicio incluya entradas, actividades o servicios proporcionados por terceros, podrÃ¡n aplicarse ademÃ¡s las condiciones de cancelaciÃ³n y utilizaciÃ³n del proveedor correspondiente.</p><h2>20. ENCOMIENDAS Y TRANSPORTE DE OBJETOS</h2><p>MeTransfers podrÃ¡ aceptar determinados servicios de transporte de encomiendas, paquetes u objetos.</p><p>La aceptaciÃ³n dependerÃ¡ del tipo de objeto, dimensions, peso, origen, destino y disponibilidad operativa.</p><p>El cliente deberÃ¡ declarar correctamente el contenido.</p><p>No podrÃ¡n transportarse, salvo aceptaciÃ³n expresa y siempre que sea legalmente posible:</p><ul><li>Armas.</li><li>Explosivos.</li><li>Sustancias inflamables.</li><li>Drogas o sustancias ilegales.</li><li>MercancÃ­as robadas.</li><li>Productos cuya posesiÃ³n o transporte sea ilegal.</li><li>Material peligroso.</li><li>Dinero en efectivo en cantidades relevantes.</li><li>DocumentaciÃ³n de valor excepcional.</li><li>Joyas u objetos de elevado valor no declarados.</li><li>Animales no autorizados.</li><li>Productos que requieran condiciones especiales de conservaciÃ³n no previamente acordadas.</li></ul><p>MeTransfers podrÃ¡ rechazar cualquier encomienda cuando exista una duda razonable sobre su seguridad, legalidad, naturaleza o contenido.</p><h2>21. RESPONSABILIDAD DEL REMITENTE DE UNA ENCOMIENDA</h2><p>La persona que solicita el envÃ­o declara que:</p><ul><li>Tiene derecho a disponer del objeto.</li><li>El contenido es legal.</li><li>La descripciÃ³n facilitada es correcta.</li><li>El embalaje es adecuado.</li><li>El objeto puede transportarse de manera segura.</li><li>Ha informado sobre cualquier caracterÃ­stica especial.</li></ul><p>El remitente serÃ¡ responsable de un embalaje insuficiente o inadecuado cuando el daÃ±o derive directamente de dicha circunstancia.</p><p>MeTransfers podrÃ¡ solicitar informaciÃ³n adicional antes de aceptar una encomienda.</p><h2>22. ENTREGA DE ENCOMIENDAS</h2><p>El cliente deberÃ¡ facilitar:</p><ul><li>Nombre del remitente.</li><li>TelÃ©fono.</li><li>DirecciÃ³n de recogida.</li><li>Nombre del destinatario.</li><li>TelÃ©fono del destinatario.</li><li>DirecciÃ³n correcta de entrega.</li><li>InformaciÃ³n necesaria para localizar al destinatario.</li></ul><p>El destinatario deberÃ¡ encontrarse disponible para recibir la encomienda.</p><p>Si la entrega no puede completarse por ausencia del destinatario, direcciÃ³n incorrecta o informaciÃ³n insuficiente, podrÃ¡n generarse costes adicionales de espera, segunda entrega o devoluciÃ³n.</p><h2>23. MODIFICACIÃ“N DE UNA RESERVA</h2><p>Cualquier modificaciÃ³n deberÃ¡ solicitarse lo antes posible.</p><p>PodrÃ¡n considerarse modificaciones:</p><ul><li>Cambio de fecha.</li><li>Cambio de hora.</li><li>Cambio de origen.</li><li>Cambio de destino.</li><li>IncorporaciÃ³n de paradas.</li><li>Aumento del nÃºmero de pasajeros.</li><li>Cambio de vehÃ­culo.</li><li>Cambio relevante de equipaje.</li><li>Cambio de itinerario.</li><li>Cambio en las caracterÃ­sticas de una encomienda.</li></ul><p>MeTransfers confirmarÃ¡ si el cambio puede realizarse.</p><p>La modificaciÃ³n podrÃ¡ implicar un cambio de precio.</p><p>Una modificaciÃ³n no se considerarÃ¡ aceptada hasta que MeTransfers la confirme.</p><h2>24. CANCELACIONES POR PARTE DEL CLIENTE</h2><p>El cliente podrÃ¡ solicitar la cancelaciÃ³n por los canales de contacto indicados por MeTransfers.</p><p>La polÃ­tica concreta de cancelaciÃ³n aplicable serÃ¡ la mostrada durante la reserva, presupuesto o confirmaciÃ³n del servicio.</p><p>Cuando existan costes de terceros ya contratados, entradas no reembolsables, servicios especiales, vehÃ­culos bloqueados expresamente para el cliente u otros gastos previamente informados, estos podrÃ¡n afectar al importe reembolsable.</p><p>MeTransfers no aplicarÃ¡ condiciones que reduzcan los derechos irrenunciables reconocidos por la legislaciÃ³n de consumidores.</p><h2>25. DERECHO DE DESISTIMIENTO</h2><p>Por la naturaleza de determinados servicios contratados para una fecha, hora o periodo especÃ­fico, incluidos determinados servicios de transporte, turÃ­sticos o de transporte de bienes, el derecho general de desistimiento previsto para otras contrataciones a distancia puede no resultar aplicable cuando asÃ­ lo establezca la legislaciÃ³n.</p><p>En estos casos serÃ¡n de aplicaciÃ³n las condiciones de cancelaciÃ³n informadas durante el proceso de contrataciÃ³n, sin perjuicio de los derechos irrenunciables que correspondan legalmente al consumidor.</p><h2>26. CANCELACIÃ“N POR PARTE DE METRANSFERS</h2><p>En circunstancias excepcionales, MeTransfers podrÃ¡ verse obligado a cancelar o modificar un servicio.</p><p>PodrÃ¡n considerarse, entre otras:</p><ul><li>AverÃ­as imprevistas.</li><li>Accidentes.</li><li>Problemas de seguridad.</li><li>Carreteras cerradas.</li><li>FenÃ³menos meteorolÃ³gicos severos.</li><li>Restricciones oficiales.</li><li>Huelgas.</li><li>Situaciones de fuerza mayor.</li><li>Imposibilidad legal de prestar el servicio.</li><li>Circunstancias extraordinarias fuera del control razonable de MeTransfers.</li></ul><p>Cuando resulte posible, MeTransfers intentarÃ¡:</p><ul><li>Proporcionar un vehÃ­culo alternativo.</li><li>Reorganizar el horario.</li><li>Proponer una alternativa razonable.</li><li>Utilizar un colaborador autorizado.</li><li>Reembolsar, cuando corresponda, el servicio que finalmente no pueda prestarse.</li></ul><h2>27. VEHÃCULOS Y COLABORADORES</h2><p>MeTransfers podrÃ¡ prestar determinados servicios utilizando vehÃ­culos propios o mediante conductores, operadores o empresas colaboradoras legalmente habilitadas cuando sea necesario para ejecutar la reserva.</p><p>La asignaciÃ³n de un vehÃ­culo concreto dependerÃ¡ de la disponibilidad.</p><p>Las imÃ¡genes de vehÃ­culos mostradas en la web tienen finalidad orientativa salvo que se indique expresamente lo contrario.</p><p>MeTransfers procurarÃ¡ asignar un vehÃ­culo de la categorÃ­a contratada o de caracterÃ­sticas equivalentes o superiores cuando resulte necesario realizar una sustituciÃ³n.</p><h2>28. CONDUCTA DE LOS PASAJEROS</h2><p>Los pasajeros deberÃ¡n mantener una conducta respetuosa y compatible con la seguridad.</p><p>No se permitirÃ¡:</p><ul><li>Fumar cuando estÃ© prohibido.</li><li>Consumir sustancias ilegales.</li><li>DaÃ±ar el vehÃ­culo.</li><li>Amenazar o agredir al conductor.</li><li>Distraer peligrosamente al conductor.</li><li>Manipular elementos del vehÃ­culo sin autorizaciÃ³n.</li><li>Transportar objetos ilegales.</li><li>Mantener conductas que comprometan la seguridad.</li></ul><p>El conductor podrÃ¡ detener o rechazar un servicio si existe un riesgo real para la seguridad de pasajeros, conductor, vehÃ­culo o terceros.</p><p>Los daÃ±os causados intencionadamente o por conducta negligente del cliente podrÃ¡n ser reclamados al responsable conforme a la legislaciÃ³n aplicable.</p><h2>29. ALCOHOL Y SUSTANCIAS</h2><p>MeTransfers podrÃ¡ negarse a transportar a una persona cuyo estado represente un riesgo razonable para:</p><ul><li>El conductor.</li><li>Otros pasajeros.</li><li>El vehÃ­culo.</li><li>Terceros.</li><li>La propia persona.</li></ul><p>La decisiÃ³n deberÃ¡ fundamentarse en razones de seguridad y no podrÃ¡ realizarse de manera discriminatoria.</p><h2>30. OBJETOS OLVIDADOS</h2><p>El cliente es responsable de sus pertenencias personales.</p><p>Si se localiza un objeto olvidado, MeTransfers intentarÃ¡ facilitar su devoluciÃ³n.</p><p>La entrega o envÃ­o posterior del objeto podrÃ¡ generar gastos razonables de desplazamiento, mensajerÃ­a o gestiÃ³n.</p><p>El cliente deberÃ¡ comunicar la pÃ©rdida tan pronto como sea posible e identificar suficientemente el objeto.</p><h2>31. CIRCUNSTANCIAS EXTRAORDINARIAS</h2><p>MeTransfers no puede garantizar que todos los servicios se desarrollen exactamente segÃºn el horario inicialmente estimado cuando se produzcan circunstancias fuera de su control razonable.</p><p>Entre ellas pueden encontrarse:</p><ul><li>TrÃ¡fico extraordinario.</li><li>Accidentes.</li><li>Cierre de carreteras.</li><li>Huelgas.</li><li>Manifestaciones.</li><li>Eventos multitudinarios.</li><li>ClimatologÃ­a adversa.</li><li>Emergencias.</li><li>Actuaciones policiales.</li><li>Decisiones administrativas.</li><li>Restricciones aeroportuarias o portuarias.</li><li>Cancelaciones o retrasos de servicios de terceros.</li></ul><p>MeTransfers adoptarÃ¡ medidas razonables para minimizar el impacto cuando resulte posible.</p><h2>32. RESPONSABILIDAD</h2><p>MeTransfers serÃ¡ responsable del cumplimiento de sus obligaciones en los tÃ©rminos establecidos por la legislaciÃ³n aplicable.</p><p>Nada de lo previsto en estas condiciones pretende excluir o limitar derechos que legalmente no puedan ser excluidos o limitados.</p><p>MeTransfers no responderÃ¡ de pÃ©rdidas derivadas exclusivamente de informaciÃ³n incorrecta facilitada por el cliente, incumplimiento de las instrucciones de recogida, retrasos imputables al propio cliente o circunstancias externas que razonablemente no pudieran evitarse, salvo que la legislaciÃ³n aplicable disponga otra cosa.</p><h2>33. RESERVAS REALIZADAS POR EMPRESAS, HOTELES O TERCEROS</h2><p>Cuando una reserva sea realizada por:</p><ul><li>Una empresa.</li><li>Un hotel.</li><li>Una agencia.</li><li>Un organizador.</li><li>Un asistente.</li><li>Una persona diferente al pasajero.</li></ul><p>la persona o entidad contratante deberÃ¡ proporcionar informaciÃ³n correcta y comunicar al pasajero las condiciones relevantes del servicio.</p><p>La relaciÃ³n de facturaciÃ³n podrÃ¡ mantenerse con la persona o entidad que haya realizado la contrataciÃ³n segÃºn lo acordado.</p><h2>34. FACTURACIÃ“N</h2><p>Cuando el cliente necesite factura deberÃ¡ facilitar los datos fiscales correctos.</p><p>La factura podrÃ¡ emitirse electrÃ³nicamente cuando resulte legalmente permitido.</p><p>Los datos proporcionados para facturaciÃ³n deberÃ¡n ser veraces.</p><h2>35. PROTECCIÃ“N DE DATOS</h2><p>Los datos personales facilitados durante el proceso de reserva serÃ¡n tratados para gestionar:</p><ul><li>Solicitudes.</li><li>Presupuestos.</li><li>Reservas.</li><li>Pagos.</li><li>AtenciÃ³n al cliente.</li><li>Comunicaciones relacionadas con el servicio.</li><li>FacturaciÃ³n.</li><li>GestiÃ³n de incidencias.</li><li>Cumplimiento de obligaciones legales.</li></ul><p>El tratamiento de datos se realizarÃ¡ conforme a la PolÃ­tica de Privacidad publicada por MeTransfers y a la normativa aplicable en materia de protecciÃ³n de datos.</p><p>La PolÃ­tica de Privacidad forma parte de la informaciÃ³n legal disponible para el usuario.</p><h2>36. COMUNICACIONES ELECTRÃ“NICAS</h2><p>El cliente acepta que las comunicaciones relativas a su reserva puedan realizarse utilizando los datos de contacto facilitados.</p><p>PodrÃ¡n utilizarse, segÃºn corresponda:</p><ul><li>Correo electrÃ³nico.</li><li>SMS.</li><li>WhatsApp.</li><li>Llamadas telefÃ³nicas.</li><li>Notificaciones del sistema de reservas.</li></ul><p>Es responsabilidad del cliente proporcionar informaciÃ³n de contacto vÃ¡lida.</p><p>Las comunicaciones comerciales distintas de las necesarias para ejecutar una reserva se gestionarÃ¡n de acuerdo con la normativa aplicable y las preferencias de consentimiento del usuario.</p><h2>37. RECLAMACIONES</h2><p>Si el cliente considera que ha existido una incidencia, deberÃ¡ contactar con MeTransfers aportando, cuando sea posible:</p><ul><li>NÃºmero de reserva.</li><li>Nombre.</li><li>Fecha del servicio.</li><li>ExplicaciÃ³n de la incidencia.</li><li>DocumentaciÃ³n relevante.</li></ul><p>Las reclamaciones podrÃ¡n dirigirse a:</p><p><a href="mailto:info@metransfers.es">info@metransfers.es</a></p><p>MeTransfers analizarÃ¡ cada caso y responderÃ¡ de acuerdo con las circunstancias y la legislaciÃ³n aplicable.</p><h2>38. IDIOMA DE CONTRATACIÃ“N</h2><p>Las condiciones podrÃ¡n ponerse a disposiciÃ³n del cliente en distintos idiomas.</p><p>En caso de discrepancia entre traducciones, se atenderÃ¡ a la versiÃ³n legalmente aplicable y a los derechos que correspondan al consumidor.</p><p>MeTransfers procurarÃ¡ que la informaciÃ³n esencial de la reserva sea facilitada en el idioma utilizado durante el proceso de contrataciÃ³n cuando dicho idioma se encuentre disponible.</p><h2>39. NULIDAD PARCIAL</h2><p>Si alguna disposiciÃ³n de estos TÃ©rminos y Condiciones fuera declarada nula, invÃ¡lida o inaplicable, dicha circunstancia no afectarÃ¡ automÃ¡ticamente a la validez del resto de las condiciones.</p><p>La clÃ¡usula afectada se interpretarÃ¡ o sustituirÃ¡, cuando legalmente resulte posible, de manera compatible con la finalidad original y la normativa vigente.</p><h2>40. MODIFICACIÃ“N DE LOS TÃ‰RMINOS</h2><p>MeTransfers podrÃ¡ actualizar estos TÃ©rminos y Condiciones cuando sea necesario por:</p><ul><li>Cambios legislativos.</li><li>Cambios operativos.</li><li>Nuevos servicios.</li><li>Cambios tecnolÃ³gicos.</li><li>Mejoras en el sistema de reservas.</li></ul><p>Las condiciones aplicables a una reserva serÃ¡n, con carÃ¡cter general, las que se encontraban vigentes y fueron aceptadas por el cliente en el momento de realizar la contrataciÃ³n, salvo modificaciones posteriores que resulten obligatorias legalmente o hayan sido expresamente aceptadas.</p><h2>41. LEGISLACIÃ“N APLICABLE</h2><p>La relaciÃ³n contractual se regirÃ¡ por la legislaciÃ³n espaÃ±ola y por la normativa europea que resulte aplicable.</p><p>Cuando el cliente tenga la consideraciÃ³n legal de consumidor o usuario, cualquier controversia se resolverÃ¡ ante los Ã³rganos competentes determinados por la normativa de protecciÃ³n de consumidores y las reglas legales de competencia territorial.</p><p>Nada de estas condiciones pretende obligar al consumidor a renunciar al fuero o a los derechos que le reconozca imperativamente la legislaciÃ³n aplicable.</p><h2>42. DECLARACIÃ“N FINAL DE ACEPTACIÃ“N</h2><p><strong>AL REALIZAR UNA RESERVA CON METRANSFERS, EL CLIENTE DECLARA HABER LEÃDO, COMPRENDIDO Y ACEPTADO ESTOS TÃ‰RMINOS Y CONDICIONES.</strong></p><p>La aceptaciÃ³n se aplica a la contrataciÃ³n de:</p><p>TRASLADOS Â· TRANSFERS Â· TOURS Â· EXCURSIONES Â· SERVICIOS PRIVADOS Â· SERVICIOS CORPORATIVOS Â· ENCOMIENDAS Y DEMÃS SERVICIOS CONFIRMADOS POR METRANSFERS.</p><p>El cliente entiende que la reserva queda sujeta a las caracterÃ­sticas, precio y condiciones particulares mostradas o comunicadas antes de finalizar la contrataciÃ³n.</p><p>Cuando la reserva implique un pago, el cliente reconoce expresamente que su confirmaciÃ³n genera una obligaciÃ³n de pago.</p><h3>TEXTO DE ACEPTACIÃ“N RECOMENDADO PARA EL FORMULARIO DE RESERVA</h3><p>He leÃ­do y acepto los TÃ©rminos y Condiciones de contrataciÃ³n y la PolÃ­tica de Privacidad de MeTransfers. Entiendo y acepto las condiciones aplicables al traslado, tour, encomienda o servicio que estoy contratando.</p><p>Cuando exista pago online, el botÃ³n final deberÃ¡ indicar de forma inequÃ­voca que la acciÃ³n genera una obligaciÃ³n de pago, por ejemplo: <strong>RESERVAR Y PAGAR</strong> o <strong>CONFIRMAR RESERVA CON OBLIGACIÃ“N DE PAGO</strong></p>';
        
        wp_update_post( array(
            'ID' => $terminos->ID,
            'post_content' => $terminos_content
        ) );
    }

    update_option( 'mt_legal_pages_fixed_v2', true );
}

// add_action( 'admin_init', 'mt_fix_legal_pages_privacy_and_cookies_v3' );
function mt_fix_legal_pages_privacy_and_cookies_v3() {
    if ( get_option( 'mt_legal_pages_fixed_v3' ) ) {
        return;
    }

    $privacidad = get_page_by_path( 'politica-de-privacidad', OBJECT, 'page' );
    if ( $privacidad instanceof WP_Post ) {
        $privacidad_content = '<h2>POLÃTICA DE PRIVACIDAD</h2><p><strong>Ãšltima actualizaciÃ³n: 18 de agosto de 2026</strong></p><p>En <strong>METRANSFERS GESTION SL</strong> (en adelante, "MeTransfers" o "nosotros"), estamos comprometidos con la protecciÃ³n y el respeto de tu privacidad. Esta PolÃ­tica de Privacidad explica cÃ³mo recopilamos, utilizamos, compartimos y protegemos la informaciÃ³n personal que nos proporcionas cuando utilizas nuestro sitio web (metransfers.es) y nuestros servicios de transporte, en cumplimiento con el Reglamento (UE) 2016/679 del Parlamento Europeo y del Consejo (RGPD) y la Ley OrgÃ¡nica 3/2018 de ProtecciÃ³n de Datos Personales y garantÃ­a de los derechos digitales (LOPDGDD).</p><h2>1. RESPONSABLE DEL TRATAMIENTO</h2><p>El responsable del tratamiento de los datos personales recopilados es:</p><ul><li><strong>Titular:</strong> METRANSFERS GESTION SL</li><li><strong>NIF:</strong> B22522353</li><li><strong>Domicilio social:</strong> Avda. Mare de DÃ©u de Montserrat, nÃºm. 18, planta 5, puerta 2, 08970 Sant Joan DespÃ­, Barcelona, EspaÃ±a.</li><li><strong>Correo electrÃ³nico:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></li><li><strong>TelÃ©fono:</strong> +34 662 02 41 36</li></ul><h2>2. QUÃ‰ DATOS PERSONALES RECOPILAMOS</h2><p>Para poder prestarte nuestros servicios de transporte y gestionar tus reservas, podemos recopilar los siguientes datos personales:</p><ul><li><strong>Datos identificativos:</strong> Nombre, apellidos.</li><li><strong>Datos de contacto:</strong> Correo electrÃ³nico, nÃºmero de telÃ©fono.</li><li><strong>Datos del servicio:</strong> Direcciones de recogida y destino, detalles de vuelos o cruceros, fechas, horarios y necesidades especiales de transporte.</li><li><strong>Datos econÃ³micos y de pago:</strong> InformaciÃ³n necesaria para procesar los pagos (gestionados de forma segura a travÃ©s de pasarelas de pago de terceros; no almacenamos datos completos de tarjetas de crÃ©dito).</li><li><strong>Datos de navegaciÃ³n:</strong> DirecciÃ³n IP, tipo de navegador, pÃ¡ginas visitadas en nuestro sitio web (consulta nuestra PolÃ­tica de Cookies).</li></ul><h2>3. FINALIDAD DEL TRATAMIENTO DE TUS DATOS</h2><p>Utilizamos tus datos personales para las siguientes finalidades:</p><ul><li><strong>GestiÃ³n de reservas:</strong> Procesar, confirmar y ejecutar los servicios de traslado, excursiones o transporte contratados.</li><li><strong>AtenciÃ³n al cliente:</strong> Responder a tus consultas, dudas, quejas o solicitudes de presupuesto.</li><li><strong>GestiÃ³n contable y administrativa:</strong> FacturaciÃ³n y cumplimiento de nuestras obligaciones legales y fiscales.</li><li><strong>Comunicaciones operativas:</strong> Enviarte correos o mensajes (SMS/WhatsApp) estrictamente relacionados con el servicio contratado (ej. confirmaciones, avisos del conductor, cambios de horario).</li><li><strong>Comunicaciones comerciales:</strong> Solo si has dado tu consentimiento expreso, enviarte informaciÃ³n sobre ofertas, nuevos servicios o noticias relevantes de MeTransfers.</li></ul><h2>4. BASE LEGITIMADORA DEL TRATAMIENTO</h2><p>El tratamiento de tus datos se basa en las siguientes bases legales:</p><ul><li><strong>EjecuciÃ³n del contrato:</strong> El tratamiento es estrictamente necesario para gestionar y prestar los servicios de transporte que nos has solicitado.</li><li><strong>ObligaciÃ³n legal:</strong> Para cumplir con la normativa fiscal, contable y administrativa aplicable.</li><li><strong>Consentimiento:</strong> Para el envÃ­o de comunicaciones comerciales y la instalaciÃ³n de cookies no tÃ©cnicas (cuando lo hayas aceptado). Puedes retirar tu consentimiento en cualquier momento.</li><li><strong>InterÃ©s legÃ­timo:</strong> Para mejorar nuestros servicios, garantizar la seguridad de la web y prevenir fraudes.</li></ul><h2>5. CONSERVACIÃ“N DE LOS DATOS</h2><p>Conservaremos tus datos personales Ãºnicamente durante el tiempo necesario para cumplir con las finalidades para las que fueron recopilados. Una vez finalizada la relaciÃ³n contractual, los datos se mantendrÃ¡n debidamente bloqueados durante los plazos de prescripciÃ³n legal exigidos (generalmente, hasta 5 aÃ±os para responsabilidades civiles y 6 aÃ±os para obligaciones contables y fiscales). Pasado este tiempo, procederemos a su eliminaciÃ³n segura.</p><h2>6. COMUNICACIÃ“N DE DATOS A TERCEROS</h2><p>Tus datos no serÃ¡n vendidos, alquilados ni cedidos a terceros, salvo en los siguientes casos en los que es estrictamente necesario:</p><ul><li><strong>Conductores y empresas colaboradoras:</strong> Para poder efectuar el servicio de traslado, necesitamos compartir tu nombre, telÃ©fono y detalles de la ruta con el conductor asignado.</li><li><strong>Proveedores de servicios (Encargados de Tratamiento):</strong> Empresas que nos prestan servicios tecnolÃ³gicos, pasarelas de pago seguro, gestorÃ­as contables o servicios de alojamiento web, los cuales estÃ¡n sujetos a estrictos acuerdos de confidencialidad.</li><li><strong>Administraciones PÃºblicas:</strong> Cuando exista una obligaciÃ³n legal de facilitar informaciÃ³n a las autoridades competentes, fuerzas y cuerpos de seguridad o tribunales.</li></ul><h2>7. TUS DERECHOS</h2><p>La normativa de protecciÃ³n de datos te otorga los siguientes derechos sobre tu informaciÃ³n personal:</p><ul><li><strong>Acceso:</strong> Conocer quÃ© datos personales tenemos sobre ti y cÃ³mo los tratamos.</li><li><strong>RectificaciÃ³n:</strong> Solicitar la correcciÃ³n de datos inexactos o incompletos.</li><li><strong>SupresiÃ³n (Derecho al olvido):</strong> Solicitar la eliminaciÃ³n de tus datos cuando, entre otros motivos, ya no sean necesarios para los fines que fueron recogidos.</li><li><strong>OposiciÃ³n:</strong> Oponerte al tratamiento de tus datos para fines especÃ­ficos (por ejemplo, marketing).</li><li><strong>LimitaciÃ³n del tratamiento:</strong> Solicitar que restrinjamos el uso de tus datos bajo ciertas condiciones.</li><li><strong>Portabilidad:</strong> Recibir tus datos en un formato estructurado y transferirlos a otro responsable.</li></ul><p>Puedes ejercer cualquiera de estos derechos enviando un correo electrÃ³nico a <strong><a href="mailto:info@metransfers.es">info@metransfers.es</a></strong>, adjuntando una copia de tu DNI o documento equivalente para verificar tu identidad. Si consideras que no hemos atendido correctamente tus derechos, puedes presentar una reclamaciÃ³n ante la Agencia EspaÃ±ola de ProtecciÃ³n de Datos (AEPD).</p><h2>8. SEGURIDAD DE LOS DATOS</h2><p>En MeTransfers aplicamos las medidas tÃ©cnicas y organizativas adecuadas para garantizar un nivel de seguridad Ã³ptimo y proteger tus datos personales contra el acceso no autorizado, pÃ©rdida, destrucciÃ³n o alteraciÃ³n accidental. Nuestro sitio web utiliza un certificado SSL para garantizar que la transmisiÃ³n de datos entre tu navegador y nuestros servidores estÃ© cifrada.</p><h2>9. CAMBIOS EN ESTA POLÃTICA</h2><p>Nos reservamos el derecho de modificar esta PolÃ­tica de Privacidad para adaptarla a novedades legislativas o cambios en nuestras prÃ¡cticas. Te recomendamos revisar esta pÃ¡gina periÃ³dicamente. Si realizamos cambios sustanciales, te lo notificaremos a travÃ©s del sitio web o por correo electrÃ³nico.</p>';
        
        wp_update_post( array(
            'ID' => $privacidad->ID,
            'post_content' => $privacidad_content
        ) );
    }

    $cookie = get_page_by_path( 'politica-de-cookies', OBJECT, 'page' );
    if ( $cookie instanceof WP_Post ) {
        $cookie_content = '<h2>POLÃTICA DE COOKIES</h2><p><strong>Ãšltima actualizaciÃ³n: 18 de agosto de 2026</strong></p><p>Esta PolÃ­tica de Cookies explica quÃ© son las cookies, cÃ³mo las utilizamos en el sitio web <strong>metransfers.es</strong>, gestionado por <strong>METRANSFERS GESTION SL</strong>, y cÃ³mo puedes controlarlas, en cumplimiento con la Ley 34/2002, de Servicios de la Sociedad de la InformaciÃ³n y de Comercio ElectrÃ³nico (LSSI-CE) y las normativas europeas sobre privacidad.</p><h2>1. Â¿QUÃ‰ SON LAS COOKIES?</h2><p>Las cookies son pequeÃ±os archivos de texto que se descargan y almacenan en el dispositivo del usuario (ordenador, smartphone, tablet, etc.) al acceder a determinadas pÃ¡ginas web. Permiten a la pÃ¡gina web, entre otras cosas, almacenar y recuperar informaciÃ³n sobre los hÃ¡bitos de navegaciÃ³n de un usuario o de su equipo y, dependiendo de la informaciÃ³n que contengan y de la forma en que utilice su equipo, pueden utilizarse para reconocer al usuario y mejorar su experiencia.</p><h2>2. TIPOS DE COOKIES QUE UTILIZAMOS</h2><p>En metransfers.es utilizamos las siguientes categorÃ­as de cookies:</p><h3>2.1. Cookies TÃ©cnicas o Estrictamente Necesarias</h3><p>Son aquellas esenciales para el correcto funcionamiento del sitio web y no pueden ser desactivadas en nuestros sistemas. Permiten funciones bÃ¡sicas como la navegaciÃ³n por la pÃ¡gina, el acceso a Ã¡reas seguras, la realizaciÃ³n del proceso de reserva y el funcionamiento del carrito o formulario de pago. TambiÃ©n incluyen las cookies que recuerdan tus preferencias de privacidad (tu decisiÃ³n sobre quÃ© cookies aceptas).</p><h3>2.2. Cookies de Rendimiento y AnÃ¡lisis</h3><p>Son aquellas que nos permiten cuantificar el nÃºmero de usuarios y realizar la mediciÃ³n y anÃ¡lisis estadÃ­stico de la utilizaciÃ³n que hacen del servicio ofertado. Analizamos tu navegaciÃ³n en nuestra pÃ¡gina web con el fin de mejorar la oferta de servicios que te ofrecemos y optimizar el diseÃ±o de la web. Toda la informaciÃ³n que recogen estas cookies es agregada y, por lo tanto, anÃ³nima.</p><h3>2.3. Cookies de PersonalizaciÃ³n</h3><p>Son aquellas que permiten recordar informaciÃ³n para que el usuario acceda al servicio con determinadas caracterÃ­sticas que pueden diferenciar su experiencia de la de otros usuarios, como, por ejemplo, el idioma, el aspecto o contenido del servicio en funciÃ³n del tipo de navegador o la regiÃ³n desde la que se accede.</p><h2>3. COOKIES DE TERCEROS</h2><p>Nuestro sitio web puede utilizar servicios de terceros que, por cuenta de METRANSFERS GESTION SL, recopilarÃ¡n informaciÃ³n con fines estadÃ­sticos y de uso del sitio. En particular, este sitio web podrÃ­a utilizar herramientas como Google Analytics para ayudar al website a analizar el uso que hacen los usuarios del sitio. Estas cookies son gestionadas por las respectivas entidades proveedoras, y sus polÃ­ticas de privacidad y uso de cookies son externas a nosotros.</p><p><em>(Nota: Actualmente nuestra web estÃ¡ configurada para respetar tus preferencias mediante un aviso de cookies, y las etiquetas de seguimiento no esenciales no se cargan sin tu consentimiento).</em></p><h2>4. CONSENTIMIENTO Y CONTROL DE LAS COOKIES</h2><p>Al acceder por primera vez a metransfers.es, se muestra un panel de gestiÃ³n de cookies que permite aceptar todas las cookies, rechazar las cookies no esenciales o configurar individualmente las categorÃ­as disponibles.</p><p>Las cookies de analÃ­tica y marketing permanecen desactivadas hasta que el usuario presta su consentimiento mediante una acciÃ³n expresa en el panel. La navegaciÃ³n por la web, el desplazamiento vertical (scroll) o los clics fuera del panel no se consideran consentimiento.</p><p>La elecciÃ³n del usuario se almacena durante 14 dÃ­as. Transcurrido ese plazo, el panel podrÃ¡ mostrarse nuevamente para solicitar la renovaciÃ³n de las preferencias.</p><h2>5. CÃ“MO DESACTIVAR O ELIMINAR LAS COOKIES DESDE TU NAVEGADOR</h2><p>En cualquier momento, puedes permitir, bloquear o eliminar las cookies instaladas en tu equipo mediante la configuraciÃ³n de las opciones del navegador que utilices. Ten en cuenta que si desactivas las cookies tÃ©cnicas o necesarias, es posible que no puedas acceder a ciertas secciones de la web o completar el proceso de reserva.</p><p>A continuaciÃ³n, te ofrecemos enlaces donde puedes encontrar informaciÃ³n sobre cÃ³mo configurar las cookies en los principales navegadores:</p><ul><li><strong>Google Chrome:</strong> <a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Configurar cookies en Chrome</a></li><li><strong>Mozilla Firefox:</strong> <a href="https://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-sitios-web-rastrear-preferencias" target="_blank" rel="noopener">Configurar cookies en Firefox</a></li><li><strong>Apple Safari:</strong> <a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Configurar cookies en Safari</a></li><li><strong>Microsoft Edge:</strong> <a href="https://support.microsoft.com/es-es/microsoft-edge/eliminar-las-cookies-en-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener">Configurar cookies en Edge</a></li></ul><h2>6. ACTUALIZACIONES DE LA POLÃTICA DE COOKIES</h2><p>Es posible que actualicemos la PolÃ­tica de Cookies de nuestro sitio web, por lo que te recomendamos revisar esta polÃ­tica cada vez que accedas a <strong>metransfers.es</strong> con el objetivo de estar adecuadamente informado sobre cÃ³mo y para quÃ© usamos las cookies.</p>';
        
        wp_update_post( array(
            'ID' => $cookie->ID,
            'post_content' => $cookie_content
        ) );
    }

    update_option( 'mt_legal_pages_fixed_v3', true );
}
// =========================================================================
// AUTO-CREAR PÃGINA DE BLOG (UNA SOLA VEZ)
// =========================================================================
// [MIGRACIÃ“N MANUAL] Descomentar y ejecutar una sola vez si necesitas crear la pÃ¡gina de blog:
// add_action( 'init', 'mt_auto_create_blog_page' );
function mt_auto_create_blog_page() {
    if ( get_option( 'mt_blog_page_created_v2' ) ) {
        return;
    }

    $blog_page = get_page_by_path( 'blog' );
    $page_id = 0;

    if ( ! $blog_page ) {
        $page_id = wp_insert_post( array(
            'post_title'   => 'Blog',
            'post_name'    => 'blog',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page'
        ) );
    } else {
        $page_id = $blog_page->ID;
    }

    if ( $page_id && ! is_wp_error( $page_id ) ) {
        // Asegurarse de que estÃ© asignada como la pÃ¡gina de entradas
        update_option( 'show_on_front', 'page' );
        update_option( 'page_for_posts', $page_id );
    }

    update_option( 'mt_blog_page_created_v2', true );
}

// =========================================================================
// AUTO-ACTUALIZAR CONTENIDO DE ENTRADAS DE RUTAS (UNA SOLA VEZ)
// =========================================================================
// [MIGRACIÃ“N MANUAL] Hook desactivado. Si necesitas actualizar masivamente,
// descomenta y vuelve a comentar despuÃ©s de la ejecuciÃ³n.
// add_action( 'init', 'mt_auto_update_routes_content' );
function mt_auto_update_routes_content() {
    if ( get_option( 'mt_routes_content_updated_v1' ) ) {
        return;
    }

    $routes_content = array(
        'ruta-de-juego-de-tronos-tour-desde-barcelona-a-girona' => '
            <h2>Descubre Desembarco del Rey y Braavos en la vida real</h2>
            <p>Si eres un verdadero fanÃ¡tico de <strong>Juego de Tronos</strong>, este tour privado desde Barcelona a Girona es una experiencia obligatoria. La ciudad de Girona, con su impresionante arquitectura medieval y su historia milenaria, fue elegida por HBO para representar algunos de los escenarios mÃ¡s icÃ³nicos de la sexta temporada de la serie.</p>
            <p>Con nuestro servicio de <em>traslado privado premium</em>, te recogeremos directamente en tu hotel en Barcelona y te llevaremos en un vehÃ­culo de alta gama (Mercedes-Benz) hacia esta joya de CataluÃ±a, garantizando un viaje cÃ³modo, seguro y con estilo.</p>
            
            <h3>Â¿QuÃ© verÃ¡s en nuestro tour por Girona?</h3>
            <p>Una vez en Girona, te adentrarÃ¡s en las calles empedradas que dieron vida a las Ciudades Libres y a la capital de los Siete Reinos:</p>
            <ul>
                <li><strong>La Catedral de Santa MarÃ­a (El Gran Septo de Baelor):</strong> Sube la majestuosa escalinata donde la Reina Margaery iba a realizar su Paseo de la VergÃ¼enza antes de la intervenciÃ³n de Jaime Lannister.</li>
                <li><strong>El Barrio JudÃ­o (Call Jueu):</strong> PiÃ©rdete por el laberinto de callejuelas estrechas que se transformaron en las calles de Braavos, donde Arya Stark entrenÃ³ con la NiÃ±a Abandonada.</li>
                <li><strong>Los BaÃ±os Ãrabes y la Plaza de los Jurados:</strong> Escenarios que sirvieron como telÃ³n de fondo para el teatro callejero en Braavos y los rincones oscuros donde Arya fue perseguida.</li>
                <li><strong>El Monasterio de Sant Pere de Galligants:</strong> Que en la ficciÃ³n albergÃ³ la Ciudadela de Antigua, donde Samwell Tarly acudiÃ³ para formarse como Maestre.</li>
            </ul>

            <h3>Una experiencia gastronÃ³mica y cultural</h3>
            <p>MÃ¡s allÃ¡ de Juego de Tronos, Girona es un destino culinario de primer nivel mundial (hogar de El Celler de Can Roca). Durante el tour, tendrÃ¡s tiempo libre para disfrutar de la excelente gastronomÃ­a local en sus encantadores restaurantes, pasear por la muralla carolingia o admirar las coloridas casas sobre el rÃ­o Onyar.</p>
            
            <h3>Ventajas de nuestro traslado privado</h3>
            <ul>
                <li><strong>Flexibilidad total:</strong> TÃº marcas los tiempos. Sin las prisas de los tours en grupo.</li>
                <li><strong>Confort garantizado:</strong> VehÃ­culos climatizados, Wi-Fi a bordo y agua de cortesÃ­a.</li>
                <li><strong>ChÃ³fer profesional:</strong> Discreto, puntual y gran conocedor de las rutas de CataluÃ±a.</li>
            </ul>
            
            <p>No pierdas la oportunidad de vivir tu propia aventura Ã©pica. <a href="/contacto">Reserva hoy tu traslado privado a Girona</a> y siÃ©ntete como un verdadero Stark o Lannister recorriendo las calles de Poniente.</p>
        ',
        'tour-privado-por-los-pueblos-medievales-de-cataluna-desde-barcelona' => '
            <h2>Un viaje en el tiempo a la CataluÃ±a Medieval</h2>
            <p>Escapa del bullicio de Barcelona y embÃ¡rcate en un viaje en el tiempo a travÃ©s de los <strong>pueblos medievales mÃ¡s encantadores de CataluÃ±a</strong>. Nuestro tour privado te llevarÃ¡ a descubrir joyas arquitectÃ³nicas, calles de piedra y castillos de cuento de hadas en la Costa Brava y el EmpordÃ , todo con la exclusividad y el confort de nuestros vehÃ­culos de lujo.</p>
            
            <h3>Itinerario destacado de la ruta medieval</h3>
            <p>Esta ruta estÃ¡ diseÃ±ada para mostrarte la esencia histÃ³rica de la regiÃ³n, visitando pueblos que han conservado intacto su trazado original durante siglos:</p>
            <ul>
                <li><strong>Rupit:</strong> Un pueblo de postal escondido entre montaÃ±as, famoso por su puente colgante de madera y sus robustas casas de piedra de los siglos XVI y XVII.</li>
                <li><strong>BesalÃº:</strong> Te darÃ¡ la bienvenida con su espectacular e icÃ³nico puente romÃ¡nico fortificado del siglo XI sobre el rÃ­o FluviÃ . Su juderÃ­a y sus baÃ±os purificadores (mikvÃ©) son Ãºnicos en Europa.</li>
                <li><strong>Peratallada:</strong> Declarado conjunto histÃ³rico-artÃ­stico, es considerado uno de los nÃºcleos medievales mejor conservados de CataluÃ±a, con su foso excavado en la roca y su castillo-palacio.</li>
                <li><strong>Pals:</strong> Un recinto gÃ³tico impecable situado en lo alto de una colina, con vistas panorÃ¡micas a las Islas Medas y rodeado de arrozales.</li>
            </ul>

            <h3>Disfruta del paisaje y la gastronomÃ­a local</h3>
            <p>El trayecto entre estos pueblos es en sÃ­ mismo una experiencia, recorriendo sinuosas carreteras entre densos bosques de pinos, campos de cultivo y masÃ­as tradicionales catalanas. AdemÃ¡s, esta regiÃ³n es cÃ©lebre por su rica gastronomÃ­a. PodrÃ¡s degustar platos tÃ­picos como carnes a la brasa, embutidos artesanales y vinos locales de la DO EmpordÃ  en restaurantes rÃºsticos de innegable encanto.</p>
            
            <h3>Por quÃ© elegir MeTransfers para tu ruta</h3>
            <p>Realizar este recorrido por tu cuenta requiere una compleja logÃ­stica de alquiler de vehÃ­culos y navegaciÃ³n por carreteras secundarias. Con nuestro servicio de <em>alquiler de vehÃ­culos con conductor</em>, solo tienes que preocuparte de disfrutar:</p>
            <ul>
                <li><strong>Servicio Puerta a Puerta:</strong> Recogida y regreso en tu hotel de Barcelona.</li>
                <li><strong>VehÃ­culos de Lujo:</strong> Mercedes Clase E, S o Minivan Clase V para grupos y familias.</li>
                <li><strong>Privacidad y Exclusividad:</strong> Un tour diseÃ±ado a tu medida, pudiendo ajustar las paradas segÃºn tus intereses.</li>
            </ul>
        ',
        'tour-de-compras-vip-en-barcelona-la-roca-village' => '
            <h2>Una experiencia de compras de lujo sin igual</h2>
            <p>Si amas la moda y las marcas exclusivas, el <strong>Tour de Compras VIP a La Roca Village</strong> es la escapada perfecta durante tu estancia en Barcelona. A tan solo 40 minutos de la ciudad, este prestigioso <em>outlet de lujo al aire libre</em> alberga mÃ¡s de 140 boutiques de las mejores marcas nacionales e internacionales de moda, belleza y estilo de vida, ofreciendo descuentos de hasta un 60% sobre el precio original durante todo el aÃ±o.</p>
            
            <h3>Viaja con el mÃ¡ximo confort y estilo</h3>
            <p>Sabemos que un dÃ­a de compras intenso requiere comodidad. Al reservar nuestro traslado privado a La Roca Village, evitarÃ¡s las molestias de los autobuses abarrotados o los problemas de aparcamiento. Nuestro chÃ³fer te recogerÃ¡ en la puerta de tu hotel en un elegante vehÃ­culo Mercedes-Benz, proporcionÃ¡ndote un viaje relajante para que llegues fresco y con energÃ­a.</p>
            
            <h3>Â¿QuÃ© marcas te esperan en La Roca Village?</h3>
            <p>El village cuenta con una selecciÃ³n inmejorable de firmas de alta costura y diseÃ±o contemporÃ¡neo, diseÃ±adas como una pequeÃ±a y encantadora villa mediterrÃ¡nea:</p>
            <ul>
                <li><strong>Alta Costura:</strong> Prada, Gucci, Armani, Balenciaga, Saint Laurent y Loewe.</li>
                <li><strong>Estilo de Vida y Deporte:</strong> Polo Ralph Lauren, Tommy Hilfiger, Nike, y Moncler.</li>
                <li><strong>JoyerÃ­a y RelojerÃ­a:</strong> Bulgari, TAG Heuer y Montblanc.</li>
            </ul>

            <h3>Beneficios VIP de nuestro servicio</h3>
            <p>Con MeTransfers, la experiencia de compras se eleva al siguiente nivel:</p>
            <ul>
                <li><strong>Gran capacidad de maletero:</strong> Nuestros vehÃ­culos (especialmente las Mercedes Clase V) tienen espacio mÃ¡s que suficiente para que no tengas que preocuparte por el nÃºmero de bolsas y compras que realices.</li>
                <li><strong>ChÃ³fer a disposiciÃ³n:</strong> Tu conductor estarÃ¡ esperÃ¡ndote para asistirte con las bolsas, permitiÃ©ndote volver al coche a dejar tus compras y seguir explorando las tiendas cÃ³modamente sin cargar peso.</li>
                <li><strong>Horario flexible:</strong> Regresa a Barcelona exactamente cuando lo desees, sin depender de horarios de transporte pÃºblico.</li>
            </ul>
            
            <p>Completa tu dÃ­a de shopping disfrutando de la exquisita oferta gastronÃ³mica de los restaurantes y cafeterÃ­as del Village. Solicita ya tu <strong>traslado VIP a La Roca Village</strong> y disfruta del lujo desde el primer kilÃ³metro.</p>
        ',
        'excursion-privada-de-barcelona-a-sitges-y-tarragona' => '
            <h2>Descubre la magia del MediterrÃ¡neo y el Imperio Romano</h2>
            <p>Combina el encanto costero, el modernismo y el imponente legado de la antigua Roma en una sola jornada con nuestra <strong>ExcursiÃ³n Privada a Sitges y Tarragona</strong>. Este tour te llevarÃ¡ por la hermosa costa al sur de Barcelona, descubriendo dos de los destinos mÃ¡s atractivos de CataluÃ±a a bordo de nuestros confortables vehÃ­culos premium.</p>
            
            <h3>Primera parada: Sitges, la Blanca Subur</h3>
            <p>Situada a escasos 40 kilÃ³metros de Barcelona, Sitges es conocida mundialmente por sus hermosas playas, su vibrante vida cultural y su patrimonio modernista. Durante tu visita podrÃ¡s:</p>
            <ul>
                <li>Pasear por su icÃ³nico <strong>Paseo MarÃ­timo</strong> flanqueado por palmeras y mansiones indianas.</li>
                <li>Visitar la emblemÃ¡tica <strong>Iglesia de San BartolomÃ© y Santa Tecla</strong>, que se alza majestuosa sobre el mar ofreciendo la imagen mÃ¡s famosa de la villa.</li>
                <li>Perderte por su encantador casco antiguo, de calles estrechas y casas blancas, y descubrir museos fascinantes como el <em>Cau Ferrat</em> o el <em>Palau Maricel</em>.</li>
            </ul>

            <h3>Segunda parada: Tarragona (Tarraco Romana)</h3>
            <p>Siguiendo la costa hacia el sur, llegaremos a Tarragona, una ciudad declarada <strong>Patrimonio de la Humanidad por la UNESCO</strong> gracias a sus extraordinariamente conservadas ruinas romanas. Hace dos mil aÃ±os, Tarraco fue una de las ciudades mÃ¡s importantes del Imperio Romano en la PenÃ­nsula IbÃ©rica.</p>
            <ul>
                <li><strong>El Anfiteatro Romano:</strong> Un espectacular coliseo del siglo II d.C. construido junto a la orilla del mar MediterrÃ¡neo, donde antaÃ±o luchaban gladiadores.</li>
                <li><strong>El Circo Romano y la Torre del Pretorio:</strong> Pasea por las bÃ³vedas subterrÃ¡neas de uno de los circos mejor conservados del mundo.</li>
                <li><strong>El Acueducto de les Ferreres (Pont del Diable):</strong> Una imponente obra de ingenierÃ­a romana situada en los bosques a las afueras de la ciudad.</li>
                <li><strong>BalcÃ³n del MediterrÃ¡neo:</strong> Termina la visita asomÃ¡ndote a este famoso mirador que ofrece unas vistas inolvidables del mar y la costa dorada.</li>
            </ul>

            <h3>Confort absoluto en ruta</h3>
            <p>Esta excursiÃ³n de dÃ­a completo es ideal para realizarla con nuestro servicio de <em>coche con conductor</em>. DisfrutarÃ¡s del trayecto por la pintoresca carretera de las Costas del Garraf con la mÃ¡xima seguridad, parando en miradores si lo deseas y escuchando las recomendaciones locales de nuestro chÃ³fer experto.</p>
        ',
        'tour-panoramico-por-barcelona-en-coche-privado' => '
            <h2>La esencia de Barcelona desde la comodidad de un vehÃ­culo premium</h2>
            <p>Si dispones de poco tiempo en la ciudad o simplemente quieres evitar largas caminatas y las aglomeraciones del transporte pÃºblico, nuestro <strong>Tour PanorÃ¡mico por Barcelona en Coche Privado</strong> es la soluciÃ³n perfecta. Te ofrecemos un recorrido exclusivo y eficiente que te permitirÃ¡ contemplar las obras maestras y los rincones mÃ¡s espectaculares de la capital catalana sin bajar de tu vehÃ­culo de lujo (o realizando paradas estratÃ©gicas y breves para tomar fotografÃ­as).</p>
            
            <h3>Los imprescindibles de la ciudad condal</h3>
            <p>A lo largo de este tour panorÃ¡mico, nuestro chÃ³fer profesional trazarÃ¡ una ruta optimizada que incluye los grandes hitos de la arquitectura y la historia de Barcelona:</p>
            <ul>
                <li><strong>La Sagrada Familia:</strong> Contempla la majestuosidad de la obra cumbre e inacabada de Antoni GaudÃ­, admirando los intrincados detalles de sus diferentes fachadas directamente desde el confort de tu asiento.</li>
                <li><strong>Paseo de Gracia:</strong> Recorre la avenida mÃ¡s lujosa de la ciudad, flanqueada por boutiques de diseÃ±o y las cÃ©lebres casas modernistas, incluyendo la <em>Casa BatllÃ³</em> y <em>La Pedrera (Casa MilÃ )</em>.</li>
                <li><strong>MontjuÃ¯c y la Plaza EspaÃ±a:</strong> Ascenderemos a la montaÃ±a de MontjuÃ¯c pasando por las Torres Venecianas y el Palacio Nacional. Desde lo alto, disfrutarÃ¡s de las mejores <strong>vistas panorÃ¡micas de toda Barcelona</strong> y su puerto, ademÃ¡s de ver las instalaciones del Anillo OlÃ­mpico de 1992.</li>
                <li><strong>Frente MarÃ­timo y Puerto OlÃ­mpico:</strong> Siente la brisa mediterrÃ¡nea recorriendo el litoral barcelonÃ©s, desde el Monumento a ColÃ³n y el Port Vell hasta las modernas playas de la Vila OlÃ­mpica.</li>
                <li><strong>Arco de Triunfo y Parque de la Ciutadella:</strong> Iconos de la ExposiciÃ³n Universal de 1888 que aportan un aire monumental e histÃ³rico al trayecto.</li>
            </ul>

            <h3>Un tour diseÃ±ado a tu medida</h3>
            <p>La mayor ventaja de nuestro <em>servicio de transfer y tour privado</em> es la personalizaciÃ³n absoluta. Si deseas alterar la ruta para pasar por el Camp Nou, acercarte al moderno distrito 22@, o hacer una parada exprÃ©s para degustar unas tapas, tu chÃ³fer estarÃ¡ a tu completa disposiciÃ³n para adaptar el itinerario al momento.</p>
            
            <p>Viaja con la elegancia de nuestra flota de vehÃ­culos Mercedes, equipados con climatizaciÃ³n, asientos de cuero y cristales tintados para tu mÃ¡xima privacidad. Convierte tu visita a Barcelona en una experiencia de cinco estrellas, libre de estrÃ©s y fatiga.</p>
        '
    );

    foreach ( $routes_content as $slug => $content ) {
        $post = get_page_by_path( $slug, OBJECT, 'post' );
        if ( $post && $post instanceof WP_Post ) {
            // Update the post content
            wp_update_post( array(
                'ID'           => $post->ID,
                'post_content' => trim( $content )
            ) );
        }
    }

    update_option( 'mt_routes_content_updated_v1', true );
}

// =========================================================================
// AUTO-CREAR ARTÃCULO TAX FREE (UNA SOLA VEZ)
// =========================================================================
// [MIGRACIÃ“N MANUAL] El artÃ­culo Tax Free ya existe en BD. Hook desactivado para evitar
// sobreescribir contenido editado manualmente y duplicados SEO.
// add_action( 'init', 'mt_auto_create_tax_free_post' );
function mt_auto_create_tax_free_post() {
    if ( get_option( 'mt_tax_free_post_created_v1' ) ) {
        return;
    }

    $slug = 'recuperar-el-iva-en-el-aeropuerto';
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    
    $excerpt = 'Si has disfrutado de una jornada de compras por la ciudad condal y resides fuera de la UniÃ³n Europea, tienes derecho a solicitar la devoluciÃ³n del Impuesto sobre el Valor AÃ±adido (IVA) de tus adquisiciones. El Aeropuerto Josep Tarradellas Barcelona-El Prat cuenta con el sistema digital DIVA, el cual ha simplificado enormemente este trÃ¡mite. A continuaciÃ³n, te presentamos el paso a paso detallado para realizar la gestiÃ³n de tu Tax Free de forma rÃ¡pida, segura y sin contratiempos antes de tu vuelo de regreso.';
    
    $content = '<p class="lead-text">' . $excerpt . '</p>
        <h2>Requisitos Previos para el Tax Free en EspaÃ±a</h2>
        <p>Antes de iniciar el proceso, es indispensable cumplir con ciertos criterios estipulados por la normativa aduanera espaÃ±ola:</p>
        <ul>
            <li><strong>Residencia:</strong> Debes tener tu residencia habitual fuera de la UniÃ³n Europea (o en territorios especÃ­ficos como Canarias, Ceuta o Melilla).</li>
            <li><strong>Tipo de bienes:</strong> Los artÃ­culos comprados deben ser para uso personal o regalo, y deben salir de la UE en tu equipaje personal en un plazo mÃ¡ximo de tres meses desde la compra.</li>
            <li><strong>Sin importe mÃ­nimo:</strong> Actualmente en EspaÃ±a no existe una cantidad mÃ­nima de gasto para tener derecho a la devoluciÃ³n del IVA.</li>
        </ul>

        <h2>Paso 1: En la Tienda (Solicitud del Formulario)</h2>
        <p>El proceso para recuperar tus impuestos comienza mucho antes de llegar al aeropuerto, concretamente en el momento de realizar el pago en el comercio.</p>
        <ul>
            <li>Informa al personal de la tienda que deseas el formulario Tax Free.</li>
            <li>Muestra tu pasaporte original para que puedan registrar tus datos correctamente.</li>
            <li>AsegÃºrate de recibir el documento con el logotipo DIVA y un cÃ³digo de barras o cÃ³digo QR. Guarda este recibo junto con el ticket de compra original.</li>
        </ul>

        <h2>Paso 2: Llegada al Aeropuerto El Prat</h2>
        <p>El dÃ­a de tu vuelo, la planificaciÃ³n del tiempo es crucial. El proceso de aduanas puede tener colas, especialmente en temporada alta de vacaciones.</p>
        <ul>
            <li>Llega al aeropuerto con al menos 3 horas de antelaciÃ³n respecto a la salida de tu vuelo.</li>
            <li>Para garantizar la puntualidad y evitar el estrÃ©s del transporte pÃºblico con todo el equipaje y las compras, reservar un traslado privado directamente hasta tu terminal de salida es una excelente estrategia logÃ­stica.</li>
            <li><strong>Regla de oro:</strong> Bajo ninguna circunstancia factures el equipaje que contiene tus compras antes de realizar el trÃ¡mite. La aduana requiere verificar que la mercancÃ­a abandona efectivamente el territorio.</li>
        </ul>

        <h2>Paso 3: ValidaciÃ³n en los Quioscos Digitales DIVA</h2>
        <p>Una vez en el aeropuerto, y siempre antes de pasar el control de seguridad, debes dirigirte a las mÃ¡quinas de validaciÃ³n automÃ¡tica DIVA.</p>
        
        <h3>Ubicaciones de los Quioscos DIVA</h3>
        <p>EncontrarÃ¡s las terminales interactivas DIVA estratÃ©gicamente ubicadas en las zonas de Salidas del aeropuerto, generalmente cerca de los mostradores de facturaciÃ³n y junto a las oficinas de la Guardia Civil (Aduanas).</p>

        <h3>Proceso de Escaneo</h3>
        <ul>
            <li>Selecciona tu idioma en la pantalla tÃ¡ctil de la mÃ¡quina.</li>
            <li>Pasa el cÃ³digo de barras de cada uno de tus formularios Tax Free por el lector Ã³ptico.</li>
            <li><strong>Mensaje Verde:</strong> El formulario estÃ¡ aprobado. El trÃ¡mite aduanero ha finalizado con Ã©xito.</li>
            <li><strong>Mensaje Rojo:</strong> La mÃ¡quina no puede validar la compra de forma automÃ¡tica. DeberÃ¡s dar un par de pasos hacia el mostrador contiguo de la Guardia Civil (Aduanas) para una revisiÃ³n manual, presentando tus mercancÃ­as y pasaporte.</li>
        </ul>

        <h2>Paso 4: Cobro del Reembolso</h2>
        <p>Con los formularios validados (ya sea digital o manualmente), el Ãºltimo paso es materializar la devoluciÃ³n del dinero. Puedes facturar tu equipaje en este momento si lo deseas y luego proceder al cobro.</p>
        
        <h3>Opciones de Cobro Disponibles</h3>
        <ul>
            <li><strong>Oficinas de Reembolso (Global Blue, Planet, Innova):</strong> EncontrarÃ¡s mostradores de estas agencias operadoras distribuidos tanto en la zona pÃºblica (antes del control de seguridad) como en la zona de embarque (despuÃ©s del control de pasaportes). Presenta tus documentos validados para recibir el dinero.</li>
            <li><strong>Reembolso en Efectivo:</strong> RecibirÃ¡s el dinero al instante en la moneda seleccionada, pero la agencia deducirÃ¡ una comisiÃ³n por gastos de gestiÃ³n.</li>
            <li><strong>Reembolso en Tarjeta de CrÃ©dito:</strong> RecuperarÃ¡s el importe Ã­ntegro correspondiente. El dinero suele reflejarse en tu cuenta bancaria en un plazo de unos dÃ­as hÃ¡biles.</li>
            <li><strong>Buzones de Correo:</strong> Si prefieres evitar las colas o tu vuelo sale de madrugada cuando las oficinas estÃ¡n cerradas, introduce el formulario validado en el sobre prefranqueado que te entregaron en la tienda, escribe los datos de tu tarjeta de crÃ©dito y deposÃ­talo en los buzones de la empresa operadora correspondientes.</li>
        </ul>
    ';

    if ( ! $post ) {
        wp_insert_post( array(
            'post_title'   => 'GuÃ­a Definitiva: CÃ³mo Recuperar el IVA (Tax Free) en el Aeropuerto de Barcelona â€“ El Prat',
            'post_name'    => $slug,
            'post_content' => trim( $content ),
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish',
            'post_type'    => 'post'
        ) );
    } else {
        wp_update_post( array(
            'ID'           => $post->ID,
            'post_content' => trim( $content ),
            'post_excerpt' => $excerpt,
        ) );
    }

    update_option( 'mt_tax_free_post_created_v1', true );
}

// =========================================================================
// AUTO-CREAR PÃGINAS GENÃ‰RICAS (FAQ, SOBRE NOSOTROS)
// =========================================================================
// [MIGRACIÃ“N MANUAL] PÃ¡ginas FAQ y Sobre Nosotros ya existen. Hook desactivado para evitar
// sobreescribir cambios editoriales hechos desde el panel de WordPress.
// add_action( 'init', 'mt_auto_create_generic_pages' );
function mt_auto_create_generic_pages() {
    if ( get_option( 'mt_generic_pages_created_v1' ) ) {
        return;
    }

    $pages = array(
        'faq' => array(
            'title'   => 'Preguntas Frecuentes (FAQ)',
            'content' => '
                <div class="luxury-prose">
                    <h2>Resolvemos tus dudas sobre nuestros traslados privados</h2>
                    <p>En MeTransfers queremos que tu experiencia sea perfecta desde el momento en que realizas tu reserva. A continuaciÃ³n, hemos recopilado las preguntas mÃ¡s habituales de nuestros clientes.</p>

                    <h3>1. Â¿CÃ³mo funciona la recogida en el aeropuerto?</h3>
                    <p>Nuestro conductor monitorizarÃ¡ el estado de tu vuelo en tiempo real. Te estarÃ¡ esperando en la zona de llegadas (Arrivals) con un cartel con tu nombre o el logotipo de tu empresa, justo despuÃ©s de que recojas tu equipaje. Te ayudarÃ¡ con las maletas y te acompaÃ±arÃ¡ hasta el vehÃ­culo VIP estacionado en la zona reservada.</p>

                    <h3>2. Â¿QuÃ© pasa si mi vuelo se retrasa?</h3>
                    <p>No tienes de quÃ© preocuparte. Incluimos <strong>hasta 60 minutos de espera de cortesÃ­a gratuita</strong> desde el momento en que aterriza el vuelo. Dado que monitorizamos los vuelos, si hay un retraso ajustaremos automÃ¡ticamente la hora de recogida sin ningÃºn coste adicional para ti.</p>

                    <h3>3. Â¿Los vehÃ­culos disponen de sillas para niÃ±os o bebÃ©s?</h3>
                    <p>SÃ­, la seguridad de los mÃ¡s pequeÃ±os es nuestra prioridad. Ofrecemos sillas infantiles y elevadores homologados sin coste adicional. Solo necesitas indicarnos la edad y el peso de los niÃ±os en el formulario de reserva o a travÃ©s de WhatsApp para que tengamos el vehÃ­culo preparado.</p>

                    <h3>4. Â¿CuÃ¡l es la polÃ­tica de cancelaciÃ³n?</h3>
                    <p>Ofrecemos total flexibilidad. Puedes cancelar o modificar tu reserva de forma gratuita hasta <strong>24 horas antes</strong> de la hora de recogida programada. Si cancelas con menos antelaciÃ³n, ponte en contacto con nuestro equipo de soporte para revisar tu caso concreto.</p>

                    <h3>5. Â¿Puedo llevar equipaje especial o voluminoso (bicicletas, esquÃ­s)?</h3>
                    <p>Â¡Por supuesto! Disponemos de furgonetas Mercedes Clase V extra largas (Minivans) ideales para transportar equipaje deportivo, sillas de ruedas o simplemente muchas maletas. Solo te pedimos que nos lo indiques al hacer la reserva para asegurarnos de enviar el vehÃ­culo adecuado.</p>
                    
                    <h3>6. Â¿Los precios son cerrados o hay cargos extra?</h3>
                    <p>Todos nuestros presupuestos son finales. El precio que ves al reservar incluye impuestos (IVA), peajes, propinas y tiempos de espera de cortesÃ­a. <strong>No hay sorpresas ni costes ocultos.</strong></p>
                    
                    <hr/>
                    <p>Â¿Tienes alguna otra pregunta? No dudes en escribirnos por WhatsApp o utilizar nuestro <a href="/contacto/">formulario de contacto</a>. Nuestro equipo de atenciÃ³n al cliente estÃ¡ disponible 24/7 para ayudarte.</p>
                </div>
            '
        ),
        'sobre-nosotros' => array(
            'title'   => 'Sobre Nosotros',
            'content' => '
                <div class="luxury-prose">
                    <h2>MeTransfers: Excelencia en Movilidad Privada</h2>
                    <p>Somos una agencia boutique de traslados privados y chÃ³feres VIP con base en Barcelona. Nacimos con un objetivo claro: elevar los estÃ¡ndares del transporte de pasajeros en CataluÃ±a, transformando un simple trayecto en una <strong>experiencia de lujo, confort y fiabilidad.</strong></p>
                    
                    <h3>Nuestra FilosofÃ­a</h3>
                    <p>Entendemos que el tiempo de nuestros clientes es su activo mÃ¡s valioso. Ya sea que viajes por negocios, asistas a un congreso internacional (como el Mobile World Congress) o disfrutes de unas merecidas vacaciones en la Costa Brava, nuestro equipo se encarga de toda la logÃ­stica para que tÃº solo tengas que relajarte y disfrutar del viaje.</p>
                    
                    <h3>La Flota: Confort de Primera Clase</h3>
                    <p>No creemos en los compromisos cuando se trata de seguridad y comodidad. Por ello, operamos exclusivamente con vehÃ­culos premium de Ãºltima generaciÃ³n de la marca <strong>Mercedes-Benz</strong>:</p>
                    <ul>
                        <li><strong>Clase E y Clase S:</strong> Elegancia y sofisticaciÃ³n absolutas para ejecutivos, diplomÃ¡ticos y parejas.</li>
                        <li><strong>Clase V (Minivan):</strong> El espacio definitivo para familias y grupos corporativos de hasta 7 personas, con asientos enfrentables y amplio espacio para equipaje.</li>
                    </ul>
                    <p>Todos nuestros vehÃ­culos se desinfectan tras cada servicio y cuentan con climatizaciÃ³n independiente, agua de cortesÃ­a y conexiÃ³n Wi-Fi gratuita.</p>

                    <h3>Nuestros ChÃ³feres: Los Mejores Anfitriones</h3>
                    <p>La tecnologÃ­a y los coches de lujo no son nada sin el toque humano. Nuestro equipo de conductores profesionales destaca por su <strong>discreciÃ³n, puntualidad extrema y conocimiento exhaustivo</strong> de Barcelona y sus alrededores. Completamente bilingÃ¼es y con una impecable presentaciÃ³n en traje oscuro, estÃ¡n capacitados para ofrecer un servicio diplomÃ¡tico y adaptarse a cualquier imprevisto en la ruta.</p>
                    
                    <h3>Compromiso Medioambiental</h3>
                    <p>En MeTransfers miramos hacia el futuro. Estamos en un proceso activo de renovaciÃ³n de nuestra flota hacia opciones hÃ­bridas y elÃ©ctricas de lujo para reducir nuestra huella de carbono, sin sacrificar ni un Ã¡pice del rendimiento y la comodidad que nos caracteriza.</p>
                </div>
            '
        )
    );

    foreach ( $pages as $slug => $data ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        
        if ( ! $page ) {
            wp_insert_post( array(
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_content' => trim( $data['content'] ),
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ) );
        } else {
            wp_update_post( array(
                'ID'           => $page->ID,
                'post_content' => trim( $data['content'] ),
            ) );
        }
    }

    update_option( 'mt_generic_pages_created_v1', true );
}

// =========================================================================
// AUTO-CREAR ARTÃCULO VIP ARTISTAS Y MÃšSICOS
// =========================================================================
// [MIGRACIÃ“N MANUAL] ArtÃ­culo de artistas ya existe en BD. Hook desactivado para evitar
// sobreescribir contenido editorial con el texto hardcodeado del tema.
// add_action( 'init', 'mt_auto_create_artist_post' );
function mt_auto_create_artist_post() {
    if ( get_option( 'mt_artist_post_created_v1' ) ) {
        return;
    }

    $slug = 'movilidad-vip-para-artistas-y-musicos-en-barcelona-discrecion-y-gran-capacidad-de-maletero-para-instrumentos';
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    
    $excerpt = 'Descubre por quÃ© MeTransfers es la agencia de movilidad de confianza para artistas, bandas musicales y talentos internacionales en Barcelona. Ofrecemos mÃ¡xima discreciÃ³n, puntualidad milimÃ©trica para conciertos y furgonetas Mercedes de gran capacidad para el transporte seguro de instrumentos y equipos delicados.';
    
    $content = '<p class="lead-text">' . $excerpt . '</p>
        <h2>Servicio de ChÃ³fer VIP para el Sector Musical en Barcelona</h2>
        <p>Barcelona es una de las capitales europeas de la mÃºsica, albergando festivales de renombre mundial como el Primavera Sound, SÃ³nar, y conciertos multitudinarios en el Palau Sant Jordi o el Estadi OlÃ­mpic. La logÃ­stica para un artista internacional o una banda requiere un nivel de excelencia y adaptabilidad que el transporte convencional no puede ofrecer.</p>
        <p>En <strong>MeTransfers</strong> nos especializamos en la <em>movilidad premium para el sector del entretenimiento</em>. Entendemos las exigencias de las giras, los horarios de los estudios de grabaciÃ³n y la necesidad de mantener un perfil bajo ante la prensa y los fans.</p>

        <h2>MÃ¡xima DiscreciÃ³n y Privacidad</h2>
        <p>Sabemos que para una celebridad, la privacidad no es un lujo, sino una necesidad. Nuestros servicios de transfer estÃ¡n diseÃ±ados para garantizar la tranquilidad del talento:</p>
        <ul>
            <li><strong>Cristales tintados y vehÃ­culos sin distintivos:</strong> Nuestras furgonetas y berlinas Mercedes-Benz pasan completamente desapercibidas en la ciudad.</li>
            <li><strong>ChÃ³feres formados en protocolo:</strong> Nuestro equipo firma estrictos acuerdos de confidencialidad (NDA). Operan con la mÃ¡xima discreciÃ³n, evitando interacciones innecesarias y asegurando un entorno relajado para el artista.</li>
            <li><strong>Rutas seguras y accesos VIP:</strong> Coordinamos con los equipos de producciÃ³n y seguridad de los recintos (hoteles, arenas, festivales) para utilizar accesos traseros o privados, evitando aglomeraciones.</li>
        </ul>

        <h2>Furgonetas Minivan: Gran Capacidad para Instrumentos y Equipo</h2>
        <p>Uno de los mayores retos para los mÃºsicos en gira es el transporte de su equipo (guitarras, teclados, amplificadores, vestuario). Moverse en taxis convencionales suele ser una pesadilla logÃ­stica.</p>
        <p>Nuestra flota de <strong>Mercedes Clase V Extra Largas (Minivans)</strong> es la soluciÃ³n definitiva:</p>
        <ul>
            <li><strong>Maletero extragrande:</strong> Capacidad sobrada para albergar estuches rÃ­gidos, instrumentos delicados y mÃºltiples maletas de gran tamaÃ±o sin sacrificar el confort de los pasajeros.</li>
            <li><strong>ConducciÃ³n suave:</strong> La suspensiÃ³n neumÃ¡tica de nuestros vehÃ­culos garantiza que los instrumentos mÃ¡s sensibles (como violonchelos o equipos de grabaciÃ³n) no sufran durante los trayectos urbanos.</li>
            <li><strong>Espacio para el equipo:</strong> Con capacidad para hasta 7 pasajeros, el artista puede viajar cÃ³modamente junto a su mÃ¡nager, personal de seguridad o tÃ©cnicos clave en un mismo vehÃ­culo.</li>
        </ul>

        <h2>Puntualidad MilimÃ©trica y Disponibilidad 24/7</h2>
        <p>En la industria musical, llegar tarde a una prueba de sonido o a una entrevista de promociÃ³n no es una opciÃ³n. Ofrecemos un servicio de <em>disposiciÃ³n por horas</em> que otorga flexibilidad total a la agenda del artista.</p>
        <p>Tu chÃ³fer privado estarÃ¡ esperando fuera del estudio de grabaciÃ³n a altas horas de la madrugada, o en la puerta del hotel listo para un traslado exprÃ©s al aeropuerto Josep Tarradellas Barcelona-El Prat. Monitorizamos constantemente el trÃ¡fico en Barcelona para evitar atascos y asegurar llegadas puntuales.</p>
        
        <hr/>
        <h3>Reserva la movilidad de tu prÃ³xima gira</h3>
        <p>Si eres mÃ¡nager, promotor o formas parte del equipo de producciÃ³n de un evento, no dejes la logÃ­stica terrestre al azar. <a href="/contacto">Contacta con nosotros hoy mismo</a> para planificar los traslados VIP de tu talento. Te proporcionaremos presupuestos personalizados para varios dÃ­as o flotas de mÃºltiples vehÃ­culos simultÃ¡neos.</p>
    ';

    if ( ! $post ) {
        wp_insert_post( array(
            'post_title'   => 'Movilidad VIP para Artistas y MÃºsicos en Barcelona: DiscreciÃ³n y Espacio para Instrumentos',
            'post_name'    => $slug,
            'post_content' => trim( $content ),
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish',
            'post_type'    => 'post'
        ) );
    } else {
        wp_update_post( array(
            'ID'           => $post->ID,
            'post_content' => trim( $content ),
            'post_excerpt' => $excerpt,
        ) );
    }

    update_option( 'mt_artist_post_created_v1', true );
}

// =========================================================================
// AUTO-CREAR ARTÃCULOS SENIORS Y LONJAS DE PESCADO
// =========================================================================
// [MIGRACIÃ“N MANUAL] ArtÃ­culos de seniors y lonjas ya existen en BD. Hook desactivado
// para evitar sobreescribir contenido editorial con el texto hardcodeado del tema.
// add_action( 'init', 'mt_auto_create_seniors_lonjas_posts' );
function mt_auto_create_seniors_lonjas_posts() {
    if ( get_option( 'mt_seniors_lonjas_posts_created_v1' ) ) {
        return;
    }

    $posts = array(
        'barcelona-seniors-comodidad-accesibilidad-vehiculos' => array(
            'title'   => 'Turismo Senior en Barcelona: Comodidad y Accesibilidad en Nuestros VehÃ­culos',
            'excerpt' => 'Descubre cÃ³mo MeTransfers facilita el turismo para personas mayores (seniors) en Barcelona. VehÃ­culos de fÃ¡cil acceso, asistencia personalizada con el equipaje y una conducciÃ³n suave para garantizar un viaje sin estrÃ©s.',
            'content' => '
                <p class="lead-text">Barcelona es una ciudad maravillosa para visitar a cualquier edad, pero para los viajeros senior, moverse por la ciudad y llegar desde el aeropuerto hasta el hotel puede resultar agotador si no se planifica adecuadamente.</p>
                
                <h2>Accesibilidad y Confort como Prioridad</h2>
                <p>En MeTransfers hemos adaptado nuestros servicios para ofrecer la experiencia mÃ¡s cÃ³moda y segura a nuestros clientes de mayor edad. Evitar las largas caminatas en el aeropuerto, las escaleras del metro o las esperas en las paradas de taxis convencionales marca una gran diferencia en el inicio de unas vacaciones.</p>
                
                <h3>VehÃ­culos adaptados a tus necesidades</h3>
                <p>Nuestra flota estÃ¡ compuesta por berlinas y Minivans de la marca Mercedes-Benz, seleccionadas especÃ­ficamente por su ergonomÃ­a:</p>
                <ul>
                    <li><strong>Acceso fÃ¡cil:</strong> Nuestras Mercedes Clase V cuentan con puertas correderas amplias y estribos bajos, lo que facilita enormemente entrar y salir del vehÃ­culo sin esfuerzo.</li>
                    <li><strong>Asientos ergonÃ³micos:</strong> Interiores espaciosos con asientos de cuero regulables, reposabrazos y climatizaciÃ³n independiente para garantizar el mÃ¡ximo confort articular y tÃ©rmico.</li>
                    <li><strong>Espacio para sillas de ruedas:</strong> Disponemos de espacio de sobra en el maletero para acomodar sillas de ruedas plegables, andadores y cualquier tipo de asistencia tÃ©cnica para la movilidad.</li>
                </ul>

                <h2>Asistencia Personalizada Puerta a Puerta</h2>
                <p>El servicio comienza en la misma terminal de llegadas. Nuestro chÃ³fer te estarÃ¡ esperando con un cartel visible justo al salir de la recogida de equipajes. A partir de ese momento, <strong>Ã©l se encargarÃ¡ de todas tus maletas</strong>.</p>
                <p>No tendrÃ¡s que cargar peso en ningÃºn momento. El conductor te acompaÃ±arÃ¡ a un paso tranquilo hasta el vehÃ­culo VIP, estacionado a pocos metros de la puerta en zonas reservadas del Aeropuerto de El Prat.</p>
                
                <h2>ConducciÃ³n Suave y Segura</h2>
                <p>Nuestros conductores profesionales aplican un estilo de conducciÃ³n defensivo y extremadamente suave, evitando frenazos o aceleraciones bruscas. AdemÃ¡s, si deseas hacer una parada tÃ©cnica durante el trayecto (por ejemplo, en un viaje largo hacia la Costa Brava), solo tienes que pedirlo.</p>
                <p>Viaja con total tranquilidad y disfruta del paisaje barcelonÃ©s. <a href="/contacto">Reserva hoy tu traslado VIP</a> y asegura un inicio de vacaciones relajado para ti o tus familiares mayores.</p>
            '
        ),
        'lonjas-de-pescado-en-la-costa-de-cataluna' => array(
            'title'   => 'Ruta GastronÃ³mica: Las Mejores Lonjas de Pescado en la Costa de CataluÃ±a',
            'excerpt' => 'SumÃ©rgete en la cultura marinera de CataluÃ±a visitando sus famosas lonjas de pescado. Desde PalamÃ³s hasta Vilanova i la GeltrÃº, te llevamos en un cÃ³modo traslado privado a presenciar la subasta del pescado y degustar el marisco mÃ¡s fresco.',
            'content' => '
                <p class="lead-text">La costa catalana no solo es famosa por sus playas y calas de aguas cristalinas, sino tambiÃ©n por su riquÃ­sima tradiciÃ³n pesquera. Una de las experiencias mÃ¡s autÃ©nticas y fascinantes que puedes vivir cerca de Barcelona es visitar una <strong>lonja de pescado (Llotja de Peix)</strong> al atardecer.</p>
                
                <h2>El EspectÃ¡culo de la Subasta del Pescado</h2>
                <p>Cada tarde, de lunes a viernes, los barcos pesqueros regresan a puerto seguidos por bandadas de gaviotas. La descarga de las cajas llenas de gambas, cigalas, rape y calamares da paso a la tradicional subasta. Antiguamente cantada a viva voz, hoy se realiza mediante paneles electrÃ³nicos, pero no ha perdido un Ã¡pice de su frenÃ©tica emociÃ³n.</p>
                
                <h3>Las lonjas imprescindibles de la Costa Brava y Dorada</h3>
                <ul>
                    <li><strong>PalamÃ³s (Girona):</strong> Mundialmente famosa por su espectacular <em>Gamba Roja de PalamÃ³s</em>. AdemÃ¡s de presenciar la subasta, puedes visitar el Museo de la Pesca y apuntarte a los talleres gastronÃ³micos del Espai del Peix.</li>
                    <li><strong>Blanes (Girona):</strong> El puerto pesquero mÃ¡s activo del sur de la Costa Brava. Su subasta es un hervidero de actividad donde restaurantes de lujo compran el gÃ©nero mÃ¡s selecto del dÃ­a.</li>
                    <li><strong>Arenys de Mar (Barcelona):</strong> A solo 45 minutos de la ciudad condal, es la lonja mÃ¡s importante del Maresme. Famosa por sus calamares y sonso, ofrece un ambiente marinero inigualable.</li>
                    <li><strong>Vilanova i la GeltrÃº (Tarragona):</strong> Una de las flotas pesqueras mÃ¡s grandes de CataluÃ±a, destacando por el marisco y el exquisito "Peix Blau" (pescado azul).</li>
                </ul>

                <h2>Del Barco al Plato</h2>
                <p>La visita a la lonja culmina, inevitablemente, en la mesa. Alrededor de estos puertos se concentran las mejores marisquerÃ­as y tabernas marineras, donde podrÃ¡s degustar el mismo pescado que acabas de ver desembarcar, acompaÃ±ado de un buen vino blanco D.O. PenedÃ¨s o EmpordÃ .</p>
                
                <h2>Tu Ruta GastronÃ³mica en VehÃ­culo Privado</h2>
                <p>Visitar estos pueblos pesqueros y volver a Barcelona el mismo dÃ­a puede resultar cansado si dependes del tren o conduces de noche tras una buena cena. Nuestro <strong>servicio de traslado y tour privado</strong> es la opciÃ³n perfecta para disfrutar de esta experiencia.</p>
                <p>Te recogemos en tu hotel, te llevamos al puerto de tu elecciÃ³n para que pasees, presencies la subasta y cenes sin prisa. Tu chÃ³fer privado te estarÃ¡ esperando a la salida del restaurante para llevarte de vuelta a tu alojamiento con total comodidad en un elegante Mercedes-Benz. <a href="/contacto">ConsÃºltanos para organizar tu ruta gastronÃ³mica marinera</a>.</p>
            '
        )
    );

    foreach ( $posts as $slug => $data ) {
        $post = get_page_by_path( $slug, OBJECT, 'post' );
        
        if ( ! $post ) {
            wp_insert_post( array(
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_content' => trim( $data['content'] ),
                'post_excerpt' => $data['excerpt'],
                'post_status'  => 'publish',
                'post_type'    => 'post'
            ) );
        } else {
            wp_update_post( array(
                'ID'           => $post->ID,
                'post_content' => trim( $data['content'] ),
                'post_excerpt' => $data['excerpt'],
            ) );
        }
    }



    update_option( 'mt_seniors_lonjas_posts_created_v1', true );
}

/**
 * ==============================================================================
 * TRACKING DE EVENTOS DE BOTONES
 * ==============================================================================
 */

// 1. Crear la tabla de base de datos en la activaciÃ³n o inicio
function mt_setup_event_tracking_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mt_event_tracking';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        button_text varchar(255) NOT NULL,
        button_class varchar(255) DEFAULT '' NOT NULL,
        page_url varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
// after_switch_theme se ejecuta UNA SOLA VEZ al activar el tema (no en cada carga).
// Antes era after_setup_theme que corrÃ­a dbDelta() en CADA peticiÃ³n HTTP.
add_action( 'after_switch_theme', 'mt_setup_event_tracking_table' );

// 2. Endpoint AJAX para registrar el clic
function mt_ajax_track_button_click() {
    check_ajax_referer( 'mt_lead_nonce', 'security' );

    $button_text  = isset( $_POST['button_text'] ) ? sanitize_text_field( $_POST['button_text'] ) : '';
    $button_class = isset( $_POST['button_class'] ) ? sanitize_text_field( $_POST['button_class'] ) : '';
    $page_url     = isset( $_POST['page_url'] ) ? esc_url_raw( $_POST['page_url'] ) : '';

    if ( ! empty( $button_text ) && ! empty( $page_url ) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_event_tracking';
        
        $wpdb->insert(
            $table_name,
            array(
                'button_text'  => $button_text,
                'button_class' => $button_class,
                'page_url'     => $page_url,
                'created_at'   => current_time( 'mysql' )
            )
        );
    }
    
    wp_send_json_success();
}
add_action( 'wp_ajax_mt_track_button_click', 'mt_ajax_track_button_click' );
add_action( 'wp_ajax_nopriv_mt_track_button_click', 'mt_ajax_track_button_click' );

// 3. MenÃº de administraciÃ³n para ver las estadÃ­sticas
function mt_add_event_tracking_menu() {
    add_menu_page(
        'MÃ©tricas Botones',
        'MÃ©tricas Botones',
        'manage_options',
        'mt-button-metrics',
        'mt_render_event_tracking_page',
        'dashicons-chart-bar',
        30
    );
}
add_action( 'admin_menu', 'mt_add_event_tracking_menu' );

function mt_render_event_tracking_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mt_event_tracking';
    
    // Obtener los datos agrupados
    $results = $wpdb->get_results( "
        SELECT button_text, page_url, COUNT(*) as click_count, MAX(created_at) as last_click
        FROM $table_name
        GROUP BY button_text, page_url
        ORDER BY click_count DESC
    " );
    
    ?>
    <div class="wrap">
        <h1>EstadÃ­sticas de Clics en Botones</h1>
        <p>A continuaciÃ³n se muestran los botones que los usuarios han pulsado en la web, agrupados por texto del botÃ³n y URL de la pÃ¡gina.</p>
        
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th class="manage-column">Texto del BotÃ³n</th>
                    <th class="manage-column">URL de la PÃ¡gina</th>
                    <th class="manage-column">Total Clics</th>
                    <th class="manage-column">Ãšltimo Clic</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $results ) ) : ?>
                    <tr>
                        <td colspan="4">No hay datos registrados aÃºn.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $results as $row ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $row->button_text ); ?></strong></td>
                            <td><a href="<?php echo esc_url( $row->page_url ); ?>" target="_blank"><?php echo esc_html( $row->page_url ); ?></a></td>
                            <td><?php echo esc_html( $row->click_count ); ?></td>
                            <td><?php echo esc_html( $row->last_click ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
