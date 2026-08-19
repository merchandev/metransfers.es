<?php

class Unified_Integration {

    public function run() {
        // 1. Cookie & Token Logic (from Hotel Plugin)
        add_action( 'init', array( $this, 'check_url_token' ) );
        
        // 2. Frontend Assets - DESHABILITADO PARA NO MEZCLAR CON RESERVAS NORMALES
        // add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // 3. AJAX Override - DESHABILITADO
        // add_action( 'wp_ajax_wptb_save_booking', array( $this, 'intercept_save_booking' ), 0 );
        // add_action( 'wp_ajax_nopriv_wptb_save_booking', array( $this, 'intercept_save_booking' ), 0 );

        // 5. Payment Intent Override - DESHABILITADO
        // add_action( 'wp_ajax_wptb_create_payment_intent', array( $this, 'intercept_create_payment_intent' ), 0 );
        // add_action( 'wp_ajax_nopriv_wptb_create_payment_intent', array( $this, 'intercept_create_payment_intent' ), 0 );

        // 6. Confirm Payment Intercept
        add_action( 'wp_ajax_wptb_confirm_payment', array( $this, 'intercept_confirm_payment' ), 0 );
        add_action( 'wp_ajax_nopriv_wptb_confirm_payment', array( $this, 'intercept_confirm_payment' ), 0 );
    }

