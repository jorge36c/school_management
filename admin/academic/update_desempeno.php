<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    // Debug: Imprimir los datos recibidos
    error_log("Datos POST recibidos: " . print_r($_POST, true));

    // Validar y sanitizar los datos de entrada
    $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
    $asignatura_id = isset($_POST['asignatura_id']) ? filter_var($_POST['asignatura_id'], FILTER_VALIDATE_INT) : null;
    $periodo_id = isset($_POST['periodo_id']) ? filter_var($_POST['periodo_id'], FILTER_VALIDATE_INT) : null;
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $porcentaje = isset($_POST['porcentaje']) ? filter_var($_POST['porcentaje'], FILTER_VALIDATE_FLOAT) : null;

    // Debug: Imprimir los datos procesados
    error_log("Datos procesados: " . print_r([
        'id' => $id,
        'asignatura_id' => $asignatura_id,
        'periodo_id' => $periodo_id,
        'tipo' => $tipo,
        'descripcion' => $descripcion,
        'porcentaje' => $porcentaje
    ], true));

    // Validaciones
    if ($id === null || $id === false) {
        throw new Exception('ID del desempeño inválido');
    }

    if ($asignatura_id === null || $asignatura_id === false) {
        throw new Exception('ID de asignatura inválido');
    }

    if ($periodo_id === null || $periodo_id === false) {
        throw new Exception('ID de periodo inválido');
    }

    if (!in_array($tipo, ['cognitivo', 'procedimental', 'actitudinal'])) {
        throw new Exception('Tipo de desempeño inválido');
    }

    if (empty($descripcion)) {
        throw new Exception('La descripción no puede estar vacía');
    }

    if ($porcentaje === false || $porcentaje === null || $porcentaje < 0 || $porcentaje > 100) {
        throw new Exception('Porcentaje inválido');
    }

    // Verificar que el desempeño existe
    $stmt = $pdo->prepare("SELECT 1 FROM desempenos WHERE id = ? AND estado = 'activo'");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('El desempeño no existe o está inactivo');
    }

    // Actualizar el desempeño
    $stmt = $pdo->prepare("
        UPDATE desempenos 
        SET tipo = ?, 
            descripcion = ?, 
            porcentaje = ?,
            fecha_modificacion = CURRENT_TIMESTAMP
        WHERE id = ? 
        AND asignatura_id = ? 
        AND periodo_id = ?
        AND estado = 'activo'
    ");

    $result = $stmt->execute([
        $tipo,
        $descripcion,
        $porcentaje,
        $id,
        $asignatura_id,
        $periodo_id
    ]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Desempeño actualizado correctamente'
        ]);
    } else {
        throw new Exception('No se pudo actualizar el desempeño');
    }

} catch (Exception $e) {
    error_log("Error al actualizar desempeño: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 