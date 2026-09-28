<?php
namespace MeTransfers\Booking;

use MeTransfers\Core\Settings;

/**
 * Server-side Google Maps web-service calls plus a persistent record of the
 * last provider failure, so a quote outage (missing key, API not enabled,
 * billing, IP/referrer restriction...) is visible in wp-admin instead of only
 * in PHP logs the site owner may not be able to read.
 */
final class MapsProvider {
    public const GEOCODING       = 'geocoding';
    public const DISTANCE_MATRIX = 'distance_matrix';

    private const OPTION       = 'mt_maps_health';
    private const CHECK_ACTION = 'mt_maps_check';
    private const ENDPOINTS    = array(
        self::GEOCODING       => 'https://maps.googleapis.com/maps/api/geocode/json',
        self::DISTANCE_MATRIX => 'https://maps.googleapis.com/maps/api/distancematrix/json',
    );
    private const LABELS = array(
        self::GEOCODING       => 'Geocoding API',
        self::DISTANCE_MATRIX => 'Distance Matrix API',
    );

    // Google answered correctly but cannot resolve this particular address or
    // route: the integration is healthy, the input is not.
    private const ADDRESS_STATUSES = array( 'ZERO_RESULTS', 'NOT_FOUND', 'MAX_ROUTE_LENGTH_EXCEEDED' );

    public function register() {
        if ( ! is_admin() ) {
            return;
        }
        add_action( 'admin_notices', array( __CLASS__, 'renderNotice' ) );
        add_action( 'admin_post_' . self::CHECK_ACTION, array( __CLASS__, 'handleCheck' ) );
    }

