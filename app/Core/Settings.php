<?php
namespace MeTransfers\Core;

/**
 * Stable configuration facade with backwards-compatible WordPress option keys.
 */
class Settings {
    private const DEFINITIONS = array(
        'google_maps_api_key' => array(
            'constants' => array( 'MT_GOOGLE_MAPS_API_KEY' ),
            'options'   => array( 'wptb_google_maps_api_key' ),
        ),
        'google_maps_server_api_key' => array(
            'constants' => array( 'MT_GOOGLE_MAPS_SERVER_API_KEY' ),
            'options'   => array( 'wptb_google_maps_server_api_key' ),
        ),
        'redsys_merchant_code' => array(
            'constants' => array( 'MT_REDSYS_MERCHANT_CODE' ),
            'options'   => array( 'wptb_redsys_merchant_code' ),
        ),
        'redsys_secret' => array(
            'constants' => array( 'MT_REDSYS_SECRET', 'MT_REDSYS_SECRET_KEY' ),
            'options'   => array( 'wptb_redsys_key', 'wptb_redsys_secret_key' ),
        ),
        'redsys_terminal' => array(
            'constants' => array( 'MT_REDSYS_TERMINAL' ),
            'options'   => array( 'wptb_redsys_terminal' ),
        ),
        'redsys_currency' => array(
            'constants' => array( 'MT_REDSYS_CURRENCY' ),
            'options'   => array( 'wptb_redsys_currency' ),
        ),
        'redsys_environment' => array(
            'constants' => array( 'MT_REDSYS_ENVIRONMENT' ),
            'options'   => array( 'wptb_redsys_environment' ),
        ),
        'smtp_host' => array(
            'constants' => array( 'MT_SMTP_HOST' ),
            'options'   => array( 'wptb_smtp_host' ),
        ),
        'smtp_user' => array(
            'constants' => array( 'MT_SMTP_USER' ),
            'options'   => array( 'wptb_smtp_user' ),
        ),
        'smtp_password' => array(
            'constants' => array( 'MT_SMTP_PASSWORD' ),
            'options'   => array( 'wptb_smtp_password' ),
        ),
        'smtp_port' => array(
            'constants' => array( 'MT_SMTP_PORT' ),
            'options'   => array( 'wptb_smtp_port' ),
        ),
        'smtp_encryption' => array(
            'constants' => array( 'MT_SMTP_ENCRYPTION' ),
            'options'   => array( 'wptb_smtp_encryption' ),
        ),
        'smtp_from' => array(
            'constants' => array( 'MT_SMTP_FROM' ),
            'options'   => array( 'wptb_smtp_from' ),
        ),
        'smtp_from_name' => array(
            'constants' => array( 'MT_SMTP_FROM_NAME' ),
            'options'   => array( 'wptb_smtp_from_name' ),
        ),
        'ga4_measurement_id' => array(
            'constants' => array( 'MT_GA4_MEASUREMENT_ID' ),
            'options'   => array( 'mt_ga4_measurement_id' ),
        ),
        'ga4_api_secret' => array(
            'constants' => array( 'MT_GA4_API_SECRET' ),
            'options'   => array( 'mt_ga4_api_secret' ),
        ),
        'whatsapp_api_key' => array(
            'constants' => array( 'MT_WHATSAPP_API_KEY' ),
            'options'   => array( 'wptb_whatsapp_apikey' ),
        ),
        'whatsapp_admin_phone' => array(
            'constants' => array( 'MT_WHATSAPP_ADMIN_PHONE' ),
            'options'   => array( 'wptb_admin_phone_notifications' ),
        ),
        'translation_api_key' => array(
            'constants' => array( 'MT_TRANSLATION_API_KEY' ),
            'options'   => array( 'mt_google_api_key' ),
        ),
        'redsys_credentials_rotated_at' => array(
            'constants' => array( 'MT_REDSYS_CREDENTIALS_ROTATED_AT' ),
            'options'   => array( 'mt_redsys_credentials_rotated_at' ),
        ),
        'smtp_credentials_rotated_at' => array(
            'constants' => array( 'MT_SMTP_CREDENTIALS_ROTATED_AT' ),
            'options'   => array( 'mt_smtp_credentials_rotated_at' ),
        ),
        'maps_credentials_rotated_at' => array(
            'constants' => array( 'MT_MAPS_CREDENTIALS_ROTATED_AT' ),
            'options'   => array( 'mt_maps_credentials_rotated_at' ),
        ),
        'redsys_sandbox_verified_at' => array(
            'constants' => array( 'MT_REDSYS_SANDBOX_VERIFIED_AT' ),
            'options'   => array( 'mt_redsys_sandbox_verified_at' ),
        ),
    );

