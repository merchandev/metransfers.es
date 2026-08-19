<?php

require_once HQP_PLUGIN_DIR . 'admin/class-hqp-vehicles-admin.php';

class HQP_Admin {

    public function register_hotel_cpt() {
        $labels = array(
            'name'                  => 'Hoteles',
            'singular_name'         => 'Hotel',
            'menu_name'             => 'Hoteles QR',
            'name_admin_bar'        => 'Hotel',
            'add_new'               => 'Añadir Nuevo',
            'add_new_item'          => 'Añadir Nuevo Hotel',
            'new_item'              => 'Nuevo Hotel',
            'edit_item'             => 'Editar Hotel',
            'view_item'             => 'Ver Hotel',
            'all_items'             => 'Todos los Hoteles',
            'search_items'          => 'Buscar Hoteles',
            'not_found'             => 'No encontrados',
            'not_found_in_trash'    => 'No encontrados en papelera',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true, // Habilitado para evitar conflictos con Gutenberg y publicación
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'hotel' ),
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'has_archive'        => false,
            'hierarchical'       => false,
            'show_in_rest'       => true,
            'menu_position'      => 56, // Below WooCommerce usually
            'menu_icon'          => 'dashicons-building',
            'supports'           => array( 'title', 'custom-fields' ),
        );

        register_post_type( 'hotel_partner', $args );
        
