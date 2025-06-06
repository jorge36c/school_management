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

// Inicializar controlador
$controller = new AsistenciaController($pdo);
$resultado = $controller->cargarDatosPrincipal($_SESSION['profesor_id']);

// Mostrar mensajes de sesión
$mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : null;
$mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : null;

// Limpiar mensajes de sesión
if (isset($_SESSION['mensaje_exito'])) unset($_SESSION['mensaje_exito']);
if (isset($_SESSION['mensaje_error'])) unset($_SESSION['mensaje_error']);

// Título de la página
$pageTitle = "Control de Asistencia";

// Incluir el header
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <!-- Encabezado de la página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check"></i> Control de Asistencia
        </h1>
        <div class="btn-group">
            <a href="reportes.php" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-chart-bar fa-sm text-white-50"></i> Ver Reportes
            </a>
        </div>
    </div>
    
    <!-- Mensajes de alerta -->
    <?php if ($mensaje_exito): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $mensaje_exito; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $mensaje_error; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if (!$resultado['exito']): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $resultado['error']; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Lista de Grados -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users"></i> Mis Grupos
                        <?php if ($resultado['exito'] && $resultado['total_grados'] > 0): ?>
                            <span class="badge badge-primary ml-2"><?php echo $resultado['total_grados']; ?> grupos</span>
                        <?php endif; ?>
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Acciones:</div>
                            <a class="dropdown-item" href="reportes.php">
                                <i class="fas fa-chart-bar fa-sm fa-fw mr-2 text-gray-400"></i> Ver Reportes
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="../dashboard.php">
                                <i class="fas fa-home fa-sm fa-fw mr-2 text-gray-400"></i> Ir al Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($resultado['exito'] && $resultado['total_grados'] > 0): ?>
                        <div class="row">
                            <?php foreach ($resultado['grados'] as $grado): ?>
                                <div class="col-xl-4 col-md-6 mb-4">
                                    <div class="card border-left-primary shadow h-100 py-2 grado-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        <?php echo htmlspecialchars($grado['sede_nombre']); ?>
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        <?php echo htmlspecialchars($grado['nombre']); ?>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                            <div class="mt-3 text-center">
                                                <a href="registro_asistencia.php?grado_id=<?php echo $grado['id']; ?>" class="btn btn-primary btn-sm btn-block">
                                                    <i class="fas fa-clipboard-check"></i> Registrar Asistencia
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i> No tiene grupos asignados para registrar asistencia.
                            <hr>
                            <p class="mb-0">Para poder registrar asistencia, debe tener asignado al menos un grupo. Comuníquese con el administrador del sistema para solicitar la asignación de grupos.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Instrucciones de Uso -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-question-circle"></i> Instrucciones de Uso
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="step-instruction">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h5>Seleccionar un grupo</h5>
                            <p>Haga clic en el botón <span class="badge badge-primary"><i class="fas fa-clipboard-check"></i> Registrar Asistencia</span> del grupo correspondiente.</p>
                        </div>
                    </div>
                    
                    <div class="step-instruction">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h5>Elegir la fecha</h5>
                            <p>En la siguiente pantalla, seleccione la fecha para la cual desea registrar asistencia.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="step-instruction">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h5>Registrar asistencia</h5>
                            <p>Marque el estado de asistencia de cada estudiante:</p>
                            <div class="estado-examples">
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Presente</span>
                                <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Ausente</span>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Tardanza</span>
                                <span class="badge badge-info"><i class="fas fa-file-alt"></i> Justificado</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-instruction">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h5>Consultar reportes</h5>
                            <p>Para ver estadísticas de asistencia, utilice la sección de <a href="reportes.php">Reportes</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para tarjetas de grado */
.grado-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.grado-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .15) !important;
}

/* Estilos para instrucciones */
.step-instruction {
    display: flex;
    margin-bottom: 20px;
}

.step-number {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 18px;
    margin-right: 15px;
    flex-shrink: 0;
}

.step-content {
    flex-grow: 1;
}

.step-content h5 {
    margin-bottom: 8px;
    color: #4e73df;
}

.estado-examples {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.estado-examples .badge {
    font-size: 90%;
    padding: 5px 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Agregar funcionalidad a las tarjetas de grado para que sean clickeables
    document.querySelectorAll('.grado-card').forEach(card => {
        const link = card.querySelector('a.btn');
        if (link) {
            card.addEventListener('click', function(e) {
                // Si el clic fue en el botón, no hacemos nada (deja que el enlace funcione normalmente)
                if (e.target.tagName === 'A' || e.target.tagName === 'I' || e.target.closest('a')) {
                    return;
                }
                // De lo contrario, simulamos un clic en el botón
                link.click();
            });
        }
    });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>