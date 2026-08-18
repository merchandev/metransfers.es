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

// DESACTIVADO: mt_update_all_page_titles_once() anteponÍa "MeTransfers Barcelona -" a TODOS
// los tÍtulos editoriales de las páginas, mezclando el tÍtulo interno con el tÍtulo SEO.
// Los tres conceptos deben estar separados: tÍtulo interno, H1 visible y <title> SEO.
// Si necesitas renombrar páginas, hazlo manualmente desde el panel de WordPress.
// add_action( 'admin_init', 'mt_update_all_page_titles_once' );
function mt_update_all_page_titles_once() {
    // Función conservada por si se necesita referenciar el historial de migración.
    // No conectada a ningún hook. No se ejecuta automáticamente.
}



require_once get_template_directory() . '/includes/rutas-cpt.php';
require_once get_template_directory() . '/includes/leads-cpt.php';

// Herramienta de administración: repoblar post_content desde los catálogos PHP.
// Disponible en: Herramientas → Repoblar Contenido
if ( is_admin() ) {
    require_once get_template_directory() . '/includes/admin-content-repopulate.php';
    require_once get_template_directory() . '/includes/auto-migration-v5.php';
}

add_action( 'template_redirect', function() {
    if ( is_404() && trim( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' ) === 'destinos' ) {
        wp_redirect( home_url( '/#rutas' ), 301 );
        exit;
    }
});
// Migration safety switch â€” set to false once initial migration is done.
if ( ! defined( 'ME_TRANSFERS_ENABLE_MIGRATIONS' ) ) {
	define( 'ME_TRANSFERS_ENABLE_MIGRATIONS', false );
}

// Centralized Versioning
if ( ! defined( 'ME_TRANSFERS_VERSION' ) ) {
	define( 'ME_TRANSFERS_VERSION', '4.1.5' );
}

// Forzar UTF-8 en cabeceras HTTP para evitar caracteres corruptos (tildes, ñ)
add_action( 'send_headers', function() {
    if ( ! headers_sent() ) {
        header( 'Content-Type: text/html; charset=UTF-8' );
    }
} );

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
 * Para evitar "Keyword Stuffing" y permitir que WordPress (y el usuario en Ajustes) manejen el tï¿½-tulo limpiamente.
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

/**
 * Force Tours page in navigation menu
 */
add_filter( 'wp_nav_menu_items', 'me_transfers_add_tours_menu_item', 10, 2 );
function me_transfers_add_tours_menu_item( $items, $args ) {
    if ( $args->theme_location === 'menu-1' || $args->menu_id === 'primary-menu' ) {
        $tours_url = home_url( '/tours/' );
        // Only inject if this URL is not already in the menu HTML.
        if ( strpos( $items, $tours_url ) === false && strpos( $items, '/tours"' ) === false ) {
            $items .= '<li class="menu-item"><a href="' . esc_url( $tours_url ) . '">Tours</a></li>';
        }
    }
    return $items;
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
        
        // Añadir capacidades especÍficas
        $role->add_cap( 'read_transfer_requests' );
        $role->add_cap( 'edit_transfer_requests' );
        $role->add_cap( 'read_tour_bookings' );
        $role->add_cap( 'export_transfer_requests' );
    }
}

// 2. Ocultar menús no deseados en el panel izquierdo
add_action('admin_menu', 'me_transfers_hide_menus_checkhoteles', 999);
function me_transfers_hide_menus_checkhoteles() {
    $user = wp_get_current_user();
    if ( in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
        global $menu;
        
        // Palabras clave de los menús permitidos
        $allowed_menus = array(
            'index.php', // Escritorio
            'edit.php?post_type=hotel_partner', // Hoteles QR
            'edit.php?post_type=mt_request', // Solicitudes
            'edit.php?post_type=gyg_review', // GYG Reviews (si es CPT)
            'gyg-reviews', // GYG Reviews (si es plugin/página)
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

// 3. Bloquear acceso directo por URL a páginas no permitidas
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
        
        // Permitir edición de Custom Post Types
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
        
        // Permitir páginas de plugins
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
        
        // Si no está permitido, redirigir al escritorio
        if ( ! $is_allowed ) {
            wp_redirect( admin_url( 'index.php' ) );
            exit;
        }
    }
}

// 4. Limitar visualización de Hoteles QR a los creados por el usuario
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
            'post_content' => 'Explora los destinos más solicitados y accede a una ficha rápida para pedir información de traslados privados, recogidas en aeropuerto, hoteles, puertos y rutas personalizadas.'
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
            $content .= '<p>' . esc_html( sprintf( 'Si estás organizando un traslado hacia %s, podemos prepararte una propuesta adaptada al punto de recogida, número de pasajeros, fecha estimada y tipo de servicio que necesites.', $dest['title'] ) ) . '</p>';
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
        'privacidad' => '<h2>1. Identificación del Responsable del Tratamiento</h2>
<p><strong>Razón Social:</strong> METRANSFERS GESTION SL</p>
<p><strong>NIF:</strong> B22522353</p>
<p><strong>Domicilio Fiscal:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÍ &ndash; (BARCELONA)</p>
<p><strong>Contacto Privacidad:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p>
<h2>2. Aceptación Vinculante</h2>
<p>Al utilizar nuestros servicios, navegar por nuestra plataforma o completar el proceso de configuración de una reserva, usted reconoce haber leÍdo, comprendido y aceptado sin reservas que sus datos personales sean tratados conforme a los términos aquÍ expuestos. La formalización de una reserva constituye un contrato entre las partes, legitimando el tratamiento de los datos necesarios para la ejecución del servicio.</p>
<h2>3. Datos Objeto de Tratamiento</h2>
<p>Recopilamos los datos estrictamente necesarios para la prestación del servicio:</p>
<ul>
<li><strong>Datos de Reserva:</strong> Nombre, apellidos, teléfono, correo electrónico y detalles del trayecto/servicio solicitado.</li>
<li><strong>Datos de Facturación:</strong> Dirección postal y NIF/DNI (según los datos de registro fiscal de la entidad).</li>
<li><strong>Datos de Navegación:</strong> Dirección IP, cookies y metadatos para garantizar la seguridad del sitio.</li>
</ul>
<h2>4. Finalidad del Tratamiento</h2>
<p>Sus datos serán tratados con el fin de:</p>
<ul>
<li><strong>Gestión de Reservas:</strong> Tramitar, confirmar y ejecutar los servicios de transporte o gestión contratados.</li>
<li><strong>Atención al Cliente:</strong> Resolver dudas y proporcionar soporte a través del punto único de contacto <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</li>
<li><strong>Cumplimiento Legal:</strong> Emitir facturas y cumplir con las obligaciones tributarias ante la AEAT.</li>
<li><strong>Seguridad:</strong> Prevenir fraudes y usos no autorizados de la plataforma.</li>
</ul>
<h2>5. Legitimación</h2>
<p>La base legal para el tratamiento es:</p>
<ul>
<li><strong>Ejecución Contractual:</strong> Necesaria para procesar su reserva y prestarle el servicio solicitado.</li>
<li><strong>Obligación Legal:</strong> Derivada de la normativa fiscal y mercantil vigente en España.</li>
<li><strong>Consentimiento:</strong> Otorgado explÍcitamente al marcar la casilla de aceptación en nuestros formularios.</li>
</ul>
<h2>6. Conservación y Destinatarios</h2>
<p><strong>Plazos:</strong> Los datos se conservarán durante el tiempo que dure la relación comercial y, posteriormente, durante los plazos legales de prescripción (generalmente 6 años para documentos contables según el Código de Comercio).</p>
<p><strong>Cesiones:</strong> No se cederán datos a terceros ajenos a la operativa del servicio, salvo obligación legal ante autoridades competentes.</p>
<h2>7. Derechos del Interesado</h2>
<p>Usted puede ejercer sus derechos de Acceso, Rectificación, Supresión, Limitación, Portabilidad y Oposición enviando una comunicación escrita acompañada de copia de su DNI a: <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p>
<p>Asimismo, tiene derecho a retirar su consentimiento en cualquier momento y a presentar una reclamación ante la Agencia Española de Protección de Datos (AEPD) si considera que sus derechos han sido vulnerados.</p>',
        'cookie' => '<h2>1. Responsable del sitio web</h2>
<p><strong>Razón social:</strong> METRANSFERS GESTION SL</p>
<p><strong>NIF:</strong> B22522353</p>
<p><strong>Domicilio:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÍ (BARCELONA)</p>
<p><strong>Correo electrónico:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p>
<h2>2. Qué son las cookies</h2>
<p>Las cookies son pequeños archivos que se descargan en su dispositivo al acceder a determinadas páginas web. Permiten, entre otras cosas, reconocer su navegador, mantener la sesión, recordar preferencias, reforzar la seguridad o facilitar determinadas funcionalidades técnicas del sitio.</p>
<h2>3. Tipos de cookies</h2>
<p>Las cookies pueden clasificarse, entre otros criterios, del siguiente modo:</p>
<ul>
<li><strong>Según la entidad que las gestione:</strong> cookies propias y cookies de terceros.</li>
<li><strong>Según su finalidad:</strong> cookies técnicas o necesarias, de preferencias o personalización, de análisis, y de publicidad o publicidad comportamental.</li>
<li><strong>Según el tiempo que permanecen activas:</strong> cookies de sesión y cookies persistentes.</li>
</ul>
<h2>4. Cookies utilizadas en metransfers.es</h2>
<p>Con carácter general, este sitio utiliza o puede utilizar cookies técnicas, de sesión y de preferencia estrictamente relacionadas con el funcionamiento de la web y la prestación del servicio solicitado por el usuario. Entre ellas se incluyen, cuando proceda:</p>
<ul>
<li><strong>Cookies técnicas de navegación y seguridad:</strong> necesarias para cargar la web, proteger formularios, prevenir usos abusivos y garantizar el funcionamiento básico del sitio.</li>
<li><strong>Cookies asociadas al proceso de reserva o contacto:</strong> necesarias para gestionar solicitudes enviadas por el usuario, mantener datos temporales de sesión y completar procesos esenciales vinculados al servicio contratado.</li>
<li><strong>Cookies de preferencias:</strong> destinadas a recordar opciones expresamente solicitadas por el usuario, como el idioma o determinadas configuraciones de visualización, cuando estas funcionalidades estén habilitadas.</li>
<li><strong>Cookies técnicas de terceros vinculadas al servicio:</strong> determinados proveedores externos integrados en la web, como herramientas de traducción, mapas, contenidos embebidos o pasarelas de pago seguras, pueden instalar sus propias cookies cuando el usuario interactúa con dichas funcionalidades.</li>
</ul>
<p>Este tema no instala por sÍ mismo cookies de publicidad comportamental. Si en el futuro se incorporan herramientas analÍticas no exentas, servicios de personalización avanzada o soluciones publicitarias que requieran consentimiento, se informará al usuario de forma previa y se recabará la autorización correspondiente antes de su activación.</p>
<h2>5. Base jurÍdica</h2>
<p>Las cookies técnicas o estrictamente necesarias pueden utilizarse sin consentimiento previo cuando resultan imprescindibles para prestar el servicio solicitado por el usuario o para posibilitar la navegación segura por el sitio web. Las cookies no necesarias solo podrán utilizarse cuando exista una base jurÍdica adecuada y, en los casos exigidos por la normativa, tras obtener el consentimiento informado del usuario.</p>
<h2>6. Plazo de conservación</h2>
<p>Las cookies de sesión permanecen activas únicamente mientras el usuario navega por el sitio y se eliminan al cerrar el navegador. Las cookies persistentes, cuando existan, se conservarán durante el tiempo estrictamente necesario para cumplir su finalidad o hasta que el usuario las elimine manualmente desde la configuración de su navegador o del servicio correspondiente.</p>
<h2>7. Gestión, configuración y desactivación</h2>
<p>El usuario puede permitir, bloquear o eliminar las cookies instaladas en su dispositivo mediante la configuración de su navegador. Debe tener en cuenta que la desactivación de cookies técnicas o necesarias puede afectar al correcto funcionamiento del sitio, del proceso de reserva o de determinadas funcionalidades esenciales.</p>
<ul>
<li><a href="https://support.google.com/chrome/answer/95647?hl=es" target="_blank" rel="noopener">Configurar cookies en Google Chrome</a></li>
<li><a href="https://support.mozilla.org/es/kb/proteccion-antirrastreo-mejorada-firefox-escritorio" target="_blank" rel="noopener">Configurar cookies en Mozilla Firefox</a></li>
<li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Configurar cookies en Safari</a></li>
<li><a href="https://support.microsoft.com/es-es/microsoft-edge/administrar-cookies-en-microsoft-edge-ver-permitir-bloquear-eliminar-y-usar-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener">Configurar cookies en Microsoft Edge</a></li>
</ul>
<h2>8. Cookies de terceros</h2>
<p>La aceptación, configuración y uso de cookies de terceros se rige por las polÍticas propias de dichos proveedores. METRANSFERS GESTION SL no puede controlar en todo momento las actualizaciones que esos terceros realicen en sus polÍticas, por lo que se recomienda al usuario revisar directamente sus condiciones cuando interactúe con herramientas externas integradas o enlazadas desde la web.</p>
<h2>9. Información adicional y contacto</h2>
<p>Para obtener más información sobre el tratamiento de datos personales, puede consultar nuestra <a href="' . home_url( '/privacidad' ) . '">PolÍtica de Privacidad</a>. Si necesita aclaraciones sobre el uso de cookies en este sitio web, puede escribir a <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p>
<p>La presente PolÍtica de Cookies podrá actualizarse cuando se produzcan cambios normativos, técnicos o funcionales en el sitio web. Se recomienda revisarla periódicamente.</p>',
        'terminos-y-condiciones' => '<h2>1. MARCO LEGAL APLICABLE</h2>
<p>El presente contrato se rige por lo dispuesto en la legislación española vigente, especÍficamente:</p>
<ul>
<li>Ley 16/1987, de 30 de julio, de Ordenación de los Transportes Terrestres (LOTT) y su Reglamento (ROTT).</li>
<li>Ley 34/2002 (LSSI-CE) sobre servicios de la sociedad de la información.</li>
<li>Real Decreto Legislativo 1/2007, por el que se aprueba el texto refundido de la Ley General para la Defensa de los Consumidores y Usuarios.</li>
<li>Reglamento (UE) 2016/679 (RGPD) en materia de protección de datos.</li>
</ul>
<h2>2. IDENTIFICACIÓN DE LAS PARTES</h2>
<p><strong>El Prestador:</strong> METRANSFERS GESTION SL, con NIF B22522353 y domicilio social en AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÍ (BARCELONA).</p>
<p><strong>El Cliente:</strong> Persona fÍsica o jurÍdica que formaliza la reserva y garantiza tener capacidad legal para contratar.</p>
<h2>3. OBLIGACIÓN DE NOTIFICACIÓN Y REQUISITOS DEL SERVICIO</h2>
<p>Para garantizar la seguridad y legalidad del transporte, el Cliente tiene la obligación inexcusable de declarar las siguientes necesidades en el formulario de reserva:</p>
<h3>3.1. Sistemas de Retención Infantil (SRI)</h3>
<p>Conforme al ArtÍculo 117 del Reglamento General de Circulación, es obligatorio el uso de sillas homologadas para menores de estatura igual o inferior a 135 cm. El Cliente debe seleccionar el número y tipo de sillas necesarias en el formulario. La omisión de este dato facultará al conductor a denegar el servicio por razones de seguridad, sin derecho a reembolso.</p>
<h3>3.2. Equipaje Extraordinario</h3>
<p>La capacidad del vehículo está limitada por su ficha técnica. El transporte de maletas adicionales, material deportivo (golf, esquÍ) o bultos voluminosos debe ser notificado. EL PRESTADOR se reserva el derecho de cobrar suplementos o denegar el transporte si el volumen excede la capacidad del maletero del vehículo contratado.</p>
<h3>3.3. Transporte de Mascotas</h3>
<p>El transporte de animales domésticos está sujeto a notificación previa y debe realizarse en trasportines homologados proporcionados por el cliente, salvo acuerdo en contrario. Los perros guÍa viajarán sin coste adicional conforme a la normativa vigente.</p>
<h2>4. PASARELA DE PAGO Y SEGURIDAD (REDSYS)</h2>
<p>El pago de los servicios se efectuará mediante tarjeta de crédito o débito a través de la pasarela de pago segura Redsys.</p>
<ul>
<li><strong>Seguridad:</strong> El sistema utiliza protocolos de encriptación SSL y autenticación 3D Secure (Verified by Visa / Mastercard ID Check).</li>
<li><strong>Confirmación:</strong> El contrato se perfecciona en el momento en que EL PRESTADOR recibe la confirmación de la autorización de pago por parte de la entidad bancaria.</li>
<li><strong>Fraude:</strong> EL PRESTADOR se reserva el derecho de anular cualquier transacción ante sospechas de uso fraudulento de tarjetas.</li>
</ul>
<h2>5. DERECHO DE DESISTIMIENTO Y POLÍTICA DE CANCELACIÓN</h2>
<p>En virtud del ArtÍculo 103 l) del Real Decreto Legislativo 1/2007, el derecho de desistimiento no será aplicable a los servicios de transporte de pasajeros si el contrato prevé una fecha o un periodo de ejecución especÍficos. No obstante, EL PRESTADOR ofrece las siguientes condiciones comerciales:</p>
<ul>
<li><strong>Cancelación con &gt;24 horas:</strong> Devolución del 100% del importe mediante el mismo sistema de pago (Redsys).</li>
<li><strong>Cancelación con &lt;24 horas o No-Show:</strong> Penalización del 100% del valor de la reserva.</li>
<li><strong>Retrasos de vuelos:</strong> EL PRESTADOR monitoriza los vuelos. No obstante, si el retraso excede los 90 minutos sobre la hora prevista, el servicio quedará sujeto a disponibilidad de flota, pudiendo incurrir en gastos de espera adicionales.</li>
</ul>
<h2>6. RESPONSABILIDAD LIMITADA</h2>
<p>EL PRESTADOR no será responsable por incumplimientos derivados de:</p>
<ul>
<li>Fuerza mayor o causas fortuitas (cortes de carretera, condiciones climáticas adversas, huelgas generales).</li>
<li>Errores en los datos facilitados por el cliente en el formulario de reserva (ej. fecha errónea o número de teléfono incorrecto).</li>
<li>Incumplimiento de las normas de seguridad por parte de los pasajeros (uso de cinturón, comportamiento disruptivo).</li>
</ul>
<h2>7. JURISDICCIÓN Y LEY APLICABLE</h2>
<p>Para la resolución de cualquier litigio derivado de la interpretación o ejecución de este contrato, las partes se someten a la legislación española. En caso de controversia, se recurrirá a los Juzgados y Tribunales de Barcelona, salvo que el cliente ostente la condición de consumidor, en cuyo caso se atenderá a la competencia territorial establecida por ley.</p>',
        'aviso-legal' => '<h2>1. INFORMACIÓN IDENTIFICATIVA</h2>
