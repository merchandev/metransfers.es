<?php

namespace MeTransfers\HotelPortal\Auth;

use MeTransfers\HotelPortal\Routing\Router;

final class AdminRedirects {
	public function register() {
		add_action( 'admin_init', array( $this, 'redirectHotelUsers' ), 1 );
		add_filter( 'login_redirect', array( $this, 'loginRedirect' ), 20, 3 );
		add_filter( 'show_admin_bar', array( $this, 'adminBar' ) );
	}

	public function redirectHotelUsers() {
		if ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		if ( self::isAllowedHotelAdminAction() ) {
			return;
		}

		if ( self::isHotelUser( wp_get_current_user() ) ) {
			wp_safe_redirect( Router::url( 'dashboard' ) );
			exit;
		}
	}

	public function loginRedirect( $redirect_to, $requested_redirect_to, $user ) {
		if ( self::isHotelUser( $user ) ) {
			return Router::url( 'dashboard' );
		}

		return $redirect_to;
	}

	public function adminBar( $show ) {
		return self::isHotelUser( wp_get_current_user() ) ? false : $show;
	}

	public static function isHotelUser( $user ) {
		if ( ! $user || empty( $user->roles ) ) {
			return false;
		}

		$roles = (array) $user->roles;
		return in_array( 'check_hoteles', $roles, true ) && ! in_array( 'administrator', $roles, true );
	}

	private static function isAllowedHotelAdminAction() {
		$script = isset( $_SERVER['PHP_SELF'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) ) : '';
		if ( 'admin-post.php' !== $script ) {
			return false;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		return in_array( $action, array( 'hqp_download_qr', 'hqp_download_flyer' ), true );
	}
}
