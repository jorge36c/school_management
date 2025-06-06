<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('ID de asignación no proporcionado');
    }

    $stmt = $pdo->prepare("
        UPDATE asignaciones_profesor 
        SET estado = 'inactivo' 
        WHERE id = ?
    ");
    
    $stmt->execute([$data['id']]);
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 