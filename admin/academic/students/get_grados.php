<?php
session_start();
if(!isset($_SESSION['admin_id'])) { header('Location: ../../../auth/login.php'); exit(); }

require_once '../../../config/database.php';

$sede_id = $_GET['sede_id'] ?? null;
$nivel = $_GET['nivel'] ?? null;

if ($sede_id && $nivel) {
    try {
        $sql = "SELECT id, nombre FROM grados WHERE sede_id = ? AND nivel = ? AND estado = 'activo'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sede_id, $nivel]);
        $grados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($grados);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} 