<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: ../../users/list_students.php?error=id_no_proporcionado');
    exit();
}

try {
    $pdo->beginTransaction();

    // Primero verificamos si el estudiante existe
    $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
    $stmt->execute([$id]);
    $estudiante = $stmt->fetch();

    if (!$estudiante) {
        throw new Exception('Estudiante no encontrado');
    }

    // Eliminar el estudiante
    $delete_stmt = $pdo->prepare("DELETE FROM estudiantes WHERE id = ?");
    if (!$delete_stmt->execute([$id])) {
        throw new Exception('Error al eliminar el estudiante');
    }

    // Registrar en el log
    $descripcion = sprintf(
        "Eliminación del estudiante: %s %s", 
        $estudiante['nombre'], 
        $estudiante['apellido']
    );

    $log_stmt = $pdo->prepare("
        INSERT INTO actividad_log 
        (tabla, registro_id, accion, descripcion, usuario_id) 
        VALUES 
        (?, ?, ?, ?, ?)
    ");
    
    if (!$log_stmt->execute(['estudiantes', $id, 'eliminar', $descripcion, $_SESSION['admin_id']])) {
        throw new Exception('Error al registrar la actividad');
    }

    $pdo->commit();
    header('Location: ../../users/list_students.php?success=1');
    exit();

} catch(Exception $e) {
    $pdo->rollBack();
    header('Location: ../../users/list_students.php?error=' . urlencode($e->getMessage()));
    exit();
}
?> 