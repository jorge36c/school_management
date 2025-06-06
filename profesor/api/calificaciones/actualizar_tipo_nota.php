<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['profesor_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug
    error_log("Datos recibidos en actualizar_tipo_nota: " . print_r($data, true));
    
    // Verificar datos necesarios
    if (!isset($data['nombre']) || !isset($data['porcentaje']) || !isset($data['asignacion_id']) || !isset($data['categoria'])) {
        throw new Exception('Datos incompletos para actualizar tipo de nota');
    }

    // Cargar el controlador
    require_once '../../controllers/calificaciones/TiposNotasController.php';
    $controller = new TiposNotasController($pdo);
    
    // Guardar tipo de nota (crear o actualizar)
    $result = $controller->guardarTipoNota($data, $_SESSION['profesor_id']);
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error en actualizar_tipo_nota.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}