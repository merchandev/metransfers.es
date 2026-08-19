<?php
// Register Custom Post Type: Destinos
function wptb_register_destinations_cpt() {
    $labels = array(
        'name'                  => _x( 'Destinos', 'Post Type General Name', 'wp-transfer-booking' ),
        'singular_name'         => _x( 'Destino', 'Post Type Singular Name', 'wp-transfer-booking' ),
        'menu_name'             => __( 'Destinos', 'wp-transfer-booking' ),
        'name_admin_bar'        => __( 'Destino', 'wp-transfer-booking' ),
        'archives'              => __( 'Archivo de Destinos', 'wp-transfer-booking' ),
        'attributes'            => __( 'Atributos de Destino', 'wp-transfer-booking' ),
        'parent_item_colon'     => __( 'Destino Padre:', 'wp-transfer-booking' ),
        'all_items'             => __( 'Todos los Destinos', 'wp-transfer-booking' ),
        'add_new_item'          => __( 'Añadir Nuevo Destino', 'wp-transfer-booking' ),
        'add_new'               => __( 'Añadir Nuevo', 'wp-transfer-booking' ),
        'new_item'              => __( 'Nuevo Destino', 'wp-transfer-booking' ),
        'edit_item'             => __( 'Editar Destino', 'wp-transfer-booking' ),
        'update_item'           => __( 'Actualizar Destino', 'wp-transfer-booking' ),
        'view_item'             => __( 'Ver Destino', 'wp-transfer-booking' ),
        'view_items'            => __( 'Ver Destinos', 'wp-transfer-booking' ),
        'search_items'          => __( 'Buscar Destino', 'wp-transfer-booking' ),
        'not_found'             => __( 'No encontrado', 'wp-transfer-booking' ),
        'not_found_in_trash'    => __( 'No encontrado en Papelera', 'wp-transfer-booking' ),
        'featured_image'        => __( 'Imagen Destacada (Foto del Destino)', 'wp-transfer-booking' ),
        'set_featured_image'    => __( 'Establecer imagen destacada', 'wp-transfer-booking' ),
        'remove_featured_image' => __( 'Quitar imagen destacada', 'wp-transfer-booking' ),
        'use_featured_image'    => __( 'Usar como imagen destacada', 'wp-transfer-booking' ),
        'insert_into_item'      => __( 'Insertar en el destino', 'wp-transfer-booking' ),
        'uploaded_to_this_item' => __( 'Subido a este destino', 'wp-transfer-booking' ),
        'items_list'            => __( 'Lista de destinos', 'wp-transfer-booking' ),
        'items_list_navigation' => __( 'Navegación de lista de destinos', 'wp-transfer-booking' ),
        'filter_items_list'     => __( 'Filtrar lista de destinos', 'wp-transfer-booking' ),
    );
    $args = array(
        'label'                 => __( 'Destino', 'wp-transfer-booking' ),
        'description'           => __( 'Destinos populares para el carrusel', 'wp-transfer-booking' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'thumbnail', 'editor', 'page-attributes' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => 'wptb-bookings', // Parent slug from class-wptb-admin.php
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-location',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
    );
    register_post_type( 'wptb_destination', $args );
}
add_action( 'init', 'wptb_register_destinations_cpt', 0 );

// One-time Import Script for Destinations
// Can be run by visiting: WP-ADMIN -> ?wptb_import_destinations=1
function wptb_run_destinations_import() {
    if ( ! isset( $_GET['wptb_import_destinations'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $destinations = [
        'Barcelona', 'Pineda de Mar', 'PortAventura', 'Barcelona Reus', 'Madrid Barcelona', 
        'Sevilla', 'Vigo Barcelona', 'Benidorm', 'Bilbao Barcelona', 'San Sebastian', 
        'Malgrat', 'Andorra', 'Granada Barcelona', 'Valencia', 'Lloret de Mar', 
        'Santiago de Compostela', 'Lourdes', 'Girona', 'Barcelona Cambrils', 'Calella', 
        'Vall de Nuria', 'Barcelona Sitges', 'Almeria', 'Figueres Barcelona', 'Perpignan', 
        'Tarragona', 'Barcelona Salou', 'Camping El Delfin Verde', 'Roses Barcelona', 
        'Marbella', 'Barcelona Cadaques', 'Santa Susanna', 'Costa Brava', 'Begur', 
        'Calella de Palafrugell', 'Cap de Creus', 'La Escala', 'Tossa de Mar', 
        'Barcelona Baqueira Beret', 'Palamos'
    ];

    $count = 0;
    foreach ( $destinations as $dest_name ) {
        // Check if exists
        $existing = get_page_by_title( $dest_name, OBJECT, 'wptb_destination' );
        if ( ! $existing ) {
            $post_id = wp_insert_post( array(
                'post_title'  => $dest_name,
                'post_type'   => 'wptb_destination',
                'post_status' => 'publish',
            ) );
            if ( $post_id ) {
                $count++;
            }
        }
    }

    echo "<div class='notice notice-success is-dismissible'><p><strong>Importación completada:</strong> se han añadido $count nuevos destinos. <a href='edit.php?post_type=wptb_destination'>Ver Destinos</a></p></div>";
}
add_action( 'admin_init', 'wptb_run_destinations_import' );
