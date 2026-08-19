<?php
/**
 * FORCE STRIPE KEY UPDATE - Ejecutar UNA VEZ
 * 
 * Este script FUERZA la actualización de las claves de Stripe
 * directamente en la base de datos usando UPDATE directo.
 * 
 * INSTRUCCIONES:
 * 1. Subir a la raíz del sitio
 * 2. Acceder: http://tu-sitio.com/FORCE-STRIPE-UPDATE.php
 * 3. ELIMINAR inmediatamente después
 */

// NO requiere WordPress - conexión directa a BD
$db_host = 'localhost'; // Cambiar si es diferente
$db_name = 'nombre_base_datos'; // CAMBIAR ESTO
$db_user = 'usuario_bd'; // CAMBIAR ESTO  
$db_pass = 'contraseña_bd'; // CAMBIAR ESTO
$table_prefix = 'wp_'; // Cambiar si usas otro prefijo

// Claves CORRECTAS (TOKENs)
$correct_publishable = 'pk_live_51LznPeH1P4XrTy9Cm8sxWqRMeRIAlUBqWAaF3FFafArxlS27tVrUrlB6l6HeacCaGTK1YqnNA6gAoMSljFjJPwFk00CzcbBfpd';
$correct_secret = 'sk_live_YOUR_LIVE_SECRET_KEY';

?>
<!DOCTYPE html>
<html>
<head>
    <title>FORCE Stripe Update</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #fff; padding: 20px; }
        .success { background: #28a745; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #dc3545; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #ffc107; color: #000; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #007bff; color: white; padding: 12px 24px; font-size: 16px; border: none; cursor: pointer; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔥 FORCE STRIPE UPDATE</h1>
    
    <?php
    // Solo permitir ejecución si se envía confirmación
    if (!isset($_POST['execute'])) {
        echo '<div class="warning">';
        echo '<h2>⚠️ ANTES DE EJECUTAR</h2>';
        echo '<p><strong>1. Edita este archivo y configura:</strong></p>';
        echo '<ul>';
        echo '<li>$db_host (línea 13)</li>';
        echo '<li>$db_name (línea 14)</li>';
        echo '<li>$db_user (línea 15)</li>';
        echo '<li>$db_pass (línea 16)</li>';
        echo '<li>$table_prefix (línea 17)</li>';
        echo '</ul>';
        echo '<p><strong>2. Haz clic para ejecutar:</strong></p>';
        echo '<form method="post">';
        echo '<input type="hidden" name="execute" value="1">';
        echo '<button type="submit">🔥 FORZAR ACTUALIZACIÓN AHORA</button>';
        echo '</form>';
        echo '</div>';
        exit;
    }
    
    // EJECUCIÓN
    echo '<h2>Ejecutando...</h2>';
    
    try {
        // Conectar a MySQL
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }
        
        echo '<div class="success">✅ Conectado a la base de datos</div>';
        
        // UPDATE 1: Publishable Key
        $sql1 = "UPDATE {$table_prefix}options 
                SET option_value = ? 
                WHERE option_name = 'wptb_stripe_publishable_key'";
        
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("s", $correct_publishable);
        $stmt1->execute();
        
        if ($stmt1->affected_rows > 0) {
            echo '<div class="success">✅ Publishable Key ACTUALIZADA</div>';
        } else {
            // Intentar INSERT si no existe
            $sql_insert1 = "INSERT INTO {$table_prefix}options (option_name, option_value, autoload) 
                           VALUES ('wptb_stripe_publishable_key', ?, 'yes')
                           ON DUPLICATE KEY UPDATE option_value = ?";
            $stmt_ins1 = $conn->prepare($sql_insert1);
            $stmt_ins1->bind_param("ss", $correct_publishable, $correct_publishable);
            $stmt_ins1->execute();
            echo '<div class="success">✅ Publishable Key INSERTADA</div>';
        }
        
        // UPDATE 2: Secret Key
        $sql2 = "UPDATE {$table_prefix}options 
                SET option_value = ? 
                WHERE option_name = 'wptb_stripe_secret_key'";
        
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("s", $correct_secret);
        $stmt2->execute();
        
        if ($stmt2->affected_rows > 0) {
            echo '<div class="success">✅ Secret Key ACTUALIZADA</div>';
        } else {
            $sql_insert2 = "INSERT INTO {$table_prefix}options (option_name, option_value, autoload) 
                           VALUES ('wptb_stripe_secret_key', ?, 'yes')
                           ON DUPLICATE KEY UPDATE option_value = ?";
            $stmt_ins2 = $conn->prepare($sql_insert2);
            $stmt_ins2->bind_param("ss", $correct_secret, $correct_secret);
            $stmt_ins2->execute();
            echo '<div class="success">✅ Secret Key INSERTADA</div>';
        }
        
        // UPDATE 3: Mode
        $mode = 'live';
        $sql3 = "UPDATE {$table_prefix}options 
                SET option_value = ? 
                WHERE option_name = 'wptb_stripe_mode'";
        
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param("s", $mode);
        $stmt3->execute();
        
        if ($stmt3->affected_rows > 0) {
            echo '<div class="success">✅ Modo configurado a LIVE</div>';
        } else {
            $sql_insert3 = "INSERT INTO {$table_prefix}options (option_name, option_value, autoload) 
                           VALUES ('wptb_stripe_mode', ?, 'yes')
                           ON DUPLICATE KEY UPDATE option_value = ?";
            $stmt_ins3 = $conn->prepare($sql_insert3);
            $stmt_ins3->bind_param("ss", $mode, $mode);
            $stmt_ins3->execute();
            echo '<div class="success">✅ Modo INSERTADO</div>';
        }
        
        // VERIFICACIÓN
        echo '<h2>Verificando configuración...</h2>';
        
        $sql_verify = "SELECT option_name, LEFT(option_value, 30) as preview 
                      FROM {$table_prefix}options 
                      WHERE option_name IN ('wptb_stripe_publishable_key', 'wptb_stripe_secret_key', 'wptb_stripe_mode')";
        
        $result = $conn->query($sql_verify);
        
        echo '<pre>';
        while($row = $result->fetch_assoc()) {
            echo $row['option_name'] . ": " . $row['preview'] . "...\n";
        }
        echo '</pre>';
        
        $conn->close();
        
        echo '<div class="success">';
        echo '<h2>✅ ACTUALIZACIÓN COMPLETADA</h2>';
        echo '<p><strong>PRÓXIMOS PASOS:</strong></p>';
        echo '<ol>';
        echo '<li>ELIMINA este archivo INMEDIATAMENTE</li>';
        echo '<li>Limpia la caché de tu sitio</li>';
        echo '<li>Recarga la página de pago (Ctrl+Shift+R)</li>';
        echo '<li>Prueba el flujo de pago</li>';
        echo '</ol>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '<h2>❌ ERROR</h2>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '</div>';
    }
    ?>
</body>
</html>
