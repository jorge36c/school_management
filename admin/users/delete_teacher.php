<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../includes/config.php';

// Verificar parámetros
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Parámetros insuficientes";
    header('Location: list_teachers.php');
    exit();
}

$id = intval($_GET['id']);

try {
    // Primero obtener información del profesor para el registro de actividad
    $stmtInfo = $conn->prepare("SELECT nombre, apellido FROM profesores WHERE id = ?");
    $stmtInfo->bind_param("i", $id);
    $stmtInfo->execute();
    $result = $stmtInfo->get_result();
    $profesor = $result->fetch_assoc();
    
    if (!$profesor) {
        $_SESSION['error'] = "Profesor no encontrado";
        header('Location: list_teachers.php');
        exit();
    }
    
    // Primero eliminar asignaciones relacionadas
    $stmt1 = $conn->prepare("DELETE FROM asignaciones_profesor WHERE profesor_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    
    // Ahora eliminar al profesor
    $stmt2 = $conn->prepare("DELETE FROM profesores WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    
    // Verificar si se eliminó correctamente
    if ($stmt2->affected_rows > 0) {
        // Registrar en el log de actividad
        $descripcion = "Eliminación del profesor: " . $profesor['nombre'] . " " . $profesor['apellido'];
        $usuario_id = $_SESSION['admin_id'];
        $fecha = date('Y-m-d H:i:s');
        
        $stmt3 = $conn->prepare("INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha) 
                               VALUES ('profesores', ?, 'eliminar', ?, ?, ?)");
        $stmt3->bind_param("isis", $id, $descripcion, $usuario_id, $fecha);
        $stmt3->execute();
        
        $_SESSION['success'] = "Profesor eliminado correctamente";
    } else {
        $_SESSION['error'] = "No se pudo eliminar al profesor";
    }
} catch (Exception $e) {
    error_log("Error al eliminar profesor: " . $e->getMessage());
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

// Redireccionar de vuelta a la lista
header('Location: list_teachers.php');
exit();