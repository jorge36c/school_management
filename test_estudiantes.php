<?php
// Script para probar la carga de estudiantes
require_once 'config/database.php';
require_once 'profesor/models/EstudianteModel.php';

try {
    echo "Probando carga de estudiantes:\n";
    
    // Crear instancia del modelo
    $estudianteModel = new EstudianteModel($pdo);
    
    // Probar con un ID de grado válido
    $gradoId = 1; // Asegúrate de que este ID exista en tu base de datos
    
    echo "Buscando estudiantes para grado_id: $gradoId\n";
    $estudiantes = $estudianteModel->obtenerEstudiantesPorGrado($gradoId);
    
    echo "Resultados: " . count($estudiantes) . " estudiantes encontrados\n\n";
    
    if (count($estudiantes) > 0) {
        echo "Primeros 3 estudiantes:\n";
        $counter = 0;
        foreach ($estudiantes as $estudiante) {
            echo "- ID: " . $estudiante['id'] . ", Nombre: " . $estudiante['nombres'] . " " . $estudiante['apellidos'] . "\n";
            $counter++;
            if ($counter >= 3) break;
        }
    } else {
        echo "No se encontraron estudiantes para el grado especificado.\n";
        
        // Verificar estructura de la tabla estudiantes
        $stmt = $pdo->query('DESCRIBE estudiantes');
        echo "\nColumnas de la tabla 'estudiantes':\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
