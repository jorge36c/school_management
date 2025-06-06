<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Verificar si los parámetros están presentes
if (!isset($_GET['id']) || !isset($_GET['estado'])) {
    header('Location: list_students.php');
    exit();
}

$id = $_GET['id'];
$nuevoEstado = $_GET['estado'];

try {
    // Actualizar el estado del estudiante en la base de datos
    $sql = "UPDATE estudiantes SET estado = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nuevoEstado, $id]);

    // Redirigir a la lista de estudiantes con un mensaje de éxito
    $_SESSION['mensaje'] = "Estado del estudiante actualizado con éxito.";
    header('Location: list_students.php');
    exit();
} catch (PDOException $e) {
    // Redirigir a la lista de estudiantes con un mensaje de error
    $_SESSION['error'] = "Error al actualizar el estado del estudiante: " . $e->getMessage();
    header('Location: list_students.php');
    exit();
}
?>
