<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    // Debug: Registrar los datos recibidos
    error_log("DELETE STUDENT - Datos recibidos: " . print_r($_GET, true));

    // Verificar si se recibió el ID y validarlo
    if (!isset($_GET['id'])) {
        throw new Exception('No se proporcionó ID de estudiante');
    }

    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        throw new Exception('ID de estudiante inválido');
    }

    // Debug: Registrar el ID después de la validación
    error_log("DELETE STUDENT - ID validado: " . $id);

    // Iniciar transacción
    $pdo->beginTransaction();

    // Verificar si el estudiante existe
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM estudiantes WHERE id = ?");
    $stmt->execute([$id]);
    $estudiante = $stmt->fetch();

    if (!$estudiante) {
        throw new Exception('Estudiante no encontrado');
    }

    // Debug: Registrar información del estudiante encontrado
    error_log("DELETE STUDENT - Estudiante encontrado: " . print_r($estudiante, true));

    // Eliminar el estudiante
    $stmt = $pdo->prepare("DELETE FROM estudiantes WHERE id = ?");
    $result = $stmt->execute([$id]);

    if (!$result) {
        throw new Exception('Error al eliminar el estudiante');
    }

    // Registrar la actividad
    $detalles = "Eliminación del estudiante: " . $estudiante['nombre'] . ' ' . $estudiante['apellido'];
    $stmt = $pdo->prepare("
        INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id) 
        VALUES ('estudiantes', ?, 'eliminar', ?, ?)
    ");
    $stmt->execute([$id, $detalles, $_SESSION['admin_id']]);

    // Confirmar transacción
    $pdo->commit();

    // Debug: Registrar éxito
    error_log("DELETE STUDENT - Eliminación exitosa del estudiante ID: " . $id);

    header('Location: list_students.php?success=Estudiante eliminado correctamente');
    exit();

} catch (Exception $e) {
    // Revertir cambios si hay error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Debug: Registrar el error
    error_log("Error en delete_student.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    header('Location: list_students.php?error=' . urlencode($e->getMessage()));
    exit();
}
?> 