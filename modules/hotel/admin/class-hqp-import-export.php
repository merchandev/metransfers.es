<?php

class HQP_Import_Export {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_submenu' ) );
        add_action( 'admin_post_hqp_export_hotels', array( $this, 'process_export' ) );
        add_action( 'admin_post_hqp_import_hotels', array( $this, 'process_import' ) );
    }

    public function add_submenu() {
        add_submenu_page(
            'edit.php?post_type=hotel_partner',
            'Importar/Exportar',
            'Importar/Exportar',
            'manage_options',
            'hqp-import-export',
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1>Importar / Exportar Hoteles</h1>

            <?php
            if ( isset( $_GET['imported'] ) && $_GET['imported'] == 1 ) {
                echo '<div class="notice notice-success is-dismissible"><p>Hoteles importados correctamente.</p></div>';
            }
            if ( isset( $_GET['import_error'] ) && $_GET['import_error'] == 1 ) {
                echo '<div class="notice notice-error is-dismissible"><p>Error al importar el archivo. Verifica el formato.</p></div>';
            }
            ?>

            <div style="display:flex; gap: 20px; margin-top: 20px;">
                <!-- Exportar -->
                <div class="card" style="max-width: 400px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h2>Exportar Hoteles</h2>
                    <p>Exporta todos los hoteles (incluyendo tokens y precios) en un archivo JSON. Este archivo puedes importarlo en otra instalación sin perder los QRs.</p>
                    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                        <input type="hidden" name="action" value="hqp_export_hotels">
                        <?php wp_nonce_field( 'hqp_export_hotels_nonce', 'hqp_export_nonce' ); ?>
                        <p>
                            <button type="submit" class="button button-primary">Descargar JSON de Hoteles</button>
                        </p>
                    </form>
                </div>

                <!-- Importar -->
                <div class="card" style="max-width: 400px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h2>Importar Hoteles</h2>
                    <p>Sube un archivo JSON previamente exportado. Si el hotel (según su token) ya existe, se actualizará; si no existe, se creará uno nuevo.</p>
                    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="hqp_import_hotels">
                        <?php wp_nonce_field( 'hqp_import_hotels_nonce', 'hqp_import_nonce' ); ?>
                        <p>
                            <input type="file" name="hqp_import_file" accept=".json" required>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">Importar JSON</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function process_export() {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['hqp_export_nonce'] ) || ! wp_verify_nonce( $_POST['hqp_export_nonce'], 'hqp_export_hotels_nonce' ) ) {
            wp_die( 'No autorizado' );
        }

        $hotels = get_posts( array(
            'post_type' => 'hotel_partner',
            'numberposts' => -1,
            'post_status' => 'any'
        ) );

        $export_data = array();

        foreach ( $hotels as $hotel ) {
            $meta = get_post_meta( $hotel->ID );
            
            // Cleanup meta formatting
            $clean_meta = array();
            foreach ( $meta as $k => $v ) {
                if ( strpos( $k, '_hqp_' ) === 0 ) {
                    $clean_meta[$k] = $v[0];
                }
            }

            $export_data[] = array(
                'post_title' => $hotel->post_title,
                'post_name'  => $hotel->post_name, // slug
                'post_status' => $hotel->post_status,
                'meta'       => $clean_meta
            );
        }

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename="hqp-hotels-export-' . date('Y-m-d') . '.json"' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );

        echo json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        exit;
    }

    public function process_import() {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['hqp_import_nonce'] ) || ! wp_verify_nonce( $_POST['hqp_import_nonce'], 'hqp_import_hotels_nonce' ) ) {
            wp_die( 'No autorizado' );
        }

        if ( empty( $_FILES['hqp_import_file']['tmp_name'] ) ) {
            wp_redirect( admin_url( 'edit.php?post_type=hotel_partner&page=hqp-import-export&import_error=1' ) );
            exit;
        }

        $file_content = file_get_contents( $_FILES['hqp_import_file']['tmp_name'] );
        $hotels_data = json_decode( $file_content, true );

        if ( ! is_array( $hotels_data ) ) {
            wp_redirect( admin_url( 'edit.php?post_type=hotel_partner&page=hqp-import-export&import_error=1' ) );
            exit;
        }

        foreach ( $hotels_data as $data ) {
            $token = isset( $data['meta']['_hqp_token'] ) ? $data['meta']['_hqp_token'] : '';

            $existing_post_id = 0;
            
            if ( $token ) {
                // Check if a hotel with this token already exists
                $existing_posts = get_posts( array(
                    'post_type' => 'hotel_partner',
                    'meta_key' => '_hqp_token',
                    'meta_value' => $token,
                    'numberposts' => 1,
                    'post_status' => 'any'
                ) );
                if ( ! empty( $existing_posts ) ) {
                    $existing_post_id = $existing_posts[0]->ID;
                }
            }

            $post_args = array(
                'post_title' => sanitize_text_field( $data['post_title'] ),
                'post_type' => 'hotel_partner',
                'post_status' => sanitize_text_field( $data['post_status'] ) ?: 'publish'
            );

            // Set slug if provided and we're creating new
            if ( ! empty( $data['post_name'] ) && ! $existing_post_id ) {
                $post_args['post_name'] = sanitize_title( $data['post_name'] );
            }

            if ( $existing_post_id ) {
                $post_args['ID'] = $existing_post_id;
                $post_id = wp_update_post( $post_args );
            } else {
                $post_id = wp_insert_post( $post_args );
            }

            if ( $post_id && ! is_wp_error( $post_id ) && isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
                foreach ( $data['meta'] as $meta_key => $meta_value ) {
                    update_post_meta( $post_id, sanitize_text_field( $meta_key ), sanitize_text_field( $meta_value ) );
                }
            }
        }

        wp_redirect( admin_url( 'edit.php?post_type=hotel_partner&page=hqp-import-export&imported=1' ) );
        exit;
    }
}
