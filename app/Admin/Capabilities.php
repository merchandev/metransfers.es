<?php
namespace MeTransfers\Admin;

final class Capabilities {
	const MANAGE_BOOKINGS       = 'mt_manage_bookings';
	const MANAGE_VEHICLES       = 'mt_manage_vehicles';
	const MANAGE_HOTELS         = 'mt_manage_hotels';
	const VIEW_STATS            = 'mt_view_stats';
	const EXPORT_BOOKINGS       = 'mt_export_bookings';
	const MANAGE_INTEGRATIONS   = 'mt_manage_integrations';
	const MANAGE_NOTIFICATIONS  = 'mt_manage_notifications';
	const HOTEL_PORTAL_ACCESS   = 'mt_hotel_portal_access';
	const HOTEL_VIEW_BOOKINGS   = 'mt_hotel_view_bookings';
	const HOTEL_CREATE_BOOKINGS = 'mt_hotel_create_bookings';
	const HOTEL_VIEW_STATS      = 'mt_hotel_view_stats';
	const HOTEL_VIEW_PROFILE    = 'mt_hotel_view_profile';
	const HOTEL_CANCEL_BOOKINGS = 'mt_hotel_cancel_bookings';
	const HOTEL_EXPORT_BOOKINGS = 'mt_hotel_export_bookings';
	const HOTEL_MANAGE_USERS    = 'mt_hotel_manage_users';
	const HOTEL_ACCESS_ALL      = 'mt_hotel_access_all';       // Acceso global a todos los hoteles (solo supervisores)
	const HOTEL_IMPORT_BOOKINGS = 'mt_hotel_import_bookings';  // Importar reservas desde Excel

	public function register() {
		add_action( 'init', array( __CLASS__, 'ensureRoles' ), 1 );
		add_action( 'after_switch_theme', array( __CLASS__, 'ensureRoles' ) );
		add_filter( 'map_meta_cap', array( __CLASS__, 'restrictHotelOwnership' ), 20, 4 );
	}

	public static function ensureRoles() {
		$all           = self::all();
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( $all as $capability ) {
				if ( ! $administrator->has_cap( $capability ) ) {
					$administrator->add_cap( $capability );
				}
			}
		}

		$operator_caps = array(
			'read'                     => true,
			self::MANAGE_BOOKINGS      => true,
			self::MANAGE_VEHICLES      => true,
			self::MANAGE_HOTELS        => true,
			self::VIEW_STATS           => true,
			self::EXPORT_BOOKINGS      => true,
			self::MANAGE_NOTIFICATIONS => true,
		);
		$operator      = get_role( 'metransfers_operator' );
		if ( ! $operator ) {
			$operator = add_role( 'metransfers_operator', 'MeTransfers Operaciones', $operator_caps );
		}
		if ( $operator ) {
			foreach ( $operator_caps as $capability => $granted ) {
				if ( ! $operator->has_cap( $capability ) ) {
					$operator->add_cap( $capability );
				}
			}
			self::removeCaps( $operator, array( self::MANAGE_INTEGRATIONS, 'manage_options' ) );
		}

		// Rol base Hotel: acceso estrictamente al propio hotel, sin permisos elevados.
		$hotel_checker = get_role( 'check_hoteles' );
		if ( ! $hotel_checker ) {
			$hotel_checker = add_role( 'check_hoteles', 'CheckHoteles', array( 'read' => true ) );
		}
		if ( $hotel_checker ) {
			$hotel_caps = array(
				'read',
				self::HOTEL_PORTAL_ACCESS,
				self::HOTEL_VIEW_BOOKINGS,
				self::HOTEL_CREATE_BOOKINGS,
				self::HOTEL_VIEW_STATS,
				self::HOTEL_VIEW_PROFILE,
			);
			foreach ( $hotel_caps as $capability ) {
				if ( ! $hotel_checker->has_cap( $capability ) ) {
					$hotel_checker->add_cap( $capability );
				}
			}
			// Retirar caps elevadas del rol base (también fuerza retirada en futuras activaciones).
			self::removeCaps(
				$hotel_checker,
				array(
					self::MANAGE_BOOKINGS,
					self::MANAGE_VEHICLES,
					self::MANAGE_HOTELS,
					self::VIEW_STATS,
					self::EXPORT_BOOKINGS,
					self::MANAGE_INTEGRATIONS,
					self::MANAGE_NOTIFICATIONS,
					self::HOTEL_MANAGE_USERS,
					self::HOTEL_ACCESS_ALL,
					self::HOTEL_IMPORT_BOOKINGS,
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

		return \MeTransfers\HotelPortal\Access\HotelAccess::canAccessHotel( (int) $post->ID, (int) $user_id ) ? $caps : array( 'do_not_allow' );
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
			self::HOTEL_PORTAL_ACCESS,
			self::HOTEL_VIEW_BOOKINGS,
			self::HOTEL_CREATE_BOOKINGS,
			self::HOTEL_VIEW_STATS,
			self::HOTEL_VIEW_PROFILE,
			self::HOTEL_MANAGE_USERS,
			self::HOTEL_ACCESS_ALL,
			self::HOTEL_IMPORT_BOOKINGS,
		);
	}

	public static function maskSecret( $value ) {
		$value  = (string) $value;
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
