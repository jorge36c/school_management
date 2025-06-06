<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if(!isset($_POST['id']) || !isset($_POST['estado'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$periodo_id = intval($_POST['id']);
$estado = $_POST['estado'];

// Validar que el estado sea válido
$estados_validos = ['en_curso', 'finalizado', 'cerrado'];
if (!in_array($estado, $estados_validos)) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // Verificar que el periodo existe
    $stmt = $pdo->prepare("SELECT id, ano_lectivo_id FROM periodos_academicos WHERE id = ?");
    $stmt->execute([$periodo_id]);
    $periodo = $stmt->fetch();
    
    if (!$periodo) {
        throw new Exception('El periodo no existe');
    }

    // Si el nuevo estado es 'en_curso', actualizar otros periodos del mismo año lectivo
    if ($estado === 'en_curso') {
        $stmt = $pdo->prepare("
            UPDATE periodos_academicos 
            SET estado_periodo = 'finalizado' 
            WHERE ano_lectivo_id = ? 
            AND id != ? 
            AND estado_periodo = 'en_curso'
        ");
        $stmt->execute([$periodo['ano_lectivo_id'], $periodo_id]);
    }

    // Actualizar el periodo específico
    $stmt = $pdo->prepare("
        UPDATE periodos_academicos 
        SET estado_periodo = :estado 
        WHERE id = :id
    ");
    
    $success = $stmt->execute([
        ':estado' => $estado,
        ':id' => $periodo_id
    ]);

    if ($success) {
        // Confirmar la transacción
        $pdo->commit();

        // Si el estado es 'en_curso', obtener todos los periodos actualizados
        if ($estado === 'en_curso') {
            $stmt = $pdo->prepare("
                SELECT id, estado_periodo 
                FROM periodos_academicos 
                WHERE ano_lectivo_id = ?
            ");
            $stmt->execute([$periodo['ano_lectivo_id']]);
            $periodos_actualizados = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Estado del periodo actualizado correctamente',
                'periodos' => $periodos_actualizados
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Estado del periodo actualizado correctamente'
            ]);
        }
    } else {
        throw new Exception('No se pudo actualizar el estado del periodo');
    }
} catch(Exception $e) {
    // Revertir la transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al actualizar estado del periodo: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al actualizar el estado del periodo: ' . $e->getMessage()
    ]);
} 