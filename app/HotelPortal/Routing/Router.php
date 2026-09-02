<?php

namespace MeTransfers\HotelPortal\Routing;

use MeTransfers\Admin\AuditLog;
use MeTransfers\Admin\Capabilities;
use MeTransfers\HotelPortal\Access\HotelAccess;
use MeTransfers\HotelPortal\Auth\AuthController;
use MeTransfers\HotelPortal\Services\HotelContext;
use MeTransfers\HotelPortal\Services\HotelDashboard;
use MeTransfers\HotelPortal\Services\HotelUserManager;
use MeTransfers\HotelPortal\Services\BookingImporter;
use MeTransfers\HotelPortal\Services\HotelOperations;

final class Router {
	const REWRITE_OPTION  = 'mt_hotel_portal_rewrite_version';
	const REWRITE_VERSION = '3';

	private AuthController $auth;

	public function __construct( AuthController $auth ) {
		$this->auth = $auth;
	}

	public function register() {
		add_action( 'init', array( __CLASS__, 'addRules' ), 9 );
		add_action( 'init', array( __CLASS__, 'maybeFlushRules' ), 99 );
		add_action( 'after_switch_theme', array( __CLASS__, 'flushRules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'queryVars' ) );
		add_action( 'template_redirect', array( $this, 'dispatch' ), 0 );
	}

	public static function rules() {
		return array(
			'^hoteles/?$'                   => 'index.php?mt_hotel_portal=login',
			'^hoteles/dashboard/?$'         => 'index.php?mt_hotel_portal=dashboard',
			'^hoteles/reservas/?$'          => 'index.php?mt_hotel_portal=bookings',
			'^hoteles/reservas/nueva/?$'    => 'index.php?mt_hotel_portal=booking-new',
			'^hoteles/reservas/([0-9]+)/?$' => 'index.php?mt_hotel_portal=booking-detail&mt_hotel_booking_id=$matches[1]',
			'^hoteles/clientes/?$'          => 'index.php?mt_hotel_portal=customers',
			'^hoteles/estadisticas/?$'      => 'index.php?mt_hotel_portal=statistics',
			'^hoteles/perfil/?$'            => 'index.php?mt_hotel_portal=profile',
			'^hoteles/usuarios/?$'          => 'index.php?mt_hotel_portal=users',
			'^hoteles/importar/?$'          => 'index.php?mt_hotel_portal=import',
			'^hoteles/logout/?$'            => 'index.php?mt_hotel_portal=logout',
		);
	}

	public static function addRules() {
		foreach ( self::rules() as $pattern => $query ) {
			add_rewrite_rule( $pattern, $query, 'top' );
		}
	}

	public static function queryVars( $vars ) {
		$vars[] = 'mt_hotel_portal';
		$vars[] = 'mt_hotel_booking_id';
		return array_values( array_unique( $vars ) );
	}

	public static function maybeFlushRules() {
		if ( (string) get_option( self::REWRITE_OPTION, '' ) === self::rewriteVersion() ) {
			return;
		}

		self::flushRules();
	}

	public static function flushRules() {
		self::addRules();
		flush_rewrite_rules( false );
		update_option( self::REWRITE_OPTION, self::rewriteVersion(), false );
	}

	private static function rewriteVersion() {
		return (string) MT_PLATFORM_VERSION . ':' . self::REWRITE_VERSION;
	}

	public function dispatch() {
		$route = sanitize_key( (string) get_query_var( 'mt_hotel_portal', '' ) );
		if ( '' === $route ) {
			return;
		}

		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
		}

		if ( 'login' === $route ) {
			$this->dispatchLogin();
			return;
		}
		if ( 'logout' === $route ) {
			if ( ! is_user_logged_in() || ! $this->auth->processLogout() ) {
				$this->renderForbidden();
			}
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( self::url() );
			exit;
		}
		if ( ! HotelAccess::canEnterPortal() ) {
			$this->renderForbidden(
				HotelAccess::isBlocked()
					? esc_html__( 'Tu acceso al Portal de Hoteles está desactivado. Contacta con soporte técnico de MeTransfers.', 'me-transfers' )
					: ''
			);
			return;
		}

		$this->processHotelSwitch();
		$requested_hotel = isset( $_GET['hotel'] ) ? absint( $_GET['hotel'] ) : 0;
		$hotel_id        = HotelContext::currentHotelId( $requested_hotel );
		if ( ! $hotel_id ) {
			$this->renderForbidden();
			return;
		}
		if ( 'users' === $route ) {
			list( $notice, $error ) = HotelUserManager::process( $hotel_id );
			$accessible_hotels      = HotelAccess::userHotelIds();
			$this->render(
				'users',
				array(
					'page_title'       => esc_html__( 'Usuarios', 'me-transfers' ),
					'hotel_id'         => $hotel_id,
					'hotel_ids'        => $accessible_hotels,
					'route'            => $route,
					'user_groups'      => HotelUserManager::usersByHotel( $accessible_hotels ),
					'unassigned_users' => HotelUserManager::unassignedUsers(),
					'notice'           => $notice,
					'error'            => $error,
				)
			);
			return;
		}
		if ( 'import' === $route ) {
			$this->render(
				'import',
				array(
					'page_title'    => esc_html__( 'Importar reservas', 'me-transfers' ),
					'hotel_id'      => $hotel_id,
					'hotel_ids'     => HotelAccess::userHotelIds(),
					'route'         => $route,
					'import_result' => BookingImporter::process( $hotel_id ),
				)
			);
			return;
		}

		$operational_routes = array(
			'bookings'    => array( 'bookings', esc_html__( 'Reservas', 'me-transfers' ), 'bookings' ),
			'booking-new' => array( 'booking-new', esc_html__( 'Nueva reserva', 'me-transfers' ), 'createBooking' ),
			'customers'   => array( 'customers', esc_html__( 'Clientes', 'me-transfers' ), 'customers' ),
			'statistics'  => array( 'statistics', esc_html__( 'Estadísticas', 'me-transfers' ), 'statistics' ),
			'profile'     => array( 'profile', esc_html__( 'Mi Hotel', 'me-transfers' ), 'profile' ),
		);
		$route_capabilities = array(
			'bookings'       => Capabilities::HOTEL_VIEW_BOOKINGS,
			'booking-detail' => Capabilities::HOTEL_VIEW_BOOKINGS,
			'booking-new'    => Capabilities::HOTEL_CREATE_BOOKINGS,
			'customers'      => Capabilities::HOTEL_VIEW_BOOKINGS,
			'statistics'     => Capabilities::HOTEL_VIEW_STATS,
			'profile'        => Capabilities::HOTEL_VIEW_PROFILE,
			'users'          => Capabilities::HOTEL_MANAGE_USERS,
			'import'         => Capabilities::HOTEL_IMPORT_BOOKINGS,
		);
		if ( isset( $route_capabilities[ $route ] ) && ! user_can( get_current_user_id(), $route_capabilities[ $route ] ) ) {
			$this->renderForbidden();
			return;
		}
		if ( 'booking-detail' === $route ) {
			$booking = HotelOperations::booking( $hotel_id, absint( get_query_var( 'mt_hotel_booking_id', 0 ) ) );
			if ( ! $booking ) {
				status_header( 404 );
				$this->renderForbidden();
				return;
			}
			$this->render(
				'booking-detail',
				array(
					'page_title' => esc_html__( 'Detalle de reserva', 'me-transfers' ),
					'hotel_id'   => $hotel_id,
					'hotel_ids'  => HotelAccess::userHotelIds(),
					'route'      => $route,
					'page_data'  => $booking,
				)
			);
			return;
		}
		if ( isset( $operational_routes[ $route ] ) ) {
			$config    = $operational_routes[ $route ];
			$page_data = call_user_func( array( HotelOperations::class, $config[2] ), $hotel_id );
			$this->render(
				$config[0],
				array(
					'page_title' => $config[1],
					'hotel_id'   => $hotel_id,
					'hotel_ids'  => HotelAccess::userHotelIds(),
					'route'      => $route,
					'page_data'  => $page_data,
				)
			);
			return;
		}
		if ( 'dashboard' !== $route ) {
			status_header( 404 );
			$this->renderForbidden();
			return;
		}

		$this->render(
			'dashboard',
			array(
				'page_title' => esc_html__( 'Dashboard', 'me-transfers' ),
				'hotel_id'   => $hotel_id,
				'hotel_ids'  => HotelAccess::userHotelIds(),
				'route'      => $route,
				'dashboard'  => HotelDashboard::data( $hotel_id ),
			)
		);
	}

	private function dispatchLogin() {
		if ( is_user_logged_in() ) {
			if ( HotelAccess::canEnterPortal() ) {
				wp_safe_redirect( self::url( 'dashboard' ) );
				exit;
			}
			$this->renderForbidden();
			return;
		}

		$error = $this->auth->processLogin();
		$this->render(
			'login',
			array(
				'page_title' => esc_html__( 'Acceso para hoteles', 'me-transfers' ),
				'error'      => $error,
			)
		);
	}

	private function processHotelSwitch() {
		if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || empty( $_POST['mt_hotel_switch'] ) ) {
			return;
		}

		$nonce    = isset( $_POST['mt_hotel_switch_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mt_hotel_switch_nonce'] ) ) : '';
		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( $_POST['hotel_id'] ) : 0;
		if ( ! wp_verify_nonce( $nonce, 'mt_hotel_switch' ) || ! HotelContext::selectHotel( $hotel_id ) ) {
			$this->renderForbidden();
			exit;
		}

