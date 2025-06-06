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

    // Validar datos recibidos
    if (!isset($_POST['id']) || !isset($_POST['ano_lectivo_id']) || 
        !isset($_POST['numero_periodo']) || !isset($_POST['fecha_inicio']) || 
        !isset($_POST['fecha_fin']) || !isset($_POST['porcentaje'])) {
        throw new Exception('Todos los campos son requeridos');
    }

    // Validar que el periodo existe y obtener su estado actual
    $stmt = $pdo->prepare("
        SELECT estado_periodo, ano_lectivo_id 
        FROM periodos_academicos 
        WHERE id = ? AND estado = 'activo'
    ");
    $stmt->execute([$_POST['id']]);
    $periodo_actual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$periodo_actual) {
        throw new Exception('El periodo no existe o está inactivo');
    }

    // Si se está cambiando el año lectivo y el periodo está en curso
    if ($periodo_actual['estado_periodo'] === 'en_curso' && 
        $periodo_actual['ano_lectivo_id'] != $_POST['ano_lectivo_id']) {
        throw new Exception('No se puede cambiar el año lectivo de un periodo en curso');
    }

    // Actualizar el periodo
    $stmt = $pdo->prepare("
        UPDATE periodos_academicos 
        SET ano_lectivo_id = ?,
            numero_periodo = ?,
            fecha_inicio = ?,
            fecha_fin = ?,
            porcentaje_calificacion = ?
        WHERE id = ?
    ");

    $result = $stmt->execute([
        $_POST['ano_lectivo_id'],
        $_POST['numero_periodo'],
        $_POST['fecha_inicio'],
        $_POST['fecha_fin'],
        $_POST['porcentaje'],
        $_POST['id']
    ]);

    if (!$result) {
        throw new Exception('Error al actualizar el periodo');
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Periodo actualizado correctamente']);

} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al actualizar periodo: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 