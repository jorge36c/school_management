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
if (!isset($_GET['grado_id']) || empty($_GET['grado_id'])) {
    $_SESSION['mensaje_error'] = "Debe seleccionar un grupo para registrar asistencia.";
    header("Location: index.php");
    exit;
}

// Obtener parámetros
$gradoId = intval($_GET['grado_id']);
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Inicializar controlador
$controller = new AsistenciaController($pdo);
$resultado = $controller->cargarDatosRegistroAsistencia($gradoId, $fecha);

// Verificar resultado
if (!$resultado['exito']) {
    $_SESSION['mensaje_error'] = $resultado['error'];
    header("Location: index.php");
    exit;
}

// Obtener datos
$grado = $resultado['grado'];
$estudiantes = $resultado['estudiantes'];
$nombreDia = $resultado['nombre_dia'];

// Establecer título de la página
$pageTitle = "Registro de Asistencia - {$grado['sede_nombre']} - {$grado['nombre']}";

// Incluir el header
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <!-- Encabezado de la página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check"></i> Registro de Asistencia
        </h1>
        <a href="index.php" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Volver
        </a>
    </div>
    
    <!-- Mensajes de alerta -->
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['mensaje_exito']; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['mensaje_error']; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php unset($_SESSION['mensaje_error']); ?>
    <?php endif; ?>
    
    <!-- Información del Grupo y Selector de Fecha -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card bg-gradient-primary text-white shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="icon-circle bg-white text-primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="text-white"><?php echo htmlspecialchars($grado['nombre']); ?></h4>
                            <div class="text-white-50"><?php echo htmlspecialchars($grado['sede_nombre']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card bg-white shadow">
                <div class="card-body">
                    <form action="" method="get" class="form-date-selector">
                        <input type="hidden" name="grado_id" value="<?php echo $gradoId; ?>">
                        
                        <div class="form-group mb-0">
                            <label for="fecha" class="small text-muted">
                                <i class="fas fa-calendar-alt"></i> Seleccionar fecha:
                            </label>
                            <div class="input-group">
                                <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo $fecha; ?>" required max="<?php echo date('Y-m-d'); ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="small mt-1 text-primary text-right">
                                <strong><?php echo $nombreDia; ?></strong> - <?php echo date('d/m/Y', strtotime($fecha)); ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lista de Estudiantes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list-check"></i> Lista de Estudiantes
                <span class="badge badge-primary"><?php echo count($estudiantes); ?> estudiantes</span>
            </h6>
            
            <div class="actions-container">
                <?php if (count($estudiantes) > 0): ?>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success btn-sm" id="marcarTodosPresenteBtn">
                            <i class="fas fa-check-circle"></i> Todos Presentes
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="marcarTodosAusenteBtn">
                            <i class="fas fa-times-circle"></i> Todos Ausentes
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (count($estudiantes) > 0): ?>
                <form action="guardar_asistencia.php" method="post" id="formAsistencia">
                    <input type="hidden" name="grado_id" value="<?php echo $gradoId; ?>">
                    <input type="hidden" name="fecha" value="<?php echo $fecha; ?>">
                    <input type="hidden" name="profesor_id" value="<?php echo $_SESSION['profesor_id']; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tablaAsistencia">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center" style="width: 50px">#</th>
                                    <th>Estudiante</th>
                                    <th class="text-center" style="width: 400px">Estado de Asistencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estudiantes as $index => $estudiante): ?>
                                    <tr class="estado-row <?php echo $estudiante['estado'] ? 'has-state' : ''; ?>">
                                        <td class="text-center">
                                            <?php echo $index + 1; ?>
                                            <input type="hidden" name="estudiantes[]" value="<?php echo $estudiante['id']; ?>">
                                        </td>
                                        <td>
                                            <div class="estudiante-info">
                                                <div class="estudiante-nombre">
                                                    <?php echo htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']); ?>
                                                </div>
                                                <div class="estudiante-documento small text-muted">
                                                    <?php echo htmlspecialchars($estudiante['documento_tipo'] . ' ' . $estudiante['documento_numero']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="estado-buttons btn-group btn-group-toggle w-100" data-toggle="buttons">
                                                <label class="btn btn-estado btn-outline-success <?php echo ($estudiante['estado'] === 'presente' || $estudiante['estado'] === null) ? 'active' : ''; ?>">
                                                    <input type="radio" name="estados[<?php echo $index; ?>]" value="presente" <?php echo ($estudiante['estado'] === 'presente' || $estudiante['estado'] === null) ? 'checked' : ''; ?>>
                                                    <i class="fas fa-check-circle"></i> Presente
                                                </label>
                                                <label class="btn btn-estado btn-outline-danger <?php echo $estudiante['estado'] === 'ausente' ? 'active' : ''; ?>">
                                                    <input type="radio" name="estados[<?php echo $index; ?>]" value="ausente" <?php echo $estudiante['estado'] === 'ausente' ? 'checked' : ''; ?>>
                                                    <i class="fas fa-times-circle"></i> Ausente
                                                </label>
                                                <label class="btn btn-estado btn-outline-warning <?php echo $estudiante['estado'] === 'tardanza' ? 'active' : ''; ?>">
                                                    <input type="radio" name="estados[<?php echo $index; ?>]" value="tardanza" <?php echo $estudiante['estado'] === 'tardanza' ? 'checked' : ''; ?>>
                                                    <i class="fas fa-clock"></i> Tardanza
                                                </label>
                                                <label class="btn btn-estado btn-outline-info <?php echo $estudiante['estado'] === 'justificado' ? 'active' : ''; ?>">
                                                    <input type="radio" name="estados[<?php echo $index; ?>]" value="justificado" <?php echo $estudiante['estado'] === 'justificado' ? 'checked' : ''; ?>>
                                                    <i class="fas fa-file-alt"></i> Justificado
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-actions text-right mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Guardar Asistencia
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No hay estudiantes registrados en este grupo.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Estilos para el círculo del icono */
.icon-circle {
    height: 50px;
    width: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

/* Estilos para la tabla de asistencia */
#tablaAsistencia {
    border-collapse: separate;
    border-spacing: 0;
}

#tablaAsistencia th, #tablaAsistencia td {
    vertical-align: middle;
}

