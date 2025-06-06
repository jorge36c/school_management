<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si estamos en producción
$isProduction = getenv('APP_ENV') === 'production';
if ($isProduction) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

echo "<h2>Prueba de Conexión a Base de Datos</h2>";

try {
    // Incluir el archivo de configuración
    require_once 'config/database.php';
    
    // Verificar la conexión antes de hacer consultas
    $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    
    // Probar la consulta con prepared statement
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM administradores WHERE estado = ?");
    $stmt->execute(['activo']);
    $count = $stmt->fetchColumn();
    
    echo "Usuarios activos encontrados: " . $count . "<br><br>";
    
    // Verificar sesión
    session_start();
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new Exception("Error al iniciar sesión");
    }
    
    // Verificar permisos de escritura en logs
    $logPath = __DIR__ . '/logs';
    if (!is_writable($logPath)) {
        throw new Exception("El directorio de logs no tiene permisos de escritura");
    }
    
    echo "Todas las verificaciones completadas exitosamente.<br>";
    
} catch(PDOException $e) {
    $error = "Error de base de datos: " . ($isProduction ? 'Contacte al administrador' : $e->getMessage());
    error_log($e->getMessage());
    die($error);
} catch(Exception $e) {
    $error = "Error: " . ($isProduction ? 'Contacte al administrador' : $e->getMessage());
    error_log($e->getMessage());
    die($error);
}
?>