<?php
namespace MeTransfers\Core;

final class DataMigrations {
    public static function backfillPriceCents() {
        global $wpdb;
        $table = $wpdb->prefix . 'wptb_bookings';
        $result = $wpdb->query(
            "UPDATE $table
             SET price_cents = CAST(ROUND(price * 100) AS UNSIGNED)
             WHERE price_cents IS NULL AND price IS NOT NULL AND price >= 0"
        );
        if ( false === $result ) {
            throw new \RuntimeException( 'Unable to backfill booking price cents.' );
        }
    }

    public static function ensureBookingIdFloor() {
        global $wpdb;
        $table = $wpdb->prefix . 'wptb_bookings';
        $max_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM $table" );
        if ( $max_id < 10000 && false === $wpdb->query( "ALTER TABLE $table AUTO_INCREMENT = 10000" ) ) {
            throw new \RuntimeException( 'Unable to enforce the booking identifier floor.' );
        }
    }
}
