<?php

namespace MeTransfers\Booking;

final class VehicleCapacityPolicy {
    public static function validate( $vehicle, $passengers = 1, $suitcases = 0, $carry_ons = 0, $language = 'es' ) {
        $passengers = max( 1, absint( $passengers ) );
        $suitcases = absint( $suitcases );
        $carry_ons = absint( $carry_ons );
        $passenger_capacity = $vehicle && isset( $vehicle->capacity ) ? max( 0, (int) $vehicle->capacity ) : 0;
        $luggage_capacity = $vehicle && isset( $vehicle->luggage_capacity ) ? max( 0, (int) $vehicle->luggage_capacity ) : 0;
        $requested_luggage = $suitcases + $carry_ons;
        $valid = $vehicle
            && $passengers <= $passenger_capacity
            && $requested_luggage <= $luggage_capacity;

        return array(
            'valid'              => (bool) $valid,
            'message'            => $valid ? '' : I18n::text( 'vehicle_capacity_error', $language ),
            'passengers'         => $passengers,
            'passenger_capacity' => $passenger_capacity,
            'suitcases'          => $suitcases,
            'carry_ons'          => $carry_ons,
            'requested_luggage'  => $requested_luggage,
            'luggage_capacity'   => $luggage_capacity,
        );
    }
}
