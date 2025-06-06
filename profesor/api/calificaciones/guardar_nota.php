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
    // Obtener datos del cuerpo de la petición o del formulario POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
    }
    
    // Verificar datos necesarios
    if (!isset($data['estudiante_id']) || !isset($data['tipo_nota_id']) || !isset($data['valor'])) {
        throw new Exception('Datos incompletos para guardar nota');
    }
    
    $controller = new CalificacionesController($pdo);
    
    // Guardar calificación
    $resultado = $controller->guardarCalificacion(
        $data['estudiante_id'],
        $data['tipo_nota_id'],
        $data['valor']
    );
    
    echo json_encode($resultado);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}