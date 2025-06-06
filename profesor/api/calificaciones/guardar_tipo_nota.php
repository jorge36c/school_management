<?php
/**
 * Endpoint para guardar tipo de nota
 */

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay parámetros requeridos
if (!isset($_POST['nombre']) || !isset($_POST['porcentaje']) || !isset($_POST['categoria']) || !isset($_POST['asignacion_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

// Incluir archivos necesarios
require_once __DIR__ . '/../../controllers/calificaciones/TiposNotasController.php';
require_once __DIR__ . '/../../../config/database.php';

try {
    // Crear conexión a la base de datos
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Inicializar controlador
    $tiposNotasController = new TiposNotasController($db);
    
    // Obtener datos del formulario
    $nombre = $_POST['nombre'];
    $porcentaje = floatval($_POST['porcentaje']);
    $categoria = $_POST['categoria'];
    $asignacionId = intval($_POST['asignacion_id']);
    
    // Verificar si es edición o creación
    if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == '1' && isset($_POST['tipo_id'])) {
        // Edición de tipo existente
        $tipoId = intval($_POST['tipo_id']);
        $resultado = $tiposNotasController->actualizarTipoNota($tipoId, $nombre, $porcentaje);
    } else {
        // Creación de nuevo tipo
        $resultado = $tiposNotasController->guardarTipoNota($nombre, $porcentaje, $categoria, $asignacionId);
    }
    
    // Devolver resultado
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}