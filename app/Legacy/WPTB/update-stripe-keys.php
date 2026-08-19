<?php
/**
 * Script temporal para actualizar las claves de Stripe en WordPress
 * INSTRUCCIONES:
 * 1. Subir este archivo a la raíz de WordPress (junto a wp-config.php)
 * 2. Acceder vía navegador: http://tu-sitio.com/update-stripe-keys.php
 * 3. ELIMINAR este archivo inmediatamente después de usarlo (por seguridad)
 */

// Cargar WordPress
require_once( dirname(__FILE__) . '/wp-load.php' );

// Verificar que sea admin
if ( ! current_user_can( 'manage_options' ) ) {
    die( '❌ Error: Necesitas ser administrador para ejecutar este script.' );
}

echo '<h1>🔐 Actualización de Claves Stripe</h1>';
echo '<style>body { font-family: system-ui; padding: 20px; } .success { color: green; } .error { color: red; }</style>';

// Las nuevas claves LIVE de producción
$new_publishable_key = 'pk_live_51LznPeH1P4XrTy9Cm8sxWqRMeRIAlUBqWAaF3FFafArxlS27tVrUrlB6l6HeacCaGTK1YqnNA6gAoMSljFjJPwFk00CzcbBfpd';
$new_secret_key = 'sk_live_YOUR_LIVE_SECRET_KEY';
$new_mode = 'live';

echo '<h2>Claves Actuales:</h2>';
echo '<p><strong>Publishable Key:</strong> ' . esc_html( get_option( 'wptb_stripe_publishable_key', 'No configurada' ) ) . '</p>';
echo '<p><strong>Secret Key:</strong> ' . esc_html( substr( get_option( 'wptb_stripe_secret_key', 'No configurada' ), 0, 20 ) ) . '...</p>';
echo '<p><strong>Modo:</strong> ' . esc_html( get_option( 'wptb_stripe_mode', 'test' ) ) . '</p>';

echo '<hr>';

echo '<h2>Actualizando a claves LIVE:</h2>';

// Actualizar las claves
$result1 = update_option( 'wptb_stripe_publishable_key', $new_publishable_key );
$result2 = update_option( 'wptb_stripe_secret_key', $new_secret_key );
$result3 = update_option( 'wptb_stripe_mode', $new_mode );

if ( $result1 !== false || get_option('wptb_stripe_publishable_key') === $new_publishable_key ) {
    echo '<p class="success">✅ Publishable Key actualizada correctamente</p>';
} else {
    echo '<p class="error">❌ Error al actualizar Publishable Key</p>';
}

if ( $result2 !== false || get_option('wptb_stripe_secret_key') === $new_secret_key ) {
    echo '<p class="success">✅ Secret Key actualizada correctamente</p>';
} else {
    echo '<p class="error">❌ Error al actualizar Secret Key</p>';
}

if ( $result3 !== false || get_option('wptb_stripe_mode') === $new_mode ) {
    echo '<p class="success">✅ Modo configurado a LIVE</p>';
} else {
    echo '<p class="error">❌ Error al configurar modo</p>';
}

echo '<hr>';

echo '<h2>Claves Nuevas (verificación):</h2>';
echo '<p><strong>Publishable Key:</strong> ' . esc_html( get_option( 'wptb_stripe_publishable_key' ) ) . '</p>';
echo '<p><strong>Secret Key:</strong> ' . esc_html( substr( get_option( 'wptb_stripe_secret_key' ), 0, 20 ) ) . '...</p>';
echo '<p><strong>Modo:</strong> <strong style="color: red;">' . esc_html( strtoupper( get_option( 'wptb_stripe_mode' ) ) ) . '</strong></p>';

echo '<hr>';
echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">';
echo '<h3>⚠️ IMPORTANTE:</h3>';
echo '<ul>';
echo '<li><strong>Elimina este archivo inmediatamente</strong> por seguridad (contiene tus claves secretas)</li>';
echo '<li>Ahora estás en <strong>MODO LIVE</strong> - Los pagos serán REALES</li>';
echo '<li>Prueba el flujo de pago completo antes de publicar</li>';
echo '<li>Verifica que la página de pago esté funcionando correctamente</li>';
echo '</ul>';
echo '</div>';

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=wptb-settings') . '" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">Ver Configuración en WordPress</a></p>';
?>
