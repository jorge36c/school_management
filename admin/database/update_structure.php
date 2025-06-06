<?php
require_once '../../config/database.php';

try {
    $pdo->beginTransaction();

    // Eliminar la foreign key existente
    $pdo->exec("ALTER TABLE estudiantes DROP FOREIGN KEY estudiantes_ibfk_1");
    
    // Eliminar el índice si existe
    $pdo->exec("ALTER TABLE estudiantes DROP INDEX grado_id");
    
    // Crear la nueva foreign key
    $pdo->exec("ALTER TABLE estudiantes 
                ADD CONSTRAINT fk_estudiante_grado 
                FOREIGN KEY (grado_id) 
                REFERENCES grados(id) 
                ON DELETE SET NULL");

    $pdo->commit();
    echo "Estructura de la base de datos actualizada correctamente";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error actualizando la estructura: " . $e->getMessage();
}
?> 