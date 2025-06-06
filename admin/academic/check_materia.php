<?php
session_start();
require_once '../../config/database.php';

try {
    // Validar datos
    if (!isset($_POST['profesor_id']) || !isset($_POST['grupo_id']) || !isset($_POST['asignatura_id'])) {
        throw new Exception('Faltan datos requeridos');
    }

    $profesor_id = (int)$_POST['profesor_id'];
    $grupo_id = (int)$_POST['grupo_id'];
    $asignatura_id = (int)$_POST['asignatura_id'];

    // Verificar que la asignatura existe y está activa
    $stmt = $pdo->prepare("
        SELECT id, nombre, estado 
        FROM asignaturas 
        WHERE id = ? AND estado = 'activo'
    ");
    $stmt->execute([$asignatura_id]);
    $asignatura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asignatura) {
        throw new Exception('La asignatura no existe o no está activa');
    }

    // Verificar que no exista ya una asignación igual
    $stmt = $pdo->prepare("
        SELECT id 
        FROM profesor_materia 
        WHERE profesor_id = ? 
        AND grupo_id = ? 
        AND asignatura_id = ? 
        AND estado = 'activo'
    ");
    $stmt->execute([$profesor_id, $grupo_id, $asignatura_id]);
    
    if ($stmt->fetch()) {
        throw new Exception('Esta asignatura ya está asignada a este profesor en este grupo');
    }

    // Obtener el periodo académico activo
    $stmt = $pdo->prepare("
        SELECT id 
        FROM periodos_academicos 
        WHERE estado = 'activo' 
        LIMIT 1
    ");
    $stmt->execute();
    $periodo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$periodo) {
        throw new Exception('No hay un periodo académico activo');
    }

    // Insertar en profesor_materia
    $stmt = $pdo->prepare("
        INSERT INTO profesor_materia 
        (profesor_id, grupo_id, asignatura_id, periodo_id, estado) 
        VALUES (?, ?, ?, ?, 'activo')
    ");
    
    $stmt->execute([
        $profesor_id, 
        $grupo_id, 
        $asignatura_id,
        $periodo['id']
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Asignación guardada correctamente',
        'asignatura' => $asignatura['nombre']
    ]);

} catch (Exception $e) {
    error_log("Error en check_materia.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar la asignación: ' . $e->getMessage()]);
}
?>
