<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Modificar la verificación para permitir tanto admin como estudiantes
if(!isset($_SESSION['admin_id']) && !isset($_SESSION['estudiante_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    // Obtener el periodo activo del año lectivo actual
    $stmt = $pdo->prepare("
        SELECT 
            pa.id,
            pa.nombre,
            pa.numero_periodo,
            DATE_FORMAT(pa.fecha_inicio, '%Y-%m-%d') as fecha_inicio,
            DATE_FORMAT(pa.fecha_fin, '%Y-%m-%d') as fecha_fin,
            pa.estado_periodo,
            al.nombre as ano_lectivo
        FROM periodos_academicos pa
        INNER JOIN anos_lectivos al ON pa.ano_lectivo_id = al.id
        WHERE pa.estado_periodo = 'en_curso'
        AND al.estado = 'activo'
        LIMIT 1
    ");
    $stmt->execute();
    $periodo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($periodo) {
        echo json_encode([
            'success' => true,
            'periodo' => $periodo
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No hay periodo activo'
        ]);
    }

} catch(PDOException $e) {
    error_log("Error en get_periodo_activo.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el periodo activo'
    ]);
}
?> 