.estudiante-info {
    padding: 5px 0;
}

.estudiante-nombre {
    font-weight: 500;
}

/* Estilos para botones de estado */
.estado-buttons {
    display: flex;
    width: 100%;
    justify-content: center;
}

.btn-estado {
    flex: 1;
    border-radius: 4px;
    margin: 0 3px;
    padding: 8px 12px;
    font-size: 13px;
    transition: all 0.2s ease;
}

.btn-estado i {
    margin-right: 5px;
}

.btn-outline-success.active, .btn-outline-success:hover {
    background-color: #28a745 !important;
    color: white !important;
}

.btn-outline-danger.active, .btn-outline-danger:hover {
    background-color: #dc3545 !important;
    color: white !important;
}

.btn-outline-warning.active, .btn-outline-warning:hover {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.btn-outline-info.active, .btn-outline-info:hover {
    background-color: #17a2b8 !important;
    color: white !important;
}

/* Estilos responsivos */
@media (max-width: 768px) {
    .btn-estado {
        padding: 6px 8px;
        font-size: 12px;
    }
    
    .btn-estado i {
        margin-right: 0;
    }
    
    .btn-estado span {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos
    const marcarTodosPresenteBtn = document.getElementById('marcarTodosPresenteBtn');
    const marcarTodosAusenteBtn = document.getElementById('marcarTodosAusenteBtn');
    const formAsistencia = document.getElementById('formAsistencia');
    
    // Función para marcar todos con un estado específico
    function marcarTodos(estado) {
        document.querySelectorAll(`input[name^="estados"][value="${estado}"]`).forEach(radio => {
            radio.checked = true;
            
            // Actualizar clases de los botones
            const label = radio.closest('label');
            if (label) {
                // Activar este botón
                label.classList.add('active');
                
                // Desactivar los demás botones en el grupo
                const btnGroup = label.closest('.btn-group');
                if (btnGroup) {
                    btnGroup.querySelectorAll('label').forEach(otherLabel => {
                        if (otherLabel !== label) {
                            otherLabel.classList.remove('active');
                        }
                    });
                }
            }
        });
        
        // Mostrar mensaje de notificación
        const mensaje = estado === 'presente' ? 
            'Todos los estudiantes marcados como presentes' : 
            'Todos los estudiantes marcados como ausentes';
        
        const tipo = estado === 'presente' ? 'success' : 'danger';
        
        mostrarNotificacion(mensaje, tipo);
    }
    
    // Event listener para botón "Todos Presentes"
    if (marcarTodosPresenteBtn) {
        marcarTodosPresenteBtn.addEventListener('click', function() {
            marcarTodos('presente');
        });
    }
    
    // Event listener para botón "Todos Ausentes"
    if (marcarTodosAusenteBtn) {
        marcarTodosAusenteBtn.addEventListener('click', function() {
            marcarTodos('ausente');
        });
    }
    
    // Validación del formulario
    if (formAsistencia) {
        formAsistencia.addEventListener('submit', function(e) {
            const fecha = document.getElementById('fecha').value;
            const fechaActual = new Date().toISOString().split('T')[0];
            
            if (new Date(fecha) > new Date(fechaActual)) {
                e.preventDefault();
                mostrarNotificacion('No se puede registrar asistencia para una fecha futura', 'danger');
            }
        });
    }
    
    // Función para mostrar notificaciones
    function mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notif = document.createElement('div');
        notif.className = `alert alert-${tipo} alert-dismissible fade show notification-toast`;
        notif.innerHTML = `
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i>
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        
        // Estilos para la notificación
        notif.style.position = 'fixed';
        notif.style.top = '20px';
        notif.style.right = '20px';
        notif.style.zIndex = '9999';
        notif.style.minWidth = '300px';
        notif.style.boxShadow = '0 0.25rem 0.75rem rgba(0, 0, 0, .1)';
        
        // Agregar al DOM
        document.body.appendChild(notif);
        
        // Auto-cerrar después de 3 segundos
        setTimeout(() => {
            notif.classList.remove('show');
            setTimeout(() => notif.remove(), 150);
        }, 3000);
    }
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>