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
    );

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
}
