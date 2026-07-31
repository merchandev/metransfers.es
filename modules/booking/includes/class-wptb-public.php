<?php

class WPTB_Public {

    public $last_mail_error = '';

    public function __construct() {
        // Load Redsys Helper
        require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-redsys.php';

        // WC Hooks
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_cart_totals' ), 10, 1 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );
        add_filter( 'woocommerce_checkout_get_value', array( $this, 'prefill_checkout_fields' ), 10, 2 );
        add_action( 'woocommerce_thankyou', array( $this, 'handle_woocommerce_booking_complete' ), 10, 1 );
        
        // Redsys Actions
        add_action( 'wp_ajax_wptb_initiate_redsys', array( $this, 'initiate_redsys_payment' ) );
        add_action( 'wp_ajax_nopriv_wptb_initiate_redsys', array( $this, 'initiate_redsys_payment' ) );
        add_action( 'init', array( $this, 'listen_redsys_ipn' ) );
        add_action( 'template_redirect', array( $this, 'check_return_url_payment_force' ) ); // FORCE CHECK ON RETURN
        add_action( 'wptb_new_booking_created', array( $this, 'notify_new_booking' ) );

        // ===== BOOKING AJAX ACTIONS =====
        // Get vehicles list
        add_action( 'wp_ajax_wptb_get_vehicles', array( $this, 'ajax_get_vehicles' ) );
        add_action( 'wp_ajax_nopriv_wptb_get_vehicles', array( $this, 'ajax_get_vehicles' ) );

        // Create booking
        add_action( 'wp_ajax_wptb_create_booking', array( $this, 'ajax_create_booking' ) );
        add_action( 'wp_ajax_nopriv_wptb_create_booking', array( $this, 'ajax_create_booking' ) );

        // Get pricing
        add_action( 'wp_ajax_wptb_get_pricing', array( $this, 'ajax_get_pricing' ) );
        add_action( 'wp_ajax_nopriv_wptb_get_pricing', array( $this, 'ajax_get_pricing' ) );

        add_action( 'wp_mail_failed', array( $this, 'capture_mail_error' ) );

        // DB MIGRATION TRIGGER (AUTO-REMOVAL CANDIDATE)
        if ( is_admin() ) {
            WPTB_Activator::activate();
        }
    }
    
    public function capture_mail_error( $wp_error ) {
        if ( is_wp_error( $wp_error ) ) {
            $this->last_mail_error = $wp_error->get_error_message();
        }
    }

    public function enqueue_scripts() {
        // 1. STYLES
        // Core Styles
        wp_enqueue_style( 'wptb-main-style', WPTB_PLUGIN_URL . 'assets/css/style.css', array(), filemtime( WPTB_PLUGIN_DIR . 'assets/css/style.css' ) );
        // wp_enqueue_style( 'wptb-booking-css', WPTB_PLUGIN_URL . 'assets/css/booking-style.css', array(), '4.1.4' ); // File does not exist
        wp_enqueue_style( 'wptb-modal-vehicles-css', WPTB_PLUGIN_URL . 'assets/css/modal-vehicles.css', array(), filemtime( WPTB_PLUGIN_DIR . 'assets/css/modal-vehicles.css' ) );
        wp_enqueue_style( 'wptb-form-fix-css', WPTB_PLUGIN_URL . 'assets/css/form-fix.css', array(), filemtime( WPTB_PLUGIN_DIR . 'assets/css/form-fix.css' ) );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200', array(), null );
        
        // 2. GOOGLE MAPS API
        $api_key = get_option( 'wptb_google_maps_api_key' );
        if ( empty( $api_key ) ) {
            $api_key = 'AIzaSyCHNNn7ZxfS5PLtsPDifp2y-5ym4Ds7Its';
        }
        
        // If API key is present, load Google Maps
        if ( ! empty( $api_key ) ) {
            // Using 'libraries' param to load places and geometry
            $maps_url = add_query_arg(
                array(
                    'key'       => $api_key,
                    'libraries' => 'places,geometry',
                    'language'  => 'es',
                    'region'    => 'ES'
                ),
                'https://maps.googleapis.com/maps/api/js'
            );
            wp_enqueue_script( 'google-maps', $maps_url, array(), null, true );
        }

        // 3. BOOKING APP (Core Logic)
        // Depends on Google Maps if available
        $deps = array('jquery');
        if ( ! empty( $api_key ) ) {
            $deps[] = 'google-maps';
        }
        // Bump version to 3.2.1 (Hotfix for selectVehicle)
        // Bump version to 3.5.0 (Hotfix for Round Trip Pricing Calculation (x2))
        wp_enqueue_script( 'wptb-booking-js', WPTB_PLUGIN_URL . 'assets/js/booking-app.js', $deps, WPTB_VERSION, true );
        
        // 4. PAYMENTS (Redsys)
        // Redsys JS Handler
        wp_enqueue_script( 'wptb-redsys-payment', WPTB_PLUGIN_URL . 'assets/js/redsys-payment.js', array('jquery', 'jspdf'), WPTB_VERSION, true );
        
        // PDF Library
        wp_enqueue_script( 'jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), '2.5.1', true );

        // PTS Tour scripts
        wp_enqueue_script( 'wptb-transfers-search', WPTB_PLUGIN_URL . 'assets/js/transfers-search.js', array('jquery', 'wptb-places-js'), WPTB_VERSION, true );        
        // 5. LOCALIZATION & DATA PASSING
        // Timezone: Spain (Madrid)
        $madrid_tz = new DateTimeZone('Europe/Madrid');
        $now_madrid = new DateTime('now', $madrid_tz);
        
        // Prepare global variables
        $wptb_vars = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wptb-booking-nonce' ),
            'vehicles_url' => site_url('/seleccionar-vehiculo/'),
            'details_url' => site_url('/reservas-metransfers/'),
            'payment_url' => site_url('/pago/'), // Generic URL
            'server_time' => $now_madrid->format('Y-m-d H:i:s'),
            'min_date' => $now_madrid->format('Y-m-d'),
            'google_maps_api_key' => $api_key,
            'home_url' => home_url('/')
        );
        
        // Pass data to scripts
        wp_localize_script( 'wptb-booking-js', 'wptb_vars', $wptb_vars );
        wp_localize_script( 'wptb-redsys-payment', 'wptb_vars', $wptb_vars );
        
        // 6. DEBUG HELPER
        if (current_user_can('manage_options')) {
            wp_enqueue_script( 'wptb-debug', WPTB_PLUGIN_URL . 'assets/js/debug-helper.js', array('jquery'), '2.0.1', true );
        }
    }

