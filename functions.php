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

// DESACTIVADO: mt_update_all_page_titles_once() anteponía "MeTransfers Barcelona -" a TODOS
// los títulos editoriales de las páginas, mezclando el título interno con el título SEO.
// Los tres conceptos deben estar separados: título interno, H1 visible y <title> SEO.
// Si necesitas renombrar páginas, hazlo manualmente desde el panel de WordPress.
// add_action( 'admin_init', 'mt_update_all_page_titles_once' );
function mt_update_all_page_titles_once() {
    // Función conservada por si se necesita referenciar el historial de migración.
    // No conectada a ningún hook. No se ejecuta automáticamente.
}



require_once get_template_directory() . '/includes/rutas-cpt.php';
require_once get_template_directory() . '/includes/leads-cpt.php';

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
	define( 'ME_TRANSFERS_VERSION', '3.1.0' );
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
        
        // Añadir capacidades especÃ­ficas
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
<li><strong>Datos de Reserva:</strong> Nombre, apellidos, teléfono, correo electrónico y detalles del trayecto/servicio solicitado.</li>
<li><strong>Datos de FacturaciÃ³n:</strong> DirecciÃ³n postal y NIF/DNI (segÃºn los datos de registro fiscal de la entidad).</li>
<li><strong>Datos de NavegaciÃ³n:</strong> DirecciÃ³n IP, cookies y metadatos para garantizar la seguridad del sitio.</li>
</ul>
<h2>4. Finalidad del Tratamiento</h2>
<p>Sus datos serÃ¡n tratados con el fin de:</p>
<ul>
<li><strong>GestiÃ³n de Reservas:</strong> Tramitar, confirmar y ejecutar los servicios de transporte o gestiÃ³n contratados.</li>
<li><strong>AtenciÃ³n al Cliente:</strong> Resolver dudas y proporcionar soporte a través del punto Ãºnico de contacto <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</li>
<li><strong>Cumplimiento Legal:</strong> Emitir facturas y cumplir con las obligaciones tributarias ante la AEAT.</li>
<li><strong>Seguridad:</strong> Prevenir fraudes y usos no autorizados de la plataforma.</li>
</ul>
<h2>5. LegitimaciÃ³n</h2>
<p>La base legal para el tratamiento es:</p>
<ul>
<li><strong>EjecuciÃ³n Contractual:</strong> Necesaria para procesar su reserva y prestarle el servicio solicitado.</li>
<li><strong>ObligaciÃ³n Legal:</strong> Derivada de la normativa fiscal y mercantil vigente en España.</li>
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
<p><strong>Correo electrónico:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p>
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
<p>Para obtener más información sobre el tratamiento de datos personales, puede consultar nuestra <a href="' . home_url( '/privacidad' ) . '">Política de Privacidad</a>. Si necesita aclaraciones sobre el uso de cookies en este sitio web, puede escribir a <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p>
<p>La presente Política de Cookies podrÃ¡ actualizarse cuando se produzcan cambios normativos, tÃ©cnicos o funcionales en el sitio web. Se recomienda revisarla periÃ³dicamente.</p>',
        'terminos-y-condiciones' => '<h2>1. MARCO LEGAL APLICABLE</h2>
<p>El presente contrato se rige por lo dispuesto en la legislaciÃ³n espaÃ±ola vigente, especÃ­ficamente:</p>
<ul>
<li>Ley 16/1987, de 30 de julio, de OrdenaciÃ³n de los Transportes Terrestres (LOTT) y su Reglamento (ROTT).</li>
<li>Ley 34/2002 (LSSI-CE) sobre servicios de la sociedad de la información.</li>
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
<p>El pago de los servicios se efectuarÃ¡ mediante tarjeta de crÃ©dito o dÃ©bito a través de la pasarela de pago segura Redsys.</p>
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
<li>Errores en los datos facilitados por el cliente en el formulario de reserva (ej. fecha errÃ³nea o nÃºmero de teléfono incorrecto).</li>
<li>Incumplimiento de las normas de seguridad por parte de los pasajeros (uso de cinturÃ³n, comportamiento disruptivo).</li>
</ul>
<h2>7. JURISDICCIÃ“N Y LEY APLICABLE</h2>
<p>Para la resoluciÃ³n de cualquier litigio derivado de la interpretaciÃ³n o ejecuciÃ³n de este contrato, las partes se someten a la legislaciÃ³n espaÃ±ola. En caso de controversia, se recurrirÃ¡ a los Juzgados y Tribunales de Barcelona, salvo que el cliente ostente la condiciÃ³n de consumidor, en cuyo caso se atenderÃ¡ a la competencia territorial establecida por ley.</p>',
        'aviso-legal' => '<h2>1. INFORMACIÃ“N IDENTIFICATIVA</h2>
