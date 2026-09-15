<?php
/**
 * Pricing Calculator Class
 * Handles all price calculations based on vehicle, distance, and trip type
 */

class WPTB_Pricing {

    /**
     * Calculate price for a booking
     * 
     * @param int $vehicle_id Vehicle ID
     * @param float $distance_km Distance in kilometers
     * @param string $trip_type 'one_way' or 'round_trip'
     * @param int $duration_minutes Optional duration in minutes for hourly pricing
     * @return array Array with 'price' and 'breakdown' details
     */
    public static function calculate_price( $vehicle_id, $distance_km, $trip_type = 'one_way', $duration_minutes = 0 ) {
        return \MeTransfers\Pricing\Calculator::calculate( $vehicle_id, $distance_km, $trip_type, $duration_minutes );
    }

    
    /**
     * Get price range for a vehicle
     */
    public static function get_vehicle_price_range( $vehicle_id ) {
        $vehicle = WPTB_Vehicle_Manager::get_vehicle( $vehicle_id );
        
        if ( ! $vehicle ) {
            return null;
        }
        
        $prices = array(
            floatval( $vehicle->min_transfer_price ),
            floatval( $vehicle->min_oneway_price ),
            floatval( $vehicle->min_roundtrip_price )
        );
        
        $prices = array_filter( $prices, function( $p ) { return $p > 0; } );
        
        if ( empty( $prices ) ) {
            return array(
                'min' => 0,
                'max' => 0,
                'display' => 'Consultar precio'
            );
        }
        
        $min = min( $prices );
        $max = max( $prices );
        
        return array(
            'min' => $min,
            'max' => $max,
            'display' => $min === $max 
                ? 'Desde €' . number_format( $min, 2 )
                : '€' . number_format( $min, 2 ) . ' - €' . number_format( $max, 2 )
        );
    }
    
    /**
     * Validate if booking meets minimum requirements
     */
    public static function validate_booking_price( $vehicle_id, $distance_km, $trip_type, $price ) {
        $calculated = self::calculate_price( $vehicle_id, $distance_km, $trip_type );
        
        if ( isset( $calculated['error'] ) ) {
            return array(
                'valid' => false,
                'message' => $calculated['error']
            );
        }
        
        // Allow some tolerance (0.01) for rounding
        if ( floatval( $price ) < ( $calculated['price'] - 0.01 ) ) {
            return array(
                'valid' => false,
                'message' => 'El precio no cumple con el mínimo requerido de €' . number_format( $calculated['price'], 2 )
            );
        }
        
        return array(
            'valid' => true,
            'calculated_price' => $calculated['price']
        );
    }
}
