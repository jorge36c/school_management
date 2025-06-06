<?php
/**
 * API para subir fotos de estudiantes
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

// Variables de respuesta
$response = [
    'success' => false,
    'message' => '',
    'foto_url' => ''
];

try {
    // Verificar datos recibidos
    if (!isset($_POST['estudiante_id']) || empty($_POST['estudiante_id'])) {
        throw new Exception('ID de estudiante no proporcionado');
    }
    
    $estudianteId = filter_var($_POST['estudiante_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Verificar archivo subido
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = isset($_FILES['foto']) ? getUploadErrorMessage($_FILES['foto']['error']) : 'No se recibió ningún archivo';
        throw new Exception($errorMsg);
    }
    
    // Conectar a la base de datos
    $db = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8",
        $db_config['user'],
        $db_config['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar que el estudiante existe
    $stmt = $db->prepare("SELECT id FROM estudiantes WHERE id = ?");
    $stmt->execute([$estudianteId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Estudiante no encontrado');
    }
    
    // Crear directorio para fotos si no existe
    $uploadDir = '../../uploads/fotos_estudiantes/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio para las fotos');
        }
    }
    
    // Verificar si ya existe una foto para este estudiante
    $stmt = $db->prepare("SELECT id, foto_url FROM estudiantes_fotos WHERE estudiante_id = ? AND estado = 'activo'");
    $stmt->execute([$estudianteId]);
    $fotoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Eliminar archivo físico anterior si existe
    if ($fotoExistente && !empty($fotoExistente['foto_url'])) {
        $rutaAnterior = '../../' . $fotoExistente['foto_url'];
        if (file_exists($rutaAnterior)) {
            @unlink($rutaAnterior);
        }
    }
    
    // Generar nombre de archivo único
    $filename = 'estudiante_' . $estudianteId . '_' . time() . '.jpg';
    $filePath = $uploadDir . $filename;
    $relativePath = 'uploads/fotos_estudiantes/' . $filename;
    
    // Mover archivo subido
    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $filePath)) {
        throw new Exception('Error al guardar la imagen en el servidor');
    }
    
    // Actualizar o insertar registro en la base de datos
    if ($fotoExistente) {
        // Actualizar registro existente
        $stmt = $db->prepare("UPDATE estudiantes_fotos SET foto_url = ?, fecha_creacion = NOW() WHERE id = ?");
        $result = $stmt->execute([$relativePath, $fotoExistente['id']]);
    } else {
        // Insertar nuevo registro
        $stmt = $db->prepare("INSERT INTO estudiantes_fotos (estudiante_id, foto_url, estado) VALUES (?, ?, 'activo')");
        $result = $stmt->execute([$estudianteId, $relativePath]);
    }
    
    if (!$result) {
        throw new Exception('Error al actualizar la base de datos');
    }
    
    // Respuesta exitosa
    $response['success'] = true;
    $response['message'] = 'Foto actualizada correctamente';
    $response['foto_url'] = $relativePath;
    
} catch (Exception $e) {
    // Capturar cualquier error
    $response['message'] = $e->getMessage();
    
    // Registrar error en log
    error_log("Error al subir foto: " . $e->getMessage());
} finally {
    // Devolver respuesta JSON
    echo json_encode($response);
}

/**
 * Obtiene un mensaje descriptivo para los errores de subida de archivos
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "El archivo excede el tamaño máximo permitido por el servidor";
        case UPLOAD_ERR_FORM_SIZE:
            return "El archivo excede el tamaño máximo permitido por el formulario";
        case UPLOAD_ERR_PARTIAL:
            return "El archivo se subió parcialmente";
        case UPLOAD_ERR_NO_FILE:
            return "No se seleccionó ningún archivo";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Falta la carpeta temporal en el servidor";
        case UPLOAD_ERR_CANT_WRITE:
            return "Error al escribir el archivo en el disco";
        case UPLOAD_ERR_EXTENSION:
            return "Una extensión de PHP detuvo la subida del archivo";
        default:
            return "Error desconocido al subir el archivo";
    }
}