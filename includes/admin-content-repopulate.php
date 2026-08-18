<?php
/**
 * Admin Page Creator and Content Repopulator - MeTransfers
 *
 * PASO 1: Crea todas las páginas faltantes
 * PASO 2: Rellena el post_content desde los catálogos PHP
 *
 * Herramientas > Repoblar Contenido
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'mt_repopulate_register_tools_page' );
function mt_repopulate_register_tools_page() {
    add_management_page(
        'Crear Páginas y Repoblar Contenido',
        'Repoblar Contenido',
        'manage_options',
        'mt-repopulate-content',
        'mt_repopulate_render_page'
    );
}

// AJAX 2A: CREAR PÁGINAS FALTANTES
add_action( 'wp_ajax_mt_create_missing_pages', 'mt_create_missing_pages_ajax' );
function mt_create_missing_pages_ajax() {
    check_ajax_referer( 'mt_repopulate_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Sin permisos.' ) );
    }

    $log = array();
    $created = 0;
    $skipped = 0;

    // A. PAGINAS BASICAS
    $basic_pages = array(
        'contacto'             => 'MeTransfers Barcelona - Contacto',
        'reservaciones'        => 'MeTransfers Barcelona - Reservaciones',
        'gracias'              => 'MeTransfers Barcelona - Gracias por tu reserva',
        'preguntas-frecuentes' => 'MeTransfers Barcelona - Preguntas Frecuentes',
    );
    foreach ( $basic_pages as $slug => $title ) {
        $existing = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $existing ) {
            $id = wp_insert_post( array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => '',
            ), true );
            if ( is_wp_error( $id ) ) {
                $log[] = '❌ Error creando /' . esc_html( $slug ) . '/: ' . $id->get_error_message();
            } else {
                $created++;
                $log[] = '✅ Creada: /' . esc_html( $slug ) . '/';
            }
        } else {
            $skipped++;
            $log[] = '🔵 Ya existe: /' . esc_html( $slug ) . '/';
        }
    }

    // B. PAGINAS LEGALES
    if ( function_exists( 'me_transfers_get_legal_pages_catalog' ) && function_exists( 'me_transfers_sync_legal_pages' ) ) {
        delete_option( 'me_transfers_legal_pages_sync_version' );
        me_transfers_sync_legal_pages();
        foreach ( me_transfers_get_legal_pages_catalog() as $slug => $title ) {
            $page = get_page_by_path( $slug, OBJECT, 'page' );
            if ( $page ) {
                $log[] = '✅ Legal OK: /' . esc_html( $slug ) . '/';
            } else {
                $log[] = '⚠️ Legal no creada: /' . esc_html( $slug ) . '/';
            }
        }
    }

    // C. HUB DE DESTINOS
    $hub = get_page_by_path( 'destinos', OBJECT, 'page' );
    $hub_id = 0;
    if ( ! $hub ) {
        $hub_result = wp_insert_post( array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'MeTransfers Barcelona - Destinos de traslados privados desde Barcelona',
            'post_name'    => 'destinos',
            'post_content' => '',
        ), true );
        if ( is_wp_error( $hub_result ) ) {
            $log[] = '❌ Error hub /destinos/: ' . $hub_result->get_error_message();
        } else {
            $hub_id = $hub_result;
            $created++;
            update_post_meta( $hub_id, '_me_transfers_page_role', 'destinations_hub' );
            $log[] = '✅ Creado: /destinos/';
        }
    } else {
        $hub_id = $hub->ID;
        $log[] = '🔵 Ya existe: /destinos/';
    }

    // D. DESTINOS INDIVIDUALES
    if ( $hub_id && function_exists( 'me_transfers_get_destination_catalog' ) ) {
        delete_option( 'me_transfers_destinations_sync_version' );
        $destinations = me_transfers_get_destination_catalog();
        foreach ( $destinations as $dest ) {
            $dslug = $dest['slug'];
            $existing = get_page_by_path( $dslug, OBJECT, 'page' );
            if ( ! $existing ) {
                $existing = get_page_by_path( 'destinos/' . $dslug, OBJECT, 'page' );
            }
            if ( ! $existing ) {
                $pid = wp_insert_post( array(
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                    'post_parent'  => $hub_id,
                    'post_title'   => 'MeTransfers Barcelona - Traslado privado a ' . $dest['title'] . ' desde Barcelona',
                    'post_name'    => $dslug,
                    'post_content' => '',
                ), true );
                if ( is_wp_error( $pid ) ) {
                    $log[] = '❌ Error destino ' . esc_html( $dslug ) . ': ' . $pid->get_error_message();
                } else {
                    update_post_meta( $pid, '_me_transfers_page_role', 'destination' );
                    $created++;
                    $log[] = '✅ Creado: /destinos/' . esc_html( $dslug ) . '/';
                }
            } else {
                $skipped++;
                $log[] = '🔵 Ya existe: /destinos/' . esc_html( $dslug ) . '/';
            }
        }
    }

    // E. SERVICIOS
    if ( function_exists( 'me_transfers_get_service_catalog' ) ) {
        if ( function_exists( 'me_transfers_sync_service_pages' ) ) {
            delete_option( 'me_transfers_services_sync_version' );
            me_transfers_sync_service_pages();
        }
        foreach ( me_transfers_get_service_catalog() as $slug => $service ) {
            $page = get_page_by_path( $slug, OBJECT, 'page' );
            if ( $page ) {
                $log[] = '✅ Servicio OK: /' . esc_html( $slug ) . '/';
            } else {
                $id = wp_insert_post( array(
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                    'post_title'   => $service['title'],
                    'post_name'    => $slug,
                    'post_content' => '',
                ), true );
                if ( ! is_wp_error( $id ) ) {
                    $created++;
                    $log[] = '✅ Creado servicio: /' . esc_html( $slug ) . '/';
                } else {
                    $log[] = '❌ Error servicio: /' . esc_html( $slug ) . '/';
                }
            }
        }
    }

    // F. HUB DE TOURS
    $tours_page = get_page_by_path( 'tours', OBJECT, 'page' );
    if ( ! $tours_page ) { $tours_page = get_page_by_path( 'tours-privados', OBJECT, 'page' ); }
    if ( ! $tours_page ) {
        $tid = wp_insert_post( array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'MeTransfers Barcelona - Tours Privados desde Barcelona',
            'post_name'    => 'tours',
            'post_content' => '',
        ), true );
        if ( ! is_wp_error( $tid ) ) {
            $created++;
            $log[] = '✅ Creado: /tours/';
        }
    } else {
        $log[] = '🔵 Ya existe: /' . $tours_page->post_name . '/';
    }

    // G. TOURS INDIVIDUALES
    if ( function_exists( 'me_transfers_get_tour_catalog' ) ) {
        if ( function_exists( 'me_transfers_sync_tour_pages' ) ) {
            delete_option( 'me_transfers_tour_pages_sync_version' );
            me_transfers_sync_tour_pages();
        }
        foreach ( me_transfers_get_tour_catalog() as $slug => $tour ) {
            $page = get_page_by_path( $slug, OBJECT, 'page' );
            if ( $page ) {
                $log[] = '✅ Tour OK: /' . esc_html( $slug ) . '/';
            } else {
                $id = wp_insert_post( array(
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                    'post_title'   => $tour['title'],
                    'post_name'    => $slug,
                    'post_content' => '',
                ), true );
                if ( ! is_wp_error( $id ) ) {
                    $created++;
                    $log[] = '✅ Creado tour: /' . esc_html( $slug ) . '/';
                } else {
                    $log[] = '❌ Error tour: /' . esc_html( $slug ) . '/';
                }
            }
        }
    }

    // H. 30 LANDING PAGES SEO DINAMICAS
    $seo_destinations = array(
        'barcelona'     => 'Barcelona',
        'salou'         => 'Salou',
        'lloret-de-mar' => 'Lloret de Mar',
        'sitges'        => 'Sitges',
        'tarragona'     => 'Tarragona',
        'reus'          => 'Reus',
        'girona'        => 'Girona',
        'andorra'       => 'Andorra',
        'perpignan'     => 'Perpignan',
        'montserrat'    => 'Montserrat',
        'granollers'    => 'Granollers',
        'mataro'        => 'Mataro',
        'badalona'      => 'Badalona',
        'hospitalet'    => 'LHospitalet de Llobregat',
        'costa-brava'   => 'Costa Brava',
    );
    $seo_types = array(
        'taxis'     => array( 'suffix' => ' - Taxis y Traslados Privados desde Barcelona', 'tpl' => 'page-taxis' ),
        'traslados' => array( 'suffix' => ' - Traslados Privados desde Barcelona',         'tpl' => 'page-seo-dynamic' ),
    );
    foreach ( $seo_destinations as $dslug => $dname ) {
        foreach ( $seo_types as $tkey => $tdata ) {
            $pslug = $dslug . '-' . $tkey;
            $ptitle = $dname . $tdata['suffix'];
            $existing = get_page_by_path( $pslug, OBJECT, 'page' );
            if ( ! $existing ) {
                $id = wp_insert_post( array(
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                    'post_title'   => $ptitle,
                    'post_name'    => $pslug,
                    'post_content' => '',
                ), true );
                if ( ! is_wp_error( $id ) ) {
                    update_post_meta( $id, '_wp_page_template', $tdata['tpl'] . '.php' );
                    update_post_meta( $id, '_me_transfers_page_role', 'seo_dynamic' );
                    $created++;
                    $log[] = '✅ SEO: /' . esc_html( $pslug ) . '/';
                } else {
                    $log[] = '❌ Error SEO: /' . esc_html( $pslug ) . '/';
                }
            } else {
                $skipped++;
                $log[] = '🔵 Ya existe SEO: /' . esc_html( $pslug ) . '/';
            }
        }
    }

    flush_rewrite_rules( false );

    wp_send_json_success( array(
        'created' => $created,
        'skipped' => $skipped,
        'log'     => $log,
    ) );
}

// AJAX 2B: REPOBLAR POST_CONTENT
add_action( 'wp_ajax_mt_repopulate_content', 'mt_repopulate_content_ajax' );
function mt_repopulate_content_ajax() {
    check_ajax_referer( 'mt_repopulate_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Sin permisos.' ) );
    }

    delete_option( 'me_transfers_editor_populated_v4' );
    delete_option( 'me_transfers_content_migrated_v1' );
    delete_option( 'me_transfers_content_migrated_services' );
    delete_option( 'me_transfers_content_migrated_legal' );
    delete_transient( 'me_transfers_populating_content_v4' );

    $log = array();
    $updated = 0;
    $skipped = 0;

    // DESTINATIONS
    if ( function_exists( 'me_transfers_get_destination_catalog' ) ) {
        $catalog = me_transfers_get_destination_catalog();

        $hub = get_page_by_path( 'destinos' );
        if ( $hub ) {
            $c  = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Destinos de traslados privados desde Barcelona</h2>\n<!-- /wp:heading -->\n\n";
            $c .= "<!-- wp:paragraph -->\n<p>Ofrecemos traslados privados desde Barcelona a más de 38 destinos en España y Francia. Todos nuestros servicios incluyen chófer profesional, vehículo Mercedes de alta gama, recogida puerta a puerta y tarifa cerrada sin sorpresas.</p>\n<!-- /wp:paragraph -->\n\n";
            $c .= "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">Por qué elegir MeTransfers</h3>\n<!-- /wp:heading -->\n\n";
            $c .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
            $c .= "<li>Chófer privado profesional y bilingüe</li>";
            $c .= "<li>Flota Mercedes premium: ECONOMIC CLASS, MINI VAN V Class y BUSINESS CLASS</li>";
            $c .= "<li>Precio cerrado desde el primer momento, sin recargos por tráfico</li>";
            $c .= "<li>Seguimiento de vuelo y 60 min de espera gratuita en aeropuertos</li>";
            $c .= "<li>Más de 38 destinos en España y sur de Francia</li>";
            $c .= "</ul>\n<!-- /wp:list -->";
            wp_update_post( array( 'ID' => $hub->ID, 'post_content' => $c ) );
            $updated++;
            $log[] = '✅ Hub destinos (ID ' . $hub->ID . ')';
        } else {
            $log[] = '❌ Hub /destinos/ no encontrado. Ejecuta primero el Paso 1.';
        }

        foreach ( $catalog as $slug => $dest ) {
            $page = get_page_by_path( 'destinos/' . $slug );
            if ( ! $page ) { $page = get_page_by_path( $slug ); }
            if ( ! $page ) {
                $skipped++;
                $log[] = '⚠️ Sin página para destino: ' . esc_html( $slug );
                continue;
            }
            $title = esc_html( $dest['title'] );
            $c  = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Traslado privado a " . $title . " desde Barcelona</h2>\n<!-- /wp:heading -->\n\n";
            if ( ! empty( $dest['summary'] ) ) {
                $c .= "<!-- wp:paragraph -->\n<p>" . esc_html( $dest['summary'] ) . "</p>\n<!-- /wp:paragraph -->\n\n";
            }
            if ( ! empty( $dest['travel_note'] ) ) {
                $c .= "<!-- wp:paragraph -->\n<p>" . esc_html( $dest['travel_note'] ) . "</p>\n<!-- /wp:paragraph -->\n\n";
            }
            if ( ! empty( $dest['highlights'] ) ) {
                $c .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
                foreach ( $dest['highlights'] as $hl ) { $c .= '<li>' . esc_html( $hl ) . '</li>'; }
                $c .= "</ul>\n<!-- /wp:list -->\n\n";
            }
            $c .= "<!-- wp:paragraph -->\n<p>Solicita información o reserva tu traslado privado a " . $title . " desde Barcelona con tarifa cerrada, chófer profesional y vehículo Mercedes de alta gama.</p>\n<!-- /wp:paragraph -->";
            wp_update_post( array( 'ID' => $page->ID, 'post_content' => $c ) );
            $updated++;
            $log[] = '✅ Destino: ' . esc_html( $dest['title'] ) . ' (ID ' . $page->ID . ')';
        }
    }

    // SERVICES
    if ( function_exists( 'me_transfers_get_service_catalog' ) ) {
        foreach ( me_transfers_get_service_catalog() as $slug => $service ) {
            $page = get_page_by_path( $slug );
            if ( ! $page ) { $skipped++; $log[] = '⚠️ Sin página para servicio: ' . esc_html( $slug ); continue; }
            $c = '';
            if ( ! empty( $service['subtitle'] ) ) {
                $c .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $service['subtitle'] ) . "</h2>\n<!-- /wp:heading -->\n\n";
            }
            if ( ! empty( $service['hero_desc'] ) ) {
                $c .= "<!-- wp:paragraph -->\n<p>" . esc_html( $service['hero_desc'] ) . "</p>\n<!-- /wp:paragraph -->\n\n";
            }
            if ( ! empty( $service['desc_long'] ) ) {
                foreach ( array_filter( array_map( 'trim', explode( "\n\n", $service['desc_long'] ) ) ) as $para ) {
                    $c .= "<!-- wp:paragraph -->\n<p>" . esc_html( $para ) . "</p>\n<!-- /wp:paragraph -->\n\n";
                }
            }
            if ( ! empty( $service['features'] ) ) {
                $c .= "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">¿Qué incluye el servicio?</h3>\n<!-- /wp:heading -->\n\n";
                $c .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
                foreach ( $service['features'] as $feat ) {
                    $label = is_array( $feat ) ? ( $feat['title'] . ': ' . $feat['desc'] ) : $feat;
                    $c .= '<li>' . esc_html( $label ) . '</li>';
                }
                $c .= "</ul>\n<!-- /wp:list -->\n\n";
            }
            if ( ! empty( $service['steps'] ) ) {
                $c .= "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">¿Cómo funciona?</h3>\n<!-- /wp:heading -->\n\n";
                $c .= "<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">";
                foreach ( $service['steps'] as $step ) {
                    $st = is_array( $step ) ? $step['title'] : $step;
                    $sd = is_array( $step ) ? ( $step['desc'] ?? '' ) : '';
                    $c .= '<li><strong>' . esc_html( $st ) . '</strong>' . ( $sd ? ' - ' . esc_html( $sd ) : '' ) . '</li>';
                }
                $c .= "</ol>\n<!-- /wp:list -->";
            }
            wp_update_post( array( 'ID' => $page->ID, 'post_content' => $c ) );
            $updated++;
            $log[] = '✅ Servicio: ' . esc_html( $service['title'] ) . ' (ID ' . $page->ID . ')';
        }
    }

    // TOURS
    if ( function_exists( 'me_transfers_get_tour_catalog' ) ) {
        $tours = me_transfers_get_tour_catalog();
        foreach ( $tours as $slug => $tour ) {
            $page = get_page_by_path( $slug );
            if ( ! $page ) { $skipped++; $log[] = '⚠️ Sin página para tour: ' . esc_html( $slug ); continue; }
            $c = '';
            if ( ! empty( $tour['desc'] ) ) {
                $c .= "<!-- wp:paragraph -->\n<p>" . esc_html( $tour['desc'] ) . "</p>\n<!-- /wp:paragraph -->\n\n";
            }
            $details = array();
            if ( ! empty( $tour['price'] ) )     $details[] = 'Precio: ' . $tour['price'];
            if ( ! empty( $tour['duration'] ) )   $details[] = 'Duración: ' . $tour['duration'];
            if ( ! empty( $tour['group_size'] ) ) $details[] = 'Grupo máximo: ' . $tour['group_size'];
            if ( $details ) {
                $c .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
                foreach ( $details as $d ) { $c .= '<li>' . esc_html( $d ) . '</li>'; }
                $c .= "</ul>\n<!-- /wp:list -->\n\n";
            }
            if ( ! empty( $tour['full_desc'] ) ) {
                foreach ( array_filter( array_map( 'trim', explode( "\n\n", $tour['full_desc'] ) ) ) as $para ) {
                    $c .= "<!-- wp:paragraph -->\n<p>" . esc_html( $para ) . "</p>\n<!-- /wp:paragraph -->\n\n";
                }
            }
            if ( ! empty( $tour['itinerary'] ) ) {
                $c .= "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">Itinerario del tour</h3>\n<!-- /wp:heading -->\n\n";
                $c .= "<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">";
                foreach ( $tour['itinerary'] as $item ) { $c .= '<li>' . esc_html( $item ) . '</li>'; }
                $c .= "</ol>\n<!-- /wp:list -->\n\n";
            }
            if ( ! empty( $tour['includes'] ) ) {
                $c .= "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">¿Qué incluye?</h3>\n<!-- /wp:heading -->\n\n";
                $c .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
                foreach ( $tour['includes'] as $inc ) { $c .= '<li>' . esc_html( $inc ) . '</li>'; }
                $c .= "</ul>\n<!-- /wp:list -->";
            }
            wp_update_post( array( 'ID' => $page->ID, 'post_content' => $c ) );
            $updated++;
            $log[] = '✅ Tour: ' . esc_html( $tour['title'] ) . ' (ID ' . $page->ID . ')';
        }

        // Tours hub
        $tp = get_page_by_path( 'tours' );
        if ( ! $tp ) { $tp = get_page_by_path( 'tours-privados' ); }
        if ( $tp ) {
            $c  = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Tours privados desde Barcelona con chófer</h2>\n<!-- /wp:heading -->\n\n";
            $c .= "<!-- wp:paragraph -->\n<p>Descubre los mejores destinos alrededor de Barcelona con nuestros tours privados en vehículo Mercedes con chófer profesional.</p>\n<!-- /wp:paragraph -->\n\n";
            $c .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
            foreach ( $tours as $tour ) {
                $c .= '<li><strong>' . esc_html( $tour['title'] ) . '</strong> - ' . esc_html( $tour['price'] ) . ', ' . esc_html( $tour['duration'] ) . '</li>';
            }
            $c .= "</ul>\n<!-- /wp:list -->";
            wp_update_post( array( 'ID' => $tp->ID, 'post_content' => $c ) );
            $updated++;
            $log[] = '✅ Hub tours (ID ' . $tp->ID . ')';
        }
    }

    // H. 30 LANDING PAGES SEO DINAMICAS
    $seo_destinations = array(
        'barcelona'     => 'Barcelona',
        'salou'         => 'Salou',
        'lloret-de-mar' => 'Lloret de Mar',
        'sitges'        => 'Sitges',
        'tarragona'     => 'Tarragona',
        'reus'          => 'Reus',
        'girona'        => 'Girona',
        'andorra'       => 'Andorra',
        'perpignan'     => 'Perpignan',
        'montserrat'    => 'Montserrat',
        'granollers'    => 'Granollers',
        'mataro'        => 'Mataro',
        'badalona'      => 'Badalona',
        'hospitalet'    => 'LHospitalet de Llobregat',
        'costa-brava'   => 'Costa Brava',
    );
    $seo_types = array(
        'taxis'     => array( 'suffix' => ' - Taxis y Traslados Privados desde Barcelona', 'tpl' => 'page-taxis', 'tipo' => 'Taxis' ),
        'traslados' => array( 'suffix' => ' - Traslados Privados desde Barcelona',         'tpl' => 'page-seo-dynamic', 'tipo' => 'Traslados privados' ),
    );
    foreach ( $seo_destinations as $dslug => $dname ) {
        foreach ( $seo_types as $tkey => $tdata ) {
            $pslug = $dslug . '-' . $tkey;
            $page = get_page_by_path( $pslug );
            if ( $page ) {
                $c = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $tdata['tipo'] ) . " a " . esc_html( $dname ) . " sin esperas</h2>\n<!-- /wp:heading -->\n\n";
                $c .= "<!-- wp:paragraph -->\n<p>Llegar a " . esc_html( $dname ) . " desde Barcelona nunca fue tan sencillo. Evita las largas colas para el autobús y las incómodas combinaciones de trenes con equipaje. Nuestro servicio de <strong>" . esc_html( strtolower($tdata['tipo']) ) . " a " . esc_html( $dname ) . "</strong> está pensado para que tu descanso comience desde el momento en que aterrizas.</p>\n<!-- /wp:paragraph -->\n\n";
                $c .= "<!-- wp:paragraph -->\n<p>Nuestros conductores monitorizan los vuelos de llegada al Aeropuerto de El Prat, por lo que incluso si tu vuelo se retrasa, te estaremos esperando en la terminal de llegadas con un cartel con tu nombre. Desde allí, el trayecto hasta " . esc_html( $dname ) . " será directo y confortable.</p>\n<!-- /wp:paragraph -->\n\n";
                $c .= "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">La mejor opción para familias y grupos</h3>\n<!-- /wp:heading -->\n\n";
                $c .= "<!-- wp:paragraph -->\n<p>Si viajas con amigos, familia o grupos grandes, disponemos de espaciosas furgonetas y minivans Mercedes-Benz con capacidad para hasta 8 pasajeros y todo su equipaje.</p>\n<!-- /wp:paragraph -->\n\n";
                $c .= "<!-- wp:paragraph -->\n<p>Además, proporcionamos bajo solicitud las sillas de retención infantil adecuadas para que los más pequeños viajen de forma segura y cumpliendo todas las normativas de tráfico.</p>\n<!-- /wp:paragraph -->\n\n";
                $c .= "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p><strong>CONSEJO: Reserva tu trayecto de ida y vuelta.</strong> Para disfrutar de un viaje sin sobresaltos, te aconsejamos reservar conjuntamente el trayecto de ida y el de vuelta al aeropuerto. Nuestro conductor te recogerá en la puerta de tu hotel en " . esc_html( $dname ) . " a la hora óptima para que llegues a tiempo a tu vuelo en Barcelona.</p></blockquote>\n<!-- /wp:quote -->";
                wp_update_post( array( 'ID' => $page->ID, 'post_content' => $c ) );
                $updated++;
                $log[] = '✅ SEO repoblado: /' . esc_html( $pslug ) . '/';
            }
        }
    }

    // I. LEGAL PAGES
    if ( function_exists( 'me_transfers_get_legal_pages_catalog' ) ) {
        foreach ( me_transfers_get_legal_pages_catalog() as $slug => $title ) {
            $page = get_page_by_path( $slug );
            if ( $page && empty( $page->post_content ) ) {
                $c = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $title ) . "</h2>\n<!-- /wp:heading -->\n\n";
                $c .= "<!-- wp:paragraph -->\n<p>Contenido legal para " . esc_html( $title ) . ". Por favor, edita esta página para incluir tus textos legales definitivos.</p>\n<!-- /wp:paragraph -->";
                wp_update_post( array( 'ID' => $page->ID, 'post_content' => $c ) );
                $updated++;
                $log[] = '✅ Legal repoblada: /' . esc_html( $slug ) . '/';
            }
        }
    }

    update_option( 'me_transfers_editor_populated_v4', true );
    wp_send_json_success( array( 'updated' => $updated, 'skipped' => $skipped, 'log' => $log ) );
}

// RENDER PAGE
function mt_repopulate_render_page() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $nonce = wp_create_nonce( 'mt_repopulate_nonce' );
    $ajax  = admin_url( 'admin-ajax.php' );
    ?>
    <div class="wrap">
        <h1>Crear Páginas y Repoblar Contenido - MeTransfers</h1>
        <div class="notice notice-warning">
            <p><strong>Orden recomendado:</strong> Ejecuta primero el <strong>Paso 1</strong> (crear páginas), luego el <strong>Paso 2</strong> (rellenar contenido). Ambos son seguros de ejecutar múltiples veces.</p>
        </div>
        <div class="card" style="max-width:680px;padding:20px;margin-top:20px;border-left:4px solid #0073aa;">
            <h2 style="margin-top:0;color:#0073aa;">Paso 1 - Crear páginas faltantes</h2>
            <p>Crea: hub destinos, 38+ destinos individuales, 6 servicios, hub tours, 4 tours, páginas legales, básicas y 30 landing SEO dinámicas.</p>
            <button id="mt-create-pages-btn" class="button button-primary button-large">Crear todas las páginas faltantes</button>
            <span id="mt-create-spinner" class="spinner" style="float:none;margin-left:10px;visibility:hidden;"></span>
        </div>
        <div id="mt-create-results" style="margin-top:15px;display:none;">
            <div class="card" style="max-width:700px;padding:20px;">
                <h3 id="mt-create-summary" style="margin-top:0;"></h3>
                <div id="mt-create-log" style="background:#1e1e1e;color:#d4d4d4;font-family:monospace;font-size:13px;padding:16px;border-radius:4px;max-height:400px;overflow-y:auto;line-height:1.6;"></div>
            </div>
        </div>
        <div class="card" style="max-width:680px;padding:20px;margin-top:30px;border-left:4px solid #2e7d32;">
            <h2 style="margin-top:0;color:#2e7d32;">Paso 2 - Repoblar contenido en el editor</h2>
            <p>Escribe el contenido de los catálogos PHP en post_content para Gutenberg y Yoast SEO.</p>
            <button id="mt-repopulate-btn" class="button button-primary button-large" style="background:#2e7d32;border-color:#1b5e20;">Repoblar contenido ahora</button>
            <span id="mt-repopulate-spinner" class="spinner" style="float:none;margin-left:10px;visibility:hidden;"></span>
        </div>
        <div id="mt-repopulate-results" style="margin-top:15px;display:none;">
            <div class="card" style="max-width:700px;padding:20px;">
                <h3 id="mt-results-summary" style="margin-top:0;"></h3>
                <div id="mt-results-log" style="background:#1e1e1e;color:#d4d4d4;font-family:monospace;font-size:13px;padding:16px;border-radius:4px;max-height:400px;overflow-y:auto;line-height:1.6;"></div>
            </div>
        </div>
    </div>
    <script>
    function mtFetch(action,btnId,spinnerId,resultsId,summaryId,logId,label){
        var btn=document.getElementById(btnId),sp=document.getElementById(spinnerId),
            res=document.getElementById(resultsId),sum=document.getElementById(summaryId),log=document.getElementById(logId);
        btn.disabled=true;sp.style.visibility='visible';res.style.display='none';
        fetch('<?php echo esc_js($ajax); ?>',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({action:action,nonce:'<?php echo esc_js($nonce); ?>'}) })
        .then(function(r){return r.json();}).then(function(data){
            sp.style.visibility='hidden';btn.disabled=false;res.style.display='block';
            if(data.success){
                var d=data.data,count=(d.created!==undefined)?d.created:d.updated;
                sum.innerHTML='✅ '+label+': <strong>'+count+' páginas</strong>'+(d.skipped?', '+d.skipped+' ya existen o sin página':'');
                sum.style.color='#2e7d32';
                log.innerHTML=d.log.map(function(l){return '<div>'+l+'</div>';}).join('');
            }else{
                sum.textContent='❌ Error: '+(data.data&&data.data.message||'Desconocido');
                sum.style.color='#c62828';
            }
        }).catch(function(e){
            sp.style.visibility='hidden';btn.disabled=false;
            sum.textContent='❌ Error de red: '+e.message;sum.style.color='#c62828';res.style.display='block';
        });
    }
    document.getElementById('mt-create-pages-btn').addEventListener('click',function(){
        mtFetch('mt_create_missing_pages','mt-create-pages-btn','mt-create-spinner','mt-create-results','mt-create-summary','mt-create-log','Páginas creadas');
    });
    document.getElementById('mt-repopulate-btn').addEventListener('click',function(){
        mtFetch('mt_repopulate_content','mt-repopulate-btn','mt-repopulate-spinner','mt-repopulate-results','mt-results-summary','mt-results-log','Páginas actualizadas');
    });
    </script>
    <?php
}
