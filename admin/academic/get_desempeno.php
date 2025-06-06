<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, asignatura_id, periodo_id, tipo, descripcion, porcentaje 
        FROM desempenos 
        WHERE id = ? AND estado = 'activo'
    ");
    
    $stmt->execute([$_GET['id']]);
    $desempeno = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($desempeno) {
        echo json_encode([
            'success' => true,
            'desempeno' => $desempeno
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Desempeño no encontrado'
        ]);
    }

} catch (Exception $e) {
    error_log("Error al obtener desempeño: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el desempeño'
    ]);
}
?> 