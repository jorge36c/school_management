<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sede_id = $_POST['sede_id'] ?? null;
    $nivel = $_POST['nivel'] ?? null;

    if ($sede_id && $nivel) {
        try {
            $sql = "INSERT INTO niveles_sede (sede_id, nivel) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sede_id, $nivel]);
            
            header("Location: view_headquarters.php?id=" . $sede_id);
            exit();
        } catch(PDOException $e) {
            // Si el nivel ya existe, ignorar el error
            header("Location: view_headquarters.php?id=" . $sede_id);
            exit();
        }
    }
}

header('Location: ../../users/list_headquarters.php');
exit(); 