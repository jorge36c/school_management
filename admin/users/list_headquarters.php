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
    // Obtener total de sedes y estadísticas
    $sqlCount = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivas,
        SUM(CASE WHEN tipo_ensenanza = 'multigrado' THEN 1 ELSE 0 END) as multigrado
    FROM sedes";
    $stmtCount = $conn->query($sqlCount);
    $stats = $stmtCount->fetch_assoc();

    // Obtener todas las sedes ordenadas por nombre
    $sql = "SELECT * FROM sedes ORDER BY nombre";
    $result = $conn->query($sql);
    $sedes = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sedes[] = $row;
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Configurar el título de la página y el breadcrumb
$page_title = "Gestión de Sedes";
$current_page = 'List headquarters';
$breadcrumb_path = [
    'Inicio',
    'Sedes',
    'List headquarters'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Sedes - Sistema Escolar</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos base -->
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/layouts.css">
    
    <!-- Página específica -->
    <link rel="stylesheet" href="../../assets/css/pages/headquarters.css">

    <!-- Incluir estilos personalizados -->
    <link rel="stylesheet" href="../../assets/css/components/stats-widgets.css">
    <link rel="stylesheet" href="../../assets/css/components/sede-cards.css">
    <link rel="stylesheet" href="../../assets/css/components/control-panel.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>

        <div class="main-content">
            <?php include '../components/topbar.php'; ?>

            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-card card-total">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-school"></i>
                    </div>
                    <h4 class="stat-title">Total Sedes</h4>
                    <div class="stat-value"><?php echo isset($stats['total']) ? $stats['total'] : 0; ?></div>
                </div>
                
                <div class="stat-card card-active">
                    <div class="stat-icon icon-active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 class="stat-title">Sedes Activas</h4>
                    <div class="stat-value"><?php echo isset($stats['activas']) ? $stats['activas'] : 0; ?></div>
                </div>
                
                <div class="stat-card card-inactive">
                    <div class="stat-icon icon-inactive">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h4 class="stat-title">Sedes Inactivas</h4>
                    <div class="stat-value"><?php echo isset($stats['inactivas']) ? $stats['inactivas'] : 0; ?></div>
                </div>
                
                <div class="stat-card card-multigrado">
                    <div class="stat-icon icon-multigrado">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h4 class="stat-title">Sedes Multigrado</h4>
                    <div class="stat-value"><?php echo isset($stats['multigrado']) ? $stats['multigrado'] : 0; ?></div>
                </div>
            </div>

            <!-- Panel de Control -->
            <div class="control-panel">
                <div class="control-panel-inner">
                    <div class="filter-controls">
                        <div class="search-control">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="search-input" placeholder="Buscar sedes por nombre, código o dirección..." autocomplete="off">
                        </div>
                        
                        <div class="filter-select-container">
                            <i class="fas fa-filter"></i>
                            <select id="statusFilter" class="filter-select">
                                <option value="">Estado: Todos</option>
                                <option value="activo">Activas</option>
                                <option value="inactivo">Inactivas</option>
                            </select>
                        </div>
                        
                        <div class="filter-select-container">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <select id="typeFilter" class="filter-select">
                                <option value="">Tipo: Todos</option>
                                <option value="unigrado">Unigrado</option>
                                <option value="multigrado">Multigrado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="action-controls">
                        <div class="view-toggle">
                            <button id="gridViewBtn" class="view-btn" data-tooltip="Vista tarjetas">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button id="compactViewBtn" class="view-btn" data-tooltip="Vista compacta">
                                <i class="fas fa-th"></i>
                            </button>
                            <button id="listViewBtn" class="view-btn" data-tooltip="Vista lista">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                        
                        <a href="create_headquarters.php" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            Nueva Sede
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contenedor de Sedes -->
            <div class="sedes-container">
                <div id="sedesContainer" class="sedes-grid">
                    <?php if (empty($sedes)): ?>
                        <div class="no-results">
                            <div class="no-results-icon"><i class="fas fa-school"></i></div>
                            <div class="no-results-title">No hay sedes registradas</div>
                            <div class="no-results-text">Comienza creando tu primera sede para el sistema escolar.</div>
                            <a href="create_headquarters.php" class="btn-primary">
                                <i class="fas fa-plus"></i> Crear primera sede
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Tabla para vista de lista -->
                        <table class="sedes-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Código DANE</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sedes as $sede): ?>
                                <tr data-estado="<?php echo htmlspecialchars($sede['estado']); ?>" data-tipo="<?php echo htmlspecialchars($sede['tipo_ensenanza'] ?? 'unigrado'); ?>">
                                    <td class="table-name"><?php echo htmlspecialchars($sede['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($sede['codigo_dane']); ?></td>
                                    <td><?php echo htmlspecialchars($sede['direccion'] ?? 'Vereda ' . $sede['nombre']); ?></td>
                                    <td><?php echo !empty($sede['telefono']) ? htmlspecialchars($sede['telefono']) : '-'; ?></td>
                                    <td><?php echo ucfirst($sede['tipo_ensenanza'] ?? 'Unigrado'); ?></td>
                                    <td>
                                        <span class="table-badge <?php echo $sede['estado'] === 'activo' ? 'badge-active' : 'badge-inactive'; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($sede['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="action-btn btn-view" data-tooltip="Ver Detalles" 
                                                    onclick="window.location.href='/school_management/admin/academic/headquarters/view_headquarters.php?id=<?php echo $sede['id']; ?>'">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn btn-edit" data-tooltip="Editar"
                                                    onclick="window.location.href='edit_headquarters.php?id=<?php echo $sede['id']; ?>'">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if($sede['estado'] == 'activo'): ?>
                                                <button class="action-btn btn-disable" data-tooltip="Inhabilitar"
                                                        onclick="confirmarCambioEstado(<?php echo $sede['id']; ?>, 'inactivo')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="action-btn btn-enable" data-tooltip="Activar"
                                                        onclick="confirmarCambioEstado(<?php echo $sede['id']; ?>, 'activo')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Tarjetas para vista de cuadrícula (DISEÑO ACTUALIZADO) -->
                        <?php foreach($sedes as $sede): ?>
                            <div class="sede-card" 
                                data-estado="<?php echo htmlspecialchars($sede['estado']); ?>"
                                data-tipo="<?php echo htmlspecialchars($sede['tipo_ensenanza'] ?? 'unigrado'); ?>">
                                
                                <!-- Encabezado de la tarjeta con el nombre y estado -->
                                <div class="sede-header">
                                    <div class="sede-title-container">
                                        <h3 class="sede-name"><?php echo htmlspecialchars($sede['nombre']); ?></h3>
                                    </div>
                                    
                                    <div class="sede-status">
                                        <span class="status-badge <?php echo $sede['estado'] === 'activo' ? 'status-active' : 'status-inactive'; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($sede['estado']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Código DANE destacado -->
                                <div class="sede-code-container">
                                    <span class="sede-code">
                                        <i class="fas fa-fingerprint"></i>
                                        <?php echo htmlspecialchars($sede['codigo_dane']); ?>
                                    </span>
                                </div>

                                <!-- Contenido principal -->
                                <div class="sede-content">
                                    <div class="sede-info">
                                        <!-- Dirección -->
                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </div>
                                            <div class="info-content">
                                                <span class="info-label">Dirección:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($sede['direccion'] ?? 'Vereda ' . $sede['nombre']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Teléfono (si existe) -->
                                        <?php if (!empty($sede['telefono'])): ?>
                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-phone"></i>
                                            </div>
                                            <div class="info-content">
                                                <span class="info-label">Teléfono:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($sede['telefono']); ?></span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Tipo de enseñanza -->
                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                            </div>
                                            <div class="info-content">
                                                <span class="info-label">Enseñanza:</span>
                                                <span class="info-value"><?php echo ucfirst($sede['tipo_ensenanza'] ?? 'Unigrado'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Email (si existe) -->
                                        <?php if (!empty($sede['email'])): ?>
                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <div class="info-content">
                                                <span class="info-label">Email:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($sede['email']); ?></span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Botones de acción -->
                                    <div class="sede-actions">
                                        <button class="action-btn btn-view" data-tooltip="Ver Detalles" 
                                                onclick="window.location.href='/school_management/admin/academic/headquarters/view_headquarters.php?id=<?php echo $sede['id']; ?>'">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn btn-edit" data-tooltip="Editar Sede"
                                                onclick="window.location.href='edit_headquarters.php?id=<?php echo $sede['id']; ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if($sede['estado'] == 'activo'): ?>
                                            <button class="action-btn btn-disable" data-tooltip="Inhabilitar Sede"
                                                    onclick="confirmarCambioEstado(<?php echo $sede['id']; ?>, 'inactivo')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="action-btn btn-enable" data-tooltip="Activar Sede"
                                                    onclick="confirmarCambioEstado(<?php echo $sede['id']; ?>, 'activo')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir archivos JavaScript -->
    <script src="../../assets/js/sede-view-controller.js"></script>
    <script src="../../assets/js/sede-filter-controller.js"></script>
</body>
</html>