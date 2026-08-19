<?php
namespace MeTransfers\Admin;

class Menu {
    public function register() {
        // Main Menu (replaces WPTB_Admin)
        add_menu_page(
            'MeTransfers', 
            'MeTransfers', 
            'manage_options', 
            'wptb-reservas',
            $this->get_legacy_callback('WPTB_Admin', 'display_bookings_page'), 
            'dashicons-car', 
            26 
        );

        // Core Submenus
        add_submenu_page('wptb-reservas', 'Reservas', 'Reservas', 'manage_options', 'wptb-reservas', $this->get_legacy_callback('WPTB_Admin', 'display_bookings_page'));
        add_submenu_page('wptb-reservas', 'Flota', 'Flota de Vehículos', 'manage_options', 'wptb-vehicles', $this->get_legacy_callback('WPTB_Vehicles_Admin', 'display_vehicles_page'));
        add_submenu_page('wptb-reservas', 'Destinos', 'Destinos', 'manage_options', 'edit.php?post_type=wptb_destination');
        add_submenu_page('wptb-reservas', 'Estadísticas', 'Estadísticas', 'manage_options', 'wptb-stats', $this->get_legacy_callback('WPTB_Admin', 'display_stats_page'));

        // Hotel Submenus (Integrating HQP_Admin)
        add_submenu_page('wptb-reservas', 'Hoteles', 'Red de Hoteles', 'manage_options', 'edit.php?post_type=hotel_partner');
        add_submenu_page('wptb-reservas', 'Reservas Hotel QR', 'Reservas Hotel QR', 'manage_options', 'hotel-qr-reservations', $this->get_legacy_callback('HQP_Admin', 'display_hotel_reservations_page'));
        add_submenu_page('wptb-reservas', 'Vehículos Hotel', 'Vehículos Hotel', 'manage_options', 'hotel-vehicles', $this->get_legacy_callback('HQP_Vehicles_Admin', 'display_vehicles_page'));

        // General Settings
        add_submenu_page('wptb-reservas', 'Ajustes Generales', 'Ajustes Generales', 'manage_options', 'wptb-settings', $this->get_legacy_callback('WPTB_Admin', 'display_settings_page'));
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
