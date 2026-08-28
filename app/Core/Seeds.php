<?php
namespace MeTransfers\Core;

final class Seeds {
    public function register() {
        add_action( 'after_switch_theme', array( __CLASS__, 'run' ), 20 );
        add_action( 'init', array( __CLASS__, 'autoRunOnce' ), 10 );
    }

    public static function run() {
        self::ensureVehicleTypes();
        self::ensureTransferProduct();
        self::ensurePage( 'Finalizar Reserva', 'reservas-metransfers', '[wptb_booking_details]' );
        self::ensurePage( 'Seleccionar Vehiculo', 'seleccionar-vehiculo', '[wptb_vehicle_selection]' );
        self::ensurePage( 'Finalizar Pago', 'pago', '[wptb_checkout]' );
        self::ensurePage( 'Reservas Hotel', 'reservas-hotel', '[hqp_booking_form]' );
        self::ensurePage( 'Reservaciones', 'reservaciones', '[wptb_booking_form]' );
        
        // Legal Pages
        self::ensurePage( 'Aviso Legal', 'aviso-legal', self::getLegalContent('aviso-legal') );
        self::ensurePage( 'Política de privacidad', 'politica-de-privacidad', self::getLegalContent('politica-de-privacidad') );
        self::ensurePage( 'Terminos y condiciones', 'terminos-y-condiciones', self::getLegalContent('terminos-y-condiciones') );
        self::ensurePage( 'Política de cookies', 'cookies', self::getLegalContent('cookies') );
    }

    /**
     * Se engancha en 'init' para auto-ejecutar las seeds una única vez en este entorno.
     */
    public static function autoRunOnce() {
        if ( ! get_option( 'mt_seeds_auto_run_done_v2' ) ) {
            self::run();
            update_option( 'mt_seeds_auto_run_done_v2', 1, false );
        }
    }

    /**
     * Comprueba si faltan páginas críticas del flujo y emite un aviso de admin.
     * Se llama desde admin_init para no penalizar el frontend.
     */
    public static function adminNoticesMissingPages() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $required = array(
            'seleccionar-vehiculo'  => 'Seleccionar Vehiculo',
            'reservas-metransfers'  => 'Finalizar Reserva',
            'pago'                  => 'Finalizar Pago',
            'reservas-hotel'        => 'Reservas Hotel',
            'reservaciones'         => 'Reservaciones',
        );

        $missing = array();
        foreach ( $required as $slug => $label ) {
            if ( ! get_page_by_path( $slug ) ) {
                $missing[] = '<strong>' . esc_html( $label ) . '</strong> (<code>/' . esc_html( $slug ) . '/</code>)';
            }
        }

