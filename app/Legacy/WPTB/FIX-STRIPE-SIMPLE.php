<?php
/**
 * SCRIPT SIMPLE - NO EDITAR NADA
 * Solo subir y ejecutar
 */

// Cargar WordPress
require_once( dirname(__FILE__) . '/wp-load.php' );

// LAS CLAVES CORRECTAS (confirmadas por el usuario)
$pk = 'pk_live_51LznPeH1P4XrTy9Cm8sxWqRMeRIAlUBqWAaF3FFafArxlS27tVrUrlB6l6HeacCaGTK1YqnNA6gAoMSljFjJPwFk00CzcbBfpd';
$sk = 'sk_live_YOUR_LIVE_SECRET_KEY';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fix Stripe AHORA</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        .btn { background: #28a745; color: white; padding: 15px 30px; font-size: 18px; border: none; border-radius: 5px; cursor: pointer; display: block; width: 100%; margin: 20px 0; }
        .btn:hover { background: #218838; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Stripe - Ejecutar AHORA</h1>
        
        <?php if (!isset($_POST['ejecutar'])): ?>
            
            <div class="warning">
                <strong>⚠️ Este script configurará las claves de Stripe automáticamente</strong>
                <p>No necesitas editar nada. Solo haz clic en el botón.</p>
            </div>
            
            <form method="post">
                <button type="submit" name="ejecutar" value="1" class="btn">
                    ✅ CONFIGURAR STRIPE AHORA
                </button>
            </form>
            
        <?php else: ?>
            
            <h2>Ejecutando...</h2>
            
            <?php
            // FORZAR actualización
            delete_option('wptb_stripe_publishable_key');
            delete_option('wptb_stripe_secret_key');
            delete_option('wptb_stripe_mode');
            
            add_option('wptb_stripe_publishable_key', $pk, '', 'yes');
            add_option('wptb_stripe_secret_key', $sk, '', 'yes');
            add_option('wptb_stripe_mode', 'live', '', 'yes');
            
            // Verificar
            $saved_pk = get_option('wptb_stripe_publishable_key');
            $saved_sk = get_option('wptb_stripe_secret_key');
            $saved_mode = get_option('wptb_stripe_mode');
            
            $pk_ok = ($saved_pk === $pk);
            $sk_ok = ($saved_sk === $sk);
            $mode_ok = ($saved_mode === 'live');
            
            if ($pk_ok && $sk_ok && $mode_ok) {
                echo '<div class="success">';
                echo '<h2>✅ ¡ÉXITO!</h2>';
                echo '<p><strong>Las claves se configuraron correctamente:</strong></p>';
                echo '<div class="code">';
                echo 'Publishable Key: ' . substr($saved_pk, 0, 30) . '...<br>';
                echo 'Secret Key: ' . substr($saved_sk, 0, 30) . '...<br>';
                echo 'Modo: ' . $saved_mode;
                echo '</div>';
                echo '</div>';
                
                echo '<div class="warning">';
                echo '<h3>📋 PRÓXIMOS PASOS:</h3>';
                echo '<ol>';
                echo '<li><strong>ELIMINA este archivo</strong> (FIX-STRIPE-SIMPLE.php)</li>';
                echo '<li><strong>Limpia la caché</strong> de tu sitio</li>';
                echo '<li><strong>Recarga la página de pago</strong> con Ctrl+Shift+R</li>';
                echo '<li><strong>Prueba de nuevo</strong> el flujo de pago</li>';
                echo '</ol>';
                echo '</div>';
                
            } else {
                echo '<div class="error">';
                echo '<h2>❌ Error</h2>';
                echo '<p>Las claves no se guardaron correctamente:</p>';
                echo '<ul>';
                if (!$pk_ok) echo '<li>Publishable Key: ERROR</li>';
                if (!$sk_ok) echo '<li>Secret Key: ERROR</li>';
                if (!$mode_ok) echo '<li>Modo: ERROR</li>';
                echo '</ul>';
                echo '<p>Intenta ejecutar el script SQL directamente en phpMyAdmin.</p>';
                echo '</div>';
            }
            ?>
            
        <?php endif; ?>
        
    </div>
</body>
</html>
