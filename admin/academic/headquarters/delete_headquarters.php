<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

// Obtener el ID de la sede
$sede_id = $_GET['id'] ?? null;

if (!$sede_id) {
    header('Location: ../../users/list_headquarters.php');
    exit();
}

try {
    // Verificar si la sede existe
    $sql = "SELECT * FROM sedes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sede_id]);
    $sede = $stmt->fetch();

    if (!$sede) {
        header('Location: ../../users/list_headquarters.php');
        exit();
    }

    // Eliminar la sede
    $sql = "DELETE FROM sedes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sede_id]);

    // Redirigir a la lista de sedes
    header('Location: ../../users/list_headquarters.php');
    exit();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
    // En caso de error, redirigir a la lista de sedes
    header('Location: ../../users/list_headquarters.php');
    exit();
}
?> 