		AuditLog::record( 'hotel.portal.hotel_switched', 'hotel', $hotel_id );
		wp_safe_redirect( self::url( 'dashboard' ) );
		exit;
	}

	private function renderForbidden( $message = '' ) {

		status_header( 403 );
		$this->render(
			'forbidden',
			array(
				'page_title' => esc_html__( 'Acceso no disponible', 'me-transfers' ),
				'message'    => $message ? (string) $message : esc_html__( 'Tu usuario no tiene acceso a un hotel activo. Contacta con MeTransfers.', 'me-transfers' ),
			)
		);
	}

	private function render( $view, array $data = array() ) {
		$views = array( 'login', 'forbidden', 'dashboard', 'users', 'import', 'bookings', 'booking-new', 'booking-detail', 'customers', 'statistics', 'profile' );
		if ( ! in_array( $view, $views, true ) ) {
			throw new \LogicException( 'Invalid Hotel Portal view.' );
		}

		$content_view     = dirname( __DIR__ ) . '/Views/' . $view . '.php';
		$page_title       = isset( $data['page_title'] ) ? $data['page_title'] : 'MeTransfers';
		$error            = isset( $data['error'] ) ? $data['error'] : '';
		$message          = isset( $data['message'] ) ? $data['message'] : '';
		$hotel_id         = isset( $data['hotel_id'] ) ? absint( $data['hotel_id'] ) : 0;
		$hotel_ids        = isset( $data['hotel_ids'] ) ? array_map( 'absint', (array) $data['hotel_ids'] ) : null;
		$route            = isset( $data['route'] ) ? sanitize_key( (string) $data['route'] ) : '';
		$notice           = isset( $data['notice'] ) ? (string) $data['notice'] : '';
		$user_groups      = isset( $data['user_groups'] ) ? (array) $data['user_groups'] : array();
		$unassigned_users = isset( $data['unassigned_users'] ) ? (array) $data['unassigned_users'] : array();
		$dashboard        = isset( $data['dashboard'] ) ? (array) $data['dashboard'] : array();
		$import_result    = isset( $data['import_result'] ) ? (array) $data['import_result'] : array();
		$page_data        = $data['page_data'] ?? array();
		include dirname( __DIR__ ) . '/Views/layout.php';
		exit;
	}

	public static function url( $route = '' ) {
		$paths = array(
			''            => '',
			'login'       => '',
			'dashboard'   => 'dashboard/',
			'bookings'    => 'reservas/',
			'booking-new' => 'reservas/nueva/',
			'customers'   => 'clientes/',
			'statistics'  => 'estadisticas/',
			'profile'     => 'perfil/',
			'users'       => 'usuarios/',
			'import'      => 'importar/',
			'logout'      => 'logout/',
		);
		$route = sanitize_key( (string) $route );
		$path  = isset( $paths[ $route ] ) ? $paths[ $route ] : '';
		return home_url( '/hoteles/' . $path );
	}
}
