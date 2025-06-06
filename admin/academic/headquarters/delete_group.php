<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

$grupo_id = $_GET['id'] ?? null;
$sede_id = $_GET['sede_id'] ?? null;
$nivel_id = $_GET['nivel_id'] ?? null;

if ($grupo_id && $sede_id && $nivel_id) {
    try {
        $sql = "DELETE FROM grupos WHERE id = ? AND sede_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$grupo_id, $sede_id]);
    } catch(PDOException $e) {
        // Manejar error si es necesario
    }
    
    header("Location: view_level.php?sede_id=" . $sede_id . "&nivel_id=" . $nivel_id);
    exit();
}

header('Location: ../../users/list_headquarters.php');
exit(); 