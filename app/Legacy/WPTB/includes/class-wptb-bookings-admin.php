<?php
/**
 * Admin Bookings Management
 * Handles the bookings list page in WordPress admin
 */

class WPTB_Bookings_Admin {

    public function __construct() {
        // admin_menu migrated to app/Admin/Menu.php
        add_action('wp_ajax_wptb_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_wptb_delete_all_bookings', array($this, 'ajax_delete_all_bookings'));
    }

    /**
     * Add Bookings menu to WordPress Admin
     */
    /**
     * REMOVED: add_menu_page for Reservas
     * Reservas is now a submenu under Metransfers in class-wptb-admin.php
     */
    public function add_menu_page() {
        // This function is kept for backward compatibility but does nothing
        // The menu is now registered in WPTB_Admin::add_plugin_admin_menu()
    }

    /**
     * Render the bookings list page
     */
    public function render_bookings_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';

        // Handle status filter
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

        // Build query
        $where = "1=1";
        if ($status_filter !== 'all') {
            $where .= $wpdb->prepare(" AND status = %s", $status_filter);
        }

        $bookings = $wpdb->get_results("SELECT * FROM $table_name WHERE $where ORDER BY created_at DESC");

        // Get counts for status tabs
        $count_all = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $count_pending = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $count_pending_payment = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending_payment'");
        $count_confirmed = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'confirmed'");
        $count_cancelled = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'cancelled'");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Gestión de Reservas</h1>

            <!-- Botón Borrar todas las reservas -->
            <button
                id="wptb-delete-all-bookings-btn"
                class="page-title-action"
                style="background:#dc3232;color:#fff;border-color:#b22222;margin-left:10px;padding:5px 14px;font-weight:700;cursor:pointer;"
            >
                🗑️ Borrar TODAS las Reservas
            </button>
            <hr class="wp-header-end">

