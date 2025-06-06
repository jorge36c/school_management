<?php
require_once '../../config/database.php';

try {
    $pdo->beginTransaction();
    
    $ids = [6, 7, 8];
    
    $stmt = $pdo->prepare("
        UPDATE asignaturas 
        SET estado = 'inactivo', 
            updated_at = CURRENT_TIMESTAMP 
        WHERE id IN (?, ?, ?)
    ");
    
    $stmt->execute($ids);
    
    $pdo->commit();
    
    echo "Asignaturas eliminadas correctamente";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage();
} 