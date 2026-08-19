<?php
namespace MeTransfers\Security;

/**
 * Small request limiter for public operations that can trigger paid providers.
 */
final class RequestRateLimiter {
    public static function consume( $bucket, $limit, $window_seconds ) {
        $bucket = sanitize_key( (string) $bucket );
        $limit = max( 1, min( 1000, (int) $limit ) );
        $window_seconds = max( 1, min( HOUR_IN_SECONDS, (int) $window_seconds ) );

        if ( '' === $bucket ) {
            return false;
        }

        $identifier = self::clientIdentifier();
        $key = 'mt_limit_' . hash( 'sha256', $bucket . '|' . $identifier );
        $count = (int) get_transient( $key );
        if ( $count >= $limit ) {
            return false;
        }

        set_transient( $key, $count + 1, $window_seconds );
        return true;
    }

    private static function clientIdentifier() {
        if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
            return 'user:' . (int) get_current_user_id();
        }

        $remote_address = isset( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '';

        return 'ip:' . ( '' !== $remote_address ? $remote_address : 'unknown' );
    }
}
