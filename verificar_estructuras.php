<?php
// Verificar la estructura de tablas para el sistema de calificaciones

require_once 'config/database.php';

echo "=== VERIFICACIÓN DE ESTRUCTURAS DE TABLAS ===\n\n";

// Verificar tabla de calificaciones
echo "1. Tabla calificaciones:\n";
try {
    $resultado = $pdo->query("DESCRIBE calificaciones")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($resultado as $columna) {
        echo "- " . $columna['Field'] . " (" . $columna['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error al verificar tabla calificaciones: " . $e->getMessage() . "\n";
}

// Verificar tabla historial_calificaciones
echo "\n2. Tabla historial_calificaciones:\n";
try {
    $resultado = $pdo->query("DESCRIBE historial_calificaciones")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($resultado as $columna) {
        echo "- " . $columna['Field'] . " (" . $columna['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error al verificar tabla historial_calificaciones: " . $e->getMessage() . "\n";
}

// Verificar periodos
echo "\n3. Periodos académicos:\n";
try {
    $resultado = $pdo->query("
        SELECT id, nombre, estado_periodo, estado 
        FROM periodos_academicos 
        WHERE estado = 'activo' 
        ORDER BY id DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($resultado as $periodo) {
        echo "- ID: {$periodo['id']}, Nombre: {$periodo['nombre']}, Estado: {$periodo['estado_periodo']}\n";
    }
} catch (Exception $e) {
    echo "Error al verificar periodos: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";
