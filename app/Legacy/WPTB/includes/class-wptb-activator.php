<?php

class WPTB_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        // Table 1: Bookings (Updated with vehicle and trip type)
        $table_bookings = $wpdb->prefix . 'wptb_bookings';
        $sql_bookings = "CREATE TABLE $table_bookings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            origin text NOT NULL,
            destination text NOT NULL,
            distance_km float,
            duration_minutes int,
            price decimal(10,2),
            customer_name varchar(150),
            customer_email varchar(150),
            customer_phone varchar(50),
            flight_number varchar(50),
            passengers int DEFAULT 1,
            suitcases int DEFAULT 0,
            carry_ons int DEFAULT 0,
            notes text,
            vehicle_id mediumint(9),
            trip_type varchar(20) DEFAULT 'one_way',
            return_pickup_address text,
            return_dropoff_address text,
            return_date date,
            return_time time,
            status varchar(20) DEFAULT 'pending',
            payment_method varchar(50),
            payment_intent_id varchar(255),
            payment_status varchar(20) DEFAULT 'pending',
            hotel_token varchar(255),
            source varchar(50) DEFAULT 'Metransfers',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY vehicle_id (vehicle_id),
            KEY booking_date (booking_date),
            KEY status (status),
            KEY payment_intent_id (payment_intent_id),
            KEY hotel_token (hotel_token)
        ) $charset_collate;";
        dbDelta( $sql_bookings );

        // Asegurar que el AUTO_INCREMENT inicia en 10000 (IDs para Getnet siempre >= 10000)
        $max_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM $table_bookings" );
        if ( $max_id < 10000 ) {
            $wpdb->query( "ALTER TABLE $table_bookings AUTO_INCREMENT = 10000" );
        }

        // Table 1.5: Backups History
        $table_backups = $wpdb->prefix . 'wptb_backups';
        $sql_backups = "CREATE TABLE $table_backups (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            filepath text NOT NULL,
            type varchar(50) DEFAULT 'manual',
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql_backups );

        // Table 2: Vehicle Types
        $table_types = $wpdb->prefix . 'wptb_vehicle_types';
        $sql_types = "CREATE TABLE $table_types (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            description text,
            icon varchar(255),
            display_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        dbDelta( $sql_types );
        
        // Table 3: Vehicles
        $table_vehicles = $wpdb->prefix . 'wptb_vehicles';
        $sql_vehicles = "CREATE TABLE $table_vehicles (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            vehicle_type_id mediumint(9) NOT NULL,
            description text,
            capacity int NOT NULL DEFAULT 4,
            luggage_capacity int DEFAULT 2,
            initial_fee decimal(10,2) DEFAULT 0,
            min_transfer_price decimal(10,2) DEFAULT 0,
            min_oneway_price decimal(10,2) DEFAULT 0,
            min_roundtrip_price decimal(10,2) DEFAULT 0,
            price_per_km_oneway decimal(10,2) DEFAULT 0,
            price_per_km_roundtrip decimal(10,2) DEFAULT 0,
            price_per_hour decimal(10,2) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            is_normal tinyint(1) DEFAULT 1,
            is_hotel tinyint(1) DEFAULT 1,
            display_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY vehicle_type_id (vehicle_type_id),
            KEY is_active (is_active),
            KEY capacity (capacity)
        ) $charset_collate;";
        dbDelta( $sql_vehicles );
        
        // Table 4: Vehicle Images
        $table_images = $wpdb->prefix . 'wptb_vehicle_images';
        $sql_images = "CREATE TABLE $table_images (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vehicle_id mediumint(9) NOT NULL,
            image_url varchar(500) NOT NULL,
            image_alt varchar(255),
            display_order int DEFAULT 0,
            is_primary tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY vehicle_id (vehicle_id),
            KEY is_primary (is_primary)
        ) $charset_collate;";
        dbDelta( $sql_images );
        
        // Insert default vehicle types if table is empty
        $types_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_types" );
        if ( $types_count == 0 ) {
            $default_types = array(
                array( 'name' => 'Sedán', 'slug' => 'sedan', 'description' => 'Vehículo sedán estándar para 3-4 pasajeros', 'display_order' => 1 ),
                array( 'name' => 'SUV', 'slug' => 'suv', 'description' => 'Vehículo grande tipo SUV para 5-6 pasajeros', 'display_order' => 2 ),
                array( 'name' => 'Van', 'slug' => 'van', 'description' => 'Furgoneta para grupos de 7-8 pasajeros', 'display_order' => 3 ),
                array( 'name' => 'Minibús', 'slug' => 'minibus', 'description' => 'Vehículo para grupos grandes de 9-15 pasajeros', 'display_order' => 4 ),
                array( 'name' => 'Lujo', 'slug' => 'luxury', 'description' => 'Vehículo de lujo premium', 'display_order' => 5 )
            );
            
            foreach ( $default_types as $type ) {
                $wpdb->insert( $table_types, $type );
            }
        }

        // Create Default Product for WooCommerce
        if ( class_exists( 'WooCommerce' ) ) {
            $product_id = get_option( 'wptb_transfer_product_id' );
            
            if ( ! $product_id || ! get_post( $product_id ) ) {
                $post_id = wp_insert_post( array(
                    'post_title'   => 'Transfer Service',
                    'post_content' => 'Booking transfer payment.',
                    'post_status'  => 'publish',
                    'post_type'    => 'product',
                ));
                
                if ( $post_id ) {
                    update_post_meta( $post_id, '_visibility', 'hidden' );
                    update_post_meta( $post_id, '_stock_status', 'instock' );
                    update_post_meta( $post_id, '_price', '1' );
                    update_post_meta( $post_id, '_regular_price', '1' );
                    update_post_meta( $post_id, '_virtual', 'yes' );
                    update_option( 'wptb_transfer_product_id', $post_id );
                }
            }
        }
        
        // Create Booking Details Page
        $page_title = 'Finalizar Reserva';
        $page_slug = 'reservas-metransfers';
        $page_content = '[wptb_booking_details]';
        
        $page_check = get_page_by_path( $page_slug );
        $page_id = $page_check ? $page_check->ID : 0;
        
        if ( ! $page_id ) {
            $page_id = wp_insert_post( array(
                'post_title'    => $page_title,
                'post_name'     => $page_slug,
                'post_content'  => $page_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'comment_status'=> 'closed'
            ));
        }

        // Create Vehicle Selection Page (Step 2)
        $vehicles_page_title = 'Seleccionar Vehiculo';
        $vehicles_page_slug = 'seleccionar-vehiculo';
        $vehicles_page_content = '[wptb_vehicle_selection]';

        $vehicles_page_check = get_page_by_path( $vehicles_page_slug );
        $vehicles_page_id = $vehicles_page_check ? $vehicles_page_check->ID : 0;

        if ( ! $vehicles_page_id ) {
            $vehicles_page_id = wp_insert_post( array(
                'post_title'    => $vehicles_page_title,
                'post_name'     => $vehicles_page_slug,
                'post_content'  => $vehicles_page_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'comment_status'=> 'closed'
            ));
        }
        
        // Create Payment Page (Generic)
        $payment_page_title = 'Finalizar Pago';
        $payment_page_slug = 'pago';
        $payment_page_content = '[wptb_checkout]';
        
        $payment_page_check = get_page_by_path( $payment_page_slug );
        $payment_page_id = $payment_page_check ? $payment_page_check->ID : 0;
        
        if ( ! $payment_page_id ) {
            $payment_page_id = wp_insert_post( array(
                'post_title'    => $payment_page_title,
                'post_name'     => $payment_page_slug,
                'post_content'  => $payment_page_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'comment_status'=> 'closed'
            ));
        }
    }
}
