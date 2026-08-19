<?php
namespace MeTransfers\Booking;

final class I18n {
    private static $translated = array();

    public static function language() {
        if ( function_exists( 'mt_lang' ) ) {
            $language = (string) mt_lang();
        } elseif ( function_exists( 'determine_locale' ) ) {
            $language = substr( (string) determine_locale(), 0, 2 );
        } else {
            $language = 'es';
        }

        $language = strtolower( preg_replace( '/[^a-z-]/i', '', $language ) );
        return '' !== $language ? $language : 'es';
    }

    public static function text( $key, $language = '' ) {
        $strings = self::strings( $language );
        return isset( $strings[ $key ] ) ? $strings[ $key ] : (string) $key;
    }

    public static function normalizeLanguage( $language ) {
        $language = strtolower( preg_replace( '/[^a-z-]/i', '', (string) $language ) );
        return preg_match( '/^[a-z]{2}$/', $language ) ? $language : 'es';
    }

    public static function strings( $language = '' ) {
        $language = self::normalizeLanguage( '' !== $language ? $language : self::language() );
        if ( isset( self::$translated[ $language ] ) ) {
            return self::$translated[ $language ];
        }

        $source = self::spanish();
        if ( 'es' === $language ) {
            self::$translated[ $language ] = $source;
            return $source;
        }

        if ( 'en' === $language ) {
            self::$translated[ $language ] = array_replace( $source, self::english() );
            return self::$translated[ $language ];
        }

        if ( function_exists( 'mt_translate_batch' ) ) {
            $translated = mt_translate_batch( array_values( $source ), $language );
            if ( is_array( $translated ) && count( $translated ) === count( $source ) ) {
                self::$translated[ $language ] = array_combine( array_keys( $source ), array_values( $translated ) );
                return self::$translated[ $language ];
            }
        }

        self::$translated[ $language ] = $source;
        return $source;
    }

    /**
     * Spanish source catalog used by the admin/CLI translation prebuilder.
     */
    public static function sourceStrings() {
        return self::spanish();
    }

    public static function url( $path = '/' ) {
        $path = '/' . ltrim( (string) $path, '/' );
        $language = self::language();

        if ( 'es' !== $language ) {
            $path = '/' . $language . $path;
        }

        return home_url( $path );
    }

    public static function maps_language() {
        $map = array(
            'zh' => 'zh-CN',
        );
        $language = self::language();
        return isset( $map[ $language ] ) ? $map[ $language ] : $language;
    }

