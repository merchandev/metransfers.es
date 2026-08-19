<?php

class HQP_Public {

    public function check_url_token() {
        if ( is_admin() ) return;
        
        $token = '';
        if ( isset( $_GET['promo'] ) && ! empty( $_GET['promo'] ) ) {
             $token = sanitize_text_field( wp_unslash( $_GET['promo'] ) );
        } elseif ( isset( $_GET['hotel_token'] ) && ! empty( $_GET['hotel_token'] ) ) {
             $token = sanitize_text_field( wp_unslash( $_GET['hotel_token'] ) );
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
                
                $cookie_options = array(
                    'expires'  => time() + DAY_IN_SECONDS,
                    'path'     => COOKIEPATH ?: '/',
                    'domain'   => COOKIE_DOMAIN,
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                );
                setcookie( 'hqp_hotel_token', $token, $cookie_options );
                setcookie( 'hqp_hotel_id', (string) $hotel_id, $cookie_options );
                $_COOKIE['hqp_hotel_token'] = $token;
                $_COOKIE['hqp_hotel_id'] = (string) $hotel_id;

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
                        $target_path = parse_url( $target_url, PHP_URL_PATH );
                        $current_path = isset( $_SERVER['REQUEST_URI'] )
                            ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
                            : '';

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
        if ( 'hotel' !== \MeTransfers\Core\Assets::booking_phase() ) {
            return;
        }

        $token = '';
        if ( isset( $_GET['promo'] ) ) $token = sanitize_text_field( wp_unslash( $_GET['promo'] ) );
        elseif ( isset( $_GET['hotel_token'] ) ) $token = sanitize_text_field( wp_unslash( $_GET['hotel_token'] ) );
        elseif ( isset( $_COOKIE['hqp_hotel_token'] ) ) $token = sanitize_text_field( wp_unslash( $_COOKIE['hqp_hotel_token'] ) );

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
        $hotel_id = $this->get_authorized_hotel_id();

        if ( ! $hotel_id && isset( $_GET['promo'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_GET['promo'] ) );
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

        wp_enqueue_style( 'hqp-booking-css', HQP_PLUGIN_URL . 'public/css/hqp-booking.css', array(), HQP_VERSION );
        wp_enqueue_script( 'hqp-booking-js', HQP_PLUGIN_URL . 'public/js/hqp-booking.js', array( 'jquery', 'wptb-booking-js' ), HQP_VERSION, true );

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
        include HQP_PLUGIN_DIR . 'public/partials/hqp-booking-form.php';
        return ob_get_clean();
    }

    public function ajax_get_fixed_pricing() {
        check_ajax_referer( 'wptb-booking-nonce', 'security' );

        $hotel_id = isset( $_POST['hotel_id'] ) ? absint( $_POST['hotel_id'] ) : 0;
        $authorized_hotel_id = $this->get_authorized_hotel_id();
        
        if ( ! $hotel_id || $hotel_id !== $authorized_hotel_id ) {
            wp_send_json_error( array( 'message' => 'El token del hotel no es válido.' ) );
            return;
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

                if ( ! empty( $fixed_price ) ) {
                    $discount_percent = (int) get_post_meta( $hotel_id, '_hqp_discount_percent', true );
                    try {
                        $price_money = $this->discounted_money( $fixed_price, $discount_percent );
                    } catch ( \InvalidArgumentException $exception ) {
                        error_log( "WPTB HOTEL VEHICLE EXCLUDED: ID $vehicle_id - Invalid fixed price." );
                        continue;
                    }
                    if ( $price_money->cents() <= 0 ) {
                        continue;
                    }
                    
                    $vehicles[] = array(
                        'id'          => $vehicle_id,
                        'name'        => $v->name,
                        'description' => isset($v->description) ? $v->description : '',
                        'capacity'    => $v->capacity,
                        'price'       => $price_money->decimal(),
                        'price_cents' => $price_money->cents(),
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
            return;
        }

        $hotel_id = absint( $data['hotel_id'] );
        $vehicle_id = absint( $data['vehicle_id'] );
        if ( $hotel_id !== $this->get_authorized_hotel_id() ) {
            wp_send_json_error( array( 'message' => 'El token del hotel no es válido.' ) );
            return;
        }

        $passengers = isset( $data['passengers'] ) ? max( 1, absint( $data['passengers'] ) ) : 1;
        $vehicle = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, capacity FROM {$wpdb->prefix}wptb_hotel_vehicles WHERE id = %d AND is_active = 1",
                $vehicle_id
            )
        );
        if ( ! $vehicle || (int) $vehicle->capacity < $passengers ) {
            wp_send_json_error( array( 'message' => 'El vehículo seleccionado no está disponible para este grupo.' ) );
            return;
        }

        $price = get_post_meta( $hotel_id, '_hqp_price_vehicle_' . $vehicle_id, true );

        $discount_percent = (int) get_post_meta( $hotel_id, '_hqp_discount_percent', true );
        try {
            $price_money = $this->discounted_money( $price, $discount_percent );
        } catch ( \InvalidArgumentException $exception ) {
            wp_send_json_error( array( 'message' => 'Precio no válido para este vehículo.' ) );
            return;
        }
        if ( $price_money->cents() <= 0 ) {
            wp_send_json_error( array( 'message' => 'Precio no válido para este vehículo.' ) );
            return;
        }

        $price = $price_money->decimalFloat();
        $price_cents = $price_money->cents();

        $gateway = null;
        if ( $price_cents > 0 ) {
            $gateway = new \MeTransfers\Payments\Redsys\Gateway();
            if ( ! $gateway->is_configured() ) {
                wp_send_json_error( array( 'message' => 'El pago no está configurado. Contacta con soporte.' ) );
                return;
            }
        }

        $date = sanitize_text_field( $data['date'] );
        $time = sanitize_text_field( $data['time'] );
        
        $origin = sanitize_text_field( $data['origin'] );
        $destination = sanitize_text_field( $data['destination'] );

        $language = \MeTransfers\Booking\I18n::language();
        $date_policy = \MeTransfers\Booking\BookingDatePolicy::validate( $date, $time );
        if ( empty( $date_policy['valid'] ) ) {
            wp_send_json_error( array( 'message' => $date_policy['error'] ) );
            return;
        }
        $area_policy = \MeTransfers\Booking\ServiceAreaPolicy::validateRoute( $origin, $destination );
        if ( empty( $area_policy['valid'] ) ) {
            wp_send_json_error( array( 'message' => $area_policy['error'] ) );
            return;
        }
        
        $distance_km = 0;
        $duration_minutes = 0;
        $route = \MeTransfers\Booking\RouteDistance::calculate( $origin, $destination );
        if ( empty( $route['error'] ) ) {
            $distance_km = (float) $route['distance_km'];
            $duration_minutes = (int) $route['duration_minutes'];
        }
        
        $booking_data = array(
            'booking_date'   => $date,
            'booking_time'   => $time,
            'origin'         => $origin,
            'destination'    => $destination,
            'distance_km'    => $distance_km,
            'duration_minutes' => $duration_minutes,
            'price'          => $price,
            'price_cents'    => $price_cents,
            'customer_name'  => sanitize_text_field( $data['customer_name'] ),
            'customer_email' => sanitize_email( $data['customer_email'] ),
            'customer_phone' => sanitize_text_field( $data['customer_phone'] ),
            'passengers'     => $passengers,
            'flight_number'  => sanitize_text_field( $data['flight_number'] ),
            'notes'          => sanitize_textarea_field( $data['notes'] ),
            'vehicle_id'     => $vehicle_id,
            'trip_type'      => 'one_way',
            'status'         => 'pending_payment',
            'payment_method' => 'redsys',
            'booking_locale' => $language,
            'created_at'     => current_time( 'mysql' ),
            'hotel_token'    => get_post_meta( $hotel_id, '_hqp_token', true ),
        );

        $format_db = array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

        $result = $wpdb->insert( $table_name, $booking_data, $format_db );
        $booking_id = $wpdb->insert_id;

        if ( ! $result || ! $booking_id ) {
            error_log( 'HQP booking insert failed: ' . $wpdb->last_error );
            wp_send_json_error( array( 'message' => 'No se pudo guardar la reserva. Contacta con soporte.' ) );
            return;
        }
        
        // Order ID para Getnet/Redsys: el booking_id ya es >= 10000 gracias al AUTO_INCREMENT.
        $order_id = str_pad( $booking_id, 12, '0', STR_PAD_LEFT );
        
        $wpdb->update( 
            $table_name, 
            array( 'payment_intent_id' => $order_id ), 
            array( 'id' => $booking_id ) 
        );

        $url_ok = \MeTransfers\Payments\Redsys\Gateway::confirmation_url( $order_id );

        if ( $price_cents <= 0 ) {
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'payment_method' => 'complimentary',
                ),
                array( 'id' => $booking_id ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );

            if ( ! \MeTransfers\Booking\BookingEvents::paid( $booking_id ) ) {
                wp_send_json_error( array( 'message' => 'La reserva se guardó, pero no se pudo programar su confirmación.' ) );
                return;
            }

            wp_send_json_success( array( 'redirect' => $url_ok ) );
            return;
        }

        try {
            $payment = $gateway->generate_payment_form(
                $booking_id,
                $price_cents,
                $order_id,
                $booking_data['customer_name']
            );

            // Persist the pending event only after a valid payment form exists.
            if ( ! \MeTransfers\Booking\BookingEvents::pending( $booking_id ) ) {
                wp_send_json_error( array( 'message' => 'No se pudo programar la notificación de la reserva.' ) );
                return;
            }

            wp_send_json_success( array(
                'url' => $payment['url'],
                'ds_signature_version' => $payment['version'],
                'ds_merchant_parameters' => $payment['params'],
                'ds_signature' => $payment['signature']
            ));

        } catch ( \Throwable $e ) {
            error_log( 'HQP Redsys payment creation failed: ' . $e->getMessage() );
            wp_send_json_error( array( 'message' => 'No se pudo iniciar el pago. Revisa la configuración de Redsys o contacta con soporte.' ) );
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

    private function get_authorized_hotel_id() {
        if ( empty( $_COOKIE['hqp_hotel_token'] ) ) {
            return 0;
        }

        $token = sanitize_text_field( wp_unslash( $_COOKIE['hqp_hotel_token'] ) );
        if ( '' === $token ) {
            return 0;
        }

        $query = new WP_Query(
            array(
                'post_type'      => 'hotel_partner',
                'post_status'    => 'publish',
                'meta_key'       => '_hqp_token',
                'meta_value'     => $token,
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            )
        );

        return $query->have_posts() ? absint( $query->posts[0] ) : 0;
    }

    private function discounted_money( $price, $discount_percent ) {
        $money = \MeTransfers\Pricing\Money::fromDecimal( $price );
        $discount_percent = (int) $discount_percent;
        if ( $discount_percent <= 0 || $discount_percent > 100 ) {
            return $money;
        }

        $discounted_cents = intdiv( ( $money->cents() * ( 100 - $discount_percent ) ) + 50, 100 );
        return new \MeTransfers\Pricing\Money( $discounted_cents );
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
        
        try {
            return $this->discounted_money( $price, $discount_percent )->decimal();
        } catch ( \InvalidArgumentException $exception ) {
            return $price;
        }
    }

    public function intercept_booking_submission() {
        if ( ! isset( $_POST['price'] ) ) return;

        $token = '';
        if ( isset( $_COOKIE['hqp_hotel_token'] ) ) {
            $token = sanitize_text_field( $_COOKIE['hqp_hotel_token'] );
        }
        
        $discount_percent = $this->get_discount_from_token( $token );
        
        if ( $discount_percent > 0 ) {
            try {
                $_POST['price'] = $this->discounted_money( wp_unslash( $_POST['price'] ), $discount_percent )->decimal();
            } catch ( \InvalidArgumentException $exception ) {
                return;
            }
            $_POST['hotel_token'] = $token;
        }
    }
}
