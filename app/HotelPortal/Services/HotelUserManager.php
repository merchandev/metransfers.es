<?php

namespace MeTransfers\HotelPortal\Services;

use MeTransfers\Admin\AuditLog;
use MeTransfers\Admin\Capabilities;
use MeTransfers\HotelPortal\Access\HotelAccess;

final class HotelUserManager {
	const NONCE_ACTION = 'mt_hotel_manage_users';

	public static function usersForHotel( $hotel_id ) {
		$hotel_id = absint( $hotel_id );
		if ( ! HotelAccess::canAccessHotel( $hotel_id ) ) {
			return array();
		}

		$users = get_users(
			array(
				'role'    => 'check_hoteles',
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);

		return array_values(
			array_filter(
				$users,
				static function ( $user ) use ( $hotel_id ) {
					if ( ! isset( $user->ID ) ) {
						return false;
					}
					$user_id = (int) $user->ID;
					if ( HotelAccess::canAccessHotel( $hotel_id, $user_id ) ) {
						return true;
					}

					$assigned = get_user_meta( $user_id, 'mt_hotel_ids', true );
					$author   = absint( get_post_field( 'post_author', $hotel_id ) );
					if ( empty( $assigned ) && $author === $user_id ) {
						update_user_meta( $user_id, 'mt_hotel_ids', array( $hotel_id ) );
						update_user_meta( $user_id, 'mt_primary_hotel_id', $hotel_id );
						return true;
					}
					return false;
				}
			)
		);
	}

	public static function unassignedUsers() {
		$users = get_users(
			array(
				'role'    => 'check_hoteles',
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
		return array_values(
			array_filter(
				$users,
				static function ( $user ) {
					return isset( $user->ID ) && empty( HotelAccess::userHotelIds( (int) $user->ID ) );
				}
			)
		);
	}

	public static function usersByHotel( array $hotel_ids ) {
		$groups = array();
		foreach ( array_values( array_unique( array_map( 'absint', $hotel_ids ) ) ) as $hotel_id ) {
			if ( ! HotelAccess::canAccessHotel( $hotel_id ) ) {
				continue;
			}
			$groups[ $hotel_id ] = array(
				'hotel_id'   => $hotel_id,
				'hotel_name' => get_the_title( $hotel_id ),
				'users'      => self::usersForHotel( $hotel_id ),
			);
		}
		return $groups;
	}

	public static function process( $hotel_id ) {
		if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || empty( $_POST['mt_hotel_user_action'] ) ) {
			return array( '', '' );
		}
		if ( ! user_can( get_current_user_id(), Capabilities::HOTEL_MANAGE_USERS ) || ! HotelAccess::canAccessHotel( $hotel_id ) ) {
			return array( '', esc_html__( 'No tienes permiso para gestionar usuarios de este hotel.', 'me-transfers' ) );
		}

		$nonce = isset( $_POST['mt_hotel_users_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mt_hotel_users_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return array( '', esc_html__( 'La solicitud ha caducado. Inténtalo de nuevo.', 'me-transfers' ) );
		}

		$action = sanitize_key( wp_unslash( $_POST['mt_hotel_user_action'] ) );
		if ( 'create' === $action ) {
			return self::createUser( $hotel_id );
		}
		if ( 'change-password' === $action ) {
			return self::changeOwnPassword();
		}
		if ( 'assign-existing' === $action ) {
			return self::assignExistingUser( $hotel_id );
		}

		return array( '', esc_html__( 'Acción no válida.', 'me-transfers' ) );
	}

	private static function createUser( $hotel_id ) {
		$display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		$user_login   = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ), true ) : '';
		$user_email   = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$password     = isset( $_POST['user_password'] ) && is_string( $_POST['user_password'] ) ? wp_unslash( $_POST['user_password'] ) : '';

		if ( '' === $display_name || '' === $user_login || ! is_email( $user_email ) || strlen( $password ) < 12 ) {
			return array( '', esc_html__( 'Completa todos los campos y utiliza una contraseña de al menos 12 caracteres.', 'me-transfers' ) );
		}
		if ( username_exists( $user_login ) || email_exists( $user_email ) ) {
			return array( '', esc_html__( 'Ese usuario o correo electrónico ya está registrado.', 'me-transfers' ) );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $user_login,
				'user_email'   => $user_email,
				'user_pass'    => $password,
				'display_name' => $display_name,
				'role'         => 'check_hoteles',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return array( '', esc_html__( 'No se pudo crear el usuario. Revisa los datos.', 'me-transfers' ) );
		}

		update_user_meta( $user_id, 'mt_hotel_ids', array( absint( $hotel_id ) ) );
		update_user_meta( $user_id, 'mt_primary_hotel_id', absint( $hotel_id ) );
		update_user_meta( $user_id, 'mt_active_hotel_id', absint( $hotel_id ) );
		AuditLog::record( 'hotel.portal.user_created', 'user', (int) $user_id, array( 'hotel_id' => absint( $hotel_id ) ) );

		return array( esc_html__( 'Usuario creado correctamente. Ya puede ingresar al portal.', 'me-transfers' ), '' );
	}

	private static function changeOwnPassword() {
		$password = isset( $_POST['new_password'] ) && is_string( $_POST['new_password'] ) ? wp_unslash( $_POST['new_password'] ) : '';
		$confirm  = isset( $_POST['confirm_password'] ) && is_string( $_POST['confirm_password'] ) ? wp_unslash( $_POST['confirm_password'] ) : '';
		if ( strlen( $password ) < 12 || $password !== $confirm ) {
			return array( '', esc_html__( 'Las contraseñas deben coincidir y tener al menos 12 caracteres.', 'me-transfers' ) );
		}

		$user_id = get_current_user_id();
		wp_set_password( $password, $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		AuditLog::record( 'hotel.portal.password_changed', 'user', $user_id );
		return array( esc_html__( 'Tu contraseña se actualizó correctamente.', 'me-transfers' ), '' );
	}

	private static function assignExistingUser( $hotel_id ) {
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$user    = $user_id ? get_userdata( $user_id ) : false;
		if ( ! $user || ! in_array( 'check_hoteles', (array) $user->roles, true ) ) {
			return array( '', esc_html__( 'El usuario seleccionado no es una cuenta Hotel válida.', 'me-transfers' ) );
		}
		$ids   = HotelAccess::userHotelIds( $user_id );
		$ids[] = absint( $hotel_id );
		$ids   = array_values( array_unique( array_filter( $ids ) ) );
		update_user_meta( $user_id, 'mt_hotel_ids', $ids );
		if ( ! get_user_meta( $user_id, 'mt_primary_hotel_id', true ) ) {
			update_user_meta( $user_id, 'mt_primary_hotel_id', absint( $hotel_id ) );
		}
		AuditLog::record( 'hotel.portal.user_assigned', 'user', $user_id, array( 'hotel_id' => absint( $hotel_id ) ) );
		return array( esc_html__( 'Usuario asignado correctamente al hotel activo.', 'me-transfers' ), '' );
	}
}
