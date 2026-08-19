<?php
namespace MeTransfers\Booking;

use MeTransfers\Pricing\Calculator;

final class QuoteService {
    public static function create( $input ) {
        $language = I18n::normalizeLanguage( $input['language'] ?? 'es' );
        $origin = sanitize_text_field( (string) ( $input['origin'] ?? '' ) );
        $destination = sanitize_text_field( (string) ( $input['destination'] ?? '' ) );
        $trip_type = 'round_trip' === ( $input['trip_type'] ?? '' ) ? 'round_trip' : 'one_way';
        $vehicle_id = absint( $input['vehicle_id'] ?? 0 );

        if ( '' === $origin || '' === $destination || ! $vehicle_id ) {
            return self::error( 'invalid_booking_request', $language );
        }

        $date_policy = BookingDatePolicy::validate(
            $input['date'] ?? '',
            $input['time'] ?? '',
            'round_trip' === $trip_type ? ( $input['return_date'] ?? '' ) : '',
            'round_trip' === $trip_type ? ( $input['return_time'] ?? '' ) : ''
        );
        if ( empty( $date_policy['valid'] ) ) {
            return array( 'valid' => false, 'error' => $date_policy['error'] );
        }

        $area = ServiceAreaPolicy::validateRoute( $origin, $destination );
        if ( empty( $area['valid'] ) ) {
            return array( 'valid' => false, 'error' => $area['error'] );
        }

        $outbound = RouteDistance::calculate( $origin, $destination );
        if ( isset( $outbound['error'] ) ) {
            return array( 'valid' => false, 'error' => $outbound['error'] );
        }

        $pricing_distance = (float) $outbound['distance_km'];
        $total_distance = $pricing_distance;
        $duration_minutes = (int) $outbound['duration_minutes'];
        $return_route = null;

        if ( 'round_trip' === $trip_type ) {
            $return_origin = sanitize_text_field( (string) ( $input['return_origin'] ?? '' ) );
            $return_destination = sanitize_text_field( (string) ( $input['return_destination'] ?? '' ) );
            if ( '' === $return_origin || '' === $return_destination ) {
                return self::error( 'return_fields_required', $language );
            }

            $return_area = ServiceAreaPolicy::validateRoute( $return_origin, $return_destination );
            if ( empty( $return_area['valid'] ) ) {
                return array( 'valid' => false, 'error' => $return_area['error'] );
            }

            $return_route = RouteDistance::calculate( $return_origin, $return_destination );
            if ( isset( $return_route['error'] ) ) {
                return array( 'valid' => false, 'error' => $return_route['error'] );
            }

            $total_distance += (float) $return_route['distance_km'];
            $pricing_distance = $total_distance / 2;
            $duration_minutes += (int) $return_route['duration_minutes'];
        }

        $pricing = Calculator::calculate( $vehicle_id, $pricing_distance, $trip_type, $duration_minutes );
        if ( isset( $pricing['error'] ) || empty( $pricing['price'] ) ) {
            return self::error( 'invalid_server_price', $language );
        }

        return array(
            'valid'                => true,
            'price'                => (float) $pricing['price'],
            'distance_km'          => round( $pricing_distance, 2 ),
            'total_distance_km'    => round( $total_distance, 2 ),
            'duration_minutes'     => $duration_minutes,
            'outbound_distance_km' => (float) $outbound['distance_km'],
            'return_distance_km'   => $return_route ? (float) $return_route['distance_km'] : 0,
            'breakdown'            => $pricing['breakdown'],
            'vehicle'              => $pricing['vehicle'],
            'booking_locale'       => $language,
        );
    }

    private static function error( $key, $language ) {
        return array( 'valid' => false, 'error' => I18n::text( $key, $language ) );
    }
}