<p>En cumplimiento con el deber de información recogido en el artÃ­culo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la InformaciÃ³n y del Comercio ElectrÃ³nico (LSSI-CE), a continuación se reflejan los siguientes datos:</p>
<p><strong>Titular del sitio web:</strong> METRANSFERS GESTION SL</p>
<p><strong>NIF:</strong> B22522353</p>
<p><strong>Domicilio Social:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESPÃ &ndash; (BARCELONA)</p>
<p><strong>Correo electrónico de contacto:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p>
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
<p>METRANSFERS GESTION SL se reserva el derecho de efectuar sin previo aviso las modificaciones que considere oportunas en su portal, pudiendo cambiar, suprimir o aÃ±adir tanto los contenidos y servicios que se presten a través de la misma como la forma en la que éstos aparezcan presentados o localizados en su portal.</p>
<h2>6. ENLACES (LINKS)</h2>
<p>En el caso de que en el sitio web se dispusiesen enlaces o hipervÃ­nculos hacÃ­a otros sitios de Internet, METRANSFERS GESTION SL no ejercerÃ¡ ningÃºn tipo de control sobre dichos sitios y contenidos. En ningÃºn caso asumirÃ¡ responsabilidad alguna por los contenidos de algÃºn enlace perteneciente a un sitio web ajeno.</p>
<h2>7. DERECHO DE EXCLUSIÃ“N</h2>
<p>METRANSFERS GESTION SL se reserva el derecho a denegar o retirar el acceso al portal y/o los servicios ofrecidos sin necesidad de preaviso, a instancia propia o de un tercero, a aquellos usuarios que incumplan las presentes Condiciones Generales de Uso.</p>
<h2>8. PROTECCIÃ“N DE DATOS</h2>
<p>Todo lo relativo a la polÃ­tica de protecciÃ³n de datos se encuentra recogido en el documento de Política de Privacidad de la entidad, conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley OrgÃ¡nica 3/2018 (LOPDGDD).</p>
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


/* ==========================================================================
   FASE 2: WPO, METADATOS Y REDIRECCIONES
   ========================================================================== */

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

// 2. Limpieza de Metadatos: Forzar TÃ­tulo y DescripciÃ³n Global para Home
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

    // Inyectar noindex si no es dominio de producciÃ³n (protecciÃ³n staging)
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

        // SMART REDIRECT ELIMINADO: El sistema anterior hacía una WP_Query con búsqueda de texto
        // libre y redirigía 301 permanentemente al primer resultado. Esto podía enviar a Google
        // y a los usuarios a páginas completamente no equivalentes, dañando el SEO.
        //
        // Si necesitas recuperar una URL específica, añade la redirección manualmente
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
// en cada carga de WordPress, incluso si se habían dejado como borrador o papelera intencionalmente.
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
    // Versión de la política activa (incrementar manualmente al actualizar la política)
    $gdpr_version = '2025-01-01';

    // Validación: nombre obligatorio
    if ( empty( trim( $nombre ) ) ) {
        wp_send_json_error( array( 'message' => 'El nombre es obligatorio.' ) );
        return;
    }

    // Validación: email obligatorio y formato correcto
    if ( empty( $email ) || ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Introduce un correo electrónico válido.' ) );
        return;
    }

    // Validación: longitudes máximas para prevenir abuso
    if ( mb_strlen( $nombre ) > 120 || mb_strlen( $mensaje ) > 3000 || mb_strlen( $telefono ) > 30 ) {
        wp_send_json_error( array( 'message' => 'Algún campo supera la longitud permitida.' ) );
        return;
    }

    // Validación: consentimiento GDPR obligatorio
    if ( '1' !== $gdpr ) {
        wp_send_json_error( array( 'message' => 'Debes aceptar la política de privacidad.' ) );
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
        $body .= "GDPR: Aceptado (servidor: {$gdpr_fecha_servidor}, política v{$gdpr_version})\n";
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
// que competían directamente con el CPT /rutas/* y /destinos/*.
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
        'Peñíscola',
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

    // Título SEO específico para rutas comerciales
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
	if ( function_exists( 'wp_get_environment_type' ) && wp_get_environment_type() !== 'production' ) {
		return [
			'noindex'   => true,
			'nofollow'  => true,
			'noarchive' => true,
		];
	}
	return $robots;
} );

/**
 * Full restoration of legal pages (Title, Slug, Content) to fix 404s and empty content.
 */
