<?php
namespace MeTransfers\Core;

class Application {
	private static $booted = false;

	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		if ( ! defined( 'MT_PLATFORM_VERSION' ) ) {
			define( 'MT_PLATFORM_VERSION', '6.9.2' );
		}
		if ( ! defined( 'MT_PLATFORM_DB_VERSION' ) ) {
			define( 'MT_PLATFORM_DB_VERSION', '6.6.0' );
		}
		if ( ! defined( 'MT_TERMS_VERSION' ) ) {
			define( 'MT_TERMS_VERSION', '2026-08-18' );
		}

		if ( ! defined( 'MT_WPTB_DIR' ) ) {
			define( 'MT_WPTB_DIR', get_template_directory() . '/app/Legacy/WPTB/' );
		}
		if ( ! defined( 'MT_WPTB_URL' ) ) {
			define( 'MT_WPTB_URL', get_template_directory_uri() . '/app/Legacy/WPTB/' );
		}
		if ( ! defined( 'WPTB_PLUGIN_DIR' ) ) {
			define( 'WPTB_PLUGIN_DIR', MT_WPTB_DIR );
		}
		if ( ! defined( 'WPTB_PLUGIN_URL' ) ) {
			define( 'WPTB_PLUGIN_URL', MT_WPTB_URL );
		}
		if ( ! defined( 'WPTB_VERSION' ) ) {
			define( 'WPTB_VERSION', MT_PLATFORM_VERSION );
		}

		if ( ! defined( 'HQP_PLUGIN_DIR' ) ) {
			define( 'HQP_PLUGIN_DIR', get_template_directory() . '/app/Legacy/Hotel/' );
		}
		if ( ! defined( 'HQP_PLUGIN_URL' ) ) {
			define( 'HQP_PLUGIN_URL', get_template_directory_uri() . '/app/Legacy/Hotel/' );
		}
		if ( ! defined( 'HQP_VERSION' ) ) {
			define( 'HQP_VERSION', MT_PLATFORM_VERSION );
		}

		self::loadLegacyModules();

		// Boot modern components
		$capabilities = new \MeTransfers\Admin\Capabilities();
		$capabilities->register();

		$settings = new \MeTransfers\Core\Settings();
		$settings->register();

		$post_types = new \MeTransfers\Core\PostTypes();
		add_action( 'init', array( $post_types, 'register' ), 5 );
		$shortcodes = new \MeTransfers\Booking\Shortcodes();
		$shortcodes->register();

		$assets = new \MeTransfers\Core\Assets();
		$assets->register();

		$hotel_portal = new \MeTransfers\HotelPortal\HotelPortal();
		$hotel_portal->register();
		if ( self::hasExternalBookingPluginConflict() ) {
			add_action( 'admin_notices', array( __CLASS__, 'renderBookingPluginConflictNotice' ) );
		}

		$migrations = new \MeTransfers\Core\Migrations();
		$migrations->register();

		$seeds = new \MeTransfers\Core\Seeds();
		$seeds->register();

		$outbox = new \MeTransfers\Core\Outbox();
		$outbox->register();

		$drafts = new \MeTransfers\Booking\BookingDraftService();
		$drafts->register();

		$receipts = new \MeTransfers\Booking\ReceiptController();
		$receipts->register();

		$analytics = new \MeTransfers\Analytics\PurchaseOutbox();
		$analytics->register();

		if ( is_admin() ) {
			$admin_menu = new \MeTransfers\Admin\Menu();
			add_action( 'admin_menu', array( $admin_menu, 'register' ) );
			add_action( 'admin_enqueue_scripts', array( $admin_menu, 'enqueueStyles' ) );
			// Aviso si faltan páginas críticas del flujo de reserva
			add_action( 'admin_notices', array( '\MeTransfers\Core\Seeds', 'adminNoticesMissingPages' ) );

			// Endpoint para crear páginas faltantes con un clic desde el aviso
			add_action(
				'admin_init',
				static function () {
					if ( ! isset( $_GET['page'] ) || 'mt-seeds-run' !== $_GET['page'] ) {
						return;
					}
					if ( ! current_user_can( 'manage_options' ) ) {
						wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'me-transfers' ) );
					}
					\MeTransfers\Core\Seeds::run();
					wp_safe_redirect( add_query_arg( 'mt_seeds_done', '1', admin_url() ) );
					exit;
				}
			);
		}
	}

	private static function loadLegacyModules() {
		if ( file_exists( WPTB_PLUGIN_DIR . 'wp-booking-plugin.php' ) ) {
			require_once WPTB_PLUGIN_DIR . 'wp-booking-plugin.php';
		}

		if ( file_exists( HQP_PLUGIN_DIR . 'hotel-qr-plugin.php' ) ) {
			require_once HQP_PLUGIN_DIR . 'hotel-qr-plugin.php';
		}

		// The former Unified_Integration shim is intentionally not loaded. Its
		// responsibilities now live in the dedicated booking and hotel modules.
	}

	public static function renderBookingPluginConflictNotice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>MeTransfers:</strong> ';
		echo esc_html__( 'El plugin externo de reservas sigue activo y está cargando código antiguo por encima de la integración del tema. Desactiva “Complete Booking Plugin” para usar el formulario, las cotizaciones y los idiomas integrados.', 'me-transfers' );
		echo '</p></div>';
	}

	private static function hasExternalBookingPluginConflict() {
		if ( ! defined( 'WPTB_PLUGIN_DIR' ) || ! defined( 'MT_WPTB_DIR' ) ) {
			return false;
		}
		$configured = rtrim( str_replace( '\\', '/', WPTB_PLUGIN_DIR ), '/' );
		$integrated = rtrim( str_replace( '\\', '/', MT_WPTB_DIR ), '/' );
		return $configured !== $integrated;
	}
}
