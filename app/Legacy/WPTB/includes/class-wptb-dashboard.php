<?php

class WPTB_Dashboard {

    public function init() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_chart_js'));
    }

    private function get_check_hoteles_where_sql() {
        global $wpdb;
        $user = wp_get_current_user();
        if ( in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
            $hotels = get_posts(array(
                'post_type' => 'hotel_partner',
                'posts_per_page' => -1,
                'author' => $user->ID,
                'fields' => 'ids'
            ));
            
            $allowed_tokens = array();
            foreach ($hotels as $hotel_id) {
                $token = get_post_meta($hotel_id, '_hqp_token', true);
                if ($token) $allowed_tokens[] = $token;
            }
            
            if (empty($allowed_tokens)) {
                return " AND 1=0";
            }
            $placeholders = implode(',', array_fill(0, count($allowed_tokens), '%s'));
            return $wpdb->prepare(" AND hotel_token IN ($placeholders)", ...$allowed_tokens);
        }
        return "";
    }

    public function enqueue_chart_js($hook) {
        if ($hook !== 'index.php') {
            return;
        }
        // Enqueue Chart.js from CDN
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
    }

    public function add_dashboard_widgets() {
        $user = wp_get_current_user();
        $is_check_hotel = in_array( 'check_hoteles', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles );

        if ( ! $is_check_hotel ) {
            wp_add_dashboard_widget(
                'wptb_dashboard_recent_bookings',
                '🆕 Últimas Reservas (Metransfers)',
                array($this, 'render_recent_bookings_widget')
            );
            wp_add_dashboard_widget(
                'wptb_dashboard_pricing',
                '🏷️ Precios y Tarifas',
                array($this, 'render_pricing_widget')
            );
        }

        wp_add_dashboard_widget(
            'wptb_dashboard_hotel_bookings',
            '🏨 Últimas Reservas de Hoteles',
            array($this, 'render_hotel_bookings_widget')
        );

        wp_add_dashboard_widget(
            'wptb_dashboard_stats',
            '📊 Estadísticas de Progreso',
            array($this, 'render_stats_widget')
        );

        wp_add_dashboard_widget(
            'wptb_dashboard_routes',
            '📍 Rutas Populares (Salidas y Destinos)',
            array($this, 'render_routes_widget')
        );
    }

    public function render_hotel_bookings_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        
        $where_ext = $this->get_check_hoteles_where_sql();
        $where_sql = "hotel_token IS NOT NULL AND hotel_token != ''" . $where_ext;

        $bookings = $wpdb->get_results("SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT 5");

        echo '<div class="wptb-dashboard-bookings">';
        if (!empty($bookings)) {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>ID</th><th>Fecha</th><th>Cliente</th><th>Hotel (Token)</th><th>Estado</th></tr></thead>';
            echo '<tbody>';
            foreach ($bookings as $booking) {
                $status_label = ucfirst($booking->status);
                if ($booking->status == 'pending') $status_label = 'Pendiente';
                if ($booking->status == 'confirmed') $status_label = 'Confirmado';
                if ($booking->status == 'cancelled') $status_label = 'Cancelado';

                $status_color = '#777';
                if ($booking->status == 'confirmed') $status_color = '#28a745';
                if ($booking->status == 'pending') $status_color = '#ffc107';

                $link = admin_url('edit.php?post_type=hotel_partner&page=hotel-qr-reservations');

                echo '<tr>';
                echo '<td><a href="' . $link . '">#' . $booking->id . '</a></td>';
                echo '<td>' . date('d/m', strtotime($booking->booking_date)) . '</td>';
                echo '<td>' . esc_html($booking->customer_name) . '</td>';
                echo '<td>' . esc_html($booking->hotel_token) . '</td>';
                echo '<td><span style="color:' . $status_color . '; font-weight:bold;">' . $status_label . '</span></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No hay reservas de hoteles recientes.</p>';
        }
        $link_all = admin_url('edit.php?post_type=hotel_partner&page=hotel-qr-reservations');
        echo '<div style="margin-top:10px; text-align:right;"><a href="' . $link_all . '" class="button button-small">Ver todas</a></div>';
        echo '</div>';
    }

    public function render_recent_bookings_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        $bookings = $wpdb->get_results("SELECT * FROM $table_name WHERE 1=1 " . $this->get_check_hoteles_where_sql() . " ORDER BY created_at DESC LIMIT 5");

        echo '<div class="wptb-dashboard-bookings">';
        if (!empty($bookings)) {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>ID</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th>Precio</th></tr></thead>';
            echo '<tbody>';
            foreach ($bookings as $booking) {
                $status_label = ucfirst($booking->status);
                
                // Translate status roughly
                if ($booking->status == 'pending') $status_label = 'Pendiente';
                if ($booking->status == 'confirmed') $status_label = 'Confirmado';
                if ($booking->status == 'cancelled') $status_label = 'Cancelado';

                $status_color = '#777';
                if ($booking->status == 'confirmed') $status_color = '#28a745';
                if ($booking->status == 'pending') $status_color = '#ffc107';

                echo '<tr>';
                echo '<td><a href="' . (in_array('check_hoteles', (array)wp_get_current_user()->roles) && !in_array('administrator', (array)wp_get_current_user()->roles) ? admin_url('edit.php?post_type=hotel_partner&page=hotel-qr-reservations') : admin_url('admin.php?page=wptb-reservas')) . '">#' . $booking->id . '</a></td>';
                echo '<td>' . date('d/m', strtotime($booking->booking_date)) . '</td>';
                echo '<td>' . esc_html($booking->customer_name) . '</td>';
                echo '<td><span style="color:' . $status_color . '; font-weight:bold;">' . $status_label . '</span></td>';
                echo '<td>€' . $booking->price . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No hay reservas recientes.</p>';
        }
        echo '<div style="margin-top:10px; text-align:right;"><a href="' . (in_array('check_hoteles', (array)wp_get_current_user()->roles) && !in_array('administrator', (array)wp_get_current_user()->roles) ? admin_url('edit.php?post_type=hotel_partner&page=hotel-qr-reservations') : admin_url('admin.php?page=wptb-reservas')) . '" class="button button-small">Ver todas</a></div>';
        echo '</div>';
    }

    public function render_stats_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';

        // Stats for Chart (Last 6 Months)
        $months = [];
        $revenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month_start = date('Y-m-01 H:i:s', strtotime("-$i months"));
            $month_end   = date('Y-m-t 23:59:59', strtotime("-$i months"));
            $month_label = date('M', strtotime("-$i months"));

            $monthly_total = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(price) FROM $table_name WHERE created_at BETWEEN %s AND %s AND status = 'confirmed'" . $this->get_check_hoteles_where_sql() . "",
                $month_start, $month_end
            ));

            $months[] = $month_label;
            $revenue[] = $monthly_total ? $monthly_total : 0;
        }

        ?>
        <canvas id="wptbRevenueChart" width="100%" height="50"></canvas>
        <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: center;">
            <div style="background:#f9f9f9; padding:10px; border-radius:4px;">
                <span style="display:block; font-size:12px; color:#666;">Total Ingresos (Año)</span>
                <strong style="font-size:16px; color:#28a745;">€<?php echo number_format(array_sum($revenue), 2); ?></strong>
            </div>
            <div style="background:#f9f9f9; padding:10px; border-radius:4px;">
                <span style="display:block; font-size:12px; color:#666;">Mes Actual</span>
                <strong style="font-size:16px; color:#007cba;">€<?php echo number_format(end($revenue), 2); ?></strong>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('wptbRevenueChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Ingresos (€)',
                        data: <?php echo json_encode($revenue); ?>,
                        borderColor: '#006597',
                        backgroundColor: 'rgba(255, 140, 0, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });
        </script>
        <?php
    }

    public function render_pricing_widget() {
        global $wpdb;
        $vehicles_table = $wpdb->prefix . 'wptb_vehicles';
        
        // Count active vehicles (Revised to use is_active)
        $vehicle_count = $wpdb->get_var("SELECT COUNT(*) FROM $vehicles_table WHERE is_active = 1");
        $avg_price_km = $wpdb->get_var("SELECT AVG(price_per_km_oneway) FROM $vehicles_table WHERE is_active = 1");

        echo '<div class="wptb-pricing-summary">';
        echo '<ul style="margin:0; padding:0; list-style:none;">';
        echo '<li style="margin-bottom:10px; display:flex; justify-content:space-between;">';
        echo '<span>🚐 Vehículos Activos:</span> <strong>' . $vehicle_count . '</strong>';
        echo '</li>';
        echo '<li style="margin-bottom:10px; display:flex; justify-content:space-between;">';
        echo '<span>📉 Precio Promedio (Km):</span> <strong>€' . number_format($avg_price_km, 2) . '</strong>';
        echo '</li>';
        echo '</ul>';
        
        echo '<div style="margin-top:15px; padding-top:10px; border-top:1px solid #eee;">';
        echo '<a href="' . admin_url('admin.php?page=wptb-vehicles') . '" class="button button-primary" style="width:100%; text-align:center;">Gestionar Precios de Vehículos</a>';
        echo '</div>';
        echo '</div>';
    }

    public function render_routes_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';

        // Top Origins
        $origins = $wpdb->get_results("SELECT origin, COUNT(*) as count FROM $table_name WHERE 1=1 " . $this->get_check_hoteles_where_sql() . " GROUP BY origin ORDER BY count DESC LIMIT 5");
        
        // Top Destinations
        $destinations = $wpdb->get_results("SELECT destination, COUNT(*) as count FROM $table_name WHERE 1=1 " . $this->get_check_hoteles_where_sql() . " GROUP BY destination ORDER BY count DESC LIMIT 5");

        echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">';
        
        // Origins Column
        echo '<div>';
        echo '<h4 style="margin-top:0; border-bottom:2px solid #ddd; padding-bottom:5px;">🚩 Top Salidas</h4>';
        if($origins) {
            echo '<ul style="list-style:none; padding:0; margin:0;">';
            foreach($origins as $o) {
                echo '<li style="display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f0f0f0;">';
                echo '<span style="font-size:12px; color:#555;">' . esc_html($o->origin) . '</span>';
                echo '<strong>' . $o->count . '</strong>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No hay datos.</p>';
        }
        echo '</div>';

        // Destinations Column
        echo '<div>';
        echo '<h4 style="margin-top:0; border-bottom:2px solid #ddd; padding-bottom:5px;">🏁 Top Destinos</h4>';
        if($destinations) {
            echo '<ul style="list-style:none; padding:0; margin:0;">';
            foreach($destinations as $d) {
                echo '<li style="display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f0f0f0;">';
                echo '<span style="font-size:12px; color:#555;">' . esc_html($d->destination) . '</span>';
                echo '<strong>' . $d->count . '</strong>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No hay datos.</p>';
        }
        echo '</div>';
        
        echo '</div>';
    }
}
