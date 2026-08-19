<?php
namespace MeTransfers\Booking;

use MeTransfers\Pricing\Calculator;

final class QuoteService {
    public static function create( $input ) {
        $language = I18n::normalizeLanguage( is_array( $input ) && isset( $input['language'] ) && is_scalar( $input['language'] ) ? $input['language'] : 'es' );
        $vehicle_id = is_array( $input ) && isset( $input['vehicle_id'] ) && is_scalar( $input['vehicle_id'] )
            ? absint( $input['vehicle_id'] )
            : 0;
        if ( ! $vehicle_id || ! is_array( $input ) ) {
            return self::error( 'invalid_booking_request', $language );
        }

        $context = RouteContext::create( $input );
        if ( empty( $context['valid'] ) ) {
            return $context;
        }

        return self::quoteFromContext( $vehicle_id, $context );
    }

    public static function createVehicleList( $input ) {
        if ( ! is_array( $input ) ) {
            return self::error( 'invalid_booking_request', 'es' );
        }

        $context = RouteContext::create( $input, true );
        if ( empty( $context['valid'] ) ) {
            return $context;
        }

        $passengers = isset( $input['passengers'] ) && is_scalar( $input['passengers'] ) ? absint( $input['passengers'] ) : 1;
        $suitcases = isset( $input['suitcases'] ) && is_scalar( $input['suitcases'] ) ? absint( $input['suitcases'] ) : 0;
        $carry_ons = isset( $input['carry_ons'] ) && is_scalar( $input['carry_ons'] ) ? absint( $input['carry_ons'] ) : 0;
        $vehicles = \WPTB_Vehicle_Manager::get_active_vehicles();
        $quotes = array();

        foreach ( is_array( $vehicles ) ? $vehicles : array() as $vehicle ) {
            $quote = self::quoteFromContext( (int) $vehicle->id, $context );
            if ( empty( $quote['valid'] ) ) {
                continue;
            }

            $capacity = VehicleCapacityPolicy::validate( $vehicle, $passengers, $suitcases, $carry_ons, $context['language'] );
            $image = \WPTB_Vehicle_Manager::get_primary_image( $vehicle->id );
            $quotes[] = array(
                'id'               => (int) $vehicle->id,
                'name'             => sanitize_text_field( $vehicle->name ),
                'type'             => isset( $vehicle->type_name ) ? sanitize_text_field( $vehicle->type_name ) : 'Standard',
                'description'      => isset( $vehicle->description ) ? wp_kses_post( $vehicle->description ) : '',
                'capacity'         => (int) $capacity['passenger_capacity'],
                'luggage_capacity' => (int) $capacity['luggage_capacity'],
                'available'        => (bool) $capacity['valid'],
                'image'            => $image,
                'image_url'        => $image,
                'price'            => (float) $quote['price'],
                'currency'         => 'EUR',
            );
        }

        if ( empty( $quotes ) ) {
            return self::error( 'no_vehicles', $context['language'] );
        }

        return array(
            'valid'          => true,
            'vehicles'       => $quotes,
            'route'          => self::publicRoute( $context ),
            'booking_locale' => $context['language'],
        );
    }

    private static function quoteFromContext( $vehicle_id, array $context ) {
        $pricing = Calculator::calculate(
            $vehicle_id,
            $context['pricing_distance_km'],
            $context['trip_type'],
            $context['duration_minutes']
        );
        if ( isset( $pricing['error'] ) || empty( $pricing['price'] ) ) {
            return self::error( 'invalid_server_price', $context['language'] );
        }

        return array(
            'valid'                => true,
            'price'                => (float) $pricing['price'],
            'distance_km'          => (float) $context['pricing_distance_km'],
            'total_distance_km'    => (float) $context['total_distance_km'],
            'duration_minutes'     => (int) $context['duration_minutes'],
            'outbound_distance_km' => (float) $context['outbound_distance_km'],
            'return_distance_km'   => (float) $context['return_distance_km'],
            'breakdown'            => $pricing['breakdown'],
            'vehicle'              => $pricing['vehicle'],
            'booking_locale'       => $context['language'],
        );
    }

    private static function publicRoute( array $context ) {
        return array(
            'origin'               => $context['origin'],
            'destination'          => $context['destination'],
            'trip_type'            => $context['trip_type'],
            'distance_km'          => (float) $context['pricing_distance_km'],
            'total_distance_km'    => (float) $context['total_distance_km'],
            'duration_minutes'     => (int) $context['duration_minutes'],
            'return_origin'        => $context['return_origin'],
            'return_destination'   => $context['return_destination'],
        );
    }

    private static function error( $key, $language ) {
        return array( 'valid' => false, 'error' => I18n::text( $key, $language ) );
    }
}
