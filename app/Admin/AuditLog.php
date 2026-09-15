<?php
namespace MeTransfers\Admin;

final class AuditLog {
    public static function record( $action, $object_type = '', $object_id = 0, array $context = array() ) {
        global $wpdb;
        if ( ! $wpdb || ! method_exists( $wpdb, 'insert' ) ) {
            return false;
        }

        $action = substr( preg_replace( '/[^a-z0-9_.-]/', '', strtolower( (string) $action ) ), 0, 80 );
        $object_type = substr( preg_replace( '/[^a-z0-9_.-]/', '', strtolower( (string) $object_type ) ), 0, 50 );
        if ( '' === $action ) {
            return false;
        }

        $encoded = function_exists( 'wp_json_encode' )
            ? wp_json_encode( self::redact( $context ) )
            : json_encode( self::redact( $context ) );

        return false !== $wpdb->insert(
            $wpdb->prefix . 'mt_admin_audit',
            array(
                'actor_user_id' => function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0,
                'action_name'    => $action,
                'object_type'    => $object_type,
                'object_id'      => max( 0, (int) $object_id ),
                'context_json'   => $encoded ?: '{}',
                'created_at'     => current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s' )
        );
    }

    public static function renderPage() {
        if ( ! current_user_can( Capabilities::MANAGE_INTEGRATIONS ) ) {
            wp_die( esc_html__( 'No tienes permisos para consultar la auditoría.', 'wptb' ) );
        }

        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT actor_user_id, action_name, object_type, object_id, context_json, created_at
             FROM {$wpdb->prefix}mt_admin_audit
             ORDER BY id DESC
             LIMIT 100"
        );
        ?>
        <div class="wrap">
            <h1>Auditoría MeTransfers</h1>
            <p>Últimas 100 acciones sensibles. El registro no almacena nombres, emails, teléfonos, tokens ni secretos.</p>
            <table class="widefat striped">
                <thead><tr><th>Fecha UTC</th><th>Usuario ID</th><th>Acción</th><th>Objeto</th><th>Contexto seguro</th></tr></thead>
                <tbody>
                <?php if ( empty( $rows ) ) : ?>
                    <tr><td colspan="5">No hay acciones registradas.</td></tr>
                <?php else : foreach ( $rows as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row->created_at ); ?></td>
                        <td><?php echo esc_html( (string) $row->actor_user_id ); ?></td>
                        <td><code><?php echo esc_html( $row->action_name ); ?></code></td>
                        <td><?php echo esc_html( $row->object_type . ( $row->object_id ? ' #' . $row->object_id : '' ) ); ?></td>
                        <td><code><?php echo esc_html( $row->context_json ); ?></code></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function redact( array $context ) {
        $safe = array();
        foreach ( $context as $key => $value ) {
            $key = substr( preg_replace( '/[^a-z0-9_.-]/', '', strtolower( (string) $key ) ), 0, 50 );
            if ( '' === $key ) {
                continue;
            }
            if ( preg_match( '/token|secret|password|api.?key|email|phone|name|address|notes/i', $key ) ) {
                $safe[ $key ] = '[redacted]';
                continue;
            }
            if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || is_string( $value ) || null === $value ) {
                $safe[ $key ] = is_string( $value ) ? substr( $value, 0, 100 ) : $value;
            }
        }
        return $safe;
    }
}
