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
        add_action( 'wptb_new_booking_created', array( $this, 'notify_new_booking' ) );

        // ===== BOOKING AJAX ACTIONS =====
        // Get vehicles list
        add_action( 'wp_ajax_wptb_get_vehicles', array( $this, 'ajax_get_vehicles' ) );
        add_action( 'wp_ajax_nopriv_wptb_get_vehicles', array( $this, 'ajax_get_vehicles' ) );
        add_action( 'wp_ajax_wptb_get_quote', array( $this, 'ajax_get_quote' ) );
        add_action( 'wp_ajax_nopriv_wptb_get_quote', array( $this, 'ajax_get_quote' ) );

        add_action( 'wp_mail_failed', array( $this, 'capture_mail_error' ) );
    }
    
    public function capture_mail_error( $wp_error ) {
        if ( is_wp_error( $wp_error ) ) {
            $this->last_mail_error = $wp_error->get_error_message();
        }
    }

    public function enqueue_scripts() {
        $phase = \MeTransfers\Core\Assets::booking_phase();
        if ( 'none' === $phase ) {
            return;
        }

        // 1. STYLES
        // Funnel visuals are centralized in assets/css/{tokens,components,booking,checkout}.css.
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200', array(), null );
        
        // 2. GOOGLE MAPS API
        $api_key = \MeTransfers\Core\Settings::get( 'google_maps_api_key', '' );
        $maps_phases = array( 'search', 'details', 'payment', 'hotel' );

        if ( ! empty( $api_key ) && in_array( $phase, $maps_phases, true ) ) {
            $maps_url = add_query_arg(
                array(
                    'key'       => $api_key,
                    'libraries' => 'places,geometry',
                    'language'  => \MeTransfers\Booking\I18n::maps_language(),
                    'region'    => 'ES'
                ),
                'https://maps.googleapis.com/maps/api/js'
            );
            wp_enqueue_script( 'google-maps', $maps_url, array(), null, true );
        }

        // 3. BOOKING APP (search, vehicle and details only)
        $booking_phases = array( 'search', 'vehicle', 'details', 'hotel' );
        $booking_enqueued = in_array( $phase, $booking_phases, true );
        if ( $booking_enqueued ) {
            $deps = array( 'jquery', 'mt-booking-tracking' );
            if ( wp_script_is( 'google-maps', 'enqueued' ) ) {
                $deps[] = 'google-maps';
            }
            wp_enqueue_script( 'wptb-booking-js', WPTB_PLUGIN_URL . 'assets/js/booking-app.js', $deps, WPTB_VERSION, true );
        }

        // 4. PAYMENTS (payment and server-confirmed return only)
        $payment_enqueued = in_array( $phase, array( 'payment', 'confirmation' ), true );
        if ( $payment_enqueued ) {
            $payment_deps = array( 'jquery', 'mt-booking-tracking' );
            wp_enqueue_script( 'wptb-redsys-payment', WPTB_PLUGIN_URL . 'assets/js/redsys-payment.js', $payment_deps, WPTB_VERSION, true );
        }

        // 5. LOCALIZATION & DATA PASSING
        // Timezone: Spain (Madrid)
        $madrid_tz = new DateTimeZone('Europe/Madrid');
        $now_madrid = new DateTime('now', $madrid_tz);
        
        // Prepare global variables
        $wptb_vars = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wptb-booking-nonce' ),
            'vehicles_url' => \MeTransfers\Booking\I18n::url( '/seleccionar-vehiculo/' ),
            'details_url' => \MeTransfers\Booking\I18n::url( '/reservas-metransfers/' ),
            'payment_url' => \MeTransfers\Booking\I18n::url( '/pago/' ),
            'server_time' => $now_madrid->format('Y-m-d H:i:s'),
            'min_date' => $now_madrid->format('Y-m-d'),
            'google_maps_api_key' => $api_key,
            'home_url' => \MeTransfers\Booking\I18n::url( '/' ),
            'language' => \MeTransfers\Booking\I18n::language(),
            'terms_version' => MT_TERMS_VERSION,
            'strings' => \MeTransfers\Booking\I18n::strings(),
            'pdf_library_url' => 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
        );

        if ( $booking_enqueued ) {
            wp_localize_script( 'wptb-booking-js', 'wptb_vars', $wptb_vars );
        }
        if ( $payment_enqueued ) {
            wp_localize_script( 'wptb-redsys-payment', 'wptb_vars', $wptb_vars );
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

    

    
    
    
    
    

    // ===== NEW SHORTCODES =====
    

    

    public function save_booking() {
        check_ajax_referer( 'wptb-booking-nonce', 'security' );

        // Check for WooCommerce
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( array( 'message' => 'WooCommerce is not active.' ) );
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';

        $data = wp_unslash( $_POST );
        $required_fields = array( 'date', 'time', 'origin', 'destination', 'vehicle_id', 'fullName', 'email', 'phone' );
        if ( array_diff( $required_fields, array_keys( $data ) ) ) {
            wp_send_json_error( array( 'message' => 'Faltan datos obligatorios.' ) );
            return;
        }

        $date = sanitize_text_field( $data['date'] );
        $time = sanitize_text_field( $data['time'] );
        $origin = sanitize_text_field( $data['origin'] );
        $destination = sanitize_text_field( $data['destination'] );
        $vehicle_id = absint( $data['vehicle_id'] );
        $trip_type = isset( $data['trip_type'] ) && 'round_trip' === $data['trip_type'] ? 'round_trip' : 'one_way';
        $fullName = sanitize_text_field( $data['fullName'] );
        $email = sanitize_email( $data['email'] );
        $phone = sanitize_text_field( $data['phone'] );
        $passengers = isset( $data['passengers'] ) ? max( 1, absint( $data['passengers'] ) ) : 1;
        $suitcases = isset( $data['suitcases'] ) ? absint( $data['suitcases'] ) : 0;
        $carry_ons = isset( $data['carryOns'] ) ? absint( $data['carryOns'] ) : 0;
        $flight_number = isset( $data['flight'] ) ? sanitize_text_field( $data['flight'] ) : '';
        $notes = isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '';

        $vehicle = WPTB_Vehicle_Manager::get_vehicle( $vehicle_id );
        if ( ! $vehicle || '' === $fullName || ! is_email( $email ) || '' === $phone || '' === $origin || '' === $destination ) {
            wp_send_json_error( array( 'message' => 'Vehículo o datos de reserva no válidos.' ) );
            return;
        }
        if ( $passengers > (int) $vehicle->capacity || ( $suitcases + $carry_ons ) > (int) $vehicle->luggage_capacity ) {
            wp_send_json_error( array( 'message' => 'El vehículo no tiene capacidad suficiente para los pasajeros o el equipaje.' ) );
            return;
        }

        $language = \MeTransfers\Booking\I18n::normalizeLanguage( isset( $data['language'] ) ? $data['language'] : 'es' );
        $quote = \MeTransfers\Booking\QuoteService::create( array(
            'language'           => $language,
            'date'               => $date,
            'time'               => $time,
            'origin'             => $origin,
            'destination'        => $destination,
            'vehicle_id'         => $vehicle_id,
            'trip_type'          => $trip_type,
            'return_date'        => isset( $data['return_date'] ) ? $data['return_date'] : '',
            'return_time'        => isset( $data['return_time'] ) ? $data['return_time'] : '',
            'return_origin'      => isset( $data['return_origin'] ) ? $data['return_origin'] : '',
            'return_destination' => isset( $data['return_destination'] ) ? $data['return_destination'] : '',
        ) );
        if ( empty( $quote['valid'] ) ) {
            wp_send_json_error( array( 'message' => isset( $quote['error'] ) ? $quote['error'] : 'No se pudo calcular el precio de la reserva.' ) );
            return;
        }
        $distance = (float) $quote['total_distance_km'];
        $duration_minutes = (int) $quote['duration_minutes'];
        $price = (float) $quote['price'];

        // Add to WooCommerce Cart
        $product_id = get_option( 'wptb_transfer_product_id' );
        if ( ! $product_id ) {
            // Fallback if activation didn't run or option missing
            $product = get_page_by_title( 'Transfer Service', OBJECT, 'product' );
            if ($product) $product_id = $product->ID;
        }

        if ( $product_id ) {
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
                    'passengers' => $passengers,
                    'suitcases' => $suitcases,
                    'carry_ons' => $carry_ons,
                    'flight_number' => $flight_number,
                    'notes' => $notes,
                    'name' => $fullName,
                    'email' => $email,
                    'phone' => $phone
                )
            );

            $cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
            if ( ! $cart_item_key ) {
                wp_send_json_error( array( 'message' => 'No se pudo añadir la reserva al carrito.' ) );
                return;
            }

            // Save to DB as 'pending payment'
            $inserted = $wpdb->insert(
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
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'passengers' => $passengers,
                    'suitcases' => $suitcases,
                    'carry_ons' => $carry_ons,
                    'flight_number' => $flight_number,
                    'notes' => $notes,
                    'vehicle_id' => $vehicle_id,
                    'trip_type' => $trip_type,
                    'status' => 'added-to-cart',
                    'payment_status' => 'pending',
                    'booking_locale' => $language,
                    'created_at' => current_time( 'mysql' )
                ),
                array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
            );

            if ( false === $inserted || ! $wpdb->insert_id ) {
                WC()->cart->remove_cart_item( $cart_item_key );
                wp_send_json_error( array( 'message' => 'No se pudo guardar la reserva.' ) );
                return;
            }

            $booking_id = (int) $wpdb->insert_id;
            WC()->cart->cart_contents[ $cart_item_key ]['wptb_booking_data']['booking_id'] = $booking_id;
            WC()->cart->set_session();

            wp_send_json_success( array( 
                'message' => 'Redirecting to checkout...', 
                'redirect_url' => wc_get_checkout_url(),
                'booking_id' => $booking_id
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

            if ( ! empty( $data['booking_id'] ) ) {
                $item->add_meta_data( '_wptb_booking_id', absint( $data['booking_id'] ), true );
            }
            
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
            $booking_id = absint( $item->get_meta( '_wptb_booking_id', true ) );
            
            // If this item has booking data, find and process it
            if ( $booking_data ) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'wptb_bookings';
                
                if ( $booking_id ) {
                    $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking_id ) );
                } else {
                    // Compatibility with orders created before the booking ID was saved in item metadata.
                    error_log( 'WPTB migration fallback: resolving booking by billing email for legacy order #' . (int) $order_id . '.' );
                    $booking = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM $table_name
                        WHERE customer_email = %s
                        AND status IN ('pending', 'added-to-cart', 'pending_payment')
                        ORDER BY created_at DESC LIMIT 1",
                        $order->get_billing_email()
                    ) );
                }
                
                if ( $booking ) {
                    // Check if already notified to prevent duplicates
                    if ( in_array( $booking->status, array( 'confirmed', 'completed' ) ) && 
                         $booking->payment_status === 'paid' ) {
                        error_log( "WPTB WooCommerce: Booking #{$booking->id} already confirmed. Skipping notifications." );
                        return;
                    }
                    
                    // Atomic transition prevents duplicate notifications when the thank-you hook repeats.
                    $updated = $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $table_name
                             SET status = %s, payment_status = %s, payment_method = %s, payment_intent_id = %s
                             WHERE id = %d
                               AND NOT (status = %s AND payment_status = %s)",
                            'confirmed',
                            'paid',
                            sanitize_key( $order->get_payment_method() ),
                            $order->get_transaction_id() ?: 'wc_' . $order_id,
                            $booking->id,
                            'confirmed',
                            'paid'
                        )
                    );

                    if ( 1 !== $updated ) {
                        continue;
                    }
                    
                    // Re-fetch updated booking
                    $booking_updated = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE id = %d",
                        $booking->id
                    ) );
                    
                    // Send notifications
                    error_log( "WPTB WooCommerce: Sending notifications for booking #{$booking->id}" );
                    \MeTransfers\Analytics\PurchaseOutbox::recordPurchase( $booking_updated );
                    $this->process_booking_notifications( $booking->id, $booking_updated );
                    
                } else {
                    error_log( 'WPTB WooCommerce: No booking found for order #' . (int) $order_id . ' (email: ' . $order->get_billing_email() . ')' );
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

        $vehicles = WPTB_Vehicle_Manager::get_active_vehicles();
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
            $language = isset( $_POST['language'] ) ? sanitize_key( wp_unslash( $_POST['language'] ) ) : 'es';
            wp_send_json_error( array(
                'message' => \MeTransfers\Booking\I18n::text( 'no_vehicles', $language ),
            ) );
        }
    }

    /**
     * Return the same authoritative quote used by the payment endpoint.
     */
    public function ajax_get_quote() {
        check_ajax_referer( 'wptb-booking-nonce', 'security' );

        $result = \MeTransfers\Booking\QuoteService::create( wp_unslash( $_POST ) );
        if ( empty( $result['valid'] ) ) {
            wp_send_json_error( array(
                'code'    => 'invalid_quote',
                'message' => isset( $result['error'] ) ? $result['error'] : \MeTransfers\Booking\I18n::text( 'invalid_booking_request' ),
            ) );
            return;
        }

        wp_send_json_success( $result );
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

            $booking_json = isset( $_POST['booking_data'] ) ? wp_unslash( $_POST['booking_data'] ) : '';
            $booking_data = json_decode( $booking_json, true );
            $language = \MeTransfers\Booking\I18n::normalizeLanguage(
                is_array( $booking_data ) && ! empty( $booking_data['language'] ) ? $booking_data['language'] : 'es'
            );
            $required_fields = array( 'date', 'time', 'origin', 'destination', 'vehicle_id', 'price', 'customer_name', 'customer_email', 'customer_phone' );

            if ( ! is_array( $booking_data ) || array_diff( $required_fields, array_keys( $booking_data ) ) ) {
                wp_send_json_error( array( 'message' => \MeTransfers\Booking\I18n::text( 'invalid_booking_request', $language ) ) );
                return;
            }

            $terms_accepted = isset( $booking_data['terms_accepted'] ) && true === filter_var( $booking_data['terms_accepted'], FILTER_VALIDATE_BOOLEAN );
            $terms_version = isset( $booking_data['terms_version'] ) ? sanitize_text_field( $booking_data['terms_version'] ) : '';
            if ( ! $terms_accepted || ! hash_equals( (string) MT_TERMS_VERSION, $terms_version ) ) {
                wp_send_json_error( array(
                    'code'    => 'terms_required',
                    'message' => \MeTransfers\Booking\I18n::text( 'terms_server_required', $language ),
                ) );
                return;
            }

            $vehicle_id = absint( $booking_data['vehicle_id'] );
            $trip_type = isset( $booking_data['trip_type'] ) && 'round_trip' === $booking_data['trip_type'] ? 'round_trip' : 'one_way';
            $customer_name = sanitize_text_field( $booking_data['customer_name'] );
            $customer_phone = sanitize_text_field( $booking_data['customer_phone'] );
            $origin = sanitize_text_field( $booking_data['origin'] );
            $destination = sanitize_text_field( $booking_data['destination'] );
            if ( '' === $customer_name || '' === $customer_phone || '' === $origin || '' === $destination ) {
                wp_send_json_error( array( 'message' => \MeTransfers\Booking\I18n::text( 'missing_booking_fields', $language ) ) );
                return;
            }
            $return_date = '';
            $return_time = '';
            $return_origin = '';
            $return_destination = '';
            if ( 'round_trip' === $trip_type ) {
                $return_date = ! empty( $booking_data['return_date'] ) ? sanitize_text_field( $booking_data['return_date'] ) : '';
                $return_time = ! empty( $booking_data['return_time'] ) ? sanitize_text_field( $booking_data['return_time'] ) : '';
                $return_origin = ! empty( $booking_data['return_origin'] ) ? sanitize_text_field( $booking_data['return_origin'] ) : '';
                $return_destination = ! empty( $booking_data['return_destination'] ) ? sanitize_text_field( $booking_data['return_destination'] ) : '';
                if ( '' === $return_date || '' === $return_time || '' === $return_origin || '' === $return_destination ) {
                    wp_send_json_error( array( 'message' => \MeTransfers\Booking\I18n::text( 'return_fields_required', $language ) ) );
                    return;
                }

            }

            $quote = \MeTransfers\Booking\QuoteService::create( array(
                'language'           => $language,
                'date'               => $booking_data['date'],
                'time'               => $booking_data['time'],
                'origin'             => $origin,
                'destination'        => $destination,
                'vehicle_id'         => $vehicle_id,
                'trip_type'          => $trip_type,
                'return_date'        => $return_date,
                'return_time'        => $return_time,
                'return_origin'      => $return_origin,
                'return_destination' => $return_destination,
            ) );
            if ( empty( $quote['valid'] ) ) {
                wp_send_json_error( array(
                    'code'    => 'invalid_quote',
                    'message' => isset( $quote['error'] ) ? $quote['error'] : \MeTransfers\Booking\I18n::text( 'invalid_server_price', $language ),
                ) );
                return;
            }

            $server_price = (float) $quote['price'];
            $distance_km = (float) $quote['total_distance_km'];
            $duration_minutes = (int) $quote['duration_minutes'];
            $displayed_price = (float) $booking_data['price'];
            if ( abs( $server_price - $displayed_price ) > 0.01 ) {
                wp_send_json_error( array(
                    'code' => 'price_changed',
                    'message' => \MeTransfers\Booking\I18n::text( 'price_changed', $language ),
                    'server_price' => $server_price,
                ) );
                return;
            }

            $vehicle = \WPTB_Vehicle_Manager::get_vehicle( $vehicle_id );
            $passengers = ! empty( $booking_data['passengers'] ) ? absint( $booking_data['passengers'] ) : 1;
            $suitcases = ! empty( $booking_data['suitcases'] ) ? absint( $booking_data['suitcases'] ) : 0;
            $carry_ons = ! empty( $booking_data['carry_ons'] ) ? absint( $booking_data['carry_ons'] ) : 0;
            if ( ! $vehicle || $passengers > (int) $vehicle->capacity || ( $suitcases + $carry_ons ) > (int) $vehicle->luggage_capacity ) {
                wp_send_json_error( array( 'message' => \MeTransfers\Booking\I18n::text( 'vehicle_capacity_error', $language ) ) );
                return;
            }

            $gateway = new \MeTransfers\Payments\Redsys\Gateway();
            if ( ! $gateway->is_configured() ) {
                throw new \RuntimeException( 'Redsys is not configured.' );
            }

            // Save Pending Booking
            // ---------------------------------------------------------
            global $wpdb;
            $table_name = $wpdb->prefix . 'wptb_bookings';

            $booking_id = 0;
            $customer_email = ! empty( $booking_data['customer_email'] ) ? sanitize_email( $booking_data['customer_email'] ) : '';

            if ( ! is_email( $customer_email ) ) {
                wp_send_json_error( array( 'message' => \MeTransfers\Booking\I18n::text( 'invalid_contact', $language ) ) );
                return;
            }

            $data_db = array(
                'booking_date' => sanitize_text_field( $booking_data['date'] ),
                'booking_time' => sanitize_text_field( $booking_data['time'] ),
                'origin' => $origin,
                'destination' => $destination,
                'distance_km' => $distance_km,
                'duration_minutes' => $duration_minutes,
                'vehicle_id' => $vehicle_id,
                'trip_type' => $trip_type,
                'price' => $server_price,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'passengers' => $passengers,
                'suitcases' => $suitcases,
                'carry_ons' => $carry_ons,
                'flight_number' => ! empty( $booking_data['flight_number'] ) ? sanitize_text_field( $booking_data['flight_number'] ) : '',
                'notes' => ! empty( $booking_data['notes'] ) ? sanitize_textarea_field( $booking_data['notes'] ) : '',
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'payment_method' => 'redsys',
                'booking_locale' => $language,
                'terms_accepted_at' => current_time( 'mysql' ),
                'terms_version' => $terms_version,
                'analytics_client_id' => ! empty( $booking_data['analytics_client_id'] ) ? sanitize_text_field( $booking_data['analytics_client_id'] ) : '',
                'created_at' => current_time( 'mysql' )
            );

            if ( 'round_trip' === $trip_type ) {
                $data_db['return_date'] = $return_date;
                $data_db['return_time'] = $return_time;
                $data_db['return_pickup_address'] = $return_origin;
                $data_db['return_dropoff_address'] = $return_destination;
            }
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

            $result = $wpdb->insert( $table_name, $data_db, $format_db );
            $booking_id = $wpdb->insert_id;

            if ( $result === false || $booking_id <= 0 ) {
                wp_send_json_error( array( 'message' => \MeTransfers\Booking\I18n::text( 'booking_save_error', $language ) ) );
                return;
            }

            $amount = (int) round( $data_db['price'] * 100 );
            $order_id = str_pad( $booking_id, 12, '0', STR_PAD_LEFT );

            // Save Order ID to DB
            $order_saved = $wpdb->update(
                $table_name,
                array( 'payment_intent_id' => $order_id ),
                array( 'id' => $booking_id ),
                array( '%s' ),
                array( '%d' )
            );

            if ( false === $order_saved ) {
                throw new \RuntimeException( 'No se pudo asociar la referencia de pago a la reserva.' );
            }

            $payment = $gateway->generate_payment_form( $booking_id, $amount, $order_id, $data_db['customer_name'], $language );

            $new_booking_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking_id ) );
            if ( $new_booking_obj ) {
                $this->process_booking_notifications( $booking_id, $new_booking_obj, 'pending' );
            }

            wp_send_json_success( array(
                'url' => $payment['url'],
                'ds_signature_version' => $payment['version'],
                'ds_merchant_parameters' => $payment['params'],
                'ds_signature' => $payment['signature'],
                'booking_id' => $booking_id
            ));

        } catch ( \Throwable $e ) {
            error_log( 'WPTB Redsys payment creation failed: ' . $e->getMessage() );
            $error_language = isset( $language ) ? $language : 'es';
            wp_send_json_error( array( 'message' => \MeTransfers\Booking\I18n::text( 'payment_start_error', $error_language ) ) );
        }
    }
    
    public function listen_redsys_ipn() {
        if ( ! isset( $_GET['wptb_redsys_ipn'] ) ) {
            return;
        }

        if ( ! isset( $_POST['Ds_MerchantParameters'], $_POST['Ds_Signature'], $_POST['Ds_SignatureVersion'] ) ) {
            status_header( 400 );
            exit;
        }

        $params = (string) wp_unslash( $_POST['Ds_MerchantParameters'] );
        $signature = (string) wp_unslash( $_POST['Ds_Signature'] );
        $version = sanitize_text_field( wp_unslash( $_POST['Ds_SignatureVersion'] ) );

        try {
            $gateway = new \MeTransfers\Payments\Redsys\Gateway();
            $notification = $gateway->verify_notification( $params, $signature, $version );
        } catch ( \Throwable $e ) {
            error_log( 'WPTB Redsys notification verification failed: ' . $e->getMessage() );
            status_header( 500 );
            exit;
        }

        if ( empty( $notification['valid'] ) ) {
            error_log( 'WPTB Redsys notification rejected: invalid signature or parameters.' );
            status_header( 400 );
            exit;
        }

        if ( empty( $notification['authorized'] ) ) {
            status_header( 200 );
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        $order_id = $notification['order_id'];
        $booking = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE payment_intent_id = %s AND payment_method = %s",
                $order_id,
                'redsys'
            )
        );

        if ( ! $booking ) {
            status_header( 200 );
            exit;
        }

        $notification_parameters = array_change_key_case( $notification['parameters'], CASE_LOWER );
        $reported_amount = isset( $notification_parameters['ds_amount'] )
            ? (int) $notification_parameters['ds_amount']
            : 0;
        $expected_amount = (int) round( (float) $booking->price * 100 );
        if ( $reported_amount !== $expected_amount ) {
            error_log( 'WPTB Redsys notification rejected: amount mismatch for booking #' . (int) $booking->id . '.' );
            status_header( 400 );
            exit;
        }

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name
                 SET status = %s, payment_status = %s
                 WHERE id = %d
                   AND NOT (status = %s AND payment_status = %s)",
                'confirmed',
                'paid',
                $booking->id,
                'confirmed',
                'paid'
            )
        );

        if ( false === $updated ) {
            status_header( 500 );
            exit;
        }

        if ( 1 === $updated ) {
            $booking_updated = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking->id ) );
            if ( $booking_updated ) {
                \MeTransfers\Analytics\PurchaseOutbox::recordPurchase( $booking_updated );
                $this->process_booking_notifications( $booking->id, $booking_updated );
            }
        }

        status_header( 200 );
        exit;
    }

    /**
     * Optional SMTP configuration sourced from platform settings.
     */

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
        \MeTransfers\Notifications\NotificationService::configureSmtp( $phpmailer );
    }

    /**
     * @deprecated 6.0.0 Use NotificationService::bookingConfirmed().
     */
    public function send_booking_emails( $booking_id, $booking ) {
        _deprecated_function( __METHOD__, '6.0.0', '\\MeTransfers\\Notifications\\NotificationService::bookingConfirmed' );
        return \MeTransfers\Notifications\NotificationService::bookingConfirmed( $booking_id, $booking );
    }

    /**
     * @deprecated 6.0.0 Use NotificationService::sendWhatsapp().
     */
    public function send_whatsapp_alert( $booking_id, $booking ) {
        _deprecated_function( __METHOD__, '6.0.0', '\\MeTransfers\\Notifications\\NotificationService::sendWhatsapp' );
        return \MeTransfers\Notifications\NotificationService::sendWhatsapp( $booking_id, $booking );
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
     * Backwards-compatible facade for callers in the imported admin/hotel code.
     */
    public function process_booking_notifications( $booking_id, $booking, $status_context = 'confirmed' ) {
        return 'pending' === $status_context
            ? \MeTransfers\Notifications\NotificationService::bookingPending( $booking_id, $booking )
            : \MeTransfers\Notifications\NotificationService::bookingConfirmed( $booking_id, $booking );
    }

}
