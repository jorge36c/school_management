<?php
require_once '../../config/database.php';

try {
    if (!isset($_GET['profesor_id'])) {
        throw new Exception('ID de profesor no proporcionado');
    }

    $stmt = $pdo->prepare("
        SELECT 
            ap.id,
            ap.estado,
            m.nombre as materia_nombre,
            g.nombre as grado_nombre,
            g.nivel,
            s.nombre as sede_nombre,
            g.id as grado_id
        FROM asignaciones_profesor ap
        INNER JOIN materias m ON ap.materia_id = m.id
        INNER JOIN grados g ON ap.grado_id = g.id
        INNER JOIN sedes s ON g.sede_id = s.id
        WHERE ap.profesor_id = ? AND ap.estado = 'activo'
        ORDER BY s.nombre, g.nombre, m.nombre
    ");

    $stmt->execute([$_GET['profesor_id']]);
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($asignaciones);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 