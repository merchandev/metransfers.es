<?php
namespace MeTransfers\Admin;

class Menu {
	public function register() {
		// Main Menu (replaces WPTB_Admin)
		add_menu_page(
			'MeTransfers',
			'MeTransfers',
			Capabilities::MANAGE_BOOKINGS,
			'wptb-reservas',
			$this->get_legacy_callback( 'WPTB_Admin', 'display_bookings_page' ),
			'dashicons-car',
			26
		);

		// Core Submenus
		add_submenu_page( 'wptb-reservas', 'Reservas', 'Reservas', Capabilities::MANAGE_BOOKINGS, 'wptb-reservas', $this->get_legacy_callback( 'WPTB_Admin', 'display_bookings_page' ) );
		add_submenu_page( 'wptb-reservas', 'Flota', 'Flota de Vehículos', Capabilities::MANAGE_VEHICLES, 'wptb-vehicles', $this->get_legacy_callback( 'WPTB_Vehicles_Admin', 'display_vehicles_page' ) );
		add_submenu_page( 'wptb-reservas', 'Destinos', 'Destinos', Capabilities::MANAGE_VEHICLES, 'edit.php?post_type=wptb_destination' );
		add_submenu_page( 'wptb-reservas', 'Estadísticas', 'Estadísticas', Capabilities::VIEW_STATS, 'wptb-stats', $this->get_legacy_callback( 'WPTB_Admin', 'display_stats_page' ) );

		// Hotel Submenus also under the main menu (visible for admins)
		add_submenu_page( 'wptb-reservas', 'Hoteles', 'Red de Hoteles', Capabilities::MANAGE_HOTELS, 'edit.php?post_type=hotel_partner' );
		add_submenu_page( 'wptb-reservas', 'Reservas Hotel QR', 'Reservas Hotel QR', Capabilities::MANAGE_HOTELS, 'hotel-qr-reservations', $this->get_legacy_callback( 'HQP_Admin', 'display_hotel_reservations_page' ) );
		add_submenu_page( 'wptb-reservas', 'Importar Hoteles', 'Importar Hoteles', Capabilities::MANAGE_HOTELS, 'mt-import-hotels', $this->get_legacy_callback( 'HQP_Import_Export', 'render_page' ) );

		// General Settings
		add_submenu_page( 'wptb-reservas', 'Ajustes Generales', 'Ajustes Generales', Capabilities::MANAGE_INTEGRATIONS, 'wptb-settings', $this->get_legacy_callback( 'WPTB_Admin', 'display_settings_page' ) );
		add_submenu_page( 'wptb-reservas', 'Auditoría', 'Auditoría', Capabilities::MANAGE_INTEGRATIONS, 'mt-admin-audit', array( AuditLog::class, 'renderPage' ) );

		// ── Hotel Partner standalone menu ──────────────────────────────────────
		// This top-level menu acts as the REGISTERED PARENT for the hotel CPT
		// page so that WordPress core's user_can_access_admin_page() authorises
		// users who only have mt_manage_hotels (CheckHoteles role) without
		// needing mt_manage_bookings.  For administrators this menu is hidden via
		// the hide_menus filter, but it MUST be registered so that
		// edit.php?post_type=hotel_partner passes the WordPress capability check.
		add_menu_page(
			'Red de Hoteles',
			'Red de Hoteles',
			Capabilities::MANAGE_HOTELS,
			'mt-hoteles-hub',
			$this->get_legacy_callback( 'HQP_Admin', 'display_hotel_reservations_page' ),
			'dashicons-building',
			27
		);
		add_submenu_page( 'mt-hoteles-hub', 'Hoteles', 'Red de Hoteles', Capabilities::MANAGE_HOTELS, 'edit.php?post_type=hotel_partner' );
		add_submenu_page( 'mt-hoteles-hub', 'Reservas Hotel QR', 'Reservas Hotel QR', Capabilities::MANAGE_HOTELS, 'hotel-qr-reservations', $this->get_legacy_callback( 'HQP_Admin', 'display_hotel_reservations_page' ) );
		add_submenu_page( 'mt-hoteles-hub', 'Importar Hoteles', 'Importar Hoteles', Capabilities::MANAGE_HOTELS, 'mt-import-hotels', $this->get_legacy_callback( 'HQP_Import_Export', 'render_page' ) );
	}

	private function get_legacy_callback( $class_name, $method ) {
		return function () use ( $class_name, $method ) {
			// Lazy load the legacy class instance
			static $instance = null;
			if ( null === $instance ) {
				if ( class_exists( $class_name ) ) {
					$instance = new $class_name();
				} else {
					echo '<div class="wrap"><h2>' . esc_html( "Error: clase $class_name no encontrada." ) . '</h2></div>';
					return;
				}
			}

			if ( method_exists( $instance, $method ) ) {
				$instance->$method();
			} else {
				echo '<div class="wrap"><h2>' . esc_html( "Error: método $class::$method no encontrado." ) . '</h2></div>';
			}
		};
	}
}
