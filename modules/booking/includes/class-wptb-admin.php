<?php

class WPTB_Admin {

    public function __construct() {
        // Handle AJAX status updates
        add_action( 'wp_ajax_wptb_update_status', array( $this, 'update_booking_status' ) );
        add_action( 'wp_ajax_wptb_resend_email', array( $this, 'resend_booking_email' ) );
        add_action( 'admin_post_wptb_view_receipt', array( $this, 'view_booking_receipt' ) );
        
        // AUTO-MIGRATION: Ensure DB is up to date (v3.2)
        if ( ! get_option( 'wptb_db_version_3_2' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wptb-activator.php';
            WPTB_Activator::activate();
            update_option( 'wptb_db_version_3_2', true );
        }

        // Ensure Vehicle Selection page exists for the separated Step 2 flow.
        if ( ! get_option( 'wptb_vehicle_page_version_1' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wptb-activator.php';
            WPTB_Activator::activate();
            update_option( 'wptb_vehicle_page_version_1', true );
        }

        // Highlight Main Menu Item (Green Darker)
        add_action('admin_head', function() {
            echo '<style>
                #toplevel_page_wptb-reservas > a {
                    background-color: #1e7e34 !important;
                    color: #fff !important;
                    font-weight: bold !important;
                }
                #toplevel_page_wptb-reservas > a:hover {
                    background-color: #155724 !important;
                }
                #toplevel_page_wptb-reservas .wp-menu-image:before {
                    color: #fff !important;
                }
            </style>';
        });
    }

    public function add_plugin_admin_menu() {
        // Main Menu: Metransfers (shows Reservas by default)
        add_menu_page(
            'Metransfers', 
            'Metransfers', 
            'manage_options', 
            'wptb-reservas',  // Changed to point to reservas
            array( $this, 'display_bookings_page' ), 
            'dashicons-car', 
            26 
        );

        // Submenu 1: Reservas (rename the auto-created first submenu)
        add_submenu_page(
            'wptb-reservas',
            'Reservas',
            'Reservas',
            'manage_options',
            'wptb-reservas',  // Same slug as parent
            array( $this, 'display_bookings_page' )
        );

        // Submenu 2: Todos los Destinos
        add_submenu_page(
            'wptb-reservas',
            'Todos los Destinos',
            'Todos los Destinos',
            'manage_options',
            'edit.php?post_type=wptb_destination'  // Link to CPT
        );

        // Submenu 3: Vehículos (if exists)
        add_submenu_page(
            'wptb-reservas',
            'Vehículos',
            'Vehículos',
            'manage_options',
            'wptb-vehicles',
            array( $this, 'display_vehicles_page' )
        );

        // Submenu 4: Configuración
        add_submenu_page(
            'wptb-reservas',
            'Configuración',
            'Configuración',
            'manage_options',
            'wptb-settings',
            array( $this, 'display_settings_page' )
        );

        // Submenu 5: Progreso
        add_submenu_page(
            'wptb-reservas',
            'Progreso',
            'Progreso',
            'manage_options',
            'wptb-stats',
            array( $this, 'display_stats_page' )
        );
    }

    public function register_settings() {
        register_setting( 'wptb_settings_group', 'wptb_google_maps_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wptb_settings_group', 'wptb_stripe_publishable_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wptb_settings_group', 'wptb_stripe_secret_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wptb_settings_group', 'wptb_stripe_mode', array( 'sanitize_callback' => 'sanitize_text_field' ) ); // 'test' or 'live'
        
        // Notifications
        register_setting( 'wptb_settings_group', 'wptb_admin_email_notifications', array( 'sanitize_callback' => 'sanitize_email' ) );
        register_setting( 'wptb_settings_group', 'wptb_admin_phone_notifications', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wptb_settings_group', 'wptb_whatsapp_apikey', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        // Removed: price_per_km and base_price - now configured per vehicle
    }

    public function update_booking_status() {
        check_ajax_referer( 'wptb_status_nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'No tienes permisos.' );
        }

        $id = intval( $_POST['id'] );
        $status = sanitize_text_field( $_POST['status'] );

        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';

        $updated = $wpdb->update( 
            $table_name, 
            array( 'status' => $status ), 
            array( 'id' => $id ), 
            array( '%s' ), 
            array( '%d' ) 
        );

        if ( $updated !== false ) {
            wp_send_json_success( 'Estado actualizado.' );
        } else {
            wp_send_json_error( 'Error al actualizar.' );
        }
    }

    public function resend_booking_email() {
        check_ajax_referer( 'wptb_status_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sin permisos.' );
        }

        $id = intval( $_POST['id'] );
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );

        if ( ! $booking ) {
            wp_send_json_error( 'Reserva no encontrada.' );
        }

        try {
            // Instantiate public class to use email method
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wptb-public.php';
            $public = new WPTB_Public();
            $result = $public->process_booking_notifications( $id, $booking ); // Returns true OR error string
            // WhatsApp is already called inside process_booking_notifications
            
            if ( $result === true ) {
                wp_send_json_success( 'Notificación enviada (Email + WhatsApp).' );
            } else {
                // Return exact error from SMTP
                $error_msg = is_string($result) ? $result : 'Error desconocido';
                wp_send_json_error( "SMTP Error: $error_msg" );
            }
        } catch ( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    public function view_booking_receipt() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sin permisos.' );
        }

        $id = intval( $_GET['id'] );
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );

        if ( ! $booking ) wp_die( 'Reserva no encontrada.' );

        // Render HTML Receipt
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Recibo Reserva #<?php echo $id; ?></title>
            <meta charset="utf-8">
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
                body { 
                    font-family: 'Inter', Helvetica, Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    background: #f4f7f6; 
                    padding: 40px 20px; 
                    margin: 0;
                }
                .receipt { 
                    max-width: 750px; 
                    margin: 0 auto; 
                    background: white; 
                    padding: 50px; 
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
                }
                .header { 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: flex-start; 
                    border-bottom: 2px solid #f0f0f0; 
                    padding-bottom: 30px; 
                    margin-bottom: 40px; 
                }
                .logo-container img {
                    max-height: 60px;
                    width: auto;
                }
                .logo-container h2 {
                    margin: 0;
                    color: #001f3f;
                    font-size: 28px;
                    font-weight: 700;
                    letter-spacing: -0.5px;
                }
                .invoice-details {
                    text-align: right;
                }
                .invoice-details h1 { 
                    margin: 0 0 5px 0; 
                    color: #001f3f; 
                    font-size: 24px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .status-badge {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 4px;
                    font-size: 13px;
                    font-weight: 700;
                    text-transform: uppercase;
                    margin-bottom: 10px;
                }
                .status-confirmed, .status-completed { background: #d4edda; color: #155724; }
                .status-pending_payment, .status-processing { background: #fff3cd; color: #856404; }
                .status-cancelled { background: #f8d7da; color: #721c24; }
                .meta { color: #666; font-size: 15px; margin-bottom: 5px; }
                .meta strong { color: #333; }
                
                .section-title {
                    font-size: 16px;
                    text-transform: uppercase;
                    color: #001f3f;
                    font-weight: 700;
                    margin-top: 0;
                    margin-bottom: 20px;
                    letter-spacing: 1px;
                    border-left: 4px solid #006597;
                    padding-left: 10px;
                }

                .grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin-bottom: 40px;
                }

                .info-box {
                    background: #f8fafc;
                    padding: 20px;
                    border-radius: 8px;
                    border: 1px solid #edf2f7;
                }

                .row { 
                    display: flex; 
                    margin-bottom: 12px; 
                    font-size: 15px;
                }
                .row:last-child {
                    margin-bottom: 0;
                }
                .label { 
                    width: 45%; 
                    color: #64748b; 
                    font-weight: 600; 
                }
                .value { 
                    width: 55%; 
                    color: #0f172a; 
                    font-weight: 600; 
                }
                
                .trip-details {
                    margin-bottom: 40px;
                }

                .trip-card {
                    background: #fff;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    overflow: hidden;
                    margin-bottom: 20px;
                }
                .trip-card-header {
                    background: #f1f5f9;
                    padding: 12px 20px;
                    font-weight: 700;
                    color: #0f172a;
                    border-bottom: 1px solid #e2e8f0;
                    display: flex;
                    justify-content: space-between;
                }
                .trip-card-body {
                    padding: 20px;
                }
                
                .total-section { 
                    margin-top: 30px; 
                    border-top: 2px solid #e2e8f0; 
                    padding-top: 20px; 
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .total-label {
                    font-size: 18px;
                    font-weight: 700;
                    color: #64748b;
                }
                .total-amount {
                    font-size: 32px; 
                    font-weight: 700; 
                    color: #001f3f; 
                }
                .actions { 
                    margin-top: 50px; 
                    text-align: center; 
                }
                .btn { 
                    background: #001f3f; 
                    color: white; 
                    padding: 12px 25px; 
                    text-decoration: none; 
                    border-radius: 6px; 
                    cursor: pointer; 
                    border: none; 
                    font-size: 16px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    display: inline-block;
                }
                .btn:hover {
                    background: #003366;
                    transform: translateY(-2px);
                }
                .btn-secondary {
                    background: #64748b;
                    margin-left: 15px;
                }
                .btn-secondary:hover {
                    background: #475569;
                }
                
                @media print {
                    @page { margin: 0; size: auto; }
                    body { background: white; padding: 20px; margin: 0; }
                    .receipt { box-shadow: none; max-width: 100%; padding: 0; border: none; }
                    .actions { display: none; }
                    .info-box, .trip-card-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="header">
                    <div class="logo-container">
                        <img src="https://metransfers.es/wp-content/uploads/2026/07/MeTransfers-exp-scaled.webp" alt="Metransfers">
                    </div>
                    <div class="invoice-details">
                        <h1>Recibo de Reserva</h1>
                        <?php 
                        $status_class = 'status-' . esc_attr($booking->status);
                        $status_label = strtoupper($booking->status);
                        $status_map = array(
                            'confirmed' => 'Confirmado',
                            'pending_payment' => 'Por Confirmar / Pago Pendiente',
                            'cancelled' => 'Cancelado',
                            'completed' => 'Completado',
                            'processing' => 'En Proceso'
                        );
                        if (array_key_exists($booking->status, $status_map)) {
                            $status_label = $status_map[$booking->status];
                        }
                        ?>
                        <div class="status-badge <?php echo $status_class; ?>"><?php echo esc_html($status_label); ?></div>
                        <div class="meta"><strong>Nº Ref:</strong> #<?php echo $id; ?></div>
                        <div class="meta"><strong>Fecha de Registro:</strong> <?php echo date('d/m/Y H:i', strtotime($booking->created_at)); ?></div>
                    </div>
                </div>

                <div class="grid">
                    <div class="info-box">
                        <h3 class="section-title">Datos del Cliente</h3>
                        <div class="row"><div class="label">Nombre:</div><div class="value"><?php echo esc_html($booking->customer_name); ?></div></div>
                        <div class="row"><div class="label">Email:</div><div class="value"><?php echo esc_html($booking->customer_email); ?></div></div>
                        <div class="row"><div class="label">Teléfono:</div><div class="value"><?php echo esc_html($booking->customer_phone); ?></div></div>
                        <?php if ( !empty($booking->hotel_token) ): ?>
                        <div class="row"><div class="label">Hotel Token:</div><div class="value" style="color:#004B68;">🏨 <?php echo esc_html($booking->hotel_token); ?></div></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-box">
                        <h3 class="section-title">Detalles del Vehículo</h3>
                        <?php 
                            if(!class_exists('WPTB_Vehicle_Manager')) require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wptb-vehicle-manager.php';
                            $vehicle_obj = WPTB_Vehicle_Manager::get_vehicle( $booking->vehicle_id );
                            $vehicle_name = $vehicle_obj ? $vehicle_obj->name : 'ID: ' . $booking->vehicle_id;
                        ?>
                        <div class="row"><div class="label">Vehículo:</div><div class="value"><?php echo esc_html($vehicle_name); ?></div></div>
                        <div class="row"><div class="label">Pasajeros:</div><div class="value"><?php echo esc_html($booking->passengers); ?> pax</div></div>
                        <div class="row"><div class="label">Equipaje:</div><div class="value"><?php echo esc_html($booking->suitcases); ?> Maletas, <?php echo esc_html($booking->carry_ons); ?> Mochilas</div></div>
                        <?php if($booking->flight_number): ?>
                            <div class="row"><div class="label">Nº Vuelo:</div><div class="value"><?php echo esc_html($booking->flight_number); ?></div></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="trip-details">
                    <h3 class="section-title">Información del Traslado</h3>
                    
                    <div class="trip-card">
                        <div class="trip-card-header">
                            <span>Traslado Principal <?php echo $booking->trip_type === 'round_trip' ? '(Ida)' : ''; ?></span>
                            <span><?php echo date('d/m/Y', strtotime($booking->booking_date)); ?> a las <?php echo date('H:i', strtotime($booking->booking_time)); ?></span>
                        </div>
                        <div class="trip-card-body">
                            <div class="row"><div class="label">Recogida:</div><div class="value"><?php echo esc_html($booking->origin); ?></div></div>
                            <div class="row"><div class="label">Destino:</div><div class="value"><?php echo esc_html($booking->destination); ?></div></div>
                            <div class="row"><div class="label">Distancia Aprox:</div><div class="value"><?php echo esc_html($booking->distance_km); ?> km</div></div>
                        </div>
                    </div>
                    
                    <?php if($booking->trip_type === 'round_trip'): ?>
                    <div class="trip-card">
                        <div class="trip-card-header">
                            <span>Traslado de Vuelta</span>
                            <span><?php echo date('d/m/Y', strtotime($booking->return_date)); ?> a las <?php echo date('H:i', strtotime($booking->return_time)); ?></span>
                        </div>
                        <div class="trip-card-body">
                            <div class="row"><div class="label">Recogida:</div><div class="value"><?php echo esc_html($booking->return_pickup_address); ?></div></div>
                            <div class="row"><div class="label">Destino:</div><div class="value"><?php echo esc_html($booking->return_dropoff_address); ?></div></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($booking->notes): ?>
                    <div class="info-box" style="margin-top:20px;">
                        <strong style="color:#001f3f;">Notas Adicionales:</strong><br>
                        <span style="color:#333; font-style:italic;"><?php echo nl2br(esc_html($booking->notes)); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="total-section">
                    <div class="total-label">Total a Pagar</div>
                    <div class="total-amount">€<?php echo number_format($booking->price, 2); ?></div>
                </div>

                <div class="actions">
                    <button class="btn" onclick="window.print()">
                        <svg style="width:16px; height:16px; vertical-align:middle; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Imprimir / Guardar PDF
                    </button>
                    <button class="btn btn-secondary" onclick="window.close()">Cerrar</button>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    public function display_bookings_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';
        $bookings = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Reservas de Traslados</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Fecha/Hora</th>
                        <th>Ruta</th>
                        <th>Distancia</th>
                        <th>Precio</th>
                        <th>Cliente</th>
                        <th>Detalles</th>
                        <th>Información de Pago</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $bookings ) ) : ?>
                        <?php foreach ( $bookings as $booking ) : ?>
                            <?php 
                                // Status Logic
                                $status_class = 'secondary';
                                if(in_array($booking->status, ['confirmed','completed','processing'])) $status_class = 'confirmed';
                                elseif(in_array($booking->status, ['added-to-cart','pending','on-hold'])) $status_class = 'pending';
                                elseif(in_array($booking->status, ['cancelled','failed'])) $status_class = 'cancelled';
                            ?>
                            <tr>
                                <td>#<?php echo esc_html( $booking->id ); ?></td>
                                <td><?php echo esc_html( $booking->booking_date ); ?></td>
                                <td>
                                    <strong>De:</strong> <?php echo esc_html( $booking->origin ); ?><br>
                                    <strong>A:</strong> <?php echo esc_html( $booking->destination ); ?>
                                    <?php if($booking->trip_type === 'round_trip' && !empty($booking->return_pickup_address)): ?>
                                        <hr style="margin:5px 0; border:0; border-top:1px dashed #ccc;">
                                        <strong>Vuelta De:</strong> <?php echo esc_html( $booking->return_pickup_address ); ?><br>
                                        <strong>A:</strong> <?php echo esc_html( $booking->return_dropoff_address ); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $is_round_trip = ($booking->trip_type === 'round_trip');
                                        $display_dist = $is_round_trip ? (floatval($booking->distance_km) * 2) : floatval($booking->distance_km);
                                        $label = $is_round_trip ? 'Ida y Vuelta' : 'Solo Ida';
                                        $badge_class = $is_round_trip ? 'wptb-badge-round' : 'wptb-badge-oneway';
                                    ?>
                                    <strong><?php echo number_format($display_dist, 1); ?> km</strong><br>
                                    <span class="<?php echo $badge_class; ?>"><?php echo $label; ?></span>
                                    <?php if($is_round_trip && !empty($booking->return_date)): ?>
                                        <div style="margin-top:4px; font-size:11px; color:#666;">
                                            Retorno: <?php echo $booking->return_date . ' ' . $booking->return_time; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>€<?php echo esc_html( $booking->price ); ?></td>
                                <td>
                                    <strong><?php echo esc_html( $booking->customer_name ); ?></strong><br>
                                    <a href="mailto:<?php echo esc_attr( $booking->customer_email ); ?>"><?php echo esc_html( $booking->customer_email ); ?></a><br>
                                    <a href="tel:<?php echo esc_attr( $booking->customer_phone ); ?>"><?php echo esc_html( $booking->customer_phone ); ?></a>
                                    <?php if ( !empty($booking->hotel_token) ) { echo '<br><small style="color:#004B68; font-weight:bold;">🏨 Hotel Token: ' . esc_html( $booking->hotel_token ) . '</small>'; } ?>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-groups"></span> <?php echo esc_html( $booking->passengers ); ?><br>
                                    <span class="dashicons dashicons-portfolio"></span> <?php echo esc_html( $booking->suitcases + $booking->carry_ons ); ?><br>
                                    <?php if(!empty($booking->flight_number)) echo '<span class="dashicons dashicons-airplane"></span> ' . esc_html( $booking->flight_number ); ?>
                                </td>
                                <td>
                                    <?php 
                                        $method = $booking->payment_method ? ucfirst($booking->payment_method) : '-';
                                        if (strtolower($method) === 'stripe') {
                                            $method = '<strong>💳 Stripe</strong>';
                                        }
                                        echo $method . '<br>';
                                        
                                        if ( $booking->payment_intent_id ) {
                                            $id_label = (strtolower($booking->payment_method) === 'redsys') ? 'Transfer' : 'ID:';
                                            echo '<small style="color:#777; font-family:monospace;">' . $id_label . ' ' . esc_html( $booking->payment_intent_id ) . '</small>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <select class="wptb-status-select wptb-badge <?php echo $status_class; ?>" data-id="<?php echo $booking->id; ?>">
                                        <option value="pending" <?php selected( in_array($booking->status, ['pending','added-to-cart','on-hold']) ); ?>>Por confirmar</option>
                                        <option value="confirmed" <?php selected( in_array($booking->status, ['confirmed','completed','processing']) ); ?>>Confirmado</option>
                                        <option value="cancelled" <?php selected( in_array($booking->status, ['cancelled','failed']) ); ?>>Cancelado</option>
                                    </select>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:5px;">
                                        <a href="<?php echo admin_url('admin-post.php?action=wptb_view_receipt&id='.$booking->id); ?>" target="_blank" class="button button-small" title="Ver Recibo / PDF">
                                            <span class="dashicons dashicons-media-document"></span> Recibo
                                        </a>
                                        <button type="button" class="button button-small wptb-resend-email" data-id="<?php echo $booking->id; ?>" title="Reenviar Notificación">
                                            <span class="dashicons dashicons-email-alt" style="margin-right:4px;"></span> Enviar Notificación
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="9">No hay reservas registradas aún.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.wptb-status-select').on('change', function() {
                var $select = $(this);
                var id = $select.data('id');
                var status = $select.val();
                var originalColor = $select.attr('class'); // Backup class

                // 1. Visual Feedback: Disable & Loading
                $select.prop('disabled', true).css('opacity', '0.6');
                
                // Update Color Class immediately for responsiveness
                $select.removeClass('confirmed pending cancelled secondary');
                if(status === 'confirmed') $select.addClass('confirmed');
                else if(status === 'pending') $select.addClass('pending');
                else if(status === 'cancelled') $select.addClass('cancelled');

                // AJAX
                $.post(ajaxurl, {
                    action: 'wptb_update_status',
                    id: id,
                    status: status,
                    security: '<?php echo wp_create_nonce("wptb_status_nonce"); ?>'
                }, function(response) {
                    // Re-enable
                    $select.prop('disabled', false).css('opacity', '1');

                    if(response.success) {
                        // Success flash
                        $select.fadeOut(100).fadeIn(100);
                    } else {
                        alert('Error: ' + (response.data || 'No se pudo guardar'));
                        // Revert visual change if error
                        $select.attr('class', originalColor);
                    }
                }).fail(function() {
                    $select.prop('disabled', false).css('opacity', '1');
                    alert('Error de conexión');
                    $select.attr('class', originalColor);
                }); // Ends the .post().fail() chain

            });

            // Resend Email Handler
            $('.wptb-resend-email').on('click', function(e) {
                e.preventDefault();
                if(!confirm('¿Estás seguro de que quieres reenviar los correos de confirmación?')) return;
                
                var $btn = $(this);
                $btn.prop('disabled', true).css('opacity', '0.6');
                
                $.post(ajaxurl, {
                    action: 'wptb_resend_email',
                    id: $btn.data('id'),
                    security: '<?php echo wp_create_nonce("wptb_status_nonce"); ?>'
                }, function(response) {
                    $btn.prop('disabled', false).css('opacity', '1');
                    if(response.success) {
                        alert('✅ Notificación enviada correctamente.');
                    } else {
                        alert('❌ Error: ' + (response.data || 'Error desconocido'));
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).css('opacity', '1');
                    alert('Error de conexión');
                });
            });
        });
        </script>
        <style>
            .button.wptb-resend-email, .button.button-small {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                width: 100%;
                margin-top: 5px;
            }
            .wptb-badge {
                padding: 4px 8px;
                border-radius: 4px;
                color: #fff;
                font-weight: 600;
                font-size: 10px;
                text-transform: uppercase;
                display: inline-block;
                width: auto;
                min-width: 100px;
            }
            .wptb-badge.confirmed { background-color: #28a745; } /* Green */
            .wptb-badge.pending { background-color: #ffc107; color: #333; } /* Yellow */
            .wptb-badge.cancelled { background-color: #dc3545; } /* Red */
            
            .wptb-badge-round {
                background-color: #6f42c1;
                color: white;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
                display: inline-block;
                margin-top: 4px;
            }
            .wptb-badge-oneway {
                background-color: #17a2b8;
                color: white;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
                display: inline-block;
                margin-top: 4px;
            }

            /* Update Select Styles */
            select.wptb-status-select {
                appearance: none;
                -webkit-appearance: none;
                border: none;
                cursor: pointer;
                text-align: center;
                width: 100%;
            }
            select.wptb-status-select:focus {
                outline: none;
                box-shadow: 0 0 0 2px rgba(0,0,0,0.2);
            }
        </style>
        <?php
    }

    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1>Configuración del Plugin de Reservas</h1>
            
            <div class="notice notice-info" style="margin: 20px 0;">
                <p><strong>💡 Nota:</strong> Los precios ahora se configuran individualmente por vehículo. 
                Ve a <a href="?page=wptb-vehicles">Metransfers → Vehículos</a> para gestionar precios.</p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'wptb_settings_group' ); ?>
                <?php do_settings_sections( 'wptb_settings_group' ); ?>
                
                <h2 style="margin-top: 30px;">🗺️ Google Maps</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Google Maps API Key</th>
                        <td>
                            <input type="text" name="wptb_google_maps_api_key" 
                                   value="<?php echo esc_attr( get_option('wptb_google_maps_api_key', '') ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                Usa tu propia API key con facturación activa. Habilita al menos: Maps JavaScript API, Places API y Directions API.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2 style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">💳 Stripe Payment Gateway</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Modo de Stripe</th>
                        <td>
                            <select name="wptb_stripe_mode">
                                <option value="test" <?php selected( get_option('wptb_stripe_mode', 'test'), 'test' ); ?>>Modo Test (Pruebas)</option>
                                <option value="live" <?php selected( get_option('wptb_stripe_mode', 'test'), 'live' ); ?>>Modo Live (Producción)</option>
                            </select>
                            <p class="description">
                                Usa "Test" para pruebas y "Live" cuando estés listo para recibir pagos reales.
                            </p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Publishable Key (Clave Pública)</th>
                        <td>
                            <input type="text" name="wptb_stripe_publishable_key" 
                                   value="<?php echo esc_attr( get_option('wptb_stripe_publishable_key', 'pk_test_51SdJRHE1acnhagHqAbtCkNnHWdNMj5I8DmTliLsHZvtVKfD2BmHhk9GLF1Im81VrKaM2VjlbiuoV21F4bWDB9Vam00VTHrYMqx') ); ?>" 
                                   class="regular-text" 
                                   placeholder="pk_test_..." />
                            <p class="description">
                                Tu clave pública de Stripe (comienza con <code>pk_test_</code> o <code>pk_live_</code>)
                            </p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Secret Key (Clave Secreta)</th>
                        <td>
                            <input type="password" name="wptb_stripe_secret_key" 
                                   value="<?php echo esc_attr( get_option('wptb_stripe_secret_key', 'sk_test_YOUR_TEST_SECRET_KEY') ); ?>" 
                                   class="regular-text" 
                                   placeholder="sk_test_..." />
                            <p class="description">
                                Tu clave secreta de Stripe (comienza con <code>sk_test_</code> o <code>sk_live_</code>). <strong>¡Nunca la compartas!</strong>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2 style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">🔔 Notificaciones (Admin)</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Email de Notificaciones</th>
                        <td>
                            <input type="email" name="wptb_admin_email_notifications" 
                                   value="<?php echo esc_attr( get_option('wptb_admin_email_notifications', 'info@metransfers.es') ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                Dirección de correo donde llegarán los avisos de nuevas reservas.
                            </p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Teléfono WhatsApp</th>
                        <td>
                            <input type="text" name="wptb_admin_phone_notifications" 
                                   value="<?php echo esc_attr( get_option('wptb_admin_phone_notifications', '+34 640 80 84 78') ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                Número internacional (con prefijo, ej. +34...) para alertas de WhatsApp.
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">WhatsApp API Key (CallMeBot)</th>
                        <td>
                            <input type="text" name="wptb_whatsapp_apikey" 
                                   value="<?php echo esc_attr( get_option('wptb_whatsapp_apikey', '') ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                API Key de <a href="https://www.callmebot.com/" target="_blank">CallMeBot</a> para recibir mensajes gratis.
                                <br>Se usa para enviar alertas al número configurado arriba.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Guardar Cambios'); ?>
            </form>
        </div>
        <?php
    }

    public function display_stats_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_bookings';

        // Filters
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01'); // Default this month
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

        // Prepare Queries
        // Note: DATE() function usage means we can't just pass strings directly to column placeholder usually, 
        // but prepare supports string replacement safely.
        
        $total_trips = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(booking_date) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        $total_distance = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(distance_km) FROM $table_name WHERE DATE(booking_date) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        $total_revenue = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(price) FROM $table_name WHERE DATE(booking_date) BETWEEN %s AND %s AND status IN ('confirmed','completed','processing')",
            $start_date,
            $end_date
        ));
        
        if(!$total_distance) $total_distance = 0;
        if(!$total_revenue) $total_revenue = 0;

        ?>
        <div class="wrap">
            <h1>Progreso y Estadísticas</h1>
            
            <form method="get" style="margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; gap: 20px; align-items: flex-end;">
                <input type="hidden" name="page" value="wptb-stats">
                <div>
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Desde:</label>
                    <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                </div>
                <div>
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Hasta:</label>
                    <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                </div>
                <div>
                    <button type="submit" class="button button-primary">Filtrar</button>
                </div>
            </form>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <!-- Card 1: Viajes -->
                <div class="wptb-stat-card" style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <h3 style="margin: 0 0 10px; color: #666; font-size: 14px; text-transform: uppercase;">Total Viajes</h3>
                    <div style="font-size: 32px; font-weight: 700; color: #333;">
                        <?php echo number_format($total_trips); ?>
                    </div>
                </div>

                <!-- Card 2: Distancia -->
                <div class="wptb-stat-card" style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <h3 style="margin: 0 0 10px; color: #666; font-size: 14px; text-transform: uppercase;">Distancia Recorrida</h3>
                    <div style="font-size: 32px; font-weight: 700; color: #007cba;">
                        <?php echo number_format($total_distance, 1); ?> <span style="font-size: 16px; color: #999;">km</span>
                    </div>
                </div>

                <!-- Card 3: Ingresos -->
                <div class="wptb-stat-card" style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <h3 style="margin: 0 0 10px; color: #666; font-size: 14px; text-transform: uppercase;">Dinero Generado</h3>
                    <div style="font-size: 32px; font-weight: 700; color: #28a745;">
                        <?php echo number_format($total_revenue, 2); ?> <span style="font-size: 16px; color: #999;">€</span>
                    </div>
                    <small style="color: #999;">*Solo reservas confirmadas</small>
                </div>
            </div>
        </div>
        <?php
    }
}
