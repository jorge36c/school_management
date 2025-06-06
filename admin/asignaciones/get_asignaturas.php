<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar que sea administrador
if (!isAdmin()) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$profesor_id = filter_input(INPUT_GET, 'profesor_id', FILTER_VALIDATE_INT);

if (!$profesor_id) {
    echo json_encode(['error' => 'ID de profesor no válido']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nombre 
        FROM asignaturas 
        WHERE profesor_id = :profesor_id 
        AND estado = 'activo'
    ");
    
    $stmt->execute(['profesor_id' => $profesor_id]);
    $asignaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($asignaturas);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error al obtener asignaturas']);
} 