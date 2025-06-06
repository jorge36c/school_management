<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['sede_id'])) {
    echo json_encode(['error' => 'Sede ID es requerido']);
    exit;
}

$sede_id = $_GET['sede_id'];

try {
    // Obtener niveles desde la tabla grados en lugar de niveles_sede
    $stmt = $pdo->prepare("
        SELECT DISTINCT nivel 
        FROM grados 
        WHERE sede_id = ? 
        AND estado = 'activo'
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
    
    error_log("Niveles encontrados para sede $sede_id: " . print_r($niveles, true));
    echo json_encode($niveles);

} catch(PDOException $e) {
    error_log("Error en get_niveles.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al obtener los niveles: ' . $e->getMessage()
    ]);
} 