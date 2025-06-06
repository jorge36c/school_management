<?php
require_once '../../../config/database.php';

$sede_id = $_GET['sede_id'] ?? 0;
$nivel = $_GET['nivel'] ?? '';

if ($sede_id && $nivel) {
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
    
    header('Content-Type: application/json');
    echo json_encode($grados);
} 