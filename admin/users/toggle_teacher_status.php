<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

if(isset($_GET['id']) && isset($_GET['estado'])) {
    try {
        $id = $_GET['id'];
        $estado = $_GET['estado'];
        
        // Actualizar el estado del profesor
        $stmt = $pdo->prepare("UPDATE profesores SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
        
        // Redirigir con mensaje de éxito
        $mensaje = $estado === 'activo' ? 'reactivado' : 'inhabilitado';
        header("Location: list_teachers.php?message=Profesor $mensaje exitosamente");
        exit();
    } catch(PDOException $e) {
        header('Location: list_teachers.php?error=Error al actualizar el estado del profesor');
        exit();
    }
}

header('Location: list_teachers.php');
exit();
?>