    public function register_shortcodes() {
        add_shortcode( 'wptb_booking_form', array( $this, 'render_booking_form' ) );
        add_shortcode( 'wptb_booking', array( $this, 'render_booking_form' ) ); // Backward compatibility
        add_shortcode( 'wptb_vehicle_selection', array( $this, 'render_vehicle_selection' ) );
        add_shortcode( 'wptb_booking_details', array( $this, 'render_booking_details' ) );
        add_shortcode( 'wptb_stripe_checkout', array( $this, 'render_checkout_page' ) ); // Backward compat
        add_shortcode( 'wptb_redsys_checkout', array( $this, 'render_checkout_page' ) ); // New
        add_shortcode( 'wptb_checkout', array( $this, 'render_checkout_page' ) ); // Generic
        add_shortcode( 'wptb_popular_destinations_carousel', array( $this, 'render_popular_carousel' ) );
        add_shortcode( 'wptb_popular_destinations', array( $this, 'render_popular_carousel' ) ); // Alias
        add_shortcode( 'wptb_booking_popup', array( $this, 'render_booking_popup' ) );
    }

    public function render_booking_form( $atts ) {
        ob_start();
        $booking_source = 'Metransfers';
        include WPTB_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }

    public function render_vehicle_selection( $atts ) {
        ob_start();
        include WPTB_PLUGIN_DIR . 'templates/booking-vehicles.php';
        return ob_get_clean();
    }
    
    public function render_booking_details() {
        ob_start();
        include WPTB_PLUGIN_DIR . 'templates/booking-details.php';
        return ob_get_clean();
    }
    
    public function render_checkout_page() {
        ob_start();
        include WPTB_PLUGIN_DIR . 'templates/checkout.php';
        return ob_get_clean();
    }

    // ===== NEW SHORTCODES =====
    public function render_popular_carousel() {
        ob_start();
        include WPTB_PLUGIN_DIR . 'templates/popular-carousel.php';
        // Auto-include modal so it's always available when carousel is present
        include WPTB_PLUGIN_DIR . 'templates/booking-modal.php';
        return ob_get_clean();
    }

    public function render_booking_popup() {
        ob_start();
        include WPTB_PLUGIN_DIR . 'templates/booking-modal.php';
        return ob_get_clean();
    }

