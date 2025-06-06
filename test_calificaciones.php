<?php
// Script para probar la funcionalidad de calificaciones
require_once 'config/database.php';
require_once 'profesor/models/CalificacionModel.php';

try {
    echo "Probando funcionalidad de calificaciones:\n";
    
    // Crear instancia del modelo
    $calificacionModel = new CalificacionModel($pdo);
    
    // Probar con IDs válidos (asegúrate de que estos IDs existan en tu base de datos)
    $estudianteId = 1; 
    $asignacionId = 1;
    $tipoNotaId = 1;
    
    // 1. Probar obtener calificaciones de un estudiante
    echo "1. Obteniendo calificaciones para estudiante_id: $estudianteId, asignacion_id: $asignacionId\n";
    $calificaciones = $calificacionModel->obtenerCalificacionesEstudiante($estudianteId, $asignacionId);
    
    echo "Resultados: " . count($calificaciones) . " calificaciones encontradas\n\n";
    
    if (count($calificaciones) > 0) {
        echo "Primeras 3 calificaciones:\n";
        $counter = 0;
        foreach ($calificaciones as $calificacion) {
            echo "- Tipo: " . $calificacion['tipo_nota'] . ", Valor: " . $calificacion['valor'] . ", Fecha: " . $calificacion['fecha'] . "\n";
            $counter++;
            if ($counter >= 3) break;
        }
    } else {
        echo "No se encontraron calificaciones para el estudiante y asignación especificados.\n";
    }
    
    // 2. Probar guardar una calificación
    echo "\n2. Guardando una calificación de prueba (valor 4.5)...\n";
    $resultado = $calificacionModel->guardarCalificacion($estudianteId, $tipoNotaId, 4.5);
    
    echo "Resultado: " . ($resultado['success'] ? "Éxito" : "Error") . " - " . $resultado['message'] . "\n";
    
    // 3. Probar obtener calificaciones para múltiples estudiantes
    echo "\n3. Obteniendo calificaciones para múltiples estudiantes\n";
    $estudianteIds = [1, 2, 3]; // Asegúrate de que estos IDs existan
    $calificacionesMultiple = $calificacionModel->obtenerCalificacionesMultiplesEstudiantes($estudianteIds, $asignacionId);
    
    echo "Resultados: Calificaciones para " . count($calificacionesMultiple) . " estudiantes\n";
    
    foreach ($calificacionesMultiple as $estId => $cals) {
        echo "- Estudiante ID: $estId, Calificaciones: " . count($cals) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
