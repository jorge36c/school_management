<?php
// Archivo: C:\xampp\htdocs\school_management\admin\academic\headquarters\get_teaching_type.php
require_once '../../../includes/config.php';

// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);
$sede_id = $data['sede_id'] ?? null;
$nivel = $data['nivel'] ?? null;

if (!$sede_id || !$nivel) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']);
    exit();
}

try {
    // Verificar si ya existe una configuración para este nivel en esta sede
    $stmt = $conn->prepare("
        SELECT tipo_ensenanza, observaciones
        FROM niveles_configuracion
        WHERE sede_id = ? AND nivel = ?
    ");
    $stmt->bind_param('is', $sede_id, $nivel);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $config = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'tipo_ensenanza' => $config['tipo_ensenanza'],
            'observaciones' => $config['observaciones']
        ]);
    } else {
        // Verificar si hay configuración a nivel de sede
        $stmt = $conn->prepare("
            SELECT tipo_ensenanza
            FROM sedes
            WHERE id = ?
        ");
        $stmt->bind_param('i', $sede_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sede = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'tipo_ensenanza' => $sede['tipo_ensenanza'] ?? 'unigrado',
            'observaciones' => ''
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>