        if ( empty( $missing ) ) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo '<strong>MeTransfers:</strong> ';
        echo esc_html__( 'Faltan páginas críticas del flujo de reserva. Sin ellas, los usuarios no podrán completar una reserva. Faltantes: ', 'me-transfers' );
        echo implode( ', ', $missing ); // already escaped above
        echo '. <a href="' . esc_url( admin_url( 'admin.php?page=mt-seeds-run' ) ) . '">';
        echo esc_html__( 'Crear páginas automáticamente', 'me-transfers' );
        echo '</a></p></div>';
    }


    private static function ensureVehicleTypes() {
        global $wpdb;
        $table = $wpdb->prefix . 'wptb_vehicle_types';
        if ( 0 !== (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) ) {
            return;
        }

        $types = array(
            array( 'name' => 'Sedán', 'slug' => 'sedan', 'description' => 'Vehículo sedán estándar para 3-4 pasajeros', 'display_order' => 1 ),
            array( 'name' => 'SUV', 'slug' => 'suv', 'description' => 'Vehículo grande tipo SUV para 5-6 pasajeros', 'display_order' => 2 ),
            array( 'name' => 'Van', 'slug' => 'van', 'description' => 'Furgoneta para grupos de 7-8 pasajeros', 'display_order' => 3 ),
            array( 'name' => 'Minibús', 'slug' => 'minibus', 'description' => 'Vehículo para grupos grandes de 9-15 pasajeros', 'display_order' => 4 ),
            array( 'name' => 'Lujo', 'slug' => 'luxury', 'description' => 'Vehículo de lujo premium', 'display_order' => 5 ),
        );
        foreach ( $types as $type ) {
            $wpdb->insert( $table, $type );
        }
    }

    private static function ensureTransferProduct() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $product_id = absint( get_option( 'wptb_transfer_product_id' ) );
        if ( $product_id && get_post( $product_id ) ) {
            return;
        }

        $product_id = wp_insert_post(
            array(
                'post_title'   => 'Transfer Service',
                'post_content' => 'Booking transfer payment.',
                'post_status'  => 'publish',
                'post_type'    => 'product',
            )
        );
        if ( ! $product_id || is_wp_error( $product_id ) ) {
            return;
        }

        update_post_meta( $product_id, '_visibility', 'hidden' );
        update_post_meta( $product_id, '_stock_status', 'instock' );
        update_post_meta( $product_id, '_price', '1' );
        update_post_meta( $product_id, '_regular_price', '1' );
        update_post_meta( $product_id, '_virtual', 'yes' );
        update_option( 'wptb_transfer_product_id', $product_id, false );
    }

    private static function ensurePage( $title, $slug, $content ) {
        $existing = get_page_by_path( $slug );
        if ( $existing ) {
            // Si existe pero está vacío, lo forzamos a actualizar para que el pre-builder lo pille
            if ( empty( trim( strip_tags( $existing->post_content ) ) ) && ! empty( $content ) ) {
                wp_update_post( array(
                    'ID'           => $existing->ID,
                    'post_content' => $content
                ) );
            }
            return;
        }

        wp_insert_post(
            array(
                'post_title'     => $title,
                'post_name'      => $slug,
                'post_content'   => $content,
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            )
        );
    }
    
    private static function getLegalContent( $type ) {
        switch ( $type ) {
            case 'aviso-legal':
                return "<h2>1. DATOS IDENTIFICATIVOS</h2>\n<p>En cumplimiento con el deber de información recogido en artículo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y del Comercio Electrónico, a continuación se reflejan los siguientes datos: la empresa titular de dominio web es METRANSFERS GESTION SL, con correo electrónico de contacto: info@metransfers.es.</p>\n<h2>2. USUARIOS</h2>\n<p>El acceso y/o uso de este portal de METRANSFERS GESTION SL atribuye la condición de USUARIO, que acepta, desde dicho acceso y/o uso, las Condiciones Generales de Uso aquí reflejadas.</p>";
            case 'politica-de-privacidad':
                return "<h2>1. PRIVACIDAD Y PROTECCIÓN DE DATOS</h2>\n<p>METRANSFERS GESTION SL cumple con las directrices del Reglamento General de Protección de Datos (RGPD) y demás normativa vigente en cada momento, y vela por garantizar un correcto uso y tratamiento de los datos personales del usuario.</p>\n<h2>2. RECOGIDA Y FINALIDAD</h2>\n<p>Los datos recabados a través de los formularios de reserva serán utilizados exclusivamente para la gestión y prestación del servicio de traslado contratado, así como para la facturación del mismo.</p>";
            case 'terminos-y-condiciones':
                return "<h2>1. CONDICIONES DE RESERVA</h2>\n<p>Al realizar una reserva a través de nuestra web, el cliente acepta expresamente las presentes condiciones. El servicio de traslado se confirmará una vez completado el proceso de pago y recibida la confirmación por correo electrónico.</p>\n<h2>2. CANCELACIONES Y MODIFICACIONES</h2>\n<p>Las modificaciones o cancelaciones deberán notificarse con al menos 24 horas de antelación al inicio del servicio. Las cancelaciones fuera de este plazo podrán conllevar gastos de cancelación de hasta el 100% del importe.</p>\n<h2>3. EQUIPAJE</h2>\n<p>El cliente deberá informar de la cantidad y volumen del equipaje durante el proceso de reserva para asignar el vehículo adecuado. METRANSFERS GESTION SL no se hace responsable del equipaje no declarado que no quepa en el vehículo.</p>";
            case 'cookies':
                return "<h2>1. ¿QUÉ SON LAS COOKIES?</h2>\n<p>Una cookie es un fichero que se descarga en su ordenador al acceder a determinadas páginas web. Las cookies permiten a una página web, entre otras cosas, almacenar y recuperar información sobre los hábitos de navegación de un usuario o de su equipo.</p>\n<h2>2. COOKIES UTILIZADAS EN ESTA WEB</h2>\n<p>Esta web utiliza cookies técnicas (necesarias para el proceso de reserva y pago) y cookies de personalización (como la selección del idioma).</p>";
        }
        return '';
    }
}
