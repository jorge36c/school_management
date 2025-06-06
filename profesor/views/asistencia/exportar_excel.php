<?php
// Verificar la sesión del profesor
session_start();
if (!isset($_SESSION['profesor_id'])) {
    header("Location: /school_management/auth/profesor_login.php");
    exit;
}

// Incluir controlador y conexión
require_once __DIR__ . '/../../controllers/asistencia/AsistenciaController.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../helpers/DateHelper.php';

// Verificar parámetros
if (!isset($_GET['grado_id']) || !isset($_GET['fecha_inicio']) || !isset($_GET['fecha_fin'])) {
    exit('Faltan parámetros necesarios para la exportación');
}

$gradoId = $_GET['grado_id'];
$fechaInicio = $_GET['fecha_inicio'];
$fechaFin = $_GET['fecha_fin'];

// Inicializar controlador y obtener datos
$controller = new AsistenciaController($pdo);
$reporte = $controller->exportarAsistenciaExcel($gradoId, $fechaInicio, $fechaFin);

// Verificar si hay error
if (isset($reporte['error'])) {
    echo $reporte['error'];
    exit;
}

// Configurar cabeceras para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reporte_asistencia_' . date('Ymd') . '.xls"');
header('Cache-Control: max-age=0');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencia</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 5px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .bg-success { background-color: #d4edda; }
        .bg-danger { background-color: #f8d7da; }
        .bg-warning { background-color: #fff3cd; }
        .bg-info { background-color: #d1ecf1; }
    </style>
</head>
<body>
    <h1>Reporte de Asistencia</h1>
    <p>
        <strong>Grupo:</strong> <?php echo $reporte['info_grado']['sede_nombre'] . ' - ' . $reporte['info_grado']['nombre']; ?><br>
        <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($fechaInicio)) . ' a ' . date('d/m/Y', strtotime($fechaFin)); ?><br>
        <strong>Fecha de Generación:</strong> <?php echo date('d/m/Y H:i:s'); ?>
    </p>
    
    <h2>Resumen General</h2>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Calcular totales
            $totalPresentes = 0;
            $totalAusentes = 0;
            $totalTardanzas = 0;
            $totalJustificados = 0;
            
            foreach ($reporte['estadisticas'] as $estadistica) {
                $totalPresentes += $estadistica['presentes'];
                $totalAusentes += $estadistica['ausentes'];
                $totalTardanzas += $estadistica['tardanzas'];
                $totalJustificados += $estadistica['justificados'];
            }
            
            $totalRegistros = $totalPresentes + $totalAusentes + $totalTardanzas + $totalJustificados;
            $porcentajePresentes = ($totalRegistros > 0) ? round(($totalPresentes / $totalRegistros) * 100, 2) : 0;
            $porcentajeAusentes = ($totalRegistros > 0) ? round(($totalAusentes / $totalRegistros) * 100, 2) : 0;
            $porcentajeTardanzas = ($totalRegistros > 0) ? round(($totalTardanzas / $totalRegistros) * 100, 2) : 0;
            $porcentajeJustificados = ($totalRegistros > 0) ? round(($totalJustificados / $totalRegistros) * 100, 2) : 0;
            ?>
            
            <tr class="bg-success">
                <td>Presentes</td>
                <td class="text-center"><?php echo $totalPresentes; ?></td>
                <td class="text-center"><?php echo $porcentajePresentes; ?>%</td>
            </tr>
            <tr class="bg-danger">
                <td>Ausentes</td>
                <td class="text-center"><?php echo $totalAusentes; ?></td>
                <td class="text-center"><?php echo $porcentajeAusentes; ?>%</td>
            </tr>
            <tr class="bg-warning">
                <td>Tardanzas</td>
                <td class="text-center"><?php echo $totalTardanzas; ?></td>
                <td class="text-center"><?php echo $porcentajeTardanzas; ?>%</td>
            </tr>
            <tr class="bg-info">
                <td>Justificados</td>
                <td class="text-center"><?php echo $totalJustificados; ?></td>
                <td class="text-center"><?php echo $porcentajeJustificados; ?>%</td>
            </tr>
            <tr>
                <td><strong>Total</strong></td>
                <td class="text-center"><strong><?php echo $totalRegistros; ?></strong></td>
                <td class="text-center"><strong>100%</strong></td>
            </tr>
        </tbody>
    </table>
    
    <h2>Detalle por Estudiante</h2>
    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Documento</th>
                <th>Presentes</th>
                <th>Ausentes</th>
                <th>Tardanzas</th>
                <th>Justificados</th>
                <th>% Asistencia</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reporte['estadisticas'] as $estadistica): 
                $totalIndividual = $estadistica['presentes'] + $estadistica['ausentes'] + 
                                   $estadistica['tardanzas'] + $estadistica['justificados'];
                $porcentajeAsistencia = ($totalIndividual > 0) ? 
                    round((($estadistica['presentes'] + $estadistica['justificados']) / $totalIndividual) * 100, 2) : 0;
                
                $claseBg = '';
                if ($porcentajeAsistencia >= 90) {
                    $claseBg = 'bg-success';
                } elseif ($porcentajeAsistencia >= 75) {
                    $claseBg = 'bg-warning';
                } else {
                    $claseBg = 'bg-danger';
                }
            ?>
                <tr>
                    <td><?php echo $estadistica['apellidos'] . ', ' . $estadistica['nombres']; ?></td>
                    <td><?php echo $estadistica['documento_tipo'] . ' ' . $estadistica['documento_numero']; ?></td>
                    <td class="text-center"><?php echo $estadistica['presentes']; ?></td>
                    <td class="text-center"><?php echo $estadistica['ausentes']; ?></td>
                    <td class="text-center"><?php echo $estadistica['tardanzas']; ?></td>
                    <td class="text-center"><?php echo $estadistica['justificados']; ?></td>
                    <td class="text-center <?php echo $claseBg; ?>"><?php echo $porcentajeAsistencia; ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p>
        <strong>Nota:</strong> Este reporte fue generado automáticamente por el sistema de control de asistencia.
    </p>
</body>
</html>