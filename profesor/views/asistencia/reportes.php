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

// Inicializar controlador
$controller = new AsistenciaController($pdo);
$datosIniciales = $controller->cargarDatosPrincipal($_SESSION['profesor_id']);

// Variables para almacenar resultados
$reporte = null;
$error = null;
$exito = false;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_reporte'])) {
    // Validar datos del formulario
    if (!isset($_POST['grado_id']) || empty($_POST['grado_id'])) {
        $error = "Debe seleccionar un grupo para generar el reporte.";
    } else if (!isset($_POST['fecha_inicio']) || empty($_POST['fecha_inicio'])) {
        $error = "La fecha inicial es requerida.";
    } else if (!isset($_POST['fecha_fin']) || empty($_POST['fecha_fin'])) {
        $error = "La fecha final es requerida.";
    } else if ($_POST['fecha_inicio'] > $_POST['fecha_fin']) {
        $error = "La fecha inicial no puede ser mayor que la fecha final.";
    } else {
        // Datos válidos, generar reporte
        $gradoId = intval($_POST['grado_id']);
        $fechaInicio = $_POST['fecha_inicio'];
        $fechaFin = $_POST['fecha_fin'];
        
        $resultado = $controller->generarReporte($gradoId, $fechaInicio, $fechaFin);
        
        if ($resultado['exito']) {
            $reporte = $resultado['reporte'];
            $exito = true;
        } else {
            $error = $resultado['error'];
        }
    }
}

// Valores predeterminados para el formulario
$gradoSeleccionado = $_POST['grado_id'] ?? $_GET['grado_id'] ?? '';
$fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d'); // Día actual

// Establecer título y breadcrumb
$pageTitle = "Reportes de Asistencia";
$breadcrumb = [
    ['url' => '/school_management/profesor/dashboard.php', 'text' => 'Dashboard'],
    ['url' => 'index.php', 'text' => 'Asistencia'],
    ['url' => '#', 'text' => 'Reportes']
];

