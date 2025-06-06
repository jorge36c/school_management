<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Obtener parámetros de filtro
$filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

try {
    // Construir la consulta base
    $sql = "SELECT asignaturas.*, sedes.nombre AS sede_nombre, profesores.nombre AS profesor_nombre, profesores.apellido AS profesor_apellido FROM asignaturas 
            LEFT JOIN sedes ON asignaturas.sede_id = sedes.id
            LEFT JOIN profesores ON asignaturas.profesor_id = profesores.id
            WHERE 1=1";
    $params = [];

    // Aplicar filtros si existen
    if (!empty($filtro_tipo) && !empty($busqueda)) {
        switch($filtro_tipo) {
            case 'nombre':
                $sql .= " AND asignaturas.nombre LIKE ?";
                $params = ["%$busqueda%"];
                break;
            case 'sede':
                $sql .= " AND sedes.nombre LIKE ?";
                $params = ["%$busqueda%"];
                break;
            case 'profesor':
                $sql .= " AND CONCAT(profesores.nombre, ' ', profesores.apellido) LIKE ?";
                $params = ["%$busqueda%"];
                break;
        }
    }

    $sql .= " ORDER BY asignaturas.nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $materias = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error al obtener la lista de materias: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Materias - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            background: #f8fafc;
        }

        /* Top Bar Styles */
        .top-bar {
            background: #2c3e50;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ecf0f1;
            font-size: 0.9rem;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .top-bar-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            background: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: white;
        }

        .user-role {
            font-size: 0.875rem;
            color: #94a3b8;
        }

        /* Content Styles */
        .content-wrapper {
            padding: 2rem;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Filters Section */
        .filters-section {
            padding: 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .filters-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
        }

        .filter-input,
        .filter-select {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            min-width: 200px;
        }

        /* Table Styles */
        .table-container {
            padding: 1.5rem;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
            color: #64748b;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f8fafc;
        }

        /* Status Badge */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-activo {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactivo {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem;
            border-radius: 0.5rem;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-edit { background: #3b82f6; }
        .btn-delete { background: #ef4444; }
        .btn-activate { background: #10b981; }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .filters-form {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>

        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="top-bar-left">
                    <button id="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <i class="fas fa-book"></i>
                        <span>/ Materias</span>
                    </div>
                </div>
                
                <div class="top-bar-right">
                    <div class="top-bar-time">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></span>
                            <span class="user-role">Administrador</span>
                        </div>
                    </div>
                    <a href="../../auth/logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </div>

            <div class="content-wrapper">
                <!-- Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-book text-primary"></i>
                            Lista de Materias
                        </h2>
                        <a href="create_materia.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Nueva Materia
                        </a>
                    </div>

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" class="filters-form">
                            <div class="filter-group">
                                <label class="filter-label">Filtrar por</label>
                                <select name="filtro_tipo" class="filter-select">
                                    <option value="">Seleccione un filtro</option>
                                    <option value="nombre" <?php echo $filtro_tipo === 'nombre' ? 'selected' : ''; ?>>Nombre</option>
                                    <option value="sede" <?php echo $filtro_tipo === 'sede' ? 'selected' : ''; ?>>Sede</option>
                                    <option value="profesor" <?php echo $filtro_tipo === 'profesor' ? 'selected' : ''; ?>>Profesor</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">Búsqueda</label>
                                <input type="text" name="busqueda" class="filter-input" 
                                       value="<?php echo htmlspecialchars($busqueda); ?>"
                                       placeholder="Ingrese su búsqueda...">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Buscar
                            </button>

                            <?php if(!empty($filtro_tipo) || !empty($busqueda)): ?>
                                <a href="list_materias.php" class="btn btn-danger">
                                    <i class="fas fa-times"></i>
                                    Limpiar
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Table -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Sede</th>
                                    <th>Profesor</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($materias)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 1.5rem;">
                                            No se encontraron materias
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($materias as $materia): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($materia['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($materia['sede_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($materia['profesor_nombre'] . ' ' . $materia['profesor_apellido']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $materia['estado']; ?>">
                                                    <i class="fas fa-circle text-xs"></i>
                                                    <?php echo ucfirst($materia['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="edit_materia.php?id=<?php echo $materia['id']; ?>" 
                                                       class="btn-action btn-edit" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if($materia['estado'] == 'activo'): ?>
                                                        <button onclick="confirmarCambioEstado(<?php echo $materia['id']; ?>, 'inactivo')"
                                                                class="btn-action btn-delete" title="Inhabilitar">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button onclick="confirmarCambioEstado(<?php echo $materia['id']; ?>, 'activo')"
                                                                class="btn-action btn-activate" title="Activar">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Actualizar reloj
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
            const mainContent = document.querySelector('.main-content');
            mainContent.style.marginLeft = mainContent.style.marginLeft === '0px' ? '260px' : '0px';
        });

        // Función para confirmar cambio de estado
        function confirmarCambioEstado(id, nuevoEstado) {
            const mensaje = nuevoEstado === 'inactivo' 
                ? '¿Está seguro que desea inhabilitar esta materia?' 
                : '¿Está seguro que desea activar esta materia?';
            
            if(confirm(mensaje)) {
                window.location.href = `toggle_status.php?id=${id}&estado=${nuevoEstado}`;
            }
        }

        // Tooltips para botones de acción
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', e => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = element.getAttribute('title');
                tooltip.style.cssText = `
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    top: ${e.pageY + 10}px;
                    left: ${e.pageX + 10}px;
                    z-index: 1000;
                    pointer-events: none;
                    opacity: 0;
                    transition: opacity 0.2s;
                `;
                document.body.appendChild(tooltip);
                setTimeout(() => tooltip.style.opacity = '1', 0);

                element.addEventListener('mouseleave', () => {
                    tooltip.style.opacity = '0';
                    setTimeout(() => tooltip.remove(), 200);
                });
            });
        });

        // Manejar responsive
        function handleResponsive() {
            const container = document.querySelector('.admin-container');
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth <= 768) {
                container.classList.add('sidebar-collapsed');
                mainContent.style.marginLeft = '0';
            } else {
                container.classList.remove('sidebar-collapsed');
                mainContent.style.marginLeft = '260px';
            }
        }

        window.addEventListener('resize', handleResponsive);
        handleResponsive();

        // Animación de entrada para las filas
        document.querySelectorAll('tbody tr').forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, 50 * index);
        });
    </script>

</body>
</html>
