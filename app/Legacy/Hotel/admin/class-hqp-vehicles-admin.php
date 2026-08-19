<?php

class HQP_Vehicles_Admin {

    public function display_vehicles_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_hotel_vehicles';
        
        // Handle actions
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
        $vehicle_id = isset( $_GET['vehicle_id'] ) ? absint( $_GET['vehicle_id'] ) : 0;
        
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hqp_hotel_vehicle_nonce']) ) {
            if ( wp_verify_nonce( $_POST['hqp_hotel_vehicle_nonce'], 'save_hotel_vehicle' ) ) {
                $this->save_vehicle();
                $action = 'list';
                echo '<div class="notice notice-success is-dismissible"><p>Vehículo guardado correctamente.</p></div>';
            }
        }
        
        if ( $action === 'delete' && $vehicle_id > 0 ) {
            $wpdb->delete( $table_name, array( 'id' => $vehicle_id ), array( '%d' ) );
            echo '<div class="notice notice-success is-dismissible"><p>Vehículo eliminado.</p></div>';
            $action = 'list';
        }
        
        if ( $action === 'edit' || $action === 'new' ) {
            $this->display_vehicle_form( $vehicle_id );
        } else {
            $this->display_vehicles_list();
        }
    }
    
    private function display_vehicles_list() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_hotel_vehicles';
        $vehicles = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY display_order ASC, name ASC" );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Vehículos para Hoteles</h1>
            <a href="?post_type=hotel_partner&page=hotel-vehicles&action=new" class="page-title-action">Añadir Nuevo</a>
            <hr class="wp-header-end">
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 80px;">Imagen</th>
                        <th>Nombre</th>
                        <th>Capacidad</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $vehicles ) ) : ?>
                        <tr>
                            <td colspan="6">No hay vehículos configurados para hoteles.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $vehicles as $v ) : ?>
                            <tr>
                                <td>
                                    <?php if ( ! empty( $v->image_url ) ) : ?>
                                        <img src="<?php echo esc_url( $v->image_url ); ?>" style="max-width: 60px; height: auto;" />
                                    <?php else : ?>
                                        <div style="width:60px;height:40px;background:#eee;text-align:center;line-height:40px;color:#999;font-size:10px;">Sin img</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html( $v->name ); ?></strong></td>
                                <td><?php echo esc_html( $v->capacity ); ?> pax</td>
                                <td><?php echo esc_html( $v->display_order ); ?></td>
                                <td>
                                    <?php if ( $v->is_active ) : ?>
                                        <span style="color: green; font-weight: bold;">Activo</span>
                                    <?php else : ?>
                                        <span style="color: red;">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?post_type=hotel_partner&page=hotel-vehicles&action=edit&vehicle_id=<?php echo $v->id; ?>" class="button button-small">Editar</a>
                                    <a href="?post_type=hotel_partner&page=hotel-vehicles&action=delete&vehicle_id=<?php echo $v->id; ?>" class="button button-small" style="color: #a00;" onclick="return confirm('¿Estás seguro de eliminar este vehículo?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function display_vehicle_form( $vehicle_id = 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_hotel_vehicles';
        $vehicle = null;
        
        if ( $vehicle_id > 0 ) {
            $vehicle = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $vehicle_id ) );
        }
        
        wp_enqueue_media();
        ?>
        <div class="wrap">
            <h1><?php echo $vehicle ? 'Editar Vehículo de Hotel' : 'Añadir Nuevo Vehículo de Hotel'; ?></h1>
            <a href="?post_type=hotel_partner&page=hotel-vehicles" class="page-title-action">Volver a la lista</a>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'save_hotel_vehicle', 'hqp_hotel_vehicle_nonce' ); ?>
                <input type="hidden" name="id" value="<?php echo $vehicle ? esc_attr( $vehicle->id ) : 0; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="name">Nombre del Vehículo</label></th>
                        <td><input type="text" name="name" id="name" value="<?php echo $vehicle ? esc_attr( $vehicle->name ) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="description">Descripción</label></th>
                        <td><textarea name="description" id="description" rows="3" class="large-text"><?php echo $vehicle ? esc_textarea( $vehicle->description ) : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="capacity">Capacidad (Pasajeros)</label></th>
                        <td><input type="number" name="capacity" id="capacity" value="<?php echo $vehicle ? esc_attr( $vehicle->capacity ) : '4'; ?>" min="1" required></td>
                    </tr>
                    <tr>
                        <th><label for="image_url">Imagen URL</label></th>
                        <td>
                            <input type="text" name="image_url" id="image_url" value="<?php echo $vehicle ? esc_attr( $vehicle->image_url ) : ''; ?>" class="regular-text">
                            <button type="button" class="button hqp-upload-image">Seleccionar Imagen</button>
                            <br><span class="description">Selecciona una imagen de la biblioteca de medios o pega la URL.</span>
                            <div id="image_preview" style="margin-top: 10px;">
                                <?php if ( $vehicle && ! empty( $vehicle->image_url ) ) : ?>
                                    <img src="<?php echo esc_url( $vehicle->image_url ); ?>" style="max-width: 200px; height: auto;" />
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="display_order">Orden de visualización</label></th>
                        <td><input type="number" name="display_order" id="display_order" value="<?php echo $vehicle ? esc_attr( $vehicle->display_order ) : '0'; ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="is_active">Estado</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked( ! $vehicle || $vehicle->is_active ); ?>>
                                Vehículo activo
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( 'Guardar Vehículo' ); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($){
            var custom_uploader;
            $('.hqp-upload-image').click(function(e) {
                e.preventDefault();
                if (custom_uploader) {
                    custom_uploader.open();
                    return;
                }
                custom_uploader = wp.media.frames.file_frame = wp.media({
                    title: 'Elegir Imagen',
                    button: { text: 'Elegir Imagen' },
                    multiple: false
                });
                custom_uploader.on('select', function() {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#image_url').val(attachment.url);
                    $('#image_preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;" />');
                });
                custom_uploader.open();
            });
        });
        </script>
        <?php
    }
    
    private function save_vehicle() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wptb_hotel_vehicles';
        
        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        
        $data = array(
            'name'          => sanitize_text_field( $_POST['name'] ),
            'description'   => wp_kses_post( $_POST['description'] ),
            'capacity'      => absint( $_POST['capacity'] ),
            'image_url'     => esc_url_raw( $_POST['image_url'] ),
            'display_order' => absint( $_POST['display_order'] ),
            'is_active'     => isset( $_POST['is_active'] ) ? 1 : 0
        );
        
        if ( $id > 0 ) {
            $wpdb->update( $table_name, $data, array( 'id' => $id ), array( '%s', '%s', '%d', '%s', '%d', '%d' ), array( '%d' ) );
        } else {
            $wpdb->insert( $table_name, $data, array( '%s', '%s', '%d', '%s', '%d', '%d' ) );
        }
    }
}
