<?php
// Archivo: C:\xampp\htdocs\school_management\admin\academic\headquarters\update_teaching_type.php
require_once '../../../includes/config.php';

// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /school_management/auth/login.php');
    exit();
}

// Obtener datos del formulario
$sede_id = $_POST['sede_id'] ?? null;
$nivel = $_POST['nivel'] ?? null;
$tipo_ensenanza = $_POST['tipo_ensenanza'] ?? 'unigrado';
$observaciones = $_POST['observaciones'] ?? '';

if (!$sede_id || !$nivel) {
    header('Location: view_headquarters.php?id=' . $sede_id . '&error=' . urlencode('Faltan parámetros requeridos'));
    exit();
}

try {
    // Verificar si ya existe una configuración para este nivel en esta sede
    $stmt = $conn->prepare("
        SELECT id 
        FROM niveles_configuracion
        WHERE sede_id = ? AND nivel = ?
    ");
    $stmt->bind_param('is', $sede_id, $nivel);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Actualizar la configuración existente
        $config = $result->fetch_assoc();
        $stmt = $conn->prepare("
            UPDATE niveles_configuracion
            SET tipo_ensenanza = ?, observaciones = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssi', $tipo_ensenanza, $observaciones, $config['id']);
        $stmt->execute();
    } else {
        // Crear una nueva configuración
        $stmt = $conn->prepare("
            INSERT INTO niveles_configuracion (sede_id, nivel, tipo_ensenanza, observaciones)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('isss', $sede_id, $nivel, $tipo_ensenanza, $observaciones);
        $stmt->execute();
    }
    
    // Registrar la actividad
    $nombre_nivel = ucfirst($nivel);
    $descripcion = "Actualización de configuración de tipo de enseñanza para $nombre_nivel";
    $stmt = $conn->prepare("
        INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id)
        VALUES ('niveles_configuracion', ?, 'actualizar', ?, ?)
    ");
    $stmt->bind_param('isi', $sede_id, $descripcion, $_SESSION['admin_id']);
    $stmt->execute();
    
    header('Location: view_headquarters.php?id=' . $sede_id . '&success=config_actualizada');
} catch (Exception $e) {
    header('Location: view_headquarters.php?id=' . $sede_id . '&error=' . urlencode('Error: ' . $e->getMessage()));
}
?>