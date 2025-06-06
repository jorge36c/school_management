<?php
// Configuración de la sesión ANTES de iniciar cualquier sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si se usa HTTPS

// Configuración de la base de datos
$db_host = 'localhost';
$db_name = 'school_management';
$db_user = 'root';
$db_pass = '';

// Zona horaria
date_default_timezone_set('America/Bogota');

// Conexión a la base de datos usando mysqli
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Verificar la conexión
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    // Establecer el juego de caracteres
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}

// Variables globales
$base_url = '/school_management';

// Función para verificar si el usuario está autenticado
function checkAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . $GLOBALS['base_url'] . '/auth/login.php');
        exit;
    }
}

// Función para sanitizar entrada
function sanitize($input) {
    global $conn;
    
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    
    return $conn->real_escape_string(trim($input));
}

// Función para registro de actividades
function logActivity($tabla, $registro_id, $accion, $descripcion, $usuario_id) {
    global $conn;
    
    $sql = "INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissi", $tabla, $registro_id, $accion, $descripcion, $usuario_id);
    $stmt->execute();
    $stmt->close();
}
?>