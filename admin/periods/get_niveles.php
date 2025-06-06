<?php
require_once '../../config/database.php';

$sede_id = $_GET['sede_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT DISTINCT nivel 
    FROM grados 
    WHERE sede_id = ? 
    AND estado = 'activo'
    ORDER BY FIELD(nivel, 'Preescolar', 'Básica Primaria', 'Básica Secundaria', 'Media')
");

$stmt->execute([$sede_id]);
while ($row = $stmt->fetch()) {
    echo "<option value='" . htmlspecialchars($row['nivel']) . "'>" . htmlspecialchars($row['nivel']) . "</option>";
} 