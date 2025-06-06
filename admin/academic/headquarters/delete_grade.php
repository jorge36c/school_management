<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if(!isset($_SESSION['admin_id'])) { 
    header('Location: ../../../auth/login.php'); 
    exit();
}

require_once '../../../config/database.php';

// Verificar que sea una petición POST y que tengamos un ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grado_id'])) {
    $id = $_POST['grado_id'];
    $sede_id = $_POST['sede_id'];
    $nivel = strtolower($_POST['nivel']); // Convertir a minúsculas para coincidir con la BD

    try {
        // Eliminar el grado
        $stmt = $pdo->prepare("DELETE FROM grados WHERE id = ?");
        if ($stmt->execute([$id])) {
            // Éxito - Redirigir de vuelta con mensaje de éxito
            header("Location: view_level.php?sede_id=$sede_id&nivel=$nivel&success=1");
            exit();
        } else {
            // Error en la eliminación
            throw new Exception("No se pudo eliminar el grado");
        }
    } catch (Exception $e) {
        // Error - Redirigir de vuelta con mensaje de error
        header("Location: view_level.php?sede_id=$sede_id&nivel=$nivel&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no es POST o no hay ID, redirigir
    header('Location: view_level.php?error=invalid_request');
    exit();
}
?> 