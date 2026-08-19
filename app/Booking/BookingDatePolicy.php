<?php
namespace MeTransfers\Booking;

final class BookingDatePolicy {
    public static function validate( $date, $time, $return_date = '', $return_time = '', $now = null ) {
        $timezone = new \DateTimeZone( 'Europe/Madrid' );
        $outbound = self::parse( $date, $time, $timezone );
        if ( ! $outbound ) {
            return array( 'valid' => false, 'error' => I18n::text( 'invalid_booking_datetime' ) );
        }

        if ( ! $now instanceof \DateTimeInterface ) {
            $now = new \DateTimeImmutable( 'now', $timezone );
        } else {
            $now = \DateTimeImmutable::createFromInterface( $now )->setTimezone( $timezone );
        }

        $minimum_minutes = max( 0, (int) apply_filters( 'mt_min_booking_lead_minutes', 120 ) );
        $earliest = $now->modify( '+' . $minimum_minutes . ' minutes' );
        if ( $outbound < $earliest ) {
            return array( 'valid' => false, 'error' => I18n::text( 'booking_lead_time_error' ) );
        }

        $has_return = '' !== trim( (string) $return_date ) || '' !== trim( (string) $return_time );
        if ( $has_return ) {
            $return = self::parse( $return_date, $return_time, $timezone );
            if ( ! $return || $return <= $outbound ) {
                return array( 'valid' => false, 'error' => I18n::text( 'return_datetime_error' ) );
            }
        }

        return array(
            'valid'        => true,
            'outbound_at'  => $outbound->format( 'Y-m-d H:i:s' ),
            'return_at'    => isset( $return ) ? $return->format( 'Y-m-d H:i:s' ) : '',
        );
    }

    private static function parse( $date, $time, $timezone ) {
        $value = trim( (string) $date ) . ' ' . trim( (string) $time );
        $parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $value, $timezone );
        $errors = \DateTimeImmutable::getLastErrors();
        if ( ! $parsed || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) ) {
            return null;
        }
        return $parsed;
    }
}
