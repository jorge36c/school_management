<?php
// Script simplificado para verificar calificaciones por periodo
require_once 'config/database.php';

echo "=== VERIFICACIÓN SIMPLIFICADA DE CALIFICACIONES POR PERIODO ===\n\n";

try {
    // 1. Verificar periodos
    $stmt = $pdo->query("
        SELECT id, nombre, estado_periodo
        FROM periodos_academicos
        WHERE estado = 'activo'
        ORDER BY id ASC
    ");
    $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Periodos académicos encontrados: " . count($periodos) . "\n";
    foreach ($periodos as $periodo) {
        echo "- ID: {$periodo['id']}, Nombre: {$periodo['nombre']}, Estado: {$periodo['estado_periodo']}\n";
    }
    
    echo "\n";
    
    // 2. Verificar calificaciones vinculadas a cada periodo
    foreach ($periodos as $periodo) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM calificaciones 
            WHERE periodo_actual_id = ? 
            AND valor IS NOT NULL
        ");
        $stmt->execute([$periodo['id']]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Periodo {$periodo['nombre']} (ID: {$periodo['id']}): {$resultado['total']} calificaciones con notas\n";
        
        // Muestra algunas calificaciones de ejemplo
        $stmt = $pdo->prepare("
            SELECT 
                c.id, 
                e.nombre as nombre_estudiante, 
                e.apellido as apellido_estudiante,
                tn.nombre as tipo_nota,
                c.valor
            FROM calificaciones c
            JOIN estudiantes e ON c.estudiante_id = e.id
            JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
            WHERE c.periodo_actual_id = ?
            AND c.valor IS NOT NULL
            LIMIT 3
        ");
        $stmt->execute([$periodo['id']]);
        $ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($ejemplos)) {
            echo "Ejemplos de calificaciones para este periodo:\n";
            foreach ($ejemplos as $ejemplo) {
                echo "  - Estudiante: {$ejemplo['nombre_estudiante']} {$ejemplo['apellido_estudiante']}, " .
                     "Tipo: {$ejemplo['tipo_nota']}, Valor: {$ejemplo['valor']}\n";
            }
        } else {
            echo "No se encontraron ejemplos de calificaciones para este periodo.\n";
        }
        
        echo "\n";
    }
    
    // 3. Verificar un estudiante específico
    $estudiante_id = 1; // Ajusta este ID a un estudiante que exista
    echo "Verificando calificaciones para el estudiante ID: {$estudiante_id}\n\n";
    
    foreach ($periodos as $periodo) {
        $stmt = $pdo->prepare("
            SELECT 
                c.valor, 
                tn.nombre as tipo_nota
            FROM calificaciones c
            JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
            WHERE c.estudiante_id = ?
            AND c.periodo_actual_id = ?
            AND c.valor IS NOT NULL
            LIMIT 5
        ");
        $stmt->execute([$estudiante_id, $periodo['id']]);
        $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Periodo {$periodo['nombre']} (ID: {$periodo['id']}): " . count($calificaciones) . " calificaciones\n";
        
        if (!empty($calificaciones)) {
            foreach ($calificaciones as $cal) {
                echo "  - {$cal['tipo_nota']}: {$cal['valor']}\n";
            }
        }
        echo "\n";
    }
    
    echo "=== FIN DE LA VERIFICACIÓN ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
