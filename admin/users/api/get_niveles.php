<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['sede_id'])) {
    echo json_encode(['error' => 'Sede ID es requerido']);
    exit;
}

$sede_id = $_GET['sede_id'];

try {
    $stmt = $pdo->prepare("
        SELECT nivel 
        FROM niveles_sede 
        WHERE sede_id = ?
        ORDER BY 
            CASE nivel
                WHEN 'preescolar' THEN 1
                WHEN 'primaria' THEN 2
                WHEN 'secundaria' THEN 3
                WHEN 'media' THEN 4
            END
    ");
    
    $stmt->execute([$sede_id]);
    $niveles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($niveles);

} catch(PDOException $e) {
    error_log("Error en get_niveles.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al obtener los niveles: ' . $e->getMessage()
    ]);
} 