    private static function spanish() {
        return array(
            'origin'                    => 'Origen',
            'destination'               => 'Destino',
            'date'                      => 'Fecha',
            'time'                      => 'Hora',
            'search_vehicles'           => 'Buscar vehículos',
            'search_destination'        => 'Buscar destino...',
            'all_destinations'          => 'Todos',
            'cities'                    => 'Ciudades',
            'beaches'                   => 'Playas',
            'airports'                  => 'Aeropuertos',
            'mountains'                 => 'Montaña',
            'no_destinations'           => 'No se encontraron destinos',
            'try_other_search'          => 'Intenta con otro término de búsqueda',
            'book_transfer'             => 'Reserva tu traslado',
            'destination_region'        => 'Destino (región)',
            'exact_address'             => 'Dirección exacta en destino',
            'exact_address_placeholder' => 'Ej.: Calle Principal 123, Tossa de Mar',
            'vehicle_selection'         => 'Selección de vehículo',
            'loading_route'             => 'Cargando ruta...',
            'search'                    => 'Búsqueda',
            'details'                   => 'Detalles',
            'confirmation'              => 'Confirmación',
            'one_way'                   => 'Solo ida',
            'round_trip'                => 'Ida y vuelta',
            'return_trip'               => 'Vuelta',
            'change_search'             => 'Cambiar búsqueda',
            'select_vehicle'            => 'Selecciona tu vehículo',
            'loading_vehicles'          => 'Buscando vehículos...',
            'no_vehicles'               => 'No se encontraron vehículos disponibles.',
            'booking_details'           => 'Detalles de la reserva',
            'trip_summary'              => 'Resumen del viaje',
            'vehicle'                   => 'Vehículo',
            'type'                      => 'Tipo',
            'distance'                  => 'Distancia',
            'total'                     => 'Total',
            'passengers'                => 'Número de pasajeros',
            'return_details'            => 'Detalles de la vuelta',
            'return_date'               => 'Fecha de vuelta',
            'return_time'               => 'Hora de vuelta',
            'return_pickup'             => 'Recogida de vuelta (origen)',
            'return_destination'        => 'Destino de vuelta',
            'full_name'                 => 'Nombre completo',
            'phone'                     => 'Teléfono',
            'email'                     => 'Email',
            'large_suitcases'           => 'Maletas grandes',
            'carry_on'                  => 'Maletas de mano',
            'flight_optional'           => 'Número de vuelo (opcional)',
            'additional_notes'          => 'Notas adicionales',
            'back'                      => 'Volver',
            'confirm_booking'           => 'Confirmar reserva',
            'booking_confirmed'         => '¡Reserva confirmada!',
            'confirmation_email'        => 'Hemos enviado los detalles a tu correo electrónico.',
            'reference'                 => 'Referencia',
            'back_home'                 => 'Volver al inicio',
            'payment_pending_title'     => 'Estamos verificando tu pago',
            'payment_pending_text'      => 'La operación volvió correctamente desde Redsys, pero la confirmación segura aún no ha llegado. Actualiza esta página dentro de unos segundos.',
            'payment_failed_title'      => 'El pago no se completó',
            'payment_failed_text'       => 'No se ha confirmado ningún cargo. Puedes volver a iniciar la reserva o contactar con soporte si necesitas ayuda.',
            'invalid_confirmation_title'=> 'No se pudo verificar la referencia',
            'invalid_confirmation_text' => 'El enlace de confirmación no es válido o está incompleto. Consulta el correo de la reserva o contacta con soporte.',
            'try_again'                 => 'Intentarlo de nuevo',
            'secure_payment'            => 'Pago seguro',
            'booking_summary'           => 'Resumen de tu reserva',
            'original_price'            => 'Precio original',
            'discount'                  => 'Descuento',
            'payment_methods_alt'       => 'Métodos de pago',
            'terms_prefix'              => 'He leído los',
            'terms_link'                => 'Términos y condiciones',
            'terms_suffix'              => 'de la web y estoy de acuerdo en continuar',
            'terms_required'            => 'Debes aceptar los Términos y condiciones para continuar.',
            'terms_server_required'     => 'La aceptación de los Términos y condiciones no pudo verificarse.',
            'pay'                       => 'Pagar',
            'processing'                => 'Procesando...',
            'payment_redirect'          => 'Serás redirigido a la pasarela de pago segura del banco.',
            'payment_received'          => 'Hemos recibido tu pago correctamente.',
            'payment_id'                => 'ID de pago',
            'total_paid'                => 'Total pagado',
            'download_receipt'          => 'Descargar recibo',
            'complete_all_fields'       => 'Por favor completa todos los campos.',
            'complete_required_fields'  => 'Por favor completa todos los campos obligatorios.',
            'location_error'            => 'Permiso denegado o error de ubicación. Por favor escribe tu origen.',
            'origin_restriction'        => 'Lo sentimos, solo operamos transfers con origen en el área de Barcelona.',
            'destination_restriction'   => 'El destino debe estar dentro de los países europeos con cobertura.',
            'origin_policy_error'       => 'No se pudo verificar el origen del traslado.',
            'destination_policy_error'  => 'No se pudo verificar el destino del traslado.',
            'route_outside_service_area'=> 'La ruta debe comenzar o terminar en Cataluña y el otro punto debe estar dentro del área europea cubierta.',
            'invalid_booking_datetime'  => 'La fecha u hora del traslado no es válida.',
            'booking_lead_time_error'   => 'La reserva debe realizarse con al menos dos horas de antelación.',
            'return_datetime_error'     => 'La fecha y hora de vuelta deben ser posteriores a la salida.',
            'geolocation_unsupported'   => 'Tu navegador no soporta geolocalización.',
            'geocode_error'             => 'No se pudo determinar la dirección. Por favor ingrésala manualmente.',
            'calculating'               => 'Calculando...',
            'route_error'               => 'No se pudo calcular la ruta. Verifica el origen y el destino.',
            'route_not_found'           => 'No se encontró una ruta entre los puntos indicados.',
            'quote_rate_limited'        => 'Has realizado demasiadas cotizaciones. Espera un minuto e inténtalo de nuevo.',
            'vehicle_load_error'        => 'Error al cargar los vehículos.',
            'map_load_error'            => 'Error al cargar el mapa.',
            'from_barcelona'            => 'Desde Barcelona',
            'to_barcelona'              => 'Hacia Barcelona',
            'vehicle_data_lost'         => 'Error: se perdieron los datos del vehículo.',
            'configuration_error'       => 'Error de configuración. Contacta con soporte.',
            'system_unavailable'        => 'El sistema no está disponible. Recarga la página.',
            'from_price'                => 'Desde',
            'final_price'               => 'Precio final',
            'select'                    => 'Seleccionar',
            'capacity_unavailable'      => 'Capacidad no disponible',
            'connection_error'          => 'Error de conexión.',
            'invalid_booking_data'      => 'No hay datos de reserva válidos. Inicia una nueva reserva.',
            'corrupt_booking_data'      => 'Los datos de la reserva están dañados.',
            'payment_cancelled'         => 'El pago ha sido cancelado o rechazado por el banco.',
            'bank_connection_error'     => 'No se pudo conectar con el banco.',
            'invalid_booking_request'   => 'Datos de reserva inválidos.',
            'missing_booking_fields'    => 'Faltan datos obligatorios de la reserva.',
            'return_fields_required'    => 'Debes completar todos los datos de la vuelta.',
            'invalid_server_price'      => 'No se pudo calcular un precio válido para la reserva.',
            'price_changed'             => 'La ruta se ha verificado y el precio se actualizó. Revísalo y pulsa Pagar de nuevo.',
            'vehicle_capacity_error'    => 'El vehículo no tiene capacidad suficiente para los pasajeros o el equipaje.',
            'invalid_contact'           => 'El email o el teléfono no son válidos.',
            'booking_save_error'        => 'No se pudo guardar la reserva. Contacta con soporte.',
            'payment_start_error'       => 'No se pudo iniciar el pago. Revisa la configuración de Redsys o contacta con soporte.',
            'pdf_unavailable'           => 'La librería PDF no está disponible.',
            'receipt_title'             => 'Recibo de reserva',
            'trip_type'                 => 'Tipo de viaje',
            'notification_pending_title'=> 'Reserva recibida',
            'notification_confirmed_title'=> '¡Reserva confirmada!',
            'notification_pending_intro'=> 'Hemos recibido tu solicitud y está pendiente de pago.',
            'notification_confirmed_intro'=> 'Tu reserva ha sido confirmada y el pago se recibió correctamente.',
            'hello'                     => 'Hola',
            'luggage'                   => 'Equipaje',
            'flight'                    => 'Vuelo',
            'notes'                     => 'Notas',
            'price'                     => 'Precio',
            'visit_website'             => 'Ir a la web',
            'notification_help'         => 'Si necesitas ayuda, responde a este correo.',
        );
    }

