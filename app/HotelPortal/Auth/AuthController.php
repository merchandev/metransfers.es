<?php

namespace MeTransfers\HotelPortal\Auth;

use MeTransfers\Admin\AuditLog;
use MeTransfers\HotelPortal\Access\HotelAccess;
use MeTransfers\HotelPortal\Routing\Router;
use MeTransfers\Security\RequestRateLimiter;

final class AuthController {
	const LOGIN_NONCE_ACTION  = 'mt_hotel_portal_login';
	const LOGOUT_NONCE_ACTION = 'mt_hotel_portal_logout';

	public function processLogin() {
		if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || empty( $_POST['mt_hotel_login'] ) ) {
			return '';
		}

		$nonce = isset( $_POST['mt_hotel_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mt_hotel_login_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::LOGIN_NONCE_ACTION ) ) {
			return self::genericError();
		}
		$login    = isset( $_POST['log'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['log'] ) ) ) : '';
		$password = isset( $_POST['pwd'] ) && is_string( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '';
		if ( '' === $login || '' === $password || strlen( $login ) > 100 || strlen( $password ) > 4096 ) {
			return self::genericError();
		}
		$account_identifier = 'login:' . hash( 'sha256', strtolower( trim( $login ) ) );
		if ( ! RequestRateLimiter::consume( 'hotel_portal_login_ip', 30, 15 * MINUTE_IN_SECONDS )
			|| ! RequestRateLimiter::consumeForIdentifier( 'hotel_portal_login_account', 8, 15 * MINUTE_IN_SECONDS, $account_identifier ) ) {
			return esc_html__( 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.', 'me-transfers' );
		}

		$wp_user = self::resolveUser( $login );
		if ( ! $wp_user ) {
			return self::genericError();
		}
		$user = wp_authenticate_username_password( null, $wp_user->user_login, $password );
		if ( is_wp_error( $user ) || (int) $user->ID !== (int) $wp_user->ID ) {
			return self::genericError();
		}
		if ( ! HotelAccess::canEnterPortal( $user->ID ) ) {
			if ( empty( HotelAccess::userHotelIds( $user->ID ) ) ) {
				return esc_html__( 'Tu usuario no tiene ningún hotel asignado en el sistema.', 'me-transfers' );
			}
			return esc_html__( 'No tienes permisos para acceder al Portal de Hoteles.', 'me-transfers' );
		}

		$remember = ! empty( $_POST['rememberme'] );
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, $remember, is_ssl() );
		do_action( 'wp_login', $user->user_login, $user );
		AuditLog::record( 'hotel.portal.login_success', 'user', (int) $user->ID );
		wp_safe_redirect( Router::url( 'dashboard' ) );
		exit;
	}

	public function processLogout() {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::LOGOUT_NONCE_ACTION ) ) {
			return false;
		}

		wp_logout();
		wp_safe_redirect( Router::url() );
		exit;
	}

	public static function logoutUrl() {
		return wp_nonce_url( Router::url( 'logout' ), self::LOGOUT_NONCE_ACTION );
	}

	private static function genericError() {
		return esc_html__( 'No se pudo iniciar sesión. Revisa tus datos o contacta con MeTransfers.', 'me-transfers' );
	}

	private static function resolveUser( $identifier ) {
		$identifier = trim( (string) $identifier );
		if ( '' === $identifier ) {
			return false; }
		if ( is_email( $identifier ) ) {
			$user = get_user_by( 'email', sanitize_email( $identifier ) );
			if ( $user instanceof \WP_User ) {
				return $user; }
		}
		$user = get_user_by( 'login', sanitize_user( $identifier ) );
		return $user instanceof \WP_User ? $user : false;
	}
}
