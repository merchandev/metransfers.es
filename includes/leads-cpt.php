<?php
/**
 * Registro del Custom Post Type: Mensajes Web
 * Para guardar los leads del formulario de contacto y WhatsApp.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Registrar CPT Mensajes
function mt_register_mensaje_cpt() {
    $labels = array(
        'name'                  => _x( 'Mensajes Web', 'Post Type General Name', 'me-transfers' ),
        'singular_name'         => _x( 'Mensaje', 'Post Type Singular Name', 'me-transfers' ),
        'menu_name'             => __( 'Mensajes Web', 'me-transfers' ),
        'all_items'             => __( 'Todos los Mensajes', 'me-transfers' ),
        'view_item'             => __( 'Ver Mensaje', 'me-transfers' ),
        'search_items'          => __( 'Buscar Mensaje', 'me-transfers' ),
        'not_found'             => __( 'No encontrado', 'me-transfers' ),
        'not_found_in_trash'    => __( 'No encontrado en la papelera', 'me-transfers' ),
    );
    $args = array(
        'label'                 => __( 'Mensaje', 'me-transfers' ),
        'description'           => __( 'Leads y contactos recibidos desde la web', 'me-transfers' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'custom-fields' ), // No editor, we'll display everything in meta boxes
        'public'                => false, // Private CPT, not searchable on front-end
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 21,
        'menu_icon'             => 'dashicons-email-alt',
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        // Make it mostly read-only except for deleting
        'capabilities' => array(
            'create_posts' => 'do_not_allow', // Prevents manually creating new ones from admin
        ),
        'map_meta_cap' => true,
    );
    register_post_type( 'mensaje', $args );
}
add_action( 'init', 'mt_register_mensaje_cpt' );

// 2. Registrar Meta Boxes
function mt_mensaje_add_meta_boxes() {
    add_meta_box(
        'mt_mensaje_details',
        __( 'Detalles del Contacto', 'me-transfers' ),
        'mt_mensaje_details_callback',
        'mensaje',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'mt_mensaje_add_meta_boxes' );

function mt_mensaje_details_callback( $post ) {
    $origen = get_post_meta( $post->ID, '_mt_mensaje_origen', true ); // 'formulario' or 'whatsapp'
    $nombre = get_post_meta( $post->ID, '_mt_mensaje_nombre', true );
    $email = get_post_meta( $post->ID, '_mt_mensaje_email', true );
    $telefono = get_post_meta( $post->ID, '_mt_mensaje_telefono', true );
    $servicio = get_post_meta( $post->ID, '_mt_mensaje_servicio', true );
    $mensaje = get_post_meta( $post->ID, '_mt_mensaje_texto', true );

    echo '<table class="form-table">';
    
    echo '<tr><th><label>Origen del Lead</label></th>';
    echo '<td><strong>' . ( $origen === 'whatsapp' ? 'WhatsApp' : 'Formulario de Contacto' ) . '</strong></td></tr>';
    
    echo '<tr><th><label>Nombre</label></th>';
    echo '<td>' . esc_html( $nombre ) . '</td></tr>';
    
    if ( $email ) {
        echo '<tr><th><label>Email</label></th>';
        echo '<td><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></td></tr>';
    }
    
    if ( $telefono ) {
        echo '<tr><th><label>Teléfono</label></th>';
        echo '<td><a href="tel:' . esc_attr( str_replace(' ', '', $telefono) ) . '">' . esc_html( $telefono ) . '</a></td></tr>';
    }
    
    if ( $servicio ) {
        echo '<tr><th><label>Servicio</label></th>';
        echo '<td>' . esc_html( ucfirst( $servicio ) ) . '</td></tr>';
    }
    
    echo '<tr><th><label>Mensaje</label></th>';
    echo '<td><div style="background:#f9f9f9;padding:15px;border:1px solid #ccc;border-radius:4px;">' . nl2br( esc_html( $mensaje ) ) . '</div></td></tr>';
    
    echo '</table>';
}

// 3. Añadir columnas personalizadas al listado de Mensajes
function mt_mensaje_custom_columns( $columns ) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = __( 'Remitente', 'me-transfers' );
    $new_columns['origen'] = __( 'Origen', 'me-transfers' );
    $new_columns['email_tel'] = __( 'Email / Teléfono', 'me-transfers' );
    $new_columns['mensaje_texto'] = __( 'Mensaje', 'me-transfers' );
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter( 'manage_mensaje_posts_columns', 'mt_mensaje_custom_columns' );

function mt_mensaje_custom_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'origen':
            $origen = get_post_meta( $post_id, '_mt_mensaje_origen', true );
            if ( $origen === 'whatsapp' ) {
                echo '<span style="color:#25d366;font-weight:bold;">WhatsApp</span>';
            } else {
                echo '<span style="color:#075ea8;font-weight:bold;">Formulario</span>';
            }
            break;
        case 'email_tel':
            $email = get_post_meta( $post_id, '_mt_mensaje_email', true );
            $tel = get_post_meta( $post_id, '_mt_mensaje_telefono', true );
            if ( $email ) echo esc_html( $email ) . '<br>';
            if ( $tel ) echo esc_html( $tel );
            break;
        case 'mensaje_texto':
            $mensaje = get_post_meta( $post_id, '_mt_mensaje_texto', true );
            if ( $mensaje ) {
                $snippet = wp_trim_words( $mensaje, 10, '...' );
                echo esc_html( $snippet ) . '<br>';
                echo '<a href="' . get_edit_post_link( $post_id ) . '" class="button button-small button-primary" style="margin-top: 6px;">' . __( 'Ver Detalle', 'me-transfers' ) . '</a>';
            } else {
                echo '<em>' . __( 'Sin mensaje', 'me-transfers' ) . '</em><br>';
                echo '<a href="' . get_edit_post_link( $post_id ) . '" class="button button-small" style="margin-top: 6px;">' . __( 'Ver Info', 'me-transfers' ) . '</a>';
            }
            break;
    }
}
add_action( 'manage_mensaje_posts_custom_column', 'mt_mensaje_custom_column_content', 10, 2 );
