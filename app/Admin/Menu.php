<?php
namespace MeTransfers\Admin;

class Menu {
	public function register() {

		// Main Menu (replaces WPTB_Admin)
		add_menu_page(
			'Reservas',
			'Reservas',
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

		// General Settings
		add_submenu_page( 'wptb-reservas', 'Ajustes Generales', 'Ajustes Generales', Capabilities::MANAGE_INTEGRATIONS, 'wptb-settings', $this->get_legacy_callback( 'WPTB_Admin', 'display_settings_page' ) );
		add_submenu_page( 'wptb-reservas', 'Auditoría', 'Auditoría', Capabilities::MANAGE_INTEGRATIONS, 'mt-admin-audit', array( AuditLog::class, 'renderPage' ) );

		// Hotel operations live in an independent section. Existing slugs remain
		// unchanged so saved links and integrations continue to work.
		add_menu_page(
			'Hoteles',
			'Hoteles',
			Capabilities::MANAGE_HOTELS,
			'mt-hoteles-hub',
			$this->get_legacy_callback( 'HQP_Admin', 'display_hotel_reservations_page' ),
			'dashicons-building',
			27
		);
		add_submenu_page( 'mt-hoteles-hub', 'Hoteles', 'Red de Hoteles', Capabilities::MANAGE_HOTELS, 'edit.php?post_type=hotel_partner' );
		add_submenu_page( 'mt-hoteles-hub', 'Reservas de Hoteles', 'Reservas de Hoteles', Capabilities::MANAGE_HOTELS, 'hotel-qr-reservations', $this->get_legacy_callback( 'HQP_Admin', 'display_hotel_reservations_page' ) );
		add_submenu_page( 'mt-hoteles-hub', 'Importar Reservas', 'Importar Reservas', Capabilities::HOTEL_IMPORT_BOOKINGS, 'mt-hotel-import-bookings', array( $this, 'renderImportBookingsPage' ) );
		add_submenu_page( 'mt-hoteles-hub', 'Importar Hoteles', 'Importar Hoteles', Capabilities::MANAGE_HOTELS, 'mt-import-hotels', $this->get_legacy_callback( 'HQP_Import_Export', 'render_page' ) );
		add_submenu_page( 'mt-hoteles-hub', 'Usuarios y Accesos', 'Usuarios / Accesos', Capabilities::HOTEL_MANAGE_USERS, 'mt-hotel-users', array( HotelUsersPage::class, 'render' ) );
		add_submenu_page( 'mt-hoteles-hub', 'Portal de Hoteles', 'Portal de Hoteles', Capabilities::HOTEL_PORTAL_ACCESS, 'mt-hotel-portal-link', array( $this, 'renderPortalPage' ) );
	}

	public function enqueueStyles() {

		wp_register_style( 'mt-admin-menu', false, array(), MT_PLATFORM_VERSION );
		wp_enqueue_style( 'mt-admin-menu' );
			wp_add_inline_style(
				'mt-admin-menu',
				'#toplevel_page_mt-hoteles-hub > a{background:#6f42c1!important;color:#fff!important;text-decoration:none!important}#toplevel_page_mt-hoteles-hub:hover > a,#toplevel_page_mt-hoteles-hub.wp-has-current-submenu > a{background:#59359a!important;color:#fff!important}#toplevel_page_wptb-reservas a,#toplevel_page_mt-hoteles-hub a{text-decoration:none!important}'
			);
	}

	public function renderImportBookingsPage() {

			$this->renderPortalLinkPage( 'Importar reservas', 'Abre el importador seguro del Portal de Hoteles para seleccionar el hotel y cargar un archivo XLSX o CSV.', \MeTransfers\HotelPortal\Routing\Router::url( 'import' ), 'Abrir importador' );
	}

	public function renderPortalPage() {

			$this->renderPortalLinkPage( 'Portal de Hoteles', 'Accede al dashboard operativo de hoteles con tu sesión actual.', \MeTransfers\HotelPortal\Routing\Router::url( 'dashboard' ), 'Abrir Portal de Hoteles' );
	}

	private function renderPortalLinkPage( $title, $description, $url, $button_label ) {

			echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $description ) . '</p><p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html( $button_label ) . '</a></p></div>';
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
				echo '<div class="wrap"><h2>' . esc_html( "Error: método $class_name::$method no encontrado." ) . '</h2></div>';
			}
		};
	}
}
