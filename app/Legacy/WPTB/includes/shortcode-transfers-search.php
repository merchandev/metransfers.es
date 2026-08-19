<?php
/**
 * Premium Transfers Search Shortcode
 * Integrated into main WPTB plugin
 */

// Register Shortcode
function wptb_premium_transfers_search_shortcode() {
    add_shortcode('premium_transfers_search', 'wptb_render_transfers_search');
}
add_action('init', 'wptb_premium_transfers_search_shortcode');

// Include PTS Modal in footer
function wptb_include_pts_modal() {
    if (!is_admin()) {
        include WPTB_PLUGIN_DIR . 'templates/pts-booking-modal.php';
    }
}
add_action('wp_footer', 'wptb_include_pts_modal');

// Enqueue Assets for Shortcode
function wptb_enqueue_transfers_search_assets() {
    // Always enqueue on frontend (shortcode check doesn't work reliably)
    if (!is_admin()) {
        // Google Fonts
        wp_enqueue_style(
            'wptb-inter-font',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            array(),
            null
        );
        
        wp_enqueue_style(
            'wptb-transfers-search',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/transfers-search.css',
            array(),
            '1.0.2'
        );
        
        wp_enqueue_script(
            'wptb-transfers-search',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/transfers-search.js',
            array('jquery', 'wptb-booking-js'), // Depend on booking-js so wptbEnsurePlacesReady is available
            '1.0.7',
            true
        );

        // Destinations data
        $destinations = array(
            array('name' => 'Tossa de Mar', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Lloret de Mar', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Barcelona Sitges', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Barcelona Salou', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Costa Brava', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Pineda de Mar', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Calella', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Malgrat', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Santa Susanna', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Roses Barcelona', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Barcelona Cadaques', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Begur', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Calella de Palafrugell', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'La Escala', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'Palamos', 'category' => 'playa', 'region' => 'Cataluña'),
            array('name' => 'San Sebastian', 'category' => 'playa', 'region' => 'País Vasco'),
            array('name' => 'Benidorm', 'category' => 'playa', 'region' => 'Valencia'),
            array('name' => 'Marbella', 'category' => 'playa', 'region' => 'Andalucía'),
            array('name' => 'Barcelona Cambrils', 'category' => 'playa', 'region' => 'Cataluña'),
            
            array('name' => 'Barcelona', 'category' => 'ciudad', 'region' => 'Cataluña'),
            array('name' => 'Madrid Barcelona', 'category' => 'ciudad', 'region' => 'Madrid'),
            array('name' => 'Sevilla', 'category' => 'ciudad', 'region' => 'Andalucía'),
            array('name' => 'Valencia', 'category' => 'ciudad', 'region' => 'Valencia'),
            array('name' => 'Girona', 'category' => 'ciudad', 'region' => 'Cataluña'),
            array('name' => 'Tarragona', 'category' => 'ciudad', 'region' => 'Cataluña'),
            array('name' => 'Vigo Barcelona', 'category' => 'ciudad', 'region' => 'Galicia'),
            array('name' => 'Bilbao Barcelona', 'category' => 'ciudad', 'region' => 'País Vasco'),
            array('name' => 'Santiago de Compostela', 'category' => 'ciudad', 'region' => 'Galicia'),
            array('name' => 'Lourdes', 'category' => 'ciudad', 'region' => 'Francia'),
            array('name' => 'Granada Barcelona', 'category' => 'ciudad', 'region' => 'Andalucía'),
            array('name' => 'Figueres Barcelona', 'category' => 'ciudad', 'region' => 'Cataluña'),
            array('name' => 'Perpignan', 'category' => 'ciudad', 'region' => 'Francia'),
            array('name' => 'Almeria', 'category' => 'ciudad', 'region' => 'Andalucía'),
            
            array('name' => 'PortAventura', 'category' => 'ocio', 'region' => 'Cataluña'),
            array('name' => 'Camping El Delfin Verde', 'category' => 'ocio', 'region' => 'Cataluña'),
            
            array('name' => 'Barcelona Reus', 'category' => 'aeropuerto', 'region' => 'Cataluña'),
            
            array('name' => 'Andorra', 'category' => 'montana', 'region' => 'Andorra'),
            array('name' => 'Vall de Nuria', 'category' => 'montana', 'region' => 'Cataluña'),
            array('name' => 'Barcelona Bagueira Beret', 'category' => 'montana', 'region' => 'Cataluña')
        );

        $maps_api_key = trim( (string) get_option( 'wptb_google_maps_api_key', '' ) );
        if ( empty( $maps_api_key ) ) {
            $maps_api_key = trim( (string) get_option( 'wptb_google_api_key', '' ) );
        }
        if ( empty( $maps_api_key ) && defined( 'WPTB_GOOGLE_MAPS_API_KEY' ) ) {
            $maps_api_key = trim( (string) constant( 'WPTB_GOOGLE_MAPS_API_KEY' ) );
        }
        if ( empty( $maps_api_key ) && defined( 'GOOGLE_MAPS_API_KEY' ) ) {
            $maps_api_key = trim( (string) constant( 'GOOGLE_MAPS_API_KEY' ) );
        }

        wp_localize_script('wptb-transfers-search', 'ptsData', array(
            'destinations' => $destinations,
            'google_maps_api_key' => $maps_api_key,
            'google_maps_language' => 'es',
            'google_maps_region' => 'ES'
        ));
    }
}
add_action('wp_enqueue_scripts', 'wptb_enqueue_transfers_search_assets');

// Render Shortcode
function wptb_render_transfers_search() {
    ob_start();
    ?>
    <div id="pts-module-wrapper">
        <div class="pts-container">
            <!-- Search and Filters -->
            <div class="pts-search-section">
                <div class="pts-search-container">
                    <svg class="pts-search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M9 17A8 8 0 1 0 9 1a8 8 0 0 0 0 16zM19 19l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input 
                        type="text" 
                        id="ptsSearchInput" 
                        class="pts-search-input" 
                        placeholder="Buscar destino..."
                        autocomplete="off"
                    />
                </div>

                <div class="pts-filters">
                    <button class="pts-filter-btn active" data-category="all">
                        <span>Todos</span>
                    </button>
                    <button class="pts-filter-btn" data-category="ciudad">
                        <span>Ciudades</span>
                    </button>
                    <button class="pts-filter-btn" data-category="playa">
                        <span>Playas</span>
                    </button>
                    <button class="pts-filter-btn" data-category="aeropuerto">
                        <span>Aeropuertos</span>
                    </button>
                    <button class="pts-filter-btn" data-category="montana">
                        <span>Montaña</span>
                    </button>
                </div>
            </div>

            <!-- Destinations Grid -->
            <div id="ptsDestinationsGrid" class="pts-destinations-grid"></div>

            <!-- No Results -->
            <div id="ptsNoResults" class="pts-no-results" style="display: none;" data-nosnippet aria-hidden="true">
                <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                    <circle cx="32" cy="32" r="32" fill="#F3F4F6"/>
                    <path d="M28 26a4 4 0 1 1 8 0 4 4 0 0 1-8 0zM26 40c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <h3>No se encontraron destinos</h3>
                <p>Intenta con otro término de búsqueda</p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
