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
        $vehicle_id = absint( $vehicle_id );
        $distance_km = str_replace( ',', '.', (string) $distance_km );
        $distance_km = floatval( $distance_km );
        $duration_minutes = absint( $duration_minutes );

        if ( ! $vehicle_id || $distance_km <= 0 ) {
            return array(
                'price' => 0,
                'error' => 'Parámetros inválidos',
            );
        }

        $vehicle = \WPTB_Vehicle_Manager::get_vehicle( $vehicle_id );
        if ( ! $vehicle ) {
            return array(
                'price' => 0,
                'error' => 'Vehículo no encontrado',
            );
        }

        $breakdown = array();
        $initial_fee = floatval( $vehicle->initial_fee );

        if ( $trip_type === 'round_trip' ) {
            $rate = floatval( $vehicle->price_per_km_roundtrip );
            $effective_distance = $distance_km * 2;
            $distance_cost = $effective_distance * $rate;
            $minimum = floatval( $vehicle->min_roundtrip_price );

            $calculated_total = $distance_cost + $initial_fee;
            $final_price = max( $calculated_total, $minimum );

            $breakdown['tipo'] = 'Ida y Vuelta';
            $breakdown['distancia'] = $effective_distance . ' km (Ida y Vuelta)';
            $breakdown['precio_km'] = '€' . number_format( $rate, 2 );
            $breakdown['coste_distancia'] = '€' . number_format( $distance_cost, 2 );
            $breakdown['fee_inicial'] = '€' . number_format( $initial_fee, 2 );
            $breakdown['total_calculado'] = '€' . number_format( $calculated_total, 2 );
            $breakdown['minimo_ida_vuelta'] = '€' . number_format( $minimum, 2 );
        } else {
            $rate = floatval( $vehicle->price_per_km_oneway );
            $distance_cost = $distance_km * $rate;
            $minimum = floatval( $vehicle->min_oneway_price );

            $calculated_total = $distance_cost + $initial_fee;
            $final_price = max( $calculated_total, $minimum );

            $breakdown['tipo'] = 'Solo Ida';
            $breakdown['distancia'] = $distance_km . ' km';
            $breakdown['precio_km'] = '€' . number_format( $rate, 2 );
            $breakdown['coste_distancia'] = '€' . number_format( $distance_cost, 2 );
            $breakdown['fee_inicial'] = '€' . number_format( $initial_fee, 2 );
            $breakdown['total_calculado'] = '€' . number_format( $calculated_total, 2 );
            $breakdown['minimo_ida'] = '€' . number_format( $minimum, 2 );
        }

        $minimum_transfer = floatval( $vehicle->min_transfer_price );
        if ( $minimum_transfer > 0 ) {
            $breakdown['minimo_traslado'] = '€' . number_format( $minimum_transfer, 2 );
            $final_price = max( $final_price, $minimum_transfer );
        }

        if ( $duration_minutes > 0 && floatval( $vehicle->price_per_hour ) > 0 ) {
            $hours = $duration_minutes / 60;
            $hourly_price = $hours * floatval( $vehicle->price_per_hour );

            $breakdown['duracion'] = round( $hours, 1 ) . ' horas';
            $breakdown['precio_hora'] = '€' . number_format( $vehicle->price_per_hour, 2 );
            $breakdown['subtotal_horas'] = '€' . number_format( $hourly_price, 2 );

            if ( $hourly_price > $final_price ) {
                $final_price = $hourly_price;
                $breakdown['metodo_calculo'] = 'Por Hora';
            } else {
                $breakdown['metodo_calculo'] = 'Por Distancia';
            }
        } else {
            $breakdown['metodo_calculo'] = 'Por Distancia';
        }

        $final_price = apply_filters( 'wptb_calculated_price', $final_price, $vehicle_id, $distance_km, $trip_type );
        $money = Money::fromDecimal( $final_price );

        return array(
            'price' => $money->decimalFloat(),
            'price_cents' => $money->cents(),
            'breakdown' => $breakdown,
            'vehicle' => array(
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'type' => isset( $vehicle->type_name ) ? $vehicle->type_name : '',
            ),
        );
    }
}
