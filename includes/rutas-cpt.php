<?php
/**
 * Registro del Custom Post Type: Rutas
 * y Meta Boxes asociados para la estrategia SEO.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Registrar CPT Rutas
function mt_register_ruta_cpt() {
    $labels = array(
        'name'                  => _x( 'Rutas', 'Post Type General Name', 'me-transfers' ),
        'singular_name'         => _x( 'Ruta', 'Post Type Singular Name', 'me-transfers' ),
        'menu_name'             => __( 'Rutas (SEO)', 'me-transfers' ),
        'name_admin_bar'        => __( 'Ruta', 'me-transfers' ),
        'archives'              => __( 'Archivo de Rutas', 'me-transfers' ),
        'attributes'            => __( 'Atributos de Ruta', 'me-transfers' ),
        'parent_item_colon'     => __( 'Ruta Padre:', 'me-transfers' ),
        'all_items'             => __( 'Todas las Rutas', 'me-transfers' ),
        'add_new_item'          => __( 'Añadir Nueva Ruta', 'me-transfers' ),
        'add_new'               => __( 'Añadir Nueva', 'me-transfers' ),
        'new_item'              => __( 'Nueva Ruta', 'me-transfers' ),
        'edit_item'             => __( 'Editar Ruta', 'me-transfers' ),
        'update_item'           => __( 'Actualizar Ruta', 'me-transfers' ),
        'view_item'             => __( 'Ver Ruta', 'me-transfers' ),
        'view_items'            => __( 'Ver Rutas', 'me-transfers' ),
        'search_items'          => __( 'Buscar Ruta', 'me-transfers' ),
        'not_found'             => __( 'No encontrada', 'me-transfers' ),
        'not_found_in_trash'    => __( 'No encontrada en la papelera', 'me-transfers' ),
    );
    $args = array(
        'label'                 => __( 'Ruta', 'me-transfers' ),
        'description'           => __( 'Landing pages para rutas comerciales', 'me-transfers' ),
        'labels'                => $labels,
        'supports'              => array(
            'title',
            'editor',
            'thumbnail',
            'page-attributes',
            'custom-fields',
        ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'show_in_rest'          => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,

        'has_archive'           => 'rutas',

        'rewrite'               => array(
            'slug'       => 'rutas',
            'with_front' => false,
        ),

        'capability_type'       => 'page',
    );
    register_post_type( 'ruta', $args );
}
add_action( 'init', 'mt_register_ruta_cpt' );

// 2. Registrar Meta Boxes
function mt_ruta_add_meta_boxes() {
    add_meta_box(
        'mt_ruta_details',
        __( 'Detalles de la Ruta', 'me-transfers' ),
        'mt_ruta_details_callback',
        'ruta',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'mt_ruta_add_meta_boxes' );

function mt_ruta_details_callback( $post ) {
    wp_nonce_field( 'mt_ruta_save_meta_box_data', 'mt_ruta_meta_box_nonce' );

    $origen = get_post_meta( $post->ID, '_mt_ruta_origen', true );
    $destino = get_post_meta( $post->ID, '_mt_ruta_destino', true );
    $duracion = get_post_meta( $post->ID, '_mt_ruta_duracion', true );
    $pax = get_post_meta( $post->ID, '_mt_ruta_pax', true );
    $maletas = get_post_meta( $post->ID, '_mt_ruta_maletas', true );
    $precio = get_post_meta( $post->ID, '_mt_ruta_precio', true );
    $h1_seo = get_post_meta( $post->ID, '_mt_ruta_h1', true );

    $seo_ready = get_post_meta( $post->ID, '_mt_seo_ready', true );

    echo '<table class="form-table">';
    echo '<tr><th><label for="mt_ruta_h1">H1 SEO (Opcional)</label></th>';
    echo '<td><input type="text" id="mt_ruta_h1" name="mt_ruta_h1" value="' . esc_attr( $h1_seo ) . '" size="40" placeholder="Ej. Taxis desde Barcelona a Sitges" /><br><small>Si se deja en blanco, se usará el título del post.</small></td></tr>';

    echo '<tr><th><label for="mt_ruta_origen">Origen</label></th>';
    echo '<td><input type="text" id="mt_ruta_origen" name="mt_ruta_origen" value="' . esc_attr( $origen ) . '" size="25" placeholder="Ej. Aeropuerto BCN" /></td></tr>';
    
    echo '<tr><th><label for="mt_ruta_destino">Destino</label></th>';
    echo '<td><input type="text" id="mt_ruta_destino" name="mt_ruta_destino" value="' . esc_attr( $destino ) . '" size="25" placeholder="Ej. Centro Ciudad" /></td></tr>';
    
    echo '<tr><th><label for="mt_ruta_duracion">Duración Estimada</label></th>';
    echo '<td><input type="text" id="mt_ruta_duracion" name="mt_ruta_duracion" value="' . esc_attr( $duracion ) . '" size="25" placeholder="Ej. 25 min" /></td></tr>';
    
    echo '<tr><th><label for="mt_ruta_pax">Max Pasajeros</label></th>';
    echo '<td><input type="text" id="mt_ruta_pax" name="mt_ruta_pax" value="' . esc_attr( $pax ) . '" size="25" placeholder="Ej. 7" /></td></tr>';
    
    echo '<tr><th><label for="mt_ruta_maletas">Max Maletas</label></th>';
    echo '<td><input type="text" id="mt_ruta_maletas" name="mt_ruta_maletas" value="' . esc_attr( $maletas ) . '" size="25" placeholder="Ej. 7" /></td></tr>';

    echo '<tr><th><label for="mt_ruta_precio">Precio Fijo (Desde)</label></th>';
    echo '<td><input type="text" id="mt_ruta_precio" name="mt_ruta_precio" value="' . esc_attr( $precio ) . '" size="25" placeholder="Ej. 65" /> €</td></tr>';

    // Campo SEO Ready
    echo '<tr style="border-top: 2px solid #0073aa; background: #f0f6fc;">';
    echo '<th><label for="mt_ruta_seo_ready"><strong>✅ Lista para SEO</strong></label></th>';
    echo '<td>';
    echo '<label><input type="checkbox" id="mt_ruta_seo_ready" name="mt_ruta_seo_ready" value="1"' . checked( $seo_ready, '1', false ) . ' /> ';
    echo '<strong>Marcar esta ruta como lista para indexación</strong></label>';
    echo '<br><small style="color:#555;">Cuando está marcada, la ruta se indexa aunque no tenga precio. Úsaóla solo cuando la ruta tenga: origen, destino, precio, contenido único, FAQ específica, H1, title y meta description.</small>';
    echo '</td></tr>';

    echo '</table>';
}

function mt_ruta_save_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['mt_ruta_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['mt_ruta_meta_box_nonce'], 'mt_ruta_save_meta_box_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array( 'h1', 'origen', 'destino', 'duracion', 'pax', 'maletas', 'precio' );
    foreach ( $fields as $field ) {
        if ( isset( $_POST["mt_ruta_$field"] ) ) {
            update_post_meta( $post_id, "_mt_ruta_$field", sanitize_text_field( $_POST["mt_ruta_$field"] ) );
        }
    }

    // Campo SEO Ready: checkbox (guardado como '1' o eliminado si no está marcado)
    if ( isset( $_POST['mt_ruta_seo_ready'] ) && '1' === $_POST['mt_ruta_seo_ready'] ) {
        update_post_meta( $post_id, '_mt_seo_ready', '1' );
    } else {
        delete_post_meta( $post_id, '_mt_seo_ready' );
    }
}
add_action( 'save_post', 'mt_ruta_save_meta_box_data' );

// 3. Shortcode para enlaces estructurados en el Blog
// Uso: [ruta_enlace id="123" texto="Reserva tu traslado al Aeropuerto"]
function mt_ruta_enlace_shortcode( $atts ) {
    $a = shortcode_atts( array(
        'id' => '',
        'texto' => 'Reservar este traslado'
    ), $atts );

    if ( empty( $a['id'] ) ) {
        return '';
    }

    $link = get_permalink( intval( $a['id'] ) );
    if ( ! $link ) return '';

    return '<a href="' . esc_url( $link ) . '" class="btn btn-primary btn-ruta-blog" style="margin: 15px 0; display: inline-flex; align-items: center; gap: 8px;">' . esc_html( $a['texto'] ) . ' <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>';
}
add_shortcode( 'ruta_enlace', 'mt_ruta_enlace_shortcode' );
