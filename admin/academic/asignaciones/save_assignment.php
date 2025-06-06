<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Manejar tanto datos de formulario como JSON
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $profesor_id = $input['profesor_id'] ?? null;
            $grado_id = $input['grado_id'] ?? null;
            $materia_id = $input['materia_id'] ?? null; // ID de la asignatura
            $sede_id = $input['sede_id'] ?? null;
        } else {
            $profesor_id = $_POST['profesor_id'] ?? null;
            $grado_id = $_POST['grado_id'] ?? null;
            $materia_id = $_POST['materia_id'] ?? null;
            $sede_id = $_POST['sede_id'] ?? null;
        }

        // Validar datos requeridos
        if (!$profesor_id || !$grado_id || !$materia_id) {
            throw new Exception('Todos los campos son requeridos');
        }

        // Verificar que el grado esté activo
        $stmt = $pdo->prepare("
            SELECT id, nivel FROM grados 
            WHERE id = ? AND estado = 'activo'
        ");
        $stmt->execute([$grado_id]);
        $grado = $stmt->fetch();
        
        if (!$grado) {
            throw new Exception('El grado seleccionado no es válido');
        }

        // Obtener información de la asignatura
        $stmt = $pdo->prepare("
            SELECT id, nombre FROM asignaturas 
            WHERE id = ? AND estado = 'activo'
        ");
        $stmt->execute([$materia_id]);
        $asignatura = $stmt->fetch();
        
        if (!$asignatura) {
            throw new Exception('La asignatura seleccionada no es válida');
        }

        // Buscar si existe la materia correspondiente en la tabla materias
        $stmt = $pdo->prepare("
            SELECT id FROM materias 
            WHERE nombre = ? AND estado = 'activo'
        ");
        $stmt->execute([$asignatura['nombre']]);
        $materia = $stmt->fetch();
        
        // Si no existe, crearla
        if (!$materia) {
            $stmt = $pdo->prepare("
                INSERT INTO materias (nombre, estado, created_at) 
                VALUES (?, 'activo', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$asignatura['nombre']]);
            $materia_id_real = $pdo->lastInsertId();
        } else {
            $materia_id_real = $materia['id'];
        }

        // Verificar si ya existe la asignación
        $stmt = $pdo->prepare("
            SELECT id FROM asignaciones_profesor 
            WHERE profesor_id = ? AND grado_id = ? AND materia_id = ? AND estado = 'activo'
        ");
        $stmt->execute([$profesor_id, $grado_id, $materia_id_real]);
        
        if ($stmt->fetch()) {
            throw new Exception('Esta materia ya está asignada a este profesor en este grado');
        }

        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Crear la asignación en asignaciones_profesor
        $stmt = $pdo->prepare("
            INSERT INTO asignaciones_profesor 
            (profesor_id, grado_id, materia_id, estado) 
            VALUES (?, ?, ?, 'activo')
        ");
        $stmt->execute([$profesor_id, $grado_id, $materia_id_real]);
        
        // Confirmar transacción
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Asignación guardada correctamente',
            'data' => [
                'profesor_id' => $profesor_id,
                'grado_id' => $grado_id,
                'asignatura' => $asignatura['nombre'],
                'nivel' => $grado['nivel'],
                'materia_id' => $materia_id_real
            ]
        ]);

    } catch (Exception $e) {
        // Revertir cualquier cambio si hay error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
}