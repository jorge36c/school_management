<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    // Obtener estadísticas
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivas
        FROM asignaturas");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener parámetros de filtro
    $filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : '';
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

    // Construir la consulta base
    $sql = "SELECT a.*, s.nombre as sede_nombre, p.nombre as profesor_nombre, 
            p.apellido as profesor_apellido 
            FROM asignaturas a 
            LEFT JOIN sedes s ON a.sede_id = s.id 
            LEFT JOIN profesores p ON a.profesor_id = p.id 
            WHERE 1=1";
    $params = [];

    // Aplicar filtros si existen
    if (!empty($filtro_tipo) && !empty($busqueda)) {
        switch ($filtro_tipo) {
            case 'nombre':
                $sql .= " AND a.nombre LIKE ?";
                $params[] = "%$busqueda%";
                break;
            case 'sede':
                $sql .= " AND s.nombre LIKE ?";
                $params[] = "%$busqueda%";
                break;
            case 'profesor':
                $sql .= " AND (p.nombre LIKE ? OR p.apellido LIKE ?)";
                $params[] = "%$busqueda%";
                $params[] = "%$busqueda%";
                break;
        }
    }

    $sql .= " ORDER BY a.nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $asignaturas = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Error en la gestión de asignaturas: " . $e->getMessage());
    $asignaturas = [];
    $stats = ['total' => 0, 'activas' => 0, 'inactivas' => 0];
}

