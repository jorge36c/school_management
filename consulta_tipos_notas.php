<?php
// Script para detallar la estructura de la tabla tipos_notas y realizar algunas consultas
require_once 'config/database.php';

try {
    echo "ESTRUCTURA DE LA TABLA TIPOS_NOTAS:\n\n";
    $stmt = $pdo->query("DESCRIBE tipos_notas");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columnas as $columna) {
        echo "- {$columna['Field']} ({$columna['Type']})";
        if ($columna['Key'] === 'PRI') echo " [PRIMARY KEY]";
        if ($columna['Null'] === 'NO') echo " [NOT NULL]";
        echo "\n";
    }
    
    echo "\n\nEJEMPLOS DE REGISTROS EN TIPOS_NOTAS:\n\n";
    $stmt = $pdo->query("SELECT * FROM tipos_notas LIMIT 5");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($tipos) > 0) {
        // Mostrar encabezados de columnas
        echo implode("\t| ", array_keys($tipos[0])) . "\n";
        echo str_repeat("-", 100) . "\n";
        
        // Mostrar datos
        foreach ($tipos as $tipo) {
            echo implode("\t| ", array_map(function($val) { 
                return $val === null ? 'NULL' : $val; 
            }, $tipo)) . "\n";
        }
    } else {
        echo "No hay registros en la tabla tipos_notas\n";
    }
    
    echo "\n\nRELACIONES ENTRE TIPOS_NOTAS Y PERIODOS:\n\n";
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'tipos_notas' 
            AND column_name = 'periodo_id'
        ) AS tiene_periodo_id
    ");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "¿La tabla tipos_notas tiene un campo periodo_id? " . ($resultado['tiene_periodo_id'] ? 'SÍ' : 'NO') . "\n";
    
    // Intentar encontrar otra relación posible con periodos
    echo "\nBuscando columnas que podrían estar relacionadas con periodos:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM tipos_notas WHERE Field LIKE '%periodo%'");
    $columnas_periodo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($columnas_periodo) > 0) {
        foreach ($columnas_periodo as $col) {
            echo "- Encontrada columna: {$col['Field']}\n";
        }
    } else {
        echo "No se encontraron columnas relacionadas con periodos en tipos_notas\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
