<?php
// Script para verificar la estructura de la tabla estudiantes
require_once 'config/database.php';

try {
    echo "Verificando estructura de la tabla estudiantes:\n";
    
    $stmt = $pdo->query('DESCRIBE estudiantes');
    echo "Columnas de la tabla 'estudiantes':\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nEjemplo de datos en la tabla estudiantes:\n";
    $stmt = $pdo->query('SELECT * FROM estudiantes LIMIT 3');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- ID: " . $row['id'] . ", Nombre: ";
        
        // Verificar si existen las columnas nombre y apellido o nombres y apellidos
        if (isset($row['nombre'])) {
            echo $row['nombre'] . " " . ($row['apellido'] ?? '');
        } elseif (isset($row['nombres'])) {
            echo $row['nombres'] . " " . ($row['apellidos'] ?? '');
        } else {
            echo "Estructura desconocida";
        }
        
        echo ", Grado ID: " . ($row['grado_id'] ?? 'N/A') . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
