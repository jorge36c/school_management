<?php
// Script para crear una tabla de historial de calificaciones que relacione calificaciones con periodos
require_once 'config/database.php';

try {
    $pdo->beginTransaction();
    
    // Crear tabla historial_calificaciones si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS historial_calificaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            calificacion_id INT NOT NULL,
            estudiante_id INT NOT NULL,
            tipo_nota_id INT NOT NULL,
            periodo_id INT NOT NULL,
            valor DECIMAL(3,1),
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id),
            FOREIGN KEY (periodo_id) REFERENCES periodos_academicos(id),
            INDEX (estudiante_id, periodo_id, tipo_nota_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "Tabla historial_calificaciones creada o verificada correctamente.\n";
    
    // Verificar si existe la columna periodo_actual_id en la tabla calificaciones
    $stmt = $pdo->query("
        SELECT COUNT(*) as existe 
        FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
        AND table_name = 'calificaciones' 
        AND column_name = 'periodo_actual_id'
    ");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no existe, añadir la columna para vincular calificaciones con el periodo actual
    if ($resultado['existe'] == 0) {
        $pdo->exec("
            ALTER TABLE calificaciones 
            ADD COLUMN periodo_actual_id INT NULL,
            ADD INDEX idx_periodo_actual (periodo_actual_id),
            ADD CONSTRAINT fk_calificacion_periodo 
            FOREIGN KEY (periodo_actual_id) REFERENCES periodos_academicos(id)
            ON DELETE SET NULL;
        ");
        echo "Columna periodo_actual_id añadida a la tabla calificaciones.\n";
    } else {
        echo "La columna periodo_actual_id ya existe en la tabla calificaciones.\n";
    }
    
    $pdo->commit();
    
    echo "Estructura de la base de datos actualizada correctamente.\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
