<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $nuevoEstado = isset($_GET['estado']) ? $_GET['estado'] : 'inactivo';
    
    if (!$id) {
        throw new Exception('ID de asignatura no proporcionado');
    }

    if (!in_array($nuevoEstado, ['activo', 'inactivo'])) {
        throw new Exception('Estado no válido');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Verificar que la asignatura existe
    $stmt = $pdo->prepare("SELECT nombre FROM asignaturas WHERE id = ?");
    $stmt->execute([$id]);
    $asignatura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asignatura) {
        throw new Exception('La asignatura no existe');
    }

    // Actualizar el estado de la asignatura
    $stmt = $pdo->prepare("
        UPDATE asignaturas 
        SET estado = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$nuevoEstado, $id]);

    // Registrar en el log de actividad
    $stmt = $pdo->prepare("
        INSERT INTO actividad_log 
        (usuario_id, accion, tabla, registro_id, descripcion, fecha) 
        VALUES (?, ?, 'asignaturas', ?, ?, CURRENT_TIMESTAMP)
    ");
    $accion = $nuevoEstado === 'inactivo' ? 'deshabilitar' : 'habilitar';
    $descripcion = ucfirst($accion) . " asignatura: " . $asignatura['nombre'];
    $stmt->execute([$_SESSION['admin_id'], $accion, $id, $descripcion]);

    // Confirmar transacción
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Asignatura ' . ($nuevoEstado === 'inactivo' ? 'deshabilitada' : 'habilitada') . ' correctamente'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en delete_materia.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al cambiar el estado de la asignatura: ' . $e->getMessage()
    ]);
}
?>