    /**
     * Check URL for Hotel Token and set Cookies
     */
    public function check_url_token() {
        if ( is_admin() ) return;
        
        if ( isset( $_GET['hotel_token'] ) && ! empty( $_GET['hotel_token'] ) ) {
            $token = sanitize_text_field( $_GET['hotel_token'] );
            
            // Find Hotel Key by Token
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
                $discount = get_post_meta( $hotel_id, '_hqp_discount_percent', true );
                $hotel_name = get_the_title( $hotel_id );
                
                // Set Cookie for 24 hours
                setcookie( 'hqp_hotel_token', $token, time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
                setcookie( 'hqp_hotel_discount', $discount, time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
                setcookie( 'hqp_hotel_id', $hotel_id, time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
                setcookie( 'hqp_hotel_name', $hotel_name, time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
            }
        }
    }

    /**
     * Enqueue JS for Discount Display
     */
    public function enqueue_scripts() {
        // Check for discount in Cookie or GET
        $discount = 0;
        $active = false;
        $hotel_name = '';

        if ( isset( $_COOKIE['hqp_hotel_discount'] ) ) {
            $discount = floatval( $_COOKIE['hqp_hotel_discount'] );
            $hotel_name = isset($_COOKIE['hqp_hotel_name']) ? $_COOKIE['hqp_hotel_name'] : 'Hotel';
            $active = true;
        } elseif ( isset( $_GET['hotel_token'] ) ) {
            // Instant check for first load
             $token = sanitize_text_field( $_GET['hotel_token'] );
             $args = array('post_type'=>'hotel_partner', 'meta_key'=>'_hqp_token', 'meta_value'=>$token, 'posts_per_page'=>1);
             $q = new WP_Query($args);
             if($q->have_posts()) {
                 $discount = floatval(get_post_meta($q->posts[0]->ID, '_hqp_discount_percent', true));
                 $hotel_name = get_the_title($q->posts[0]->ID);
                 $active = true;
             }
        }

        if ( $active && $discount > 0 ) {
            wp_enqueue_script( 'cbp-unified-discount', CBP_PLUGIN_URL . 'assets/js/unified-discount.js', array( 'jquery', 'wptb-booking-js' ), '1.0.2', true );
            wp_localize_script( 'cbp-unified-discount', 'cbp_vars', array(
                'discount_percent' => $discount,
                'hotel_name' => $hotel_name,
                'hotel_token' => isset($token) ? $token : (isset($_COOKIE['hqp_hotel_token']) ? $_COOKIE['hqp_hotel_token'] : '')
            ));
        }
    }

    /**
     * OVERRIDE AJAX: Save Booking with Discount Validation
     */
    public function intercept_save_booking() {
        $discount_percent = 0;
        if ( isset( $_COOKIE['hqp_hotel_discount'] ) ) {
            $discount_percent = floatval( $_COOKIE['hqp_hotel_discount'] );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        $data = $_POST;
        
        $date = sanitize_text_field( $data['date'] );
        $time = sanitize_text_field( $data['time'] );
        $origin = sanitize_text_field( $data['origin'] );
        $destination = sanitize_text_field( $data['destination'] );
        $distance = floatval( $data['distance'] );
        $duration_minutes = isset( $data['duration_minutes'] ) ? absint( $data['duration_minutes'] ) : 0;
        $vehicle_id = isset( $data['vehicle_id'] ) ? absint( $data['vehicle_id'] ) : 0;
        $trip_type = isset( $data['trip_type'] ) ? sanitize_text_field( $data['trip_type'] ) : 'one_way';
        $price = floatval( $data['price'] );
        $fullName = sanitize_text_field( $data['fullName'] );
        
        if ( ! $vehicle_id || !class_exists('WPTB_Vehicle_Manager') ) {
            wp_send_json_error( array( 'message' => 'Vehículo no válido' ) );
        }
        
        $vehicle = WPTB_Vehicle_Manager::get_vehicle( $vehicle_id );
        if ( ! $vehicle ) {
            wp_send_json_error( array( 'message' => 'Vehículo no válido' ) );
        }
        
        $cal_result = WPTB_Pricing::calculate_price( $vehicle_id, $distance, $trip_type, $duration_minutes );
        if ( isset( $cal_result['error'] ) ) {
             wp_send_json_error( array( 'message' => $cal_result['error'] ) );
        }
        
        $base_price = $cal_result['price'];
        $min_allowed = $base_price;
        if ( $discount_percent > 0 ) {
            $min_allowed = $base_price * (1 - ($discount_percent / 100));
        }
        
        if ( $price < ($min_allowed - 0.1) ) {
            wp_send_json_error( array( 
                'message' => "Error de validación de precio. Esperado: €" . number_format($min_allowed, 2) . " (Desc: $discount_percent%), Recibido: €" . number_format($price, 2)
            ));
        }

        $wpdb->insert( 
            $table_name, 
            array( 
                'booking_date' => $date,
                'booking_time' => $time,
                'origin' => $origin,
                'destination' => $destination,
                'distance_km' => $distance,
                'duration_minutes' => $duration_minutes,
                'price' => $price,
                'customer_name' => $fullName,
                'customer_email' => sanitize_email($data['email']),
                'customer_phone' => sanitize_text_field($data['phone']),
                'passengers' => intval($data['passengers']),
                'suitcases' => intval($data['suitcases']),
                'carry_ons' => intval($data['carryOns']),
                'flight_number' => sanitize_text_field($data['flight']),
                'notes' => sanitize_textarea_field($data['notes']),
                'vehicle_id' => $vehicle_id,
                'trip_type' => $trip_type,
                'status' => 'pending'
            ),
            array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
        );

        $inserted_id = $wpdb->insert_id;
        do_action( 'wptb_new_booking_created', $inserted_id );

        wp_send_json_success( array( 
            'message' => 'Reserva guardada correctamente.', 
            'booking_id' => $inserted_id 
        ));
    }

    public function intercept_create_payment_intent() {
        try {
            check_ajax_referer( 'wptb-booking-nonce', 'security' ); // Security Check
            // Get booking data
            $booking_data = json_decode( stripslashes( $_POST['booking_data'] ), true );
            
            if ( ! $booking_data || ! isset( $booking_data['vehicle_id'] ) ) {
                wp_send_json_error( array( 'message' => 'Datos de reserva inválidos' ) );
                return;
            }

            // --- SERVER SIDE RECALCULATION & DISCOUNT ---
            // 1. Calculate Base Price
            $vehicle_id = absint( $booking_data['vehicle_id'] );
            $distance_km = floatval( $booking_data['distance_km'] );
            $trip_type = isset( $booking_data['trip_type'] ) ? $booking_data['trip_type'] : 'one_way';
            $duration_minutes = isset( $booking_data['duration_minutes'] ) ? absint( $booking_data['duration_minutes'] ) : 0;
            
            $cal_result = WPTB_Pricing::calculate_price( $vehicle_id, $distance_km, $trip_type, $duration_minutes );
            
            if ( isset( $cal_result['error'] ) ) {
                 wp_send_json_error( array( 'message' => $cal_result['error'] ) );
                 return;
            }
            
            $final_price = $cal_result['price']; // Base Price

            // 2. Check and Apply Discount
            $discount_percent = 0;
            if ( isset( $_COOKIE['hqp_hotel_discount'] ) ) {
                $discount_percent = floatval( $_COOKIE['hqp_hotel_discount'] );
            }
            
            // Fallback: Check Token in Booking Data (Robustness for when Cookies fail)
            if ( $discount_percent <= 0 && isset( $booking_data['hotel_token'] ) && ! empty( $booking_data['hotel_token'] ) ) {
                 $token = sanitize_text_field( $booking_data['hotel_token'] );
                 // Look up hotel by token
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
                    $discount_percent = floatval( get_post_meta( $hotel_id, '_hqp_discount_percent', true ) );
                }
            }
            
            if ( $discount_percent > 0 ) {
                $discount_val = $final_price * ($discount_percent / 100);
                $final_price = $final_price - $discount_val;
                // Force formatted float check
                $final_price = round($final_price, 2);
            }
            // ---------------------------------------------
            
            // Get Stripe secret key
            $stripe_secret = get_option( 'wptb_stripe_secret_key', 'sk_test_YOUR_TEST_SECRET_KEY' );
            
            // Calculate amount in cents
            $amount = intval( $final_price * 100 );
            
            // Create Payment Intent via Stripe API
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.stripe.com/v1/payment_intents',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(array(
                    'amount' => $amount,
                    'currency' => 'eur',
                    'automatic_payment_methods[enabled]' => 'true',
                    'description' => 'Reserva Metransfers: ' . $booking_data['origin'] . ' -> ' . $booking_data['destination'],
                    'metadata[vehicle]' => $booking_data['vehicle_name'],
                    'metadata[customer_email]' => isset($booking_data['customer_email']) ? $booking_data['customer_email'] : '',
                    'metadata[customer_phone]' => isset($booking_data['customer_phone']) ? $booking_data['customer_phone'] : '',
                    'metadata[discount_applied]' => $discount_percent . '%'
                )),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $stripe_secret,
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if ($err) {
                wp_send_json_error( array( 'message' => 'Error de conexión: ' . $err ) );
                return;
            }
            
            $result = json_decode($response, true);
            
            if ( isset( $result['error'] ) ) {
                wp_send_json_error( array( 'message' => $result['error']['message'] ) );
                return;
            }
            
            // Save pending booking to database
            global $wpdb;
            $table_name = $wpdb->prefix . 'wptb_bookings';
            
            $wpdb->insert(
                $table_name,
                array(
                    'booking_date' => $booking_data['date'],
                    'booking_time' => $booking_data['time'],
                    'origin' => sanitize_text_field( $booking_data['origin'] ),
                    'destination' => sanitize_text_field( $booking_data['destination'] ),
                    'distance_km' => floatval( $booking_data['distance_km'] ),
                    'vehicle_id' => intval( $booking_data['vehicle_id'] ),
                    'price' => floatval( $final_price ),
                    'customer_name' => isset($booking_data['customer_name']) ? sanitize_text_field( $booking_data['customer_name'] ) : '',
                    'customer_email' => isset($booking_data['customer_email']) ? sanitize_email( $booking_data['customer_email'] ) : '',
                    'customer_phone' => isset($booking_data['customer_phone']) ? sanitize_text_field( $booking_data['customer_phone'] ) : '',
                    'passengers' => isset($booking_data['passengers']) ? intval( $booking_data['passengers'] ) : 1,
                    'suitcases' => isset($booking_data['suitcases']) ? intval( $booking_data['suitcases'] ) : 0,
                    'carry_ons' => isset($booking_data['carry_ons']) ? intval( $booking_data['carry_ons'] ) : 0,
                    'flight_number' => isset($booking_data['flight_number']) ? sanitize_text_field( $booking_data['flight_number'] ) : '',
                    'notes' => isset($booking_data['notes']) ? sanitize_textarea_field( $booking_data['notes'] ) : '',
                    'status' => 'pending_payment',
                    'payment_method' => 'stripe',
                    'payment_intent_id' => $result['id'],
                    'created_at' => current_time( 'mysql' )
                ),
                array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
            );
            
            $booking_id = $wpdb->insert_id;
            
            wp_send_json_success( array(
                'clientSecret' => $result['client_secret'],
                'booking_id' => $booking_id,
                'server_calculated_price' => $final_price,
                'discount_percent' => $discount_percent,
                'original_price' => isset($cal_result['price']) ? $cal_result['price'] : $final_price
            ) );
            
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Clear Hotel Cookies on Payment Confirmation
     */
    public function intercept_confirm_payment() {
        // We just clear cookies and let the Main Plugin handle the logic (Priority 0 runs first)
        
        if ( isset( $_COOKIE['hqp_hotel_token'] ) || isset( $_COOKIE['hqp_hotel_discount'] ) ) {
            setcookie( 'hqp_hotel_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
            setcookie( 'hqp_hotel_discount', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
            setcookie( 'hqp_hotel_id', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
            setcookie( 'hqp_hotel_name', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
            
            // Also unset for current request just in case
            unset( $_COOKIE['hqp_hotel_token'] );
            unset( $_COOKIE['hqp_hotel_discount'] );
        }
    }
}
