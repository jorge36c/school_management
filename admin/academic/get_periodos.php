<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if(!isset($_GET['ano_lectivo_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de año lectivo no proporcionado']);
    exit;
}

try {
    // Obtener periodos del año lectivo
    $stmt = $pdo->prepare("
        SELECT 
            id,
            numero_periodo,
            nombre,
            DATE_FORMAT(fecha_inicio, '%d/%m/%Y') as fecha_inicio,
            DATE_FORMAT(fecha_fin, '%d/%m/%Y') as fecha_fin,
            estado_periodo
        FROM periodos_academicos 
        WHERE ano_lectivo_id = ? 
        AND estado = 'activo'
        ORDER BY numero_periodo ASC
    ");
    $stmt->execute([$_GET['ano_lectivo_id']]);
    $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($periodos)) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay periodos disponibles para este año lectivo'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'periodos' => $periodos
    ]);

} catch(PDOException $e) {
    error_log("Error en get_periodos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los periodos'
    ]);
}
?> 