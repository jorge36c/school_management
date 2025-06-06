<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    // Obtener años lectivos activos
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nombre,
            DATE_FORMAT(fecha_inicio, '%d/%m/%Y') as fecha_inicio,
            DATE_FORMAT(fecha_fin, '%d/%m/%Y') as fecha_fin
        FROM anos_lectivos 
        WHERE estado = 'activo'
        ORDER BY nombre DESC
    ");
    $stmt->execute();
    $anos_lectivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($anos_lectivos)) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay años lectivos disponibles. Por favor, cree un año lectivo primero.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'anos_lectivos' => $anos_lectivos
    ]);

} catch(PDOException $e) {
    error_log("Error en get_anos_lectivos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los años lectivos'
    ]);
}
?> 