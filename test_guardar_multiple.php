<?php
// Script para probar la funcionalidad de guardar múltiples calificaciones
require_once 'config/database.php';
require_once 'profesor/models/CalificacionModel.php';

try {
    echo "Probando guardar múltiples calificaciones:\n";
    
    // Crear instancia del modelo
    $calificacionModel = new CalificacionModel($pdo);
    
    // Datos de prueba (asegúrate de que los IDs existan en tu base de datos)
    $calificaciones = [
        [
            'estudiante_id' => 1,
            'tipo_nota_id' => 1,
            'valor' => 4.5
        ],
        [
            'estudiante_id' => 2,
            'tipo_nota_id' => 1,
            'valor' => 4.0
        ],
        [
            'estudiante_id' => 3,
            'tipo_nota_id' => 1,
            'valor' => 3.8
        ]
    ];
    
    // Guardar calificaciones múltiples
    echo "Guardando " . count($calificaciones) . " calificaciones...\n";
    $resultado = $calificacionModel->guardarCalificacionesMultiple($calificaciones);
    
    echo "Resultado: " . ($resultado['success'] ? "Éxito" : "Error") . " - " . $resultado['message'] . "\n";
    
    if ($resultado['success']) {
        echo "Nuevas: " . $resultado['nuevas'] . ", Actualizadas: " . $resultado['actualizadas'] . "\n";
    }
    
    // Verificar estructura de la tabla calificaciones
    $stmt = $pdo->query('DESCRIBE calificaciones');
    echo "\nColumnas de la tabla 'calificaciones':\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
