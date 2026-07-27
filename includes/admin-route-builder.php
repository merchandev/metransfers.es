<?php
/**
 * Herramienta nativa para construir, publicar y completar las rutas de la Fase 1.
 * Integrado como funcionalidad del tema en lugar de un plugin externo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 0. Auto-ejecución transparente para garantizar que las rutas existan
add_action( 'init', 'mt_auto_build_routes_once' );
function mt_auto_build_routes_once() {
    // Clave versionada: incrementar cuando se añadan rutas nuevas
    if ( ! get_transient( 'mt_auto_built_routes_v4_expanded' ) ) {
        if ( function_exists( 'mt_execute_route_builder' ) ) {
            mt_execute_route_builder();
            set_transient( 'mt_auto_built_routes_v4_expanded', true, YEAR_IN_SECONDS );
            // Limpiar caché de Yoast SEO tras crear las rutas
            mt_clear_yoast_sitemap_cache();
        }
    }
}

// 0b. Integración con Yoast SEO ─────────────────────────────────────────────

/**
 * Asegurar que el CPT 'ruta' está incluido en el sitemap de Yoast.
 * Necesario porque Yoast a veces excluye CPTs registrados después de su init.
 */
add_filter( 'wpseo_sitemap_exclude_post_type', 'mt_yoast_include_ruta_cpt', 10, 2 );
function mt_yoast_include_ruta_cpt( $exclude, $post_type ) {
    if ( 'ruta' === $post_type ) {
        return false; // nunca excluir
    }
    return $exclude;
}

/**
 * Forzar que las rutas sin imagen se muestren en el sitemap
 * (Yoast puede omitir posts sin featured image en ciertos modos).
 */
add_filter( 'wpseo_sitemap_entry', 'mt_yoast_ruta_sitemap_entry', 10, 3 );
function mt_yoast_ruta_sitemap_entry( $url, $type, $object ) {
    if ( 'ruta' === get_post_type( $object ) ) {
        // Forzar prioridad y frecuencia para todas las rutas
        $url['pri']  = '0.8';
        $url['chf']  = 'weekly';
    }
    return $url;
}

/**
 * Limpiar la caché del sitemap de Yoast SEO.
 * Se llama tras crear o actualizar rutas.
 */
function mt_clear_yoast_sitemap_cache() {
    if ( class_exists( 'WPSEO_Sitemaps_Router' ) ) {
        WPSEO_Sitemaps_Router::invalidate_sitemap();
    }
    // Método alternativo para versiones anteriores de Yoast
    if ( function_exists( 'wpseo_invalidate_sitemap_cache' ) ) {
        wpseo_invalidate_sitemap_cache( 'ruta' );
    }
    // Fallback: borrar directamente las opciones de caché de Yoast
    delete_option( 'wpseo_sitemap_cache_validator_all' );
    delete_transient( 'wpseo_sitemap_cache_validator_all' );
    // Flush rewrite rules para que el sitemap reaparezca
    delete_option( 'rewrite_rules' );
}

/**
 * Limpiar caché de Yoast automáticamente cuando se publica una ruta.
 */
