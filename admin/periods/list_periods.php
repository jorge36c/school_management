<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

try {
    // Obtener año lectivo actual
    $stmt = $pdo->query("SELECT * FROM anos_lectivos WHERE id = 4");
    $currentYear = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener periodo actual
    $stmt = $pdo->query("SELECT * FROM periodos_academicos WHERE id = 5");
    $currentPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener los periodos
    $stmt = $pdo->prepare("
        SELECT 
            pa.id,
            pa.numero_periodo,
            pa.fecha_inicio,
            pa.fecha_fin,
            pa.porcentaje_calificacion,
            pa.estado_periodo,
            pa.estado,
            al.id as ano_lectivo_id,
            al.nombre as ano_lectivo
        FROM periodos_academicos pa
        JOIN anos_lectivos al ON pa.ano_lectivo_id = al.id
        WHERE pa.estado = 'activo'
        ORDER BY 
            al.nombre DESC,
            pa.id DESC
    ");
    $stmt->execute();
    $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar periodos por año lectivo
    $periodos_por_ano = [];
    foreach ($periodos as $periodo) {
        $periodos_por_ano[$periodo['ano_lectivo_id']][] = $periodo;
    }

    // Primero, verificamos si hay años lectivos activos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM anos_lectivos WHERE estado = 'activo'");
    $total_anos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch(PDOException $e) {
    error_log("Error en la consulta: " . $e->getMessage());
    $periodos = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Periodos - Sistema Escolar</title>
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
        .icon-activos { background: var(--success-color); }
        .icon-inactivos { background: var(--danger-color); }

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

        .button-group {
            display: flex;
            gap: 0.75rem;
        }

        .btn-nuevo {
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
            background-color: var(--primary-light);
            color: white;
        }

        .btn-nuevo:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-nuevo.disabled {
            background-color: var(--text-muted);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .btn-nuevo.disabled:hover {
            transform: none;
            background-color: var(--text-muted);
        }

        /* Año Lectivo Grupo */
        .ano-lectivo-grupo {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .ano-header {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
            background-color: var(--bg-light);
        }

        .ano-header:hover {
            background-color: #edf2f7;
        }

        .ano-header i {
            margin-right: 1rem;
            transition: transform 0.2s;
            color: var(--primary-light);
        }

        .ano-header.collapsed i {
            transform: rotate(-90deg);
        }

        .ano-header h3 {
            margin: 0;
            flex: 1;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .periodo-count {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            background-color: var(--bg-light);
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
        }

        .periodos-container {
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            max-height: 2000px; /* Valor alto para permitir contenido completo */
        }

        .periodos-container.collapsed {
            max-height: 0;
        }

        /* Tabla dentro de período */
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

        .align-middle {
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }

        .fecha-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.875rem;
        }

        /* Botones de acción */
        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
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

        .btn-edit {
            background-color: var(--primary-light);
        }

        .btn-delete {
            background-color: var(--danger-color);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Estado del período */
        .btn-estado {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            border: none;
            color: white;
        }

        .btn-estado.en_curso {
            background-color: var(--secondary-color);
        }

        .btn-estado.finalizado {
            background-color: var(--success-color);
        }

        .btn-estado.cerrado {
            background-color: var(--text-muted);
        }

        .btn-estado:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .estado-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--bg-white);
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
            z-index: 100;
            min-width: 150px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .estado-menu.active {
            display: block;
        }

        .estado-opcion {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .estado-opcion:hover {
            background-color: var(--bg-light);
        }

        .estado-opcion.en_curso:hover {
            color: var(--secondary-color);
        }

        .estado-opcion.finalizado:hover {
            color: var(--success-color);
        }

        .estado-opcion.cerrado:hover {
            color: var(--text-muted);
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
            max-width: 600px;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }

        .modal-ano {
            max-width: 450px !important;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--primary-light);
            color: white;
        }

        .modal-header .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .close-btn:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 1.5rem;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary-light);
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

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background-color: var(--bg-light);
            border-top: 1px solid var(--border-color);
        }

        .btn-primary {
            background-color: var(--primary-light);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: var(--text-muted);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background-color: var(--text-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Mensaje de alerta */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .alert-warning {
            background-color: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
        }

        .alert i {
            font-size: 1.125rem;
        }

        /* Modal para boletines */
        .modal-boletines {
            max-width: 500px !important;
        }

        .modal-boletines .modal-header {
            background-color: var(--primary-light);
            color: white;
        }

        .modal-boletines select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background-color: var(--bg-white);
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        .modal-boletines select:disabled {
            background-color: var(--bg-light);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .btn-info {
            background-color: var(--info-color);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-info:hover {
            background-color: #1e60d6;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
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
            
            .button-group {
                flex-direction: column;
            }
        }

        @media (max-width: 767.98px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .button-group {
                width: 100%;
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
            
            th, td {
                padding: 0.75rem 1rem;
            }
            
            .actions {
                flex-wrap: wrap;
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
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($periodos); ?></h3>
                            <p>Total Periodos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-activos">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php 
                                $periodos_activos = array_filter($periodos, function($periodo) {
                                    return $periodo['estado_periodo'] !== 'cerrado';
                                });
                                echo count($periodos_activos);
                            ?></h3>
                            <p>Periodos Activos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-inactivos">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php 
                                $periodos_inactivos = array_filter($periodos, function($periodo) {
                                    return $periodo['estado_periodo'] === 'cerrado';
                                });
                                echo count($periodos_inactivos);
                            ?></h3>
                            <p>Periodos Cerrados</p>
                        </div>
                    </div>
                </div>

                <!-- Tabla de datos -->
                <div class="data-table">
                    <div class="table-header">
                        <h2><i class="fas fa-calendar-alt"></i> Lista de Periodos</h2>
                        <div class="button-group">
                            <button class="btn-nuevo" onclick="abrirModalNuevoAno()">
                                <i class="fas fa-calendar-plus"></i>
                                Nuevo Año Lectivo
                            </button>
                            <button class="btn-nuevo <?php echo $total_anos == 0 ? 'disabled' : ''; ?>" 
                                    onclick="<?php echo $total_anos > 0 ? 'abrirModalNuevoPeriodo()' : 'alertaNoAnoLectivo()'; ?>"
                                    <?php echo $total_anos == 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-plus"></i>
                                Nuevo Periodo
                            </button>
                        </div>
                    </div>

                    <?php if (empty($periodos_por_ano)): ?>
                        <div class="empty-state" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <h3 style="margin-bottom: 0.5rem; font-weight: 600;">No hay periodos configurados</h3>
                            <p>Cree un año lectivo y agregue periodos académicos.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($periodos_por_ano as $ano_id => $periodos_ano): 
                            $primer_periodo = reset($periodos_ano); // Obtener el primer periodo del año
                        ?>
                            <div class="ano-lectivo-grupo">
                                <div class="ano-header" onclick="togglePeriodos(<?php echo $ano_id; ?>)">
                                    <i class="fas fa-chevron-down" id="icon-<?php echo $ano_id; ?>"></i>
                                    <h3><?php echo htmlspecialchars($primer_periodo['ano_lectivo']); ?></h3>
                                    <span class="periodo-count"><?php echo count($periodos_ano); ?> periodos</span>
                                </div>
                                
                                <div class="periodos-container" id="periodos-<?php echo $ano_id; ?>">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Periodo</th>
                                                <th>Nombre</th>
                                                <th>Fechas</th>
                                                <th>Porcentaje</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($periodos_ano as $periodo): ?>
                                                <tr>
                                                    <td class="align-middle"><?php echo $periodo['numero_periodo']; ?></td>
                                                    <td class="align-middle">Periodo <?php echo $periodo['numero_periodo']; ?></td>
                                                    <td class="align-middle">
                                                        <div class="fecha-info">
                                                            <div><i class="fas fa-calendar-day"></i> Inicio: <?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?></div>
                                                            <div><i class="fas fa-calendar-check"></i> Fin: <?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?></div>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle text-center"><?php echo $periodo['porcentaje_calificacion']; ?>%</td>
                                                    <td class="align-middle">
                                                        <?php
                                                        $periodos_activos_ano = array_filter($periodos_ano, function($periodo) {
                                                            return $periodo['estado_periodo'] !== 'cerrado';
                                                        });
                                                        $periodos_activos_ano = array_values($periodos_activos_ano);
                                                        if (count($periodos_activos_ano) > 0) {
                                                            $estado_periodo = $periodo['estado_periodo'];
                                                            $iconos = [
                                                                'en_curso' => 'clock',
                                                                'finalizado' => 'check-circle',
                                                                'cerrado' => 'lock'
                                                            ];
                                                            ?>
                                                            <button onclick="cambiarEstadoPeriodo(<?php echo $periodo['id']; ?>, '<?php echo $estado_periodo; ?>')"
                                                                    class="btn-estado <?php echo $estado_periodo; ?>"
                                                                    data-periodo-id="<?php echo $periodo['id']; ?>">
                                                                <span class="estado-texto">
                                                                    <i class="fas fa-<?php echo $iconos[$estado_periodo]; ?>"></i>
                                                                    <?php echo ucfirst(str_replace('_', ' ', $estado_periodo)); ?>
                                                                </span>
                                                            </button>
                                                            <?php
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="actions">
                                                            <button onclick="abrirModalBoletines(<?php echo intval($periodo['id']); ?>)" 
                                                                    class="btn-action btn-info" 
                                                                    title="Generar Boletines">
                                                                <i class="fas fa-file-alt"></i>
                                                            </button>
                                                            <button class="btn-action btn-edit" 
                                                                    title="Editar"
                                                                    onclick="editarPeriodo(<?php echo $periodo['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button onclick="confirmarEliminacion(<?php echo $periodo['id']; ?>)" 
                                                                    class="btn-action btn-delete" 
                                                                    title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal de Nuevo Periodo -->
            <div class="modal" id="modalNuevoPeriodo">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title">
                            <i class="fas fa-calendar-plus"></i>
                            Nuevo Periodo
                        </div>
                        <button class="close-btn" onclick="cerrarModalNuevoPeriodo()">×</button>
                    </div>
                    
                    <div class="modal-body">
                        <?php
                        $anos_lectivos = $pdo->query("
                            SELECT * FROM anos_lectivos 
                            WHERE estado = 'activo' 
                            ORDER BY nombre DESC
                        ")->fetchAll();

                        if (count($anos_lectivos) > 0): 
                        ?>
                            <form id="formNuevoPeriodo" onsubmit="return guardarPeriodo(event)">
                                <!-- Año Lectivo -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar"></i> Año Lectivo
                                    </label>
                                    <select name="ano_lectivo_id" class="form-control" required>
                                        <option value="">Seleccione un año lectivo</option>
                                        <?php foreach($anos_lectivos as $ano): ?>
                                            <option value="<?php echo $ano['id']; ?>">
                                                <?php echo htmlspecialchars($ano['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Número de Periodo -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-list-ol"></i> Número de Periodo
                                    </label>
                                    <input type="number" name="numero_periodo" class="form-control" required min="1" max="4">
                                </div>

                                <!-- Nombre -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-font"></i> Nombre
                                    </label>
                                    <input type="text" name="nombre" class="form-control" required>
                                </div>

                                <!-- Fechas -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-alt"></i> Fecha Inicio
                                    </label>
                                    <input type="date" name="fecha_inicio" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-alt"></i> Fecha Fin
                                    </label>
                                    <input type="date" name="fecha_fin" class="form-control" required>
                                </div>

                                <!-- Porcentaje -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-percentage"></i> Porcentaje
                                    </label>
                                    <input type="number" name="porcentaje_calificacion" class="form-control" required min="0" max="100" step="0.01">
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn-secondary" onclick="cerrarModalNuevoPeriodo()">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="btn-primary">
                                        Guardar Periodo
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                No hay años lectivos disponibles. Por favor, cree un año lectivo primero.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-secondary" onclick="cerrarModalNuevoPeriodo()">
                                    Cerrar
                                </button>
                                <button type="button" class="btn-primary" onclick="cerrarModalNuevoPeriodo(); abrirModalNuevoAno();">
                                    <i class="fas fa-calendar-plus"></i> Crear Año Lectivo
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Modal de Nuevo Año Lectivo -->
            <div class="modal" id="modalNuevoAno">
                <div class="modal-content modal-ano">
                    <div class="modal-header">
                        <div class="modal-title">
                            <i class="fas fa-calendar-plus"></i>
                            Nuevo Año Lectivo
                        </div>
                        <button class="close-btn" onclick="cerrarModalNuevoAno()">×</button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="formNuevoAno" onsubmit="return guardarAnoLectivo(event)">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar"></i> Nombre del Año Lectivo
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       class="form-control" 
                                       required 
                                       pattern="Año Lectivo [0-9]{4}"
                                       title="Formato: Año Lectivo YYYY (ejemplo: Año Lectivo 2026)"
                                       placeholder="Ejemplo: Año Lectivo 2026">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-day"></i> Fecha de Inicio
                                </label>
                                <input type="date" name="fecha_inicio" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-day"></i> Fecha de Fin
                                </label>
                                <input type="date" name="fecha_fin" class="form-control" required>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                                <button type="button" class="btn-secondary" onclick="cerrarModalNuevoAno()">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal de Boletines -->
            <div class="modal" id="modalBoletines">
                <div class="modal-content modal-boletines">
                    <div class="modal-header">
                        <h3 class="modal-title"><i class="fas fa-file-alt"></i> Generar Boletines</h3>
                        <button type="button" class="close-btn" onclick="cerrarModalBoletines()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formBoletines">
                            <input type="hidden" id="periodo_id" name="periodo_id" value="">
                            
                            <div class="form-group">
                                <label class="form-label" for="sede">
                                    <i class="fas fa-building"></i> Sede
                                </label>
                                <select name="sede" id="sede" class="form-control" required>
                                    <option value="">Seleccione una sede</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nivel">
                                    <i class="fas fa-layer-group"></i> Nivel Educativo
                                </label>
                                <select name="nivel" id="nivel" class="form-control" required disabled>
                                    <option value="">Primero seleccione una sede</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="grado">
                                    <i class="fas fa-graduation-cap"></i> Grado
                                </label>
                                <select name="grado" id="grado" class="form-control" disabled>
                                    <option value="">Primero seleccione un nivel</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="cerrarModalBoletines()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn-info" id="btnVistaPrevia" disabled>
                            <i class="fas fa-eye"></i> Vista Previa
                        </button>
                        <button type="button" class="btn-primary" id="btnGenerar" disabled>
                            <i class="fas fa-file-pdf"></i> Generar
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function confirmarEliminacion(id) {
        if (confirm('¿Está seguro que desea eliminar este periodo?')) {
            fetch('delete_period.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Periodo eliminado correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el periodo');
                });
        }
    }

    function abrirModalNuevoPeriodo() {
        const modal = document.getElementById('modalNuevoPeriodo');
        modal.style.display = 'block';
        
        // Si hay un formulario (significa que hay años lectivos), lo reseteamos
        const form = document.getElementById('formNuevoPeriodo');
        if (form) {
            form.reset();
        }
    }

    function cerrarModalNuevoPeriodo() {
        document.getElementById('modalNuevoPeriodo').style.display = 'none';
    }

    function guardarPeriodo(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch('save_period.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Periodo guardado correctamente');
                location.reload();
            } else {
                alert('Error al guardar el periodo: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar el periodo');
        });

        return false;
    }

    function cambiarEstadoPeriodo(periodoId, estadoActual) {
        const estados = {
            'en_curso': { texto: 'En Curso', icono: 'clock' },
            'finalizado': { texto: 'Finalizado', icono: 'check-circle' },
            'cerrado': { texto: 'Cerrado', icono: 'lock' }
        };

        // Crear el menú de opciones
        const menu = document.createElement('div');
        menu.className = 'estado-menu';
        
        Object.entries(estados).forEach(([estado, info]) => {
            if (estado !== estadoActual) {
                const opcion = document.createElement('div');
                opcion.className = `estado-opcion ${estado}`;
                opcion.innerHTML = `<i class="fas fa-${info.icono}"></i> ${info.texto}`;
                opcion.onclick = () => actualizarEstadoPeriodo(periodoId, estado);
                menu.appendChild(opcion);
            }
        });

        // Remover menú existente si hay alguno
        const menuExistente = document.querySelector('.estado-menu');
        if (menuExistente) {
            menuExistente.remove();
        }

        // Obtener el botón específico para este periodo
        const boton = document.querySelector(`button[data-periodo-id="${periodoId}"]`);
        if (boton) {
            boton.appendChild(menu);
            menu.classList.add('active');

            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function cerrarMenu(e) {
                if (!menu.contains(e.target) && !boton.contains(e.target)) {
                    menu.remove();
                    document.removeEventListener('click', cerrarMenu);
                }
            });
        }
    }

    function actualizarEstadoPeriodo(periodoId, nuevoEstado) {
        fetch('cambiar_estado_periodo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${periodoId}&estado=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar todos los periodos afectados
                data.periodos.forEach(periodo => {
                    const boton = document.querySelector(`button[data-periodo-id="${periodo.id}"]`);
                    if (boton) {
                        boton.className = `btn-estado ${periodo.estado_periodo}`;
                        const estadoTexto = boton.querySelector('.estado-texto');
                        const iconos = {
                            'en_curso': 'clock',
                            'finalizado': 'check-circle',
                            'cerrado': 'lock'
                        };
                        estadoTexto.innerHTML = `<i class="fas fa-${iconos[periodo.estado_periodo]}"></i> ${periodo.estado_periodo.charAt(0).toUpperCase() + periodo.estado_periodo.slice(1).replace('_', ' ')}`;
                    }
                });

                // Remover el menú
                const menu = document.querySelector('.estado-menu');
                if (menu) menu.remove();

                // Actualizar la página para reflejar los cambios
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el estado del periodo');
        });
    }

    function editarPeriodo(id) {
        console.log('Editando periodo:', id);
        window.location.href = `edit_period.php?id=${id}`;
    }

    function togglePeriodos(anoId) {
        const container = document.getElementById(`periodos-${anoId}`);
        const header = container.previousElementSibling;
        const icon = document.getElementById(`icon-${anoId}`);
        
        container.classList.toggle('collapsed');
        header.classList.toggle('collapsed');
        
        if (container.classList.contains('collapsed')) {
            icon.style.transform = 'rotate(-90deg)';
        } else {
            icon.style.transform = 'rotate(0deg)';
        }
        
        // Guardar estado en localStorage
        const estados = JSON.parse(localStorage.getItem('periodosEstados') || '{}');
        estados[anoId] = !container.classList.contains('collapsed');
        localStorage.setItem('periodosEstados', JSON.stringify(estados));
    }

    // Restaurar estados al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const estados = JSON.parse(localStorage.getItem('periodosEstados') || '{}');
        Object.entries(estados).forEach(([anoId, expandido]) => {
            const container = document.getElementById(`periodos-${anoId}`);
            if (container) {
                const header = container.previousElementSibling;
                const icon = document.getElementById(`icon-${anoId}`);
                
                if (!expandido) {
                    container.classList.add('collapsed');
                    header.classList.add('collapsed');
                    if (icon) icon.style.transform = 'rotate(-90deg)';
                }
            }
        });
    });

    function abrirModalNuevoAno() {
        document.getElementById('modalNuevoAno').style.display = 'block';
    }

    function cerrarModalNuevoAno() {
        document.getElementById('modalNuevoAno').style.display = 'none';
    }

    function guardarAnoLectivo(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch('save_ano_lectivo.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Año Lectivo guardado correctamente');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar el año lectivo');
        });

        return false;
    }

    function alertaNoAnoLectivo() {
        alert('Debe crear un año lectivo antes de poder crear periodos.');
    }

    // Funciones para el modal de boletines
    function abrirModalBoletines(periodoId) {
        // Establecer el periodo_id en el campo oculto
        document.getElementById('periodo_id').value = periodoId;
        
        // Mostrar el modal
        document.getElementById('modalBoletines').style.display = 'block';
        
        // Resetear los selects
        const selectSede = document.getElementById('sede');
        const selectNivel = document.getElementById('nivel');
        const selectGrado = document.getElementById('grado');
        
        selectNivel.disabled = true;
        selectGrado.disabled = true;
        selectNivel.innerHTML = '<option value="">Primero seleccione una sede</option>';
        selectGrado.innerHTML = '<option value="">Primero seleccione un nivel</option>';
        
        // Cargar las sedes
        fetch('get_sedes.php')
            .then(response => response.text())
            .then(html => {
                selectSede.innerHTML = '<option value="">Seleccione una sede</option>' + html;
            })
            .catch(error => console.error('Error al cargar sedes:', error));
    }

    function cerrarModalBoletines() {
        document.getElementById('modalBoletines').style.display = 'none';
    }

    // Configurar eventos al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const selectSede = document.getElementById('sede');
        const selectNivel = document.getElementById('nivel');
        const selectGrado = document.getElementById('grado');
        const btnVistaPrevia = document.getElementById('btnVistaPrevia');
        const btnGenerar = document.getElementById('btnGenerar');

        // Cuando se selecciona una sede
        selectSede.addEventListener('change', function() {
            const sedeId = this.value;
            selectNivel.disabled = !sedeId;
            selectGrado.disabled = true;
            btnVistaPrevia.disabled = true;
            btnGenerar.disabled = true;

            if (!sedeId) {
                selectNivel.innerHTML = '<option value="">Seleccione una sede primero</option>';
                selectGrado.innerHTML = '<option value="">Seleccione un nivel primero</option>';
                return;
            }

            // Cargar niveles
            fetch(`get_niveles.php?sede_id=${sedeId}`)
                .then(response => response.text())
                .then(html => {
                    selectNivel.innerHTML = '<option value="">Seleccione un nivel</option>' + html;
                })
                .catch(error => console.error('Error al cargar niveles:', error));
        });

        // Cuando se selecciona un nivel
        selectNivel.addEventListener('change', function() {
            const nivel = this.value;
            const sedeId = selectSede.value;
            selectGrado.disabled = !nivel;
            btnVistaPrevia.disabled = true;
            btnGenerar.disabled = true;

            if (!nivel) {
                selectGrado.innerHTML = '<option value="">Seleccione un nivel primero</option>';
                return;
            }

            // Cargar grados
            fetch(`get_grados.php?sede_id=${sedeId}&nivel=${encodeURIComponent(nivel)}`)
                .then(response => response.text())
                .then(html => {
                    selectGrado.innerHTML = '<option value="">Seleccione un grado</option>' + html;
                })
                .catch(error => console.error('Error al cargar grados:', error));
        });

        // Cuando se selecciona un grado
        selectGrado.addEventListener('change', function() {
            const habilitarBotones = this.value !== '';
            btnVistaPrevia.disabled = !habilitarBotones;
            btnGenerar.disabled = !habilitarBotones;
        });

        // Botón Vista Previa
        btnVistaPrevia.addEventListener('click', function() {
            const url = `preview_report.php?sede_id=${selectSede.value}&nivel=${encodeURIComponent(selectNivel.value)}&grado=${encodeURIComponent(selectGrado.value)}&periodo_id=preview`;
            window.open(url, '_blank');
        });

        // Botón Generar
        btnGenerar.addEventListener('click', function() {
            const sedeId = document.getElementById('sede').value;
            const nivel = document.getElementById('nivel').value;
            const grado = document.getElementById('grado').value;
            const periodoId = document.getElementById('periodo_id').value;

            // Validar que todos los campos estén seleccionados
            if (!sedeId || !nivel || !grado || !periodoId) {
                alert('Por favor seleccione todos los campos');
                return;
            }

            // Construir la URL con los parámetros
            const url = `generate_report_cards.php?sede_id=${sedeId}&nivel=${encodeURIComponent(nivel)}&grado=${encodeURIComponent(grado)}&periodo_id=${periodoId}`;
            
            // Redirigir a la generación del PDF
            window.location.href = url;
        });
    });

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            cerrarModalNuevoPeriodo();
            cerrarModalNuevoAno();
            cerrarModalBoletines();
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