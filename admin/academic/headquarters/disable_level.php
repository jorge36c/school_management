<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/school_management/config/database.php';

class APIResponse {
    public static function send($success, $message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
}

// Validaciones principales
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    APIResponse::send(false, 'Método no permitido', null, 405);
}

if (!isset($_SESSION['admin_id'])) {
    APIResponse::send(false, 'No autorizado', null, 401);
}

// Obtener y validar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['nivel_id'])) {
    APIResponse::send(false, 'ID de nivel no proporcionado', null, 400);
}

$nivelId = filter_var($input['nivel_id'], FILTER_VALIDATE_INT);
if (!$nivelId) {
    APIResponse::send(false, 'ID de nivel inválido', null, 400);
}

try {
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Verificar si el nivel existe y está activo
    $stmt = $pdo->prepare("
        SELECT n.*, s.nombre as sede_nombre 
        FROM niveles_educativos n 
        JOIN sedes s ON n.sede_id = s.id 
        WHERE n.id = ? AND n.estado = 'activo'
    ");
    $stmt->execute([$nivelId]);
    $nivel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nivel) {
        APIResponse::send(false, 'Nivel no encontrado o ya está inactivo', null, 404);
    }

    // Verificar estado de los grados
    $stmt = $pdo->prepare("
        UPDATE grados 
        SET estado = 'inactivo', 
            fecha_modificacion = NOW() 
        WHERE nivel_id = ? AND estado = 'activo'
    ");
    $stmt->execute([$nivelId]);
    $gradosAfectados = $stmt->rowCount();

    // Deshabilitar el nivel
    $stmt = $pdo->prepare("
        UPDATE niveles_educativos 
        SET estado = 'inactivo', 
            fecha_modificacion = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$nivelId]);

    // Registrar en el log de actividad
    $stmt = $pdo->prepare("
        INSERT INTO actividad_log (
            tabla, 
            registro_id, 
            accion, 
            descripcion, 
            usuario_id, 
            fecha
        ) VALUES (
            'niveles_educativos',
            ?,
            'deshabilitar',
            ?,
            ?,
            NOW()
        )
    ");

    $descripcion = sprintf(
        'Deshabilitación de nivel educativo: %s en sede %s. %d grados afectados.',
        ucfirst($nivel['nombre']),
        $nivel['sede_nombre'],
        $gradosAfectados
    );
    
    $stmt->execute([
        $nivelId,
        $descripcion,
        $_SESSION['admin_id']
    ]);

    // Confirmar transacción
    $pdo->commit();

    APIResponse::send(true, 'Nivel deshabilitado exitosamente', [
        'nivel_id' => $nivelId,
        'nombre' => $nivel['nombre'],
        'sede' => $nivel['sede_nombre'],
        'grados_afectados' => $gradosAfectados
    ]);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Error de base de datos en disable_level.php: " . $e->getMessage());
    APIResponse::send(false, 'Error al deshabilitar el nivel', null, 500);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Error en disable_level.php: " . $e->getMessage());
    APIResponse::send(false, $e->getMessage(), null, 400);

} finally {
    if (isset($pdo)) {
        Database::disconnect();
    }
}