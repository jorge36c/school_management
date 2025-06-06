<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$profesor_id = filter_input(INPUT_GET, 'profesor_id', FILTER_VALIDATE_INT);
$periodo_id = filter_input(INPUT_GET, 'periodo_id', FILTER_VALIDATE_INT);

if (!$profesor_id || !$periodo_id) {
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            ap.id,
            g.nombre as grupo_nombre,
            a.nombre as asignatura_nombre,
            s.nombre as sede_nombre
        FROM asignaciones_profesor ap
        JOIN grupos g ON ap.grupo_id = g.id
        JOIN asignaturas a ON ap.asignatura_id = a.id
        JOIN sedes s ON g.sede_id = s.id
        WHERE ap.profesor_id = :profesor_id 
        AND ap.periodo_id = :periodo_id
        AND ap.estado = 'activo'
        ORDER BY g.nombre
    ");
    
    $stmt->execute([
        'profesor_id' => $profesor_id,
        'periodo_id' => $periodo_id
    ]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error al obtener asignaciones']);
} 