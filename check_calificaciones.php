<?php
// Verificación completa de la estructura de calificaciones y su funcionamiento
require_once 'config/database.php';
require_once 'profesor/models/CalificacionModel.php';

echo "===== VERIFICACIÓN DEL SISTEMA DE CALIFICACIONES =====\n\n";

try {
    // 1. Verificar estructura de la tabla calificaciones
    echo "1. Estructura de la tabla calificaciones:\n";
    $stmt = $pdo->query('DESCRIBE calificaciones');
    $columnas = [];
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columnas[] = $row['Field'] . ' | ' . $row['Type'];
    }
    
    if (empty($columnas)) {
        echo "No se encontró la tabla calificaciones\n";
    } else {
        echo implode("\n", $columnas) . "\n\n";
    }
    
    // 2. Verificar si existe la columna periodo_actual_id
    $stmt = $pdo->query("
        SELECT COUNT(*) as existe 
        FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
        AND table_name = 'calificaciones' 
        AND column_name = 'periodo_actual_id'
    ");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "2. ¿Existe la columna periodo_actual_id? " . 
         ($resultado['existe'] > 0 ? "SÍ" : "NO") . "\n\n";
    
    // 3. Verificar si existe la tabla historial_calificaciones
    echo "3. Verificando tabla historial_calificaciones:\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as existe 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'historial_calificaciones'
    ");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "¿Existe la tabla historial_calificaciones? " . 
         ($resultado['existe'] > 0 ? "SÍ" : "NO") . "\n\n";
    
    // 4. Verificar periodo activo
    echo "4. Período académico activo:\n";
    $stmt = $pdo->query("
        SELECT id, nombre, estado_periodo
        FROM periodos_academicos 
        WHERE estado_periodo = 'en_curso'
        AND estado = 'activo'
        LIMIT 1
    ");
    $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($periodo) {
        echo "Periodo activo: ID {$periodo['id']}, Nombre: {$periodo['nombre']}\n\n";
    } else {
        echo "No hay periodo académico activo configurado\n\n";
    }
    
} catch (PDOException $e) {
    echo "Error en la verificación: " . $e->getMessage();
}
?>