            <!-- Status Tabs -->
            <ul class="subsubsub">
                <li><a href="?page=wptb-bookings&status=all" class="<?php echo $status_filter === 'all' ? 'current' : ''; ?>">Todos <span class="count">(<?php echo $count_all; ?>)</span></a> |</li>
                <li><a href="?page=wptb-bookings&status=pending_payment" class="<?php echo $status_filter === 'pending_payment' ? 'current' : ''; ?>">Pago Pendiente <span class="count">(<?php echo $count_pending_payment; ?>)</span></a> |</li>
                <li><a href="?page=wptb-bookings&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">Pendiente <span class="count">(<?php echo $count_pending; ?>)</span></a> |</li>
                <li><a href="?page=wptb-bookings&status=confirmed" class="<?php echo $status_filter === 'confirmed' ? 'current' : ''; ?>">Confirmado <span class="count">(<?php echo $count_confirmed; ?>)</span></a> |</li>
                <li><a href="?page=wptb-bookings&status=cancelled" class="<?php echo $status_filter === 'cancelled' ? 'current' : ''; ?>">Cancelado <span class="count">(<?php echo $count_cancelled; ?>)</span></a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Ruta</th>
                        <th>Fecha/Hora</th>
                        <th>Vehículo</th>
                        <th>Distancia</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)) : ?>
                        <tr>
                            <td colspan="9" style="text-align:center;">No hay reservas registradas.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($bookings as $booking) : ?>
                            <tr>
                                <td>
                                    <?php echo $booking->id; ?>
                                    <?php if (!empty($booking->hotel_token)): ?>
                                        <br><span style="padding: 2px 5px; background: #0073aa; color: white; border-radius: 3px; font-size: 10px; white-space: nowrap;">Desde Hotel</span>
                                    <?php endif; ?>
                                    <?php if (isset($booking->source) && $booking->source === 'BTT'): ?>
                                        <br><span style="padding: 2px 5px; background: #e67e22; color: white; border-radius: 3px; font-size: 10px; white-space: nowrap;">BTT</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($booking->customer_name); ?></strong><br>
                                    <small><?php echo esc_html($booking->customer_email); ?></small><br>
                                    <small><?php echo esc_html($booking->customer_phone); ?></small>
                                </td>
                                <td>
                                    <strong>Desde:</strong> <?php echo esc_html($booking->origin); ?><br>
                                    <strong>Hasta:</strong> <?php echo esc_html($booking->destination); ?>
                                </td>
                                <td>
                                    <?php 
                                    $date = $booking->booking_date;
                                    if (strpos($date, ' ') !== false) {
                                        echo esc_html($date);
                                    } else {
                                        echo esc_html($date . ' ' . $booking->booking_time);
                                    }
                                    
                                    // ADDED: Vehicle Name below date
                                    $vehicle = WPTB_Vehicle_Manager::get_vehicle($booking->vehicle_id);
                                    if ($vehicle) {
                                        echo '<br><small style="color: #666;">' . esc_html($vehicle->name) . '</small>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    // Keeping this column or hiding it? User asked to show it below date.
                                    // I'll keep it for now but the request satisfied above.
                                    echo $vehicle ? esc_html($vehicle->name) : 'N/A';
                                    ?>
                                </td>
                                <td><?php echo number_format($booking->distance_km, 2); ?> km</td>
                                <td><strong>€<?php echo number_format($booking->price, 2); ?></strong></td>
                                <td>
                                    <?php echo $this->get_status_badge($booking->status); ?>
                                </td>
                                <td>
                                    <select class="wptb-status-select" data-booking-id="<?php echo $booking->id; ?>">
                                        <option value="pending_payment" <?php selected($booking->status, 'pending_payment'); ?>>Pago Pendiente</option>
                                        <option value="pending" <?php selected($booking->status, 'pending'); ?>>Pendiente</option>
                                        <option value="confirmed" <?php selected($booking->status, 'confirmed'); ?>>Confirmado</option>
                                        <option value="cancelled" <?php selected($booking->status, 'cancelled'); ?>>Cancelado</option>
                                        <option value="completed" <?php selected($booking->status, 'completed'); ?>>Completado</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
            .wptb-status-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            .wptb-status-pending { background: #f0ad4e; color: white; }
            .wptb-status-confirmed { background: #5cb85c; color: white; }
            .wptb-status-cancelled { background: #d9534f; color: white; }
            .wptb-status-completed { background: #5bc0de; color: white; }
            
            .wptb-status-select {
                padding: 5px;
                border-radius: 3px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // ── Cambio de estado individual ──
            $('.wptb-status-select').on('change', function() {
                const $select = $(this);
                const bookingId = $select.data('booking-id');
                const newStatus = $select.val();
                
                if (!confirm('¿Cambiar el estado de esta reserva?')) {
                    $select.val($select.data('original-status'));
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wptb_update_booking_status',
                        booking_id: bookingId,
                        status: newStatus,
                        nonce: '<?php echo wp_create_nonce('wptb_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error al actualizar el estado.');
                    }
                });
            });
            
            // Store original status
            $('.wptb-status-select').each(function() {
                $(this).data('original-status', $(this).val());
            });

            // ── Borrar TODAS las reservas ──
            $('#wptb-delete-all-bookings-btn').on('click', function() {
                // Primera confirmación
                if (!confirm('⚠️ ¿Estás SEGURO de que quieres borrar TODAS las reservas?\n\nEsta acción no se puede deshacer.')) {
                    return;
                }
                // Segunda confirmación: escribir la palabra clave
                const keyword = prompt('Para confirmar, escribe exactamente: BORRAR');
                if (keyword !== 'BORRAR') {
                    alert('Acción cancelada. No se ha borrado nada.');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).text('Borrando...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wptb_delete_all_bookings',
                        nonce: '<?php echo wp_create_nonce('wptb_delete_all_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ ' + response.data.message);
                            location.reload();
                        } else {
                            alert('❌ Error: ' + response.data.message);
                            $btn.prop('disabled', false).text('🗑️ Borrar TODAS las Reservas');
                        }
                    },
                    error: function() {
                        alert('❌ Error de conexión al intentar borrar.');
                        $btn.prop('disabled', false).text('🗑️ Borrar TODAS las Reservas');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get status badge HTML
     */
    private function get_status_badge($status) {
        $labels = array(
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            'completed' => 'Completado',
            'pending_payment' => 'Pago Pendiente'
        );
        
        $label = isset($labels[$status]) ? $labels[$status] : ucfirst($status);
        $class = 'wptb-status-badge wptb-status-' . $status;
        
        return '<span class="' . $class . '">' . $label . '</span>';
    }

    /**
     * AJAX: Update booking status
     */
    public function ajax_update_booking_status() {
        check_ajax_referer('wptb_admin_nonce', 'nonce');
        
        if ( ! current_user_can( \MeTransfers\Admin\Capabilities::MANAGE_BOOKINGS ) ) {
            wp_send_json_error(array('message' => 'No tienes permisos.'));
        }
        
        $booking_id = absint($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Validate status
        $allowed_statuses = array('pending', 'confirmed', 'cancelled', 'completed', 'pending_payment');
        if (!in_array($status, $allowed_statuses)) {
            wp_send_json_error(array('message' => 'Estado inválido.'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        
        $updated = $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            \MeTransfers\Admin\AuditLog::record( 'booking.status_updated', 'booking', $booking_id, array( 'status' => $status ) );
            wp_send_json_success(array('message' => 'Estado actualizado.'));
        } else {
            wp_send_json_error(array('message' => 'Error al actualizar.'));
        }
    }

    /**
     * AJAX: Delete ALL bookings and reset AUTO_INCREMENT to 10000
     */
    public function ajax_delete_all_bookings() {
        check_ajax_referer( 'wptb_delete_all_nonce', 'nonce' );

        if ( ! current_user_can( \MeTransfers\Admin\Capabilities::MANAGE_BOOKINGS ) ) {
            wp_send_json_error( array( 'message' => 'No tienes permisos para realizar esta acción.' ) );
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';

        // Contar reservas antes de borrar
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

        // Truncar la tabla (borra todo y resetea auto_increment a 1)
        $result = $wpdb->query( "TRUNCATE TABLE $table_name" );

        if ( $result === false ) {
            wp_send_json_error( array( 'message' => 'Error al borrar las reservas: ' . $wpdb->last_error ) );
            return;
        }

        // Establecer el AUTO_INCREMENT en 100000 para que el próximo ID sea 100000
        $wpdb->query( "ALTER TABLE $table_name AUTO_INCREMENT = 100000" );

        \MeTransfers\Admin\AuditLog::record( 'booking.all_deleted', 'booking', 0, array( 'count' => $total ) );

        wp_send_json_success( array(
            'message' => "Se han borrado $total reservas. El próximo ID de reserva será 100000."
        ) );
    }
}

// Initialize
new WPTB_Bookings_Admin();
