<?php

namespace MeTransfers\Booking;

final class VehicleCapacityPolicy {
    public static function validate( $vehicle, $passengers = 1, $suitcases = 0, $carry_ons = 0, $language = 'es' ) {
        $passengers = max( 1, absint( $passengers ) );
        $suitcases = absint( $suitcases );
        $carry_ons = absint( $carry_ons );
        $passenger_capacity = $vehicle && isset( $vehicle->capacity ) ? max( 0, (int) $vehicle->capacity ) : 0;
        $luggage_capacity = $vehicle && isset( $vehicle->luggage_capacity ) ? max( 0, (int) $vehicle->luggage_capacity ) : 0;
        $max_per_person = 2;
        $max_suitcases = $passengers * $max_per_person;
        $max_carry_ons = $passengers * $max_per_person;
		$valid = $vehicle
			&& $passengers <= $passenger_capacity
			&& ( $suitcases + $carry_ons ) <= $luggage_capacity
			&& $suitcases <= $max_suitcases
            && $carry_ons <= $max_carry_ons;

        return array(
            'valid'              => (bool) $valid,
            'message'            => $valid ? '' : I18n::text( 'vehicle_capacity_error', $language ),
            'passengers'         => $passengers,
            'passenger_capacity' => $passenger_capacity,
            'suitcases'          => $suitcases,
            'carry_ons'          => $carry_ons,
            'max_suitcases'      => $max_suitcases,
            'max_carry_ons'      => $max_carry_ons,
            'luggage_capacity'   => $luggage_capacity,
        );
    }
}
