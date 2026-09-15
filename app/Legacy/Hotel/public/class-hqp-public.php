<?php

class HQP_Public {

    public function check_url_token() {
        if ( is_admin() ) {
            return;
        }

        $token = '';
        if ( isset( $_GET['promo'] ) && is_scalar( $_GET['promo'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_GET['promo'] ) );
        } elseif ( isset( $_GET['hotel_token'] ) && is_scalar( $_GET['hotel_token'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_GET['hotel_token'] ) );
        }

        if ( '' === $token ) {
            return;
        }

        $hotel_id = $this->hotel_id_from_token( $token );
        if ( ! $hotel_id ) {
            $this->clear_hotel_cookies();
            return;
        }

        $cookie_options = array(
            'expires'  => time() + DAY_IN_SECONDS,
            'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
            'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        );
        setcookie( 'hqp_hotel_token', $token, $cookie_options );
        setcookie( 'hqp_hotel_id', (string) $hotel_id, $cookie_options );
        $_COOKIE['hqp_hotel_token'] = $token;
        $_COOKIE['hqp_hotel_id']    = (string) $hotel_id;

        // Every generated hotel QR points to /reservas-hotel/. Resolve that page
        // first so an old transient or another page containing the shortcode
        // cannot redirect a valid QR somewhere else.
        $booking_page_id = 0;
        $booking_page = get_page_by_path( 'reservas-hotel', OBJECT, 'page' );
        if ( $booking_page && 'publish' === $booking_page->post_status && has_shortcode( (string) $booking_page->post_content, 'hqp_booking_form' ) ) {
            $booking_page_id = (int) $booking_page->ID;
        }

        if ( ! $booking_page_id ) {
            global $wpdb;
            $booking_page_id = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_content LIKE '%[hqp_booking_form]%' LIMIT 1" );
        }

        if ( ! $booking_page_id ) {
            return;
        }

        $booking_page_url = get_permalink( $booking_page_id );
        if ( ! $booking_page_url ) {
            return;
        }

        $target_url  = add_query_arg( 'promo', $token, $booking_page_url );
        $target_path = wp_parse_url( $target_url, PHP_URL_PATH );
        $current_path = isset( $_SERVER['REQUEST_URI'] )
            ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
            : '';

        if ( trim( (string) $target_path, '/' ) !== trim( (string) $current_path, '/' ) ) {
            wp_safe_redirect( $target_url );
            exit;
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
        $request_token = '';
        if ( isset( $_GET['promo'] ) && is_scalar( $_GET['promo'] ) ) {
            $request_token = sanitize_text_field( wp_unslash( $_GET['promo'] ) );
        } elseif ( isset( $_GET['hotel_token'] ) && is_scalar( $_GET['hotel_token'] ) ) {
            $request_token = sanitize_text_field( wp_unslash( $_GET['hotel_token'] ) );
        }

        $hotel_id = $this->get_authorized_hotel_id( $request_token );

        wp_enqueue_style( 'hqp-booking-css', HQP_PLUGIN_URL . 'public/css/hqp-booking.css', array(), HQP_VERSION );
        wp_enqueue_script( 'hqp-booking-js', HQP_PLUGIN_URL . 'public/js/hqp-booking.js', array( 'jquery' ), HQP_VERSION, true );

        $hotel_name = '';
        $hotel_address = '';

        if ( $hotel_id ) {
            $hotel_name = get_the_title( $hotel_id );
            $hotel_address = get_post_meta( $hotel_id, '_hqp_hotel_address', true );
        } else {
            return '<p>No se pudo identificar el hotel. Por favor, asegúrate de acceder a través del código QR correcto o contacta con recepción.</p>';
        }

        $hotel_token = '';
        if ( isset( $_GET['promo'] ) && is_scalar( $_GET['promo'] ) ) {
            $hotel_token = sanitize_text_field( wp_unslash( $_GET['promo'] ) );
        } elseif ( isset( $_GET['hotel_token'] ) && is_scalar( $_GET['hotel_token'] ) ) {
            $hotel_token = sanitize_text_field( wp_unslash( $_GET['hotel_token'] ) );
        } elseif ( isset( $_COOKIE['hqp_hotel_token'] ) && is_scalar( $_COOKIE['hqp_hotel_token'] ) ) {
            $hotel_token = sanitize_text_field( wp_unslash( $_COOKIE['hqp_hotel_token'] ) );
        }

        $route_locations = $this->hotel_route_locations();
        $route_locations_public = array();
        foreach ( $route_locations as $location_id => $location ) {
            $route_locations_public[ $location_id ] = array(
                'label'   => $location['label'],
                'address' => $location['address'],
            );
        }

        wp_localize_script( 'hqp-booking-js', 'hqpBookingVars', array(
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'wptb-booking-nonce' ),
            'hotel_id'        => (int) $hotel_id,
            'hotel_token'     => $hotel_token,
            'route_locations' => $route_locations_public,
            'actions'         => array(
                'pricing' => 'mt_hotel_get_fixed_pricing',
                'booking' => 'mt_hotel_create_booking',
            ),
        ) );

        ob_start();
        include HQP_PLUGIN_DIR . 'public/partials/hqp-booking-form.php';
        return ob_get_clean();
    }

    public function ajax_get_fixed_pricing() {
        check_ajax_referer( 'wptb-booking-nonce', 'security' );

        $hotel_id = isset( $_POST['hotel_id'] ) ? absint( $_POST['hotel_id'] ) : 0;
        $token = isset( $_POST['hotel_token'] ) && is_scalar( $_POST['hotel_token'] )
            ? sanitize_text_field( wp_unslash( $_POST['hotel_token'] ) )
            : '';
        $authorized_hotel_id = $this->get_authorized_hotel_id( $token );

        if ( ! $hotel_id || ! $authorized_hotel_id || $hotel_id !== $authorized_hotel_id ) {
            wp_send_json_error( array( 'message' => 'El código QR del hotel no es válido. Vuelve a escanear el QR de recepción.' ), 403 );
        }

        if ( isset( $_POST['date'], $_POST['time'] ) ) {
            $date_policy = \MeTransfers\Booking\BookingDatePolicy::validate(
                sanitize_text_field( wp_unslash( $_POST['date'] ) ),
                sanitize_text_field( wp_unslash( $_POST['time'] ) )
            );
            if ( empty( $date_policy['valid'] ) ) {
                wp_send_json_error( array( 'message' => $date_policy['error'] ) );
            }
        }

        $passengers = isset( $_POST['passengers'] ) ? max( 1, absint( $_POST['passengers'] ) ) : 1;
        $vehicles = \MeTransfers\HotelPortal\Services\HotelFixedPricing::availableVehicles( $hotel_id, $passengers );

        if ( empty( $vehicles ) ) {
            $active_count = count( \MeTransfers\HotelPortal\Services\HotelFixedPricing::activeVehicles() );
            error_log(
                sprintf(
                    'MeTransfers hotel pricing: no offerable vehicles for hotel %d, passengers %d; active fleet count %d.',
                    $hotel_id,
                    $passengers,
                    $active_count
                )
            );
            wp_send_json_error(
                array(
                    'code'    => 'no_hotel_vehicles',
                    'message' => 'No hay vehículos con tarifa fija disponibles para este número de pasajeros. Contacta con recepción si necesitas ayuda.',
                )
            );
        }

        wp_send_json_success( $vehicles );
    }

    public function ajax_create_booking() {
        check_ajax_referer( 'wptb-booking-nonce', 'security' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        
        $data = $_POST;
        
        // 1. Validate hotel, customer, vehicle and server-side fixed price.
        if ( empty( $data['hotel_id'] ) || empty( $data['vehicle_id'] ) || empty( $data['date'] ) || empty( $data['time'] ) ) {
            wp_send_json_error( array( 'message' => 'Faltan datos obligatorios.' ) );
        }

        $hotel_id   = absint( $data['hotel_id'] );
        $vehicle_id = absint( $data['vehicle_id'] );
        $token = isset( $data['hotel_token'] ) && is_scalar( $data['hotel_token'] )
            ? sanitize_text_field( wp_unslash( $data['hotel_token'] ) )
            : '';
        if ( ! $hotel_id || $hotel_id !== $this->get_authorized_hotel_id( $token ) ) {
            wp_send_json_error( array( 'message' => 'El código QR del hotel no es válido. Vuelve a escanear el QR de recepción.' ), 403 );
        }

        $customer_name  = isset( $data['customer_name'] ) && is_scalar( $data['customer_name'] ) ? sanitize_text_field( wp_unslash( $data['customer_name'] ) ) : '';
        $customer_email = isset( $data['customer_email'] ) && is_scalar( $data['customer_email'] ) ? sanitize_email( wp_unslash( $data['customer_email'] ) ) : '';
        $customer_phone = isset( $data['customer_phone'] ) && is_scalar( $data['customer_phone'] ) ? sanitize_text_field( wp_unslash( $data['customer_phone'] ) ) : '';
        if ( '' === $customer_name || ! is_email( $customer_email ) || '' === $customer_phone ) {
            wp_send_json_error( array( 'message' => 'Completa tu nombre, un email válido y tu teléfono.' ) );
        }

        $passengers = isset( $data['passengers'] ) ? max( 1, absint( $data['passengers'] ) ) : 1;
        $vehicle = \MeTransfers\HotelPortal\Services\HotelFixedPricing::activeVehicle( $vehicle_id );
        if ( ! $vehicle || (int) $vehicle->capacity < $passengers ) {
            wp_send_json_error( array( 'message' => 'El vehículo seleccionado no está disponible para este grupo.' ) );
        }

        $price_money = \MeTransfers\HotelPortal\Services\HotelFixedPricing::priceForVehicle( $hotel_id, $vehicle_id );
        if ( ! $price_money ) {
            wp_send_json_error( array( 'message' => 'Este vehículo no tiene una tarifa fija activa para el hotel.' ) );
        }

        $price       = $price_money->decimalFloat();
        $price_cents = $price_money->cents();

        $gateway = null;
        if ( $price_cents > 0 ) {
            $gateway = new \MeTransfers\Payments\Redsys\Gateway();
            if ( ! $gateway->is_configured() ) {
                $payment_status = $gateway->configuration_status();
                error_log( 'HQP Redsys configuration incomplete: ' . implode( ', ', $payment_status['missing'] ) );
                wp_send_json_error( array( 'message' => 'El pago no está configurado. Contacta con soporte.' ) );
                return;
            }
        }

        $date = isset( $data['date'] ) && is_scalar( $data['date'] ) ? sanitize_text_field( wp_unslash( $data['date'] ) ) : '';
        $time = isset( $data['time'] ) && is_scalar( $data['time'] ) ? sanitize_text_field( wp_unslash( $data['time'] ) ) : '';

        $language = \MeTransfers\Booking\I18n::language();
        $date_policy = \MeTransfers\Booking\BookingDatePolicy::validate( $date, $time );
        if ( empty( $date_policy['valid'] ) ) {
            wp_send_json_error( array( 'message' => $date_policy['error'] ) );
            return;
        }

        /*
         * Hotel QR bookings use a fixed hotel fare and a closed list of endpoints.
         * Rebuild the route on the server instead of trusting posted address text or
         * requiring Google Geocoding to approve an already-authorized hotel route.
         * This also prevents a guest from replacing the destination while keeping
         * the hotel's fixed price.
         */
        $hotel_route = $this->resolve_hotel_route( $hotel_id, $data );
        if ( empty( $hotel_route['valid'] ) ) {
            wp_send_json_error(
                array(
                    'code'    => 'invalid_hotel_route',
                    'message' => isset( $hotel_route['message'] ) ? $hotel_route['message'] : 'Selecciona nuevamente el origen o destino del traslado.',
                ),
                400
            );
            return;
        }

        $origin      = $hotel_route['origin'];
        $destination = $hotel_route['destination'];

        $route = \MeTransfers\Booking\RouteDistance::calculate( $origin, $destination );
        if ( ! empty( $route['error'] ) ) {
            error_log( 'HQP route distance failed: ' . sanitize_text_field( (string) $route['error'] ) );
            wp_send_json_error(
                array(
                    'code'    => 'route_distance_unavailable',
                    'message' => 'No se pudo calcular la distancia de la ruta. Revisa el origen y el destino o contacta con soporte.',
                )
            );
            return;
        }

        $distance_km = isset( $route['distance_km'] ) ? (float) $route['distance_km'] : 0.0;
        $duration_minutes = isset( $route['duration_minutes'] ) ? (int) $route['duration_minutes'] : 0;
        if ( $distance_km <= 0 ) {
            error_log( 'HQP route distance failed: provider returned a non-positive distance.' );
            wp_send_json_error(
                array(
                    'code'    => 'route_distance_unavailable',
                    'message' => 'No se pudo calcular la distancia de la ruta. Revisa el origen y el destino o contacta con soporte.',
                )
            );
            return;
        }
        
        $booking_data = array_merge( array(
            'booking_date'   => $date,
            'booking_time'   => $time,
            'origin'         => $origin,
            'destination'    => $destination,
            'distance_km'    => $distance_km,
            'duration_minutes' => $duration_minutes,
            'price'          => $price,
            'price_cents'    => $price_cents,
            'customer_name'  => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'passengers'     => $passengers,
            'flight_number'  => isset( $data['flight_number'] ) && is_scalar( $data['flight_number'] ) ? sanitize_text_field( wp_unslash( $data['flight_number'] ) ) : '',
            'notes'          => isset( $data['notes'] ) && is_scalar( $data['notes'] ) ? sanitize_textarea_field( wp_unslash( $data['notes'] ) ) : '',
            'vehicle_id'     => $vehicle_id,
            'trip_type'      => 'one_way',
            'status'         => 'pending_payment',
            'payment_method' => 'redsys',
            'booking_locale' => $language,
            'created_at'     => current_time( 'mysql' ),
        ), \MeTransfers\HotelPortal\Services\HotelBookingAttribution::forQrBooking(
            $hotel_id,
            get_post_meta( $hotel_id, '_hqp_token', true )
        ) );

        $format_db = array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' );

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

    private function clear_hotel_cookies() {
        $options = array(
            'expires'  => time() - HOUR_IN_SECONDS,
            'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
            'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        );
        setcookie( 'hqp_hotel_token', '', $options );
        setcookie( 'hqp_hotel_id', '', $options );
        unset( $_COOKIE['hqp_hotel_token'], $_COOKIE['hqp_hotel_id'] );
    }

    private function get_authorized_hotel_id( $explicit_token = '' ) {
        $token = is_scalar( $explicit_token ) ? sanitize_text_field( (string) $explicit_token ) : '';
        if ( '' === $token && isset( $_COOKIE['hqp_hotel_token'] ) && is_scalar( $_COOKIE['hqp_hotel_token'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_COOKIE['hqp_hotel_token'] ) );
        }
        if ( '' === $token ) {
            return 0;
        }

        return $this->hotel_id_from_token( $token );
    }

    private function hotel_id_from_token( $token ) {
        $token = is_scalar( $token ) ? sanitize_text_field( (string) $token ) : '';
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

    /**
     * Closed catalogue of destinations offered by the hotel QR flow.
     * Values are canonical server-side addresses; the browser only sends the ID.
     */
    private function hotel_route_locations() {
        $locations = array(
            'airport_bcn' => array(
                'label'   => 'Aeropuerto Barcelona-El Prat (BCN)',
                'address' => 'Aeropuerto Josep Tarradellas Barcelona-El Prat (BCN), 08820 El Prat de Llobregat, Barcelona, España',
                'aliases' => array(
                    'Aerop. Josep Tarradellas Barcelona-El Prat (BCN)',
                    'Aeropuerto Barcelona-El Prat (BCN)',
                ),
            ),
            'barcelona_sants' => array(
                'label'   => 'Estación Barcelona Sants',
                'address' => 'Barcelona Sants, Plaça dels Països Catalans, 1-7, 08014 Barcelona, España',
                'aliases' => array(
                    'Estación de Sants (Barcelona)',
                    'Estación Barcelona Sants',
                    'Barcelona Sants',
                ),
            ),
            'barcelona_port' => array(
                'label'   => 'Puerto de Barcelona · Cruceros',
                'address' => 'Moll Adossat, Port de Barcelona, 08039 Barcelona, España',
                'aliases' => array(
                    'Puerto de Barcelona (Terminal Cruceros)',
                    'Puerto de Barcelona · Cruceros',
                    'Port de Barcelona',
                ),
            ),
        );

        return (array) apply_filters( 'hqp_hotel_route_locations', $locations );
    }

    private function normalize_route_value( $value ) {
        $value = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES, 'UTF-8' );
        $value = preg_replace( '/\s+/u', ' ', trim( $value ) );
        $value = function_exists( 'remove_accents' ) ? remove_accents( $value ) : $value;
        return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
    }

    private function resolve_hotel_route( $hotel_id, $data ) {
        $hotel_address = sanitize_text_field( (string) get_post_meta( $hotel_id, '_hqp_hotel_address', true ) );
        if ( '' === trim( $hotel_address ) ) {
            return array(
                'valid'   => false,
                'message' => 'El hotel no tiene una dirección configurada. Contacta con recepción.',
            );
        }

        $direction = isset( $data['route_direction'] ) && is_scalar( $data['route_direction'] )
            ? sanitize_key( wp_unslash( $data['route_direction'] ) )
            : '';
        $location_id = isset( $data['route_location'] ) && is_scalar( $data['route_location'] )
            ? sanitize_key( wp_unslash( $data['route_location'] ) )
            : '';

        $locations = $this->hotel_route_locations();
        if ( isset( $locations[ $location_id ] ) ) {
            $location = $locations[ $location_id ];
        } else {
            // Backward compatibility for a cached 4.0.2 form: match only known
            // canonical/legacy values, never arbitrary posted addresses.
            $posted_origin = isset( $data['origin'] ) && is_scalar( $data['origin'] )
                ? sanitize_text_field( wp_unslash( $data['origin'] ) )
                : '';
            $posted_destination = isset( $data['destination'] ) && is_scalar( $data['destination'] )
                ? sanitize_text_field( wp_unslash( $data['destination'] ) )
                : '';
            $hotel_normalized = $this->normalize_route_value( $hotel_address );

            if ( '' === $direction ) {
                if ( $this->normalize_route_value( $posted_origin ) === $hotel_normalized ) {
                    $direction = 'from_hotel';
                    $candidate = $posted_destination;
                } elseif ( $this->normalize_route_value( $posted_destination ) === $hotel_normalized ) {
                    $direction = 'to_hotel';
                    $candidate = $posted_origin;
                } else {
                    $candidate = '';
                }
            } else {
                $candidate = 'from_hotel' === $direction ? $posted_destination : $posted_origin;
            }

            $candidate_normalized = $this->normalize_route_value( $candidate );
            $location = null;
            foreach ( $locations as $known_location ) {
                $allowed_values = array_merge(
                    array( $known_location['label'], $known_location['address'] ),
                    isset( $known_location['aliases'] ) ? (array) $known_location['aliases'] : array()
                );
                foreach ( $allowed_values as $allowed_value ) {
                    if ( $candidate_normalized === $this->normalize_route_value( $allowed_value ) ) {
                        $location = $known_location;
                        break 2;
                    }
                }
            }
        }

        if ( ! is_array( $location ) || empty( $location['address'] ) ) {
            return array(
                'valid'   => false,
                'message' => 'El destino seleccionado no pertenece a las rutas habilitadas para este hotel.',
            );
        }

        if ( ! in_array( $direction, array( 'from_hotel', 'to_hotel' ), true ) ) {
            return array(
                'valid'   => false,
                'message' => 'Selecciona si el traslado sale del hotel o llega al hotel.',
            );
        }

        $external_address = sanitize_text_field( (string) $location['address'] );
        return array(
            'valid'       => true,
            'origin'      => 'from_hotel' === $direction ? $hotel_address : $external_address,
            'destination' => 'from_hotel' === $direction ? $external_address : $hotel_address,
            'direction'   => $direction,
        );
    }

    private function route_uses_hotel_address( $origin, $destination, $hotel_address ) {
        $normalize = static function ( $value ) {
            $value = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES, 'UTF-8' );
            $value = preg_replace( '/\s+/u', ' ', trim( $value ) );
            return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
        };

        $hotel = $normalize( $hotel_address );
        return '' !== $hotel && ( $normalize( $origin ) === $hotel || $normalize( $destination ) === $hotel );
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
