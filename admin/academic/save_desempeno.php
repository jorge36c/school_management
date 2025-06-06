<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Verificar la sesión del administrador
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Debug: Imprimir los datos recibidos
error_log("Datos POST recibidos: " . print_r($_POST, true));

try {
    $pdo->beginTransaction();

    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $asignatura_id = $_POST['asignatura_id'];
    $tipo = $_POST['tipo'];
    $descripcion = $_POST['descripcion'];
    $porcentaje = $_POST['porcentaje'];

    if ($id) {
        // Actualizar
        $stmt = $pdo->prepare("
            UPDATE desempenos 
            SET tipo = ?, descripcion = ?, porcentaje = ?
            WHERE id = ? AND estado = 'activo'
        ");
        $stmt->execute([$tipo, $descripcion, $porcentaje, $id]);
    } else {
        // Insertar nuevo
        $stmt = $pdo->prepare("
            INSERT INTO desempenos (tipo, descripcion, porcentaje, asignatura_id, estado)
            VALUES (?, ?, ?, ?, 'activo')
        ");
        $stmt->execute([$tipo, $descripcion, $porcentaje, $asignatura_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en save_desempeno.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 