<?php
session_start();
if(!isset($_SESSION['admin_id'])) { header('Location: ../../../auth/login.php'); exit(); }

require_once '../../../config/database.php';

$sede_id = $_GET['sede_id'] ?? null;

if ($sede_id) {
    try {
        $sql = "SELECT DISTINCT nivel FROM niveles_sede WHERE sede_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sede_id]);
        $niveles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($niveles);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} 