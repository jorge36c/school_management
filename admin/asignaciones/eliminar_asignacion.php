<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$asignacion_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$asignacion_id) {
    echo json_encode(['success' => false, 'message' => 'ID de asignación no válido']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Obtener datos de la asignación antes de eliminar
    $stmt = $pdo->prepare("
        SELECT profesor_id, grupo_id, asignatura_id, periodo_id 
        FROM asignaciones_profesor 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $asignacion_id]);
    $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Marcar como inactiva la asignación
    $stmt = $pdo->prepare("
        UPDATE asignaciones_profesor 
        SET estado = 'inactivo' 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $asignacion_id]);
    
    // Registrar en historial
    $stmt = $pdo->prepare("
        INSERT INTO historial_asignaciones 
        (asignacion_id, profesor_id, grupo_id, asignatura_id, periodo_id, tipo_cambio, realizado_por)
        VALUES (:asignacion_id, :profesor_id, :grupo_id, :asignatura_id, :periodo_id, 'eliminacion', :admin_id)
    ");
    
    $stmt->execute([
        'asignacion_id' => $asignacion_id,
        'profesor_id' => $asignacion['profesor_id'],
        'grupo_id' => $asignacion['grupo_id'],
        'asignatura_id' => $asignacion['asignatura_id'],
        'periodo_id' => $asignacion['periodo_id'],
        'admin_id' => $_SESSION['admin_id']
    ]);
    
    $pdo->commit();
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la asignación']);
} 