<p>En cumplimiento con el deber de información recogido en el artÍculo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y del Comercio Electrónico (LSSI-CE), a continuación se reflejan los siguientes datos:</p>
<p><strong>Titular del sitio web:</strong> METRANSFERS GESTION SL</p>
<p><strong>NIF:</strong> B22522353</p>
<p><strong>Domicilio Social:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÍ &ndash; (BARCELONA)</p>
<p><strong>Correo electrónico de contacto:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p>
<p><strong>Actividad:</strong> Transporte de viajeros y gestión de servicios turÍsticos.</p>
<h2>2. CONDICIONES DE USO</h2>
<p>El acceso y/o uso de este portal atribuye la condición de USUARIO, que acepta, desde dicho acceso y/o uso, las Condiciones Generales de Uso aquÍ reflejadas.</p>
<p>El sitio web proporciona acceso a informaciones, servicios o datos (en adelante, &ldquo;los contenidos&rdquo;) en Internet pertenecientes a METRANSFERS GESTION SL. El USUARIO asume la responsabilidad del uso del portal. Dicha responsabilidad se extiende al registro que fuese necesario para acceder a determinados servicios o contenidos (como el formulario de reservas).</p>
<h2>3. PROPIEDAD INTELECTUAL E INDUSTRIAL</h2>
<p>METRANSFERS GESTION SL es titular de todos los derechos de propiedad intelectual e industrial de su página web, asÍ como de los elementos contenidos en la misma (a tÍtulo enunciativo: imágenes, sonido, audio, vÍdeo, software o textos; marcas o logotipos, combinaciones de colores, estructura y diseño, selección de materiales usados, programas de ordenador necesarios para su funcionamiento, acceso y uso, etc.).</p>
<p>En virtud de lo dispuesto en los artÍculos 8 y 32.1, párrafo segundo, de la Ley de Propiedad Intelectual, quedan expresamente prohibidas la reproducción, la distribución y la comunicación pública, incluida su modalidad de puesta a disposición, de la totalidad o parte de los contenidos de esta página web, con fines comerciales, en cualquier soporte y por cualquier medio técnico, sin la autorización de METRANSFERS GESTION SL.</p>
<h2>4. EXCLUSIÓN DE GARANTÍAS Y RESPONSABILIDAD</h2>
<p>EL PRESTADOR no se hace responsable, en ningún caso, de los daños y perjuicios de cualquier naturaleza que pudieran ocasionar, a tÍtulo enunciativo: errores u omisiones en los contenidos, falta de disponibilidad del portal o la transmisión de virus o programas maliciosos o lesivos en los contenidos, a pesar de haber adoptado todas las medidas tecnológicas necesarias para evitarlo.</p>
<h2>5. MODIFICACIONES</h2>
<p>METRANSFERS GESTION SL se reserva el derecho de efectuar sin previo aviso las modificaciones que considere oportunas en su portal, pudiendo cambiar, suprimir o añadir tanto los contenidos y servicios que se presten a través de la misma como la forma en la que éstos aparezcan presentados o localizados en su portal.</p>
<h2>6. ENLACES (LINKS)</h2>
<p>En el caso de que en el sitio web se dispusiesen enlaces o hipervÍnculos hacÍa otros sitios de Internet, METRANSFERS GESTION SL no ejercerá ningún tipo de control sobre dichos sitios y contenidos. En ningún caso asumirá responsabilidad alguna por los contenidos de algún enlace perteneciente a un sitio web ajeno.</p>
<h2>7. DERECHO DE EXCLUSIÓN</h2>
<p>METRANSFERS GESTION SL se reserva el derecho a denegar o retirar el acceso al portal y/o los servicios ofrecidos sin necesidad de preaviso, a instancia propia o de un tercero, a aquellos usuarios que incumplan las presentes Condiciones Generales de Uso.</p>
<h2>8. PROTECCIÓN DE DATOS</h2>
<p>Todo lo relativo a la polÍtica de protección de datos se encuentra recogido en el documento de PolÍtica de Privacidad de la entidad, conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley Orgánica 3/2018 (LOPDGDD).</p>
<h2>9. LEGISLACIÓN APLICABLE Y JURISDICCIÓN</h2>
<p>La relación entre METRANSFERS GESTION SL y el USUARIO se regirá por la normativa española vigente y cualquier controversia se someterá a los Juzgados y Tribunales de la ciudad de Barcelona.</p>'
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
 * Migration v4 — Populate post_content from PHP catalog so pages are editable
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

    // ── 1. DESTINOS ──────────────────────────────────────────────────────────
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
                'Si estás organizando un traslado hacia %s, podemos prepararte una propuesta adaptada al punto de recogida, número de pasajeros, fecha estimada y tipo de servicio que necesites.',
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

    // ── 2. TOURS ─────────────────────────────────────────────────────────────
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

    // ── 3. SERVICIOS PRINCIPALES ─────────────────────────────────────────────
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
            $content .= '<h3>Características del servicio</h3>' . "\n<ul>\n";
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
add_action( 'admin_init', 'me_transfers_populate_editor_content_v4' );




// 1. Optimización WPO: Forzar WebP como formato de salida y forzar lazy load
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

// 2. Limpieza de Metadatos: Forzar TÍtulo y Descripción Global para Home
add_filter( 'pre_get_document_title', function( $title ) {
    if ( is_front_page() || is_home() ) {
        return 'MeTransfers | Traslados Privados y Tours privados en Barcelona';
    }
    return $title;
}, 999 );

