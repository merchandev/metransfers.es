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
        // Validate inputs
        $vehicle_id = absint( $vehicle_id );
        
        // Helper to handle commas in inputs
        $distance_km = str_replace( ',', '.', (string) $distance_km );
        $distance_km = floatval( $distance_km );
        
        $duration_minutes = absint( $duration_minutes );
        
        if ( ! $vehicle_id || $distance_km <= 0 ) {
            return array(
                'price' => 0,
                'error' => 'Parámetros inválidos'
            );
        }
        
        // Get vehicle
        $vehicle = WPTB_Vehicle_Manager::get_vehicle( $vehicle_id );
        
        if ( ! $vehicle ) {
            return array(
                'price' => 0,
                'error' => 'Vehículo no encontrado'
            );
        }
        
        $breakdown = array();
        $final_price = 0;
        
        // Initial Fee
        $initial_fee = floatval( $vehicle->initial_fee );
        if ( $initial_fee > 0 ) {
            $breakdown['initial_fee'] = '€' . number_format( $initial_fee, 2 );
        }

        
        // Calculate based on trip type
        // Calculate based on trip type
        if ( $trip_type === 'round_trip' ) {
            // Round trip calculation
            $rate = floatval( $vehicle->price_per_km_roundtrip );
            $effective_distance = $distance_km * 2; // Double distance for round trip
            $distance_cost = $effective_distance * $rate;
            $min_price = floatval( $vehicle->min_roundtrip_price );
            
            $breakdown['tipo'] = 'Ida y Vuelta';
            $breakdown['distancia'] = $effective_distance . ' km (Ida y Vuelta)';
            $breakdown['precio_km'] = '€' . number_format( $rate, 2 );
            $breakdown['coste_distancia'] = '€' . number_format( $distance_cost, 2 );
            $breakdown['fee_inicial'] = '€' . number_format( $initial_fee, 2 );
            
            // Total calculated before min check
            $calculated_total = $distance_cost + $initial_fee;
            $breakdown['total_calculado'] = '€' . number_format( $calculated_total, 2 );
            
            $breakdown['minimo_ida_vuelta'] = '€' . number_format( $min_price, 2 );
            
            // Use the higher of calculated or minimum
            $final_price = max( $calculated_total, $min_price );
            
        } else {
            // One way calculation
            $rate = floatval( $vehicle->price_per_km_oneway );
            $distance_cost = $distance_km * $rate;
            $min_price = floatval( $vehicle->min_oneway_price );
            
            $breakdown['tipo'] = 'Solo Ida';
            $breakdown['distancia'] = $distance_km . ' km';
            $breakdown['precio_km'] = '€' . number_format( $rate, 2 );
            $breakdown['coste_distancia'] = '€' . number_format( $distance_cost, 2 );
            $breakdown['fee_inicial'] = '€' . number_format( $initial_fee, 2 );
            
            // Total calculated before min check
            $calculated_total = $distance_cost + $initial_fee;
            $breakdown['total_calculado'] = '€' . number_format( $calculated_total, 2 );

            $breakdown['minimo_ida'] = '€' . number_format( $min_price, 2 );
            
            // Use the higher of calculated or minimum
            $final_price = max( $calculated_total, $min_price );
        }
        
        // Apply general minimum transfer price
        $min_transfer = floatval( $vehicle->min_transfer_price );
        if ( $min_transfer > 0 ) {
            $breakdown['minimo_traslado'] = '€' . number_format( $min_transfer, 2 );
            $final_price = max( $final_price, $min_transfer );
        }
        
        // Hourly pricing (if applicable and specified)
        if ( $duration_minutes > 0 && floatval( $vehicle->price_per_hour ) > 0 ) {
            $hours = $duration_minutes / 60;
            $hourly_price = $hours * floatval( $vehicle->price_per_hour );
            
            $breakdown['duracion'] = round( $hours, 1 ) . ' horas';
            $breakdown['precio_hora'] = '€' . number_format( $vehicle->price_per_hour, 2 );
            $breakdown['subtotal_horas'] = '€' . number_format( $hourly_price, 2 );
            
            // Use hourly if it's higher
            if ( $hourly_price > $final_price ) {
                $final_price = $hourly_price;
                $breakdown['metodo_calculo'] = 'Por Hora';
            } else {
                $breakdown['metodo_calculo'] = 'Por Distancia';
            }
        } else {
            $breakdown['metodo_calculo'] = 'Por Distancia';
        }
        
        return array(
            'price' => round( $final_price, 2 ),
            'breakdown' => $breakdown,
            'vehicle' => array(
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'type' => $vehicle->type_name
            )
        );
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
