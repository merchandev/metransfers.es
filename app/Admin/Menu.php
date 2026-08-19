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

        // Hotel Submenus (Integrating HQP_Admin)
        add_submenu_page('wptb-reservas', 'Hoteles', 'Red de Hoteles', 'manage_options', 'edit.php?post_type=hotel_partner');
        add_submenu_page('wptb-reservas', 'Hotel QR Ajustes', 'Ajustes Hotel QR', 'manage_options', 'hqp-settings', $this->get_legacy_callback('HQP_Admin', 'display_settings_page'));

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
                    echo "<div class='wrap'><h2>Error: Legacy class $class no encontrada.</h2></div>";
                    return;
                }
            }
            
            if (method_exists($instance, $method)) {
                $instance->$method();
            } else {
                echo "<div class='wrap'><h2>Error: Legacy $class::$method no encontrado.</h2></div>";
            }
        };
    }
}