// Configurar el breadcrumb
$breadcrumb_path = [
    '/admin/dashboard.php' => 'Inicio',
    '/admin/academic/list_dba.php' => 'Gestión de Materias'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asignaturas - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        :root {
            --primary-color: #1a2b40;
            --primary-light: #2563eb;
            --secondary-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --danger-color: #ef4444;
            --purple-color: #8b5cf6;
            --pink-color: #ec4899;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --bg-light: #f3f4f6;
            --bg-white: #ffffff;
            --border-color: #e5e7eb;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            --transition: all 0.3s ease;
            --border-radius: 0.75rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .icon-total { background: var(--secondary-color); }
        .icon-activas { background: var(--success-color); }
        .icon-inactivas { background: var(--danger-color); }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-info h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .stat-info p {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Data table */
        .data-table {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .search-controls {
            display: flex;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            gap: 1rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            width: 100%;
        }

        .search-controls select,
        .search-controls input {
            padding: 0.6rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background-color: var(--bg-white);
        }

        .search-controls input {
            flex: 1;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-light);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: var(--text-muted);
            color: white;
        }

        .btn-secondary:hover {
            background-color: var(--text-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-header i {
            color: var(--primary-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: var(--bg-light);
            padding: 0.75rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 0.75rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        tr:hover {
            background-color: var(--bg-light);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            color: white;
        }

        .edit-btn {
            background-color: var(--primary-light);
        }

        .delete-btn {
            background-color: var(--danger-color);
        }

        .performance-btn {
            background-color: var(--purple-color);
        }

        .activate-btn {
            background-color: var(--success-color);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: var(--bg-white);
            margin: 2% auto;
            width: 90%;
            max-width: 800px;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }

        .close-btn:hover {
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Tablas de desempeños */
        .table-desempenos {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .table-desempenos th,
        .table-desempenos td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table-desempenos th {
            background-color: var(--bg-light);
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        .tipo-desempeno {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: capitalize;
        }

        .tipo-cognitivo {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .tipo-procedimental {
            background-color: #dcfce7;
            color: #166534;
        }

        .tipo-actitudinal {
            background-color: #fef3c7;
            color: #92400e;
        }

        .descripcion-desempeno {
            line-height: 1.5;
            color: var(--text-secondary);
            font-size: 0.875rem;
            max-width: 100%;
            overflow-wrap: break-word;
            word-wrap: break-word;
            hyphens: auto;
        }

        .porcentaje-desempeno {
            font-weight: 600;
            color: var(--text-primary);
            background-color: var(--bg-light);
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            display: inline-block;
            font-size: 0.875rem;
        }

        .acciones-desempeno {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-edit,
        .btn-delete {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-edit {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .btn-edit:hover {
            background-color: #bae6fd;
        }

        .btn-delete {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background-color: #fecaca;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-primary);
            background-color: var(--bg-white);
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background-color: var(--bg-light);
            border-top: 1px solid var(--border-color);
        }

        /* Utilidades */
        .text-center {
            text-align: center;
        }

        .no-data {
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .btn-nuevo-desempeno {
            background-color: var(--primary-light);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            font-weight: 500;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-nuevo-desempeno:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .desempenos-header {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.25rem;
        }

        .loading {
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
        }

        .loading::before {
            content: '';
            display: block;
            width: 40px;
            height: 40px;
            margin: 0 auto 1rem;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-light);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert-error {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        /* Media queries */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 280px;
            }
        }

        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 767.98px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-form {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }

        @media (max-width: 575.98px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .search-controls {
                padding: 1rem;
            }
            
            th, td {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../components/topbar.php'; ?>

            <div class="dashboard-content">
                <!-- Tarjetas de estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon icon-total">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>Total Asignaturas</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-activas">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['activas']; ?></h3>
                            <p>Asignaturas Activas</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-inactivas">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['inactivas']; ?></h3>
                            <p>Asignaturas Inactivas</p>
                        </div>
                    </div>
                </div>

                <!-- Tabla de datos -->
                <div class="data-table">
                    <div class="search-controls">
                        <form method="GET" class="search-form">
                            <select name="filtro_tipo">
                                <option value="">Filtrar por...</option>
                                <option value="nombre" <?php echo $filtro_tipo === 'nombre' ? 'selected' : ''; ?>>Nombre</option>
                                <option value="sede" <?php echo $filtro_tipo === 'sede' ? 'selected' : ''; ?>>Sede</option>
                                <option value="profesor" <?php echo $filtro_tipo === 'profesor' ? 'selected' : ''; ?>>Profesor</option>
                            </select>
                            <input type="text" name="busqueda" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Buscar asignatura...">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Buscar
                            </button>
                            <?php if (!empty($filtro_tipo) || !empty($busqueda)): ?>
                                <a href="list_dba.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Limpiar
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="table-header">
                        <h2><i class="fas fa-book"></i> Lista de Asignaturas</h2>
                        <a href="create_materia.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Nueva Asignatura
                        </a>
                    </div>

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
                                <?php if (empty($asignaturas)): ?>
                                    <tr>
                                        <td colspan="5" class="no-data">
                                            <i class="fas fa-info-circle"></i> No hay asignaturas registradas
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($asignaturas as $asignatura): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asignatura['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($asignatura['sede_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($asignatura['profesor_nombre'] . ' ' . $asignatura['profesor_apellido']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $asignatura['estado'] === 'activo' ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo ucfirst($asignatura['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="edit_materia.php?id=<?php echo $asignatura['id']; ?>" 
                                                       class="action-btn edit-btn" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" onclick="abrirModalDesempenos(<?php echo $asignatura['id']; ?>, '<?php echo htmlspecialchars($asignatura['nombre']); ?>')" 
                                                       class="action-btn performance-btn" title="Desempeños">
                                                        <i class="fas fa-chart-line"></i>
                                                    </a>
                                                    <?php if ($asignatura['estado'] === 'activo'): ?>
                                                        <button onclick="confirmarCambioEstado(<?php echo $asignatura['id']; ?>, 'inactivo')" 
                                                                class="action-btn delete-btn" 
                                                                title="Deshabilitar">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button onclick="confirmarCambioEstado(<?php echo $asignatura['id']; ?>, 'activo')" 
                                                                class="action-btn activate-btn" 
                                                                title="Habilitar">
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

    <!-- Modal de Desempeños -->
    <div id="modalDesempenos" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalAsignaturaTitulo">Desempeños</h2>
                <button class="close-btn" onclick="cerrarModalDesempenos()">×</button>
            </div>
            <div class="modal-body" id="contenidoDesempenos">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Modal para Nuevo/Editar Desempeño -->
    <div id="modalFormDesempeno" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalFormTitle">Nuevo Desempeño</h2>
                <button class="close-btn" onclick="cerrarModalFormDesempeno()">×</button>
            </div>
            <div class="modal-body">
                <form id="formDesempeno" onsubmit="return guardarDesempeno(event)">
                    <input type="hidden" name="id" id="desempeno_id">
                    <input type="hidden" name="asignatura_id" id="asignatura_id">
                    
                    <div class="form-group">
                        <label for="tipo">Tipo de Desempeño</label>
                        <select name="tipo" id="tipo" class="form-control" required>
                            <option value="cognitivo">Cognitivo</option>
                            <option value="procedimental">Procedimental</option>
                            <option value="actitudinal">Actitudinal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="porcentaje">Porcentaje</label>
                        <input type="number" name="porcentaje" id="porcentaje" 
                               class="form-control" required min="0" max="100" step="0.01">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalFormDesempeno()">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Variables globales para mantener el contexto
    let currentAsignaturaId = null;
    let currentAsignaturaNombre = null;

    function confirmarCambioEstado(id, nuevoEstado) {
        const mensaje = nuevoEstado === 'inactivo' 
            ? '¿Está seguro que desea deshabilitar esta asignatura?' 
            : '¿Está seguro que desea habilitar esta asignatura?';
        
        if (confirm(mensaje)) {
            fetch('delete_materia.php?id=' + id + '&estado=' + nuevoEstado)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar el estado de la asignatura');
                });
        }
    }

    function abrirModalDesempenos(asignaturaId, nombreAsignatura) {
        currentAsignaturaId = asignaturaId;
        currentAsignaturaNombre = nombreAsignatura;
        const modal = document.getElementById('modalDesempenos');
        const modalTitle = document.getElementById('modalAsignaturaTitulo');
        const modalBody = document.getElementById('contenidoDesempenos');
        
        modal.style.display = 'block';
        modalTitle.textContent = 'Desempeños - ' + nombreAsignatura;
        
        // Cargar los desempeños
        modalBody.innerHTML = '<div class="loading">Cargando desempeños...</div>';
        
        fetch(`get_desempenos.php?asignatura_id=${asignaturaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `
                        <div class="desempenos-container">
                            <div class="desempenos-header">
                                <button class="btn-nuevo-desempeno" onclick="nuevoDesempeno()">
                                    <i class="fas fa-plus"></i> Nuevo Desempeño
                                </button>
                            </div>
                            <table class="table-desempenos">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Porcentaje</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    if (data.desempenos && data.desempenos.length > 0) {
                        data.desempenos.forEach(desempeno => {
                            html += `
                                <tr>
                                    <td>
                                        <span class="tipo-desempeno tipo-${desempeno.tipo.toLowerCase()}">
                                            ${desempeno.tipo}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="descripcion-desempeno">
                                            ${desempeno.descripcion}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="porcentaje-desempeno">
                                            ${desempeno.porcentaje}%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="acciones-desempeno">
                                            <button onclick="editarDesempeno(${desempeno.id})" 
                                                    class="btn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="eliminarDesempeno(${desempeno.id})" 
                                                    class="btn-delete" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        html += `
                            <tr>
                                <td colspan="4" class="no-data">
                                    No hay desempeños configurados
                                </td>
                            </tr>`;
                    }
                    
                    html += `</tbody></table></div>`;
                    modalBody.innerHTML = html;
                } else {
                    modalBody.innerHTML = `
                        <div class="alert-error">
                            ${data.message || 'Error al cargar los desempeños'}
                        </div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert-error">
                        Error al cargar los desempeños. Por favor, intente nuevamente.
                    </div>`;
            });
    }

    function cerrarModalDesempenos() {
        document.getElementById('modalDesempenos').style.display = 'none';
    }

    function nuevoDesempeno() {
        document.getElementById('modalFormTitle').textContent = 'Nuevo Desempeño';
        document.getElementById('formDesempeno').reset();
        document.getElementById('desempeno_id').value = '';
        document.getElementById('asignatura_id').value = currentAsignaturaId;
        document.getElementById('modalFormDesempeno').style.display = 'block';
    }

    function editarDesempeno(id) {
        document.getElementById('modalFormTitle').textContent = 'Editar Desempeño';
        document.getElementById('desempeno_id').value = id;
        
        fetch(`get_desempeno.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('tipo').value = data.desempeno.tipo;
                    document.getElementById('descripcion').value = data.desempeno.descripcion;
                    document.getElementById('porcentaje').value = data.desempeno.porcentaje;
                    document.getElementById('modalFormDesempeno').style.display = 'block';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar el desempeño');
            });
    }

    function guardarDesempeno(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch('save_desempeno.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cerrarModalFormDesempeno();
                abrirModalDesempenos(currentAsignaturaId, currentAsignaturaNombre);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar el desempeño');
        });

        return false;
    }

    function eliminarDesempeno(id) {
        if (confirm('¿Está seguro que desea eliminar este desempeño?')) {
            fetch(`delete_desempeno.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        abrirModalDesempenos(currentAsignaturaId, currentAsignaturaNombre);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el desempeño');
                });
        }
    }

    function cerrarModalFormDesempeno() {
        document.getElementById('modalFormDesempeno').style.display = 'none';
    }

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            cerrarModalDesempenos();
            cerrarModalFormDesempeno();
        }
    });

    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>