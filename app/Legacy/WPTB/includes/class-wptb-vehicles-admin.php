<?php
/**
 * Vehicles Admin Panel
 * Manages vehicle CRUD operations in WordPress admin
 */

class WPTB_Vehicles_Admin {

    public function __construct() {
        // AJAX handlers
        add_action( 'wp_ajax_wptb_save_vehicle', array( $this, 'ajax_save_vehicle' ) );
        add_action( 'wp_ajax_wptb_delete_vehicle', array( $this, 'ajax_delete_vehicle' ) );
        add_action( 'wp_ajax_wptb_upload_vehicle_image', array( $this, 'ajax_upload_image' ) );
        add_action( 'wp_ajax_wptb_delete_vehicle_image', array( $this, 'ajax_delete_image' ) );
    }

    /**
     * Add vehicles submenu
     */
    public function add_vehicles_menu() {
        add_submenu_page(
            'wptb-bookings',
            'Vehículos',
            'Vehículos',
            'manage_options',
            'wptb-vehicles',
            array( $this, 'display_vehicles_page' )
        );
    }

    /**
     * Display vehicles page
     */
    public function display_vehicles_page() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
        $vehicle_id = isset( $_GET['vehicle_id'] ) ? absint( $_GET['vehicle_id'] ) : 0;

