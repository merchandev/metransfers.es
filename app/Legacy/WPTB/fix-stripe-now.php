<?php
/**
 * Script de Verificación y Configuración de Stripe
 * 
 * INSTRUCCIONES:
 * 1. Subir este archivo a la raíz de WordPress
 * 2. Acceder vía: http://tu-sitio.com/fix-stripe-now.php
 * 3. ELIMINAR este archivo después de usarlo
 */

// Cargar WordPress
require_once( dirname(__FILE__) . '/wp-load.php' );

// Verificar permisos
if ( ! current_user_can( 'manage_options' ) ) {
    die( '<h1>❌ Error de Permisos</h1><p>Debes estar logueado como administrador.</p>' );
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Stripe - Configuración Inmediata</title>
    <style>
        body { font-family: system-ui; padding: 20px; max-width: 900px; margin: 0 auto; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 10px 0; }
        button { background: #007bff; color: white; border: none; padding: 12px 24px; font-size: 16px; cursor: pointer; border-radius: 5px; margin: 10px 5px; }
        button.danger { background: #dc3545; }
        button.success { background: #28a745; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Fix Stripe - Configuración Inmediata</h1>
    
    <?php
    // PASO 1: Verificar claves actuales
    echo '<h2>1️⃣ Claves Actuales en la Base de Datos</h2>';
    
    $current_pub = get_option('wptb_stripe_publishable_key', 'NO CONFIGURADA');
    $current_sec = get_option('wptb_stripe_secret_key', 'NO CONFIGURADA');
    $current_mode = get_option('wptb_stripe_mode', 'test');
    
    $pub_preview = $current_pub !== 'NO CONFIGURADA' ? substr($current_pub, 0, 30) . '...' : 'NO CONFIGURADA';
    $sec_preview = $current_sec !== 'NO CONFIGURADA' ? substr($current_sec, 0, 30) . '...' : 'NO CONFIGURADA';
    
    // Verificar si son correctas
    $pub_valid = (strpos($current_pub, 'pk_live_') === 0 || strpos($current_pub, 'pk_test_') === 0);
    $sec_valid = (strpos($current_sec, 'sk_live_') === 0 || strpos($current_sec, 'sk_test_') === 0);
    
    if ($pub_valid && $sec_valid) {
        echo '<div class="success">';
        echo '<h3>✅ Claves CORRECTAS encontradas</h3>';
    } else {
        echo '<div class="error">';
        echo '<h3>❌ Claves INCORRECTAS o faltantes</h3>';
    }
    
    echo "<p><strong>Publishable Key:</strong> $pub_preview</p>";
    echo "<p><strong>Secret Key:</strong> $sec_preview</p>";
    echo "<p><strong>Modo:</strong> $current_mode</p>";
    echo '</div>';
    
    // PASO 2: Configurar automáticamente si están mal
    if (!$pub_valid || !$sec_valid) {
        echo '<h2>2️⃣ Configurar Claves Correctas AHORA</h2>';
        echo '<div class="warning">';
        echo '<p>Haz clic para configurar las claves CORRECTAS (TOKENs, no IDs):</p>';
        echo '<form method="post" style="display:inline;">';
        echo '<input type="hidden" name="action" value="fix_stripe_keys">';
        echo '<button type="submit" class="success">✅ Configurar Claves Correctas AHORA</button>';
        echo '</form>';
        echo '</div>';
    }
    
    // Procesar acción
    if (isset($_POST['action']) && $_POST['action'] === 'fix_stripe_keys') {
        echo '<h2>🔄 Configurando claves...</h2>';
        
        $correct_pub = 'pk_live_51LznPeH1P4XrTy9Cm8sxWqRMeRIAlUBqWAaF3FFafArxlS27tVrUrlB6l6HeacCaGTK1YqnNA6gAoMSljFjJPwFk00CzcbBfpd';
        $correct_sec = 'sk_live_YOUR_LIVE_SECRET_KEY';
        
        update_option('wptb_stripe_publishable_key', $correct_pub);
        update_option('wptb_stripe_secret_key', $correct_sec);
        update_option('wptb_stripe_mode', 'live');
        
        echo '<div class="success">';
        echo '<h3>✅ Claves Configuradas Exitosamente</h3>';
        echo '<p><strong>Publishable Key:</strong> ' . substr($correct_pub, 0, 30) . '...</p>';
        echo '<p><strong>Secret Key:</strong> ' . substr($correct_sec, 0, 30) . '...</p>';
        echo '<p><strong>Modo:</strong> LIVE (Producción)</p>';
        echo '</div>';
        
        echo '<div class="warning">';
        echo '<h3>⚠️ Próximos Pasos:</h3>';
        echo '<ol>';
        echo '<li><strong>Recarga esta página</strong> para verificar que se guardaron</li>';
        echo '<li><strong>Limpia la caché</strong> de tu sitio (si usas plugin de caché)</li>';
        echo '<li><strong>Prueba la página de pago</strong> de nuevo</li>';
        echo '<li><strong>ELIMINA este archivo</strong> (fix-stripe-now.php) por seguridad</li>';
        echo '</ol>';
        echo '</div>';
    }
    
    // PASO 3: Test de conexión con Stripe
    echo '<h2>3️⃣ Probar Conexión con Stripe</h2>';
    
    if ($pub_valid && $sec_valid) {
        echo '<div class="info">';
        echo '<p>Test básico: Intentar crear un Payment Intent de prueba...</p>';
        
        $test_amount = 100; // €1.00
        $stripe_secret = get_option('wptb_stripe_secret_key');
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.stripe.com/v1/payment_intents',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array(
                'amount' => $test_amount,
                'currency' => 'eur',
                'automatic_payment_methods[enabled]' => 'true',
                'description' => 'Test desde fix-stripe-now.php'
            )),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $stripe_secret,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $result = json_decode($response, true);
        
        if ($http_code === 200 && isset($result['id'])) {
            echo '<div class="success">';
            echo '<h3>✅ Conexión con Stripe EXITOSA</h3>';
            echo '<p>Payment Intent de prueba creado: ' . $result['id'] . '</p>';
            echo '<p><strong>Todo está funcionando correctamente. Ahora deberías poder procesar pagos.</strong></p>';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>❌ Error al Conectar con Stripe</h3>';
            if (isset($result['error'])) {
                echo '<p><strong>Error:</strong> ' . $result['error']['message'] . '</p>';
                echo '<p><strong>Tipo:</strong> ' . $result['error']['type'] . '</p>';
            }
            echo '<pre>' . print_r($result, true) . '</pre>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="warning">';
        echo '<p>⚠️ No se puede probar la conexión hasta que configures las claves correctas.</p>';
        echo '</div>';
    }
    
    // Warning final
    echo '<div class="error" style="margin-top: 30px;">';
    echo '<h3>⚠️ IMPORTANTE - Seguridad</h3>';
    echo '<p><strong>ELIMINA este archivo (fix-stripe-now.php) INMEDIATAMENTE</strong> después de usarlo.</p>';
    echo '<p>Este archivo expone información sensible y debe ser eliminado por seguridad.</p>';
    echo '</div>';
    ?>
    
    <script>
    // Auto-scroll to errors
    window.addEventListener('load', function() {
        const errorDiv = document.querySelector('.error');
        if (errorDiv) {
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    </script>
</body>
</html>
