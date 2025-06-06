<?php
require_once '../../../config/database.php';

try {
    $pdo->beginTransaction();

    // Obtener estudiantes con su último grado asignado
    $stmt = $pdo->query("
        SELECT e.id, e.nombre, e.apellido, 
               MAX(h.fecha_cambio) as ultima_fecha,
               h.grado_nuevo as grado_correcto
        FROM estudiantes e
        LEFT JOIN historial_cambios_grado h ON e.id = h.estudiante_id
        GROUP BY e.id
    ");
    $estudiantes = $stmt->fetchAll();

    foreach ($estudiantes as $estudiante) {
        // Si hay un registro en el historial, usar ese grado
        if ($estudiante['grado_correcto']) {
            $stmt = $pdo->prepare("
                UPDATE estudiantes 
                SET grado_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$estudiante['grado_correcto'], $estudiante['id']]);
        }
        // Si no hay historial, mantener el grado actual
    }

    $pdo->commit();
    echo "Corrección completada exitosamente";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?> 