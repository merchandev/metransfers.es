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
            $this->get_legacy_callback('WPTB_Admin', 'display_bookings_page'), 
            'dashicons-car', 
            26 
        );

        // Core Submenus
        add_submenu_page('wptb-reservas', 'Reservas', 'Reservas', Capabilities::MANAGE_BOOKINGS, 'wptb-reservas', $this->get_legacy_callback('WPTB_Admin', 'display_bookings_page'));
        add_submenu_page('wptb-reservas', 'Flota', 'Flota de Vehículos', Capabilities::MANAGE_VEHICLES, 'wptb-vehicles', $this->get_legacy_callback('WPTB_Vehicles_Admin', 'display_vehicles_page'));
        add_submenu_page('wptb-reservas', 'Destinos', 'Destinos', Capabilities::MANAGE_VEHICLES, 'edit.php?post_type=wptb_destination');
        add_submenu_page('wptb-reservas', 'Estadísticas', 'Estadísticas', Capabilities::VIEW_STATS, 'wptb-stats', $this->get_legacy_callback('WPTB_Admin', 'display_stats_page'));

        // Hotel Submenus (Integrating HQP_Admin)
        add_submenu_page('wptb-reservas', 'Hoteles', 'Red de Hoteles', Capabilities::MANAGE_HOTELS, 'edit.php?post_type=hotel_partner');
        add_submenu_page('wptb-reservas', 'Reservas Hotel QR', 'Reservas Hotel QR', Capabilities::MANAGE_HOTELS, 'hotel-qr-reservations', $this->get_legacy_callback('HQP_Admin', 'display_hotel_reservations_page'));
        add_submenu_page('wptb-reservas', 'Vehículos Hotel', 'Vehículos Hotel', Capabilities::MANAGE_VEHICLES, 'hotel-vehicles', $this->get_legacy_callback('HQP_Vehicles_Admin', 'display_vehicles_page'));

        // General Settings
        add_submenu_page('wptb-reservas', 'Ajustes Generales', 'Ajustes Generales', Capabilities::MANAGE_INTEGRATIONS, 'wptb-settings', $this->get_legacy_callback('WPTB_Admin', 'display_settings_page'));
        add_submenu_page('wptb-reservas', 'Auditoría', 'Auditoría', Capabilities::MANAGE_INTEGRATIONS, 'mt-admin-audit', array( AuditLog::class, 'renderPage' ));
    }

    private function get_legacy_callback($class, $method) {
        return function() use ($class, $method) {
            // Lazy load the legacy class instance
            static $instance = null;
            if ($instance === null) {
                if (class_exists($class)) {
                    $instance = new $class();
                } else {
                    echo '<div class="wrap"><h2>' . esc_html( "Error: clase $class no encontrada." ) . '</h2></div>';
                    return;
                }
            }
            
            if (method_exists($instance, $method)) {
                $instance->$method();
            } else {
                echo '<div class="wrap"><h2>' . esc_html( "Error: método $class::$method no encontrado." ) . '</h2></div>';
            }
        };
    }
}
