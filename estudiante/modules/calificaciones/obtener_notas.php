<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['estudiante_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $asignacion_id = isset($_GET['asignacion_id']) ? intval($_GET['asignacion_id']) : 0;
    
    if ($asignacion_id <= 0) {
        throw new Exception('ID de asignación no válido');
    }

    // Verificar que la asignación corresponda al estudiante
    $stmt = $pdo->prepare("
        SELECT ap.id 
        FROM asignaciones_profesor ap
        INNER JOIN estudiantes e ON e.grado_id = ap.grado_id
        WHERE ap.id = :asignacion_id 
        AND e.id = :estudiante_id 
        AND ap.estado = 'activo'
    ");
    
    $stmt->execute([
        ':asignacion_id' => $asignacion_id,
        ':estudiante_id' => $_SESSION['estudiante_id']
    ]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Asignación no válida para este estudiante');
    }

    // Obtener las notas
    $stmt = $pdo->prepare("
        SELECT 
            tn.nombre,
            tn.porcentaje,
            COALESCE(c.valor, 0) as valor
        FROM tipos_notas tn
        LEFT JOIN calificaciones c ON tn.id = c.tipo_nota_id 
            AND c.estudiante_id = :estudiante_id
        WHERE tn.asignacion_id = :asignacion_id
        AND tn.estado = 'activo'
        ORDER BY tn.id
    ");

    $stmt->execute([
        ':estudiante_id' => $_SESSION['estudiante_id'],
        ':asignacion_id' => $asignacion_id
    ]);
    
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'notas' => $notas
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_notas.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 