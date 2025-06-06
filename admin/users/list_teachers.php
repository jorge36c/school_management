<?php
// Primero incluir config.php
require_once '../../includes/config.php';

// DESPUÉS de incluir config.php, iniciar la sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['admin_id'])) {
    header('Location: /school_management/auth/login.php');
    exit();
}

try {
    // Obtener total de profesores y estadísticas
    $sqlCount = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
    FROM profesores";
    $stmtCount = $conn->query($sqlCount);
    $stats = $stmtCount->fetch_assoc();

    // Obtener todas las sedes para el filtro
    $sqlSedes = "SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre";
    $stmtSedes = $conn->query($sqlSedes);
    $sedes = [];
    
    if ($stmtSedes && $stmtSedes->num_rows > 0) {
        while ($row = $stmtSedes->fetch_assoc()) {
            $sedes[] = $row;
        }
    }

    // Procesar parámetros de búsqueda
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
    $filter_sede = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : 0;

    // Construir consulta con filtros
    $query = "SELECT p.*, s.nombre as sede_nombre 
              FROM profesores p
              LEFT JOIN sedes s ON p.sede_id = s.id
              WHERE 1=1";
    $params = [];

    // Añadir condiciones de búsqueda
    if (!empty($search)) {
        $query .= " AND (p.nombre LIKE ? OR p.apellido LIKE ? OR p.email LIKE ? OR s.nombre LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Filtrar por estado
    if (!empty($filter_status)) {
        $query .= " AND p.estado = ?";
        $params[] = $filter_status;
    }

    // Filtrar por sede
    if ($filter_sede > 0) {
        $query .= " AND p.sede_id = ?";
        $params[] = $filter_sede;
    }

    // Ordenar resultados
    $query .= " ORDER BY p.apellido, p.nombre";

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $profesores = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $profesores[] = $row;
        }
    }

    // Obtener materias para el modal de asignación
    $sqlMaterias = "SELECT id, nombre FROM materias WHERE estado = 'activo' ORDER BY nombre";
    $stmtMaterias = $conn->query($sqlMaterias);
    $materias = [];
    
    if ($stmtMaterias && $stmtMaterias->num_rows > 0) {
        while ($row = $stmtMaterias->fetch_assoc()) {
            $materias[] = $row;
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    $profesores = [];
    $stats = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
}

// Configurar el título de la página y el breadcrumb
$page_title = "Gestión de Profesores";
$current_page = 'List teachers';
$breadcrumb_path = [
    'Inicio',
    'Usuarios',
    'Profesores'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Profesores - Sistema Escolar</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos base -->
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/layouts.css">
    
    <!-- Página específica -->
    <link rel="stylesheet" href="../../assets/css/pages/teachers.css">

    <!-- Incluir estilos personalizados -->
    <link rel="stylesheet" href="../../assets/css/components/stats-widgets.css">
    <link rel="stylesheet" href="../../assets/css/components/teacher-cards.css">
    <link rel="stylesheet" href="../../assets/css/components/control-panel.css">
    <style>
    /* Estilos para tooltips en botones */
    .action-btn {
        position: relative;
    }

    .action-btn:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 100;
        margin-bottom: 5px;
    }

    .action-btn:hover::before {
        content: '';
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: rgba(0, 0, 0, 0.8);
        margin-bottom: -5px;
        z-index: 100;
    }

    /* Estilos para alertas */
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .alert-success {
        background-color: #d1fae5;
        color: #059669;
    }
    
    .alert-danger {
        background-color: #fee2e2;
        color: #dc2626;
    }
    
    .alerts-container {
        padding: 0 1.5rem;
    }

    /* Estilos para botones de acción */
    .btn-delete { background: var(--danger-color); }
    .btn-delete:hover { background: var(--danger-dark); }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>

        <div class="main-content">
            <?php include '../components/topbar.php'; ?>

            <!-- Contenedor de alertas -->
            <div class="alerts-container">
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> 
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-card card-total">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h4 class="stat-title">Total Profesores</h4>
                    <div class="stat-value"><?php echo isset($stats['total']) ? $stats['total'] : 0; ?></div>
                </div>
                
                <div class="stat-card card-active">
                    <div class="stat-icon icon-active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h4 class="stat-title">Profesores Activos</h4>
                    <div class="stat-value"><?php echo isset($stats['activos']) ? $stats['activos'] : 0; ?></div>
                </div>
                
                <div class="stat-card card-inactive">
                    <div class="stat-icon icon-inactive">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <h4 class="stat-title">Profesores Inactivos</h4>
                    <div class="stat-value"><?php echo isset($stats['inactivos']) ? $stats['inactivos'] : 0; ?></div>
                </div>
            </div>

            <!-- Panel de Control -->
            <div class="control-panel">
                <div class="control-panel-inner">
                    <div class="filter-controls">
                        <div class="search-control">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="search-input" placeholder="Buscar profesores por nombre, apellido o email..." autocomplete="off" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-select-container">
                            <i class="fas fa-filter"></i>
                            <select id="statusFilter" class="filter-select">
                                <option value="">Estado: Todos</option>
                                <option value="activo" <?php echo $filter_status === 'activo' ? 'selected' : ''; ?>>Activos</option>
                                <option value="inactivo" <?php echo $filter_status === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                            </select>
                        </div>
                        
                        <div class="filter-select-container">
                            <i class="fas fa-school"></i>
                            <select id="sedeFilter" class="filter-select">
                                <option value="0">Sede: Todas</option>
                                <?php foreach($sedes as $sede): ?>
                                    <option value="<?php echo $sede['id']; ?>" <?php echo $filter_sede == $sede['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sede['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="action-controls">
                        <div class="view-toggle">
                            <button id="gridViewBtn" class="view-btn active" data-tooltip="Vista tarjetas">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button id="compactViewBtn" class="view-btn" data-tooltip="Vista compacta">
                                <i class="fas fa-th"></i>
                            </button>
                            <button id="listViewBtn" class="view-btn" data-tooltip="Vista lista">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                        
                        <a href="create_teacher.php" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            Nuevo Profesor
                        </a>
                        </div>
            </div>

            <!-- Contenedor de Profesores -->
            <div class="teachers-container">
                <div id="teachersContainer" class="teachers-grid">
                    <?php if (empty($profesores)): ?>
                        <div class="no-results">
                            <div class="no-results-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="no-results-title">No hay profesores registrados</div>
                            <div class="no-results-text">Comienza creando tu primer profesor para el sistema escolar.</div>
                            <a href="create_teacher.php" class="btn-primary">
                                <i class="fas fa-plus"></i> Crear primer profesor
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Tabla para vista de lista -->
                        <table class="teachers-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Email</th>
                                    <th>Sede</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($profesores as $profesor): ?>
                                <tr data-estado="<?php echo htmlspecialchars($profesor['estado']); ?>" data-sede="<?php echo htmlspecialchars($profesor['sede_id']); ?>">
                                    <td class="table-name"><?php echo htmlspecialchars($profesor['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($profesor['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($profesor['email']); ?></td>
                                    <td><?php echo htmlspecialchars($profesor['sede_nombre'] ?? 'Sin asignar'); ?></td>
                                    <td>
                                        <span class="table-badge <?php echo $profesor['estado'] === 'activo' ? 'badge-active' : 'badge-inactive'; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($profesor['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="action-btn btn-edit" data-tooltip="Editar profesor" 
                                                    onclick="window.location.href='edit_teacher.php?id=<?php echo $profesor['id']; ?>'">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn btn-assign" data-tooltip="Asignar materias y grupos"
                                                   onclick="abrirModalAsignacion(<?php echo $profesor['id']; ?>, '<?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>')">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                            </button>
                                            <button class="action-btn btn-view" data-tooltip="Ver asignaciones"
                                                   onclick="verAsignaciones(<?php echo $profesor['id']; ?>, '<?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($profesor['estado'] == 'activo'): ?>
                                                <button class="action-btn btn-disable" data-tooltip="Inhabilitar profesor"
                                                        onclick="confirmarCambioEstado(<?php echo $profesor['id']; ?>, 'inactivo')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="action-btn btn-enable" data-tooltip="Activar profesor"
                                                        onclick="confirmarCambioEstado(<?php echo $profesor['id']; ?>, 'activo')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="action-btn btn-delete" data-tooltip="Eliminar profesor"
                                                    onclick="confirmarEliminacion(<?php echo $profesor['id']; ?>, '<?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Tarjetas para vista de cuadrícula -->
                        <?php foreach($profesores as $profesor): ?>
                            <div class="teacher-card" 
                                data-estado="<?php echo htmlspecialchars($profesor['estado']); ?>"
                                data-sede="<?php echo htmlspecialchars($profesor['sede_id']); ?>">
                                
                                <!-- Encabezado de la tarjeta con el nombre y estado -->
                                <div class="teacher-header">
                                    <div class="teacher-title-container">
                                        <h3 class="teacher-name">
                                            <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>
                                        </h3>
                                    </div>
                                    
                                    <div class="teacher-status">
                                        <span class="status-badge <?php echo $profesor['estado'] === 'activo' ? 'status-active' : 'status-inactive'; ?>">
                                            <i class="fas fa-circle"></i>
                                           <?php echo ucfirst($profesor['estado']); ?>
                                       </span>
                                   </div>
                               </div>

                               <!-- Contenido principal -->
                               <div class="teacher-content">
                                   <div class="teacher-info">
                                       <!-- Documento -->
                                       <div class="info-row">
                                           <div class="info-icon">
                                               <i class="fas fa-id-card"></i>
                                           </div>
                                           <div class="info-content">
                                               <span class="info-label">Documento:</span>
                                               <span class="info-value"><?php echo htmlspecialchars($profesor['usuario']); ?></span>
                                           </div>
                                       </div>
                                       
                                       <!-- Sede -->
                                       <div class="info-row">
                                           <div class="info-icon">
                                               <i class="fas fa-school"></i>
                                           </div>
                                           <div class="info-content">
                                               <span class="info-label">Sede:</span>
                                               <span class="info-value"><?php echo htmlspecialchars($profesor['sede_nombre'] ?? 'Sin asignar'); ?></span>
                                           </div>
                                       </div>
                                       
                                       <!-- Teléfono (si existe) -->
                                       <?php if (!empty($profesor['telefono'])): ?>
                                       <div class="info-row">
                                           <div class="info-icon">
                                               <i class="fas fa-phone"></i>
                                           </div>
                                           <div class="info-content">
                                               <span class="info-label">Teléfono:</span>
                                               <span class="info-value"><?php echo htmlspecialchars($profesor['telefono']); ?></span>
                                           </div>
                                       </div>
                                       <?php endif; ?>
                                       
                                       <!-- Especialidad (si existe) -->
                                       <?php if (!empty($profesor['especialidad'])): ?>
                                       <div class="info-row">
                                           <div class="info-icon">
                                               <i class="fas fa-book"></i>
                                           </div>
                                           <div class="info-content">
                                               <span class="info-label">Especialidad:</span>
                                               <span class="info-value"><?php echo htmlspecialchars($profesor['especialidad']); ?></span>
                                           </div>
                                       </div>
                                       <?php endif; ?>
                                   </div>

                                   <!-- Botones de acción -->
                                   <div class="teacher-actions">
                                       <button class="action-btn btn-edit" data-tooltip="Editar" 
                                               onclick="window.location.href='edit_teacher.php?id=<?php echo $profesor['id']; ?>'">
                                           <i class="fas fa-edit"></i>
                                       </button>
                                       <button class="action-btn btn-assign" data-tooltip="Asignar materias"
                                              onclick="abrirModalAsignacion(<?php echo $profesor['id']; ?>, '<?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>')">
                                           <i class="fas fa-chalkboard-teacher"></i>
                                       </button>
                                       <button class="action-btn btn-view" data-tooltip="Ver asignaciones"
                                              onclick="verAsignaciones(<?php echo $profesor['id']; ?>, '<?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>')">
                                           <i class="fas fa-eye"></i>
                                       </button>
                                       <?php if($profesor['estado'] == 'activo'): ?>
                                           <button class="action-btn btn-disable" data-tooltip="Inhabilitar"
                                                   onclick="confirmarCambioEstado(<?php echo $profesor['id']; ?>, 'inactivo')">
                                               <i class="fas fa-ban"></i>
                                           </button>
                                       <?php else: ?>
                                           <button class="action-btn btn-enable" data-tooltip="Activar"
                                                   onclick="confirmarCambioEstado(<?php echo $profesor['id']; ?>, 'activo')">
                                               <i class="fas fa-check"></i>
                                           </button>
                                       <?php endif; ?>
                                       <button class="action-btn btn-delete" data-tooltip="Eliminar"
                                                onclick="confirmarEliminacion(<?php echo $profesor['id']; ?>, '<?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>')">
                                           <i class="fas fa-trash"></i>
                                       </button>
                                   </div>
                               </div>
                           </div>
                       <?php endforeach; ?>
                   <?php endif; ?>
               </div>
           </div>
       </div>
   </div>

   <!-- Modal de Asignación -->
   <div id="asignacionModal" class="modal">
       <div class="modal-content">
           <div class="modal-header">
               <h2 class="modal-title"><i class="fas fa-chalkboard-teacher"></i> Asignar Materias y Grupos</h2>
               <button id="closeModal" class="close-btn">&times;</button>
           </div>
           <div class="modal-body">
               <form id="formAsignacion">
                   <input type="hidden" id="profesor_id" name="profesor_id">
                   
                   <!-- Sección de Sede -->
                   <div class="form-group">
                       <label class="form-label">
                           <i class="fas fa-school"></i> Sede
                       </label>
                       <select name="sede" id="sede" class="form-control" required>
                           <option value="">Seleccione una sede</option>
                           <?php foreach($sedes as $sede): ?>
                               <option value="<?= $sede['id'] ?>"><?= $sede['nombre'] ?></option>
                           <?php endforeach; ?>
                       </select>
                   </div>

                   <!-- Sección de Nivel -->
                   <div class="form-group">
                       <label class="form-label">
                           <i class="fas fa-layer-group"></i> Nivel
                       </label>
                       <select name="nivel" id="nivel" class="form-control" required onchange="cargarGrados()">
                           <option value="">Seleccione un nivel</option>
                           <option value="preescolar">Preescolar</option>
                           <option value="primaria">Primaria</option>
                           <option value="secundaria">Secundaria</option>
                           <option value="media">Media</option>
                       </select>
                   </div>

                   <!-- Sección de Grados -->
                   <div class="form-group">
                       <label class="form-label">
                           <i class="fas fa-users"></i> Grados Disponibles
                       </label>
                       <div id="gradosDisponibles" class="checkbox-grid">
                           <!-- Los grados se cargarán dinámicamente -->
                       </div>
                   </div>

                   <!-- Sección de Materias -->
                   <div class="form-group">
                       <label class="form-label">
                           <i class="fas fa-book"></i> Materias Disponibles
                       </label>
                       <div class="materias-grid">
                           <?php foreach ($materias as $materia): ?>
                               <div class="materia-checkbox">
                                   <input type="checkbox" 
                                          id="materia_<?= $materia['id'] ?>" 
                                          name="materias[]" 
                                          value="<?= $materia['id'] ?>">
                                   <label for="materia_<?= $materia['id'] ?>">
                                       <?= htmlspecialchars($materia['nombre']) ?>
                                   </label>
                               </div>
                           <?php endforeach; ?>
                       </div>
                   </div>

                   <div class="modal-actions">
                       <button type="button" class="btn-secondary" onclick="cerrarModalAsignacion()">
                           Cancelar
                       </button>
                       <button type="submit" class="btn-primary">
                           <i class="fas fa-save"></i> Guardar Asignación
                       </button>
                   </div>
               </form>
           </div>
       </div>
   </div>

   <!-- Modal de Ver Asignaciones -->
   <div id="verAsignacionesModal" class="modal">
       <div class="modal-content">
           <div class="modal-header">
               <h2 class="modal-title"><i class="fas fa-eye"></i> <span id="profesorNombre"></span></h2>
               <button class="close-btn">&times;</button>
           </div>
           <div class="modal-body">
               <div id="asignacionesContainer">
                   <!-- Aquí se cargarán las asignaciones -->
               </div>
           </div>
       </div>
   </div>

   <!-- Scripts para vistas, filtros y modales -->
   <script>
   document.addEventListener('DOMContentLoaded', function() {
       // 1. CONTROLADOR DE VISTAS
       const gridViewBtn = document.getElementById('gridViewBtn');
       const compactViewBtn = document.getElementById('compactViewBtn');
       const listViewBtn = document.getElementById('listViewBtn');
       const teachersContainer = document.getElementById('teachersContainer');
       const teacherCards = document.querySelectorAll('.teacher-card');
       const teachersTable = document.querySelector('.teachers-table');
       
       function switchView(viewType) {
    // Quitar clase active de todos los botones
    gridViewBtn.classList.remove('active');
    compactViewBtn.classList.remove('active');
    listViewBtn.classList.remove('active');
    
    // Agregar clase active al botón seleccionado
    if (viewType === 'grid') {
        gridViewBtn.classList.add('active');
    } else if (viewType === 'compact') {
        compactViewBtn.classList.add('active');
    } else if (viewType === 'list') {
        listViewBtn.classList.add('active');
    }
    
    // Aplicar estilo según la vista
    if (viewType === 'grid') {
        teachersContainer.className = 'teachers-grid';  // Solo aplica esta clase
        teacherCards.forEach(card => card.style.display = 'flex');
        if (teachersTable) teachersTable.style.display = 'none';
    } 
    else if (viewType === 'compact') {
        teachersContainer.className = 'teachers-grid compact-view';  // Usa las clases definidas en CSS
        teacherCards.forEach(card => card.style.display = 'flex');
        if (teachersTable) teachersTable.style.display = 'none';
    }
    else if (viewType === 'list') {
        teachersContainer.className = 'teachers-list';  // Cambia la clase completamente
        teacherCards.forEach(card => card.style.display = 'none');
        if (teachersTable) teachersTable.style.display = 'table';
    }
    
    // Guardar preferencia
    localStorage.setItem('teacherViewPreference', viewType);
}
       
       // Eventos a los botones
       gridViewBtn.addEventListener('click', function(e) {
           e.preventDefault();
           switchView('grid');
       });
       
       compactViewBtn.addEventListener('click', function(e) {
           e.preventDefault();
           switchView('compact');
       });
       
       listViewBtn.addEventListener('click', function(e) {
           e.preventDefault();
           switchView('list');
       });
       
       // 2. FUNCIONES GLOBALES PARA MODALES Y ACCIONES
       // Estas funciones deben estar en window para que los botones las puedan llamar
       
       // Función para confirmar cambio de estado (habilitar/deshabilitar)
       window.confirmarCambioEstado = function(id, estado) {
           const accion = estado === 'activo' ? 'activar' : 'inhabilitar';
           if (confirm(`¿Está seguro que desea ${accion} este profesor?`)) {
               window.location.href = `update_teacher_status.php?id=${id}&estado=${estado}`;
           }
       };
       
       // Función para confirmar eliminación
       window.confirmarEliminacion = function(id, nombre) {
           if (confirm(`¿Está seguro que desea eliminar al profesor ${nombre}? Esta acción no se puede deshacer.`)) {
               window.location.href = `delete_teacher.php?id=${id}`;
           }
       };
       
       // Función para abrir modal de asignación
       window.abrirModalAsignacion = function(id, nombre) {
           console.log('Abriendo modal de asignación para:', id, nombre);
           
           // Establecer el ID del profesor en el campo oculto
           document.getElementById('profesor_id').value = id;
           
           // Mostrar el modal
           const modal = document.getElementById('asignacionModal');
           modal.style.display = 'block';
           
           // Limpiar selecciones previas
           const sedeSelect = document.getElementById('sede');
           const nivelSelect = document.getElementById('nivel');
           const gradosContainer = document.getElementById('gradosDisponibles');
           
           if (sedeSelect) sedeSelect.value = '';
           if (nivelSelect) nivelSelect.value = '';
           if (gradosContainer) gradosContainer.innerHTML = '<p class="no-items">Seleccione una sede y un nivel</p>';
           
           const materiasCheckboxes = document.querySelectorAll('input[name="materias[]"]');
           materiasCheckboxes.forEach(cb => cb.checked = false);
       };
       
       // Función para cerrar modal de asignación
       window.cerrarModalAsignacion = function() {
           const modal = document.getElementById('asignacionModal');
           if (modal) modal.style.display = 'none';
       };
       
       // Función para ver asignaciones
       window.verAsignaciones = function(id, nombre) {
           console.log('Abriendo modal de ver asignaciones para:', id, nombre);
           
           // Mostrar el modal y establecer el nombre del profesor
           const modal = document.getElementById('verAsignacionesModal');
           const nombreElement = document.getElementById('profesorNombre');
           
           if (modal && nombreElement) {
               modal.style.display = 'block';
               modal.setAttribute('data-profesor-id', id);
               nombreElement.textContent = 'Asignaciones de ' + nombre;
               
               // Cargar los datos de asignaciones
               const container = document.getElementById('asignacionesContainer');
               if (container) {
                   container.innerHTML = '<p class="loading">Cargando asignaciones...</p>';
                   
                   fetch(`get_asignaciones.php?profesor_id=${id}`)
                       .then(response => response.json())
                       .then(data => {
                           if (data.length === 0) {
                               container.innerHTML = '<p class="no-items">No hay asignaciones para este profesor</p>';
                               return;
                           }
                           
                           // Agrupar por sede
                           const porSede = {};
                           data.forEach(item => {
                               if (!porSede[item.sede_nombre]) {
                                   porSede[item.sede_nombre] = {};
                               }
                               if (!porSede[item.sede_nombre][item.grado_nombre]) {
                                   porSede[item.sede_nombre][item.grado_nombre] = [];
                               }
                               porSede[item.sede_nombre][item.grado_nombre].push(item);
                           });
                           
                           // Generar HTML
                           let html = '';
                           for (const sede in porSede) {
                               html += `
                                   <div class="asignacion-sede">
                                       <h3><i class="fas fa-school"></i> ${sede}</h3>
                               `;
                               
                               for (const grado in porSede[sede]) {
                                   html += `
                                       <div class="asignacion-grado">
                                           <h4><i class="fas fa-users"></i> ${grado}</h4>
                                           <div class="asignacion-materias">
                                   `;
                                   
                                   porSede[sede][grado].forEach(materia => {
                                       html += `
                                           <div class="asignacion-materia">
                                               <span><i class="fas fa-book"></i> ${materia.materia_nombre}</span>
                                               <button class="btn-delete" onclick="eliminarAsignacion(${materia.id}, ${id})">
                                                   <i class="fas fa-trash"></i>
                                               </button>
                                           </div>
                                       `;
                                   });
                                   
                                   html += `
                                           </div>
                                       </div>
                                   `;
                               }
                               
                               html += `</div>`;
                           }
                           
                           container.innerHTML = html;
                       })
                       .catch(error => {
                           console.error('Error:', error);
                           container.innerHTML = '<p class="error">Error al cargar las asignaciones</p>';
                       });
               }
           }
       };
       
       // Función para eliminar asignación
       window.eliminarAsignacion = function(id, profesorId) {
           if (confirm('¿Está seguro de eliminar esta asignación?')) {
               fetch('eliminar_asignacion.php', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify({ id: id })
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       alert('Asignación eliminada correctamente');
                       // Recargar las asignaciones
                       verAsignaciones(profesorId, document.getElementById('profesorNombre').textContent.replace('Asignaciones de ', ''));
                   } else {
                       alert('Error: ' + (data.message || 'No se pudo eliminar la asignación'));
                   }
               })
               .catch(error => {
                   console.error('Error:', error);
                   alert('Error al eliminar la asignación');
               });
           }
       };
       
       // Funcionalidad para cargar grados
       window.cargarGrados = function() {
           const sedeId = document.getElementById('sede').value;
           const nivel = document.getElementById('nivel').value;
           const gradosContainer = document.getElementById('gradosDisponibles');
           
           if (!sedeId || !nivel) {
               gradosContainer.innerHTML = '<p class="no-items">Seleccione una sede y un nivel</p>';
               return;
           }
           
           // Mostrar indicador de carga
           gradosContainer.innerHTML = '<p class="loading">Cargando grados...</p>';
           
           fetch(`get_grados.php?sede_id=${sedeId}&nivel=${nivel}`)
               .then(response => response.json())
               .then(grados => {
                   if (grados.length === 0) {
                       gradosContainer.innerHTML = '<p class="no-items">No hay grados disponibles para esta sede y nivel</p>';
                       return;
                   }
                   
                   gradosContainer.innerHTML = grados.map(grado => `
                       <div class="checkbox-item">
                           <input type="checkbox" 
                               id="grado_${grado.id}" 
                               name="grados[]" 
                               value="${grado.id}">
                           <label for="grado_${grado.id}">${grado.nombre}</label>
                       </div>
                   `).join('');
               })
               .catch(error => {
                   console.error('Error:', error);
                   gradosContainer.innerHTML = '<p class="error">Error al cargar los grados</p>';
               });
       };
       
       // Configurar el formulario de asignación
       const formAsignacion = document.getElementById('formAsignacion');
       if (formAsignacion) {
           formAsignacion.addEventListener('submit', function(e) {
               e.preventDefault();
               
               const profesorId = document.getElementById('profesor_id').value;
               const gradosSeleccionados = Array.from(document.querySelectorAll('input[name="grados[]"]:checked')).map(el => el.value);
               const materiasSeleccionadas = Array.from(document.querySelectorAll('input[name="materias[]"]:checked')).map(el => el.value);
               
               if (gradosSeleccionados.length === 0) {
                   alert('Debe seleccionar al menos un grado');
                   return;
               }
               
               if (materiasSeleccionadas.length === 0) {
                   alert('Debe seleccionar al menos una materia');
                   return;
               }
               
               // Crear las asignaciones
               const promises = [];
               
               gradosSeleccionados.forEach(gradoId => {
                   materiasSeleccionadas.forEach(materiaId => {
                       promises.push(
                           fetch('guardar_asignacion.php', {
                               method: 'POST',
                               headers: {
                                   'Content-Type': 'application/json',
                               },
                               body: JSON.stringify({
                                   profesor_id: profesorId,
                                   grado_id: gradoId,
                                   materia_id: materiaId
                               })
                           })
                       );
                   });
               });
               
               Promise.all(promises)
                   .then(responses => Promise.all(responses.map(r => r.json())))
                   .then(results => {
                       alert('Asignaciones guardadas correctamente');
                       cerrarModalAsignacion();
                   })
                   .catch(error => {
                       console.error('Error:', error);
                       alert('Error al guardar las asignaciones');
                   });
           });
       }
       
       // Eventos para cerrar modales
       const closeButtons = document.querySelectorAll('.close-btn');
       closeButtons.forEach(btn => {
           btn.addEventListener('click', function() {
               // Cerrar todos los modales
               const modals = document.querySelectorAll('.modal');
               modals.forEach(modal => modal.style.display = 'none');
           });
       });
       
       // Cerrar modales al hacer clic fuera
       window.addEventListener('click', function(event) {
           if (event.target.classList.contains('modal')) {
               event.target.style.display = 'none';
           }
       });
       
       // Cargar vista guardada o predeterminada
       const savedView = localStorage.getItem('teacherViewPreference') || 'grid';
       switchView(savedView);
   });
   </script>
</body>
</html>