add_action( 'wp_head', function() {
    // Only output meta description if Yoast SEO is NOT active (avoids duplication).
    if ( ( is_front_page() || is_home() ) && ! defined( 'WPSEO_VERSION' ) && ! function_exists( 'the_seo_framework' ) ) {
        echo '<meta name="description" content="Traslados Privados y Tours privados en Barcelona. Reserva tu servicio de chófer privado en MeTransfers para un viaje seguro, cómodo y exclusivo.">' . "\n";
    }

    // Inyectar noindex si no es dominio de producción (protección staging)
    if ( ! str_contains( home_url(), 'metransfers.es' ) ) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    }
}, 1 );

// 3.1b: Filtros de integracion con Yoast SEO para titulo y meta description del home.
// Si Yoast esta activo, usar sus filtros nativos evita duplicar etiquetas SEO.
if ( defined( 'WPSEO_VERSION' ) ) {
    add_filter( 'wpseo_title', function( $title ) {
        if ( is_front_page() || is_home() ) {
            return 'MeTransfers | Traslados Privados y Tours privados en Barcelona';
        }
        return $title;
    } );

    add_filter( 'wpseo_metadesc', function( $desc ) {
        if ( is_front_page() || is_home() ) {
            return 'Traslados Privados y Tours privados en Barcelona. Reserva tu servicio de chofer privado en MeTransfers.';
        }
        return $desc;
    } );
}

// 3. Motor de Redirecciones 301, 410 y Smart Redirect (SEO 404 Recovery)
add_action( 'template_redirect', 'me_transfers_custom_redirects' );
function me_transfers_custom_redirects() {
    if ( is_404() ) {
        $path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
        $path = trailingslashit( '/' . trim( $path, '/' ) );
        
        // Array de redirecciones 301 exactas. Formato: '/url-antigua/' => '/url-nueva/'
        $redirects_301 = array(
            '/transporte-en-barcelona-para-grupos-grandes-y-equipaje-extra-la-solucion-mercedes-clase-v/' => '/tours/',
            '/taxis-privado-barcelona/'           => '/traslados-privados/',
            '/taxis-barcelona-port-aventura/'     => '/destinos/portaventura/',
            '/taxis-barcelona-salou/'             => '/rutas/barcelona-salou/',
            '/taxis-barcelona-costa-brava/'       => '/destinos/costa-brava/',
            '/taxis-barcelona-girona/'            => '/destinos/girona/',
            '/taxis-barcelona-tossa-de-mar/'      => '/destinos/tossa-de-mar/',
            '/traslados-barcelona-tossa-de-mar/'  => '/destinos/tossa-de-mar/',
        );

        if ( isset( $redirects_301[ $path ] ) ) {
            wp_safe_redirect( home_url( $redirects_301[ $path ] ), 301 );
            exit;
        }
        
        // Array de URLS 410 (Gone) para eliminar definitivamente de Google
        $gone_urls = array(
            '/taxis-barcelona-andorra/',
            '/taxis-barcelona-taull/',
            '/taxis-barcelona-vielha/',
            '/taxis-barcelona-cadaques/',
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
            '/traslados-barcelona-andorra/',
            '/traslados-barcelona-taull/',
            '/traslados-barcelona-vielha/',
            '/traslados-barcelona-cadaques/',
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

        // SMART REDIRECT ELIMINADO: El sistema anterior hacÍa una WP_Query con búsqueda de texto
        // libre y redirigÍa 301 permanentemente al primer resultado. Esto podía enviar a Google
        // y a los usuarios a páginas completamente no equivalentes, dañando el SEO.
        //
        // Si necesitas recuperar una URL especÍfica, añade la redirección manualmente
        // en el array $redirects_301 de arriba con la URL de destino correcta.
    }
}

// ==========================================
// 1. Theme Support & Setup
// ==========================================
require_once get_template_directory() . '/mt-seo-importer.php';
// No ejecutar automáticamente.
// add_action( 'admin_init', 'mt_run_seo_importer_phase_1' );

// Herramienta nativa para construir y publicar rutas de la Fase 1
require_once get_template_directory() . '/includes/admin-route-builder.php';

// ==========================================
// 4. AJAX Handlers for Leads (Form & WhatsApp)
// ==========================================
add_action( 'wp_ajax_mt_save_lead', 'mt_ajax_save_lead' );
add_action( 'wp_ajax_nopriv_mt_save_lead', 'mt_ajax_save_lead' );

// ELIMINADO: El bloque AUTO-CREAR PÁGINAS ESENCIALES re-publicaba /contacto y /reservaciones
// en cada carga de WordPress, incluso si se habÍan dejado como borrador o papelera intencionalmente.
// Las páginas esenciales deben gestionarse manualmente desde el panel de WordPress.
// Si las páginas no existen, créalas una vez desde Páginas > Añadir nueva.

function mt_ajax_save_lead() {
    check_ajax_referer( 'mt_lead_nonce', 'security' );

    $origen   = isset( $_POST['origen'] )   ? sanitize_text_field( $_POST['origen'] )      : 'formulario';
    $nombre   = isset( $_POST['nombre'] )   ? sanitize_text_field( $_POST['nombre'] )      : '';
    $email    = isset( $_POST['email'] )    ? sanitize_email( $_POST['email'] )            : '';
    $telefono = isset( $_POST['telefono'] ) ? sanitize_text_field( $_POST['telefono'] )    : '';
    $servicio = isset( $_POST['servicio'] ) ? sanitize_text_field( $_POST['servicio'] )    : '';
    $mensaje  = isset( $_POST['mensaje'] )  ? sanitize_textarea_field( $_POST['mensaje'] ) : '';
    $gdpr     = isset( $_POST['gdpr_aceptado'] ) && '1' === $_POST['gdpr_aceptado'] ? '1' : '0';

    // Fecha del servidor — no confiar en el reloj del navegador
    $gdpr_fecha_servidor = current_time( 'c' );
    // Versión de la polÍtica activa (incrementar manualmente al actualizar la polÍtica)
    $gdpr_version = '2025-01-01';

    // Validación: nombre obligatorio
    if ( empty( trim( $nombre ) ) ) {
        wp_send_json_error( array( 'message' => 'El nombre es obligatorio.' ) );
        return;
    }

    // Validación: email o teléfono según el origen
    if ( $origen === 'whatsapp' ) {
        if ( empty( trim( $telefono ) ) ) {
            wp_send_json_error( array( 'message' => 'El teléfono es obligatorio.' ) );
            return;
        }
    } else {
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Introduce un correo electrónico válido.' ) );
            return;
        }
    }

    // Validación: longitudes máximas para prevenir abuso
    if ( mb_strlen( $nombre ) > 120 || mb_strlen( $mensaje ) > 3000 || mb_strlen( $telefono ) > 30 ) {
        wp_send_json_error( array( 'message' => 'Algún campo supera la longitud permitida.' ) );
        return;
    }

    // Validación: consentimiento GDPR obligatorio
    if ( '1' !== $gdpr ) {
        wp_send_json_error( array( 'message' => 'Debes aceptar la polÍtica de privacidad.' ) );
        return;
    }

    $title = $nombre . ' - ' . date_i18n( 'd/m/Y H:i' );

    $post_data = array(
        'post_title'  => $title,
        'post_type'   => 'mensaje',
        'post_status' => 'private', // Privado: no accesible públicamente, visible sólo para admins
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

        // Enviar notificación por email
        $to      = get_option( 'admin_email', 'info@metransfers.es' );
        $subject = 'Nueva consulta web: ' . $nombre;

        $body  = "Nueva consulta desde la web de MeTransfers.\n\n";
        $body .= "Nombre: {$nombre}\n";
        $body .= "Email: {$email}\n";
        $body .= "Teléfono: {$telefono}\n";
        $body .= "Servicio: {$servicio}\n";
        $body .= "Origen: {$origen}\n\n";
        $body .= "Mensaje:\n{$mensaje}\n\n";
        $body .= "GDPR: Aceptado (servidor: {$gdpr_fecha_servidor}, polÍtica v{$gdpr_version})\n";
        $body .= "Gestionar: " . admin_url( 'edit.php?post_type=mensaje' ) . "\n";

        $headers    = array( 'Reply-To: ' . $nombre . ' <' . $email . '>' );
        $mail_sent  = wp_mail( $to, $subject, $body, $headers );

        // Registrar si el correo falló (sin bloquear la respuesta al cliente)
        if ( ! $mail_sent ) {
            update_post_meta( $post_id, '_mt_email_fallido', '1' );
        }

        wp_send_json_success( array( 'message' => '¡Solicitud recibida correctamente! Te responderemos muy pronto.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Error al guardar la solicitud. Por favor, inténtalo de nuevo.' ) );
    }
}

// ==========================================
// 5. Force-assign template-servicio.php to all service pages
//    and create missing pages (chofer-por-horas, grupos, etc.)
// ==========================================
// DESACTIVADO: mt_ensure_service_pages_and_templates() creaba y publicaba páginas de servicios
// automáticamente cada 24h, sin revisión editorial. Las páginas de servicios deben crearse
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

// DESACTIVADO: mt_ensure_seo_pages() generaba automáticamente 35 páginas /taxis-* y /traslados-*
// que competÍan directamente con el CPT /rutas/* y /destinos/*.
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
    // Generación dinámica de 30 landings (15 taxis, 15 traslados)
    $destinos_dinamicos = array(
        'Andorra',
        'Taüll',
        'Vielha',
        'Tossa de Mar',
        'Cadaqués',
        'Besalú',
        'Bagur',
        'Delta del Ebro',
        'PeñÍscola',
        'Morella',
        'Altea',
        'Valderrobres',
        'Alquézar',
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
 * SEO Title: Separado del H1 visible.
 * - Para el CPT "ruta": "Transfer privado [Origen]–[Destino] | MeTransfers"
 * - Para el resto: normaliza el nombre de la marca.
 */
add_filter( 'document_title_parts', function( $title ) {
    // Normalizar marca
    if ( isset( $title['site'] ) ) {
        $title['site'] = str_replace( 'Me Transfers', 'MeTransfers', $title['site'] );
    }
    if ( isset( $title['title'] ) ) {
        $title['title'] = str_replace( 'Me Transfers', 'MeTransfers', $title['title'] );
    }

    // TÍtulo SEO especÍfico para rutas comerciales
    if ( is_singular( 'ruta' ) ) {
        $post_id = get_the_ID();
        $origen  = get_post_meta( $post_id, '_mt_ruta_origen',  true );
        $destino = get_post_meta( $post_id, '_mt_ruta_destino', true );

        if ( $origen && $destino ) {
            $title['title'] = sprintf( 'Transfer privado %s–%s', $origen, $destino );
        }
        // La marca siempre al final, nunca al principio
        $title['site'] = 'MeTransfers';
    }

    return $title;
}, 99 );

add_filter( 'option_blogname', function( $name ) {
	return str_replace( 'Me Transfers', 'MeTransfers', $name );
} );

/**
 * Inject SEO 10/10 Structured Data
 */
add_action( 'wp_head', function() {
	$schema = array();

	// 1. Organization (Solo en la portada)
	if ( is_front_page() ) {
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
			'areaServed' => array( 'Barcelona', 'Cataluña', 'España', 'Andorra' ),
		);

		// Preload LCP image for front page
		echo '<link rel="preload" as="image" href="' . esc_url( get_template_directory_uri() . '/assets/img/V2.webp' ) . '" fetchpriority="high">' . "\n";
	}

	// 2. Breadcrumbs & Service (Páginas de servicio, tours, destinos, rutas)
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
				'serviceType' => 'Traslado privado con chófer',
				'provider' => array( '@id' => home_url( '/#organization' ) ),
				'areaServed' => $area_served,
				'url' => get_permalink(),
			);

			$breadcrumbs[] = array(
				'@type' => 'ListItem',
				'position' => 2,
				'name' => 'Rutas',
				'item' => home_url( '/#rutas' ), // Enlace genérico o al hub si existe
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
				'serviceType' => 'Traslado privado con chófer',
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

		if ( count( $breadcrumbs ) > 1 ) {
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
// ROBOTS: Controlar indexación por entorno
// En wp-config.php staging: define('WP_ENVIRONMENT_TYPE','staging');
// En wp-config.php producción: define('WP_ENVIRONMENT_TYPE','production');
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
	
	// 3. Internacionalización incompleta (todo lo que no sea 'es')
	if ( function_exists( 'mt_lang' ) && mt_lang() !== 'es' ) {
		return array_merge( $robots, [ 'noindex' => true, 'follow' => true ] );
	}

	// 4. Umbral de calidad para rutas (Phase 2)
	if ( is_singular( 'ruta' ) ) {
		$precio = get_post_meta( get_the_ID(), '_mt_ruta_precio', true );
		if ( empty( $precio ) ) {
			return array_merge( $robots, [ 'noindex' => true, 'follow' => true ] );
		}
	}

	return $robots;
}, 99 );

// ==========================================
// FORZAR INDEXACIÓN DE PÁGINAS ESPECÍFICAS
// ==========================================
// Sobrescribir directivas de Yoast SEO
add_filter( 'wpseo_robots', function( $robotsval ) {
	if ( is_page( ['preguntas-frecuentes', 'reservas-metransfers'] ) ) {
		return 'index, follow';
	}
	return $robotsval;
} );

// Sobrescribir directivas nativas de WordPress (por si Yoast está inactivo)
add_filter( 'wp_robots', function( $robots ) {
	if ( is_page( ['preguntas-frecuentes', 'reservas-metransfers'] ) ) {
		if ( isset( $robots['noindex'] ) ) unset( $robots['noindex'] );
		$robots['index'] = true;
		$robots['follow'] = true;
	}
	return $robots;
}, 100 ); // Prioridad 100 para ejecutar después del filtro del tema

// ==========================================
// YOAST SEO: Excluir rutas de baja calidad del sitemap
// ==========================================
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function( $excluded ) {
	$args = array(
		'post_type'      => 'ruta',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => '_mt_ruta_precio',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_mt_ruta_precio',
				'value'   => '',
				'compare' => '=',
			),
			'relation' => 'OR',
		),
	);
	$poor_routes = get_posts( $args );
	
	if ( ! empty( $poor_routes ) ) {
		$excluded = array_merge( $excluded, $poor_routes );
	}
	
	return $excluded;
} );

