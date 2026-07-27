<?php
/**
 * Herramienta nativa para construir, publicar y completar las rutas de la Fase 1.
 * Integrado como funcionalidad del tema en lugar de un plugin externo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Añadir submenú bajo el CPT 'ruta'
add_action( 'admin_menu', 'mt_add_route_builder_menu' );
function mt_add_route_builder_menu() {
    add_submenu_page(
        'edit.php?post_type=ruta',
        'Constructor de Rutas',
        'Constructor de Rutas',
        'manage_options',
        'mt-route-builder',
        'mt_render_route_builder_page'
    );
}

// 2. Renderizar la página de administración
function mt_render_route_builder_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'No tienes permisos para acceder a esta página.' );
    }

    $action_triggered = false;

    if ( isset( $_POST['mt_build_routes'] ) && check_admin_referer( 'mt_build_routes_action', 'mt_build_routes_nonce' ) ) {
        mt_execute_route_builder();
        $action_triggered = true;
    }

    ?>
    <div class="wrap">
        <h1>Constructor de Rutas MeTransfers</h1>
        <p>Esta herramienta crea, publica y rellena automáticamente los datos SEO (origen, destino, etc.) de las 13 rutas de la Fase 1.</p>
        
        <?php if ( $action_triggered ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>¡Éxito!</strong> Las rutas han sido creadas/actualizadas, publicadas y las reglas de enlaces permanentes han sido regeneradas.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'mt_build_routes_action', 'mt_build_routes_nonce' ); ?>
            <p>
                <button type="submit" name="mt_build_routes" class="button button-primary button-hero">
                    Crear, completar y publicar las 13 rutas
                </button>
            </p>
        </form>

        <h2>Estado de las Rutas</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Ruta</th>
                    <th>Slug</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rutas_esperadas = mt_get_phase1_routes();
                foreach ( $rutas_esperadas as $slug => $title ) {
                    $page = get_page_by_path( $slug, OBJECT, 'ruta' );
                    if ( ! $page ) {
                        // Comprobar también en papelera o borradores
                        $query = new WP_Query( array(
                            'name'        => $slug,
                            'post_type'   => 'ruta',
                            'post_status' => 'any',
                            'posts_per_page' => 1
                        ) );
                        if ( $query->have_posts() ) {
                            $page = $query->posts[0];
                        }
                    }

                    if ( $page ) {
                        $status = $page->post_status;
                        $color = $status === 'publish' ? 'green' : 'red';
                        echo '<tr>';
                        echo '<td><strong>' . esc_html( $title ) . '</strong></td>';
                        echo '<td><code>' . esc_html( $slug ) . '</code></td>';
                        echo '<td><span style="color:' . $color . '; font-weight:bold;">' . esc_html( $status ) . '</span></td>';
                        echo '<td><a href="' . get_permalink( $page->ID ) . '" target="_blank">Ver ruta</a></td>';
                        echo '</tr>';
                    } else {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html( $title ) . '</strong></td>';
                        echo '<td><code>' . esc_html( $slug ) . '</code></td>';
                        echo '<td><span style="color:red; font-weight:bold;">No existe</span></td>';
                        echo '<td>-</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// 3. Lógica de ejecución
function mt_get_phase1_routes() {
    return array(
        'aeropuerto-barcelona-lloret-de-mar' => 'Aeropuerto de Barcelona–Lloret de Mar',
        'aeropuerto-girona-lloret-de-mar'    => 'Aeropuerto de Girona–Lloret de Mar',
        'barcelona-lloret-de-mar'            => 'Barcelona centro–Lloret de Mar',
        'puerto-barcelona-lloret-de-mar'     => 'Puerto de Barcelona–Lloret de Mar',
        'estacion-sants-lloret-de-mar'       => 'Estación de Sants–Lloret de Mar',
        
        'aeropuerto-barcelona-salou'         => 'Aeropuerto de Barcelona–Salou',
        'aeropuerto-reus-salou'              => 'Aeropuerto de Reus–Salou',
        'barcelona-salou'                    => 'Barcelona centro–Salou',
        'puerto-barcelona-salou'             => 'Puerto de Barcelona–Salou',
        'estacion-sants-salou'               => 'Estación de Sants–Salou',
        
        'aeropuerto-barcelona-portaventura'  => 'Aeropuerto de Barcelona–PortAventura',
        'aeropuerto-reus-portaventura'       => 'Aeropuerto de Reus–PortAventura',
        'salou-portaventura'                 => 'Salou–PortAventura',
    );
}

function mt_execute_route_builder() {
    $rutas = mt_get_phase1_routes();

    foreach ( $rutas as $slug => $title ) {
        // Buscar por slug en cualquier estado
        $query = new WP_Query( array(
            'name'           => $slug,
            'post_type'      => 'ruta',
            'post_status'    => 'any',
            'posts_per_page' => 1
        ) );

        $parts = explode( '–', $title );
        $origen = isset( $parts[0] ) ? trim( $parts[0] ) : '';
        $destino = isset( $parts[1] ) ? trim( $parts[1] ) : '';

        $post_data = array(
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_status'  => 'publish', // Forzar siempre publicación
            'post_type'    => 'ruta',
        );

        $post_id = 0;

        if ( $query->have_posts() ) {
            // Actualizar existente
            $page = $query->posts[0];
            $post_data['ID'] = $page->ID;
            $post_id = wp_update_post( $post_data );
        } else {
            // Crear nueva
            $post_id = wp_insert_post( $post_data );
        }

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_mt_ruta_origen', $origen );
            update_post_meta( $post_id, '_mt_ruta_destino', $destino );
            update_post_meta( $post_id, '_mt_ruta_duracion', '60 min' );
            update_post_meta( $post_id, '_mt_ruta_pax', '1-8' );
            update_post_meta( $post_id, '_mt_ruta_maletas', '8' );
        }
    }

    // Flush rewrite rules para asentar el CPT y los slugs
    flush_rewrite_rules();
}
