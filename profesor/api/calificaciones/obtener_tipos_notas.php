<?php
/**
 * Endpoint para obtener tipos de notas
 */

// Verificar si es una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    // Si no es AJAX, devolver error en formato JSON
    echo json_encode([
        'success' => false,
        'message' => 'Método de acceso no permitido'
    ]);
    exit;
}

// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay parámetros requeridos
if (!isset($_GET['asignacion_id']) && (!isset($_GET['es_multigrado']) || !isset($_GET['materia_id']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Parámetros insuficientes'
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
    
    // Obtener parámetros
    $asignacionId = isset($_GET['asignacion_id']) ? intval($_GET['asignacion_id']) : 0;
    $esMultigrado = isset($_GET['es_multigrado']) && $_GET['es_multigrado'] == '1';
    $nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
    $sedeId = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : 0;
    $materiaId = isset($_GET['materia_id']) ? intval($_GET['materia_id']) : 0;
    
    // Obtener tipos de notas
    $resultado = $tiposNotasController->obtenerTiposNotas($asignacionId, $esMultigrado, $nivel, $sedeId, $materiaId);
    
    // Devolver resultado
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}