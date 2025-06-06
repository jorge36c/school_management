<?php
// Verificar la sesión del profesor
session_start();
if (!isset($_SESSION['profesor_id'])) {
    exit('Acceso denegado');
}

// Incluir controlador y conexión
require_once __DIR__ . '/../../controllers/asistencia/AsistenciaController.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../helpers/DateHelper.php';

// Verificar parámetros
if (!isset($_POST['estudiante_id']) || !isset($_POST['fecha_inicio']) || !isset($_POST['fecha_fin'])) {
    exit('<div class="alert alert-danger">Faltan parámetros necesarios</div>');
}

$estudianteId = intval($_POST['estudiante_id']);
$fechaInicio = $_POST['fecha_inicio'];
$fechaFin = $_POST['fecha_fin'];

// Inicializar controlador y obtener datos
$controller = new AsistenciaController($pdo);
$resultado = $controller->obtenerDetalleAsistencia($estudianteId, $fechaInicio, $fechaFin);

// Verificar si hay error
if (!$resultado['exito']) {
    exit('<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . $resultado['error'] . '</div>');
}

// Obtener los datos
$detalle = $resultado['detalle'];
?>

<div class="card border-0">
    <div class="card-header bg-primary text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-user"></i> Información del Estudiante
        </h6>
    </div>
    <div class="card-body bg-light">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="d-flex">
                    <div class="mr-3">
                        <div class="icon-circle bg-primary text-white">
                            <?php echo strtoupper(substr($detalle['estudiante']['nombres'], 0, 1) . substr($detalle['estudiante']['apellidos'], 0, 1)); ?>
                        </div>
                    </div>
                    <div>
                        <h5 class="font-weight-bold text-gray-800 mb-1">
                            <?php echo htmlspecialchars($detalle['estudiante']['apellidos'] . ', ' . $detalle['estudiante']['nombres']); ?>
                        </h5>
                        <p class="mb-0 text-gray-600">
                            <strong>Documento:</strong> <?php echo htmlspecialchars($detalle['estudiante']['documento_tipo'] . ' ' . $detalle['estudiante']['documento_numero']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card bg-gradient-primary text-white h-100">
                    <div class="card-body p-3">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                            Período Analizado
                        </div>
                        <div class="h6 mb-0 font-weight-bold">
                            <?php echo date('d/m/Y', strtotime($detalle['fecha_inicio'])); ?> - 
                            <?php echo date('d/m/Y', strtotime($detalle['fecha_fin'])); ?>
                        </div>
                        <div class="text-xs mt-2">
                            <i class="fas fa-info-circle"></i> 
                            <?php 
                                $diasDiferencia = DateHelper::calcularDiferenciaDias($detalle['fecha_inicio'], $detalle['fecha_fin']);
                                echo "Total: $diasDiferencia días";
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="detalle-container mt-4">
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Fecha</th>
                    <th>Día</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Obtener todas las fechas en el rango
                $inicio = new DateTime($detalle['fecha_inicio']);
                $fin = new DateTime($detalle['fecha_fin']);
                $intervalo = new DateInterval('P1D');
                $rango = new DatePeriod($inicio, $intervalo, $fin->modify('+1 day'));
                
                // Crear un array indexado con los registros de asistencia
                $asistencias = [];
                foreach ($detalle['asistencias'] as $asistencia) {
                    $asistencias[$asistencia['fecha']] = $asistencia;
                }
                
                // Iterar por todas las fechas del rango
                foreach ($rango as $fecha) {
                    $fechaStr = $fecha->format('Y-m-d');
                    $diaSemana = $fecha->format('N');
                    $nombreDia = DateHelper::obtenerNombreDiaSemana($fechaStr);
                    $esFinDeSemana = ($diaSemana >= 6); // 6 = sábado, 7 = domingo
                    
                    // Determinar si hay registro para esta fecha
                    $tieneRegistro = isset($asistencias[$fechaStr]);
                    $estado = $tieneRegistro ? $asistencias[$fechaStr]['estado'] : null;
                    
                    // Saltear fin de semana si no hay registro
                    if ($esFinDeSemana && !$tieneRegistro) {
                        continue;
                    }
                    
                    // Determinar clase CSS para la fila
                    $claseFilaEstado = '';
                    if ($tieneRegistro) {
                        switch ($estado) {
                            case 'presente':
                                $claseFilaEstado = 'table-success';
                                break;
                            case 'ausente':
                                $claseFilaEstado = 'table-danger';
                                break;
                            case 'tardanza':
                                $claseFilaEstado = 'table-warning';
                                break;
                            case 'justificado':
                                $claseFilaEstado = 'table-info';
                                break;
                        }
                    } else if ($esFinDeSemana) {
                        $claseFilaEstado = 'table-secondary';
                    }
                ?>
                    <tr class="<?php echo $claseFilaEstado; ?>">
                        <td><?php echo date('d/m/Y', strtotime($fechaStr)); ?></td>
                        <td>
                            <?php echo $nombreDia; ?>
                            <?php if ($esFinDeSemana): ?>
                                <span class="badge badge-secondary ml-1">Fin de semana</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($tieneRegistro): ?>
                                <?php switch ($estado):
                                    case 'presente': ?>
                                        <span class="badge badge-success p-2">
                                            <i class="fas fa-check-circle"></i> Presente
                                        </span>
                                        <?php break;
                                    
                                    case 'ausente': ?>
                                        <span class="badge badge-danger p-2">
                                            <i class="fas fa-times-circle"></i> Ausente
                                        </span>
                                        <?php break;
                                    
                                    case 'tardanza': ?>
                                        <span class="badge badge-warning p-2">
                                            <i class="fas fa-clock"></i> Tardanza
                                        </span>
                                        <?php break;
                                    
                                    case 'justificado': ?>
                                        <span class="badge badge-info p-2">
                                            <i class="fas fa-file-alt"></i> Justificado
                                        </span>
                                        <?php break;
                                    
                                    default: ?>
                                        <span class="badge badge-secondary p-2">
                                            <i class="fas fa-question-circle"></i> Desconocido
                                        </span>
                                    <?php endswitch; ?>
                            <?php elseif ($esFinDeSemana): ?>
                                <span class="badge badge-secondary p-2">
                                    <i class="fas fa-calendar-week"></i> No aplica
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary p-2">
                                    <i class="fas fa-minus-circle"></i> Sin registro
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-chart-bar"></i> Resumen de Asistencia
            </h6>
        </div>
        <div class="card-body">
            <?php
            // Calcular estadísticas
            $presentes = 0;
            $ausentes = 0;
            $tardanzas = 0;
            $justificados = 0;
            $sinRegistro = 0;
            $diasHabiles = 0;
            
            // Iterar por todas las fechas del rango nuevamente
            $inicio = new DateTime($detalle['fecha_inicio']);
            $fin = new DateTime($detalle['fecha_fin']);
            $intervalo = new DateInterval('P1D');
            $rango = new DatePeriod($inicio, $intervalo, $fin->modify('+1 day'));
            
            foreach ($rango as $fecha) {
                $fechaStr = $fecha->format('Y-m-d');
                $diaSemana = $fecha->format('N');
                
                // Si es día hábil (lunes a viernes)
                if ($diaSemana < 6) {
                    $diasHabiles++;
                    
                    if (isset($asistencias[$fechaStr])) {
                        switch ($asistencias[$fechaStr]['estado']) {
                            case 'presente':
                                $presentes++;
                                break;
                            case 'ausente':
                                $ausentes++;
                                break;
                            case 'tardanza':
                                $tardanzas++;
                                break;
                            case 'justificado':
                                $justificados++;
                                break;
                        }
                    } else {
                        $sinRegistro++;
                    }
                }
            }
            
            // Calcular porcentajes
            $totalRegistros = $presentes + $ausentes + $tardanzas + $justificados;
            $porcentajePresentes = ($diasHabiles > 0) ? round(($presentes / $diasHabiles) * 100, 1) : 0;
            $porcentajeAusentes = ($diasHabiles > 0) ? round(($ausentes / $diasHabiles) * 100, 1) : 0;
            $porcentajeTardanzas = ($diasHabiles > 0) ? round(($tardanzas / $diasHabiles) * 100, 1) : 0;
            $porcentajeJustificados = ($diasHabiles > 0) ? round(($justificados / $diasHabiles) * 100, 1) : 0;
            $porcentajeSinRegistro = ($diasHabiles > 0) ? round(($sinRegistro / $diasHabiles) * 100, 1) : 0;
            
            // Calcular porcentaje de asistencia (presente + justificado)
            $porcentajeAsistencia = ($diasHabiles > 0) ? round((($presentes + $justificados) / $diasHabiles) * 100, 1) : 0;
            ?>
            
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="progress mb-2" style="height: 25px;">
                        <?php if ($presentes > 0): ?>
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo ($presentes / $diasHabiles) * 100; ?>%">
                                <?php echo $presentes; ?> Presentes
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($ausentes > 0): ?>
                            <div class="progress-bar bg-danger" role="progressbar" 
                                 style="width: <?php echo ($ausentes / $diasHabiles) * 100; ?>%">
                                <?php echo $ausentes; ?> Ausentes
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($tardanzas > 0): ?>
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?php echo ($tardanzas / $diasHabiles) * 100; ?>%">
                                <?php echo $tardanzas; ?> Tardanzas
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($justificados > 0): ?>
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: <?php echo ($justificados / $diasHabiles) * 100; ?>%">
                                <?php echo $justificados; ?> Justificados
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($sinRegistro > 0): ?>
                            <div class="progress-bar bg-secondary" role="progressbar" 
                                 style="width: <?php echo ($sinRegistro / $diasHabiles) * 100; ?>%">
                                <?php echo $sinRegistro; ?> Sin registro
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="small text-muted mb-3">
                        <span class="badge badge-success">Presentes: <?php echo $presentes; ?> (<?php echo $porcentajePresentes; ?>%)</span>
                        <span class="badge badge-danger ml-1">Ausentes: <?php echo $ausentes; ?> (<?php echo $porcentajeAusentes; ?>%)</span>
                        <span class="badge badge-warning ml-1">Tardanzas: <?php echo $tardanzas; ?> (<?php echo $porcentajeTardanzas; ?>%)</span>
                        <span class="badge badge-info ml-1">Justificados: <?php echo $justificados; ?> (<?php echo $porcentajeJustificados; ?>%)</span>
                        <span class="badge badge-secondary ml-1">Sin registro: <?php echo $sinRegistro; ?> (<?php echo $porcentajeSinRegistro; ?>%)</span>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="alert <?php echo $porcentajeAsistencia >= 80 ? 'alert-success' : 'alert-danger'; ?>">
                        <div class="h3 mb-0 font-weight-bold text-center">
                            <?php echo $porcentajeAsistencia; ?>%
                        </div>
                        <div class="text-center">Porcentaje de asistencia</div>
                        
                        <?php if ($porcentajeAsistencia < 80): ?>
                            <div class="mt-2 small">
                                <i class="fas fa-exclamation-triangle"></i> 
                                El estudiante está por debajo del umbral mínimo de asistencia (80%).
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="text-muted small">
                <i class="fas fa-info-circle"></i> 
                Total días hábiles: <?php echo $diasHabiles; ?> | 
                Días con registro: <?php echo $totalRegistros; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para la vista de detalle */
.icon-circle {
    height: 40px;
    width: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.detalle-container {
    max-height: 70vh;
    overflow-y: auto;
}
</style>