<?php
session_start();
require_once '../../config/database.php';
require_once '../../controllers/calificaciones/CalificacionesController.php';

header('Content-Type: application/json');

if (!isset($_SESSION['profesor_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    // Obtener datos del cuerpo de la petición
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verificar datos necesarios
    if (!isset($data['notas']) || !is_array($data['notas']) || empty($data['notas'])) {
        throw new Exception('No se recibieron notas para guardar');
    }
      $controller = new CalificacionesController($pdo);
    
    // Guardar calificaciones
    $resultado = $controller->guardarCalificacionesMultiple($data['notas']);
    
    echo json_encode($resultado);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}