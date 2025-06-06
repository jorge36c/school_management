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
    $sede_id = $_POST['sede_id'];
    $nivel = $_POST['nivel'];
    $nombre = $_POST['nombre'];

    try {
        // Verificar si ya existe un grado con el mismo nombre en este nivel
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM grados WHERE sede_id = ? AND nivel = ? AND nombre = ?");
        $stmt->execute([$sede_id, $nivel, $nombre]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe un grado con ese nombre en este nivel');
        }

        // Insertar nuevo grado
        $stmt = $pdo->prepare("INSERT INTO grados (sede_id, nivel, nombre) VALUES (?, ?, ?)");
        $result = $stmt->execute([$sede_id, $nivel, $nombre]);

        if ($result) {
            // Éxito - Redirigir de vuelta a view_level.php
            header("Location: view_level.php?sede_id=$sede_id&nivel=$nivel&success=grado_creado");
            exit();
        } else {
            throw new Exception('No se pudo crear el grado');
        }
    } catch (Exception $e) {
        // Error - Redirigir de vuelta a view_level.php con mensaje de error
        header("Location: view_level.php?sede_id=$sede_id&nivel=$nivel&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no es POST, redirigir a view_level.php
    header('Location: view_level.php?error=metodo_no_permitido');
    exit();
}
?> 