<?php
require_once '../../config/database.php';

// Debug de parámetros recibidos
error_log("GET params: " . print_r($_GET, true));

$sede_id = $_GET['sede_id'] ?? 0;
$nivel = $_GET['nivel'] ?? '';

error_log("Buscando grados para sede_id: $sede_id y nivel: $nivel");

try {
    $stmt = $pdo->prepare("
        SELECT nombre 
        FROM grados 
        WHERE sede_id = ? 
        AND nivel = ? 
        AND estado = 'activo'
        ORDER BY nombre
    ");

    $stmt->execute([$sede_id, $nivel]);
    $grados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Grados encontrados: " . print_r($grados, true));

    foreach ($grados as $grado) {
        echo "<option value='" . htmlspecialchars($grado['nombre']) . "'>" . 
             htmlspecialchars($grado['nombre']) . "</option>";
    }

} catch (PDOException $e) {
    error_log("Error en get_grados.php: " . $e->getMessage());
    echo "<option value=''>Error al cargar grados</option>";
} 