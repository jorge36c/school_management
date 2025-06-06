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
    
    // Validar datos requeridos
    if (empty($data['profesor_id']) || empty($data['grado_id']) || empty($data['materia_id'])) {
        throw new Exception('Faltan datos requeridos');
    }

    // Verificar si ya existe la asignación
    $stmt = $pdo->prepare("
        SELECT id 
        FROM asignaciones_profesor 
        WHERE profesor_id = ? AND grado_id = ? AND materia_id = ? AND estado = 'activo'
    ");
    $stmt->execute([
        $data['profesor_id'],
        $data['grado_id'],
        $data['materia_id']
    ]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'La asignación ya existe']);
        exit;
    }

    // Insertar nueva asignación
    $stmt = $pdo->prepare("
        INSERT INTO asignaciones_profesor 
        (profesor_id, grado_id, materia_id, estado) 
        VALUES (?, ?, ?, 'activo')
    ");

    $stmt->execute([
        $data['profesor_id'],
        $data['grado_id'],
        $data['materia_id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Asignación guardada correctamente']);

} catch (Exception $e) {
    error_log("Error en guardar_asignacion.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al guardar la asignación: ' . $e->getMessage()
    ]);
} 