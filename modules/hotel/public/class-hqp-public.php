<?php

class HQP_Public {

    public function check_url_token() {
        if ( is_admin() ) return;
        
        $token = '';
        if ( isset( $_GET['promo'] ) && ! empty( $_GET['promo'] ) ) {
             $token = sanitize_text_field( $_GET['promo'] );
        } elseif ( isset( $_GET['hotel_token'] ) && ! empty( $_GET['hotel_token'] ) ) {
             $token = sanitize_text_field( $_GET['hotel_token'] );
        }

        if ( $token ) {
            $args = array(
                'post_type' => 'hotel_partner',
                'meta_key' => '_hqp_token',
                'meta_value' => $token,
                'posts_per_page' => 1,
                'fields' => 'ids'
            );
            $query = new WP_Query( $args );
            
            if ( $query->have_posts() ) {
                $hotel_id = $query->posts[0];
                
                setcookie( 'hqp_hotel_token', $token, time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
                setcookie( 'hqp_hotel_id', $hotel_id, time() + 86400, COOKIEPATH, COOKIE_DOMAIN );

                $booking_page_id = get_transient( 'hqp_booking_page_id' );
                if ( false === $booking_page_id ) {
                    global $wpdb;
                    $booking_page_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_content LIKE '%[hqp_booking_form]%' LIMIT 1" );
                    if ( $booking_page_id ) {
                        set_transient( 'hqp_booking_page_id', $booking_page_id, DAY_IN_SECONDS );
                    }
                }
                
                if ( $booking_page_id ) {
                    $booking_page_url = get_permalink( $booking_page_id );
                    if ( $booking_page_url ) {
                        $target_url = add_query_arg( 'promo', $token, $booking_page_url );
                        $current_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                        
                        $target_path = parse_url( $target_url, PHP_URL_PATH );
                        $current_path = parse_url( $current_url, PHP_URL_PATH );

                        if ( trim($target_path, '/') !== trim($current_path, '/') ) {
                            wp_safe_redirect( $target_url );
                            exit;
                        }
                    }
                }
            }
        }
    }

    public function enqueue_scripts() {
        $token = '';
        if ( isset( $_GET['promo'] ) ) $token = sanitize_text_field( $_GET['promo'] );
        elseif ( isset( $_GET['hotel_token'] ) ) $token = sanitize_text_field( $_GET['hotel_token'] );
        elseif ( isset( $_COOKIE['hqp_hotel_token'] ) ) $token = sanitize_text_field( $_COOKIE['hqp_hotel_token'] );

        if ( $token ) {
            $args = array(
                'post_type' => 'hotel_partner',
                'meta_key' => '_hqp_token',
                'meta_value' => $token,
                'posts_per_page' => 1,
                'fields' => 'ids'
            );
            $q = new WP_Query( $args );
            if ( $q->have_posts() ) {
                $hotel_id = $q->posts[0];
                $discount = get_post_meta( $hotel_id, '_hqp_discount_percent', true );
                if ( $discount > 0 ) {
                    wp_enqueue_script( 'hqp-intercept', HQP_PLUGIN_URL . 'public/js/hotel-booking-intercept.js', array( 'jquery' ), '1.0.1', true );
                    wp_localize_script( 'hqp-intercept', 'hqp_vars', array(
                        'discount_percent' => $discount,
                        'message' => "Descuento de Hotel aplicado: {$discount}%"
                    ));
                }
            }
        }
    }

    public function register_shortcodes() {
        add_shortcode( 'hqp_booking_form', array( $this, 'render_booking_form' ) );
    }

    public function render_booking_form( $atts ) {
        $hotel_id = 0;
        if ( isset( $_COOKIE['hqp_hotel_id'] ) ) {
            $hotel_id = intval( $_COOKIE['hqp_hotel_id'] );
        }

        if ( ! $hotel_id && isset( $_GET['promo'] ) ) {
            $token = sanitize_text_field( $_GET['promo'] );
            $args = array(
                'post_type' => 'hotel_partner',
                'meta_key' => '_hqp_token',
                'meta_value' => $token,
                'posts_per_page' => 1,
                'fields' => 'ids'
            );
            $q = new WP_Query( $args );
            if ( $q->have_posts() ) {
                $hotel_id = $q->posts[0];
            }
        }

        wp_enqueue_style( 'hqp-booking-css', plugin_dir_url( __FILE__ ) . 'css/hqp-booking.css', array(), '1.0.1' );
        wp_enqueue_script( 'hqp-booking-js', plugin_dir_url( __FILE__ ) . 'js/hqp-booking.js', array( 'jquery', 'wptb-booking-js' ), '1.0.2', true );

        $hotel_name = '';
        $hotel_address = '';

        if ( $hotel_id ) {
            $hotel_name = get_the_title( $hotel_id );
            $hotel_address = get_post_meta( $hotel_id, '_hqp_hotel_address', true );
        } else {
            return '<p>No se pudo identificar el hotel. Por favor, asegúrate de acceder a través del código QR correcto o contacta con recepción.</p>';
        }

        wp_localize_script( 'hqp-booking-js', 'wptb_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wptb-booking-nonce' )
        ));

