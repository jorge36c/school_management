<?php
/**
 * Endpoint para eliminar tipo de nota
 */

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Obtener datos enviados
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de tipo de nota no proporcionado'
    ]);
    exit;
}

// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
    
    // Eliminar tipo de nota
    $tipoId = intval($data['id']);
    $resultado = $tiposNotasController->eliminarTipoNota($tipoId);
    
    // Devolver resultado
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}