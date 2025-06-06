<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['sede_id']) || !isset($_GET['nivel'])) {
    echo json_encode(['error' => 'Sede ID y Nivel son requeridos']);
    exit;
}

$sede_id = $_GET['sede_id'];
$nivel = $_GET['nivel'];

try {
    $stmt = $pdo->prepare("
        SELECT id, nombre
        FROM grados 
        WHERE sede_id = ? 
        AND nivel = ?
        AND estado = 'activo'
        ORDER BY 
            CASE 
                WHEN nombre LIKE '%primero%' THEN 1
                WHEN nombre LIKE '%segundo%' THEN 2
                WHEN nombre LIKE '%tercero%' THEN 3
                WHEN nombre LIKE '%cuarto%' THEN 4
                WHEN nombre LIKE '%quinto%' THEN 5
                WHEN nombre LIKE '%sexto%' THEN 6
                WHEN nombre LIKE '%séptimo%' THEN 7
                WHEN nombre LIKE '%octavo%' THEN 8
                WHEN nombre LIKE '%noveno%' THEN 9
                WHEN nombre LIKE '%décimo%' THEN 10
                WHEN nombre LIKE '%once%' THEN 11
                ELSE 99
            END,
            nombre
    ");
    
    $stmt->execute([$sede_id, $nivel]);
    $grados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($grados);

} catch(PDOException $e) {
    error_log("Error en get_grados.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al obtener los grados: ' . $e->getMessage()
    ]);
} 