<?php
// Script para verificar si existe una tabla de historial de calificaciones
require_once 'config/database.php';

try {
    echo "Verificando tablas para historial de calificaciones:\n\n";
    
    // Verificar si existe una tabla de historial_calificaciones
    $stmt = $pdo->query("
        SELECT COUNT(*) as existe 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'historial_calificaciones'
    ");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "¿Existe tabla historial_calificaciones? " . ($resultado['existe'] > 0 ? "SÍ\n" : "NO\n");
    
    // Verificar estructura de calificaciones
    echo "\nEstructura de la tabla calificaciones:\n";
    $stmt = $pdo->query("DESCRIBE calificaciones");
    while ($columna = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $columna['Field'] . " (" . $columna['Type'] . ")\n";
    }
    
    // Verificar si hay alguna tabla de relación entre periodos y calificaciones
    echo "\nTablas que podrían tener relación con periodos y calificaciones:\n";
    $stmt = $pdo->query("
        SHOW TABLES LIKE '%periodo%'
    ");
    while ($tabla = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . array_values($tabla)[0] . "\n";
    }
    
    // Consultar la estructura de periodos_academicos
    echo "\nEstructura de la tabla periodos_academicos:\n";
    $stmt = $pdo->query("DESCRIBE periodos_academicos");
    while ($columna = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $columna['Field'] . " (" . $columna['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
