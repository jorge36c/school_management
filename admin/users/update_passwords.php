<?php
/**
 * Script para actualizar contraseñas con hash bcrypt
 * Este script cifra las contraseñas antes de guardarlas en la base de datos
 */

// Definir la función de log
function logAction($message) {
    echo $message . "<br>";
    flush();
    ob_flush();
    return $message;
}

// Iniciar el buffer de salida
ob_start();

// Configuración de la base de datos - Ajustar según corresponda
$db_host = 'localhost';
$db_name = 'iesantan_school_management';
$db_user = 'iesantan';
$db_pass = 'N-5nN37vUb5;qB';

// Variables para estadísticas
$total = 0;
$actualizados = 0;
$errores = 0;
$registros_error = [];

// Intentar conectar a la base de datos
try {
    logAction("<h1>Actualización de Contraseñas con Hash Bcrypt</h1>");
    logAction("Intentando conexión a la base de datos: {$db_host} / {$db_name}");
    $conn = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
    logAction("Conexión exitosa a la base de datos.");
} catch(PDOException $e) {
    logAction("Error de conexión: " . $e->getMessage());
    die();
}

// Confirmar antes de continuar
if (!isset($_POST['confirm']) || $_POST['confirm'] != '1') {
    ?>
    <h2>Confirmación</h2>
    <p>Este script actualizará todas las contraseñas de los estudiantes para que coincidan con su número de documento, usando cifrado bcrypt.</p>
    <p><strong>IMPORTANTE:</strong> Este es un cambio que no se puede deshacer fácilmente. Se recomienda hacer una copia de seguridad de la base de datos antes de continuar.</p>
    <form method="post" action="">
        <input type="hidden" name="confirm" value="1">
        <input type="submit" value="Confirmar y Continuar" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer;">
    </form>
    <?php
    exit;
}

// Obtener todos los estudiantes de la base de datos
try {
    $query = "SELECT id, usuario, documento_numero, nombre, apellido FROM estudiantes";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($estudiantes);
    
    logAction("Se encontraron $total estudiantes para actualizar.");
    
    if ($total == 0) {
        logAction("No se encontraron estudiantes para actualizar. Verifica el nombre de la tabla.");
        exit;
    }
    
    // Iniciar transacción para seguridad
    $conn->beginTransaction();
    
    // Procesar cada estudiante
    foreach ($estudiantes as $estudiante) {
        try {
            $id = $estudiante['id'];
            $documento = $estudiante['documento_numero'];
            $nombre = $estudiante['nombre'] . ' ' . $estudiante['apellido'];
            
            // Verificar que el documento no esté vacío
            if (empty($documento)) {
                logAction("ERROR: El estudiante ID:$id ($nombre) no tiene documento. Se omite.");
                $errores++;
                $registros_error[] = $estudiante;
                continue;
            }
            
            // Crear hash bcrypt de la contraseña
            $hashedPassword = password_hash($documento, PASSWORD_BCRYPT);
            
            // Actualizar la contraseña con hash
            $updateQuery = "UPDATE estudiantes SET password = :password WHERE id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', $hashedPassword);
            $updateStmt->bindParam(':id', $id);
            $updateStmt->execute();
            
            if ($updateStmt->rowCount() > 0) {
                $actualizados++;
                logAction("Estudiante ID:$id - $nombre - Contraseña actualizada con hash bcrypt");
            } else {
                logAction("AVISO: No se actualizó el estudiante ID:$id ($nombre). No se realizaron cambios.");
            }
        } catch (PDOException $e) {
            $errores++;
            $registros_error[] = $estudiante;
            logAction("ERROR en estudiante ID:" . $estudiante['id'] . ": " . $e->getMessage());
        }
    }
    
    // Confirmar transacción si todo está bien
    $conn->commit();
    
    logAction("=== Proceso completado ===");
    logAction("Total de estudiantes: $total");
    logAction("Actualizados correctamente: $actualizados");
    logAction("Errores: $errores");
    
    if ($errores > 0) {
        logAction("=== Registros con errores ===");
        foreach ($registros_error as $error) {
            logAction("ID:" . $error['id'] . " - " . $error['nombre'] . " " . $error['apellido']);
        }
    }
    
    // Mostrar información sobre cómo probar
    logAction("<h2>Información importante</h2>");
    logAction("<p>Las contraseñas han sido actualizadas con hash bcrypt. Ahora puedes probar el inicio de sesión con el documento de un estudiante.</p>");
    logAction("<p>Por ejemplo, para el estudiante " . $estudiantes[0]['nombre'] . " " . $estudiantes[0]['apellido'] . ", usa el documento: " . $estudiantes[0]['documento_numero'] . "</p>");
    
} catch (PDOException $e) {
    // Si hay algún error, revertir la transacción
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    logAction("ERROR FATAL: " . $e->getMessage());
}

// HTML para mostrar resultados de forma más atractiva
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Contraseñas con Hash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .result-box { background-color: #f5f5f5; border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .log-container { max-height: 500px; overflow-y: auto; background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-box">
            <h2>Resumen del Proceso</h2>
            <p>Total de estudiantes: <strong><?php echo $total; ?></strong></p>
            <p>Estudiantes actualizados: <span class="success"><?php echo $actualizados; ?></span></p>
            <p>Errores encontrados: <span class="<?php echo ($errores > 0) ? 'error' : 'success'; ?>"><?php echo $errores; ?></span></p>
        </div>
        
        <div class="result-box">
            <h2>Próximos pasos</h2>
            <p>1. Verifica que puedes iniciar sesión con el número de documento de algún estudiante.</p>
            <p>2. Si el inicio de sesión falla, es posible que el sistema utilice otro método de cifrado o verificación.</p>
        </div>
        
        <p><a href="javascript:history.back()" style="padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; display: inline-block; border-radius: 5px;">Volver</a></p>
    </div>
</body>
</html>