<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Verificar parámetros necesarios
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nuevo_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Validar el estado
$estados_permitidos = ['activo', 'inactivo'];

if(!$id || !in_array($nuevo_estado, $estados_permitidos)) {
    header('Location: list_materias.php?error=Parámetros inválidos');
    exit();
}

try {
    // Verificar si la materia existe
    $stmt = $pdo->prepare("SELECT estado FROM asignaturas WHERE id = ?");
    $stmt->execute([$id]);
    $materia = $stmt->fetch();

    if(!$materia) {
        header('Location: list_materias.php?error=Materia no encontrada');
        exit();
    }

    // Actualizar el estado
    $stmt = $pdo->prepare("UPDATE asignaturas SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);

    // Registrar el cambio en el log de actividad
    $admin_id = $_SESSION['admin_id'];
    $log_sql = "INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha) 
                VALUES ('asignaturas', ?, 'actualizar', ?, ?, NOW())";
    $log_stmt = $pdo->prepare($log_sql);
    $detalles = "Cambio de estado de materia a: $nuevo_estado";
    $log_stmt->execute([$id, $detalles, $admin_id]);

    header('Location: list_materias.php?success=1');
    exit();

} catch(PDOException $e) {
    error_log("Error al cambiar estado de materia: " . $e->getMessage());
    header('Location: list_materias.php?error=1');
    exit();
}
?>