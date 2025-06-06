<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sede_id = $_POST['sede_id'] ?? null;
    $nivel = $_POST['nivel'] ?? null;
    $nombre = $_POST['nombre'] ?? null;
    $capacidad = $_POST['capacidad'] ?? null;

    if (!$sede_id || !$nivel || !$nombre || !$capacidad) {
        header('Location: view_level.php?sede_id=' . $sede_id . '&nivel=' . $nivel . '&error=datos_incompletos');
        exit();
    }

    try {
        $sql = "INSERT INTO grupos (sede_id, nivel, nombre, capacidad, estado) VALUES (?, ?, ?, ?, 'activo')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sede_id, $nivel, $nombre, $capacidad]);

        header('Location: view_level.php?sede_id=' . $sede_id . '&nivel=' . $nivel . '&success=grupo_creado');
        exit();

    } catch(PDOException $e) {
        error_log("Error al crear grupo: " . $e->getMessage());
        header('Location: view_level.php?sede_id=' . $sede_id . '&nivel=' . $nivel . '&error=error_db');
        exit();
    }
} 