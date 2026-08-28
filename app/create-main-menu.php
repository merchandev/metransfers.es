<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mt_create_main_menu() {
    // Only run once
    if ( get_option( 'mt_main_menu_created_v1' ) ) {
        return;
    }

    $menu_name = 'Menú Principal (Auto)';
    $menu_exists = wp_get_nav_menu_object( $menu_name );

    if ( ! $menu_exists ) {
        $menu_id = wp_create_nav_menu( $menu_name );

        if ( ! is_wp_error( $menu_id ) ) {
            // Helper function to get page URL and Title safely
            $get_page = function( $path ) {
                $page = get_page_by_path( $path );
                return $page ? $page : false;
            };

            // 1. SERVICIOS PREMIUM
            $servicios_id = wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'   => 'Servicios Premium',
                'menu-item-url'     => '#',
                'menu-item-status'  => 'publish',
                'menu-item-type'    => 'custom',
            ));

            $services = [
                'taxis-privado-barcelona' => 'Aeropuerto',
                'taxis-barcelona-costa-brava' => 'Costa Brava',
            ]; // Wait, I need the actual pages for the submenus
            // Let's just use custom links that point to the pages if they exist, or just use paths.
            // Actually, better to just create custom links with home_url() appended.
            
            $add_item = function( $parent_id, $title, $path ) use ( $menu_id ) {
                wp_update_nav_menu_item( $menu_id, 0, array(
                    'menu-item-title'     => $title,
                    'menu-item-url'       => home_url( '/' . $path . '/' ),
                    'menu-item-status'    => 'publish',
                    'menu-item-type'      => 'custom',
                    'menu-item-parent-id' => $parent_id,
                ));
            };

            // Submenu 1: Servicios Premium
            $add_item( $servicios_id, 'Aeropuerto', 'transfer-aeropuerto-barcelona' );
            $add_item( $servicios_id, 'Puerto', 'traslados-puerto' );
            $add_item( $servicios_id, 'Por horas', 'chofer-por-horas' );
            $add_item( $servicios_id, 'Empresas y Eventos', 'corporativo-y-eventos' );
            $add_item( $servicios_id, 'Grupos', 'grupos' );
            $add_item( $servicios_id, 'Traslados privados', 'taxis-privado-barcelona' );

            // 2. RUTAS Y DESTINOS
            $rutas_id = wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'   => 'Rutas y Destinos',
                'menu-item-url'     => '#',
                'menu-item-status'  => 'publish',
                'menu-item-type'    => 'custom',
            ));
            $add_item( $rutas_id, 'Costa Brava', 'taxis-barcelona-costa-brava' );
            $add_item( $rutas_id, 'Salou', 'taxis-barcelona-salou' );
            $add_item( $rutas_id, 'PortAventura', 'taxis-barcelona-port-aventura' );
            $add_item( $rutas_id, 'Girona', 'taxis-barcelona-girona' );
            $add_item( $rutas_id, 'Ver todas las rutas', 'rutas' );

            // 3. NOSOTROS
            $nosotros_id = wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'   => 'Nosotros',
                'menu-item-url'     => '#',
                'menu-item-status'  => 'publish',
                'menu-item-type'    => 'custom',
            ));
            $add_item( $nosotros_id, 'Sobre nosotros', 'sobre-nosotros' );
            $add_item( $nosotros_id, 'Preguntas frecuentes', 'preguntas-frecuentes' );
            $add_item( $nosotros_id, 'Blog', 'blog' );
            $add_item( $nosotros_id, 'Contacto', 'contacto' );

            // Assign the menu to the theme location 'menu-1'
            $locations = get_theme_mod( 'nav_menu_locations' );
            $locations['menu-1'] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
        }
    }

    update_option( 'mt_main_menu_created_v1', true );
}
add_action( 'init', 'mt_create_main_menu' );