    public function save_booking() {
        // Check for WooCommerce
        if ( ! class_exists( 'WooCommerce' ) ) {
            wp_send_json_error( array( 'message' => 'WooCommerce is not active.' ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';

        // Sanitize Input
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
        
        // Validate vehicle
        if ( ! $vehicle_id ) {
            wp_send_json_error( array( 'message' => 'Debe seleccionar un vehículo' ) );
        }
        
        $vehicle = WPTB_Vehicle_Manager::get_vehicle( $vehicle_id );
        if ( ! $vehicle ) {
            wp_send_json_error( array( 'message' => 'Vehículo no válido' ) );
        }
        
        // Validate price meets minimums
        $validation = WPTB_Pricing::validate_booking_price( $vehicle_id, $distance, $trip_type, $price );
        if ( ! $validation['valid'] ) {
            wp_send_json_error( array( 'message' => $validation['message'] ) );
        }

        // Add to WooCommerce Cart
        $product_id = get_option( 'wptb_transfer_product_id' );
        if ( ! $product_id ) {
            // Fallback if activation didn't run or option missing
            $product = get_page_by_title( 'Transfer Service', OBJECT, 'product' );
            if ($product) $product_id = $product->ID;
        }

        if ( $product_id ) {
            WC()->cart->empty_cart(); // Optional: Clear cart to ensure only this booking is there
            
            $cart_item_data = array(
                'wptb_booking_data' => array(
                    'origin' => $origin,
                    'destination' => $destination,
                    'date' => $date,
                    'time' => $time,
                    'distance' => $distance,
                    'duration_minutes' => $duration_minutes,
                    'vehicle_id' => $vehicle_id,
                    'vehicle_name' => $vehicle->name,
                    'trip_type' => $trip_type,
                    'custom_price' => $price,
                    'passengers' => intval($data['passengers']),
                    'suitcases' => intval($data['suitcases']),
                    'carry_ons' => intval($data['carryOns']),
                    'flight_number' => sanitize_text_field($data['flight']),
                    'notes' => sanitize_textarea_field($data['notes']),
                    'name' => $fullName,
                    'email' => sanitize_email($data['email']),
                    'phone' => sanitize_text_field($data['phone'])
                )
            );

            WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

            // Save to DB as 'pending payment'
             $wpdb->insert( 
                $table_name, 
                array( 
                    'booking_date' => $date . ' ' . $time, 
                    'pickup_address' => $origin,
                    'dropoff_address' => $destination,
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
                    'status' => 'added-to-cart'
                ),
                array( '%s', '%s', '%s', '%f', '%d', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
            );

            wp_send_json_success( array( 
                'message' => 'Redirecting to checkout...', 
                'redirect_url' => wc_get_checkout_url() 
            ));
        } else {
            wp_send_json_error( array( 'message' => 'Transfer Product not found. Please contact admin.' ) );
        }
    }

    // Hook: Override Price
    public function calculate_cart_totals( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['wptb_booking_data']['custom_price'] ) ) {
                $cart_item['data']->set_price( $cart_item['wptb_booking_data']['custom_price'] );
            }
        }
    }

    // Hook: Display Data in Cart
    public function display_cart_item_data( $item_data, $cart_item ) {
        if ( isset( $cart_item['wptb_booking_data'] ) ) {
            $data = $cart_item['wptb_booking_data'];
            
            if ( isset( $data['vehicle_name'] ) ) {
                $item_data[] = array( 'key' => 'Vehículo', 'value' => $data['vehicle_name'] );
            }
            
            if ( isset( $data['trip_type'] ) ) {
                $trip_labels = array(
                    'one_way' => 'Solo Ida',
                    'round_trip' => 'Ida y Vuelta',
                    'return' => 'Vuelta'
                );
                $trip_label = isset( $trip_labels[ $data['trip_type'] ] ) ? $trip_labels[ $data['trip_type'] ] : 'Solo Ida';
                $item_data[] = array( 'key' => 'Tipo de Viaje', 'value' => $trip_label );
            }
            
            $item_data[] = array( 'key' => 'Origen', 'value' => $data['origin'] );
            $item_data[] = array( 'key' => 'Destino', 'value' => $data['destination'] );
            $item_data[] = array( 'key' => 'Fecha/Hora', 'value' => $data['date'] . ' ' . $data['time'] );
            $item_data[] = array( 'key' => 'Pasajeros', 'value' => $data['passengers'] );
            $item_data[] = array( 'key' => 'Distancia', 'value' => $data['distance'] . ' km' );
            
            if ( ! empty( $data['flight_number'] ) ) {
                $item_data[] = array( 'key' => 'Vuelo', 'value' => $data['flight_number'] );
            }
        }
        return $item_data;
    }

    // Hook: Save Meta to Order
    public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['wptb_booking_data'] ) ) {
            $data = $values['wptb_booking_data'];
            
            if ( isset( $data['vehicle_name'] ) ) {
                $item->add_meta_data( 'Vehículo', $data['vehicle_name'] );
            }
            
            if ( isset( $data['trip_type'] ) ) {
                $trip_labels = array(
                    'one_way' => 'Solo Ida',
                    'round_trip' => 'Ida y Vuelta',
                    'return' => 'Vuelta'
                );
                $trip_label = isset( $trip_labels[ $data['trip_type'] ] ) ? $trip_labels[ $data['trip_type'] ] : 'Solo Ida';
                $item->add_meta_data( 'Tipo de Viaje', $trip_label );
            }
            
            $item->add_meta_data( 'Origen', $data['origin'] );
            $item->add_meta_data( 'Destino', $data['destination'] );
            $item->add_meta_data( 'Fecha', $data['date'] );
            $item->add_meta_data( 'Hora', $data['time'] );
            $item->add_meta_data( 'Pasajeros', $data['passengers'] );
            $item->add_meta_data( 'Distancia', $data['distance'] . ' km' );
            
            if ( ! empty( $data['flight_number'] ) ) {
                $item->add_meta_data( 'Vuelo', $data['flight_number'] );
            }
            
            if ( ! empty( $data['notes'] ) ) {
                $item->add_meta_data( 'Notas', $data['notes'] );
            }
        }
    }

    // Hook: Pre-fill Checkout Fields
    public function prefill_checkout_fields( $value, $input ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return $value;

        // Only check on checkout page
        if ( ! is_checkout() ) return $value;

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) return $value;

        $booking_data = false;
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['wptb_booking_data'] ) ) {
                $booking_data = $cart_item['wptb_booking_data'];
                break;
            }
        }

        if ( $booking_data ) {
            switch ( $input ) {
                case 'billing_first_name':
                    if ( empty( $value ) ) {
                         $parts = explode(' ', trim($booking_data['name']));
                         return $parts[0]; 
                    }
                    break;
                case 'billing_last_name':
                    if ( empty( $value ) ) {
                         $parts = explode(' ', trim($booking_data['name']), 2);
                         return isset($parts[1]) ? $parts[1] : ''; 
                    }
                    break;
                case 'billing_email':
                    if ( empty( $value ) ) return $booking_data['email'];
                    break;
                case 'billing_phone':
                    if ( empty( $value ) ) return $booking_data['phone'];
                    break;
            }
        }

        return $value;
    }
    
    /**
     * Handle WooCommerce Booking Completion
     * Triggered when user completes payment via WooCommerce (Stripe, etc.)
     */
    public function handle_woocommerce_booking_complete( $order_id ) {
        if ( ! $order_id ) return;
        
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        
        // Only process if order is paid/completed
        if ( ! in_array( $order->get_status(), array( 'processing', 'completed' ) ) ) {
            return;
        }
        
        error_log( "WPTB WooCommerce: Processing order #$order_id for booking notifications" );
        
        // Get booking data from order items
        foreach ( $order->get_items() as $item ) {
            $booking_data = $item->get_meta( 'Origen', true );
            
            // If this item has booking data, find and process it
            if ( $booking_data ) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'wptb_bookings';
                
                $customer_email = $order->get_billing_email();
                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                
                // Find booking by customer email and pending/added-to-cart status
                $booking = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM $table_name 
                    WHERE customer_email = %s 
                    AND status IN ('pending', 'added-to-cart', 'pending_payment')
                    ORDER BY created_at DESC LIMIT 1",
                    $customer_email
                ) );
                
                if ( $booking ) {
                    // Check if already notified to prevent duplicates
                    if ( in_array( $booking->status, array( 'confirmed', 'completed' ) ) && 
                         $booking->payment_status === 'paid' ) {
                        error_log( "WPTB WooCommerce: Booking #{$booking->id} already confirmed. Skipping notifications." );
                        return;
                    }
                    
                    // Update booking status
                    $wpdb->update(
                        $table_name,
                        array( 
                            'status' => 'confirmed',
                            'payment_status' => 'paid',
                            'payment_method' => $order->get_payment_method(),
                            'payment_intent_id' => $order->get_transaction_id()
                        ),
                        array( 'id' => $booking->id )
                    );
                    
                    // Re-fetch updated booking
                    $booking_updated = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE id = %d",
                        $booking->id
                    ) );
                    
                    // Send notifications
                    error_log( "WPTB WooCommerce: Sending notifications for booking #{$booking->id}" );
                    $this->process_booking_notifications( $booking->id, $booking_updated );
                    $this->send_whatsapp_alert( $booking->id, $booking_updated );
                    
                } else {
                    error_log( "WPTB WooCommerce: No booking found for order #$order_id (email: $customer_email)" );
                }
                
                break; // Only process first booking item
            }
        }
    }
    
    /**
     * AJAX: Get available vehicles
     */
    public function ajax_get_vehicles() {
        check_ajax_referer( 'wptb-booking-nonce', 'security' ); // Security Check
        global $wpdb;
        
        // Get vehicles (now getting ALL vehicles for debug)
        $vehicles = WPTB_Vehicle_Manager::get_active_vehicles();
        
        // Debug info
        $table_vehicles = $wpdb->prefix . 'wptb_vehicles';
        $total_in_db = $wpdb->get_var("SELECT COUNT(*) FROM $table_vehicles");
        
        $response = array();
        
        if ( ! empty( $vehicles ) ) {
            foreach ( $vehicles as $vehicle ) {
                $price_range = WPTB_Pricing::get_vehicle_price_range( $vehicle->id );
                
                // Fetch type name separately if needed or default
                $type_name = 'Standard'; 
                // We're skipping the join for now, so type_name might be missing
                if(isset($vehicle->type_name)) $type_name = $vehicle->type_name;

                $response[] = array(
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'type' => $type_name,
                    'description' => $vehicle->description,
                    'capacity' => $vehicle->capacity,
                    'luggage_capacity' => $vehicle->luggage_capacity,
                    'image' => WPTB_Vehicle_Manager::get_primary_image( $vehicle->id ),
                    'image_url' => WPTB_Vehicle_Manager::get_primary_image( $vehicle->id ), // For modal compatibility
                    'price_range' => $price_range,
                    'is_active_db' => $vehicle->is_active, 
                    'pricing' => array(
                        'min_transfer' => floatval( $vehicle->min_transfer_price ),
                        'min_oneway' => floatval( $vehicle->min_oneway_price ),
                        'min_roundtrip' => floatval( $vehicle->min_roundtrip_price ),
                        'price_per_km_oneway' => floatval( $vehicle->price_per_km_oneway ),
                        'price_per_km_roundtrip' => floatval( $vehicle->price_per_km_roundtrip ),
                        'price_per_hour' => floatval( $vehicle->price_per_hour )
                    )
                );
            }
            wp_send_json_success( $response );
        } else {
            // Send error with debug info if no vehicles found
            wp_send_json_error( array(
                'message' => 'No vehicles found in database',
                'debug_info' => array(
                    'total_vehicles_in_db' => $total_in_db,
                    'table_name' => $table_vehicles,
                    'last_error' => $wpdb->last_error
                )
            ) );
        }
    }
    
    /**
     * AJAX: Calculate price for vehicle and trip
     */
    public function ajax_calculate_price() {
        $vehicle_id = isset( $_POST['vehicle_id'] ) ? absint( $_POST['vehicle_id'] ) : 0;
        $distance_km = isset( $_POST['distance_km'] ) ? floatval( $_POST['distance_km'] ) : 0;
        $trip_type = isset( $_POST['trip_type'] ) ? sanitize_text_field( $_POST['trip_type'] ) : 'one_way';
        $duration_minutes = isset( $_POST['duration_minutes'] ) ? absint( $_POST['duration_minutes'] ) : 0;
        
        $result = WPTB_Pricing::calculate_price( $vehicle_id, $distance_km, $trip_type, $duration_minutes );
        
        if ( isset( $result['error'] ) ) {
            wp_send_json_error( $result );
        }
        
        wp_send_json_success( $result );
    }
    
    // ===== REDSYS PAYMENT METHODS =====
    
    public function initiate_redsys_payment() {
        try {
            check_ajax_referer( 'wptb-booking-nonce', 'security' );
            
            $booking_data = json_decode( stripslashes( $_POST['booking_data'] ), true );
            
            if ( ! $booking_data || ! isset( $booking_data['price'] ) ) {
                wp_send_json_error( array( 'message' => 'Datos de reserva inválidos' ) );
                return;
            }
            
            // Save Pending Booking
            // ---------------------------------------------------------
            global $wpdb;
            $table_name = $wpdb->prefix . 'wptb_bookings';
            
            $existing_id = isset($_POST['existing_booking_id']) ? intval($_POST['existing_booking_id']) : 0;
            $booking_id = 0;

            // Get hotel token from cookie if set
            $hotel_token = isset($_COOKIE['hqp_hotel_token']) ? sanitize_text_field($_COOKIE['hqp_hotel_token']) : null;
            
            $data_db = array(
                'booking_date' => $booking_data['date'],
                'booking_time' => $booking_data['time'],
                'origin' => sanitize_text_field( $booking_data['origin'] ),
                'destination' => sanitize_text_field( $booking_data['destination'] ),
                'distance_km' => floatval( $booking_data['distance_km'] ),
                'vehicle_id' => intval( $booking_data['vehicle_id'] ),
                'trip_type' => !empty( $booking_data['trip_type'] ) ? sanitize_text_field( $booking_data['trip_type'] ) : 'one_way',
                'price' => floatval( $booking_data['price'] ),
                'customer_name' => !empty($booking_data['customer_name']) ? sanitize_text_field( $booking_data['customer_name'] ) : '',
                'customer_email' => !empty($booking_data['customer_email']) ? sanitize_email( $booking_data['customer_email'] ) : '',
                'customer_phone' => !empty($booking_data['customer_phone']) ? sanitize_text_field( $booking_data['customer_phone'] ) : '',
                'passengers' => !empty($booking_data['passengers']) ? intval( $booking_data['passengers'] ) : 1,
                'suitcases' => !empty($booking_data['suitcases']) ? intval( $booking_data['suitcases'] ) : 0,
                'carry_ons' => !empty($booking_data['carry_ons']) ? intval( $booking_data['carry_ons'] ) : 0,
                'flight_number' => !empty($booking_data['flight_number']) ? sanitize_text_field( $booking_data['flight_number'] ) : '',
                'notes' => !empty($booking_data['notes']) ? sanitize_textarea_field( $booking_data['notes'] ) : '',
                'status' => 'pending_payment',
                'payment_method' => 'redsys',
                'created_at' => current_time( 'mysql' )
            );

            if ( !empty( $booking_data['return_date'] ) ) $data_db['return_date'] = sanitize_text_field( $booking_data['return_date'] );
            if ( !empty( $booking_data['return_time'] ) ) $data_db['return_time'] = sanitize_text_field( $booking_data['return_time'] );
            if ( !empty( $booking_data['return_origin'] ) ) $data_db['return_pickup_address'] = sanitize_text_field( $booking_data['return_origin'] );
            if ( !empty( $booking_data['return_destination'] ) ) $data_db['return_dropoff_address'] = sanitize_text_field( $booking_data['return_destination'] );
            if ( !empty( $hotel_token ) ) $data_db['hotel_token'] = $hotel_token;

            $format_db = array();
            foreach ( $data_db as $key => $val ) {
                if ( is_int( $val ) ) {
                    $format_db[] = '%d';
                } elseif ( is_float( $val ) ) {
                    $format_db[] = '%f';
                } else {
                    $format_db[] = '%s';
                }
            }

            // Forzar que el AUTO_INCREMENT inicie en 1000 para que coincida con el requerimiento de Redsys Transfer
            $max_id = $wpdb->get_var("SELECT MAX(id) FROM $table_name");
            if ( ! $max_id || $max_id < 1000 ) {
                $wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = 1000");
            }

            if ( $existing_id > 0 ) {
                $existing_booking = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM $table_name WHERE id = %d", $existing_id ) );
                if ( $existing_booking && $existing_booking->status === 'pending_payment' ) {
                    $result = $wpdb->update( $table_name, $data_db, array( 'id' => $existing_id ), $format_db, array( '%d' ) );
                    $booking_id = $existing_id;
                } else {
                    $result = $wpdb->insert( $table_name, $data_db, $format_db );
                    $booking_id = $wpdb->insert_id;
                }
            } else {
                $result = $wpdb->insert( $table_name, $data_db, $format_db );
                $booking_id = $wpdb->insert_id;
            }

            if ( $result === false || $booking_id <= 0 ) {
                wp_send_json_error( array( 'message' => 'Error al guardar en la base de datos (Posible fallo de campos). Por favor, contacta con soporte.' ) );
                return;
            }

            // --- SEND PENDING NOTIFICATIONS ---
            // Only send if it's a new booking to avoid spamming on retries
            if ( $existing_id <= 0 ) {
                $new_booking_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking_id ) );
                if ( $new_booking_obj ) {
                    $this->process_booking_notifications( $booking_id, $new_booking_obj, 'pending' );
                }
            }
            

            // 2. Prepare Redsys Params
            // ---------------------------------------------------------
            $key = '6N2lZu0nf+j7MnyFKGWyOxdzZau5sAAE'; // SHA-256 Key provided
            $redsys = new WPTB_Redsys_API();
            
            $amount = intval( $booking_data['price'] * 100 ); // Cents
            
            // Order ID: 4 nums + 8 alphanumeric.
            $order_id = str_pad( $booking_id, 12, '0', STR_PAD_LEFT ); 
            
            // Save Order ID to DB
            $wpdb->update( 
                $table_name, 
                array( 'payment_intent_id' => $order_id ), 
                array( 'id' => $booking_id ) 
            );

            $merchant_code = '48234082';
            $terminal = '1';
            $currency = '978'; // EUR
            $trans_type = '0'; // Autorización
            
            $url_notification = home_url( '/?wptb_redsys_ipn=1' );
            $url_ok = home_url( '/reservas-metransfers/?payment_result=ok&oid=' . $order_id );
            $url_ko = home_url( '/reservas-metransfers/?payment_result=ko&oid=' . $order_id );
            
            // Use Official API Methods
            $redsys->setParameter("DS_MERCHANT_AMOUNT", $amount);
            $redsys->setParameter("DS_MERCHANT_ORDER", $order_id);
            $redsys->setParameter("DS_MERCHANT_MERCHANTCODE", $merchant_code);
            $redsys->setParameter("DS_MERCHANT_CURRENCY", $currency);
            $redsys->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $trans_type);
            $redsys->setParameter("DS_MERCHANT_TERMINAL", $terminal);
            $redsys->setParameter("DS_MERCHANT_MERCHANTURL", $url_notification);
            $redsys->setParameter("DS_MERCHANT_URLOK", $url_ok);
            $redsys->setParameter("DS_MERCHANT_URLKO", $url_ko);
            $redsys->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION", "Reserva " . $booking_id);
            $redsys->setParameter("DS_MERCHANT_TITULAR", substr($booking_data['customer_name'], 0, 60));

            $params = $redsys->createMerchantParameters();
            $signature = $redsys->createMerchantSignature($key);
            
            wp_send_json_success( array(
                'url' => 'https://sis.redsys.es/sis/realizarPago',
                'ds_signature_version' => 'HMAC_SHA256_V1',
                'ds_merchant_parameters' => $params,
                'ds_signature' => $signature
            ));
            
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }
    
    public function listen_redsys_ipn() {
        if ( isset( $_GET['wptb_redsys_ipn'] ) && isset( $_POST['Ds_MerchantParameters'] ) ) {
            
            $key = '6N2lZu0nf+j7MnyFKGWyOxdzZau5sAAE';
            $redsys = new WPTB_Redsys_API();
            
            $version = $_POST['Ds_SignatureVersion'];
            $params = $_POST['Ds_MerchantParameters'];
            $signature_recv = $_POST['Ds_Signature'];
            
            // Check Signature
            $signature_calc = $redsys->createMerchantSignatureNotif( $key, $params );
            
            if ( $signature_calc === $signature_recv ) {
                
                $decoded = $redsys->decodeMerchantParameters( $params );
                $response = intval( $decoded['Ds_Response'] );
                $order_id = $decoded['Ds_Order'];
                
                // 0000 to 0099 = Authorized
                if ( $response >= 0 && $response <= 99 ) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'wptb_bookings';
                    
                    // Update Booking
                    $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE payment_intent_id = %s", $order_id ) );
                    
                    if ( $booking ) {
                        // PREVENT DUPLICATE PROCESSING
                        if ( $booking->status === 'confirmed' || $booking->payment_status === 'paid' ) {
                            // Already confirmed (possibly by force check), just exit or log
                            error_log( "WPTB INFO: IPN received for ALREADY CONFIRMED booking #{$booking->id}. Skipping emails." );
                            die();
                        }

                        $wpdb->update(
                            $table_name,
                            array( 'status' => 'confirmed', 'payment_status' => 'paid' ),
                            array( 'id' => $booking->id )
                        );
                        
                        $booking_updated = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking->id ) );
                        
                        // Send Notification Emails
                        $this->process_booking_notifications( $booking->id, $booking_updated );
                        $this->send_whatsapp_alert( $booking->id, $booking_updated );
                    }
                }
            } else {
                 error_log( "Redsys Signature Mismatch for parameters: " . $params );
            }
            
            // Always die() to prevent WP rendering
            die();
        }
    }

    /**
     * FORCE CHECK ON RETURN URL (Backup for IPN failure)
     */
    public function check_return_url_payment_force() {
        if ( isset( $_GET['payment_result'] ) && $_GET['payment_result'] === 'ok' && isset( $_GET['oid'] ) ) {
            
            $order_id = sanitize_text_field( $_GET['oid'] );
            error_log( "WPTB FORCE CHECK: Detected return URL for Order ID $order_id" );
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'wptb_bookings';
            
            // Find Booking by Order ID
            $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE payment_intent_id = %s", $order_id ) );
            
            if ( $booking ) {
                // If it's still pending, FORCE confirm it
                if ( $booking->status !== 'confirmed' ) {
                    
                    error_log( "WPTB SUB-FORCE: Forcing confirmation for Booking #{$booking->id} via Return URL." );
                    
                    $wpdb->update(
                        $table_name,
                        array( 
                            'status' => 'confirmed', 
                            'payment_status' => 'paid' 
                        ),
                        array( 'id' => $booking->id )
                    );
                    
                    // RE-FETCH updated booking object to be safe
                    $booking_updated = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking->id ) );
                    
                    // Send Emails
                    $this->process_booking_notifications( $booking->id, $booking_updated );
                    $this->send_whatsapp_alert( $booking->id, $booking_updated );
                } else {
                     error_log( "WPTB FORCE CHECK: Booking #{$booking->id} already confirmed. Skipping force action." );
                }
            } else {
                error_log( "WPTB FORCE CHECK: Booking NOT found for Order ID $order_id" );
            }
        }
    }

    /**
     * SMTP Configuration (Hardcoded as per request)
     */
    // [EMAIL REFACTOR] Old Configure SMTP Removed

    /**
     * Send Booking Emails (Client & Admin)
     */
    /**
     * Send Booking Emails (Client & Admin)
     */
    /**
     * Send Booking Emails (Client & Admin)
     */
    public function configure_smtp( $phpmailer ) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = 'mail.barcelonatours.email';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = 465;
        $phpmailer->Username   = 'reservas@barcelonatours.email';
        $phpmailer->Password   = 'G0ku*1896_@';
        $phpmailer->SMTPSecure = 'ssl';
        $phpmailer->From       = 'reservas@barcelonatours.email';
        $phpmailer->FromName   = 'Metransfers';
        $phpmailer->Sender     = 'reservas@barcelonatours.email'; // Return-Path
        $phpmailer->ClearReplyTos();
        $phpmailer->addReplyTo('reservas@barcelonatours.email', 'Metransfers');
    }

    public function send_booking_emails( $booking_id, $booking ) {
        error_log( "WPTB EMAIL START: Sending confirmation for Booking #$booking_id" );
        
        // Enforce SMTP for this sending (High Priority)
        add_action( 'phpmailer_init', array( $this, 'configure_smtp' ), 9999 );
        
        $final_status = true; // Track overall success

        // FORCE Admin Emails (Dynamic Construction below)
        
        $trip_labels = array(
            'one_way' => 'Solo Ida',
            'round_trip' => 'Ida y Vuelta',
            'return' => 'Vuelta'
        );

        // Create Vehicle Name
        $vehicle_obj = WPTB_Vehicle_Manager::get_vehicle( $booking->vehicle_id );
        $vehicle_name = $vehicle_obj ? $vehicle_obj->name : 'Vehículo no encontrado';

        $client_email = $booking->customer_email;
        
        $subject = "Confirmación de Reserva #{$booking_id} - Metransfers";
        
        // Dynamic Fields
        $flight_row = !empty($booking->flight_number) ? "<div class='detail-row'><strong>Vuelo:</strong> <span>{$booking->flight_number}</span></div>" : '';
        $notes_row = !empty($booking->notes) ? "<div class='detail-row'><strong>Notas:</strong> <span>{$booking->notes}</span></div>" : '';
        $luggage_info = "{$booking->suitcases} Maletas, {$booking->carry_ons} Mochilas";
        
        // Prepare HTML Message
        $message = "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; }
                .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .header { background: #FF8C00; color: #fff; padding: 20px; text-align: center; }
                .header h2 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .detail-box { background: #fdfdfd; border: 1px solid #eee; border-radius: 6px; padding: 15px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
                .detail-row:last-child { border-bottom: none; }
                .detail-row strong { color: #555; }
                .detail-row span { color: #000; font-weight: 600; text-align: right; max-width: 60%; }
                .footer { background: #eee; padding: 30px 20px; text-align: center; font-size: 13px; color: #777; border-top: 1px solid #e0e0e0; }
                .btn { display: inline-block; background: #FF8C00; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; }
                .footer-logo { max-width: 250px; width: 100%; height: auto; margin-bottom: 20px; display: inline-block; }
                .credit { margin-top: 20px; font-size: 11px; opacity: 0.7; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>¡Reserva Confirmada!</h2>
                    <p>Referencia: #{$booking_id}</p>
                </div>
                
                <div class='content'>
                    <p>Hola <strong>{$booking->customer_name}</strong>,</p>
                    <p>Gracias por confirmar tu reserva. Hemos recibido tu pago correctamente y tu vehículo está reservado.</p>
                    
                    <div class='detail-box'>
                        <div class='detail-row'><strong>Fecha / Hora:</strong> <span>{$booking->booking_date} {$booking->booking_time}</span></div>
                        <div class='detail-row'><strong>Tipo de Viaje:</strong> <span style='color: #ff7100;'> " . ( isset( $trip_labels[$booking->trip_type] ) ? $trip_labels[$booking->trip_type] : ( $booking->trip_type === 'round_trip' ? 'Ida y Vuelta' : 'Solo Ida' ) ) . "</span></div>
                        <div class='detail-row'><strong>Origen:</strong> <span>{$booking->origin}</span></div>
                        <div class='detail-row'><strong>Destino:</strong> <span>{$booking->destination}</span></div>
                        <div class='detail-row'><strong>Vehículo:</strong> <span>{$vehicle_name}</span></div>
                        <div class='detail-row'><strong>Pasajeros:</strong> <span>{$booking->passengers}</span></div>
                        <div class='detail-row'><strong>Equipaje:</strong> <span>{$luggage_info}</span></div>
                        {$flight_row}
                        " . ( $booking->trip_type === 'round_trip' ? "
                        <div class='detail-row' style='background:#f0f7ff; margin-top:15px; border-top:2px solid #ff7100; padding-top:10px;'><strong>📅 VUELTA CONFIRMADA:</strong> <span>{$booking->return_date} {$booking->return_time}</span></div>
                        <div class='detail-row' style='background:#f0f7ff;'><strong>Recogida Vuelta:</strong> <span>{$booking->return_pickup_address}</span></div>
                        <div class='detail-row' style='background:#f0f7ff; margin-bottom:15px; border-bottom:1px solid #ddd;'><strong>Destino Vuelta:</strong> <span>{$booking->return_dropoff_address}</span></div>
                        " : "" ) . "
                        <div class='detail-row'><strong>Precio Total:</strong> <span style='color:#28a745;'>€{$booking->price}</span></div>
                        {$notes_row}
                        <div class='detail-row'><strong>Teléfono:</strong> <span>{$booking->customer_phone}</span></div>
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='" . home_url() . "' class='btn'>Ir a la Web</a>
                    </p>
                </div>
                
                <div class='footer'>
                    <img src='https://metransfers.es/wp-content/uploads/2026/01/LOGO-CREDITOS-MAIL.png' alt='Metransfers' class='footer-logo'><br>
                    &copy; " . date('Y') . " Metransfers. Todos los derechos reservados.<br>
                    Si necesitas ayuda, responde a este correo.
                    <p class='credit'>Sistema de pago desarrollado por <strong>Merchan.Dev</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Metransfers <reservas@barcelonatours.email>',
            'Reply-To: Metransfers <reservas@barcelonatours.email>'
        );

        // Send to Client
        if ( ! empty( $client_email ) ) {
            $this->last_mail_error = '';
            $sent_client = wp_mail( $client_email, $subject, $message, $headers );
            if ( $sent_client ) {
                error_log( "WPTB SUCCESS: Email sent to client ($client_email)" );
            } else {
                error_log( "WPTB ERROR: Email FAILED to client ($client_email). Reason: " . $this->last_mail_error );
                $final_status = false;
            }
        } else {
             error_log( "WPTB WARNING: No client email found for booking #$booking_id" );
        }

        // 1. Get Configured Email & Defaults
        $configured_email = get_option( 'wptb_admin_email_notifications' );
        
        // Ensure it only sends to the main routing email as requested
        $admin_emails = array( 'reservas@barcelonatours.email' ); 

        if ( ! empty( $configured_email ) && is_email( $configured_email ) && ! in_array( $configured_email, $admin_emails ) ) {
            $admin_emails[] = $configured_email;
        }
        
        $admin_emails = array_unique( $admin_emails );
        
        $admin_subject = "Nueva Reserva #{$booking_id} - Pago Confirmado (Cliente: {$booking->customer_name})";

        // Send to Admins
        foreach ( $admin_emails as $email ) {
            // DO NOT PUSH ANOTHER 'From' HEADER. It's already in $headers
            $headers_admin = $headers;
            
            // Reset error
            $this->last_mail_error = '';
            
            $sent = wp_mail( $email, $admin_subject, $message, $headers_admin );
            
            if ( ! $sent ) {
                 // Format error message
                 $err_msg = !empty($this->last_mail_error) ? $this->last_mail_error : 'Unknown Error';
                 error_log( "WPTB ERROR: Email FAILED to admin $email. Reason: $err_msg" );
                 
                 // If specific error, set it as status (string) instead of false
                 $final_status = "Error: $err_msg";
            } else {
                 error_log( "WPTB SUCCESS: Email sent to admin $email" );
            }
        }
        
        // 3. Notificar al Contacto del Hotel (si aplica)
        if ( isset( $booking->hotel_token ) && ! empty( $booking->hotel_token ) ) {
            $hotel_query = new \WP_Query( array(
                'post_type'      => 'hotel_partner',
                'meta_key'       => '_hqp_token',
                'meta_value'     => $booking->hotel_token,
                'posts_per_page' => 1,
                'fields'         => 'ids'
            ) );
            if ( $hotel_query->have_posts() ) {
                $hotel_id = $hotel_query->posts[0];
                $contact_email = get_post_meta( $hotel_id, '_hqp_contact_email', true );
                if ( ! empty( $contact_email ) && is_email( $contact_email ) ) {
                    $hotel_subject = "Nueva Reserva desde tu Hotel (#{$booking_id})";
                    $headers_hotel = $headers;
                    $sent_hotel = wp_mail( $contact_email, $hotel_subject, $message, $headers_hotel );
                    if ( $sent_hotel ) {
                        error_log( "WPTB SUCCESS: Email sent to Hotel ($contact_email)" );
                    } else {
                        error_log( "WPTB ERROR: Email FAILED to Hotel ($contact_email)" );
                    }
                }
            }
        }
        
        // Remove hook to avoid affecting other plugins/emails (Priority 9999)
        remove_action( 'phpmailer_init', array( $this, 'configure_smtp' ), 9999 );
        
        return $final_status;
    }

    /**
     * Send WhatsApp Alert (CallMeBot Integration)
     */
    public function send_whatsapp_alert( $booking_id, $booking ) {
        $admin_phone = get_option( 'wptb_admin_phone_notifications', '+34 640 80 84 78' );
        $apikey = get_option( 'wptb_whatsapp_apikey', '' );
        
        if ( empty( $apikey ) ) {
            error_log( 'WPTB: WhatsApp API Key missing. Notification skipped.' );
            return;
        }

        // Clean phone number (remove + and spaces for CallMeBot if needed, usually they accept +)
        // CallMeBot example: https://api.callmebot.com/whatsapp.php?phone=[phone]&text=[text]&apikey=[apikey]
        
        // Get Vehicle Name
        $vehicle_obj = WPTB_Vehicle_Manager::get_vehicle( $booking->vehicle_id );
        $vehicle_name = $vehicle_obj ? $vehicle_obj->name : 'Vehículo #' . $booking->vehicle_id;

        $text = "*Nueva Reserva #{$booking_id}*\n";
        $text .= "👤 {$booking->customer_name}\n";
        $text .= "🚘 {$vehicle_name}\n";
        $text .= "📍 {$booking->origin} \n⬇️\n📍 {$booking->destination}\n";
        $text .= "📅 {$booking->booking_date} {$booking->booking_time}\n";
        $text .= "💶 €{$booking->price}\n";
        $text .= "📞 {$booking->customer_phone}";
        
        $url = 'https://api.callmebot.com/whatsapp.php';
        $params = array(
            'phone' => str_replace(' ', '', $admin_phone), // Remove spaces
            'text' => $text,
            'apikey' => $apikey
        );
        
        $request_url = add_query_arg( $params, $url );
        
        // Timeout 5s to prevent hanging
        $response = wp_remote_get( $request_url, array( 'timeout' => 5 ) );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'WPTB: WhatsApp Error: ' . $response->get_error_message() );
        } else {
            error_log( 'WPTB: WhatsApp Sent to ' . $admin_phone );
        }
    }

    public function notify_new_booking( $booking_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking_id ) );
        if ( $booking ) {
            $this->process_booking_notifications( $booking_id, $booking, 'pending' );
        }
    }

    /**
     * [EMAIL REFACTOR] Main Orchestrator
     * Generates content and sends to all parties.
     */
    public function process_booking_notifications( $booking_id, $booking, $status_context = 'confirmed' ) {
        error_log( "WPTB NOTIFICATION START for Booking #$booking_id" );

        // Enforce SMTP for this sending (High Priority)
        add_action( 'phpmailer_init', array( $this, 'configure_smtp' ), 9999 );

        // A. Prepare Data
        $vehicle_obj = WPTB_Vehicle_Manager::get_vehicle( $booking->vehicle_id );
        $vehicle_name = $vehicle_obj ? $vehicle_obj->name : 'Vehículo no encontrado';
        
        $trip_labels = array( 'one_way' => 'Solo Ida', 'round_trip' => 'Ida y Vuelta', 'return' => 'Vuelta' );
        $trip_type_label = isset( $trip_labels[$booking->trip_type] ) ? $trip_labels[$booking->trip_type] : 'Solo Ida';
        
        $flight_row = !empty($booking->flight_number) ? "<div class='detail-row'><strong>Vuelo:</strong> <span>{$booking->flight_number}</span></div>" : '';
        $notes_row = !empty($booking->notes) ? "<div class='detail-row'><strong>Notas:</strong> <span>{$booking->notes}</span></div>" : '';
        $luggage_info = "{$booking->suitcases} Maletas, {$booking->carry_ons} Mochilas";

        $title = ( $status_context === 'pending' ) ? 'Reserva Recibida' : '¡Reserva Confirmada!';
        $intro = ( $status_context === 'pending' ) ? 'Hemos recibido tu solicitud de reserva y está pendiente de pago.' : 'Tu reserva ha sido confirmada correctamente.';
        $subject = ( $status_context === 'pending' ) ? "Reserva Recibida #{$booking_id} - Metransfers" : "Confirmación de Reserva #{$booking_id} - Metransfers";
        $admin_subject = ( $status_context === 'pending' ) ? "NUEVA Reserva Pendiente #{$booking_id}" : "Reserva PAGADA #{$booking_id}";

        // B. HTML Content
        $message = "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; }
                .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .header { background: #FF8C00; color: #fff; padding: 20px; text-align: center; }
                .header h2 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .detail-box { background: #fdfdfd; border: 1px solid #eee; border-radius: 6px; padding: 15px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
                .detail-row:last-child { border-bottom: none; }
                .detail-row strong { color: #555; }
                .detail-row span { color: #000; font-weight: 600; text-align: right; max-width: 60%; }
                .footer { background: #eee; padding: 30px 20px; text-align: center; font-size: 13px; color: #777; border-top: 1px solid #e0e0e0; }
                .btn { display: inline-block; background: #FF8C00; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; }
                .footer-logo { max-width: 250px; width: 100%; height: auto; margin-bottom: 20px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>{$title}</h2>
                    <p>Referencia: #{$booking_id}</p>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$booking->customer_name}</strong>,</p>
                    <p>{$intro}</p>
                    <div class='detail-box'>
                        <div class='detail-row'><strong>Fecha / Hora:</strong> <span>{$booking->booking_date} {$booking->booking_time}</span></div>
                        <div class='detail-row'><strong>Tipo:</strong> <span style='color: #ff7100;'>{$trip_type_label}</span></div>
                        <div class='detail-row'><strong>Origen:</strong> <span>{$booking->origin}</span></div>
                        <div class='detail-row'><strong>Destino:</strong> <span>{$booking->destination}</span></div>
                        <div class='detail-row'><strong>Vehículo:</strong> <span>{$vehicle_name}</span></div>
                        <div class='detail-row'><strong>Pasajeros:</strong> <span>{$booking->passengers}</span></div>
                        <div class='detail-row'><strong>Equipaje:</strong> <span>{$luggage_info}</span></div>
                        {$flight_row}
                        <div class='detail-row'><strong>Precio:</strong> <span style='color:#28a745;'>€{$booking->price}</span></div>
                        {$notes_row}
                        <div class='detail-row'><strong>Teléfono:</strong> <span>{$booking->customer_phone}</span></div>
                    </div>
                    <p style='text-align: center;'><a href='" . home_url() . "' class='btn'>Ir a la Web</a></p>
                </div>
                <div class='footer'>
                    <img src='https://metransfers.es/wp-content/uploads/2026/01/LOGO-CREDITOS-MAIL.png' alt='Metransfers' class='footer-logo'><br>
                    &copy; " . date('Y') . " Metransfers.<br>
                    <small>Sistema de reservas desarrollado por <strong>Merchan.Dev</strong></small>
                </div>
            </div>
        </body>
        </html>";
        // Prepare headers for wp_mail (always use the SMTP sender account)
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Metransfers <reservas@barcelonatours.email>',
            'Reply-To: Metransfers <reservas@barcelonatours.email>'
        );

        // Helper function to send email and log
        $send_email_helper = function($to, $sub, $message) use ($headers) {
            // Enforce SMTP for this sending (High Priority)
            add_action( 'phpmailer_init', array( $this, 'configure_smtp' ), 9999 );
            
            $sent = wp_mail($to, $sub, $message, $headers);
            
            remove_action( 'phpmailer_init', array( $this, 'configure_smtp' ), 9999 );
            
            if ($sent) {
                error_log("WPTB EMAIL SUCCESS to $to via wp_mail");
                return true;
            } else {
                error_log("WPTB FATAL SMTP ERROR to $to via wp_mail. Please check your SMTP plugin configuration.");
                return "Error enviando por wp_mail a $to";
            }
        };

        // C. Send to CLIENT
        if ( ! empty( $booking->customer_email ) ) {
            $send_email_helper( $booking->customer_email, $subject, $message );
        }

        // D. Send to ADMIN(s)
        $errors = array();
        
        $configured_email = get_option( 'wptb_admin_email_notifications' );

        // Ensure it only sends to the main routing email as requested
        $admin_emails = array( 'reservas@barcelonatours.email' );

        if ( ! empty( $configured_email ) && is_email( $configured_email ) && ! in_array( $configured_email, $admin_emails ) ) {
            $admin_emails[] = $configured_email;
        }

        $admin_emails = array_unique( $admin_emails );

        foreach ( $admin_emails as $admin_email ) {
            $res = $send_email_helper( $admin_email, $admin_subject, $message );
            if ( $res !== true ) $errors[] = $res;
        }
        
        // 2. Notificar al Contacto del Hotel (si aplica)
        if ( isset( $booking->hotel_token ) && ! empty( $booking->hotel_token ) ) {
            $hotel_query = new \WP_Query( array(
                'post_type'      => 'hotel_partner',
                'meta_key'       => '_hqp_token',
                'meta_value'     => $booking->hotel_token,
                'posts_per_page' => 1,
                'fields'         => 'ids'
            ) );
            if ( $hotel_query->have_posts() ) {
                $hotel_id = $hotel_query->posts[0];
                $contact_email = get_post_meta( $hotel_id, '_hqp_contact_email', true );
                if ( ! empty( $contact_email ) && is_email( $contact_email ) ) {
                    $send_email_helper( $contact_email, "Nueva Reserva desde tu Hotel (#{$booking_id})", $message );
                }
            }
        }
        
        // E. Send WhatsApp (Using the existing method directly or moving logic here if needed)
        // We can just call the existing method since it's still in the class
        $this->send_whatsapp_alert( $booking_id, $booking );
        
        // Return success or first error
        return empty($errors) ? true : $errors[0];
    }
}
