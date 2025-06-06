<?php
require_once '../../vendor/autoload.php';
require_once '../../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Obtener parámetros
$periodo_id = $_GET['periodo_id'] ?? null;
$grado_id = $_GET['grado_id'] ?? null;
$estudiante_id = $_GET['estudiante_id'] ?? null;

if (!$periodo_id || !$grado_id || !$estudiante_id) {
    die('Parámetros incompletos');
}

// Capturar el HTML del boletín
ob_start();
include 'boletin_template.php';
$html = ob_get_clean();

// Configurar DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Inter');

$dompdf = new Dompdf($options);

// Cargar el HTML
$dompdf->loadHtml($html);

// Configurar el papel
$dompdf->setPaper('A4', 'portrait');

// Renderizar el PDF
$dompdf->render();

// Generar nombre del archivo
$filename = "boletin_{$estudiante_id}_{$periodo_id}.pdf";

// Enviar el PDF al navegador
$dompdf->stream($filename, array("Attachment" => true)); 