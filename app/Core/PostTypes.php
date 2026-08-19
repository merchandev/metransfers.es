<?php
namespace MeTransfers\Core;

class PostTypes {
    public function register() {
        // Register legacy WPTB Destinations
        $this->register_destinations();
        
        // Register legacy Hotel Partners
        $this->register_hotel_partners();
    }

    private function register_destinations() {
        $labels = array(
            'name'                  => _x( 'Destinos Transfer', 'Post type general name', 'wptb' ),
            'singular_name'         => _x( 'Destino Transfer', 'Post type singular name', 'wptb' ),
            'menu_name'             => _x( 'Destinos', 'Admin Menu text', 'wptb' ),
            'name_admin_bar'        => _x( 'Destino Transfer', 'Add New on Toolbar', 'wptb' ),
            'add_new'               => __( 'Añadir Nuevo', 'wptb' ),
            'add_new_item'          => __( 'Añadir Nuevo Destino', 'wptb' ),
            'new_item'              => __( 'Nuevo Destino', 'wptb' ),
            'edit_item'             => __( 'Editar Destino', 'wptb' ),
            'view_item'             => __( 'Ver Destino', 'wptb' ),
            'all_items'             => __( 'Todos los Destinos', 'wptb' ),
            'search_items'          => __( 'Buscar Destinos', 'wptb' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // Handled by custom menu in WPTB_Admin
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'wptb_destination' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'custom-fields' ),
        );

        register_post_type( 'wptb_destination', $args );
    }

    private function register_hotel_partners() {
        $labels = array(
            'name'               => 'Hoteles Partners',
            'singular_name'      => 'Hotel Partner',
            'menu_name'          => 'Hoteles Partners',
            'name_admin_bar'     => 'Hotel Partner',
            'add_new'            => 'Añadir Nuevo',
            'add_new_item'       => 'Añadir Nuevo Hotel',
            'new_item'           => 'Nuevo Hotel',
            'edit_item'          => 'Editar Hotel',
            'view_item'          => 'Ver Hotel',
            'all_items'          => 'Todos los Hoteles',
            'search_items'       => 'Buscar Hoteles',
            'not_found'          => 'No se encontraron hoteles.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // Handled by custom menu in Hotel Admin
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'supports'           => array( 'title', 'custom-fields' ),
        );

        register_post_type( 'hotel_partner', $args );
    }
}