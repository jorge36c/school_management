<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$sede_id = $_GET['sede_id'] ?? null;
$nivel = $_GET['nivel'] ?? null;
$grado = $_GET['grado'] ?? null;

if (!$sede_id || !$nivel) {
    die(json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']));
}

try {
    $sql = "
        SELECT DISTINCT 
            e.id,
            e.nombre,
            e.apellido,
            g.nombre as grado
        FROM estudiantes e
        JOIN grados g ON e.grado_id = g.id
        WHERE g.sede_id = ?
        AND g.nivel = ?
        AND e.estado = 'activo'
    ";
    
    $params = [$sede_id, $nivel];
    
    if ($grado) {
        $sql .= " AND g.nombre = ?";
        $params[] = $grado;
    }
    
    $sql .= " ORDER BY e.apellido, e.nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'estudiantes' => $estudiantes
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 