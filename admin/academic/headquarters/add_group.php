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
    $nombre = $_POST['nombre'] ?? null;
    $grado = $_POST['grado'] ?? null;
    $capacidad = $_POST['capacidad'] ?? 40;

    if ($sede_id && $nivel && $nombre && $grado) {
        try {
            $sql = "INSERT INTO grupos (sede_id, nivel, nombre, grado, capacidad) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sede_id, $nivel, $nombre, $grado, $capacidad]);
            
            header("Location: view_level.php?sede_id=" . $sede_id . "&nivel_id=" . $_POST['nivel_id']);
            exit();
        } catch(PDOException $e) {
            $error = "Error al crear el grupo: " . $e->getMessage();
        }
    }
}

header('Location: ../../users/list_headquarters.php');
exit(); 