        if ( $action === 'edit' || $action === 'add' ) {
            $this->display_vehicle_form( $vehicle_id );
        } else {
            $this->display_vehicles_list();
        }
    }

    /**
     * Display vehicles list
     */
    private function display_vehicles_list() {
        $vehicles = WPTB_Vehicle_Manager::get_active_vehicles( array( 'capacity' => 0 ) );
        
        // Also get inactive vehicles
        global $wpdb;
        $table_vehicles = $wpdb->prefix . 'wptb_vehicles';
        $table_types = $wpdb->prefix . 'wptb_vehicle_types';
        $all_vehicles = $wpdb->get_results(
            "SELECT v.*, t.name as type_name 
             FROM $table_vehicles v
             LEFT JOIN $table_types t ON v.vehicle_type_id = t.id
             ORDER BY v.display_order ASC, v.id DESC"
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Gestión de Vehículos</h1>
            <a href="?page=wptb-vehicles&action=add" class="page-title-action">Añadir Nuevo</a>
            <hr class="wp-header-end">

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="60">Imagen</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Capacidad</th>
                        <th>Precios</th>
                        <th>Disponibilidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $all_vehicles ) ) : ?>
                        <?php foreach ( $all_vehicles as $vehicle ) : ?>
                            <?php $primary_image = WPTB_Vehicle_Manager::get_primary_image( $vehicle->id ); ?>
                            <tr>
                                <td>
                                    <img src="<?php echo esc_url( $primary_image ); ?>" 
                                         style="width:50px;height:50px;object-fit:cover;border-radius:4px;" 
                                         alt="<?php echo esc_attr( $vehicle->name ); ?>">
                                </td>
                                <td><strong><?php echo esc_html( $vehicle->name ); ?></strong></td>
                                <td><?php echo esc_html( $vehicle->type_name ); ?></td>
                                <td><?php echo esc_html( $vehicle->capacity ); ?> pasajeros</td>
                                <td>
                                    <small>
                                        Ida: €<?php echo number_format( $vehicle->price_per_km_oneway, 2 ); ?>/km<br>
                                        I/V: €<?php echo number_format( $vehicle->price_per_km_roundtrip, 2 ); ?>/km<br>
                                        Hora: €<?php echo number_format( $vehicle->price_per_hour, 2 ); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ( $vehicle->is_active ) : ?>
                                        <span class="wptb-badge" style="background:#28a745;margin-bottom:5px;display:inline-block;">Activo</span>
                                    <?php else : ?>
                                        <span class="wptb-badge" style="background:#dc3545;margin-bottom:5px;display:inline-block;">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?page=wptb-vehicles&action=edit&vehicle_id=<?php echo $vehicle->id; ?>" class="button button-small">Editar</a>
                                    <button class="button button-small wptb-delete-vehicle" data-id="<?php echo $vehicle->id; ?>" data-name="<?php echo esc_attr( $vehicle->name ); ?>">Eliminar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7">No hay vehículos registrados. <a href="?page=wptb-vehicles&action=add">Añadir uno ahora</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.wptb-delete-vehicle').on('click', function() {
                if (!confirm('¿Estás seguro de eliminar el vehículo "' + $(this).data('name') + '"?')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('Eliminando...');
                
                $.post(ajaxurl, {
                    action: 'wptb_delete_vehicle',
                    vehicle_id: $(this).data('id'),
                    nonce: '<?php echo wp_create_nonce( 'wptb_vehicle_nonce' ); ?>'
                }, function(response) {
                    if (response.success) {
                        $btn.closest('tr').fadeOut(300, function() { $(this).remove(); });
                    } else {
                        alert('Error: ' + (response.data || 'No se pudo eliminar'));
                        $btn.prop('disabled', false).text('Eliminar');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Display vehicle form
     */
    private function display_vehicle_form( $vehicle_id ) {
        $vehicle = $vehicle_id ? WPTB_Vehicle_Manager::get_vehicle( $vehicle_id ) : null;
        $types = WPTB_Vehicle_Manager::get_vehicle_types();
        $images = $vehicle_id ? WPTB_Vehicle_Manager::get_vehicle_images( $vehicle_id ) : array();
        
        $is_edit = (bool) $vehicle;
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? 'Editar Vehículo' : 'Añadir Nuevo Vehículo'; ?></h1>
            <a href="?page=wptb-vehicles" class="page-title-action">← Volver a la lista</a>
            <hr>

            <form id="wptb-vehicle-form" method="post" style="max-width: 900px;">
                <input type="hidden" name="id" value="<?php echo $vehicle_id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="name">Nombre del Vehículo *</label></th>
                        <td>
                            <input type="text" id="name" name="name" class="regular-text" 
                                   value="<?php echo $vehicle ? esc_attr( $vehicle->name ) : ''; ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="vehicle_type_id">Tipo de Vehículo *</label></th>
                        <td>
                            <select id="vehicle_type_id" name="vehicle_type_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ( $types as $type ) : ?>
                                    <option value="<?php echo $type->id; ?>" 
                                            <?php selected( $vehicle && $vehicle->vehicle_type_id == $type->id ); ?>>
                                        <?php echo esc_html( $type->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="description">Descripción</label></th>
                        <td>
                            <textarea id="description" name="description" rows="4" class="large-text"><?php echo $vehicle ? esc_textarea( $vehicle->description ) : ''; ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="capacity">Capacidad (Pasajeros) *</label></th>
                        <td>
                            <input type="number" id="capacity" name="capacity" min="1" max="50" 
                                   value="<?php echo $vehicle ? $vehicle->capacity : 4; ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="luggage_capacity">Capacidad de Equipaje</label></th>
                        <td>
                            <input type="number" id="luggage_capacity" name="luggage_capacity" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->luggage_capacity : 2; ?>">
                        </td>
                    </tr>
                </table>

                <h2>Configuración de Precios</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="min_transfer_price">Precio Mínimo Traslado (€)</label></th>
                        <td>
                            <input type="number" id="min_transfer_price" name="min_transfer_price" step="0.01" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->min_transfer_price : '0'; ?>">
                            <p class="description">Precio mínimo general para cualquier tipo de traslado</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="initial_fee">Bajada de Bandera / Fee Inicial (€)</label></th>
                        <td>
                            <input type="number" id="initial_fee" name="initial_fee" step="0.01" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->initial_fee : '0'; ?>">
                            <p class="description">Se suma al precio total independientemente de la distancia (Ej. Bajada de bandera)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="min_oneway_price">Precio Mínimo Solo Ida (€)</label></th>
                        <td>
                            <input type="number" id="min_oneway_price" name="min_oneway_price" step="0.01" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->min_oneway_price : '0'; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="min_roundtrip_price">Precio Mínimo Ida y Vuelta (€)</label></th>
                        <td>
                            <input type="number" id="min_roundtrip_price" name="min_roundtrip_price" step="0.01" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->min_roundtrip_price : '0'; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="price_per_km_oneway">Precio por km (Solo Ida) €</label></th>
                        <td>
                            <input type="number" id="price_per_km_oneway" name="price_per_km_oneway" step="0.01" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->price_per_km_oneway : '1.50'; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="price_per_km_roundtrip">Precio por km (Ida y Vuelta) €</label></th>
                        <td>
                            <input type="number" id="price_per_km_roundtrip" name="price_per_km_roundtrip" step="0.01" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->price_per_km_roundtrip : '2.50'; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="price_per_hour">Precio por Hora (€)</label></th>
                        <td>
                            <input type="number" id="price_per_hour" name="price_per_hour" step="0.01" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->price_per_hour : '0'; ?>">
                            <p class="description">Dejar en 0 si no se usa tarifa por hora</p>
                        </td>
                    </tr>
                </table>

                <h2>Configuración Adicional</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="is_active">Vehículo Activo</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked( ! $vehicle || $vehicle->is_active ); ?>>
                                Marcar para que este vehículo esté disponible para reservas
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="display_order">Orden de Visualización</label></th>
                        <td>
                            <input type="number" id="display_order" name="display_order" min="0" 
                                   value="<?php echo $vehicle ? $vehicle->display_order : 0; ?>">
                            <p class="description">Menor número aparece primero</p>
                        </td>
                    </tr>
                </table>

                <?php if ( $is_edit ) : ?>
                    <h2>Imágenes del Vehículo</h2>
                    <div id="vehicle-images-container" style="margin-bottom: 20px;">
                        <div id="images-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:15px;margin-bottom:15px;">
                            <?php foreach ( $images as $image ) : ?>
                                <div class="vehicle-image-item" data-id="<?php echo $image->id; ?>" style="position:relative;">
                                    <img src="<?php echo esc_url( $image->image_url ); ?>" style="width:100%;height:120px;object-fit:cover;border-radius:8px;border:2px solid <?php echo $image->is_primary ? '#006597' : '#ddd'; ?>;">
                                    <?php if ( $image->is_primary ) : ?>
                                        <span style="position:absolute;top:5px;left:5px;background:#006597;color:#fff;padding:2px 8px;font-size:10px;border-radius:4px;">Principal</span>
                                    <?php endif; ?>
                                    <button type="button" class="delete-image-btn" data-image-id="<?php echo $image->id; ?>" 
                                            style="position:absolute;top:5px;right:5px;background:#dc3545;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:12px;">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" id="upload-image-btn" class="button">Añadir Imagen</button>
                        <input type="file" id="vehicle-image-input" accept="image/*" style="display:none;">
                    </div>
                <?php endif; ?>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php echo $is_edit ? 'Actualizar Vehículo' : 'Crear Vehículo'; ?>
                    </button>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Save vehicle
            $('#wptb-vehicle-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $(this).find('button[type="submit"]');
                var originalText = $btn.text();
                $btn.prop('disabled', true).text('Guardando...');
                
                $.post(ajaxurl, {
                    action: 'wptb_save_vehicle',
                    nonce: '<?php echo wp_create_nonce( 'wptb_vehicle_nonce' ); ?>',
                    data: $(this).serialize()
                }, function(response) {
                    if (response.success) {
                        window.location.href = '?page=wptb-vehicles&message=saved';
                    } else {
                        alert('Error: ' + (response.data || 'No se pudo guardar'));
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Upload image
            $('#upload-image-btn').on('click', function() {
                $('#vehicle-image-input').click();
            });
            
            $('#vehicle-image-input').on('change', function() {
                if (!this.files || !this.files[0]) return;
                
                var formData = new FormData();
                formData.append('action', 'wptb_upload_vehicle_image');
                formData.append('nonce', '<?php echo wp_create_nonce( 'wptb_vehicle_nonce' ); ?>');
                formData.append('vehicle_id', <?php echo $vehicle_id; ?>);
                formData.append('image', this.files[0]);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error al subir imagen: ' + (response.data || 'Error desconocido'));
                        }
                    }
                });
                
                this.value = '';
            });
            
            // Delete image
            $(document).on('click', '.delete-image-btn', function() {
                if (!confirm('¿Eliminar esta imagen?')) return;
                
                var $item = $(this).closest('.vehicle-image-item');
                var imageId = $(this).data('image-id');
                
                $.post(ajaxurl, {
                    action: 'wptb_delete_vehicle_image',
                    nonce: '<?php echo wp_create_nonce( 'wptb_vehicle_nonce' ); ?>',
                    image_id: imageId
                }, function(response) {
                    if (response.success) {
                        $item.fadeOut(300, function() { $(this).remove(); });
                    } else {
                        alert('Error al eliminar');
                    }
                });
            });
        });
        </script>

        <style>
            .wptb-badge {
                padding: 4px 10px;
                border-radius: 4px;
                color: #fff;
                font-size: 11px;
                font-weight: 600;
                display: inline-block;
            }
        </style>
        <?php
    }

    /**
     * AJAX: Save vehicle
     */
    public function ajax_save_vehicle() {
        check_ajax_referer( 'wptb_vehicle_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'No tienes permisos' );
        }
        
        parse_str( $_POST['data'], $data );
        
        $vehicle_id = WPTB_Vehicle_Manager::save_vehicle( $data );
        
        if ( $vehicle_id ) {
            wp_send_json_success( array( 'vehicle_id' => $vehicle_id ) );
        } else {
            wp_send_json_error( 'Error al guardar vehículo' );
        }
    }

    /**
     * AJAX: Delete vehicle
     */
    public function ajax_delete_vehicle() {
        check_ajax_referer( 'wptb_vehicle_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'No tienes permisos' );
        }
        
        $vehicle_id = absint( $_POST['vehicle_id'] );
        
        if ( WPTB_Vehicle_Manager::delete_vehicle( $vehicle_id ) ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Error al eliminar' );
        }
    }

    /**
     * AJAX: Upload image
     */
    public function ajax_upload_image() {
        check_ajax_referer( 'wptb_vehicle_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'No tienes permisos' );
        }
        
        $vehicle_id = absint( $_POST['vehicle_id'] );
        
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        
        $uploaded = wp_handle_upload( $_FILES['image'], array( 'test_form' => false ) );
        
        if ( isset( $uploaded['error'] ) ) {
            wp_send_json_error( $uploaded['error'] );
        }
        
        // Check if this is the first image (make it primary)
        $images = WPTB_Vehicle_Manager::get_vehicle_images( $vehicle_id );
        $is_primary = empty( $images );
        
        WPTB_Vehicle_Manager::add_vehicle_image( $vehicle_id, $uploaded['url'], $is_primary );
        
        wp_send_json_success( array( 'url' => $uploaded['url'] ) );
    }

    /**
     * AJAX: Delete image
     */
    public function ajax_delete_image() {
        check_ajax_referer( 'wptb_vehicle_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'No tienes permisos' );
        }
        
        $image_id = absint( $_POST['image_id'] );
        
        if ( WPTB_Vehicle_Manager::delete_vehicle_image( $image_id ) ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Error al eliminar' );
        }
    }
}
