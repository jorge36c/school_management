<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../includes/config.php';

// Verificar parámetros
if (!isset($_GET['id']) || !isset($_GET['estado'])) {
    $_SESSION['error'] = "Parámetros insuficientes";
    header('Location: list_teachers.php');
    exit();
}

$id = intval($_GET['id']);
$estado = $_GET['estado'] === 'activo' ? 'activo' : 'inactivo';

try {
    // Actualizar el estado del profesor
    $stmt = $conn->prepare("UPDATE profesores SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $id);
    $stmt->execute();
    
    // Verificar si se actualizó correctamente
    if ($stmt->affected_rows > 0) {
        // Obtener información del profesor para el registro
        $stmt2 = $conn->prepare("SELECT nombre, apellido FROM profesores WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $profesor = $result->fetch_assoc();
        
        // Registrar en el log de actividad
        $descripcion = "Cambio de estado del profesor: " . $profesor['nombre'] . " " . $profesor['apellido'] . " a " . $estado;
        $usuario_id = $_SESSION['admin_id'];
        $fecha = date('Y-m-d H:i:s');
        
        $stmt3 = $conn->prepare("INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha) 
                                VALUES ('profesores', ?, 'actualizar', ?, ?, ?)");
        $stmt3->bind_param("isis", $id, $descripcion, $usuario_id, $fecha);
        $stmt3->execute();
        
        $_SESSION['success'] = "Estado del profesor actualizado correctamente a " . ucfirst($estado);
    } else {
        $_SESSION['error'] = "No se pudo actualizar el estado del profesor";
    }
} catch (Exception $e) {
    error_log("Error al actualizar estado del profesor: " . $e->getMessage());
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

// Redireccionar de vuelta a la lista
header('Location: list_teachers.php');
exit();