    private static function english() {
        return array(
            'origin'                    => 'Origin',
            'destination'               => 'Destination',
            'date'                      => 'Date',
            'time'                      => 'Time',
            'search_vehicles'           => 'Search vehicles',
            'search_destination'        => 'Search destinations...',
            'all_destinations'          => 'All',
            'cities'                    => 'Cities',
            'beaches'                   => 'Beaches',
            'airports'                  => 'Airports',
            'mountains'                 => 'Mountains',
            'no_destinations'           => 'No destinations found',
            'try_other_search'          => 'Try a different search term',
            'book_transfer'             => 'Book your transfer',
            'destination_region'        => 'Destination (region)',
            'exact_address'             => 'Exact destination address',
            'exact_address_placeholder' => 'E.g. 123 Main Street, Tossa de Mar',
            'vehicle_selection'         => 'Vehicle selection',
            'loading_route'             => 'Loading route...',
            'search'                    => 'Search',
            'details'                   => 'Details',
            'confirmation'              => 'Confirmation',
            'one_way'                   => 'One way',
            'round_trip'                => 'Round trip',
            'return_trip'               => 'Return',
            'change_search'             => 'Change search',
            'select_vehicle'            => 'Select your vehicle',
            'loading_vehicles'          => 'Searching for vehicles...',
            'no_vehicles'               => 'No vehicles are currently available.',
            'booking_details'           => 'Booking details',
            'trip_summary'              => 'Trip summary',
            'vehicle'                   => 'Vehicle',
            'type'                      => 'Type',
            'distance'                  => 'Distance',
            'total'                     => 'Total',
            'passengers'                => 'Number of passengers',
            'return_details'            => 'Return trip details',
            'return_date'               => 'Return date',
            'return_time'               => 'Return time',
            'return_pickup'             => 'Return pickup (origin)',
            'return_destination'        => 'Return destination',
            'full_name'                 => 'Full name',
            'phone'                     => 'Phone',
            'email'                     => 'Email',
            'large_suitcases'           => 'Large suitcases',
            'carry_on'                  => 'Carry-on bags',
            'flight_optional'           => 'Flight number (optional)',
            'additional_notes'          => 'Additional notes',
            'back'                      => 'Back',
            'confirm_booking'           => 'Confirm booking',
            'booking_confirmed'         => 'Booking confirmed!',
            'confirmation_email'        => 'We sent the booking details to your email address.',
            'reference'                 => 'Reference',
            'back_home'                 => 'Back to home',
            'payment_pending_title'     => 'We are verifying your payment',
            'payment_pending_text'      => 'Redsys returned successfully, but the secure confirmation has not arrived yet. Refresh this page in a few seconds.',
            'payment_failed_title'      => 'The payment was not completed',
            'payment_failed_text'       => 'No charge has been confirmed. You can restart the booking or contact support if you need help.',
            'invalid_confirmation_title'=> 'The reference could not be verified',
            'invalid_confirmation_text' => 'The confirmation link is invalid or incomplete. Check your booking email or contact support.',
            'try_again'                 => 'Try again',
            'secure_payment'            => 'Secure payment',
            'booking_summary'           => 'Your booking summary',
            'original_price'            => 'Original price',
            'discount'                  => 'Discount',
            'payment_methods_alt'       => 'Payment methods',
            'terms_prefix'              => 'I have read the',
            'terms_link'                => 'Terms and conditions',
            'terms_suffix'              => 'and agree to continue',
            'terms_required'            => 'You must accept the Terms and conditions to continue.',
            'terms_server_required'     => 'Acceptance of the Terms and conditions could not be verified.',
            'pay'                       => 'Pay',
            'processing'                => 'Processing...',
            'payment_redirect'          => 'You will be redirected to the bank\'s secure payment gateway.',
            'payment_received'          => 'We received your payment successfully.',
            'payment_id'                => 'Payment ID',
            'total_paid'                => 'Total paid',
            'download_receipt'          => 'Download receipt',
            'complete_all_fields'       => 'Please complete all fields.',
            'complete_required_fields'  => 'Please complete all required fields.',
            'location_error'            => 'Location permission was denied or unavailable. Please enter your origin.',
            'origin_restriction'        => 'Sorry, transfer origins must be within the Barcelona area.',
            'destination_restriction'   => 'The destination must be within one of the supported European countries.',
            'origin_policy_error'       => 'The transfer origin could not be verified.',
            'destination_policy_error'  => 'The transfer destination could not be verified.',
            'route_outside_service_area'=> 'The route must start or finish in Catalonia and the other point must be within the supported European service area.',
            'invalid_booking_datetime'  => 'The transfer date or time is invalid.',
            'booking_lead_time_error'   => 'Bookings require at least two hours of advance notice.',
            'return_datetime_error'     => 'The return date and time must be later than the outbound trip.',
            'geolocation_unsupported'   => 'Your browser does not support geolocation.',
            'geocode_error'             => 'The address could not be determined. Please enter it manually.',
            'calculating'               => 'Calculating...',
            'route_error'               => 'The route could not be calculated. Check the origin and destination.',
            'route_not_found'           => 'No route was found between the selected locations.',
            'quote_rate_limited'        => 'Too many quotes were requested. Wait one minute and try again.',
            'vehicle_load_error'        => 'The vehicles could not be loaded.',
            'map_load_error'            => 'The map could not be loaded.',
            'from_barcelona'            => 'From Barcelona',
            'to_barcelona'              => 'To Barcelona',
            'vehicle_data_lost'         => 'Error: vehicle data is missing.',
            'configuration_error'       => 'Configuration error. Contact support.',
            'system_unavailable'        => 'The system is unavailable. Reload the page.',
            'from_price'                => 'From',
            'final_price'               => 'Final price',
            'select'                    => 'Select',
            'capacity_unavailable'      => 'Capacity unavailable',
            'connection_error'          => 'Connection error.',
            'invalid_booking_data'      => 'There is no valid booking data. Start a new booking.',
            'corrupt_booking_data'      => 'The booking data is corrupted.',
            'payment_cancelled'         => 'The payment was cancelled or declined by the bank.',
            'bank_connection_error'     => 'The bank could not be reached.',
            'invalid_booking_request'   => 'The booking data is invalid.',
            'missing_booking_fields'    => 'Required booking details are missing.',
            'return_fields_required'    => 'Complete all return trip details.',
            'invalid_server_price'      => 'A valid server price could not be calculated for this booking.',
            'price_changed'             => 'The route was verified and the price changed. Review it and press Pay again.',
            'vehicle_capacity_error'    => 'The vehicle does not have enough passenger or luggage capacity.',
            'invalid_contact'           => 'The email address or phone number is invalid.',
            'booking_save_error'        => 'The booking could not be saved. Contact support.',
            'payment_start_error'       => 'The payment could not be started. Check the Redsys configuration or contact support.',
            'pdf_unavailable'           => 'The PDF library is unavailable.',
            'receipt_title'             => 'Booking receipt',
            'trip_type'                 => 'Trip type',
            'notification_pending_title'=> 'Booking received',
            'notification_confirmed_title'=> 'Booking confirmed!',
            'notification_pending_intro'=> 'We received your booking request and it is awaiting payment.',
            'notification_confirmed_intro'=> 'Your booking is confirmed and your payment was received successfully.',
            'hello'                     => 'Hello',
            'luggage'                   => 'Luggage',
            'flight'                    => 'Flight',
            'notes'                     => 'Notes',
            'price'                     => 'Price',
            'visit_website'             => 'Visit website',
            'notification_help'         => 'Reply to this email if you need help.',
        );
    }
}