// Excluir taxonomías y tipos de contenido irrelevantes del Sitemap
add_filter( 'wpseo_sitemap_exclude_taxonomy', function( $exclude, $taxonomy ) {
	// Excluir etiquetas (tags) y categorías vacías si las hay
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

// ==========================================
// YOAST SEO: Optimización de CTR para Home y Hubs
// ==========================================
add_filter( 'wpseo_title', function( $title ) {
	if ( is_front_page() || is_home() ) {
		return 'Traslados Privados y Taxis en Barcelona | Precio Cerrado | MeTransfers';
	}
	if ( is_page( 'taxis-privado-barcelona' ) ) {
		return 'Taxi Privado en Barcelona - Traslados VIP y Premium | MeTransfers';
	}
	if ( is_page( 'taxis-barcelona-costa-brava' ) ) {
		return 'Traslados Barcelona - Costa Brava | Taxis Privados | MeTransfers';
	}
	if ( is_page( 'taxis-barcelona-salou' ) ) {
		return 'Traslado Barcelona a Salou | Taxi Privado Directo | MeTransfers';
	}
	if ( is_page( 'taxis-barcelona-port-aventura' ) ) {
		return 'Taxi Barcelona a PortAventura | Traslados Privados | MeTransfers';
	}
	if ( is_page( 'taxis-barcelona-girona' ) ) {
		return 'Traslados Barcelona - Girona | Aeropuerto y Ciudad | MeTransfers';
	}
	return $title;
} );

add_filter( 'wpseo_metadesc', function( $desc ) {
	if ( is_front_page() || is_home() ) {
		return 'Reserva tu traslado privado en Barcelona con MeTransfers. Vehículos Mercedes, conductores profesionales y precios fijos sin sorpresas. Cancelación gratuita.';
	}
	if ( is_page( 'taxis-privado-barcelona' ) ) {
		return 'Servicio de taxi privado y chófer en Barcelona. Traslados al aeropuerto, puerto y eventos en vehículos de alta gama. Reserva con precio cerrado.';
	}
	if ( is_page( 'taxis-barcelona-costa-brava' ) ) {
		return 'Viaja a la Costa Brava desde Barcelona con total comodidad. Taxis privados y traslados puerta a puerta. Espacio para equipaje garantizado.';
	}
	if ( is_page( 'taxis-barcelona-salou' ) ) {
		return 'Reserva tu traslado directo desde Barcelona o el Aeropuerto de El Prat hasta Salou. Conductores expertos y vehículos cómodos. Precio fijo.';
	}
	if ( is_page( 'taxis-barcelona-port-aventura' ) ) {
		return 'Traslados privados a PortAventura desde Barcelona. La forma más rápida y cómoda de viajar en familia o con amigos. Sillas infantiles bajo petición.';
	}
	if ( is_page( 'taxis-barcelona-girona' ) ) {
		return 'Conexión directa entre Barcelona y Girona. Traslados privados al aeropuerto o centro de la ciudad. Vehículos Mercedes y atención personalizada.';
	}
	return $desc;
} );

/**
 * Full restoration of legal pages (Title, Slug, Content) to fix 404s and empty content.
 */
// DESACTIVADO: mt_full_restore_legal_pages_once() sobreescribÍa el contenido de las páginas
// legales con texto hardcodeado en functions.php en cada instalación nueva.
// Las páginas legales deben editarse desde el panel de WordPress. El contenido de esta
// función se conserva como referencia pero NO debe re-activarse: cualquier actualización del
// tema sobreescribirÍa cambios legales aprobados.
// add_action( 'admin_init', 'mt_full_restore_legal_pages_once' );
function mt_full_restore_legal_pages_once() {
    if ( get_transient( 'mt_full_restored_legal_pages_v1' ) ) {
        return;
    }
    
    // The exact content from the original XML for the Spanish pages
    $legal_pages = array(
        'politica-de-privacidad' => array(
            'title' => 'PolÍtica de privacidad',
            'content' => '<h2>1. Identificaci&oacute;n del Responsable del Tratamiento</h2><p><strong>Raz&oacute;n Social:</strong> METRANSFERS GESTION SL</p><p><strong>NIF:</strong> B22522353</p><p><strong>Domicilio Fiscal:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESP&Iacute; &ndash; (BARCELONA)</p><p><strong>Contacto Privacidad:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p><h2>2. Aceptaci&oacute;n Vinculante</h2><p>Al utilizar nuestros servicios, navegar por nuestra plataforma o completar el proceso de configuraci&oacute;n de una reserva, usted reconoce haber le&iacute;do, comprendido y aceptado sin reservas que sus datos personales sean tratados conforme a los t&eacute;rminos aqu&iacute; expuestos. La formalizaci&oacute;n de una reserva constituye un contrato entre las partes, legitimando el tratamiento de los datos necesarios para la ejecuci&oacute;n del servicio.</p><h2>3. Datos Objeto de Tratamiento</h2><p>Recopilamos los datos estrictamente necesarios para la prestaci&oacute;n del servicio:</p><ul><li><strong>Datos de Reserva:</strong> Nombre, apellidos, tel&eacute;fono, correo electr&oacute;nico y detalles del trayecto/servicio solicitado.</li><li><strong>Datos de Facturaci&oacute;n:</strong> Direcci&oacute;n postal y NIF/DNI (seg&uacute;n los datos de registro fiscal de la entidad).</li><li><strong>Datos de Navegaci&oacute;n:</strong> Direcci&oacute;n IP, cookies y metadatos para garantizar la seguridad del sitio.</li></ul><h2>4. Finalidad del Tratamiento</h2><p>Sus datos ser&aacute;n tratados con el fin de:</p><ul><li><strong>Gesti&oacute;n de Reservas:</strong> Tramitar, confirmar y ejecutar los servicios de transporte o gesti&oacute;n contratados.</li><li><strong>Atenci&oacute;n al Cliente:</strong> Resolver dudas y proporcionar soporte a trav&eacute;s del punto &uacute;nico de contacto <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</li><li><strong>Cumplimiento Legal:</strong> Emitir facturas y cumplir con las obligaciones tributarias ante la AEAT.</li><li><strong>Seguridad:</strong> Prevenir fraudes y usos no autorizados de la plataforma.</li></ul><h2>5. Legitimaci&oacute;n</h2><p>La base legal para el tratamiento es:</p><ul><li><strong>Ejecuci&oacute;n Contractual:</strong> Necesaria para procesar su reserva y prestarle el servicio solicitado.</li><li><strong>Obligaci&oacute;n Legal:</strong> Derivada de la normativa fiscal y mercantil vigente en Espa&ntilde;a.</li><li><strong>Consentimiento:</strong> Otorgado expl&iacute;citamente al marcar la casilla de aceptaci&oacute;n en nuestros formularios.</li></ul><h2>6. Conservaci&oacute;n y Destinatarios</h2><p><strong>Plazos:</strong> Los datos se conservar&aacute;n durante el tiempo que dure la relaci&oacute;n comercial y, posteriormente, durante los plazos legales de prescripci&oacute;n (generalmente 6 a&ntilde;os para documentos contables seg&uacute;n el C&oacute;digo de Comercio).</p><p><strong>Cesiones:</strong> No se ceder&aacute;n datos a terceros ajenos a la operativa del servicio, salvo obligaci&oacute;n legal ante autoridades competentes.</p><h2>7. Derechos del Interesado</h2><p>Usted puede ejercer sus derechos de Acceso, Rectificaci&oacute;n, Supresi&oacute;n, Limitaci&oacute;n, Portabilidad y Oposici&oacute;n enviando una comunicaci&oacute;n escrita acompa&ntilde;ada de copia de su DNI a: <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p><p>Asimismo, tiene derecho a retirar su consentimiento en cualquier momento y a presentar una reclamaci&oacute;n ante la Agencia Espa&ntilde;ola de Protecci&oacute;n de Datos (AEPD) si considera que sus derechos han sido vulnerados.</p>'
        ),
        'politica-de-cookies' => array(
            'title' => 'PolÍtica de Cookies',
            'content' => '<h2>1. Responsable del sitio web</h2><p><strong>Raz&oacute;n social:</strong> METRANSFERS GESTION SL</p><p><strong>NIF:</strong> B22522353</p><p><strong>Domicilio:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESP&Iacute; (BARCELONA)</p><p><strong>Correo electr&oacute;nico:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p><h2>2. Qu&eacute; son las cookies</h2><p>Las cookies son peque&ntilde;os archivos que se descargan en su dispositivo al acceder a determinadas p&aacute;ginas web. Permiten, entre otras cosas, reconocer su navegador, mantener la sesi&oacute;n, recordar preferencias, reforzar la seguridad o facilitar determinadas funcionalidades t&eacute;cnicas del sitio.</p><h2>3. Tipos de cookies</h2><p>Las cookies pueden clasificarse, entre otros criterios, del siguiente modo:</p><ul><li><strong>Seg&uacute;n la entidad que las gestione:</strong> cookies propias y cookies de terceros.</li><li><strong>Seg&uacute;n su finalidad:</strong> cookies t&eacute;cnicas o necesarias, de preferencias o personalizaci&oacute;n, de an&aacute;lisis, y de publicidad o publicidad comportamental.</li><li><strong>Seg&uacute;n el tiempo que permanecen activas:</strong> cookies de sesi&oacute;n y cookies persistentes.</li></ul><h2>4. Cookies utilizadas en metransfers.es</h2><p>Con car&aacute;cter general, este sitio utiliza o puede utilizar cookies t&eacute;cnicas, de sesi&oacute;n y de preferencia estrictamente relacionadas con el funcionamiento de la web y la prestaci&oacute;n del servicio solicitado por el usuario. Entre ellas se incluyen, cuando proceda:</p><ul><li><strong>Cookies t&eacute;cnicas de navegaci&oacute;n y seguridad:</strong> necesarias para cargar la web, proteger formularios, prevenir usos abusivos y garantizar el funcionamiento b&aacute;sico del sitio.</li><li><strong>Cookies asociadas al proceso de reserva o contacto:</strong> necesarias para gestionar solicitudes enviadas por el usuario, mantener datos temporales de sesi&oacute;n y completar procesos esenciales vinculados al servicio contratado.</li><li><strong>Cookies de preferencias:</strong> destinadas a recordar opciones expresamente solicitadas por el usuario, como el idioma o determinadas configuraciones de visualizaci&oacute;n, cuando estas funcionalidades est&eacute;n habilitadas.</li><li><strong>Cookies t&eacute;cnicas de terceros vinculadas al servicio:</strong> determinados proveedores externos integrados en la web, como herramientas de traducci&oacute;n, mapas, contenidos embebidos o pasarelas de pago seguras, pueden instalar sus propias cookies cuando el usuario interact&uacute;a con dichas funcionalidades.</li></ul><p>Este tema no instala por s&iacute; mismo cookies de publicidad comportamental. Si en el futuro se incorporan herramientas anal&iacute;ticas no exentas, servicios de personalizaci&oacute;n avanzada o soluciones publicitarias que requieran consentimiento, se informar&aacute; al usuario de forma previa y se recabar&aacute; la autorizaci&oacute;n correspondiente antes de su activaci&oacute;n.</p><h2>5. Base jur&iacute;dica</h2><p>Las cookies t&eacute;cnicas o estrictamente necesarias pueden utilizarse sin consentimiento previo cuando resultan imprescindibles para prestar el servicio solicitado por el usuario o para posibilitar la navegaci&oacute;n segura por el sitio web. Las cookies no necesarias solo podr&aacute;n utilizarse cuando exista una base jur&iacute;dica adecuada y, en los casos exigidos por la normativa, tras obtener el consentimiento informado del usuario.</p><h2>6. Plazo de conservaci&oacute;n</h2><p>Las cookies de sesi&oacute;n permanecen activas &uacute;nicamente mientras el usuario navega por el sitio y se eliminan al cerrar el navegador. Las cookies persistentes, cuando existan, se conservar&aacute;n durante el tiempo estrictamente necesario para cumplir su finalidad o hasta que el usuario las elimine manualmente desde la configuraci&oacute;n de su navegador o del servicio correspondiente.</p><h2>7. Gesti&oacute;n, configuraci&oacute;n y desactivaci&oacute;n</h2><p>El usuario puede permitir, bloquear o eliminar las cookies instaladas en su dispositivo mediante la configuraci&oacute;n de su navegador. Debe tener en cuenta que la desactivaci&oacute;n de cookies t&eacute;cnicas o necesarias puede afectar al correcto funcionamiento del sitio, del proceso de reserva o de determinadas funcionalidades esenciales.</p><ul><li><a href="https://support.google.com/chrome/answer/95647?hl=es" target="_blank" rel="noopener">Configurar cookies en Google Chrome</a></li><li><a href="https://support.mozilla.org/es/kb/proteccion-antirrastreo-mejorada-firefox-escritorio" target="_blank" rel="noopener">Configurar cookies en Mozilla Firefox</a></li><li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Configurar cookies en Safari</a></li><li><a href="https://support.microsoft.com/es-es/microsoft-edge/administrar-cookies-en-microsoft-edge-ver-permitir-bloquear-eliminar-y-usar-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener">Configurar cookies en Microsoft Edge</a></li></ul><h2>8. Cookies de terceros</h2><p>La aceptaci&oacute;n, configuraci&oacute;n y uso de cookies de terceros se rige por las pol&iacute;ticas propias de dichos proveedores. METRANSFERS GESTION SL no puede controlar en todo momento las actualizaciones que esos terceros realicen en sus pol&iacute;ticas, por lo que se recomienda al usuario revisar directamente sus condiciones cuando interact&uacute;e con herramientas externas integradas o enlazadas desde la web.</p><h2>9. Informaci&oacute;n adicional y contacto</h2><p>Para obtener m&aacute;s informaci&oacute;n sobre el tratamiento de datos personales, puede consultar nuestra <a href="https://metransfers.es/privacidad">Pol&iacute;tica de Privacidad</a>. Si necesita aclaraciones sobre el uso de cookies en este sitio web, puede escribir a <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p><p>La presente Pol&iacute;tica de Cookies podr&aacute; actualizarse cuando se produzcan cambios normativos, t&eacute;cnicos o funcionales en el sitio web. Se recomienda revisarla peri&oacute;dicamente.</p>'
        ),
        'aviso-legal' => array(
            'title' => 'Aviso Legal',
            'content' => '<h2>1. INFORMACI&Oacute;N IDENTIFICATIVA</h2><p>En cumplimiento con el deber de informaci&oacute;n recogido en el art&iacute;culo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Informaci&oacute;n y del Comercio Electr&oacute;nico (LSSI-CE), a continuaci&oacute;n se reflejan los siguientes datos:</p><p><strong>Titular del sitio web:</strong> METRANSFERS GESTION SL</p><p><strong>NIF:</strong> B22522353</p><p><strong>Domicilio Social:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESP&Iacute; &ndash; (BARCELONA)</p><p><strong>Correo electr&oacute;nico de contacto:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p><p><strong>Actividad:</strong> Transporte de viajeros y gesti&oacute;n de servicios tur&iacute;sticos.</p><h2>2. CONDICIONES DE USO</h2><p>El acceso y/o uso de este portal atribuye la condici&oacute;n de USUARIO, que acepta, desde dicho acceso y/o uso, las Condiciones Generales de Uso aqu&iacute; reflejadas.</p><p>El sitio web proporciona acceso a informaciones, servicios o datos (en adelante, &ldquo;los contenidos&rdquo;) en Internet pertenecientes a METRANSFERS GESTION SL. El USUARIO asume la responsabilidad del uso del portal. Dicha responsabilidad se extiende al registro que fuese necesario para acceder a determinados servicios o contenidos (como el formulario de reservas).</p><h2>3. PROPIEDAD INTELECTUAL E INDUSTRIAL</h2><p>METRANSFERS GESTION SL es titular de todos los derechos de propiedad intelectual e industrial de su p&aacute;gina web, as&iacute; como de los elementos contenidos en la misma (a t&iacute;tulo enunciativo: im&aacute;genes, sonido, audio, v&iacute;deo, software o textos; marcas o logotipos, combinaciones de colores, estructura y dise&ntilde;o, selecci&oacute;n de materiales usados, programas de ordenador necesarios para su funcionamiento, acceso y uso, etc.).</p><p>En virtud de lo dispuesto en los art&iacute;culos 8 y 32.1, p&aacute;rrafo segundo, de la Ley de Propiedad Intelectual, quedan expresamente prohibidas la reproducci&oacute;n, la distribuci&oacute;n y la comunicaci&oacute;n p&uacute;blica, incluida su modalidad de puesta a disposici&oacute;n, de la totalidad o parte de los contenidos de esta p&aacute;gina web, con fines comerciales, en cualquier soporte y por cualquier medio t&eacute;cnico, sin la autorizaci&oacute;n de METRANSFERS GESTION SL.</p><h2>4. EXCLUSI&Oacute;N DE GARANT&Iacute;AS Y RESPONSABILIDAD</h2><p>EL PRESTADOR no se hace responsable, en ning&uacute;n caso, de los da&ntilde;os y perjuicios de cualquier naturaleza que pudieran ocasionar, a t&iacute;tulo enunciativo: errores u omisiones en los contenidos, falta de disponibilidad del portal o la transmisi&oacute;n de virus o programas maliciosos o lesivos en los contenidos, a pesar de haber adoptado todas las medidas tecnol&oacute;gicas necesarias para evitarlo.</p><h2>5. MODIFICACIONES</h2><p>METRANSFERS GESTION SL se reserva el derecho de efectuar sin previo aviso las modificaciones que considere oportunas en su portal, pudiendo cambiar, suprimir o a&ntilde;adir tanto los contenidos y servicios que se presten a trav&eacute;s de la misma como la forma en la que &eacute;stos aparezcan presentados o localizados en su portal.</p><h2>6. ENLACES (LINKS)</h2><p>En el caso de que en el sitio web se dispusiesen enlaces o hiperv&iacute;nculos hac&iacute;a otros sitios de Internet, METRANSFERS GESTION SL no ejercer&aacute; ning&uacute;n tipo de control sobre dichos sitios y contenidos. En ning&uacute;n caso asumir&aacute; responsabilidad alguna por los contenidos de alg&uacute;n enlace perteneciente a un sitio web ajeno.</p><h2>7. DERECHO DE EXCLUSI&Oacute;N</h2><p>METRANSFERS GESTION SL se reserva el derecho a denegar o retirar el acceso al portal y/o los servicios ofrecidos sin necesidad de preaviso, a instancia propia o de un tercero, a aquellos usuarios que incumplan las presentes Condiciones Generales de Uso.</p><h2>8. PROTECCI&Oacute;N DE DATOS</h2><p>Todo lo relativo a la pol&iacute;tica de protecci&oacute;n de datos se encuentra recogido en el documento de Pol&iacute;tica de Privacidad de la entidad, conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley Org&aacute;nica 3/2018 (LOPDGDD).</p><h2>9. LEGISLACI&Oacute;N APLICABLE Y JURISDICCI&Oacute;N</h2><p>La relaci&oacute;n entre METRANSFERS GESTION SL y el USUARIO se regir&aacute; por la normativa espa&ntilde;ola vigente y cualquier controversia se someter&aacute; a los Juzgados y Tribunales de la ciudad de Barcelona.</p>'
        ),
        'terminos-y-condiciones' => array(
            'title' => 'Términos y Condiciones regulan la contratación',
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

// =========================================================================
// AUTO-CREAR PÁGINA DE BLOG (UNA SOLA VEZ)
// =========================================================================
add_action( 'init', 'mt_auto_create_blog_page' );
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
        // Asegurarse de que esté asignada como la página de entradas
        update_option( 'show_on_front', 'page' );
        update_option( 'page_for_posts', $page_id );
    }

    update_option( 'mt_blog_page_created_v2', true );
}

// =========================================================================
// AUTO-ACTUALIZAR CONTENIDO DE ENTRADAS DE RUTAS (UNA SOLA VEZ)
// =========================================================================
add_action( 'init', 'mt_auto_update_routes_content' );
function mt_auto_update_routes_content() {
    if ( get_option( 'mt_routes_content_updated_v1' ) ) {
        return;
    }

    $routes_content = array(
        'ruta-de-juego-de-tronos-tour-desde-barcelona-a-girona' => '
            <h2>Descubre Desembarco del Rey y Braavos en la vida real</h2>
            <p>Si eres un verdadero fanático de <strong>Juego de Tronos</strong>, este tour privado desde Barcelona a Girona es una experiencia obligatoria. La ciudad de Girona, con su impresionante arquitectura medieval y su historia milenaria, fue elegida por HBO para representar algunos de los escenarios más icónicos de la sexta temporada de la serie.</p>
            <p>Con nuestro servicio de <em>traslado privado premium</em>, te recogeremos directamente en tu hotel en Barcelona y te llevaremos en un vehículo de alta gama (Mercedes-Benz) hacia esta joya de Cataluña, garantizando un viaje cómodo, seguro y con estilo.</p>
            
            <h3>¿Qué verás en nuestro tour por Girona?</h3>
            <p>Una vez en Girona, te adentrarás en las calles empedradas que dieron vida a las Ciudades Libres y a la capital de los Siete Reinos:</p>
            <ul>
                <li><strong>La Catedral de Santa María (El Gran Septo de Baelor):</strong> Sube la majestuosa escalinata donde la Reina Margaery iba a realizar su Paseo de la Vergüenza antes de la intervención de Jaime Lannister.</li>
                <li><strong>El Barrio Judío (Call Jueu):</strong> Piérdete por el laberinto de callejuelas estrechas que se transformaron en las calles de Braavos, donde Arya Stark entrenó con la Niña Abandonada.</li>
                <li><strong>Los Baños Árabes y la Plaza de los Jurados:</strong> Escenarios que sirvieron como telón de fondo para el teatro callejero en Braavos y los rincones oscuros donde Arya fue perseguida.</li>
                <li><strong>El Monasterio de Sant Pere de Galligants:</strong> Que en la ficción albergó la Ciudadela de Antigua, donde Samwell Tarly acudió para formarse como Maestre.</li>
            </ul>

            <h3>Una experiencia gastronómica y cultural</h3>
            <p>Más allá de Juego de Tronos, Girona es un destino culinario de primer nivel mundial (hogar de El Celler de Can Roca). Durante el tour, tendrás tiempo libre para disfrutar de la excelente gastronomía local en sus encantadores restaurantes, pasear por la muralla carolingia o admirar las coloridas casas sobre el río Onyar.</p>
            
            <h3>Ventajas de nuestro traslado privado</h3>
            <ul>
                <li><strong>Flexibilidad total:</strong> Tú marcas los tiempos. Sin las prisas de los tours en grupo.</li>
                <li><strong>Confort garantizado:</strong> Vehículos climatizados, Wi-Fi a bordo y agua de cortesía.</li>
                <li><strong>Chófer profesional:</strong> Discreto, puntual y gran conocedor de las rutas de Cataluña.</li>
            </ul>
            
            <p>No pierdas la oportunidad de vivir tu propia aventura épica. <a href="/contacto">Reserva hoy tu traslado privado a Girona</a> y siéntete como un verdadero Stark o Lannister recorriendo las calles de Poniente.</p>
        ',
        'tour-privado-por-los-pueblos-medievales-de-cataluna-desde-barcelona' => '
            <h2>Un viaje en el tiempo a la Cataluña Medieval</h2>
            <p>Escapa del bullicio de Barcelona y embárcate en un viaje en el tiempo a través de los <strong>pueblos medievales más encantadores de Cataluña</strong>. Nuestro tour privado te llevará a descubrir joyas arquitectónicas, calles de piedra y castillos de cuento de hadas en la Costa Brava y el Empordà, todo con la exclusividad y el confort de nuestros vehículos de lujo.</p>
            
            <h3>Itinerario destacado de la ruta medieval</h3>
            <p>Esta ruta está diseñada para mostrarte la esencia histórica de la región, visitando pueblos que han conservado intacto su trazado original durante siglos:</p>
            <ul>
                <li><strong>Rupit:</strong> Un pueblo de postal escondido entre montañas, famoso por su puente colgante de madera y sus robustas casas de piedra de los siglos XVI y XVII.</li>
                <li><strong>Besalú:</strong> Te dará la bienvenida con su espectacular e icónico puente románico fortificado del siglo XI sobre el río Fluvià. Su judería y sus baños purificadores (mikvé) son únicos en Europa.</li>
                <li><strong>Peratallada:</strong> Declarado conjunto histórico-artístico, es considerado uno de los núcleos medievales mejor conservados de Cataluña, con su foso excavado en la roca y su castillo-palacio.</li>
                <li><strong>Pals:</strong> Un recinto gótico impecable situado en lo alto de una colina, con vistas panorámicas a las Islas Medas y rodeado de arrozales.</li>
            </ul>

            <h3>Disfruta del paisaje y la gastronomía local</h3>
            <p>El trayecto entre estos pueblos es en sí mismo una experiencia, recorriendo sinuosas carreteras entre densos bosques de pinos, campos de cultivo y masías tradicionales catalanas. Además, esta región es célebre por su rica gastronomía. Podrás degustar platos típicos como carnes a la brasa, embutidos artesanales y vinos locales de la DO Empordà en restaurantes rústicos de innegable encanto.</p>
            
            <h3>Por qué elegir MeTransfers para tu ruta</h3>
            <p>Realizar este recorrido por tu cuenta requiere una compleja logística de alquiler de vehículos y navegación por carreteras secundarias. Con nuestro servicio de <em>alquiler de vehículos con conductor</em>, solo tienes que preocuparte de disfrutar:</p>
            <ul>
                <li><strong>Servicio Puerta a Puerta:</strong> Recogida y regreso en tu hotel de Barcelona.</li>
                <li><strong>Vehículos de Lujo:</strong> Mercedes Clase E, S o Minivan Clase V para grupos y familias.</li>
                <li><strong>Privacidad y Exclusividad:</strong> Un tour diseñado a tu medida, pudiendo ajustar las paradas según tus intereses.</li>
            </ul>
        ',
        'tour-de-compras-vip-en-barcelona-la-roca-village' => '
            <h2>Una experiencia de compras de lujo sin igual</h2>
            <p>Si amas la moda y las marcas exclusivas, el <strong>Tour de Compras VIP a La Roca Village</strong> es la escapada perfecta durante tu estancia en Barcelona. A tan solo 40 minutos de la ciudad, este prestigioso <em>outlet de lujo al aire libre</em> alberga más de 140 boutiques de las mejores marcas nacionales e internacionales de moda, belleza y estilo de vida, ofreciendo descuentos de hasta un 60% sobre el precio original durante todo el año.</p>
            
            <h3>Viaja con el máximo confort y estilo</h3>
            <p>Sabemos que un día de compras intenso requiere comodidad. Al reservar nuestro traslado privado a La Roca Village, evitarás las molestias de los autobuses abarrotados o los problemas de aparcamiento. Nuestro chófer te recogerá en la puerta de tu hotel en un elegante vehículo Mercedes-Benz, proporcionándote un viaje relajante para que llegues fresco y con energía.</p>
            
            <h3>¿Qué marcas te esperan en La Roca Village?</h3>
            <p>El village cuenta con una selección inmejorable de firmas de alta costura y diseño contemporáneo, diseñadas como una pequeña y encantadora villa mediterránea:</p>
            <ul>
                <li><strong>Alta Costura:</strong> Prada, Gucci, Armani, Balenciaga, Saint Laurent y Loewe.</li>
                <li><strong>Estilo de Vida y Deporte:</strong> Polo Ralph Lauren, Tommy Hilfiger, Nike, y Moncler.</li>
                <li><strong>Joyería y Relojería:</strong> Bulgari, TAG Heuer y Montblanc.</li>
            </ul>

            <h3>Beneficios VIP de nuestro servicio</h3>
            <p>Con MeTransfers, la experiencia de compras se eleva al siguiente nivel:</p>
            <ul>
                <li><strong>Gran capacidad de maletero:</strong> Nuestros vehículos (especialmente las Mercedes Clase V) tienen espacio más que suficiente para que no tengas que preocuparte por el número de bolsas y compras que realices.</li>
                <li><strong>Chófer a disposición:</strong> Tu conductor estará esperándote para asistirte con las bolsas, permitiéndote volver al coche a dejar tus compras y seguir explorando las tiendas cómodamente sin cargar peso.</li>
                <li><strong>Horario flexible:</strong> Regresa a Barcelona exactamente cuando lo desees, sin depender de horarios de transporte público.</li>
            </ul>
            
            <p>Completa tu día de shopping disfrutando de la exquisita oferta gastronómica de los restaurantes y cafeterías del Village. Solicita ya tu <strong>traslado VIP a La Roca Village</strong> y disfruta del lujo desde el primer kilómetro.</p>
        ',
        'excursion-privada-de-barcelona-a-sitges-y-tarragona' => '
            <h2>Descubre la magia del Mediterráneo y el Imperio Romano</h2>
            <p>Combina el encanto costero, el modernismo y el imponente legado de la antigua Roma en una sola jornada con nuestra <strong>Excursión Privada a Sitges y Tarragona</strong>. Este tour te llevará por la hermosa costa al sur de Barcelona, descubriendo dos de los destinos más atractivos de Cataluña a bordo de nuestros confortables vehículos premium.</p>
            
            <h3>Primera parada: Sitges, la Blanca Subur</h3>
            <p>Situada a escasos 40 kilómetros de Barcelona, Sitges es conocida mundialmente por sus hermosas playas, su vibrante vida cultural y su patrimonio modernista. Durante tu visita podrás:</p>
            <ul>
                <li>Pasear por su icónico <strong>Paseo Marítimo</strong> flanqueado por palmeras y mansiones indianas.</li>
                <li>Visitar la emblemática <strong>Iglesia de San Bartolomé y Santa Tecla</strong>, que se alza majestuosa sobre el mar ofreciendo la imagen más famosa de la villa.</li>
                <li>Perderte por su encantador casco antiguo, de calles estrechas y casas blancas, y descubrir museos fascinantes como el <em>Cau Ferrat</em> o el <em>Palau Maricel</em>.</li>
            </ul>

            <h3>Segunda parada: Tarragona (Tarraco Romana)</h3>
            <p>Siguiendo la costa hacia el sur, llegaremos a Tarragona, una ciudad declarada <strong>Patrimonio de la Humanidad por la UNESCO</strong> gracias a sus extraordinariamente conservadas ruinas romanas. Hace dos mil años, Tarraco fue una de las ciudades más importantes del Imperio Romano en la Península Ibérica.</p>
            <ul>
                <li><strong>El Anfiteatro Romano:</strong> Un espectacular coliseo del siglo II d.C. construido junto a la orilla del mar Mediterráneo, donde antaño luchaban gladiadores.</li>
                <li><strong>El Circo Romano y la Torre del Pretorio:</strong> Pasea por las bóvedas subterráneas de uno de los circos mejor conservados del mundo.</li>
                <li><strong>El Acueducto de les Ferreres (Pont del Diable):</strong> Una imponente obra de ingeniería romana situada en los bosques a las afueras de la ciudad.</li>
                <li><strong>Balcón del Mediterráneo:</strong> Termina la visita asomándote a este famoso mirador que ofrece unas vistas inolvidables del mar y la costa dorada.</li>
            </ul>

            <h3>Confort absoluto en ruta</h3>
            <p>Esta excursión de día completo es ideal para realizarla con nuestro servicio de <em>coche con conductor</em>. Disfrutarás del trayecto por la pintoresca carretera de las Costas del Garraf con la máxima seguridad, parando en miradores si lo deseas y escuchando las recomendaciones locales de nuestro chófer experto.</p>
        ',
        'tour-panoramico-por-barcelona-en-coche-privado' => '
            <h2>La esencia de Barcelona desde la comodidad de un vehículo premium</h2>
            <p>Si dispones de poco tiempo en la ciudad o simplemente quieres evitar largas caminatas y las aglomeraciones del transporte público, nuestro <strong>Tour Panorámico por Barcelona en Coche Privado</strong> es la solución perfecta. Te ofrecemos un recorrido exclusivo y eficiente que te permitirá contemplar las obras maestras y los rincones más espectaculares de la capital catalana sin bajar de tu vehículo de lujo (o realizando paradas estratégicas y breves para tomar fotografías).</p>
            
            <h3>Los imprescindibles de la ciudad condal</h3>
            <p>A lo largo de este tour panorámico, nuestro chófer profesional trazará una ruta optimizada que incluye los grandes hitos de la arquitectura y la historia de Barcelona:</p>
            <ul>
                <li><strong>La Sagrada Familia:</strong> Contempla la majestuosidad de la obra cumbre e inacabada de Antoni Gaudí, admirando los intrincados detalles de sus diferentes fachadas directamente desde el confort de tu asiento.</li>
                <li><strong>Paseo de Gracia:</strong> Recorre la avenida más lujosa de la ciudad, flanqueada por boutiques de diseño y las célebres casas modernistas, incluyendo la <em>Casa Batlló</em> y <em>La Pedrera (Casa Milà)</em>.</li>
                <li><strong>Montjuïc y la Plaza España:</strong> Ascenderemos a la montaña de Montjuïc pasando por las Torres Venecianas y el Palacio Nacional. Desde lo alto, disfrutarás de las mejores <strong>vistas panorámicas de toda Barcelona</strong> y su puerto, además de ver las instalaciones del Anillo Olímpico de 1992.</li>
                <li><strong>Frente Marítimo y Puerto Olímpico:</strong> Siente la brisa mediterránea recorriendo el litoral barcelonés, desde el Monumento a Colón y el Port Vell hasta las modernas playas de la Vila Olímpica.</li>
                <li><strong>Arco de Triunfo y Parque de la Ciutadella:</strong> Iconos de la Exposición Universal de 1888 que aportan un aire monumental e histórico al trayecto.</li>
            </ul>

            <h3>Un tour diseñado a tu medida</h3>
            <p>La mayor ventaja de nuestro <em>servicio de transfer y tour privado</em> es la personalización absoluta. Si deseas alterar la ruta para pasar por el Camp Nou, acercarte al moderno distrito 22@, o hacer una parada exprés para degustar unas tapas, tu chófer estará a tu completa disposición para adaptar el itinerario al momento.</p>
            
            <p>Viaja con la elegancia de nuestra flota de vehículos Mercedes, equipados con climatización, asientos de cuero y cristales tintados para tu máxima privacidad. Convierte tu visita a Barcelona en una experiencia de cinco estrellas, libre de estrés y fatiga.</p>
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
// AUTO-CREAR ARTÍCULO TAX FREE (UNA SOLA VEZ)
// =========================================================================
add_action( 'init', 'mt_auto_create_tax_free_post' );
function mt_auto_create_tax_free_post() {
    if ( get_option( 'mt_tax_free_post_created_v1' ) ) {
        return;
    }

    $slug = 'recuperar-el-iva-en-el-aeropuerto';
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    
    $excerpt = 'Si has disfrutado de una jornada de compras por la ciudad condal y resides fuera de la Unión Europea, tienes derecho a solicitar la devolución del Impuesto sobre el Valor Añadido (IVA) de tus adquisiciones. El Aeropuerto Josep Tarradellas Barcelona-El Prat cuenta con el sistema digital DIVA, el cual ha simplificado enormemente este trámite. A continuación, te presentamos el paso a paso detallado para realizar la gestión de tu Tax Free de forma rápida, segura y sin contratiempos antes de tu vuelo de regreso.';
    
    $content = '<p class="lead-text">' . $excerpt . '</p>
        <h2>Requisitos Previos para el Tax Free en España</h2>
        <p>Antes de iniciar el proceso, es indispensable cumplir con ciertos criterios estipulados por la normativa aduanera española:</p>
        <ul>
            <li><strong>Residencia:</strong> Debes tener tu residencia habitual fuera de la Unión Europea (o en territorios específicos como Canarias, Ceuta o Melilla).</li>
            <li><strong>Tipo de bienes:</strong> Los artículos comprados deben ser para uso personal o regalo, y deben salir de la UE en tu equipaje personal en un plazo máximo de tres meses desde la compra.</li>
            <li><strong>Sin importe mínimo:</strong> Actualmente en España no existe una cantidad mínima de gasto para tener derecho a la devolución del IVA.</li>
        </ul>

        <h2>Paso 1: En la Tienda (Solicitud del Formulario)</h2>
        <p>El proceso para recuperar tus impuestos comienza mucho antes de llegar al aeropuerto, concretamente en el momento de realizar el pago en el comercio.</p>
        <ul>
            <li>Informa al personal de la tienda que deseas el formulario Tax Free.</li>
            <li>Muestra tu pasaporte original para que puedan registrar tus datos correctamente.</li>
            <li>Asegúrate de recibir el documento con el logotipo DIVA y un código de barras o código QR. Guarda este recibo junto con el ticket de compra original.</li>
        </ul>

        <h2>Paso 2: Llegada al Aeropuerto El Prat</h2>
        <p>El día de tu vuelo, la planificación del tiempo es crucial. El proceso de aduanas puede tener colas, especialmente en temporada alta de vacaciones.</p>
        <ul>
            <li>Llega al aeropuerto con al menos 3 horas de antelación respecto a la salida de tu vuelo.</li>
            <li>Para garantizar la puntualidad y evitar el estrés del transporte público con todo el equipaje y las compras, reservar un traslado privado directamente hasta tu terminal de salida es una excelente estrategia logística.</li>
            <li><strong>Regla de oro:</strong> Bajo ninguna circunstancia factures el equipaje que contiene tus compras antes de realizar el trámite. La aduana requiere verificar que la mercancía abandona efectivamente el territorio.</li>
        </ul>

        <h2>Paso 3: Validación en los Quioscos Digitales DIVA</h2>
        <p>Una vez en el aeropuerto, y siempre antes de pasar el control de seguridad, debes dirigirte a las máquinas de validación automática DIVA.</p>
        
        <h3>Ubicaciones de los Quioscos DIVA</h3>
        <p>Encontrarás las terminales interactivas DIVA estratégicamente ubicadas en las zonas de Salidas del aeropuerto, generalmente cerca de los mostradores de facturación y junto a las oficinas de la Guardia Civil (Aduanas).</p>

        <h3>Proceso de Escaneo</h3>
        <ul>
            <li>Selecciona tu idioma en la pantalla táctil de la máquina.</li>
            <li>Pasa el código de barras de cada uno de tus formularios Tax Free por el lector óptico.</li>
            <li><strong>Mensaje Verde:</strong> El formulario está aprobado. El trámite aduanero ha finalizado con éxito.</li>
            <li><strong>Mensaje Rojo:</strong> La máquina no puede validar la compra de forma automática. Deberás dar un par de pasos hacia el mostrador contiguo de la Guardia Civil (Aduanas) para una revisión manual, presentando tus mercancías y pasaporte.</li>
        </ul>

        <h2>Paso 4: Cobro del Reembolso</h2>
        <p>Con los formularios validados (ya sea digital o manualmente), el último paso es materializar la devolución del dinero. Puedes facturar tu equipaje en este momento si lo deseas y luego proceder al cobro.</p>
        
        <h3>Opciones de Cobro Disponibles</h3>
        <ul>
            <li><strong>Oficinas de Reembolso (Global Blue, Planet, Innova):</strong> Encontrarás mostradores de estas agencias operadoras distribuidos tanto en la zona pública (antes del control de seguridad) como en la zona de embarque (después del control de pasaportes). Presenta tus documentos validados para recibir el dinero.</li>
            <li><strong>Reembolso en Efectivo:</strong> Recibirás el dinero al instante en la moneda seleccionada, pero la agencia deducirá una comisión por gastos de gestión.</li>
            <li><strong>Reembolso en Tarjeta de Crédito:</strong> Recuperarás el importe íntegro correspondiente. El dinero suele reflejarse en tu cuenta bancaria en un plazo de unos días hábiles.</li>
            <li><strong>Buzones de Correo:</strong> Si prefieres evitar las colas o tu vuelo sale de madrugada cuando las oficinas están cerradas, introduce el formulario validado en el sobre prefranqueado que te entregaron en la tienda, escribe los datos de tu tarjeta de crédito y deposítalo en los buzones de la empresa operadora correspondientes.</li>
        </ul>
    ';

    if ( ! $post ) {
        wp_insert_post( array(
            'post_title'   => 'Guía Definitiva: Cómo Recuperar el IVA (Tax Free) en el Aeropuerto de Barcelona – El Prat',
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
// AUTO-CREAR PÁGINAS GENÉRICAS (FAQ, SOBRE NOSOTROS)
// =========================================================================
add_action( 'init', 'mt_auto_create_generic_pages' );
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
                    <p>En MeTransfers queremos que tu experiencia sea perfecta desde el momento en que realizas tu reserva. A continuación, hemos recopilado las preguntas más habituales de nuestros clientes.</p>

                    <h3>1. ¿Cómo funciona la recogida en el aeropuerto?</h3>
                    <p>Nuestro conductor monitorizará el estado de tu vuelo en tiempo real. Te estará esperando en la zona de llegadas (Arrivals) con un cartel con tu nombre o el logotipo de tu empresa, justo después de que recojas tu equipaje. Te ayudará con las maletas y te acompañará hasta el vehículo VIP estacionado en la zona reservada.</p>

                    <h3>2. ¿Qué pasa si mi vuelo se retrasa?</h3>
                    <p>No tienes de qué preocuparte. Incluimos <strong>hasta 60 minutos de espera de cortesía gratuita</strong> desde el momento en que aterriza el vuelo. Dado que monitorizamos los vuelos, si hay un retraso ajustaremos automáticamente la hora de recogida sin ningún coste adicional para ti.</p>

                    <h3>3. ¿Los vehículos disponen de sillas para niños o bebés?</h3>
                    <p>Sí, la seguridad de los más pequeños es nuestra prioridad. Ofrecemos sillas infantiles y elevadores homologados sin coste adicional. Solo necesitas indicarnos la edad y el peso de los niños en el formulario de reserva o a través de WhatsApp para que tengamos el vehículo preparado.</p>

                    <h3>4. ¿Cuál es la política de cancelación?</h3>
                    <p>Ofrecemos total flexibilidad. Puedes cancelar o modificar tu reserva de forma gratuita hasta <strong>24 horas antes</strong> de la hora de recogida programada. Si cancelas con menos antelación, ponte en contacto con nuestro equipo de soporte para revisar tu caso concreto.</p>

                    <h3>5. ¿Puedo llevar equipaje especial o voluminoso (bicicletas, esquís)?</h3>
                    <p>¡Por supuesto! Disponemos de furgonetas Mercedes Clase V extra largas (Minivans) ideales para transportar equipaje deportivo, sillas de ruedas o simplemente muchas maletas. Solo te pedimos que nos lo indiques al hacer la reserva para asegurarnos de enviar el vehículo adecuado.</p>
                    
                    <h3>6. ¿Los precios son cerrados o hay cargos extra?</h3>
                    <p>Todos nuestros presupuestos son finales. El precio que ves al reservar incluye impuestos (IVA), peajes, propinas y tiempos de espera de cortesía. <strong>No hay sorpresas ni costes ocultos.</strong></p>
                    
                    <hr/>
                    <p>¿Tienes alguna otra pregunta? No dudes en escribirnos por WhatsApp o utilizar nuestro <a href="/contacto/">formulario de contacto</a>. Nuestro equipo de atención al cliente está disponible 24/7 para ayudarte.</p>
                </div>
            '
        ),
        'sobre-nosotros' => array(
            'title'   => 'Sobre Nosotros',
            'content' => '
                <div class="luxury-prose">
                    <h2>MeTransfers: Excelencia en Movilidad Privada</h2>
                    <p>Somos una agencia boutique de traslados privados y chóferes VIP con base en Barcelona. Nacimos con un objetivo claro: elevar los estándares del transporte de pasajeros en Cataluña, transformando un simple trayecto en una <strong>experiencia de lujo, confort y fiabilidad.</strong></p>
                    
                    <h3>Nuestra Filosofía</h3>
                    <p>Entendemos que el tiempo de nuestros clientes es su activo más valioso. Ya sea que viajes por negocios, asistas a un congreso internacional (como el Mobile World Congress) o disfrutes de unas merecidas vacaciones en la Costa Brava, nuestro equipo se encarga de toda la logística para que tú solo tengas que relajarte y disfrutar del viaje.</p>
                    
                    <h3>La Flota: Confort de Primera Clase</h3>
                    <p>No creemos en los compromisos cuando se trata de seguridad y comodidad. Por ello, operamos exclusivamente con vehículos premium de última generación de la marca <strong>Mercedes-Benz</strong>:</p>
                    <ul>
                        <li><strong>Clase E y Clase S:</strong> Elegancia y sofisticación absolutas para ejecutivos, diplomáticos y parejas.</li>
                        <li><strong>Clase V (Minivan):</strong> El espacio definitivo para familias y grupos corporativos de hasta 7 personas, con asientos enfrentables y amplio espacio para equipaje.</li>
                    </ul>
                    <p>Todos nuestros vehículos se desinfectan tras cada servicio y cuentan con climatización independiente, agua de cortesía y conexión Wi-Fi gratuita.</p>

                    <h3>Nuestros Chóferes: Los Mejores Anfitriones</h3>
                    <p>La tecnología y los coches de lujo no son nada sin el toque humano. Nuestro equipo de conductores profesionales destaca por su <strong>discreción, puntualidad extrema y conocimiento exhaustivo</strong> de Barcelona y sus alrededores. Completamente bilingües y con una impecable presentación en traje oscuro, están capacitados para ofrecer un servicio diplomático y adaptarse a cualquier imprevisto en la ruta.</p>
                    
                    <h3>Compromiso Medioambiental</h3>
                    <p>En MeTransfers miramos hacia el futuro. Estamos en un proceso activo de renovación de nuestra flota hacia opciones híbridas y eléctricas de lujo para reducir nuestra huella de carbono, sin sacrificar ni un ápice del rendimiento y la comodidad que nos caracteriza.</p>
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
// AUTO-CREAR ARTÍCULO VIP ARTISTAS Y MÚSICOS
// =========================================================================
add_action( 'init', 'mt_auto_create_artist_post' );
function mt_auto_create_artist_post() {
    if ( get_option( 'mt_artist_post_created_v1' ) ) {
        return;
    }

    $slug = 'movilidad-vip-para-artistas-y-musicos-en-barcelona-discrecion-y-gran-capacidad-de-maletero-para-instrumentos';
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    
    $excerpt = 'Descubre por qué MeTransfers es la agencia de movilidad de confianza para artistas, bandas musicales y talentos internacionales en Barcelona. Ofrecemos máxima discreción, puntualidad milimétrica para conciertos y furgonetas Mercedes de gran capacidad para el transporte seguro de instrumentos y equipos delicados.';
    
    $content = '<p class="lead-text">' . $excerpt . '</p>
        <h2>Servicio de Chófer VIP para el Sector Musical en Barcelona</h2>
        <p>Barcelona es una de las capitales europeas de la música, albergando festivales de renombre mundial como el Primavera Sound, Sónar, y conciertos multitudinarios en el Palau Sant Jordi o el Estadi Olímpic. La logística para un artista internacional o una banda requiere un nivel de excelencia y adaptabilidad que el transporte convencional no puede ofrecer.</p>
        <p>En <strong>MeTransfers</strong> nos especializamos en la <em>movilidad premium para el sector del entretenimiento</em>. Entendemos las exigencias de las giras, los horarios de los estudios de grabación y la necesidad de mantener un perfil bajo ante la prensa y los fans.</p>

        <h2>Máxima Discreción y Privacidad</h2>
        <p>Sabemos que para una celebridad, la privacidad no es un lujo, sino una necesidad. Nuestros servicios de transfer están diseñados para garantizar la tranquilidad del talento:</p>
        <ul>
            <li><strong>Cristales tintados y vehículos sin distintivos:</strong> Nuestras furgonetas y berlinas Mercedes-Benz pasan completamente desapercibidas en la ciudad.</li>
            <li><strong>Chóferes formados en protocolo:</strong> Nuestro equipo firma estrictos acuerdos de confidencialidad (NDA). Operan con la máxima discreción, evitando interacciones innecesarias y asegurando un entorno relajado para el artista.</li>
            <li><strong>Rutas seguras y accesos VIP:</strong> Coordinamos con los equipos de producción y seguridad de los recintos (hoteles, arenas, festivales) para utilizar accesos traseros o privados, evitando aglomeraciones.</li>
        </ul>

        <h2>Furgonetas Minivan: Gran Capacidad para Instrumentos y Equipo</h2>
        <p>Uno de los mayores retos para los músicos en gira es el transporte de su equipo (guitarras, teclados, amplificadores, vestuario). Moverse en taxis convencionales suele ser una pesadilla logística.</p>
        <p>Nuestra flota de <strong>Mercedes Clase V Extra Largas (Minivans)</strong> es la solución definitiva:</p>
        <ul>
            <li><strong>Maletero extragrande:</strong> Capacidad sobrada para albergar estuches rígidos, instrumentos delicados y múltiples maletas de gran tamaño sin sacrificar el confort de los pasajeros.</li>
            <li><strong>Conducción suave:</strong> La suspensión neumática de nuestros vehículos garantiza que los instrumentos más sensibles (como violonchelos o equipos de grabación) no sufran durante los trayectos urbanos.</li>
            <li><strong>Espacio para el equipo:</strong> Con capacidad para hasta 7 pasajeros, el artista puede viajar cómodamente junto a su mánager, personal de seguridad o técnicos clave en un mismo vehículo.</li>
        </ul>

        <h2>Puntualidad Milimétrica y Disponibilidad 24/7</h2>
        <p>En la industria musical, llegar tarde a una prueba de sonido o a una entrevista de promoción no es una opción. Ofrecemos un servicio de <em>disposición por horas</em> que otorga flexibilidad total a la agenda del artista.</p>
        <p>Tu chófer privado estará esperando fuera del estudio de grabación a altas horas de la madrugada, o en la puerta del hotel listo para un traslado exprés al aeropuerto Josep Tarradellas Barcelona-El Prat. Monitorizamos constantemente el tráfico en Barcelona para evitar atascos y asegurar llegadas puntuales.</p>
        
        <hr/>
        <h3>Reserva la movilidad de tu próxima gira</h3>
        <p>Si eres mánager, promotor o formas parte del equipo de producción de un evento, no dejes la logística terrestre al azar. <a href="/contacto">Contacta con nosotros hoy mismo</a> para planificar los traslados VIP de tu talento. Te proporcionaremos presupuestos personalizados para varios días o flotas de múltiples vehículos simultáneos.</p>
    ';

    if ( ! $post ) {
        wp_insert_post( array(
            'post_title'   => 'Movilidad VIP para Artistas y Músicos en Barcelona: Discreción y Espacio para Instrumentos',
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
// AUTO-CREAR ARTÍCULOS SENIORS Y LONJAS DE PESCADO
// =========================================================================
add_action( 'init', 'mt_auto_create_seniors_lonjas_posts' );
function mt_auto_create_seniors_lonjas_posts() {
    if ( get_option( 'mt_seniors_lonjas_posts_created_v1' ) ) {
        return;
    }

    $posts = array(
        'barcelona-seniors-comodidad-accesibilidad-vehiculos' => array(
            'title'   => 'Turismo Senior en Barcelona: Comodidad y Accesibilidad en Nuestros Vehículos',
            'excerpt' => 'Descubre cómo MeTransfers facilita el turismo para personas mayores (seniors) en Barcelona. Vehículos de fácil acceso, asistencia personalizada con el equipaje y una conducción suave para garantizar un viaje sin estrés.',
            'content' => '
                <p class="lead-text">Barcelona es una ciudad maravillosa para visitar a cualquier edad, pero para los viajeros senior, moverse por la ciudad y llegar desde el aeropuerto hasta el hotel puede resultar agotador si no se planifica adecuadamente.</p>
                
                <h2>Accesibilidad y Confort como Prioridad</h2>
                <p>En MeTransfers hemos adaptado nuestros servicios para ofrecer la experiencia más cómoda y segura a nuestros clientes de mayor edad. Evitar las largas caminatas en el aeropuerto, las escaleras del metro o las esperas en las paradas de taxis convencionales marca una gran diferencia en el inicio de unas vacaciones.</p>
                
                <h3>Vehículos adaptados a tus necesidades</h3>
                <p>Nuestra flota está compuesta por berlinas y Minivans de la marca Mercedes-Benz, seleccionadas específicamente por su ergonomía:</p>
                <ul>
                    <li><strong>Acceso fácil:</strong> Nuestras Mercedes Clase V cuentan con puertas correderas amplias y estribos bajos, lo que facilita enormemente entrar y salir del vehículo sin esfuerzo.</li>
                    <li><strong>Asientos ergonómicos:</strong> Interiores espaciosos con asientos de cuero regulables, reposabrazos y climatización independiente para garantizar el máximo confort articular y térmico.</li>
                    <li><strong>Espacio para sillas de ruedas:</strong> Disponemos de espacio de sobra en el maletero para acomodar sillas de ruedas plegables, andadores y cualquier tipo de asistencia técnica para la movilidad.</li>
                </ul>

                <h2>Asistencia Personalizada Puerta a Puerta</h2>
                <p>El servicio comienza en la misma terminal de llegadas. Nuestro chófer te estará esperando con un cartel visible justo al salir de la recogida de equipajes. A partir de ese momento, <strong>él se encargará de todas tus maletas</strong>.</p>
                <p>No tendrás que cargar peso en ningún momento. El conductor te acompañará a un paso tranquilo hasta el vehículo VIP, estacionado a pocos metros de la puerta en zonas reservadas del Aeropuerto de El Prat.</p>
                
                <h2>Conducción Suave y Segura</h2>
                <p>Nuestros conductores profesionales aplican un estilo de conducción defensivo y extremadamente suave, evitando frenazos o aceleraciones bruscas. Además, si deseas hacer una parada técnica durante el trayecto (por ejemplo, en un viaje largo hacia la Costa Brava), solo tienes que pedirlo.</p>
                <p>Viaja con total tranquilidad y disfruta del paisaje barcelonés. <a href="/contacto">Reserva hoy tu traslado VIP</a> y asegura un inicio de vacaciones relajado para ti o tus familiares mayores.</p>
            '
        ),
        'lonjas-de-pescado-en-la-costa-de-cataluna' => array(
            'title'   => 'Ruta Gastronómica: Las Mejores Lonjas de Pescado en la Costa de Cataluña',
            'excerpt' => 'Sumérgete en la cultura marinera de Cataluña visitando sus famosas lonjas de pescado. Desde Palamós hasta Vilanova i la Geltrú, te llevamos en un cómodo traslado privado a presenciar la subasta del pescado y degustar el marisco más fresco.',
            'content' => '
                <p class="lead-text">La costa catalana no solo es famosa por sus playas y calas de aguas cristalinas, sino también por su riquísima tradición pesquera. Una de las experiencias más auténticas y fascinantes que puedes vivir cerca de Barcelona es visitar una <strong>lonja de pescado (Llotja de Peix)</strong> al atardecer.</p>
                
                <h2>El Espectáculo de la Subasta del Pescado</h2>
                <p>Cada tarde, de lunes a viernes, los barcos pesqueros regresan a puerto seguidos por bandadas de gaviotas. La descarga de las cajas llenas de gambas, cigalas, rape y calamares da paso a la tradicional subasta. Antiguamente cantada a viva voz, hoy se realiza mediante paneles electrónicos, pero no ha perdido un ápice de su frenética emoción.</p>
                
                <h3>Las lonjas imprescindibles de la Costa Brava y Dorada</h3>
                <ul>
                    <li><strong>Palamós (Girona):</strong> Mundialmente famosa por su espectacular <em>Gamba Roja de Palamós</em>. Además de presenciar la subasta, puedes visitar el Museo de la Pesca y apuntarte a los talleres gastronómicos del Espai del Peix.</li>
                    <li><strong>Blanes (Girona):</strong> El puerto pesquero más activo del sur de la Costa Brava. Su subasta es un hervidero de actividad donde restaurantes de lujo compran el género más selecto del día.</li>
                    <li><strong>Arenys de Mar (Barcelona):</strong> A solo 45 minutos de la ciudad condal, es la lonja más importante del Maresme. Famosa por sus calamares y sonso, ofrece un ambiente marinero inigualable.</li>
                    <li><strong>Vilanova i la Geltrú (Tarragona):</strong> Una de las flotas pesqueras más grandes de Cataluña, destacando por el marisco y el exquisito "Peix Blau" (pescado azul).</li>
                </ul>

                <h2>Del Barco al Plato</h2>
                <p>La visita a la lonja culmina, inevitablemente, en la mesa. Alrededor de estos puertos se concentran las mejores marisquerías y tabernas marineras, donde podrás degustar el mismo pescado que acabas de ver desembarcar, acompañado de un buen vino blanco D.O. Penedès o Empordà.</p>
                
                <h2>Tu Ruta Gastronómica en Vehículo Privado</h2>
                <p>Visitar estos pueblos pesqueros y volver a Barcelona el mismo día puede resultar cansado si dependes del tren o conduces de noche tras una buena cena. Nuestro <strong>servicio de traslado y tour privado</strong> es la opción perfecta para disfrutar de esta experiencia.</p>
                <p>Te recogemos en tu hotel, te llevamos al puerto de tu elección para que pasees, presencies la subasta y cenes sin prisa. Tu chófer privado te estará esperando a la salida del restaurante para llevarte de vuelta a tu alojamiento con total comodidad en un elegante Mercedes-Benz. <a href="/contacto">Consúltanos para organizar tu ruta gastronómica marinera</a>.</p>
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

// 1. Crear la tabla de base de datos en la activación o inicio
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
add_action( 'after_setup_theme', 'mt_setup_event_tracking_table' );

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

// 3. Menú de administración para ver las estadísticas
function mt_add_event_tracking_menu() {
    add_menu_page(
        'Métricas Botones',
        'Métricas Botones',
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
        <h1>Estadísticas de Clics en Botones</h1>
        <p>A continuación se muestran los botones que los usuarios han pulsado en la web, agrupados por texto del botón y URL de la página.</p>
        
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th class="manage-column">Texto del Botón</th>
                    <th class="manage-column">URL de la Página</th>
                    <th class="manage-column">Total Clics</th>
                    <th class="manage-column">Último Clic</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $results ) ) : ?>
                    <tr>
                        <td colspan="4">No hay datos registrados aún.</td>
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
