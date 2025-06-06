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
    $nivel_id = $_POST['nivel_id'];
    $sede_id = $_POST['sede_id'];

    try {
        // Verificar si el nivel existe
        $stmt = $pdo->prepare("SELECT * FROM niveles_sede WHERE id = ? AND sede_id = ?");
        $stmt->execute([$nivel_id, $sede_id]);
        $nivel = $stmt->fetch();

        if (!$nivel) {
            throw new Exception('El nivel no existe o no pertenece a esta sede');
        }

        // Verificar si hay grados asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM grados WHERE sede_id = ? AND nivel = ?");
        $stmt->execute([$sede_id, $nivel['nivel']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception('No se puede eliminar el nivel porque tiene grados asociados');
        }

        // Eliminar el nivel
        $stmt = $pdo->prepare("DELETE FROM niveles_sede WHERE id = ? AND sede_id = ?");
        $result = $stmt->execute([$nivel_id, $sede_id]);

        if (!$result) {
            throw new Exception('Error al eliminar el nivel');
        }

        // Redireccionar con mensaje de éxito
        header("Location: view_headquarters.php?id=$sede_id&success=nivel_eliminado");
        exit();

    } catch (Exception $e) {
        // Redireccionar con mensaje de error
        header("Location: view_headquarters.php?id=$sede_id&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no es POST, redirigir
    header("Location: view_headquarters.php?id=$sede_id&error=metodo_no_permitido");
    exit();
}
?>