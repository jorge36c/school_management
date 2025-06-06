<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

$grado_id = $_GET['id'] ?? null;
$sede_id = $_GET['sede_id'] ?? null;
$nivel = $_GET['nivel'] ?? null;
$tipo = $_GET['tipo'] ?? 'unigrado'; // Para diferenciar vista de multigrado

// Redireccionamiento si faltan parámetros esenciales
if ((!$grado_id && $tipo !== 'multigrado') || !$sede_id || !$nivel) {
    header('Location: view_level.php?sede_id=' . $sede_id . '&nivel=' . $nivel);
    exit();
}

try {
    // Obtener información de la sede para breadcrumb
    $stmt = $pdo->prepare("SELECT nombre FROM sedes WHERE id = ?");
    $stmt->execute([$sede_id]);
    $sede = $stmt->fetch();

    // Obtener tipo de enseñanza del nivel
    $stmt = $pdo->prepare("
        SELECT tipo_ensenanza, observaciones 
        FROM niveles_configuracion 
        WHERE sede_id = ? AND nivel = ?
    ");
    $stmt->execute([$sede_id, $nivel]);
    $nivel_config = $stmt->fetch();

    // Si no hay configuración específica, usar el tipo de enseñanza de la sede
    if (!$nivel_config) {
        $stmt = $pdo->prepare("SELECT tipo_ensenanza FROM sedes WHERE id = ?");
        $stmt->execute([$sede_id]);
        $sede_config = $stmt->fetch();
        $tipo_ensenanza = $sede_config['tipo_ensenanza'] ?? 'unigrado';
    } else {
        $tipo_ensenanza = $nivel_config['tipo_ensenanza'];
    }

    // Manejo diferente según el tipo de vista
    if ($tipo === 'multigrado') {
        // Vista multigrado: Obtener todos los grados del nivel
        $stmt = $pdo->prepare("SELECT * FROM grados WHERE sede_id = ? AND nivel = ?");
        $stmt->execute([$sede_id, $nivel]);
        $grados = $stmt->fetchAll();
        
        // Construir lista de IDs de grados para la consulta
        $grado_ids = array_column($grados, 'id');
        
        if (empty($grado_ids)) {
            throw new Exception('No hay grados registrados para este nivel');
        }
        
        // Obtener lista de estudiantes de todos los grados
        $placeholders = str_repeat('?,', count($grado_ids) - 1) . '?';
        $query = "
            SELECT e.*, 
                   CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                   g.nombre as nombre_grado
            FROM estudiantes e
            JOIN grados g ON e.grado_id = g.id
            WHERE e.sede_id = ? 
            AND e.nivel = ? 
            AND e.grado_id IN ($placeholders)
            AND e.estado = 'Activo'
            ORDER BY g.nombre, e.nombre
        ";
        
        $params = array_merge([$sede_id, $nivel], $grado_ids);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para mostrar en el header
        $nombre_vista = "Grupo Multigrado - " . ucfirst($nivel);
    } else {
        // Vista de grado individual
        // Obtener información del grado
        $stmt = $pdo->prepare("SELECT * FROM grados WHERE id = ?");
        $stmt->execute([$grado_id]);
        $grado = $stmt->fetch();

        if (!$grado) {
            throw new Exception('Grado no encontrado');
        }
        
        // Obtener lista de estudiantes del grado
        $stmt = $pdo->prepare("
            SELECT e.*, 
                CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                g.nombre as nombre_grado
            FROM estudiantes e
            JOIN grados g ON e.grado_id = g.id
            WHERE e.sede_id = ? 
            AND e.nivel = ? 
            AND e.grado_id = ?
            AND e.estado = 'Activo'
        ");
        $stmt->execute([$sede_id, $nivel, $grado_id]);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $nombre_vista = $grado['nombre'];
    }

} catch(PDOException $e) {
    $error = "Error en la base de datos: " . $e->getMessage();
    error_log($error);
} catch(Exception $e) {
    $error = $e->getMessage();
    error_log($error);
}

// Asegurarnos de que $estudiantes sea un array aunque esté vacío
$estudiantes = $estudiantes ?? [];

// Función para obtener un color de nivel - manteniendo consistencia con view_level.php
function getNivelColor($nivel) {
    $colores = [
        'preescolar' => 'info',
        'primaria' => 'success',
        'secundaria' => 'purple',
        'media' => 'warning'
    ];
    
    return $colores[$nivel] ?? 'primary';
}

$nivelColor = getNivelColor($nivel);

// Configurar el título de la página y el breadcrumb
$page_title = "Estudiantes de " . ($tipo === 'multigrado' ? "Grupo Multigrado" : htmlspecialchars($grado['nombre'] ?? ''));
$current_page = 'View Grade';
$breadcrumb_path = [
    'Inicio',
    'Sedes',
    $sede['nombre'] ?? '',
    ucfirst($nivel),
    $tipo === 'multigrado' ? "Grupo Multigrado" : htmlspecialchars($grado['nombre'] ?? '')
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes de <?php echo htmlspecialchars($nombre_vista); ?></title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos base -->
    <link rel="stylesheet" href="../../../assets/css/variables.css">
    <link rel="stylesheet" href="../../../assets/css/common.css">
    <link rel="stylesheet" href="../../../assets/css/layouts.css">
    
    <style>
    :root {
        --primary: #4f46e5;
        --primary-light: #6366f1;
        --primary-dark: #4338ca;
        --primary-50: rgba(79, 70, 229, 0.05);
        --primary-100: rgba(79, 70, 229, 0.1);
        --primary-200: rgba(79, 70, 229, 0.2);
        --success: #10b981;
        --success-50: rgba(16, 185, 129, 0.05);
        --success-100: rgba(16, 185, 129, 0.1);
        --danger: #ef4444;
        --danger-50: rgba(239, 68, 68, 0.05);
        --danger-100: rgba(239, 68, 68, 0.1);
        --warning: #f59e0b;
        --warning-50: rgba(245, 158, 11, 0.05);
        --warning-100: rgba(245, 158, 11, 0.1);
        --info: #3b82f6;
        --info-50: rgba(59, 130, 246, 0.05);
        --info-100: rgba(59, 130, 246, 0.1);
        --dark: #1f2937;
        --light: #f9fafb;
        --gray: #6b7280;
        --gray-light: #e5e7eb;
        --gray-lighter: #f3f4f6;
        --purple: #7c3aed;
        --purple-50: rgba(124, 58, 237, 0.05);
        --purple-100: rgba(124, 58, 237, 0.1);
        
        --radius-sm: 0.25rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
        
        --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        
        --transition-fast: all 0.15s ease;
        --transition: all 0.2s ease;
        --transition-slow: all 0.3s ease;
        
        /* Variables específicas del nivel actual */
        --nivel-color: var(--<?php echo $nivelColor; ?>);
        --nivel-color-50: var(--<?php echo $nivelColor; ?>-50);
        --nivel-color-100: var(--<?php echo $nivelColor; ?>-100);
    }

    body {
        background-color: #f8fafc;
        color: var(--dark);
        font-family: 'Inter', sans-serif;
    }

    /* === CONTENT WRAPPER === */
    .content-wrapper {
        padding: 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* === ACTION HEADER === */
    .action-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: var(--gray);
    }

    .breadcrumb a {
        color: var(--nivel-color);
        text-decoration: none;
        transition: var(--transition-fast);
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .breadcrumb a:hover {
        color: var(--dark);
    }

    .breadcrumb .separator {
        color: var(--gray-light);
        font-size: 0.7rem;
    }

    .action-buttons {
        display: flex;
        gap: 0.75rem;
    }

    .btn-action {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
        font-weight: 600;
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: var(--transition);
        border: none;
    }

    .btn-add {
        background: var(--nivel-color-50);
        color: var(--nivel-color);
    }

    .btn-add:hover {
        background: var(--nivel-color-100);
        transform: translateY(-2px);
    }

    .btn-export {
        background: var(--success-50);
        color: var(--success);
    }

    .btn-export:hover {
        background: var(--success-100);
        transform: translateY(-2px);
    }

    .btn-back {
        background: var(--gray-lighter);
        color: var(--gray);
    }

    .btn-back:hover {
        background: var(--gray-light);
        color: var(--dark);
        transform: translateY(-2px);
    }

    /* === GRADE HEADER === */
    .grade-header {
        background: white;
        padding: 1.75rem;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }
    
    .grade-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 4px;
        width: 100%;
        background: var(--nivel-color);
    }

    .grade-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .grade-icon {
        color: var(--nivel-color);
        font-size: 1.75rem;
        background-color: var(--nivel-color-50);
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-lg);
    }

    .grade-details h1 {
        margin: 0 0 0.5rem 0;
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--dark);
        letter-spacing: -0.01em;
    }

    .grade-details .nivel-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: var(--nivel-color-50);
        color: var(--nivel-color);
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .tipo-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: var(--warning-50);
        color: var(--warning);
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .grade-stats {
        display: flex;
        gap: 1.25rem;
    }

    .stat-box {
        padding: 0.75rem 1.25rem;
        background: var(--gray-lighter);
        border-radius: var(--radius-md);
        min-width: 120px;
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--gray);
        margin-bottom: 0.25rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
    }

    /* === STUDENTS TABLE === */
    .students-table {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .students-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .students-table th, 
    .students-table td {
        padding: 1rem 1.5rem;
        text-align: left;
        border-bottom: 1px solid var(--gray-light);
    }

    .students-table th {
        background: var(--gray-lighter);
        font-weight: 600;
        color: var(--dark);
        font-size: 0.95rem;
    }

    .students-table tr:hover {
        background: var(--gray-lighter);
    }

    .students-table .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .students-table .badge-success {
        background: var(--success-50);
        color: var(--success);
    }

    .students-table .badge-grado {
        background: var(--nivel-color-50);
        color: var(--nivel-color);
    }

    .students-table .actions {
        display: flex;
        gap: 0.5rem;
    }

    .students-table .btn-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: var(--transition-fast);
        border: none;
        background: var(--gray-lighter);
        color: var(--gray);
    }

    .students-table .btn-view {
        background: var(--info-50);
        color: var(--info);
    }

    .students-table .btn-view:hover {
        background: var(--info-100);
        transform: scale(1.05);
    }

    .students-table .btn-change {
        background: var(--success-50);
        color: var(--success);
    }

    .students-table .btn-change:hover {
        background: var(--success-100);
        transform: scale(1.05);
    }

    /* === EMPTY STATE === */
    .no-data {
        text-align: center;
        padding: 3.5rem 2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .no-data i {
        font-size: 3.5rem;
        color: var(--gray-light);
        background: var(--gray-lighter);
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-bottom: 1rem;
    }

    .no-data p {
        font-size: 1.25rem;
        color: var(--gray);
    }

    /* === MODAL === */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        padding: 1rem;
    }

    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: var(--radius-lg);
        width: 100%;
        max-width: 500px;
        box-shadow: var(--shadow-xl);
        animation: modalFadeIn 0.3s ease;
        position: relative;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-content h2 {
        margin: 0 0 1.75rem 0;
        font-size: 1.5rem;
        color: var(--dark);
        position: relative;
        padding-bottom: 0.75rem;
        letter-spacing: -0.01em;
        font-weight: 600;
    }

    .modal-content h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: var(--nivel-color);
        border-radius: 3px;
    }

    .form-group {
        margin-bottom: 1.75rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: var(--dark);
        font-size: 0.95rem;
    }

    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--gray-light);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        transition: var(--transition-fast);
        background-color: var(--light);
    }

    .form-control:focus {
        border-color: var(--nivel-color);
        outline: none;
        box-shadow: 0 0 0 3px var(--nivel-color-50);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-secondary {
        padding: 0.75rem 1.5rem;
        background: var(--gray-light);
        border: none;
        border-radius: var(--radius-md);
        cursor: pointer;
        color: var(--dark);
        font-weight: 500;
        transition: var(--transition);
        font-size: 0.95rem;
    }

    .btn-secondary:hover {
        background: var(--gray);
        color: white;
    }

    .btn-primary {
        padding: 0.75rem 1.5rem;
        background: var(--nivel-color);
        color: white;
        border: none;
        border-radius: var(--radius-md);
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        font-size: 0.95rem;
        box-shadow: var(--shadow-sm);
    }

    .btn-primary:hover {
        filter: brightness(1.1);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .estudiante-resultado {
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        border: 1px solid var(--gray-light);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: var(--transition-fast);
    }

    .estudiante-resultado:hover {
        background: var(--gray-lighter);
    }

    .estudiante-resultado label {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        width: 100%;
    }

    /* === ALERTS === */
    .alert {
        padding: 1.25rem;
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .alert-error {
        background-color: var(--danger-50);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        .grade-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1.25rem;
        }

        .grade-stats {
            flex-direction: column;
            width: 100%;
        }

        .stat-box {
            width: 100%;
        }

        .action-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .action-buttons {
            flex-direction: column;
            width: 100%;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }

        .students-table {
            overflow-x: auto;
        }

        .students-table table {
            min-width: 600px;
        }

        .modal-content {
            padding: 1.5rem;
        }
    }

    /* === ANIMATIONS === */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .fade-in {
        animation: fadeIn 0.5s ease;
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../../sidebar.php'; ?>

        <div class="main-content">
            <?php include '../../components/topbar.php'; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php endif; ?>

            <div class="content-wrapper">
                <!-- Breadcrumb & Actions -->
                <div class="action-header fade-in">
                    <div class="breadcrumb">
                        <a href="view_headquarters.php?id=<?php echo $sede_id; ?>">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($sede['nombre'] ?? 'Sede'); ?>
                        </a>
                        <span class="separator"><i class="fas fa-chevron-right"></i></span>
                        <a href="view_level.php?sede_id=<?php echo $sede_id; ?>&nivel=<?php echo $nivel; ?>">
                            <i class="fas fa-layer-group"></i> <?php echo ucfirst($nivel); ?>
                        </a>
                        <span class="separator"><i class="fas fa-chevron-right"></i></span>
                        <span><?php echo htmlspecialchars($nombre_vista); ?></span>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn-action btn-add" onclick="mostrarFormularioAsignar()">
                            <i class="fas fa-user-plus"></i>
                            <span>Asignar Estudiante</span>
                        </button>
                        <button class="btn-action btn-export" onclick="exportarLista()">
                            <i class="fas fa-file-export"></i>
                            <span>Exportar Lista</span>
                        </button>
                        <a href="view_level.php?sede_id=<?php echo $sede_id; ?>&nivel=<?php echo $nivel; ?>" class="btn-action btn-back">
                            <i class="fas fa-arrow-left"></i>
                            <span>Volver</span>
                        </a>
                    </div>
                </div>

                <!-- Grado Header -->
                <div class="grade-header fade-in">
                    <div class="grade-info">
                        <div class="grade-icon">
                            <?php if ($tipo === 'multigrado'): ?>
                                <i class="fas fa-chalkboard-teacher"></i>
                            <?php else: ?>
                                <i class="fas fa-graduation-cap"></i>
                            <?php endif; ?>
                        </div>
                        <div class="grade-details">
                            <h1><?php echo htmlspecialchars($nombre_vista); ?></h1>
                            <div>
                                <span class="nivel-badge">
                                    <i class="fas fa-layer-group"></i> <?php echo ucfirst($nivel); ?>
                                </span>
                                <?php if ($tipo === 'multigrado'): ?>
                                <span class="tipo-badge">
                                    <i class="fas fa-chalkboard-teacher"></i> Multigrado
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grade-stats">
                        <div class="stat-box">
                            <div class="stat-label">Estudiantes Activos</div>
                            <div class="stat-value"><?php echo count($estudiantes); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Estudiantes -->
                <div class="students-table fade-in">
                    <?php if (empty($estudiantes)): ?>
                        <div class="no-data">
                            <i class="fas fa-users"></i>
                            <p>No hay estudiantes registrados en este <?php echo $tipo === 'multigrado' ? 'nivel' : 'grado'; ?></p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Nombre Completo</th>
                                    <?php if ($tipo === 'multigrado'): ?>
                                    <th>Grado</th>
                                    <?php endif; ?>
                                    <th>Documento</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $contador = 1;
                                foreach ($estudiantes as $estudiante): 
                                ?>
                                <tr>
                                    <td><?php echo $contador++; ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['nombre_completo']); ?></td>
                                    <?php if ($tipo === 'multigrado'): ?>
                                    <td>
                                        <span class="badge badge-grado">
                                            <?php echo htmlspecialchars($estudiante['nombre_grado']); ?>
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($estudiante['documento_tipo'] . ': ' . $estudiante['documento_numero']); ?></td>
                                    <td>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> Activo
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <button onclick="verEstudiante(<?php echo $estudiante['id']; ?>)" class="btn-icon btn-view" data-tooltip="Ver estudiante">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="cambiarGrado(<?php echo $estudiante['id']; ?>)" class="btn-icon btn-change" data-tooltip="Cambiar de grado">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para asignar estudiante -->
    <div id="modalAsignar" class="modal">
        <div class="modal-content">
            <h2>Asignar Estudiante al <?php echo $tipo === 'multigrado' ? 'Grupo Multigrado' : 'Grado'; ?></h2>
            <form id="formAsignar" method="POST" action="assign_student.php">
                <?php if ($tipo === 'multigrado'): ?>
                    <input type="hidden" name="tipo" value="multigrado">
                <?php else: ?>
                    <input type="hidden" name="grado_id" value="<?php echo $grado_id; ?>">
                <?php endif; ?>
                <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
                <input type="hidden" name="nivel" value="<?php echo $nivel; ?>">
                
                <div class="form-group">
                    <label for="buscarEstudiante">Buscar Estudiante:</label>
                    <input type="text" id="buscarEstudiante" class="form-control" placeholder="Nombre o documento del estudiante..." autocomplete="off">
                </div>
                
                <div id="resultadosBusqueda" class="resultados-container"></div>
                
                <?php if ($tipo === 'multigrado'): ?>
                <div class="form-group">
                    <label for="grado_seleccionado">Seleccionar Grado:</label>
                    <select name="grado_id" id="grado_seleccionado" class="form-control" required>
                        <option value="">Seleccione un grado...</option>
                        <?php foreach ($grados as $grado): ?>
                            <option value="<?php echo $grado['id']; ?>">
                                <?php echo htmlspecialchars($grado['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="button" onclick="cerrarModal()" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Asignar Estudiante
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function mostrarFormularioAsignar() {
        const modal = document.getElementById('modalAsignar');
        modal.style.display = 'flex';
        
        // Animar entrada
        const modalContent = modal.querySelector('.modal-content');
        modalContent.style.animation = 'modalFadeIn 0.3s ease';
        
        // Enfocar el campo de búsqueda automáticamente
        setTimeout(() => {
            document.getElementById('buscarEstudiante').focus();
        }, 300);
        
        // Prevenir scroll
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        const modal = document.getElementById('modalAsignar');
        const modalContent = modal.querySelector('.modal-content');
        
        // Animar salida
        modalContent.style.animation = 'modalFadeIn 0.25s ease reverse';
        
        // Esperar a que termine la animación
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restaurar scroll
            
            // Limpiar resultados
            document.getElementById('resultadosBusqueda').innerHTML = '';
            document.getElementById('buscarEstudiante').value = '';
        }, 250);
    }

    function exportarLista() {
        <?php if ($tipo === 'multigrado'): ?>
            window.location.href = `export_students.php?sede_id=<?php echo $sede_id; ?>&nivel=<?php echo $nivel; ?>&tipo=multigrado`;
        <?php else: ?>
            window.location.href = `export_students.php?grado_id=<?php echo $grado_id; ?>&sede_id=<?php echo $sede_id; ?>&nivel=<?php echo $nivel; ?>`;
        <?php endif; ?>
    }

    function verEstudiante(id) {
        window.location.href = `view_student.php?id=${id}&<?php echo $tipo === 'multigrado' ? 'tipo=multigrado&' : 'grado_id=' . $grado_id . '&'; ?>sede_id=<?php echo $sede_id; ?>&nivel=<?php echo $nivel; ?>`;
    }

    function cambiarGrado(id) {
        // Implementar lógica para cambiar de grado
        alert('Funcionalidad de cambio de grado en desarrollo');
    }

    // Búsqueda en tiempo real de estudiantes
    document.getElementById('buscarEstudiante').addEventListener('input', function(e) {
        const busqueda = e.target.value;
        if (busqueda.length > 2) {
            // Mostrar indicador de carga
            document.getElementById('resultadosBusqueda').innerHTML = '<div class="cargando"><i class="fas fa-spinner fa-spin"></i> Buscando estudiantes...</div>';
            
            fetch(`search_students.php?q=${encodeURIComponent(busqueda)}&sede_id=<?php echo $sede_id; ?>`)
                .then(response => response.json())
                .then(data => {
                    const resultados = document.getElementById('resultadosBusqueda');
                    
                    if (data.length === 0) {
                        resultados.innerHTML = '<div class="no-results">No se encontraron estudiantes con ese criterio de búsqueda</div>';
                        return;
                    }
                    
                    resultados.innerHTML = data.map(estudiante => `
                        <div class="estudiante-resultado">
                            <label>
                                <input type="radio" name="estudiante_id" value="${estudiante.id}" required>
                                ${estudiante.nombre_completo} - ${estudiante.documento_tipo}: ${estudiante.documento_numero}
                            </label>
                        </div>
                    `).join('');
                })
                .catch(error => {
                    console.error('Error al buscar estudiantes:', error);
                    document.getElementById('resultadosBusqueda').innerHTML = '<div class="error">Error al buscar estudiantes. Intente nuevamente.</div>';
                });
        } else if (busqueda.length === 0) {
            document.getElementById('resultadosBusqueda').innerHTML = '';
        }
    });
    
    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModal();
        }
    });
    
    // Cerrar modal al hacer clic fuera
    document.getElementById('modalAsignar').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModal();
        }
    });
    
    // Validar formulario antes de enviar
    document.getElementById('formAsignar').addEventListener('submit', function(e) {
        const radioButtons = document.querySelectorAll('input[name="estudiante_id"]');
        let selected = false;
        
        radioButtons.forEach(radio => {
            if (radio.checked) {
                selected = true;
            }
        });
        
        if (!selected) {
            e.preventDefault();
            alert('Por favor seleccione un estudiante para asignar al grado');
            return;
        }
        
        <?php if ($tipo === 'multigrado'): ?>
        const gradoSeleccionado = document.getElementById('grado_seleccionado').value;
        if (!gradoSeleccionado) {
            e.preventDefault();
            alert('Por favor seleccione un grado para el estudiante');
        }
        <?php endif; ?>
    });

    // Actualizar la hora en la parte superior
    function updateTime() {
        const now = new Date();
        if (document.getElementById('current-time')) {
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }
    }
    
    setInterval(updateTime, 1000);
    updateTime();
    </script>
</body>
</html>