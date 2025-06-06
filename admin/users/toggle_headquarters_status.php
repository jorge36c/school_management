<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    if (!isset($_GET['id'], $_GET['estado'])) {
        throw new Exception('Datos incompletos');
    }

    $stmt = $pdo->prepare("UPDATE sedes SET estado = ? WHERE id = ?");
    $result = $stmt->execute([$_GET['estado'], $_GET['id']]);

    if ($result) {
        header('Location: list_headquarters.php');
    } else {
        throw new Exception('Error al actualizar el estado');
    }

} catch (Exception $e) {
    error_log("Error en toggle_headquarters_status.php: " . $e->getMessage());
    header('Location: list_headquarters.php?error=' . urlencode($e->getMessage()));
}