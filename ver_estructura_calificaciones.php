<?php
// Script para verificar la estructura de la tabla calificaciones
require_once 'config/database.php';

try {
    echo "Estructura de la tabla calificaciones:\n";
    
    $stmt = $pdo->query('DESCRIBE calificaciones');
    echo "Columnas de la tabla 'calificaciones':\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\n\nEstructura de la tabla tipos_notas:\n";
    $stmt = $pdo->query('DESCRIBE tipos_notas');
    echo "Columnas de la tabla 'tipos_notas':\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\n\nEstructura de la tabla periodos_academicos:\n";
    $stmt = $pdo->query('DESCRIBE periodos_academicos');
    echo "Columnas de la tabla 'periodos_academicos':\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
