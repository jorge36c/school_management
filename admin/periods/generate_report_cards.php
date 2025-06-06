<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';
require_once 'BoletinController.php';

// Validar parámetros requeridos
$sede_id = filter_var($_GET['sede_id'], FILTER_VALIDATE_INT);
$nivel = filter_var($_GET['nivel'], FILTER_SANITIZE_STRING);
$grado = filter_var($_GET['grado'], FILTER_SANITIZE_STRING);
$periodo_id = filter_var($_GET['periodo_id'], FILTER_VALIDATE_INT);

if (!$sede_id || !$nivel || !$grado || !$periodo_id) {
    die('Parámetros inválidos');
}

try {
    // Configurar el entorno
    setlocale(LC_ALL, 'es_ES.UTF-8');
    date_default_timezone_set('America/Bogota');
    
    // Inicializar el controlador
    $controller = new BoletinController($pdo);
    
    // Preparar los parámetros
    $params = [
        'sede_id' => $sede_id,
        'nivel' => $nivel,
        'grado' => $grado,
        'periodo_id' => $periodo_id
    ];

    // Obtener estudiantes del grado
    $stmt = $pdo->prepare("
        SELECT e.id 
        FROM estudiantes e
        JOIN grados g ON e.grado_id = g.id
        WHERE g.sede_id = ? 
        AND g.nivel = ? 
        AND g.nombre = ?
        AND e.estado = 'activo'
        ORDER BY e.apellido, e.nombre
    ");
    $stmt->execute([$sede_id, $nivel, $grado]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($estudiantes)) {
        throw new Exception('No se encontraron estudiantes en este grado');
    }

    // Configurar DOMPDF
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->setPaper('A4', 'portrait');

    // Generar PDF combinado para todos los estudiantes
    $html = '';
    foreach ($estudiantes as $estudiante_id) {
        $params['estudiante_id'] = $estudiante_id;
        
        // Capturar el HTML del boletín
        ob_start();
        $controller->generarBoletinIndividual($params);
        $html .= ob_get_clean();
        
        // Agregar salto de página entre estudiantes
        $html .= '<div style="page-break-after: always;"></div>';
    }

    // Cargar HTML completo
    $dompdf->loadHtml($html);

    // Renderizar PDF
    $dompdf->render();

    // Generar nombre del archivo
    $fecha = date('Y-m-d');
    $filename = "boletines_{$grado}_{$fecha}.pdf";

    // Enviar el PDF al navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    echo $dompdf->output();

} catch (Exception $e) {
    error_log("Error generando boletines: " . $e->getMessage());
    die("Error: " . $e->getMessage());
} 