    private const PRIVATE_OPTIONS = array(
        'wptb_google_maps_server_api_key',
        'wptb_redsys_key',
        'wptb_redsys_secret_key',
        'wptb_smtp_password',
        'mt_ga4_api_secret',
        'wptb_whatsapp_apikey',
        'mt_google_api_key',
    );

    public function register() {
        add_action( 'added_option', array( __CLASS__, 'protectAddedOption' ), 10, 2 );
        add_action( 'updated_option', array( __CLASS__, 'protectUpdatedOption' ), 10, 3 );
        if ( is_admin() ) {
            add_action( 'admin_init', array( __CLASS__, 'protectExistingPrivateOptions' ), 1 );
        }
    }

    public static function get( $key, $default = null ) {
        if ( ! isset( self::DEFINITIONS[ $key ] ) ) {
            return $default;
        }

        foreach ( self::DEFINITIONS[ $key ]['constants'] as $constant ) {
            if ( defined( $constant ) ) {
                $value = constant( $constant );
                if ( '' !== $value && null !== $value ) {
                    return $value;
                }
            }
        }

        foreach ( self::DEFINITIONS[ $key ]['options'] as $option ) {
            $value = get_option( $option, null );
            if ( null !== $value && '' !== $value ) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Return the private Maps credential used by server-side provider calls.
     * Browser credentials are intentionally never accepted as a fallback.
     */
    public static function requireServerMapsKey() {
        $key = trim( (string) self::get( 'google_maps_server_api_key', '' ) );
        if ( '' === $key ) {
            throw new \RuntimeException( 'Server Maps key missing.' );
        }

        return $key;
    }

    public static function protectAddedOption( $option, $value = null ) {
        if ( self::markPrivate( $option ) ) {
            \MeTransfers\Admin\AuditLog::record( 'integration.secret_added', 'setting', 0, array( 'setting_id' => self::settingId( $option ) ) );
        }
    }

    public static function protectUpdatedOption( $option, $old_value = null, $value = null ) {
        if ( $old_value !== $value && self::markPrivate( $option ) ) {
            \MeTransfers\Admin\AuditLog::record( 'integration.secret_updated', 'setting', 0, array( 'setting_id' => self::settingId( $option ) ) );
        }
    }

    public static function protectExistingPrivateOptions() {
        global $wpdb;
        if ( ! $wpdb || ! isset( $wpdb->options ) ) {
            return;
        }

        $placeholders = implode( ',', array_fill( 0, count( self::PRIVATE_OPTIONS ), '%s' ) );
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->options} SET autoload = 'no' WHERE option_name IN ($placeholders) AND autoload NOT IN ('no', 'off', 'auto-off')",
                ...self::PRIVATE_OPTIONS
            )
        );
    }

    private static function markPrivate( $option ) {
        if ( ! in_array( (string) $option, self::PRIVATE_OPTIONS, true ) ) {
            return false;
        }
        global $wpdb;
        if ( $wpdb && isset( $wpdb->options ) ) {
            $wpdb->update( $wpdb->options, array( 'autoload' => 'no' ), array( 'option_name' => (string) $option ), array( '%s' ), array( '%s' ) );
        }
        return true;
    }

    private static function settingId( $option ) {
        return substr( hash( 'sha256', (string) $option ), 0, 16 );
    }
}
