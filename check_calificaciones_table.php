<?php
// Script para verificar la estructura de la tabla calificaciones
require_once 'config/database.php';

try {
    echo "Verificando estructura de la tabla calificaciones:\n";
    
    $stmt = $pdo->query('DESCRIBE calificaciones');
    echo "Columnas de la tabla 'calificaciones':\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nEjemplo de datos en la tabla calificaciones:\n";
    $stmt = $pdo->query('SELECT * FROM calificaciones LIMIT 3');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- ID: " . $row['id'] . ", Estudiante ID: " . $row['estudiante_id'] . 
             ", Tipo Nota ID: " . $row['tipo_nota_id'] . ", Valor: " . $row['valor'];
        
        // Verificar si existe la columna periodo_id
        if (isset($row['periodo_id'])) {
            echo ", Periodo ID: " . $row['periodo_id'];
        } else {
            echo ", No hay columna periodo_id";
        }
        
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
