<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['sede_id']) || !isset($_GET['nivel'])) {
    echo json_encode(['error' => 'Sede ID y Nivel son requeridos']);
    exit;
}

$sede_id = $_GET['sede_id'];
$nivel = $_GET['nivel'];

try {
    error_log("Buscando grados para sede: $sede_id y nivel: $nivel");
    
    $stmt = $pdo->prepare("
        SELECT id, nombre
        FROM grados 
        WHERE sede_id = ? 
        AND nivel = ?
        AND estado = 'activo'
        ORDER BY nombre
    ");
    
    $stmt->execute([$sede_id, $nivel]);
    $grados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Grados encontrados: " . print_r($grados, true));
    echo json_encode($grados);

} catch(PDOException $e) {
    error_log("Error en get_grados.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al obtener los grados: ' . $e->getMessage()
    ]);
} 