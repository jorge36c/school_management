<?php
require_once '../../../config/database.php';

try {
    $pdo->beginTransaction();

    // Obtener todos los estudiantes que aparecen en múltiples grados
    $stmt = $pdo->query("
        SELECT e.id, e.nombre, e.apellido, e.grado_id,
               MAX(h.fecha_cambio) as ultima_fecha,
               h.grado_nuevo as ultimo_grado
        FROM estudiantes e
        LEFT JOIN historial_cambios_grado h ON e.id = h.estudiante_id
        GROUP BY e.id
        HAVING COUNT(DISTINCT e.grado_id) > 1
    ");
    
    $estudiantes = $stmt->fetchAll();

    foreach ($estudiantes as $estudiante) {
        // Actualizar al estudiante con su último grado asignado
        $grado_correcto = $estudiante['ultimo_grado'] ?? $estudiante['grado_id'];
        
        $update = $pdo->prepare("
            UPDATE estudiantes 
            SET grado_id = ? 
            WHERE id = ?
        ");
        $update->execute([$grado_correcto, $estudiante['id']]);
        
        echo "Estudiante {$estudiante['nombre']} {$estudiante['apellido']} actualizado al grado correcto.<br>";
    }

    $pdo->commit();
    echo "Limpieza completada exitosamente";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?> 