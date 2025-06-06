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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['grado_id'];
    $nombre = $_POST['nombre'];
    $nivel = $_POST['nivel'];
    $sede_id = $_POST['sede_id'];

    try {
        // Actualizar el grado
        $stmt = $pdo->prepare("
            UPDATE grados 
            SET nombre = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$nombre, $id]);

        if ($result) {
            // Éxito - Redirigir de vuelta con mensaje de éxito
            header("Location: view_level.php?sede_id=$sede_id&nivel=$nivel&success=grado_actualizado");
            exit();
        } else {
            throw new Exception('No se pudo actualizar el grado');
        }
    } catch (Exception $e) {
        // Error - Redirigir de vuelta con mensaje de error
        header("Location: view_level.php?sede_id=$sede_id&nivel=$nivel&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no es POST, redirigir
    header("Location: view_level.php?sede_id=$sede_id&nivel=$nivel&error=metodo_no_permitido");
    exit();
}
?> 