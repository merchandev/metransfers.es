<?php
namespace MeTransfers\Admin;

final class Capabilities {
    const MANAGE_BOOKINGS      = 'mt_manage_bookings';
    const MANAGE_VEHICLES      = 'mt_manage_vehicles';
    const MANAGE_HOTELS        = 'mt_manage_hotels';
    const VIEW_STATS           = 'mt_view_stats';
    const EXPORT_BOOKINGS      = 'mt_export_bookings';
    const MANAGE_INTEGRATIONS  = 'mt_manage_integrations';
    const MANAGE_NOTIFICATIONS = 'mt_manage_notifications';

    public function register() {
        add_action( 'init', array( __CLASS__, 'ensureRoles' ), 1 );
        add_action( 'after_switch_theme', array( __CLASS__, 'ensureRoles' ) );
        add_filter( 'map_meta_cap', array( __CLASS__, 'restrictHotelOwnership' ), 20, 4 );
    }

    public static function ensureRoles() {
        $all = self::all();
        $administrator = get_role( 'administrator' );
        if ( $administrator ) {
            foreach ( $all as $capability ) {
                if ( ! $administrator->has_cap( $capability ) ) {
                    $administrator->add_cap( $capability );
                }
            }
        }

        $operator_caps = array(
            'read'                      => true,
            self::MANAGE_BOOKINGS      => true,
            self::MANAGE_VEHICLES      => true,
            self::MANAGE_HOTELS        => true,
            self::VIEW_STATS           => true,
            self::EXPORT_BOOKINGS      => true,
            self::MANAGE_NOTIFICATIONS => true,
        );
        $operator = get_role( 'metransfers_operator' );
        if ( ! $operator ) {
            $operator = add_role( 'metransfers_operator', 'MeTransfers Operaciones', $operator_caps );
        }
        if ( $operator ) {
            foreach ( $operator_caps as $capability => $granted ) {
                if ( $granted && ! $operator->has_cap( $capability ) ) {
                    $operator->add_cap( $capability );
                }
            }
            self::removeCaps( $operator, array( self::MANAGE_INTEGRATIONS, 'manage_options' ) );
        }

        // The existing hotel-check role remains restricted to its hotel scope.
        $hotel_checker = get_role( 'check_hoteles' );
        if ( $hotel_checker ) {
            if ( ! $hotel_checker->has_cap( self::MANAGE_HOTELS ) ) {
                $hotel_checker->add_cap( self::MANAGE_HOTELS );
            }
            self::removeCaps(
                $hotel_checker,
                array(
                    self::MANAGE_BOOKINGS,
                    self::MANAGE_VEHICLES,
                    self::VIEW_STATS,
                    self::EXPORT_BOOKINGS,
                    self::MANAGE_INTEGRATIONS,
                    self::MANAGE_NOTIFICATIONS,
                    'manage_options',
                )
            );
        }
    }

    public static function restrictHotelOwnership( $caps, $capability, $user_id, $args ) {
        if ( ! in_array( $capability, array( 'edit_post', 'delete_post', 'read_post' ), true ) || empty( $args[0] ) ) {
            return $caps;
        }

        $post = get_post( (int) $args[0] );
        $user = get_userdata( (int) $user_id );
        if ( ! $post || 'hotel_partner' !== $post->post_type || ! self::isHotelChecker( $user ) ) {
            return $caps;
        }

        return (int) $post->post_author === (int) $user_id ? $caps : array( 'do_not_allow' );
    }

    private static function isHotelChecker( $user ) {
        if ( ! $user || empty( $user->roles ) ) {
            return false;
        }

        $roles = (array) $user->roles;
        return in_array( 'check_hoteles', $roles, true ) && ! in_array( 'administrator', $roles, true );
    }

    private static function removeCaps( $role, array $capabilities ) {
        foreach ( $capabilities as $capability ) {
            if ( $role->has_cap( $capability ) ) {
                $role->remove_cap( $capability );
            }
        }
    }

    public static function all() {
        return array(
            self::MANAGE_BOOKINGS,
            self::MANAGE_VEHICLES,
            self::MANAGE_HOTELS,
            self::VIEW_STATS,
            self::EXPORT_BOOKINGS,
            self::MANAGE_INTEGRATIONS,
            self::MANAGE_NOTIFICATIONS,
        );
    }

    public static function maskSecret( $value ) {
        $value = (string) $value;
        $length = strlen( $value );
        if ( 0 === $length ) {
            return '';
        }
        if ( $length <= 8 ) {
            return str_repeat( '•', $length );
        }

        return substr( $value, 0, 4 ) . str_repeat( '•', min( 12, $length - 8 ) ) . substr( $value, -4 );
    }
}
