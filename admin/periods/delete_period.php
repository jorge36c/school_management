<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

$periodo_id = intval($_GET['id']); // Convertir a entero para mayor seguridad

try {
    // Primero verificamos si el periodo existe
    $stmt = $pdo->prepare("SELECT id FROM periodos_academicos WHERE id = :id");
    $stmt->bindParam(':id', $periodo_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'El periodo no existe']);
        exit;
    }

    // Eliminar el periodo específico usando parámetros nombrados
    $stmt = $pdo->prepare("DELETE FROM periodos_academicos WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $periodo_id, PDO::PARAM_INT);
    $success = $stmt->execute();

    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Periodo eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el periodo']);
    }

} catch(PDOException $e) {
    error_log("Error al eliminar periodo: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al eliminar el periodo: ' . $e->getMessage()
    ]);
} 