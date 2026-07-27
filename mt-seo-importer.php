<?php
/**
 * MT SEO Importer
 * Crea las páginas de destinos y rutas comerciales de la Fase 1.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mt_run_seo_importer_phase_1() {
    // Solo ejecutar una vez
    if ( get_transient( 'mt_seo_imported_phase_1' ) ) {
        return;
    }

    // 1. Crear página padre "Destinos" si no existe
    $destinos_page = get_page_by_path( 'destinos', OBJECT, 'page' );
    $destinos_id = 0;
    
    if ( ! $destinos_page ) {
        $destinos_id = wp_insert_post( array(
            'post_title'   => 'Destinos de MeTransfers',
            'post_name'    => 'destinos',
            'post_status'  => 'draft',
            'post_type'    => 'page',
        ) );
    } else {
        $destinos_id = $destinos_page->ID;
    }

    // 2. Crear destinos hijos (Páginas regulares)
    $destinos = array(
        'lloret-de-mar' => 'Transfer privado Lloret de Mar',
        'salou'         => 'Transfer privado Salou',
        'portaventura'  => 'Transfer privado PortAventura',
    );

    foreach ( $destinos as $slug => $title ) {
        $page = get_page_by_path( 'destinos/' . $slug, OBJECT, 'page' );
        if ( ! $page ) {
            wp_insert_post( array(
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_status'  => 'draft',
                'post_type'    => 'page',
                'post_parent'  => $destinos_id,
            ) );
        }
    }

    // 3. Crear rutas comerciales (CPT: ruta)
    $rutas = array(
        // Grupo Lloret de Mar
        'aeropuerto-barcelona-lloret-de-mar' => 'Aeropuerto de Barcelona–Lloret',
        'aeropuerto-girona-lloret-de-mar'    => 'Aeropuerto de Girona–Lloret',
        'barcelona-lloret-de-mar'            => 'Barcelona centro–Lloret',
        'puerto-barcelona-lloret-de-mar'     => 'Puerto de Barcelona–Lloret',
        'estacion-sants-lloret-de-mar'       => 'Estación de Sants–Lloret',
        
        // Grupo Salou
        'aeropuerto-barcelona-salou'         => 'Aeropuerto de Barcelona–Salou',
        'aeropuerto-reus-salou'              => 'Aeropuerto de Reus–Salou',
        'barcelona-salou'                    => 'Barcelona centro–Salou',
        'puerto-barcelona-salou'             => 'Puerto de Barcelona–Salou',
        'estacion-sants-salou'               => 'Estación de Sants–Salou',
        
        // Grupo PortAventura
        'aeropuerto-barcelona-portaventura'  => 'Aeropuerto de Barcelona–PortAventura',
        'aeropuerto-reus-portaventura'       => 'Aeropuerto de Reus–PortAventura',
        'salou-portaventura'                 => 'Salou–PortAventura',
    );

    foreach ( $rutas as $slug => $title ) {
        // Verificar si la ruta ya existe
        $ruta_existente = get_page_by_path( $slug, OBJECT, 'ruta' );
        if ( ! $ruta_existente ) {
            $parts = explode( '–', $title );
            $origen = isset( $parts[0] ) ? trim( $parts[0] ) : '';
            $destino = isset( $parts[1] ) ? trim( $parts[1] ) : '';

            wp_insert_post( array(
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_status'  => 'draft',
                'post_type'    => 'ruta',
                'meta_input'   => array(
                    '_mt_ruta_origen'   => $origen,
                    '_mt_ruta_destino'  => $destino,
                    '_mt_ruta_duracion' => '60 min',
                    '_mt_ruta_pax'      => '1-8',
                    '_mt_ruta_maletas'  => '8',
                ),
            ) );
        }
    }

    // Refrescar reglas de reescritura para asegurar que los nuevos slugs y CPT funcionen
    flush_rewrite_rules();

    // Marcar como completado
    set_transient( 'mt_seo_imported_phase_1', true, YEAR_IN_SECONDS );
}