        ob_start();
        include plugin_dir_path( __FILE__ ) . 'partials/hqp-booking-form.php';
        return ob_get_clean();
    }

    public function ajax_get_fixed_pricing() {
        check_ajax_referer( 'wptb-booking-nonce', 'security' );

        $hotel_id = isset( $_POST['hotel_id'] ) ? intval( $_POST['hotel_id'] ) : 0;
        
        if ( ! $hotel_id ) {
            wp_send_json_error( array( 'message' => 'Hotel ID missing.' ) );
        }

        $passengers = isset( $_POST['passengers'] ) ? intval( $_POST['passengers'] ) : 1;
        $vehicle_type = isset( $_POST['vehicle_type'] ) ? sanitize_text_field( $_POST['vehicle_type'] ) : '';

        $vehicles = array();
        global $wpdb;
        $db_vehicles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wptb_hotel_vehicles WHERE is_active = 1 ORDER BY display_order ASC");

        if ( $db_vehicles ) {
            foreach ( $db_vehicles as $v ) {
                // Filter by minimum passengers
                if ( intval( $v->capacity ) < $passengers ) {
                    continue;
                }

                // Filter by vehicle type preference
                if ( $vehicle_type === 'van' && intval( $v->capacity ) <= 4 ) {
                    continue; // They want a van, skip sedans
                }
                if ( $vehicle_type === 'sedan' && intval( $v->capacity ) > 4 ) {
                    continue; // They want a sedan, skip vans
                }

                $vehicle_id = $v->id;
                $fixed_price = get_post_meta( $hotel_id, '_hqp_price_vehicle_' . $vehicle_id, true );

                // Si no hay precio fijo establecido para este vehículo en este hotel, no se ofrece.

                if ( ! empty( $fixed_price ) && floatval( $fixed_price ) > 0 ) {
                    $discount_percent = (int) get_post_meta( $hotel_id, '_hqp_discount_percent', true );
                    $final_price = floatval( $fixed_price );
                    if ( $discount_percent > 0 && $discount_percent <= 100 ) {
                        $final_price = $final_price - ( $final_price * ( $discount_percent / 100 ) );
                    }
                    
                    $vehicles[] = array(
                        'id'          => $vehicle_id,
                        'name'        => $v->name,
                        'description' => isset($v->description) ? $v->description : '',
                        'capacity'    => $v->capacity,
                        'price'       => number_format( $final_price, 2, '.', '' )
                    );
                } else {
                    error_log("WPTB HOTEL VEHICLE EXCLUDED: ID $vehicle_id - Fixed Price: $fixed_price");
                }
            }
        } else {
            error_log("WPTB HOTEL VEHICLES: db_vehicles is empty. is_active=1 returned no rows.");
        }

        if ( empty( $vehicles ) ) {
            error_log("WPTB HOTEL AJAX: Array de vehiculos esta vacio para hotel $hotel_id");
            wp_send_json_error( array( 'message' => 'No hay vehículos disponibles para este hotel.' ) );
        }

        wp_send_json_success( $vehicles );
    }

    public function ajax_create_booking() {
        check_ajax_referer( 'wptb-booking-nonce', 'security' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        
        $data = $_POST;
        
        // 1. Validation
        if ( empty( $data['hotel_id'] ) || empty( $data['vehicle_id'] ) || empty( $data['date'] ) || empty( $data['time'] ) ) {
            wp_send_json_error( array( 'message' => 'Faltan datos obligatorios.' ) );
        }

        $hotel_id = intval( $data['hotel_id'] );
        $vehicle_id = intval( $data['vehicle_id'] );

        $price = get_post_meta( $hotel_id, '_hqp_price_vehicle_' . $vehicle_id, true );

        if ( ! $price || floatval( $price ) <= 0 ) {
            wp_send_json_error( array( 'message' => 'Precio no válido para este vehículo.' ) );
        }

        $discount_percent = (int) get_post_meta( $hotel_id, '_hqp_discount_percent', true );
        if ( $discount_percent > 0 && $discount_percent <= 100 ) {
            $price_val = floatval( $price );
            $price = $price_val - ( $price_val * ( $discount_percent / 100 ) );
        }

        $date = sanitize_text_field( $data['date'] );
        $time = sanitize_text_field( $data['time'] );
        
        $origin = sanitize_text_field( $data['origin'] );
        $destination = sanitize_text_field( $data['destination'] );
        
        // --- ADD DISTANCE CALCULATION FOR HOTELS ---
        $distance_km = 0;
        $api_key = get_option( 'wptb_google_maps_api_key' );
        if ( ! empty( $api_key ) && ! empty( $origin ) && ! empty( $destination ) ) {
            $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . urlencode($origin) . '&destinations=' . urlencode($destination) . '&key=' . $api_key . '&units=metric';
            $response = wp_remote_get( $url );
            if ( ! is_wp_error( $response ) ) {
                $body = wp_remote_retrieve_body( $response );
                $json = json_decode( $body, true );
                if ( isset( $json['rows'][0]['elements'][0]['status'] ) && $json['rows'][0]['elements'][0]['status'] === 'OK' ) {
                    $meters = $json['rows'][0]['elements'][0]['distance']['value'];
                    $distance_km = round( $meters / 1000, 1 );
                }
            }
        }
        // -------------------------------------------
        
        $booking_data = array(
            'booking_date'   => $date,
            'booking_time'   => $time,
            'origin'         => $origin,
            'destination'    => $destination,
            'distance_km'    => $distance_km,
            'duration_minutes' => 0,
            'price'          => $price,
            'customer_name'  => sanitize_text_field( $data['customer_name'] ),
            'customer_email' => sanitize_email( $data['customer_email'] ),
            'customer_phone' => sanitize_text_field( $data['customer_phone'] ),
            'passengers'     => intval( $data['passengers'] ),
            'flight_number'  => sanitize_text_field( $data['flight_number'] ),
            'notes'          => sanitize_textarea_field( $data['notes'] ),
            'vehicle_id'     => $vehicle_id,
            'trip_type'      => 'one_way',
            'status'         => 'pending_payment',
            'payment_method' => 'redsys',
            'created_at'     => current_time( 'mysql' ),
            'hotel_token'    => get_post_meta( $hotel_id, '_hqp_token', true ),
        );

        $format_db = array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' );

        $result = $wpdb->insert( $table_name, $booking_data, $format_db );
        $booking_id = $wpdb->insert_id;

        if ( ! $result || ! $booking_id ) {
            wp_send_json_error( array( 'message' => 'Error al guardar la reserva en la base de datos. ' . $wpdb->last_error ) );
            return;
        }
        
        // Order ID para Getnet/Redsys: el booking_id ya es >= 10000 gracias al AUTO_INCREMENT.
        $order_id = str_pad( $booking_id, 12, '0', STR_PAD_LEFT );
        
        $wpdb->update( 
            $table_name, 
            array( 'payment_intent_id' => $order_id ), 
            array( 'id' => $booking_id ) 
        );

        // Enviar notificaciones de reserva pendiente (Admin y Cliente)
        if ( class_exists( 'WPTB_Public' ) ) {
            $booking_obj = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $booking_id) );
            if ( $booking_obj ) {
                $wptb_public = new WPTB_Public();
                $wptb_public->process_booking_notifications( $booking_id, $booking_obj, 'pending' );
            }
        }

        $url_ok = home_url( '/reservas-metransfers/?payment_result=ok&oid=' . $order_id );

        if ( $price <= 0 ) {
            wp_send_json_success( array( 'redirect' => $url_ok ) );
            return;
        }

        try {
            if ( ! class_exists( 'WPTB_Redsys_API' ) ) {
                require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-redsys.php';
            }
            
            $key = '6N2lZu0nf+j7MnyFKGWyOxdzZau5sAAE'; 
            $merchant_code = '48234082';
            $terminal = '1';
            $currency = '978'; // EUR
            $trans_type = '0'; // Autorización
            
            $redsys = new WPTB_Redsys_API();
            
            $amount = intval( $price * 100 ); // Cents
            
            $url_notification = home_url( '/?wptb_redsys_ipn=1' );
            $url_ko = home_url( '/reservas-metransfers/?payment_result=ko&oid=' . $order_id );
            
            $redsys->setParameter("DS_MERCHANT_AMOUNT", $amount);
            $redsys->setParameter("DS_MERCHANT_ORDER", $order_id);
            $redsys->setParameter("DS_MERCHANT_MERCHANTCODE", $merchant_code);
            $redsys->setParameter("DS_MERCHANT_CURRENCY", $currency);
            $redsys->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $trans_type);
            $redsys->setParameter("DS_MERCHANT_TERMINAL", $terminal);
            $redsys->setParameter("DS_MERCHANT_MERCHANTURL", $url_notification);
            $redsys->setParameter("DS_MERCHANT_URLOK", $url_ok);
            $redsys->setParameter("DS_MERCHANT_URLKO", $url_ko);
            $redsys->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION", "Reserva #" . $booking_id);
            $redsys->setParameter("DS_MERCHANT_TITULAR", substr($data['customer_name'], 0, 60));

            $params = $redsys->createMerchantParameters();
            $signature = $redsys->createMerchantSignature($key);
            
            wp_send_json_success( array(
                'url' => 'https://sis.redsys.es/sis/realizarPago', 
                'ds_signature_version' => 'HMAC_SHA256_V1',
                'ds_merchant_parameters' => $params,
                'ds_signature' => $signature
            ));

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Error generando pago: ' . $e->getMessage() ) );
        }
    }


    private function get_discount_from_token( $token ) {
        if ( empty( $token ) ) return 0;
        
        $args = array(
            'post_type' => 'hotel_partner',
            'meta_key' => '_hqp_token',
            'meta_value' => $token,
            'posts_per_page' => 1,
            'fields' => 'ids'
        );
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
             return (int) get_post_meta( $query->posts[0], '_hqp_discount_percent', true );
        }
        return 0;
    }

    public function apply_booking_discount( $price ) {
        $token = '';
        if ( isset( $_COOKIE['hqp_hotel_token'] ) ) {
            $token = sanitize_text_field( $_COOKIE['hqp_hotel_token'] );
        }
        
        $discount_percent = $this->get_discount_from_token( $token );
        
        if ( $discount_percent <= 0 || $discount_percent > 100 ) {
            return $price;
        }
        
        $original_price = floatval( $price );
        $discount_amount = ( $original_price * $discount_percent ) / 100;
        $final_price = $original_price - $discount_amount;
        
        return number_format( $final_price, 2, '.', '' );
    }

    public function intercept_booking_submission() {
        if ( ! isset( $_POST['price'] ) ) return;

        $token = '';
        if ( isset( $_COOKIE['hqp_hotel_token'] ) ) {
            $token = sanitize_text_field( $_COOKIE['hqp_hotel_token'] );
        }
        
        $discount_percent = $this->get_discount_from_token( $token );
        
        if ( $discount_percent > 0 ) {
            $original_price = floatval( $_POST['price'] );
            $discount_amount = ( $original_price * $discount_percent ) / 100;
            $final_price = $original_price - $discount_amount;
            
            $_POST['price'] = number_format( $final_price, 2, '.', '' );
            $_POST['hotel_token'] = $token;
        }
    }
}