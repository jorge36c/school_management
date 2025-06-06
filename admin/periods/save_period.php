<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $pdo->beginTransaction();    // 0. Obtener el periodo actual que está en curso para guardar su historial
    $stmt = $pdo->prepare("
        SELECT id 
        FROM periodos_academicos 
        WHERE ano_lectivo_id = ? 
        AND estado_periodo = 'en_curso'
        LIMIT 1
    ");
    $stmt->execute([$_POST['ano_lectivo_id']]);
    $periodo_anterior = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($periodo_anterior) {
        // Guardar historial de calificaciones del periodo que se va a cerrar
        $stmt = $pdo->prepare("
            INSERT INTO historial_calificaciones (
                calificacion_id, estudiante_id, tipo_nota_id, periodo_id, valor
            )
            SELECT 
                c.id, c.estudiante_id, c.tipo_nota_id, :periodo_id, c.valor
            FROM calificaciones c
            INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
            WHERE c.estado = 'activo' AND c.valor IS NOT NULL
        ");
        $stmt->bindValue(':periodo_id', $periodo_anterior['id'], PDO::PARAM_INT);
        $stmt->execute();
        $calificaciones_guardadas = $stmt->rowCount();
        error_log("Guardadas {$calificaciones_guardadas} calificaciones en historial para periodo_id: {$periodo_anterior['id']}");
        
        // Actualizar el campo periodo_actual_id en las calificaciones existentes
        $stmt = $pdo->prepare("
            UPDATE calificaciones SET periodo_actual_id = :periodo_id
            WHERE estado = 'activo'
        ");
        $stmt->bindValue(':periodo_id', $periodo_anterior['id'], PDO::PARAM_INT);
        $stmt->execute();
    }

    // 1. Finalizamos cualquier periodo que esté en curso para este año lectivo
    $stmt = $pdo->prepare("
        UPDATE periodos_academicos 
        SET estado_periodo = 'finalizado' 
        WHERE ano_lectivo_id = ? 
        AND estado_periodo = 'en_curso'
    ");
    $stmt->execute([$_POST['ano_lectivo_id']]);

    // 2. Insertamos el nuevo periodo como "en_curso"
    $stmt = $pdo->prepare("
        INSERT INTO periodos_academicos (
            ano_lectivo_id, 
            numero_periodo, 
            nombre, 
            fecha_inicio, 
            fecha_fin, 
            porcentaje_calificacion,
            estado_periodo,
            estado
        ) VALUES (?, ?, ?, ?, ?, ?, 'en_curso', 'activo')
    ");

    $stmt->execute([
        $_POST['ano_lectivo_id'],
        $_POST['numero_periodo'],
        $_POST['nombre'],
        $_POST['fecha_inicio'],
        $_POST['fecha_fin'],
        $_POST['porcentaje_calificacion']
    ]);    $nuevo_id = $pdo->lastInsertId();
    error_log("Nuevo periodo creado con ID: " . $nuevo_id);

    // 3. Inicializar las notas de manera más eficiente y segura
    try {        // Ya no inactivamos todas las calificaciones para preservar la historia
        // pero sí creamos nuevas para el nuevo periodo
        
        // Obtenemos las asignaciones de profesores que vinculan grados con materias
        // Esto nos asegura que solo creemos calificaciones para materias asignadas a cada grado
        $stmt = $pdo->prepare("
            SELECT e.id as estudiante_id, tn.id as tipo_nota_id
            FROM estudiantes e
            JOIN grados g ON e.grado_id = g.id
            JOIN asignaciones_profesor ap ON g.id = ap.grado_id
            JOIN tipos_notas tn ON tn.asignacion_id = ap.id
            WHERE e.estado = 'Activo'
            AND ap.estado = 'activo'
            AND tn.estado = 'activo'
        ");
        $stmt->execute();
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);          if (count($asignaciones) > 0) {
            // Preparamos la inserción de nuevas calificaciones vacías para el nuevo periodo
            $stmt = $pdo->prepare("
                INSERT INTO calificaciones (estudiante_id, tipo_nota_id, valor, estado, fecha_registro, periodo_actual_id) 
                VALUES (:estudiante_id, :tipo_nota_id, NULL, 'activo', NOW(), :periodo_id)
            ");
            
            $contadorInserciones = 0;
            foreach ($asignaciones as $asignacion) {
                $stmt->bindValue(':estudiante_id', $asignacion['estudiante_id'], PDO::PARAM_INT);
                $stmt->bindValue(':tipo_nota_id', $asignacion['tipo_nota_id'], PDO::PARAM_INT);
                $stmt->bindValue(':periodo_id', $nuevo_id, PDO::PARAM_INT);
                $stmt->execute();
                $contadorInserciones += $stmt->rowCount();
            }
            
            error_log("Notas inicializadas para el nuevo periodo con ID: {$nuevo_id}. Total de inserciones: {$contadorInserciones}");
        } else {
            error_log("No hay asignaciones de materias a estudiantes para inicializar notas");
        }
    } catch (Exception $ex) {
        // Solo registramos el error, pero permitimos que la transacción continúe
        error_log("Error al inicializar notas: " . $ex->getMessage());
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al guardar periodo: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar el periodo: ' . $e->getMessage()]);
}