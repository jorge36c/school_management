<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    // Obtener estadísticas
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
        FROM estudiantes");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener lista de estudiantes
    $stmt = $pdo->prepare("
        SELECT e.*, 
               s.nombre as sede_nombre, 
               g.nombre as grado_nombre 
        FROM estudiantes e 
        LEFT JOIN sedes s ON e.sede_id = s.id 
        LEFT JOIN grados g ON e.grado_id = g.id 
        ORDER BY e.apellido, e.nombre
    ");
    $stmt->execute();
    $estudiantes = $stmt->fetchAll();
    
    // Obtener sedes para filtro
    $stmtSedes = $pdo->query("SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre");
    $sedes = $stmtSedes->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error en la gestión de estudiantes: " . $e->getMessage());
    $estudiantes = [];
    $stats = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
    $sedes = [];
}

// Configurar el breadcrumb
$breadcrumb_path = [
    '/admin/dashboard.php' => 'Inicio',
    '/admin/users/list_students.php' => 'Estudiantes'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes - Sistema Escolar</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos base -->
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/layouts.css">
    
    <!-- Página específica -->
    <link rel="stylesheet" href="../../assets/css/pages/students.css">

    <!-- Incluir estilos personalizados -->
    <link rel="stylesheet" href="../../assets/css/components/stats-widgets.css">
    <link rel="stylesheet" href="../../assets/css/components/student-cards.css">
    <link rel="stylesheet" href="../../assets/css/components/control-panel.css">
    
    <!-- Estilos para tooltips -->
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
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h4 class="stat-title">Total Estudiantes</h4>
                    <div class="stat-value"><?php echo isset($stats['total']) ? $stats['total'] : 0; ?></div>
                </div>
                
                <div class="stat-card card-active">
                    <div class="stat-icon icon-active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h4 class="stat-title">Estudiantes Activos</h4>
                    <div class="stat-value"><?php echo isset($stats['activos']) ? $stats['activos'] : 0; ?></div>
                </div>
                
                <div class="stat-card card-inactive">
                    <div class="stat-icon icon-inactive">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <h4 class="stat-title">Estudiantes Inactivos</h4>
                    <div class="stat-value"><?php echo isset($stats['inactivos']) ? $stats['inactivos'] : 0; ?></div>
                </div>
            </div>

            <!-- Panel de Control -->
            <div class="control-panel">
                <div class="control-panel-inner">
                    <div class="filter-controls">
                        <div class="search-control">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="search-input" placeholder="Buscar estudiantes por nombre, apellido o documento..." autocomplete="off">
                        </div>
                        
                        <div class="filter-select-container">
                            <i class="fas fa-filter"></i>
                            <select id="statusFilter" class="filter-select">
                                <option value="">Estado: Todos</option>
                                <option value="activo">Activos</option>
                                <option value="inactivo">Inactivos</option>
                            </select>
                        </div>
                        
                        <div class="filter-select-container">
                            <i class="fas fa-school"></i>
                            <select id="sedeFilter" class="filter-select">
                                <option value="0">Sede: Todas</option>
                                <?php foreach($sedes as $sede): ?>
                                    <option value="<?php echo $sede['id']; ?>">
                                        <?php echo htmlspecialchars($sede['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-select-container">
                            <i class="fas fa-layer-group"></i>
                            <select id="nivelFilter" class="filter-select">
                                <option value="">Nivel: Todos</option>
                                <option value="preescolar">Preescolar</option>
                                <option value="primaria">Primaria</option>
                                <option value="secundaria">Secundaria</option>
                                <option value="media">Media</option>
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
                        
                        <a href="create_student.php" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            Nuevo Estudiante
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contenedor de Estudiantes -->
            <div class="students-container">
                <div id="studentsContainer" class="students-grid">
                    <?php if (empty($estudiantes)): ?>
                        <div class="no-results">
                            <div class="no-results-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="no-results-title">No hay estudiantes registrados</div>
                            <div class="no-results-text">Comienza creando tu primer estudiante para el sistema escolar.</div>
                            <a href="create_student.php" class="btn-primary">
                                <i class="fas fa-plus"></i> Crear primer estudiante
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Tabla para vista de lista -->
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Documento</th>
                                    <th>Nivel</th>
                                    <th>Grado</th>
                                    <th>Sede</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($estudiantes as $estudiante): ?>
                                <tr data-estado="<?php echo htmlspecialchars($estudiante['estado']); ?>" 
                                    data-sede="<?php echo htmlspecialchars($estudiante['sede_id']); ?>"
                                    data-nivel="<?php echo htmlspecialchars($estudiante['nivel']); ?>">
                                    <td><?php echo htmlspecialchars($estudiante['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['documento_numero'] ?? $estudiante['usuario']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($estudiante['nivel'] ?? 'No asignado')); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['grado_nombre'] ?? 'No asignado'); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['sede_nombre'] ?? 'No asignada'); ?></td>
                                    <td>
                                        <span class="table-badge <?php echo $estudiante['estado'] === 'activo' ? 'badge-active' : 'badge-inactive'; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($estudiante['estado']); ?>
                                        </span>
                                    </td>
                                    <!-- DESPUÉS: Columna con botones sin incluir inhabilitar/activar -->
<td>
    <div class="table-actions">
        <button class="action-btn btn-edit" data-tooltip="Editar estudiante"
                onclick="window.location.href='edit_student.php?id=<?php echo $estudiante['id']; ?>'">
            <i class="fas fa-edit"></i>
        </button>
        <button class="action-btn btn-password" data-tooltip="Cambiar contraseña"
               onclick="window.location.href='change_password.php?id=<?php echo $estudiante['id']; ?>'">
            <i class="fas fa-key"></i>
        </button>
        <button class="action-btn btn-delete" data-tooltip="Eliminar estudiante"
                onclick="confirmarEliminacion(<?php echo $estudiante['id']; ?>)">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</td>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Tarjetas para vista de cuadrícula -->
                        <?php foreach($estudiantes as $estudiante): ?>
                            <div class="student-card" 
                                data-estado="<?php echo htmlspecialchars($estudiante['estado']); ?>"
                                data-sede="<?php echo htmlspecialchars($estudiante['sede_id']); ?>"
                                data-nivel="<?php echo htmlspecialchars($estudiante['nivel']); ?>">
                                
                                <!-- Encabezado de la tarjeta con el nombre y estado -->
                                <div class="student-header">
                                    <div class="student-title-container">
                                        <h3 class="student-name">
                                            <?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?>
                                        </h3>
                                    </div>
                                    
                                    <div class="student-status">
                                        <span class="status-badge <?php echo $estudiante['estado'] === 'activo' ? 'status-active' : 'status-inactive'; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($estudiante['estado']); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Contenido principal -->
                                <div class="student-content">
                                    <div class="student-info">
                                        <!-- Documento -->
                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <div class="info-content">
                                                <span class="info-label">Documento:</span>
                                                <span class="info-value">
                                                    <?php echo htmlspecialchars($estudiante['documento_tipo'] ?? ''); ?>
                                                    <?php echo htmlspecialchars($estudiante['documento_numero'] ?? $estudiante['usuario']); ?>
                                               </span>
                                           </div>
                                       </div>
                                       
                                       <!-- Nivel y Grado -->
                                       <div class="info-row">
                                           <div class="info-icon">
                                               <i class="fas fa-graduation-cap"></i>
                                           </div>
                                           <div class="info-content">
                                               <span class="info-label">Nivel/Grado:</span>
                                               <span class="info-value">
                                                   <?php echo ucfirst(htmlspecialchars($estudiante['nivel'] ?? 'No asignado')); ?> - 
                                                   <?php echo htmlspecialchars($estudiante['grado_nombre'] ?? 'No asignado'); ?>
                                               </span>
                                           </div>
                                       </div>
                                       
                                       <!-- Sede -->
                                       <div class="info-row">
                                           <div class="info-icon">
                                               <i class="fas fa-school"></i>
                                           </div>
                                           <div class="info-content">
                                               <span class="info-label">Sede:</span>
                                               <span class="info-value"><?php echo htmlspecialchars($estudiante['sede_nombre'] ?? 'No asignada'); ?></span>
                                           </div>
                                       </div>
                                       
                                       <!-- Información de contacto si existe -->
                                       <?php if (!empty($estudiante['telefono'])): ?>
                                       <div class="info-row">
                                           <div class="info-icon">
                                               <i class="fas fa-phone"></i>
                                           </div>
                                           <div class="info-content">
                                               <span class="info-label">Teléfono:</span>
                                               <span class="info-value"><?php echo htmlspecialchars($estudiante['telefono']); ?></span>
                                           </div>
                                       </div>
                                       <?php endif; ?>
                                       
                                       <?php if (!empty($estudiante['email'])): ?>
                                       <div class="info-row">
                                           <div class="info-icon">
                                               <i class="fas fa-envelope"></i>
                                           </div>
                                           <div class="info-content">
                                               <span class="info-label">Email:</span>
                                               <span class="info-value"><?php echo htmlspecialchars($estudiante['email']); ?></span>
                                           </div>
                                       </div>
                                       <?php endif; ?>
                                   </div>

                                  <!-- ANTES: Botones en tarjetas que incluyen inhabilitar/activar -->
<!-- DESPUÉS: Botones en tarjetas sin incluir inhabilitar/activar -->
<div class="student-actions">
    <button class="action-btn btn-edit" data-tooltip="Editar estudiante" 
            onclick="window.location.href='edit_student.php?id=<?php echo $estudiante['id']; ?>'">
        <i class="fas fa-edit"></i>
    </button>
    <button class="action-btn btn-password" data-tooltip="Cambiar contraseña"
           onclick="window.location.href='change_password.php?id=<?php echo $estudiante['id']; ?>'">
        <i class="fas fa-key"></i>
    </button>
    <button class="action-btn btn-delete" data-tooltip="Eliminar estudiante"
            onclick="confirmarEliminacion(<?php echo $estudiante['id']; ?>)">
        <i class="fas fa-trash"></i>
    </button>
</div>
                               </div>
                           </div>
                       <?php endforeach; ?>
                   <?php endif; ?>
               </div>
           </div>
           
           <!-- Paginación -->
           <div class="pagination-container">
               <div id="pagination" class="pagination">
                   <!-- La paginación se generará dinámicamente con JavaScript -->
               </div>
           </div>
       </div>
   </div>

   <!-- Script para controladores de vista y filtrado -->
   <script>
   document.addEventListener('DOMContentLoaded', function() {
       // 1. CONTROLADOR DE VISTAS
       const gridViewBtn = document.getElementById('gridViewBtn');
       const compactViewBtn = document.getElementById('compactViewBtn');
       const listViewBtn = document.getElementById('listViewBtn');
       const studentsContainer = document.getElementById('studentsContainer');
       const studentCards = document.querySelectorAll('.student-card');
       const studentsTable = document.querySelector('.students-table');
       
       let currentPage = 1;
       const itemsPerPage = 10;
       let filteredItems = [];
       
       function switchView(viewType) {
           console.log('Cambiando a vista:', viewType);
           
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
           
           // Ocultar todos los elementos primero
           studentCards.forEach(card => card.style.display = 'none');
           if (studentsTable) studentsTable.style.display = 'none';
           
           // Aplicar estilo según la vista
           if (viewType === 'grid') {
               studentsContainer.style.display = 'grid';
               studentsContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(300px, 1fr))';
               // Solo mostrar las tarjetas filtradas
               applyFilters();
           } 
           else if (viewType === 'compact') {
               studentsContainer.style.display = 'grid';
               studentsContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(220px, 1fr))';
               // Solo mostrar las tarjetas filtradas
               applyFilters();
           }
           else if (viewType === 'list') {
               studentsContainer.style.display = 'block';
               // En vista de lista, todas las tarjetas deben estar ocultas
               studentCards.forEach(card => card.style.display = 'none');
               // Y la tabla debe estar visible
               if (studentsTable) studentsTable.style.display = 'table';
               // Aplicar filtros a las filas de la tabla
               applyFilters();
           }
           
           // Guardar preferencia
           localStorage.setItem('studentViewPreference', viewType);
       }
       
       // 2. CONTROLADOR DE FILTROS
       const searchInput = document.getElementById('searchInput');
       const statusFilter = document.getElementById('statusFilter');
       const sedeFilter = document.getElementById('sedeFilter');
       const nivelFilter = document.getElementById('nivelFilter');
       
       function applyFilters() {
           const searchTerm = searchInput.value.toLowerCase();
           const statusValue = statusFilter.value;
           const sedeValue = sedeFilter.value;
           const nivelValue = nivelFilter.value;
           
           // Reiniciar array de elementos filtrados
           filteredItems = [];
           
           // Determinar la vista actual
           const currentView = listViewBtn.classList.contains('active') ? 'list' : 
                        compactViewBtn.classList.contains('active') ? 'compact' : 'grid';
           
           // Filtrar según la vista actual
           if (currentView === 'list') {
               // Filtrar filas de tabla
               const tableRows = document.querySelectorAll('.students-table tbody tr');
               tableRows.forEach(row => {
                   const rowText = row.textContent.toLowerCase();
                   const rowStatus = row.getAttribute('data-estado');
                   const rowSede = row.getAttribute('data-sede');
                   const rowNivel = row.getAttribute('data-nivel');
                   
                   const matchesSearch = !searchTerm || rowText.includes(searchTerm);
                   const matchesStatus = !statusValue || rowStatus === statusValue;
                   const matchesSede = !sedeValue || sedeValue === '0' || rowSede === sedeValue;
                   const matchesNivel = !nivelValue || rowNivel === nivelValue;
                   
                   const isVisible = matchesSearch && matchesStatus && matchesSede && matchesNivel;
                   
                   row.style.display = isVisible ? '' : 'none';
                   
                   if (isVisible) {
                       filteredItems.push(row);
                   }
               });
           } else {
               // Filtrar tarjetas
               studentCards.forEach(card => {
                   const cardName = card.querySelector('.student-name')?.textContent.toLowerCase() || '';
                   const cardInfo = Array.from(card.querySelectorAll('.info-value')).map(el => el.textContent.toLowerCase()).join(' ');
                   const cardStatus = card.getAttribute('data-estado');
                   const cardSede = card.getAttribute('data-sede');
                   const cardNivel = card.getAttribute('data-nivel');
                   
                   const matchesSearch = !searchTerm || cardName.includes(searchTerm) || cardInfo.includes(searchTerm);
                   const matchesStatus = !statusValue || cardStatus === statusValue;
                   const matchesSede = !sedeValue || sedeValue === '0' || cardSede === sedeValue;
                   const matchesNivel = !nivelValue || cardNivel === nivelValue;
                   
                   const isVisible = matchesSearch && matchesStatus && matchesSede && matchesNivel;
                   
                   card.style.display = isVisible ? 'flex' : 'none';
                   
                   if (isVisible) {
                       filteredItems.push(card);
                   }
               });
           }
           
           // Mostrar mensaje de "sin resultados" si corresponde
           const noResultsElement = document.querySelector('.no-results');
           if (filteredItems.length === 0) {
               if (!noResultsElement) {
                   const noResultsDiv = document.createElement('div');
                   noResultsDiv.className = 'no-results';
                   
                   noResultsDiv.innerHTML = `
                       <div class="no-results-icon"><i class="fas fa-search"></i></div>
                       <div class="no-results-title">No se encontraron estudiantes</div>
                       <div class="no-results-text">Intenta con otros términos de búsqueda o ajusta los filtros.</div>
                       <button class="btn-primary" onclick="clearFilters()">
                           <i class="fas fa-times"></i> Limpiar filtros
                       </button>
                   `;
                   
                   studentsContainer.appendChild(noResultsDiv);
               } else {
                   noResultsElement.style.display = 'block';
               }
           } else if (noResultsElement) {
               noResultsElement.style.display = 'none';
           }
           
           // Actualizar paginación
           updatePagination();
           showPage(1);
       }
       
       // Función para mostrar elementos según paginación
       function showPage(page) {
           currentPage = page;
           
           const startIndex = (page - 1) * itemsPerPage;
           const endIndex = Math.min(startIndex + itemsPerPage, filteredItems.length);
           
           // No hacer nada si no hay elementos
           if (filteredItems.length === 0) return;
           
           // Actualizar botones de paginación
           const paginationButtons = document.querySelectorAll('#pagination button');
           paginationButtons.forEach(button => {
               button.classList.remove('active');
               if (button.getAttribute('data-page') == page) {
                   button.classList.add('active');
               }
           });
       }
       
       // Función para actualizar paginación
       function updatePagination() {
           const paginationContainer = document.getElementById('pagination');
           if (!paginationContainer) return;
           
           paginationContainer.innerHTML = '';
           
           const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
           
           // Solo mostrar paginación si hay más de una página
           if (totalPages <= 1) {
               paginationContainer.style.display = 'none';
               return;
           }
           
           paginationContainer.style.display = 'flex';
           
           // Botón anterior
           if (currentPage > 1) {
               const prevButton = document.createElement('button');
               prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
               prevButton.onclick = () => showPage(currentPage - 1);
               paginationContainer.appendChild(prevButton);
           }
           
           // Números de página
           for (let i = 1; i <= totalPages; i++) {
               if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                   const pageButton = document.createElement('button');
                   pageButton.textContent = i;
                   pageButton.setAttribute('data-page', i);
                   pageButton.onclick = () => showPage(i);
                   if (i === currentPage) {
                       pageButton.classList.add('active');
                   }
                   
                   paginationContainer.appendChild(pageButton);
               } else if ((i === currentPage - 2 && currentPage > 3) || (i === currentPage + 2 && currentPage < totalPages - 2)) {
                   const ellipsis = document.createElement('button');
                   ellipsis.textContent = '...';
                   ellipsis.disabled = true;
                   paginationContainer.appendChild(ellipsis);
               }
           }
           
           // Botón siguiente
           if (currentPage < totalPages) {
               const nextButton = document.createElement('button');
               nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
               nextButton.onclick = () => showPage(currentPage + 1);
               paginationContainer.appendChild(nextButton);
           }
       }
       
       // Event listeners para filtros
       searchInput.addEventListener('input', applyFilters);
       statusFilter.addEventListener('change', applyFilters);
       sedeFilter.addEventListener('change', applyFilters);
       nivelFilter.addEventListener('change', applyFilters);
       
       // Event listeners para botones de vista
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
       
       // Función global para limpiar filtros
       window.clearFilters = function() {
           searchInput.value = '';
           statusFilter.value = '';
           sedeFilter.value = '0';
           nivelFilter.value = '';
           
           applyFilters();
       };
       
       // Función global para confirmar eliminación
       window.confirmarEliminacion = function(id) {
           if (confirm('¿Está seguro que desea eliminar este estudiante?')) {
               window.location.href = 'delete_student.php?id=' + id;
           }
       };
       
       
       
       // Cargar vista guardada o predeterminada
       const savedView = localStorage.getItem('studentViewPreference') || 'grid';
       switchView(savedView);
       
       // Inicializar filtros
       applyFilters();
   });
   </script>
</body>
</html>
                  