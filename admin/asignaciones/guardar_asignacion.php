<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar que sea administrador
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Validar datos recibidos
$profesor_id = filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT);
$grupo_id = filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT);
$asignatura_id = filter_input(INPUT_POST, 'asignatura_id', FILTER_VALIDATE_INT);
$periodo_id = filter_input(INPUT_POST, 'periodo_id', FILTER_VALIDATE_INT);

if (!$profesor_id || !$grupo_id || !$asignatura_id || !$periodo_id) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

try {
    // Verificar si ya existe la asignación
    $stmt = $pdo->prepare("
        SELECT id FROM asignaciones_profesor 
        WHERE profesor_id = :profesor_id 
        AND grupo_id = :grupo_id 
        AND asignatura_id = :asignatura_id 
        AND periodo_id = :periodo_id
    ");
    
    $stmt->execute([
        'profesor_id' => $profesor_id,
        'grupo_id' => $grupo_id,
        'asignatura_id' => $asignatura_id,
        'periodo_id' => $periodo_id
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Esta asignación ya existe']);
        exit();
    }
    
    // Insertar nueva asignación
    $stmt = $pdo->prepare("
        INSERT INTO asignaciones_profesor 
        (profesor_id, grupo_id, asignatura_id, periodo_id, asignado_por) 
        VALUES (:profesor_id, :grupo_id, :asignatura_id, :periodo_id, :admin_id)
    ");
    
    $stmt->execute([
        'profesor_id' => $profesor_id,
        'grupo_id' => $grupo_id,
        'asignatura_id' => $asignatura_id,
        'periodo_id' => $periodo_id,
        'admin_id' => $_SESSION['admin_id']
    ]);
    
    // Registrar en el historial
    $asignacion_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        INSERT INTO historial_asignaciones 
        (asignacion_id, profesor_id, grupo_id, asignatura_id, periodo_id, tipo_cambio, realizado_por)
        VALUES (:asignacion_id, :profesor_id, :grupo_id, :asignatura_id, :periodo_id, 'creacion', :admin_id)
    ");
    
    $stmt->execute([
        'asignacion_id' => $asignacion_id,
        'profesor_id' => $profesor_id,
        'grupo_id' => $grupo_id,
        'asignatura_id' => $asignatura_id,
        'periodo_id' => $periodo_id,
        'admin_id' => $_SESSION['admin_id']
    ]);
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la asignación']);
} 