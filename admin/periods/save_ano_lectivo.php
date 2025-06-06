<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Validar datos
    if (!isset($_POST['nombre']) || !isset($_POST['fecha_inicio']) || !isset($_POST['fecha_fin'])) {
        throw new Exception('Todos los campos son requeridos');
    }

    // Validar formato del nombre
    if (!preg_match('/^Año Lectivo [0-9]{4}$/', $_POST['nombre'])) {
        throw new Exception('El nombre debe tener el formato: Año Lectivo YYYY');
    }

    // Validar que no exista otro año lectivo con el mismo nombre
    $stmt = $pdo->prepare("SELECT id FROM anos_lectivos WHERE nombre = ?");
    $stmt->execute([$_POST['nombre']]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe un año lectivo con ese nombre');
    }

    // Insertar nuevo año lectivo
    $stmt = $pdo->prepare("
        INSERT INTO anos_lectivos (
            nombre, 
            fecha_inicio, 
            fecha_fin, 
            estado
        ) VALUES (?, ?, ?, 'activo')
    ");

    $stmt->execute([
        $_POST['nombre'],
        $_POST['fecha_inicio'],
        $_POST['fecha_fin']
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Año lectivo creado correctamente']);

} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al guardar año lectivo: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 