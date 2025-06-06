<?php
// Script para restaurar las calificaciones del primer periodo académico
require_once 'config/database.php';

echo "=== RESTAURACIÓN DE CALIFICACIONES DEL PRIMER PERIODO ===\n\n";

try {
    $pdo->beginTransaction();
    
    // 1. Identificar el primer periodo (Periodo ID 7 según la verificación)
    $primer_periodo_id = 7; // ID del primer periodo según verificar_estructuras.php
    
    $stmt = $pdo->prepare("
        SELECT id, nombre, estado_periodo 
        FROM periodos_academicos 
        WHERE id = ?
    ");
    $stmt->execute([$primer_periodo_id]);
    $primer_periodo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$primer_periodo) {
        echo "No se encontró el primer periodo académico con ID: {$primer_periodo_id}.\n";
        exit;
    }
    
    echo "Periodo encontrado: ID {$primer_periodo['id']}, Nombre: {$primer_periodo['nombre']}\n";
    echo "Estado del periodo: {$primer_periodo['estado_periodo']}\n\n";
    
    // 2. Verificar si hay calificaciones en el historial para este periodo
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM historial_calificaciones 
        WHERE periodo_id = ?
    ");
    $stmt->execute([$primer_periodo_id]);
    $total_historial = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Calificaciones en historial para el primer periodo: {$total_historial}\n\n";
    
    if ($total_historial == 0) {
        // Si no hay calificaciones en el historial, intentamos restaurar del segundo periodo
        echo "No hay calificaciones en el historial para el primer periodo.\n";
          // 3. Actualizar calificaciones existentes para vincularlas al primer periodo
        // Esto tomará las calificaciones actuales y las vinculará al primer periodo
        $stmt = $pdo->prepare("
            UPDATE calificaciones 
            SET periodo_actual_id = ?
            WHERE valor IS NOT NULL
            AND periodo_actual_id IS NULL
            LIMIT 1000  -- Límite ampliado para abarcar todas las calificaciones
        ");
        $stmt->execute([$primer_periodo_id]);
        $actualizadas = $stmt->rowCount();
        
        echo "Calificaciones vinculadas manualmente al primer periodo: {$actualizadas}\n";
    } else {
        // Si hay calificaciones en el historial, las usamos para restaurar
        echo "Restaurando calificaciones desde el historial...\n";
        
        // Obtener las calificaciones del historial para el primer periodo
        $stmt = $pdo->prepare("
            SELECT 
                estudiante_id, 
                tipo_nota_id, 
                valor
            FROM historial_calificaciones
            WHERE periodo_id = ?
        ");
        $stmt->execute([$primer_periodo_id]);
        $calificaciones_historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $actualizadas = 0;
        $nuevas = 0;
        
        // Procesar cada calificación y actualizarla en la tabla calificaciones
        foreach ($calificaciones_historial as $calif) {
            // Verificar si ya existe una calificación para este estudiante y tipo de nota
            $stmt = $pdo->prepare("
                SELECT id 
                FROM calificaciones
                WHERE estudiante_id = ? 
                AND tipo_nota_id = ?
                LIMIT 1
            ");
            $stmt->execute([$calif['estudiante_id'], $calif['tipo_nota_id']]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existe) {
                // Actualizar la calificación existente
                $stmt = $pdo->prepare("
                    UPDATE calificaciones
                    SET valor = ?, periodo_actual_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$calif['valor'], $primer_periodo_id, $existe['id']]);
                $actualizadas++;
            } else {
                // Crear una nueva calificación
                $stmt = $pdo->prepare("
                    INSERT INTO calificaciones 
                    (estudiante_id, tipo_nota_id, valor, estado, fecha_registro, periodo_actual_id)
                    VALUES (?, ?, ?, 'activo', NOW(), ?)
                ");
                $stmt->execute([
                    $calif['estudiante_id'], 
                    $calif['tipo_nota_id'], 
                    $calif['valor'],
                    $primer_periodo_id
                ]);
                $nuevas++;
            }
        }
        
        echo "Calificaciones actualizadas: {$actualizadas}\n";
        echo "Calificaciones nuevas creadas: {$nuevas}\n";
    }
    
    // 4. Verificar el resultado final
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM calificaciones 
        WHERE periodo_actual_id = ?
        AND valor IS NOT NULL
    ");
    $stmt->execute([$primer_periodo_id]);
    $total_final = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\nTotal de calificaciones ahora vinculadas al primer periodo: {$total_final}\n\n";
    
    // Confirmar cambios
    $pdo->commit();
    echo "¡Cambios guardados exitosamente!\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error durante la restauración: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL PROCESO ===\n";
