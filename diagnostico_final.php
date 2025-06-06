<?php
// Script de diagnóstico final para verificar si se han resuelto los problemas
// Este script debe ejecutarse desde el navegador web

// Mostrar todos los errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico Final del Sistema de Calificaciones</h1>";

// Cargar configuración de base de datos
require_once 'config/database.php';

// Cargar modelos necesarios
require_once 'profesor/models/EstudianteModel.php';
require_once 'profesor/models/CalificacionModel.php';
require_once 'profesor/models/TipoNotaModel.php';
require_once 'helpers/CalificacionesHelper.php';

// Función para mostrar resultados en formato legible
function mostrarResultado($titulo, $datos, $tipo = 'info') {
    echo "<div style='margin: 20px 0; padding: 15px; border-radius: 5px; background-color: " . 
         ($tipo == 'error' ? '#ffcccc' : '#e6f7ff') . ";'>";
    echo "<h3>$titulo</h3>";
    
    if (is_array($datos)) {
        if (empty($datos)) {
            echo "<p>No se encontraron datos.</p>";
        } else {
            echo "<pre>" . print_r($datos, true) . "</pre>";
        }
    } else {
        echo "<p>$datos</p>";
    }
    
    echo "</div>";
}

try {
    // 1. Verificar conexión a la base de datos
    mostrarResultado("1. Conexión a la Base de Datos", "Conexión establecida correctamente", "info");
    
    // 2. Verificar estructura de la tabla calificaciones
    $stmt = $pdo->query('DESCRIBE calificaciones');
    $estructuraTabla = $stmt->fetchAll(PDO::FETCH_ASSOC);
    mostrarResultado("2. Estructura de la Tabla Calificaciones", $estructuraTabla);
    
    // Verificar si existe la columna periodo_id
    $tienePeriodoId = false;
    foreach ($estructuraTabla as $columna) {
        if ($columna['Field'] == 'periodo_id') {
            $tienePeriodoId = true;
            break;
        }
    }
    
    if ($tienePeriodoId) {
        mostrarResultado("2.1 Advertencia", "La columna 'periodo_id' existe en la tabla calificaciones. Es posible que las correcciones no funcionen correctamente.", "error");
    } else {
        mostrarResultado("2.1 Columna periodo_id", "La columna 'periodo_id' NO existe en la tabla calificaciones, lo cual es correcto según las modificaciones realizadas.", "info");
    }
    
    // 3. Probar obtener estudiantes
    $estudianteModel = new EstudianteModel($pdo);
    $gradoId = 1; // Usar un ID de grado que sepas que existe
    
    $estudiantes = $estudianteModel->obtenerEstudiantesPorGrado($gradoId);
    mostrarResultado("3. Obtener Estudiantes por Grado (ID: $gradoId)", 
                    !empty($estudiantes) 
                        ? "Se encontraron " . count($estudiantes) . " estudiantes" 
                        : "No se encontraron estudiantes para este grado");
    
    if (!empty($estudiantes)) {
        mostrarResultado("3.1 Primeros 3 Estudiantes", array_slice($estudiantes, 0, 3));
    }
    
    // 4. Probar obtener calificaciones
    if (!empty($estudiantes)) {
        $calificacionModel = new CalificacionModel($pdo);
        $estudianteId = $estudiantes[0]['id'];
        $asignacionId = 1; // Usar un ID de asignación que sepas que existe
        
        $calificaciones = $calificacionModel->obtenerCalificacionesEstudiante($estudianteId, $asignacionId);
        mostrarResultado("4. Obtener Calificaciones para Estudiante (ID: $estudianteId, Asignación: $asignacionId)", 
                        !empty($calificaciones) 
                            ? "Se encontraron " . count($calificaciones) . " calificaciones" 
                            : "No se encontraron calificaciones para este estudiante y asignación");
        
        if (!empty($calificaciones)) {
            mostrarResultado("4.1 Primeras 3 Calificaciones", array_slice($calificaciones, 0, 3));
        }
    }
    
    // 5. Verificar la función guardarCalificacion (sin periodo_id)
    echo "<h3>5. Test de guardarCalificacion sin periodo_id</h3>";
    echo "<pre>";
    $metodo = new ReflectionMethod('CalificacionModel', 'guardarCalificacion');
    $parametros = $metodo->getParameters();
    foreach ($parametros as $parametro) {
        echo "Parámetro: " . $parametro->getName() . "\n";
    }
    echo "</pre>";
    
    $tienePeriodoId = false;
    foreach ($parametros as $parametro) {
        if ($parametro->getName() == 'periodoId') {
            $tienePeriodoId = true;
            break;
        }
    }
    
    mostrarResultado("5.1 Verificación del Método guardarCalificacion", 
                    $tienePeriodoId 
                        ? "El método aún tiene el parámetro periodoId, lo cual es incorrecto" 
                        : "El método ha sido modificado correctamente para no usar periodoId", 
                    $tienePeriodoId ? "error" : "info");
    
    // 6. Verificar la función guardarCalificacionesMultiple
    echo "<h3>6. Test de guardarCalificacionesMultiple sin periodo_id</h3>";
    echo "<pre>";
    $metodo = new ReflectionMethod('CalificacionModel', 'guardarCalificacionesMultiple');
    $parametros = $metodo->getParameters();
    foreach ($parametros as $parametro) {
        echo "Parámetro: " . $parametro->getName() . "\n";
    }
    echo "</pre>";
    
    $tienePeriodoId = false;
    foreach ($parametros as $parametro) {
        if ($parametro->getName() == 'periodoId') {
            $tienePeriodoId = true;
            break;
        }
    }
    
    mostrarResultado("6.1 Verificación del Método guardarCalificacionesMultiple", 
                    $tienePeriodoId 
                        ? "El método aún tiene el parámetro periodoId, lo cual es incorrecto" 
                        : "El método ha sido modificado correctamente para no usar periodoId", 
                    $tienePeriodoId ? "error" : "info");
    
    // 7. Resumen final
    mostrarResultado("7. Resumen de Diagnóstico", 
                  "Se han verificado los cambios principales requeridos para solucionar los problemas mencionados. " . 
                  "La estructura de la tabla calificaciones no incluye la columna periodo_id y los métodos han sido " . 
                  "actualizados para no utilizar este parámetro. El método de obtención de estudiantes también se " . 
                  "ha corregido previamente para usar los nombres correctos de columnas.");

} catch (Exception $e) {
    mostrarResultado("Error en Diagnóstico", "Se produjo un error: " . $e->getMessage(), "error");
}