// DESACTIVADO: mt_full_restore_legal_pages_once() sobreescribía el contenido de las páginas
// legales con texto hardcodeado en functions.php en cada instalación nueva.
// Las páginas legales deben editarse desde el panel de WordPress. El contenido de esta
// función se conserva como referencia pero NO debe re-activarse: cualquier actualización del
// tema sobreescribiría cambios legales aprobados.
// add_action( 'admin_init', 'mt_full_restore_legal_pages_once' );
function mt_full_restore_legal_pages_once() {
    if ( get_transient( 'mt_full_restored_legal_pages_v1' ) ) {
        return;
    }
    
    // The exact content from the original XML for the Spanish pages
    $legal_pages = array(
        'politica-de-privacidad' => array(
            'title' => 'Política de privacidad',
            'content' => '<h2>1. Identificaci&oacute;n del Responsable del Tratamiento</h2><p><strong>Raz&oacute;n Social:</strong> METRANSFERS GESTION SL</p><p><strong>NIF:</strong> B22522353</p><p><strong>Domicilio Fiscal:</strong> AVDA MARE DE DEU DE MONTSERRAT, NUM 18, PLANTA 5, PUERTA 2, 08970 SANT JOAN DESP&Iacute; &ndash; (BARCELONA)</p><p><strong>Contacto Privacidad:</strong> <a href="mailto:info@metransfers.es">info@metransfers.es</a></p><h2>2. Aceptaci&oacute;n Vinculante</h2><p>Al utilizar nuestros servicios, navegar por nuestra plataforma o completar el proceso de configuraci&oacute;n de una reserva, usted reconoce haber le&iacute;do, comprendido y aceptado sin reservas que sus datos personales sean tratados conforme a los t&eacute;rminos aqu&iacute; expuestos. La formalizaci&oacute;n de una reserva constituye un contrato entre las partes, legitimando el tratamiento de los datos necesarios para la ejecuci&oacute;n del servicio.</p><h2>3. Datos Objeto de Tratamiento</h2><p>Recopilamos los datos estrictamente necesarios para la prestaci&oacute;n del servicio:</p><ul><li><strong>Datos de Reserva:</strong> Nombre, apellidos, tel&eacute;fono, correo electr&oacute;nico y detalles del trayecto/servicio solicitado.</li><li><strong>Datos de Facturaci&oacute;n:</strong> Direcci&oacute;n postal y NIF/DNI (seg&uacute;n los datos de registro fiscal de la entidad).</li><li><strong>Datos de Navegaci&oacute;n:</strong> Direcci&oacute;n IP, cookies y metadatos para garantizar la seguridad del sitio.</li></ul><h2>4. Finalidad del Tratamiento</h2><p>Sus datos ser&aacute;n tratados con el fin de:</p><ul><li><strong>Gesti&oacute;n de Reservas:</strong> Tramitar, confirmar y ejecutar los servicios de transporte o gesti&oacute;n contratados.</li><li><strong>Atenci&oacute;n al Cliente:</strong> Resolver dudas y proporcionar soporte a trav&eacute;s del punto &uacute;nico de contacto <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</li><li><strong>Cumplimiento Legal:</strong> Emitir facturas y cumplir con las obligaciones tributarias ante la AEAT.</li><li><strong>Seguridad:</strong> Prevenir fraudes y usos no autorizados de la plataforma.</li></ul><h2>5. Legitimaci&oacute;n</h2><p>La base legal para el tratamiento es:</p><ul><li><strong>Ejecuci&oacute;n Contractual:</strong> Necesaria para procesar su reserva y prestarle el servicio solicitado.</li><li><strong>Obligaci&oacute;n Legal:</strong> Derivada de la normativa fiscal y mercantil vigente en Espa&ntilde;a.</li><li><strong>Consentimiento:</strong> Otorgado expl&iacute;citamente al marcar la casilla de aceptaci&oacute;n en nuestros formularios.</li></ul><h2>6. Conservaci&oacute;n y Destinatarios</h2><p><strong>Plazos:</strong> Los datos se conservar&aacute;n durante el tiempo que dure la relaci&oacute;n comercial y, posteriormente, durante los plazos legales de prescripci&oacute;n (generalmente 6 a&ntilde;os para documentos contables seg&uacute;n el C&oacute;digo de Comercio).</p><p><strong>Cesiones:</strong> No se ceder&aacute;n datos a terceros ajenos a la operativa del servicio, salvo obligaci&oacute;n legal ante autoridades competentes.</p><h2>7. Derechos del Interesado</h2><p>Usted puede ejercer sus derechos de Acceso, Rectificaci&oacute;n, Supresi&oacute;n, Limitaci&oacute;n, Portabilidad y Oposici&oacute;n enviando una comunicaci&oacute;n escrita acompa&ntilde;ada de copia de su DNI a: <a href="mailto:info@metransfers.es">info@metransfers.es</a>.</p><p>Asimismo, tiene derecho a retirar su consentimiento en cualquier momento y a presentar una reclamaci&oacute;n ante la Agencia Espa&ntilde;ola de Protecci&oacute;n de Datos (AEPD) si considera que sus derechos han sido vulnerados.</p>'
        ),
        'politica-de-cookies' => array(
            'title' => 'Política de Cookies',
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