    /**
     * @return array{ok: bool, outage: bool, status: string, detail: string, payload: array}
     */
    public static function request( $service, array $params ) {
        try {
            $key = Settings::requireServerMapsKey();
        } catch ( \RuntimeException $exception ) {
            return self::failure( $service, 'key_missing', '' );
        }

        $response = wp_remote_get(
            add_query_arg( array_merge( $params, array( 'key' => $key ) ), self::ENDPOINTS[ $service ] ),
            array( 'timeout' => 8, 'headers' => array( 'Referer' => home_url( '/' ) ) )
        );
        if ( is_wp_error( $response ) ) {
            return self::failure( $service, 'transport_error', $response->get_error_message() );
        }

        $payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $payload ) || ! isset( $payload['status'] ) ) {
            return self::failure( $service, 'http_' . (int) wp_remote_retrieve_response_code( $response ), '' );
        }

        $status = (string) $payload['status'];
        if ( self::DISTANCE_MATRIX === $service && 'OK' === $status ) {
            $status = (string) ( $payload['rows'][0]['elements'][0]['status'] ?? 'unknown' );
        }
        if ( 'OK' !== $status ) {
            return self::failure( $service, $status, (string) ( $payload['error_message'] ?? '' ), $payload );
        }

        self::recordSuccess( $service );
        return array( 'ok' => true, 'outage' => false, 'status' => 'OK', 'detail' => '', 'payload' => $payload );
    }

    /**
     * Live request against both APIs, bypassing caches and test filters.
     */
    public static function runCheck() {
        $origin = 'Barcelona Airport (BCN), Spain';
        $checks = array(
            self::GEOCODING       => self::request( self::GEOCODING, array( 'address' => $origin, 'language' => 'en' ) ),
            self::DISTANCE_MATRIX => self::request(
                self::DISTANCE_MATRIX,
                array( 'origins' => $origin, 'destinations' => 'Plaça de Catalunya, Barcelona, Spain', 'units' => 'metric' )
            ),
        );

        $results = array();
        foreach ( $checks as $service => $result ) {
            $results[ $service ] = array(
                'ok'     => $result['ok'],
                'status' => $result['status'],
                'detail' => $result['detail'],
                'hint'   => $result['ok'] ? '' : self::hint( $result['status'], $result['detail'] ),
            );
        }
        return $results;
    }

    public static function failures() {
        $failures = get_option( self::OPTION, array() );
        return is_array( $failures ) ? $failures : array();
    }

    /**
     * Actionable Spanish explanation for the site owner. The raw Google
     * message is always shown next to it, so this only has to point the way.
     */
    public static function hint( $status, $detail ) {
        $detail = strtolower( (string) $detail );
        if ( 'key_missing' === $status ) {
            return 'No hay clave de Maps de servidor. Configúrala en MeTransfers → Integraciones («Google Maps API Key (servidor)») o con la constante MT_GOOGLE_MAPS_SERVER_API_KEY en wp-config.php.';
        }
        if ( 'transport_error' === $status || 0 === strpos( (string) $status, 'http_' ) ) {
            return 'El servidor no pudo hablar con maps.googleapis.com (DNS, TLS, cortafuegos o salida HTTPS bloqueada en el hosting).';
        }
        if ( false !== strpos( $detail, 'referer restrictions' ) ) {
            return 'La clave de servidor está restringida por sitios web (HTTP referrer). Geocoding API y Distance Matrix API no admiten esa restricción: usa una clave restringida por dirección IP (la IP de salida del hosting) y limitada a esas dos APIs.';
        }
        if ( false !== strpos( $detail, 'ip address' ) || false !== strpos( $detail, 'not authorized to use this api key' ) ) {
            return 'La restricción por IP de la clave no incluye la IP de salida del servidor. Añade en Google Cloud Console la IP que Google indica en el detalle.';
        }
        if ( false !== strpos( $detail, 'legacy api' ) ) {
            return 'Google trata esta API como «Legacy» y no está habilitada en el proyecto de la clave (Google ya no permite activarla en proyectos nuevos). Usa una clave de un proyecto donde siga activa; si no existe, hay que migrar el cálculo de distancias a Routes API.';
        }
        if ( false !== strpos( $detail, 'project is not authorized' ) || false !== strpos( $detail, 'not activated' ) ) {
            return 'La API no está habilitada en el proyecto de la clave. En Google Cloud Console → APIs y servicios, habilita Geocoding API y Distance Matrix API.';
        }
        if ( false !== strpos( $detail, 'billing' ) ) {
            return 'El proyecto de Google Cloud de la clave no tiene la facturación activa.';
        }
        if ( false !== strpos( $detail, 'api key is invalid' ) || false !== strpos( $detail, 'api key not valid' ) ) {
            return 'Google no reconoce la clave de servidor (borrada, rotada o copiada con errores).';
        }
        if ( in_array( $status, array( 'OVER_QUERY_LIMIT', 'OVER_DAILY_LIMIT' ), true ) ) {
            return 'Se superó la cuota de Google Maps del proyecto o la facturación está suspendida.';
        }
        if ( 'REQUEST_DENIED' === $status ) {
            return 'Google rechazó la clave de servidor. Revisa que exista, que tenga Geocoding API y Distance Matrix API habilitadas, facturación activa y una restricción por IP que incluya el servidor.';
        }
        return 'Respuesta inesperada del proveedor. Pulsa «Probar conexión ahora» para ver el detalle actual.';
    }

    public static function checkUrl() {
        return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::CHECK_ACTION ), self::CHECK_ACTION );
    }

    public static function handleCheck() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'me-transfers' ) );
        }
        check_admin_referer( self::CHECK_ACTION );
        set_transient( self::checkTransient(), self::runCheck(), 10 * MINUTE_IN_SECONDS );
        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
        exit;
    }

    public static function renderNotice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $check = get_transient( self::checkTransient() );
        if ( is_array( $check ) ) {
            delete_transient( self::checkTransient() );
            self::renderCheckResult( $check );
            return;
        }

        $failures = self::failures();
        if ( empty( $failures ) ) {
            return;
        }

        echo '<div class="notice notice-error"><p><strong>MeTransfers: las cotizaciones online están fallando.</strong> ';
        echo 'Ningún cliente puede ver vehículos ni precios hasta que Google acepte la clave de Maps del servidor.</p><ul style="list-style:disc;margin-left:20px;">';
        foreach ( $failures as $service => $failure ) {
            echo '<li>' . esc_html(
                sprintf(
                    'Google %1$s respondió «%2$s» desde el %3$s (último fallo: %4$s).',
                    self::LABELS[ $service ] ?? $service,
                    $failure['status'],
                    wp_date( 'd/m/Y H:i', (int) $failure['first_at'] ),
                    wp_date( 'd/m/Y H:i', (int) $failure['last_at'] )
                )
            );
            self::renderDetail( $failure['status'], $failure['detail'] );
            echo '</li>';
        }
        echo '</ul>';
        self::renderFooter();
        echo '</div>';
    }

    private static function renderCheckResult( array $check ) {
        $ok = ! in_array( false, array_column( $check, 'ok' ), true );
        echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-error' ) . '"><p><strong>';
        echo esc_html( $ok ? 'MeTransfers: la conexión del servidor con Google Maps funciona.' : 'MeTransfers: la comprobación de Google Maps del servidor ha fallado.' );
        echo '</strong></p><ul style="list-style:disc;margin-left:20px;">';
        foreach ( $check as $service => $result ) {
            echo '<li>' . esc_html( sprintf( '%1$s: %2$s', self::LABELS[ $service ] ?? $service, $result['status'] ) );
            if ( ! $result['ok'] ) {
                self::renderDetail( $result['status'], $result['detail'] );
            }
            echo '</li>';
        }
        echo '</ul>';
        self::renderFooter();
        echo '</div>';
    }

    private static function renderDetail( $status, $detail ) {
        if ( '' !== (string) $detail ) {
            echo '<br>' . esc_html( 'Detalle de Google: ' . $detail );
        }
        echo '<br><strong>' . esc_html( 'Qué hacer: ' ) . '</strong>' . esc_html( self::hint( $status, $detail ) );
    }

    private static function renderFooter() {
        $source = Settings::source( 'google_maps_server_api_key' );
        $source_text = 'none' === $source['type']
            ? 'No hay ninguna clave de servidor configurada.'
            : ( 'constant' === $source['type']
                ? sprintf( 'Clave en uso: constante %s de wp-config.php (tiene prioridad sobre el campo del panel; cambiar el panel no la reemplaza).', $source['name'] )
                : sprintf( 'Clave en uso: opción %s (MeTransfers → Integraciones).', $source['name'] ) );
        echo '<p>' . esc_html( $source_text ) . '</p>';
        echo '<p><a class="button button-primary" href="' . esc_url( self::checkUrl() ) . '">' . esc_html( 'Probar conexión ahora' ) . '</a></p>';
    }

    private static function checkTransient() {
        return 'mt_maps_check_' . get_current_user_id();
    }

    private static function failure( $service, $status, $detail, array $payload = array() ) {
        $detail = self::redact( $detail );
        $outage = ! in_array( $status, self::ADDRESS_STATUSES, true );
        if ( $outage ) {
            error_log( sprintf( 'MeTransfers Maps (%s): status=%s%s', $service, $status, '' !== $detail ? ', ' . $detail : '' ) );
            self::recordFailure( $service, $status, $detail );
        } else {
            self::recordSuccess( $service );
        }

        return array( 'ok' => false, 'outage' => $outage, 'status' => $status, 'detail' => $detail, 'payload' => $payload );
    }

    private static function recordFailure( $service, $status, $detail ) {
        $failures = self::failures();
        $previous = isset( $failures[ $service ] ) && is_array( $failures[ $service ] ) ? $failures[ $service ] : null;
        $now = time();

        // During an outage every search fails: avoid one option write per visitor.
        if ( $previous && $previous['status'] === $status && $previous['detail'] === $detail && $now - (int) $previous['last_at'] < MINUTE_IN_SECONDS ) {
            return;
        }

        $failures[ $service ] = array(
            'status'   => $status,
            'detail'   => $detail,
            'first_at' => $previous ? (int) $previous['first_at'] : $now,
            'last_at'  => $now,
        );
        update_option( self::OPTION, $failures, false );
    }

    private static function recordSuccess( $service ) {
        $failures = self::failures();
        if ( ! isset( $failures[ $service ] ) ) {
            return;
        }

        unset( $failures[ $service ] );
        update_option( self::OPTION, $failures, false );
    }

    private static function redact( $text ) {
        $text = preg_replace( '/AIza[0-9A-Za-z_\-]{20,}/', '[redacted]', (string) $text );
        $text = preg_replace( '/([?&]key=)[^&\s]+/', '$1[redacted]', $text );
        return substr( sanitize_text_field( $text ), 0, 300 );
    }
}