        // Highlight Men— Item
        add_action('admin_head', function() {
            echo '<style>
                #menu-posts-hotel_partner > a {
                    background-color: #28a745 !important;
                    color: #fff !important;
                    font-weight: bold !important;
                }
                #menu-posts-hotel_partner > a:hover {
                    background-color: #218838 !important;
                }
                #menu-posts-hotel_partner .wp-menu-image:before {
                    color: #fff !important;
                }
            </style>';
        });
    }

    public function enqueue_admin_scripts( $hook ) {
        global $post;
        
        if ( ($hook == 'post-new.php' || $hook == 'post.php') && 'hotel_partner' === $post->post_type ) {
            $api_key = get_option( 'wptb_google_maps_api_key' );
            if ( empty( $api_key ) ) {
                $api_key = 'AIzaSyCHNNn7ZxfS5PLtsPDifp2y-5ym4Ds7Its'; // Fallback
            }
            
            $maps_url = add_query_arg(
                array(
                    'key'       => $api_key,
                    'libraries' => 'places,geometry',
                    'language'  => 'es',
                    'region'    => 'ES'
                ),
                'https://maps.googleapis.com/maps/api/js'
            );
            
            wp_enqueue_script( 'google-maps-admin', $maps_url, array(), null, true );
            
            // Inline script para vincular el autocomplete al textarea (Google Places lo soporta, aunque es raro, funciona).
            // O mejor cambiar el textarea por un input tipo text si lo prefiere. El usuario ya tiene datos ahí.
            wp_add_inline_script( 'google-maps-admin', "
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                        var input = document.getElementById('hqp_hotel_address');
                        if (input) {
                            var autocomplete = new google.maps.places.Autocomplete(input, {
                                fields: ['formatted_address', 'geometry', 'name', 'address_components'],
                                strictBounds: false
                            });
                        }
                    }
                });
            " );
        }
    }

    public function add_custom_columns( $columns ) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            'hqp_address' => 'Dirección',
            'hqp_phone' => 'Teléfono',
            'hqp_discount' => 'Descuento (%)',
            'hqp_qr' => 'Código QR',
            'date' => $columns['date'],
        );
        return $new_columns;
    }

    public function render_custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'hqp_address':
                $address = get_post_meta( $post_id, '_hqp_hotel_address', true );
                echo $address ? esc_html( mb_substr( $address, 0, 50 ) ) . '...' : '&mdash;';
                break;
            case 'hqp_phone':
                $phone = get_post_meta( $post_id, '_hqp_hotel_phone', true );
                echo $phone ? esc_html( $phone ) : '&mdash;';
                break;
            case 'hqp_discount':
                $discount = get_post_meta( $post_id, '_hqp_discount_percent', true );
                echo $discount ? esc_html( $discount ) . '%' : '0%';
                break;
            case 'hqp_qr':
                $token = get_post_meta( $post_id, '_hqp_token', true );
                if ( $token ) {
          // Generate URL
        $url = home_url( '/reservas-hotel/?promo=' . $token );
        
        // QR API
        $qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode( $url );
                    echo '<img src="' . esc_url( $qr_api ) . '" alt="QR" style="width: 50px; height: 50px;" />';
                    echo '<br><a href="' . esc_url( $url ) . '" target="_blank" style="font-size:10px;">Ver Enlace</a>';
                } else {
                    echo 'Guarda para generar.';
                }
                break;
        }
    }

    public function add_hotel_meta_boxes() {
        add_meta_box(
            'hqp_hotel_details',
            'Detalles del Descuento',
            array( $this, 'render_meta_box' ),
            'hotel_partner',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        $discount = get_post_meta( $post->ID, '_hqp_discount_percent', true );
        $token = get_post_meta( $post->ID, '_hqp_token', true );
        $hotel_address = get_post_meta( $post->ID, '_hqp_hotel_address', true );
        $hotel_phone = get_post_meta( $post->ID, '_hqp_hotel_phone', true );
        $contact_name = get_post_meta( $post->ID, '_hqp_contact_name', true );
        $contact_email = get_post_meta( $post->ID, '_hqp_contact_email', true );
        $price_sedan = get_post_meta( $post->ID, '_hqp_price_sedan', true );
        $price_van = get_post_meta( $post->ID, '_hqp_price_van', true );
        
        $sedan_id = get_post_meta( $post->ID, '_hqp_sedan_id', true );
        $van_id = get_post_meta( $post->ID, '_hqp_van_id', true );
        
        global $wpdb;
        $vehicles = $wpdb->get_results("SELECT id, name, capacity FROM {$wpdb->prefix}wptb_hotel_vehicles WHERE is_active = 1 ORDER BY display_order ASC");

        // If no token, generate one (temp, will be saved on save_post if checking empty, but better here for display)
        if ( empty( $token ) ) {
            $token = 'HOTEL-' . strtoupper( wp_generate_password( 8, false ) );
        }

        wp_nonce_field( 'hqp_save_hotel_details', 'hqp_nonce' );
        ?>
        <p>
            <label for="hqp_discount_percent"><strong>Porcentaje de Descuento (%):</strong></label>
            <input type="number" id="hqp_discount_percent" name="hqp_discount_percent" value="<?php echo esc_attr( $discount ); ?>" min="0" max="100" step="1" style="width: 80px;">
            <span class="description">Ej: 10 para 10% de descuento.</span>
        </p>
        <p>
            <label for="hqp_token"><strong>Token Único (Año):</strong></label>
            <input type="text" id="hqp_token" name="hqp_token" value="<?php echo esc_attr( $token ); ?>" readonly style="width: 250px; background: #f0f0f0;">
        </p>
        
        <hr style="margin: 20px 0;">
        <h4>Información del Hotel</h4>
        <p>
            <label for="hqp_hotel_address"><strong>Dirección del Hotel:</strong></label><br>
            <input type="text" id="hqp_hotel_address" name="hqp_hotel_address" value="<?php echo esc_attr( $hotel_address ); ?>" style="width: 100%; max-width: 500px;">
            <span class="description">Dirección completa del hotel para referencia.</span>
        </p>
        <p>
            <label for="hqp_hotel_phone"><strong>Teléfono del Hotel:</strong></label><br>
            <input type="text" id="hqp_hotel_phone" name="hqp_hotel_phone" value="<?php echo esc_attr( $hotel_phone ); ?>" style="width: 300px;">
            <span class="description">Número de contacto del hotel.</span>
        </p>
        <p>
            <label for="hqp_contact_name"><strong>Nombre del Contacto:</strong></label><br>
            <input type="text" id="hqp_contact_name" name="hqp_contact_name" value="<?php echo esc_attr( $contact_name ); ?>" style="width: 300px;">
            <span class="description">Nombre de la persona de contacto en el hotel.</span>
        </p>
        <p>
            <label for="hqp_contact_email"><strong>Email del Contacto:</strong></label><br>
            <input type="email" id="hqp_contact_email" name="hqp_contact_email" value="<?php echo esc_attr( $contact_email ); ?>" style="width: 300px;">
            <span class="description">Correo electrónico de contacto del hotel.</span>
        </p>
        <hr style="margin: 20px 0;">
        <h4>Precios Fijos (Opcional)</h4>
        <p class="description">Si se establece un precio aquí, será el precio único ofertado para el trayecto desde/hacia este hotel (dentro de Barcelona/Cataluña) para ese vehículo. Los vehículos con precio "0" o vacío no se ofrecerán.</p>
        
        <div style="display:flex; flex-wrap: wrap; gap: 20px;">
            <?php foreach($vehicles as $v): 
                $price_val = get_post_meta( $post->ID, '_hqp_price_vehicle_' . $v->id, true );
            ?>
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; width: 250px;">
                <p style="margin-top: 0;"><strong><?php echo esc_html($v->name); ?></strong><br>
                <span class="description">Capacidad: <?php echo esc_html($v->capacity); ?> pax</span></p>
                <p>
                    <label for="hqp_price_vehicle_<?php echo esc_attr($v->id); ?>">Precio (€):</label><br>
                    <input type="number" id="hqp_price_vehicle_<?php echo esc_attr($v->id); ?>" name="hqp_price_vehicle_<?php echo esc_attr($v->id); ?>" value="<?php echo esc_attr( $price_val ); ?>" min="0" step="0.01" style="width: 100px;">
                </p>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ( $post->ID && $token ) : 
            // Point to the dedicated hotel booking page
            $url = home_url( '/reservas-hotel/?promo=' . $token );
            $qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode( $url );
            
            $download_url = admin_url( 'admin-post.php?action=hqp_download_qr&post_id=' . $post->ID . '&nonce=' . wp_create_nonce('hqp_download_qr_' . $post->ID) );
            $flyer_url = admin_url( 'admin-post.php?action=hqp_download_flyer&post_id=' . $post->ID . '&nonce=' . wp_create_nonce('hqp_download_flyer_' . $post->ID) );
        ?>
            <hr style="margin: 20px 0;">
            <h4>Código QR Permanente</h4>
            <div style="display: flex; align-items: flex-start; gap: 20px;">
                <img src="<?php echo esc_url( $qr_api ); ?>" alt="QR Code" style="border: 1px solid #ccc; padding: 5px; max-width: 200px; height: auto;">
                <div>
                    <p>Este código QR dirige a:</p>
                    <code><?php echo esc_url( $url ); ?></code>
                    <p>Imprime este QR y colócalo en la recepción del hotel. No caduca.</p>
                    <div style="display: flex; gap: 10px;">
                        <a href="<?php echo esc_url( $download_url ); ?>" class="button button-secondary">Descargar Imagen QR</a>
                        <a href="<?php echo esc_url( $flyer_url ); ?>" class="button button-primary" style="background: #0073aa; border-color: #0073aa;">Descargar Flyer PDF</a>
                    </div>
                </div>
            </div>
            
            <?php
            // Show list of bookings that used this QR codee
            global $wpdb;
            $bookings_table = $wpdb->prefix . 'wptb_bookings';
            $bookings = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, booking_date, booking_time, origin, destination, customer_name, customer_email, customer_phone, price, status, payment_intent_id
                FROM $bookings_table
                WHERE hotel_token = %s
                ORDER BY created_at DESC
                LIMIT 50",
                $token
            ) );
            
            if ( ! empty( $bookings ) ) :
            ?>
                <hr style="margin: 20px 0;">
                <h4>Clientes que usaron este Código QR (<?php echo count($bookings); ?>)</h4>
                <table class="widefat striped" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Ruta</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Precio</th>
                            <th>C— — — — — — — — ód. Redsys</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $bookings as $booking ) : ?>
                            <tr>
                                <td><a href="<?php echo admin_url('admin.php?page=wptb-reservas'); ?>" target="_blank">#<?php echo $booking->id; ?></a></td>
                                <td><?php echo esc_html( $booking->booking_date . ' ' . $booking->booking_time ); ?></td>
                                <td style="font-size:11px;">
                                    <?php if ( ! empty( $booking->origin ) ) : ?>
                                        <strong>De:</strong> <?php echo esc_html( mb_substr( $booking->origin, 0, 30 ) ); ?><br>
                                        <strong>A:</strong> <?php echo esc_html( mb_substr( $booking->destination, 0, 30 ) ); ?>
                                    <?php else : ?>
                                        — — — — — — — — — — — — —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $booking->customer_name ); ?></td>
                                <td><a href="mailto:<?php echo esc_attr( $booking->customer_email ); ?>"><?php echo esc_html( $booking->customer_email ); ?></a></td>
                                <td><?php echo esc_html( $booking->customer_phone ); ?></td>
                                <td>— — — — — — — — — — — php echo esc_html( $booking->price ); ?></td>
                                <td style="font-family:monospace; font-size:11px; color:#555;">
                                    <?php echo $booking->payment_intent_id ? esc_html( $booking->payment_intent_id ) : '—'; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_labels = array(
                                        'confirmed'       => '— — — — — — — — — — — — Confirmado',
                                        'pending'         => '— — — — — — — — — — — Pendiente',
                                        'pending_payment' => '— — — — — — — — — — — — — — — Pend. Pago',
                                        'cancelled'       => '— — — — — — — — — — — Cancelado'
                                    );
                                    echo isset( $status_labels[ $booking->status ] ) ? $status_labels[ $booking->status ] : esc_html( $booking->status );
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 10px;"><em>Mostrando las — — — — — — — — últimas 50 reservas.</em></p>
            <?php else : ?>
                <hr style="margin: 20px 0;">
                <p><em>Aún no hay reservas realizadas con este código QR.</em></p>
            <?php endif; ?>
        <?php endif; ?>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var input = document.getElementById('hqp_hotel_address');
            if (input && typeof google !== 'undefined' && google.maps && google.maps.places) {
                var autocomplete = new google.maps.places.Autocomplete(input);
                autocomplete.setFields(['formatted_address', 'geometry', 'name']);
            }
        });
        </script>
        <?php
    }

    public function save_hotel_meta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        
        if ( ! isset( $_POST['hqp_nonce'] ) || ! wp_verify_nonce( $_POST['hqp_nonce'], 'hqp_save_hotel_details' ) ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_posts' ) ) return;

        if ( isset( $_POST['hqp_discount_percent'] ) ) {
             update_post_meta( $post_id, '_hqp_discount_percent', intval( $_POST['hqp_discount_percent'] ) );
        }

        if ( isset( $_POST['hqp_token'] ) ) {
            update_post_meta( $post_id, '_hqp_token', sanitize_text_field( $_POST['hqp_token'] ) );
        }
        
        if ( isset( $_POST['hqp_hotel_address'] ) ) {
            update_post_meta( $post_id, '_hqp_hotel_address', sanitize_text_field( $_POST['hqp_hotel_address'] ) );
        }
        
        if ( isset( $_POST['hqp_hotel_phone'] ) ) {
            update_post_meta( $post_id, '_hqp_hotel_phone', sanitize_text_field( $_POST['hqp_hotel_phone'] ) );
        }

        if ( isset( $_POST['hqp_contact_name'] ) ) {
            update_post_meta( $post_id, '_hqp_contact_name', sanitize_text_field( $_POST['hqp_contact_name'] ) );
        }

        if ( isset( $_POST['hqp_contact_email'] ) ) {
            update_post_meta( $post_id, '_hqp_contact_email', sanitize_email( $_POST['hqp_contact_email'] ) );
        }

        global $wpdb;
        $vehicles = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}wptb_hotel_vehicles WHERE is_active = 1");
        if ( $vehicles ) {
            foreach ( $vehicles as $v ) {
                $key_post = 'hqp_price_vehicle_' . $v->id;
                $key_meta = '_hqp_price_vehicle_' . $v->id;
                if ( isset( $_POST[$key_post] ) ) {
                    update_post_meta( $post_id, $key_meta, floatval( $_POST[$key_post] ) );
                }
            }
        }
    }

    public function download_qr_code() {
        if ( ! isset( $_GET['post_id'] ) || ! isset( $_GET['nonce'] ) ) {
            wp_die( 'Faltan parámetros.' );
        }

        $post_id = intval( $_GET['post_id'] );
        if ( ! wp_verify_nonce( $_GET['nonce'], 'hqp_download_qr_' . $post_id ) ) {
            wp_die( 'Enlace caducado o inválido.' );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Permisos insuficientes.' );
        }

        $token = get_post_meta( $post_id, '_hqp_token', true );
        if ( ! $token ) wp_die( 'Token no encontrado.' );

        $url = home_url( '/reservas-hotel/?promo=' . $token );
        $qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode( $url ); // High Res for download

        // Fetch Image
        $response = wp_remote_get( $qr_api );
        if ( is_wp_error( $response ) ) {
            wp_die( 'Error al generar QR: ' . $response->get_error_message() );
        }

        $image_data = wp_remote_retrieve_body( $response );
        $filename = 'qr-hotel-' . get_the_title( $post_id ) . '.png';

        // Headers to force download
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: image/png' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . strlen( $image_data ) );

        echo $image_data;
        exit;
    }

    /**
     * Descarga el Flyer PDF con el QR sobre el fondo (formato hablador)
     */
    public function download_flyer_pdf() {
        if ( ! isset( $_GET['post_id'] ) || ! isset( $_GET['nonce'] ) ) {
            wp_die( 'Faltan parámetros.' );
        }

        $post_id = intval( $_GET['post_id'] );
        if ( ! wp_verify_nonce( $_GET['nonce'], 'hqp_download_flyer_' . $post_id ) ) {
            wp_die( 'Enlace caducado o inválido.' );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Permisos insuficientes.' );
        }

        $token = get_post_meta( $post_id, '_hqp_token', true );
        if ( ! $token ) wp_die( 'Token no encontrado.' );

        // Buscar imagen flyer hotel
        $bg_name = 'HABLADOR - METRANSFERS.png';
        $bg_image_path = defined( 'CBP_PLUGIN_DIR' ) ? CBP_PLUGIN_DIR . $bg_name : '';
        if ( empty( $bg_image_path ) || ! file_exists( $bg_image_path ) ) {
            $bg_image_path = HQP_PLUGIN_DIR . 'assets/' . $bg_name;
        }
        if ( ! file_exists( $bg_image_path ) ) {
            wp_die(
                'No se encuentra el archivo de fondo: ' . esc_html( $bg_name ) . '<br><br>' .
                'Coloca la imagen en una de estas ubicaciones:<br>' .
                '------------ Raíz del plugin: <code>' . esc_html( $plugin_dir ) . '</code><br>' .
                '------------ Módulo hotel: <code>' . esc_html( $hotel_module_dir ) . '</code>'
            );
        }

        $url    = home_url( '/reservas-hotel/?promo=' . $token );
        $qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=' . urlencode( $url );

        $qr_response = wp_remote_get( $qr_api );
        if ( is_wp_error( $qr_response ) ) {
            wp_die( 'Error al descargar QR: ' . $qr_response->get_error_message() );
        }
        $tmp_qr = sys_get_temp_dir() . '/hqp_qr_' . $post_id . '.png';
        file_put_contents( $tmp_qr, wp_remote_retrieve_body( $qr_response ) );

        if ( ! class_exists( 'FPDF' ) ) {
            require_once HQP_PLUGIN_DIR . 'includes/fpdf.php';
        }

        $pdf = new FPDF( 'P', 'mm', 'A4' );
        $pdf->AddPage();

        // Fondo A4 (210x297 mm)
        $pdf->Image( $bg_image_path, 0, 0, 210, 297 );

        // Posici— — — — — — — — ón del QR dentro del cuadro blanco izquierdo del hablador
        $qr_rect = apply_filters( 'hqp_flyer_qr_rect', array(
            'x'    => 14,   // mm
            'y'    => 130,  // mm
            'size' => 70,   // mm
        ) );
        $qr_x    = isset( $qr_rect['x'] ) ? floatval( $qr_rect['x'] ) : 14;
        $qr_y    = isset( $qr_rect['y'] ) ? floatval( $qr_rect['y'] ) : 130;
        $qr_size = isset( $qr_rect['size'] ) ? floatval( $qr_rect['size'] ) : 70;

        // QR del plugin sobre el fondo, en el espacio designado
        if ( file_exists( $tmp_qr ) && filesize( $tmp_qr ) > 0 ) {
            $pdf->Image( $tmp_qr, $qr_x, $qr_y, $qr_size, $qr_size, 'PNG' );
        }

        @unlink( $tmp_qr );

        $filename = 'Flyer-Hotel-' . sanitize_title( get_the_title( $post_id ) ) . '.pdf';
        $pdf->Output( 'D', $filename );
        exit;
    }

    /**
     * Add Submenu Page for Hotel Reservations
     */
    public function add_hotel_submenu() {
        add_submenu_page(
            'edit.php?post_type=hotel_partner',
            'Reservas por QR',
            'Reservas por QR',
            'manage_options',
            'hotel-qr-reservations',
            array( $this, 'display_hotel_reservations_page' )
        );
        
        $vehicles_admin = new HQP_Vehicles_Admin();
        add_submenu_page(
            'edit.php?post_type=hotel_partner',
            'Vehículos',
            'Vehículos',
            'manage_options',
            'hotel-vehicles',
            array( $vehicles_admin, 'display_vehicles_page' )
        );
    }
    
    /**
     * Display Hotel Reservations Page
     */
    public function display_hotel_reservations_page() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'wptb_bookings';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $hotel_filter = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $where_clauses = array("hotel_token IS NOT NULL AND hotel_token != ''");
        
        if (!empty($search)) {
            $where_clauses[] = $wpdb->prepare(
                "(customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        if (!empty($status_filter)) {
            $where_clauses[] = $wpdb->prepare("status = %s", $status_filter);
        }
        
        // Optimize: Filter directly in SQL if hotel is selected
        if ($hotel_filter > 0) {
            $filter_token = get_post_meta($hotel_filter, '_hqp_token', true);
            if ($filter_token) {
                 $where_clauses[] = $wpdb->prepare("hotel_token = %s", $filter_token);
            }
        }
        
        $user = wp_get_current_user();
        $is_check_hotel = in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles );

        $hotel_args = array(
            'post_type' => 'hotel_partner',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        if ( $is_check_hotel ) {
            $hotel_args['author'] = $user->ID;
        }
        $hotels = get_posts($hotel_args);
        
        $hotel_map = array();
        $allowed_tokens = array();
        foreach ($hotels as $hotel) {
            $token = get_post_meta($hotel->ID, '_hqp_token', true);
            if ($token) {
                $hotel_map[$token] = array(
                    'id' => $hotel->ID,
                    'name' => $hotel->post_title,
                    'address' => get_post_meta($hotel->ID, '_hqp_hotel_address', true),
                    'phone' => get_post_meta($hotel->ID, '_hqp_hotel_phone', true),
                    'discount' => get_post_meta($hotel->ID, '_hqp_discount_percent', true)
                );
                $allowed_tokens[] = $token;
            }
        }

        // Aplicar restriccion para check_hoteles (solo reservas de sus propios hoteles)
        if ( $is_check_hotel ) {
            if ( empty( $allowed_tokens ) ) {
                $where_clauses[] = "1=0"; // No mostrar reservas si no tienen hoteles
            } else {
                $placeholders = implode(',', array_fill(0, count($allowed_tokens), '%s'));
                $where_clauses[] = $wpdb->prepare("hotel_token IN ($placeholders)", ...$allowed_tokens);
            }
        }

        $where_sql = implode(' AND ', $where_clauses);
        
        // Paginacion
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table WHERE $where_sql");
        $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM $bookings_table WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset));
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Reservas por Código QR de Hoteles</h1>
            <hr class="wp-header-end">
            
            <div class="tablenav top" style="margin: 20px 0;">
                <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="post_type" value="hotel_partner">
                    <input type="hidden" name="page" value="hotel-qr-reservations">
                    
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar cliente..." style="height: 30px;">
                    
                    <select name="hotel_id">
                        <option value="0">Todos los Hoteles</option>
                        <?php foreach ($hotels as $hotel) : ?>
                            <option value="<?php echo $hotel->ID; ?>" <?php selected($hotel_filter, $hotel->ID); ?>>
                                <?php echo esc_html($hotel->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status">
                        <option value="">Todos los Estados</option>
                        <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>>Confirmado</option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pendiente</option>
                        <option value="pending_payment" <?php selected($status_filter, 'pending_payment'); ?>>Pago Pendiente</option>
                        <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelado</option>
                    </select>
                    
                    <input type="submit" class="button" value="Filtrar">
                    
                    <?php if ($search || $hotel_filter || $status_filter) : ?>
                        <a href="<?php echo admin_url('edit.php?post_type=hotel_partner&page=hotel-qr-reservations'); ?>" class="button">Limpiar</a>
                    <?php endif; ?>
                </form>
                
                <div class="alignright actions">
                     <span class="displaying-num">Total: <strong><?php echo intval($total_items); ?></strong></span>
                </div>
                
                <?php
                // Pagination Links
                $total_pages = ceil($total_items / $per_page);
                if ($total_pages > 1) {
                    echo '<div class="tablenav-pages">';
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    echo '</div>';
                }
                ?>
            </div>
            
            <style>
                .wptb-status-badge {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                .wptb-status-pending { background: #f0ad4e; color: white; }
                .wptb-status-confirmed { background: #5cb85c; color: white; }
                .wptb-status-cancelled { background: #d9534f; color: white; }
                .wptb-status-completed { background: #5bc0de; color: white; }
                .wptb-status-pending_payment { background: #0275d8; color: white; }
                
                .wp-list-table td { vertical-align: top; padding: 12px 10px; }
                .wp-list-table th { font-weight: 600; padding: 12px 10px; }
                .tablenav-pages .page-numbers { padding: 4px 8px; border: 1px solid #ccc; text-decoration: none; margin-left: 3px; background: #fff; font-size: 12px; }
                .tablenav-pages .page-numbers.current { background: #0073aa; color: #fff; border-color: #0073aa; }
                
                .row-title { font-weight: 600; color: #0073aa; font-size: 14px; }
                .subtitle { color: #666; font-size: 12px; display: block; margin-top: 3px; line-height: 1.4; }
                .hotel-tag { background: #f0f0f1; border: 1px solid #ccd0d4; border-radius: 4px; padding: 2px 6px; font-size: 11px; color: #50575e; display: inline-block; margin-top: 4px;}
            </style>

            <?php if (empty($bookings)) : ?>
                <div class="notice notice-info inline"><p>No se encontraron reservas.</p></div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>Hotel Origen</th>
                            <th>Cliente</th>
                            <th>Fecha/Hora</th>
                            <th>Ruta</th>
                            <th>Vehículo</th>
                            <th>Precio</th>
                            <th>Desc.</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking) : 
                            $hotel_info = isset($hotel_map[$token]) ? $hotel_map[$token] : null;
                        ?>
                            <tr>
                                <td>#<?php echo $booking->id; ?></td>
                                <td>
                                    <?php if ($hotel_info) : ?>
                                        <div class="row-title"><?php echo esc_html($hotel_info['name']); ?></div>
                                        <?php if ($hotel_info['address']) : ?>
                                            <span class="subtitle"><?php echo esc_html(mb_substr($hotel_info['address'], 0, 30)); ?>...</span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <em>Hotel no encontrado</em><br>
                                        <small class="description"><?php echo esc_html(substr($booking->hotel_token, 0, 8)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($booking->customer_name); ?></strong><br>
                                    <small><?php echo esc_html($booking->customer_email); ?></small><br>
                                    <small><?php echo esc_html($booking->customer_phone); ?></small><br>
                                    <small style="color: #666;"><?php echo $booking->passengers; ?> pasajeros</small>
                                </td>
                                <td>
                                    <?php 
                                    if (strpos($booking->booking_date, ' ') !== false) {
                                        echo esc_html($booking->booking_date);
                                    } else {
                                        echo esc_html($booking->booking_date . ' ' . $booking->booking_time);
                                    }
                                    ?>
                                </td>
                                <td style="min-width: 250px;">
                                    <strong>Desde:</strong> <?php echo esc_html(isset($booking->origin) ? $booking->origin : (isset($booking->pickup_address) ? $booking->pickup_address : '')); ?><br>
                                    <strong>Hasta:</strong> <?php echo esc_html(isset($booking->destination) ? $booking->destination : (isset($booking->dropoff_address) ? $booking->dropoff_address : '')); ?>
                                </td>
                                <td>
                                    <?php 
                                    // Try to get vehicle name via Manager if available, else DB/Fallback
                                    $v_name = 'N/A';
                                    if (class_exists('WPTB_Vehicle_Manager')) {
                                        $veh = WPTB_Vehicle_Manager::get_vehicle($booking->vehicle_id);
                                        if ($veh) $v_name = $veh->name;
                                    }
                                    // Fallback if not found or manager not exists
                                    if ($v_name === 'N/A' && !empty($booking->vehicle_id)) {
                                         // manual fallback map if needed, or query
                                         $v_name = ($booking->vehicle_id == 1) ? 'Sedan' : 'Minivan';
                                    }
                                    echo esc_html($v_name);
                                    ?>
                                </td>
                                <td><strong>—</strong></td>
                                <td><?php echo $hotel_info && $hotel_info['discount'] > 0 ? '<span class="hotel-tag">' . $hotel_info['discount'] . '% OFF</span>' : '—'; ?></td>
                                <td>
                                    <?php
                                    $status_labels = array(
                                        'pending' => 'Pendiente',
                                        'confirmed' => 'Confirmado',
                                        'cancelled' => 'Cancelado',
                                        'completed' => 'Completado',
                                        'pending_payment' => 'Pago Pendiente'
                                    );
                                    $label = isset($status_labels[$booking->status]) ? $status_labels[$booking->status] : ucfirst($booking->status);
                                    $status_class = 'wptb-status-badge wptb-status-' . $booking->status;
                                    echo '<span class="' . $status_class . '">' . esc_html($label) . '</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function create_booking_page() {
        $page_title = 'Reservas Hotel';
        $page_slug = 'reservas-hotel';
        $page_content = '[hqp_booking_form]';

        $page = get_page_by_path( $page_slug );

        if ( ! $page ) {
            $page_id = wp_insert_post( array(
                'post_title'    => $page_title,
                'post_name'     => $page_slug,
                'post_content'  => $page_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'comment_status'=> 'closed'
            ) );
        }
    }
}