// Incluir header
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <!-- Encabezado de la página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar"></i> Reportes de Asistencia
        </h1>
        <a href="index.php" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Volver
        </a>
    </div>
    
    <!-- Mensajes de alerta -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if ($exito): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Reporte generado correctamente.
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <!-- Formulario de generación de reportes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-gradient-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-filter"></i> Filtros del Reporte
            </h6>
        </div>
        <div class="card-body">
            <form method="post" action="reportes.php" id="formReportes">
                <input type="hidden" name="generar_reporte" value="1">
                
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="grado_id" class="form-label">
                            <i class="fas fa-users"></i> Seleccione Grupo:
                        </label>
                        <select class="form-control" id="grado_id" name="grado_id" required>
                            <option value="">-- Seleccione un grupo --</option>
                            <?php if ($datosIniciales['exito'] && !empty($datosIniciales['grados'])): ?>
                                <?php foreach ($datosIniciales['grados'] as $grado): ?>
                                    <option value="<?php echo $grado['id']; ?>" 
                                            <?php echo ($gradoSeleccionado == $grado['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grado['sede_nombre'] . ' - ' . $grado['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="fecha_inicio" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Fecha Inicial:
                        </label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                               value="<?php echo $fechaInicio; ?>" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="fecha_fin" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Fecha Final:
                        </label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                               value="<?php echo $fechaFin; ?>" required>
                    </div>
                    
                    <div class="col-md-1 mb-3">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Generar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($reporte): ?>
        <!-- Información del reporte generado -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-file-alt"></i> Reporte: <?php echo htmlspecialchars($reporte['info_grado']['sede_nombre'] . ' - ' . $reporte['info_grado']['nombre']); ?>
                </h6>
                <div>
                    <span class="badge badge-primary p-2">
                        <i class="fas fa-calendar-alt"></i> 
                        <?php echo date('d/m/Y', strtotime($reporte['fecha_inicio'])); ?> 
                        a 
                        <?php echo date('d/m/Y', strtotime($reporte['fecha_fin'])); ?>
                    </span>
                    <a href="exportar_excel.php?grado_id=<?php echo $_POST['grado_id']; ?>&fecha_inicio=<?php echo $_POST['fecha_inicio']; ?>&fecha_fin=<?php echo $_POST['fecha_fin']; ?>" class="btn btn-sm btn-success ml-2" target="_blank">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Resumen de asistencia -->
                <?php
                // Calcular totales
                $totalPresentes = 0;
                $totalAusentes = 0;
                $totalTardanzas = 0;
                $totalJustificados = 0;
                $totalEstudiantes = count($reporte['estadisticas']);
                $totalDias = count($reporte['fechas']);
                
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
                
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto pr-4">
                                        <i class="fas fa-chart-pie fa-3x text-gray-300 mb-3"></i>
                                        <div class="text-xs font-weight-bold text-primary text-uppercase">
                                            Resumen General
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <div class="report-stat stat-presente">
                                                    <div class="number"><?php echo $totalPresentes; ?></div>
                                                    <div class="label">Presentes</div>
                                                    <div class="percent"><?php echo $porcentajePresentes; ?>%</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="report-stat stat-ausente">
                                                    <div class="number"><?php echo $totalAusentes; ?></div>
                                                    <div class="label">Ausentes</div>
                                                    <div class="percent"><?php echo $porcentajeAusentes; ?>%</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="report-stat stat-tardanza">
                                                    <div class="number"><?php echo $totalTardanzas; ?></div>
                                                    <div class="label">Tardanzas</div>
                                                    <div class="percent"><?php echo $porcentajeTardanzas; ?>%</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="report-stat stat-justificado">
                                                    <div class="number"><?php echo $totalJustificados; ?></div>
                                                    <div class="label">Justificados</div>
                                                    <div class="percent"><?php echo $porcentajeJustificados; ?>%</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="mt-2">
                                                    <span class="fw-bold">Presentes: </span>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                             style="width: <?php echo $porcentajePresentes; ?>%;" 
                                                             aria-valuenow="<?php echo $porcentajePresentes; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <span class="fw-bold">Ausentes: </span>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-danger" role="progressbar" 
                                                             style="width: <?php echo $porcentajeAusentes; ?>%;" 
                                                             aria-valuenow="<?php echo $porcentajeAusentes; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="mt-2">
                                                    <span class="fw-bold">Tardanzas: </span>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-warning" role="progressbar" 
                                                             style="width: <?php echo $porcentajeTardanzas; ?>%;" 
                                                             aria-valuenow="<?php echo $porcentajeTardanzas; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <span class="fw-bold">Justificados: </span>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-info" role="progressbar" 
                                                             style="width: <?php echo $porcentajeJustificados; ?>%;" 
                                                             aria-valuenow="<?php echo $porcentajeJustificados; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 text-muted small">
                                            <i class="fas fa-info-circle"></i>
                                            Período analizado: <?php echo $totalDias; ?> día(s) | <?php echo $totalEstudiantes; ?> estudiante(s) | 
                                            Total registros: <?php echo $totalRegistros; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de estadísticas por estudiante -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaReporte" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Estudiante</th>
                                <th>Documento</th>
                                <th class="text-center">Presentes</th>
                                <th class="text-center">Ausentes</th>
                                <th class="text-center">Tardanzas</th>
                                <th class="text-center">Justificados</th>
                                <th class="text-center">% Asistencia</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reporte['estadisticas'] as $estadistica): 
                                $totalIndividual = $estadistica['presentes'] + $estadistica['ausentes'] + 
                                                  $estadistica['tardanzas'] + $estadistica['justificados'];
                                $porcentajeAsistencia = ($totalIndividual > 0) ? 
                                    round((($estadistica['presentes'] + $estadistica['justificados']) / $totalIndividual) * 100, 2) : 0;
                                
                                // Determinar clase CSS para porcentaje
                                $claseAsistencia = "";
                                if ($porcentajeAsistencia >= 90) {
                                    $claseAsistencia = "badge-success";
                                } elseif ($porcentajeAsistencia >= 75) {
                                    $claseAsistencia = "badge-warning";
                                } else {
                                    $claseAsistencia = "badge-danger";
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($estadistica['apellidos'] . ', ' . $estadistica['nombres']); ?></td>
                                    <td><?php echo htmlspecialchars($estadistica['documento_tipo'] . ' ' . $estadistica['documento_numero']); ?></td>
                                    <td class="text-center"><?php echo $estadistica['presentes']; ?></td>
                                    <td class="text-center"><?php echo $estadistica['ausentes']; ?></td>
                                    <td class="text-center"><?php echo $estadistica['tardanzas']; ?></td>
                                    <td class="text-center"><?php echo $estadistica['justificados']; ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $claseAsistencia; ?> p-2">
                                            <?php echo $porcentajeAsistencia; ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info ver-detalle" 
                                                data-estudiante-id="<?php echo $estadistica['estudiante_id']; ?>"
                                                data-estudiante-nombre="<?php echo htmlspecialchars($estadistica['apellidos'] . ', ' . $estadistica['nombres']); ?>"
                                                data-fecha-inicio="<?php echo $reporte['fecha_inicio']; ?>"
                                                data-fecha-fin="<?php echo $reporte['fecha_fin']; ?>">
                                            <i class="fas fa-eye"></i> Ver Detalle
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow mb-4">
            <div class="card-body py-5 text-center">
                <img src="/school_management/assets/img/report-icon.png" alt="Reportes" class="mb-3" style="max-width: 150px; opacity: 0.6;">
                <h4 class="text-gray-800 mb-3">No hay reportes generados</h4>
                <p class="text-gray-600 mb-0">Seleccione un grupo y un rango de fechas, luego haga clic en "Generar" para ver los reportes de asistencia.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para mostrar detalle de asistencia por estudiante -->
<div class="modal fade" id="modalDetalleAsistencia" tabindex="-1" aria-labelledby="modalDetalleAsistenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetalleAsistenciaLabel">
                    <i class="fas fa-user-check"></i> Detalle de Asistencia
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detalleContenido">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando información de asistencia...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable si existe
    if (document.getElementById('tablaReporte')) {
        $('#tablaReporte').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
            },
            pageLength: 10,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copiar',
                    className: 'btn btn-sm btn-secondary'
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-sm btn-success'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger'},
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-sm btn-primary'
                }
            ]
        });
    }
    
    // Validación del formulario de reportes
    document.getElementById('formReportes')?.addEventListener('submit', function(e) {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        const gradoId = document.getElementById('grado_id').value;
        
        if (!gradoId) {
            e.preventDefault();
            mostrarAlerta('Debe seleccionar un grupo para generar el reporte', 'danger');
            return;
        }
        
        if (!fechaInicio || !fechaFin) {
            e.preventDefault();
            mostrarAlerta('Debe seleccionar fechas de inicio y fin', 'danger');
            return;
        }
        
        if (fechaInicio > fechaFin) {
            e.preventDefault();
            mostrarAlerta('La fecha inicial no puede ser mayor que la fecha final', 'danger');
            return;
        }
    });
    
    // Manejo de modal de detalle de asistencia
    document.querySelectorAll('.ver-detalle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const estudianteId = this.getAttribute('data-estudiante-id');
            const estudianteNombre = this.getAttribute('data-estudiante-nombre');
            const fechaInicio = this.getAttribute('data-fecha-inicio');
            const fechaFin = this.getAttribute('data-fecha-fin');
            
            // Actualizar título del modal
            document.getElementById('modalDetalleAsistenciaLabel').innerHTML = 
                `<i class="fas fa-user-check"></i> Detalle de Asistencia: ${estudianteNombre}`;
            
            // Mostrar modal con indicador de carga
            $('#modalDetalleAsistencia').modal('show');
            
            // Preparar datos para enviar por POST
            const formData = new FormData();
            formData.append('estudiante_id', estudianteId);
            formData.append('fecha_inicio', fechaInicio);
            formData.append('fecha_fin', fechaFin);
            
            // Cargar detalles mediante Fetch API
            fetch('detalle_asistencia.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.text();
            })
            .then(data => {
                document.getElementById('detalleContenido').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('detalleContenido').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> 
                        Error al cargar los detalles: ${error.message}
                    </div>
                `;
            });
        });
    });
    
    // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo = 'info') {
        const alertaHtml = `
            <div class="alert alert-${tipo} alert-dismissible fade show">
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${mensaje}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        `;
        
        // Insertar alerta al inicio del contenedor
        const container = document.querySelector('.container-fluid');
        const primerHijo = container.firstChild;
        
        const alertaTemp = document.createElement('div');
        alertaTemp.innerHTML = alertaHtml;
        const alerta = alertaTemp.firstChild;
        
        container.insertBefore(alerta, primerHijo);
        
        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 150);
        }, 5000);
    }
});
</script>

<style>
/* Estilos para reportes */
.report-stat {
    text-align: center;
    padding: 1rem;
    border-radius: 0.35rem;
    margin-bottom: 1rem;
}

.report-stat .number {
    font-size: 2rem;
    font-weight: bold;
}

.report-stat .label {
    font-size: 0.875rem;
    text-transform: uppercase;
    font-weight: bold;
}

.report-stat .percent {
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

/* Colores para estadísticas */
.stat-presente {
    background-color: #d4edda;
    color: #155724;
}

.stat-ausente {
    background-color: #f8d7da;
    color: #721c24;
}

.stat-tardanza {
    background-color: #fff3cd;
    color: #856404;
}

.stat-justificado {
    background-color: #d1ecf1;
    color: #0c5460;
}

/* Estilos para DataTables */
.dataTables_wrapper .dt-buttons {
    margin-bottom: 1rem;
}

/* Estilos para el modal */
.modal-header h5 {
    font-weight: bold;
}

/* Estilos para barras de progreso pequeñas */
.progress-sm {
    height: 8px;
    margin-bottom: 0.5rem;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>   