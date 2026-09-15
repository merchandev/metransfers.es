<?php
/**
 * Vehicle Manager Class
 * Handles all CRUD operations for vehicles
 */

class WPTB_Vehicle_Manager {

    /**
     * Get all active vehicles
     */
    public static function get_active_vehicles( $args = array() ) {
        global $wpdb;
        $table_vehicles = $wpdb->prefix . 'wptb_vehicles';
        
        $sql = "SELECT * FROM $table_vehicles WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
        
        $results = $wpdb->get_results( $sql );
        
        if ( is_null($results) || ! empty($wpdb->last_error) ) {
            error_log("WPTB DB Error: " . $wpdb->last_error);
        }
        
        return $results;
    }
    
    /**
     * Get vehicle by ID
     */
    public static function get_vehicle( $vehicle_id ) {
        global $wpdb;
        $table_vehicles = $wpdb->prefix . 'wptb_vehicles';
        $table_types = $wpdb->prefix . 'wptb_vehicle_types';
        
        $vehicle_id = absint( $vehicle_id );
        
        $sql = $wpdb->prepare(
            "SELECT v.*, t.name as type_name, t.slug as type_slug 
             FROM $table_vehicles v
             LEFT JOIN $table_types t ON v.vehicle_type_id = t.id
             WHERE v.id = %d",
            $vehicle_id
        );
        
        return $wpdb->get_row( $sql );
    }
    
    /**
     * Get vehicle images
     */
    public static function get_vehicle_images( $vehicle_id ) {
        global $wpdb;
        $table_images = $wpdb->prefix . 'wptb_vehicle_images';
        
        $vehicle_id = absint( $vehicle_id );
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_images 
             WHERE vehicle_id = %d 
             ORDER BY is_primary DESC, display_order ASC",
            $vehicle_id
        );
        
        return $wpdb->get_results( $sql );
    }
    
    /**
     * Get primary image URL
     */
    public static function get_primary_image( $vehicle_id ) {
        global $wpdb;
        $table_images = $wpdb->prefix . 'wptb_vehicle_images';
        
        $vehicle_id = absint( $vehicle_id );
        
        $image = $wpdb->get_var( $wpdb->prepare(
            "SELECT image_url FROM $table_images 
             WHERE vehicle_id = %d AND is_primary = 1 
             LIMIT 1",
            $vehicle_id
        ));
        
        // If no primary, get first image
        if ( ! $image ) {
            $image = $wpdb->get_var( $wpdb->prepare(
                "SELECT image_url FROM $table_images 
                 WHERE vehicle_id = %d 
                 ORDER BY display_order ASC 
                 LIMIT 1",
                $vehicle_id
            ));
        }
        
        return $image ? $image : WPTB_PLUGIN_URL . 'assets/images/vehicle-placeholder.png';
    }
    
    /**
     * Create or update vehicle
     */
    public static function save_vehicle( $data ) {
        global $wpdb;
        $table_vehicles = $wpdb->prefix . 'wptb_vehicles';
        
        // Sanitize data
        $vehicle_data = array(
            'name' => sanitize_text_field( $data['name'] ),
            'vehicle_type_id' => absint( $data['vehicle_type_id'] ),
            'description' => wp_kses_post( $data['description'] ),
            'capacity' => absint( $data['capacity'] ),
            'luggage_capacity' => absint( $data['luggage_capacity'] ),
            'initial_fee' => floatval( $data['initial_fee'] ),
            'min_transfer_price' => floatval( $data['min_transfer_price'] ),
            'min_oneway_price' => floatval( $data['min_oneway_price'] ),
            'min_roundtrip_price' => floatval( $data['min_roundtrip_price'] ),
            'price_per_km_oneway' => floatval( $data['price_per_km_oneway'] ),
            'price_per_km_roundtrip' => floatval( $data['price_per_km_roundtrip'] ),
            'price_per_hour' => floatval( $data['price_per_hour'] ),
            'is_active' => isset( $data['is_active'] ) ? 1 : 0,
            'display_order' => absint( $data['display_order'] )
        );
        
        if ( isset( $data['id'] ) && $data['id'] > 0 ) {
            // Update
            $wpdb->update( 
                $table_vehicles, 
                $vehicle_data, 
                array( 'id' => absint( $data['id'] ) ),
                array( '%s', '%d', '%s', '%d', '%d', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d' ),
                array( '%d' )
            );
            return absint( $data['id'] );
        } else {
            // Insert
            $wpdb->insert( 
                $table_vehicles, 
                $vehicle_data,
                array( '%s', '%d', '%s', '%d', '%d', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d' )
            );
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete vehicle
     */
    public static function delete_vehicle( $vehicle_id ) {
        global $wpdb;
        $vehicle_id = absint( $vehicle_id );
        
        // Delete images first
        $table_images = $wpdb->prefix . 'wptb_vehicle_images';
        $wpdb->delete( $table_images, array( 'vehicle_id' => $vehicle_id ), array( '%d' ) );
        
        // Delete vehicle
        $table_vehicles = $wpdb->prefix . 'wptb_vehicles';
        return $wpdb->delete( $table_vehicles, array( 'id' => $vehicle_id ), array( '%d' ) );
    }
    
    /**
     * Add vehicle image
     */
    public static function add_vehicle_image( $vehicle_id, $image_url, $is_primary = false ) {
        global $wpdb;
        $table_images = $wpdb->prefix . 'wptb_vehicle_images';
        
        // If this is primary, unset other primaries
        if ( $is_primary ) {
            $wpdb->update(
                $table_images,
                array( 'is_primary' => 0 ),
                array( 'vehicle_id' => absint( $vehicle_id ) ),
                array( '%d' ),
                array( '%d' )
            );
        }
        
        // Get next display order
        $max_order = $wpdb->get_var( $wpdb->prepare(
            "SELECT MAX(display_order) FROM $table_images WHERE vehicle_id = %d",
            absint( $vehicle_id )
        ));
        
        $wpdb->insert(
            $table_images,
            array(
                'vehicle_id' => absint( $vehicle_id ),
                'image_url' => esc_url_raw( $image_url ),
                'is_primary' => $is_primary ? 1 : 0,
                'display_order' => ( $max_order + 1 )
            ),
            array( '%d', '%s', '%d', '%d' )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Delete vehicle image
     */
    public static function delete_vehicle_image( $image_id ) {
        global $wpdb;
        $table_images = $wpdb->prefix . 'wptb_vehicle_images';
        
        return $wpdb->delete( $table_images, array( 'id' => absint( $image_id ) ), array( '%d' ) );
    }
    
    /**
     * Get all vehicle types
     */
    public static function get_vehicle_types() {
        global $wpdb;
        $table_types = $wpdb->prefix . 'wptb_vehicle_types';
        
        return $wpdb->get_results( "SELECT * FROM $table_types ORDER BY display_order ASC" );
    }
}
