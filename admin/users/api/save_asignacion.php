<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    foreach ($data['grados'] as $grado_id) {
        // Verificar si ya existe la asignación
        $stmt = $pdo->prepare("
            SELECT id FROM asignaciones_profesor 
            WHERE profesor_id = ? AND grado_id = ? AND asignatura_id = ? AND periodo_id = ?
        ");
        $stmt->execute([
            $data['profesor_id'],
            $grado_id,
            $data['asignatura_id'],
            $data['periodo_id']
        ]);
        
        if (!$stmt->fetch()) {
            // Si no existe, crear nueva asignación
            $stmt = $pdo->prepare("
                INSERT INTO asignaciones_profesor (
                    profesor_id, 
                    grado_id, 
                    asignatura_id, 
                    periodo_id,
                    fecha_asignacion,
                    asignado_por,
                    estado
                ) VALUES (?, ?, ?, ?, NOW(), ?, 'activo')
            ");
            
            $stmt->execute([
                $data['profesor_id'],
                $grado_id,
                $data['asignatura_id'],
                $data['periodo_id'],
                1 // ID del administrador que realiza la asignación
            ]);
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 