add_action( 'save_post_ruta', 'mt_on_ruta_saved', 10, 1 );
function mt_on_ruta_saved( $post_id ) {
    if ( wp_is_post_revision( $post_id ) ) return;
    mt_clear_yoast_sitemap_cache();
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
    $cache_cleared    = false;

    if ( isset( $_POST['mt_build_routes'] ) && check_admin_referer( 'mt_build_routes_action', 'mt_build_routes_nonce' ) ) {
        mt_execute_route_builder();
        mt_clear_yoast_sitemap_cache();
        $action_triggered = true;
    }

    if ( isset( $_POST['mt_clear_sitemap_cache'] ) && check_admin_referer( 'mt_build_routes_action', 'mt_build_routes_nonce' ) ) {
        mt_clear_yoast_sitemap_cache();
        // Forzar flush de rewrite rules
        flush_rewrite_rules( true );
        $cache_cleared = true;
    }

    ?>
    <div class="wrap">
        <h1>Constructor de Rutas MeTransfers</h1>
        <p>Esta herramienta crea, publica y rellena automáticamente los datos SEO de las <?php echo count( mt_get_phase1_routes() ); ?> rutas del catálogo.</p>

        <?php if ( $action_triggered ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>✅ ¡Éxito!</strong> Las rutas han sido creadas/actualizadas, publicadas. La caché de Yoast SEO ha sido limpiada. Visita <a href="<?php echo home_url('/ruta-sitemap.xml'); ?>" target="_blank">ruta-sitemap.xml</a> para confirmar que muestra las rutas.</p>
            </div>
        <?php endif; ?>

        <?php if ( $cache_cleared ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>✅ Caché limpiada.</strong> La caché del sitemap de Yoast ha sido regenerada y los rewrite rules han sido actualizados. Ahora puedes ir a <a href="https://search.google.com/search-console/sitemaps" target="_blank">Google Search Console</a> y solicitar la reinspección del sitemap <code>/ruta-sitemap.xml</code>.</p>
            </div>
        <?php endif; ?>

        <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
            <form method="post" action="" style="display:inline;">
                <?php wp_nonce_field( 'mt_build_routes_action', 'mt_build_routes_nonce' ); ?>
                <button type="submit" name="mt_build_routes" class="button button-primary button-hero">
                    🚀 Crear, completar y publicar las <?php echo count( mt_get_phase1_routes() ); ?> rutas
                </button>
            </form>

            <form method="post" action="" style="display:inline;">
                <?php wp_nonce_field( 'mt_build_routes_action', 'mt_build_routes_nonce' ); ?>
                <button type="submit" name="mt_clear_sitemap_cache" class="button button-secondary button-hero">
                    🗺️ Limpiar caché Yoast + Flush rewrite rules
                </button>
            </form>

            <a href="<?php echo esc_url( home_url( '/ruta-sitemap.xml' ) ); ?>" target="_blank" class="button button-hero" style="line-height:2.4;">
                🔍 Ver sitemap de rutas
            </a>
        </div>

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

// 3. Catálogo completo de rutas
function mt_get_phase1_routes() {
    return array(

        // ── FASE 1: Lloret de Mar ───────────────────────────────────
        'aeropuerto-barcelona-lloret-de-mar'   => 'Aeropuerto de Barcelona–Lloret de Mar',
        'aeropuerto-girona-lloret-de-mar'      => 'Aeropuerto de Girona–Lloret de Mar',
        'barcelona-lloret-de-mar'              => 'Barcelona centro–Lloret de Mar',
        'puerto-barcelona-lloret-de-mar'       => 'Puerto de Barcelona–Lloret de Mar',
        'estacion-sants-lloret-de-mar'         => 'Estación de Sants–Lloret de Mar',

        // ── FASE 1: Salou ───────────────────────────────────────────
        'aeropuerto-barcelona-salou'           => 'Aeropuerto de Barcelona–Salou',
        'aeropuerto-reus-salou'                => 'Aeropuerto de Reus–Salou',
        'barcelona-salou'                      => 'Barcelona centro–Salou',
        'puerto-barcelona-salou'               => 'Puerto de Barcelona–Salou',
        'estacion-sants-salou'                 => 'Estación de Sants–Salou',

        // ── FASE 1: PortAventura ────────────────────────────────────
        'aeropuerto-barcelona-portaventura'    => 'Aeropuerto de Barcelona–PortAventura',
        'aeropuerto-reus-portaventura'         => 'Aeropuerto de Reus–PortAventura',
        'salou-portaventura'                   => 'Salou–PortAventura',

        // ── FASE 2: Sitges ──────────────────────────────────────────
        'aeropuerto-barcelona-sitges'          => 'Aeropuerto de Barcelona–Sitges',
        'barcelona-sitges'                     => 'Barcelona centro–Sitges',
        'puerto-barcelona-sitges'              => 'Puerto de Barcelona–Sitges',
        'estacion-sants-sitges'                => 'Estación de Sants–Sitges',

        // ── FASE 2: Tarragona ───────────────────────────────────────
        'aeropuerto-barcelona-tarragona'       => 'Aeropuerto de Barcelona–Tarragona',
        'barcelona-tarragona'                  => 'Barcelona centro–Tarragona',
        'puerto-barcelona-tarragona'           => 'Puerto de Barcelona–Tarragona',
        'estacion-sants-tarragona'             => 'Estación de Sants–Tarragona',

        // ── FASE 2: Cambrils ────────────────────────────────────────
        'aeropuerto-barcelona-cambrils'        => 'Aeropuerto de Barcelona–Cambrils',
        'barcelona-cambrils'                   => 'Barcelona centro–Cambrils',
        'puerto-barcelona-cambrils'            => 'Puerto de Barcelona–Cambrils',

        // ── FASE 2: Girona ──────────────────────────────────────────
        'aeropuerto-barcelona-girona'          => 'Aeropuerto de Barcelona–Girona',
        'barcelona-girona'                     => 'Barcelona centro–Girona',
        'estacion-sants-girona'                => 'Estación de Sants–Girona',

        // ── FASE 2: Costa Brava Norte ───────────────────────────────
        'aeropuerto-barcelona-tossa-de-mar'    => 'Aeropuerto de Barcelona–Tossa de Mar',
        'barcelona-tossa-de-mar'               => 'Barcelona centro–Tossa de Mar',
        'aeropuerto-barcelona-blanes'          => 'Aeropuerto de Barcelona–Blanes',
        'barcelona-blanes'                     => 'Barcelona centro–Blanes',
        'aeropuerto-barcelona-calella'         => 'Aeropuerto de Barcelona–Calella',
        'barcelona-calella'                    => 'Barcelona centro–Calella',
        'aeropuerto-barcelona-roses'           => 'Aeropuerto de Barcelona–Roses',
        'barcelona-roses'                      => 'Barcelona centro–Roses',
        'aeropuerto-barcelona-cadaques'        => 'Aeropuerto de Barcelona–Cadaqués',
        'barcelona-cadaques'                   => 'Barcelona centro–Cadaqués',
        'aeropuerto-barcelona-platja-daro'     => 'Aeropuerto de Barcelona–Platja d\'Aro',
        'barcelona-platja-daro'                => 'Barcelona centro–Platja d\'Aro',

        // ── FASE 2: Andorra ─────────────────────────────────────────
        'aeropuerto-barcelona-andorra'         => 'Aeropuerto de Barcelona–Andorra la Vella',
        'barcelona-andorra'                    => 'Barcelona centro–Andorra la Vella',
        'estacion-sants-andorra'               => 'Estación de Sants–Andorra la Vella',

        // ── FASE 2: Montserrat ──────────────────────────────────────
        'aeropuerto-barcelona-montserrat'      => 'Aeropuerto de Barcelona–Montserrat',
        'barcelona-montserrat'                 => 'Barcelona centro–Montserrat',

        // ── FASE 2: Reus / Aeropuerto de Reus ──────────────────────
        'aeropuerto-barcelona-reus'            => 'Aeropuerto de Barcelona–Reus',
        'barcelona-reus'                       => 'Barcelona centro–Reus',

        // ── FASE 2: Otras Costa Daurada ─────────────────────────────
        'aeropuerto-barcelona-vilanova'        => 'Aeropuerto de Barcelona–Vilanova i la Geltrú',
        'barcelona-vilanova'                   => 'Barcelona centro–Vilanova i la Geltrú',
        'aeropuerto-barcelona-calafell'        => 'Aeropuerto de Barcelona–Calafell',
        'barcelona-calafell'                   => 'Barcelona centro–Calafell',
        'aeropuerto-barcelona-la-pineda'       => 'Aeropuerto de Barcelona–La Pineda',
        'barcelona-la-pineda'                  => 'Barcelona centro–La Pineda',

        // ── FASE 2: Pirineos / Nieve ────────────────────────────────
        'aeropuerto-barcelona-la-molina'       => 'Aeropuerto de Barcelona–La Molina',
        'barcelona-la-molina'                  => 'Barcelona centro–La Molina',
        'aeropuerto-barcelona-baqueira-beret'  => 'Aeropuerto de Barcelona–Baqueira Beret',
        'barcelona-baqueira-beret'             => 'Barcelona centro–Baqueira Beret',
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
            'post_content' => '',
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
