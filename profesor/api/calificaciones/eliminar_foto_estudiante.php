<?php
/**
 * API para eliminar fotos de estudiantes
 */

// Configuraciones iniciales
header('Content-Type: application/json');
ini_set('display_errors', 0);
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para realizar esta acción'
    ]);
    exit;
}

// Incluir configuración de base de datos
require_once '../../config/database.php';

// Obtener datos de la solicitud
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Variables de respuesta
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Verificar datos recibidos
    if (!isset($input['estudiante_id']) || empty($input['estudiante_id'])) {
        throw new Exception('ID de estudiante no proporcionado');
    }
    
    $estudianteId = filter_var($input['estudiante_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Conectar a la base de datos
    $db = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8",
        $db_config['user'],
        $db_config['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar que el estudiante tiene foto
    $stmt = $db->prepare("SELECT id, foto_url FROM estudiantes_fotos WHERE estudiante_id = ? AND estado = 'activo'");
    $stmt->execute([$estudianteId]);
    $foto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$foto) {
        throw new Exception('El estudiante no tiene foto asignada');
    }
    
    // Eliminar archivo físico
    if (!empty($foto['foto_url'])) {
        $fotoPath = '../../' . $foto['foto_url'];
        if (file_exists($fotoPath)) {
            if (!@unlink($fotoPath)) {
                // No lanzar excepción, solo registrar el error
                error_log("No se pudo eliminar el archivo: {$fotoPath}");
            }
        }
    }
    
    // Actualizar estado en la base de datos
    $stmt = $db->prepare("UPDATE estudiantes_fotos SET estado = 'inactivo' WHERE id = ?");
    $result = $stmt->execute([$foto['id']]);
    
    if (!$result) {
        throw new Exception('Error al actualizar la base de datos');
    }
    
    // Respuesta exitosa
    $response['success'] = true;
    $response['message'] = 'Foto eliminada correctamente';
    
} catch (Exception $e) {
    // Capturar cualquier error
    $response['message'] = $e->getMessage();
    
    // Registrar error en log
    error_log("Error al eliminar foto: " . $e->getMessage());
} finally {
    // Devolver respuesta JSON
    echo json_encode($response);
}