<?php
namespace MeTransfers\Pricing;

class Calculator {
    /**
     * Calculate price for a booking
     * 
     * @param int $vehicle_id Vehicle ID
     * @param float $distance_km Distance in kilometers
     * @param string $trip_type 'one_way' or 'round_trip'
     * @param int $duration_minutes Optional duration in minutes for hourly pricing
     * @return array Array with 'price' and 'breakdown' details
     */
    public static function calculate( $vehicle_id, $distance_km, $trip_type = 'one_way', $duration_minutes = 0 ) {
        // Validate inputs
        $vehicle_id = absint( $vehicle_id );
        
        // Helper to handle commas in inputs
        $distance_km = str_replace( ',', '.', (string) $distance_km );
        $distance_km = floatval( $distance_km );
        
        $duration_minutes = absint( $duration_minutes );
        
        if ( ! $vehicle_id || $distance_km <= 0 ) {
            return array( 'price' => 0, 'breakdown' => array() );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_vehicles';

        $cache_key = 'wptb_vehicle_' . $vehicle_id;
        $vehicle = wp_cache_get( $cache_key, 'wptb' );
        
        if ( false === $vehicle ) {
            $vehicle = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $vehicle_id ) );
            if ( $vehicle ) {
                wp_cache_set( $cache_key, $vehicle, 'wptb', 3600 );
            }
        }

        if ( ! $vehicle ) {
            return array( 'price' => 0, 'breakdown' => array() );
        }

        $price = 0;
        $breakdown = array();

        // Base/Initial fee
        $initial_fee = floatval( $vehicle->initial_fee );
        if ( $initial_fee > 0 ) {
            $price += $initial_fee;
            $breakdown['initial_fee'] = $initial_fee;
        }

        if ( $trip_type === 'round_trip' ) {
            // Check minimum roundtrip price
            $min_price = floatval( $vehicle->min_roundtrip_price );
            $per_km = floatval( $vehicle->price_per_km_roundtrip );
            
            // If roundtrip km price is 0, fallback to oneway * 2
            if ( $per_km <= 0 ) {
                $per_km = floatval( $vehicle->price_per_km_oneway ) * 2;
            }
            
            // Total distance for calculation (usually roundtrip means distance * 2)
            $total_distance = $distance_km * 2;
            $calc_price = $total_distance * $per_km;
            
            // Apply whichever is higher: calculated or minimum
            $trip_price = max( $calc_price, $min_price );
            $price += $trip_price;
            
            $breakdown['trip_price'] = $trip_price;
            $breakdown['calculation'] = "$total_distance km x $per_km/km";
            if ( $min_price > $calc_price ) {
                $breakdown['note'] = "Minimum round-trip price applied";
            }
        } else {
            // One-way calculation
            $min_price = floatval( $vehicle->min_oneway_price );
            // Fallback to min_transfer_price if min_oneway is 0
            if ( $min_price <= 0 ) {
                $min_price = floatval( $vehicle->min_transfer_price );
            }
            
            $per_km = floatval( $vehicle->price_per_km_oneway );
            
            $calc_price = $distance_km * $per_km;
            
            $trip_price = max( $calc_price, $min_price );
            $price += $trip_price;
            
            $breakdown['trip_price'] = $trip_price;
            $breakdown['calculation'] = "$distance_km km x $per_km/km";
            if ( $min_price > $calc_price ) {
                $breakdown['note'] = "Minimum one-way price applied";
            }
        }

        // Apply filters (legacy compatibility)
        $price = apply_filters( 'wptb_calculated_price', $price, $vehicle_id, $distance_km, $trip_type );

        return array(
            'price' => number_format( $price, 2, '.', '' ),
            'breakdown' => $breakdown
        );
    }
}