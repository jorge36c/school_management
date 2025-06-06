<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID no proporcionado');
    }

    $stmt = $pdo->prepare("
        UPDATE desempenos 
        SET estado = 'inactivo' 
        WHERE id = ?
    ");
    
    $stmt->execute([$_GET['id']]);
    
    echo json_encode(['success' => true]);

} catch(Exception $e) {
    error_log("Error en delete_desempeno.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 