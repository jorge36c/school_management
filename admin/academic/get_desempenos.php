<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if(!isset($_GET['asignatura_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de asignatura no proporcionado']);
    exit;
}

try {
    $asignatura_id = (int)$_GET['asignatura_id'];
    
    if (!$asignatura_id) {
        throw new Exception('ID de asignatura inválido');
    }

    // Debug
    error_log("Buscando desempeños para asignatura_id: " . $asignatura_id);

    $stmt = $pdo->prepare("
        SELECT 
            id,
            tipo,
            descripcion,
            porcentaje
        FROM desempenos 
        WHERE asignatura_id = ? 
        AND estado = 'activo'
        ORDER BY tipo, porcentaje DESC
    ");
    
    $stmt->execute([$asignatura_id]);
    $desempenos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug
    error_log("Desempeños encontrados: " . count($desempenos));

    echo json_encode([
        'success' => true,
        'desempenos' => $desempenos
    ]);

} catch(Exception $e) {
    error_log("Error en get_desempenos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los desempeños: ' . $e->getMessage()
    ]);
}
?> 