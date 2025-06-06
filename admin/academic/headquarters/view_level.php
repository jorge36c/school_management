<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

$sede_id = $_GET['sede_id'] ?? null;
$nivel = $_GET['nivel'] ?? null;

if (!$sede_id || !$nivel) {
    header('Location: view_headquarters.php?id=' . $sede_id);
    exit();
}

try {
    // Obtener información de la sede
    $stmt = $pdo->prepare("SELECT * FROM sedes WHERE id = ?");
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
        $observaciones = '';
    } else {
        $tipo_ensenanza = $nivel_config['tipo_ensenanza'];
        $observaciones = $nivel_config['observaciones'];
    }

    // Obtener grados del nivel
    $stmt = $pdo->prepare("
        SELECT * FROM grados 
        WHERE sede_id = ? AND nivel = ? 
        ORDER BY nombre
    ");
    $stmt->execute([$sede_id, $nivel]);
    $grados = $stmt->fetchAll();

    // Para multigrado: contar total de estudiantes en todos los grados
    if ($tipo_ensenanza === 'multigrado' || $tipo_ensenanza === 'hibrido') {
        $total_estudiantes = 0;
        foreach ($grados as $grado) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM estudiantes WHERE grado_id = ? AND estado = 'Activo'");
            $stmt->execute([$grado['id']]);
            $total_estudiantes += $stmt->fetchColumn();
        }
    }

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Función para obtener un color de nivel
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
$page_title = "Grados de " . ucfirst($nivel);
$current_page = 'View Level';
$breadcrumb_path = [
    'Inicio',
    'Sedes',
    htmlspecialchars($sede['nombre']),
    'Grados de ' . ucfirst($nivel)
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grados de <?php echo ucfirst($nivel); ?> - <?php echo htmlspecialchars($sede['nombre']); ?></title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos base - Mismos que view_headquarters.php -->
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

    /* === LEVEL HEADER === */
    .level-header {
        background: white;
        padding: 1.75rem;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        margin-bottom: 1.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    .level-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 4px;
        width: 100%;
        background: var(--nivel-color);
    }

    .level-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .level-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .level-title i {
        color: var(--nivel-color);
        font-size: 1.75rem;
        background-color: var(--nivel-color-50);
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-lg);
    }

    .level-title h1 {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--dark);
        letter-spacing: -0.01em;
    }

    .sede-name {
        color: var(--gray);
        font-size: 0.95rem;
        margin-left: 1rem;
        padding-left: 1rem;
        border-left: 2px solid var(--gray-light);
    }

    .btn-add {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--nivel-color-50);
        color: var(--nivel-color);
        border: none;
        font-weight: 600;
        cursor: pointer;
        padding: 0.75rem 1.25rem;
        transition: var(--transition);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
    }

    .btn-add:hover {
        background: var(--nivel-color-100);
        transform: translateY(-2px);
    }

    .btn-add i {
        font-size: 0.9rem;
    }

    /* === ENSEÑANZA TIPO === */
    .enseñanza-tipo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        margin-left: 0.5rem;
    }

    .tipo-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .tipo-unigrado {
        background-color: var(--info-50);
        color: var(--info);
    }

    .tipo-multigrado {
        background-color: var(--warning-50);
        color: var(--warning);
    }

    .tipo-hibrido {
        background-color: var(--purple-50);
        color: var(--purple);
    }

    .observaciones-icon {
        color: var(--gray);
        cursor: help;
        transition: var(--transition-fast);
    }

    .observaciones-icon:hover {
        color: var(--nivel-color);
    }

    /* === GRADOS GRID === */
    .grados-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .grado-card {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        border: 1px solid var(--gray-light);
        position: relative;
    }

    .grado-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 4px;
        width: 100%;
        background: var(--nivel-color);
    }

    .grado-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .grado-header {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--gray-light);
    }

    .grado-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dark);
    }

    .grado-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: var(--transition-fast);
        background: var(--gray-lighter);
        color: var(--gray);
    }

    .btn-icon:hover {
        background: var(--nivel-color-50);
        color: var(--nivel-color);
    }

    .btn-icon.delete:hover {
        background: var(--danger-50);
        color: var(--danger);
    }

    .grado-stats {
        padding: 1.5rem;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .stat-item i {
        font-size: 1.25rem;
        color: var(--nivel-color);
        background: var(--nivel-color-50);
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-lg);
        transition: var(--transition);
    }

    .grado-card:hover .stat-item i {
        background: var(--nivel-color-100);
        transform: scale(1.05);
    }

    .stat-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--gray);
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
    }

    .grado-footer {
        padding: 1.5rem;
        background: var(--light);
        border-top: 1px solid var(--gray-light);
    }

    .btn-view {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.875rem;
        background: var(--nivel-color);
        color: white;
        text-decoration: none;
        border-radius: var(--radius-md);
        transition: all 0.3s ease;
        font-size: 0.95rem;
        font-weight: 600;
        box-shadow: var(--shadow-sm);
    }

    .btn-view:hover {
        background: var(--dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .btn-view i {
        transition: transform 0.3s ease;
    }

    .btn-view:hover i {
        transform: translateX(4px);
    }

    /* === MULTIGRADO CARD === */
    .card-multigrado {
        grid-column: 1 / -1;
    }

    .card-multigrado .grado-header h3 {
        font-size: 1.5rem;
    }

    .card-multigrado .grado-content {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .grados-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .grado-tag {
        background: var(--nivel-color-50);
        color: var(--nivel-color);
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* === EMPTY STATE === */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3.5rem 2rem;
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .empty-state i {
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

    .empty-state h3 {
        margin: 0;
        font-size: 1.5rem;
        color: var(--dark);
        font-weight: 600;
    }

    .empty-state p {
        color: var(--gray);
        margin: 0 0 1rem 0;
        max-width: 400px;
    }

    .empty-state .btn-add {
        margin-top: 1rem;
    }

    /* === ALERTS === */
    .alert {
        padding: 1.25rem;
        border-radius: var(--radius-md);
        margin: 0 1rem 1.5rem 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: alertFadeIn 0.5s ease;
    }

    @keyframes alertFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background-color: var(--success-50);
        color: var(--success);
        border-left: 4px solid var(--success);
    }

    .alert-error {
        background-color: var(--danger-50);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }

    .alert i {
        font-size: 1.25rem;
    }

    /* === MODAL === */
    .form-modal {
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

    .form-content {
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

    .form-content h3 {
        margin: 0 0 1.75rem 0;
        font-size: 1.5rem;
        color: var(--dark);
        position: relative;
        padding-bottom: 0.75rem;
        letter-spacing: -0.01em;
        font-weight: 600;
    }

    .form-content h3::after {
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

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        .level-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1.25rem;
            padding: 1.25rem;
        }

        .btn-add {
            align-self: stretch;
            justify-content: center;
        }

        .grados-grid {
            grid-template-columns: 1fr;
        }

        .form-content {
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

    .fade-in-delay-1 {
        animation: fadeIn 0.5s ease 0.1s backwards;
    }

    .fade-in-delay-2 {
        animation: fadeIn 0.5s ease 0.2s backwards;
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../../sidebar.php'; ?>

        <div class="main-content">
            <?php include '../../components/topbar.php'; ?>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle"></i>
                <div>
                    <?php 
                    $success_message = '';
                    switch ($_GET['success']) {
                        case 'grado_eliminado':
                            $success_message = 'El grado ha sido eliminado correctamente.';
                            break;
                        case 'grado_editado':
                            $success_message = 'El grado ha sido editado correctamente.';
                            break;
                        case 'grado_agregado':
                            $success_message = 'El grado ha sido agregado correctamente.';
                            break;
                        default:
                            $success_message = 'Operación realizada con éxito.';
                            break;
                    }
                    echo $success_message;
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></div>
            </div>
            <?php endif; ?>

            <div class="content-wrapper">
                <!-- Header del Nivel -->
                <div class="level-header fade-in">
                    <div class="level-info">
                        <div class="level-title">
                            <i class="fas fa-graduation-cap"></i>
                            <h1><?php echo ucfirst($nivel); ?></h1>
                            <span class="sede-name"><?php echo htmlspecialchars($sede['nombre']); ?></span>
                        </div>
                        <div class="enseñanza-tipo">
                            <span class="tipo-badge tipo-<?php echo $tipo_ensenanza; ?>">
                                <?php 
                                $icon_class = '';
                                switch ($tipo_ensenanza) {
                                    case 'unigrado':
                                        $icon_class = 'fa-chalkboard';
                                        break;
                                    case 'multigrado':
                                        $icon_class = 'fa-chalkboard-teacher';
                                        break;
                                    case 'hibrido':
                                        $icon_class = 'fa-random';
                                        break;
                                }
                                ?>
                                <i class="fas <?php echo $icon_class; ?>"></i>
                                <?php echo ucfirst($tipo_ensenanza); ?>
                            </span>
                            <?php if (!empty($observaciones)): ?>
                                <span class="observaciones-icon" data-tooltip="<?php echo htmlspecialchars($observaciones); ?>">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="btn-add" onclick="mostrarFormularioGrado()">
                        <i class="fas fa-plus"></i>
                        <span>Agregar Grado</span>
                    </button>
                </div>

                <!-- Grid de Grados -->
                <div class="grados-grid">
                    <?php if (empty($grados)): ?>
                        <div class="empty-state fade-in">
                            <i class="fas fa-school"></i>
                            <h3>No hay grados registrados</h3>
                            <p>Comienza agregando el primer grado para este nivel educativo</p>
                            <button class="btn-add" onclick="mostrarFormularioGrado()">
                                <i class="fas fa-plus"></i>
                                <span>Agregar Grado</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <?php if ($tipo_ensenanza === 'multigrado'): ?>
                            <!-- Vista Multigrado: Solo una tarjeta para todos los grados -->
                            <div class="grado-card card-multigrado fade-in">
                                <div class="grado-header">
                                    <h3>Grupo Multigrado - <?php echo ucfirst($nivel); ?></h3>
                                    <div class="grado-actions">
                                        <button class="btn-icon" onclick="editarMultigrado()" data-tooltip="Editar configuración">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="grado-content">
                                <div class="stat-item">
                                        <i class="fas fa-user-graduate"></i>
                                        <div class="stat-info">
                                            <span class="stat-label">Estudiantes activos en todos los grados</span>
                                            <span class="stat-value"><?php echo $total_estudiantes; ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grados-list" style="padding: 0 1.5rem 1.5rem 1.5rem;">
                                    <strong>Grados incluidos:</strong>
                                    <?php foreach ($grados as $grado): ?>
                                        <span class="grado-tag"><?php echo htmlspecialchars($grado['nombre']); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="grado-footer">
                                    <a href="view_grade.php?sede_id=<?php echo $sede_id; ?>&nivel=<?php echo $nivel; ?>&tipo=multigrado" 
                                       class="btn-view">
                                        <span>Ver Todos los Estudiantes</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Vista Unigrado/Híbrido: Una tarjeta por cada grado -->
                            <?php $i = 0; foreach ($grados as $grado): $i++; ?>
                                <div class="grado-card fade-in" style="animation-delay: <?php echo $i * 0.1; ?>s">
                                    <div class="grado-header">
                                        <h3><?php echo htmlspecialchars($grado['nombre']); ?></h3>
                                        <div class="grado-actions">
                                            <button class="btn-icon" onclick="mostrarEditModal(<?php echo $grado['id']; ?>, '<?php echo htmlspecialchars($grado['nombre']); ?>')" data-tooltip="Editar grado">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon delete" onclick="confirmarEliminacion(<?php echo $grado['id']; ?>)" data-tooltip="Eliminar grado">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="grado-stats">
                                        <div class="stat-item">
                                            <i class="fas fa-user-graduate"></i>
                                            <div class="stat-info">
                                                <span class="stat-label">Estudiantes activos</span>
                                                <span class="stat-value">
                                                    <?php 
                                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM estudiantes WHERE grado_id = ? AND estado = 'Activo'");
                                                    $stmt->execute([$grado['id']]);
                                                    echo $stmt->fetchColumn();
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grado-footer">
                                        <a href="view_grade.php?id=<?php echo $grado['id']; ?>&sede_id=<?php echo $sede_id; ?>&nivel=<?php echo $nivel; ?>" 
                                           class="btn-view">
                                            <span>Ver Estudiantes</span>
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para eliminar grado -->
    <form id="deleteForm" method="POST" action="delete_grade.php" style="display: none;">
        <input type="hidden" name="grado_id" id="delete_grado_id">
        <input type="hidden" name="nivel" id="delete_nivel" value="<?php echo $nivel; ?>">
        <input type="hidden" name="sede_id" id="delete_sede_id" value="<?php echo $sede_id; ?>">
    </form>

    <!-- Modal para editar grado -->
    <div id="editModal" class="form-modal">
        <div class="form-content">
            <h3>Editar Grado</h3>
            <form id="editForm" method="POST" action="edit_grade.php">
                <input type="hidden" name="grado_id" id="grado_id">
                <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
                <input type="hidden" name="nivel" value="<?php echo $nivel; ?>">
                
                <div class="form-group">
                    <label for="nombre">Nombre del Grado:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="ocultarEditModal()" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para agregar grado -->
    <div id="formGrado" class="form-modal">
        <div class="form-content">
            <h3>Agregar Nuevo Grado</h3>
            <form action="add_grade.php" method="POST">
                <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
                <input type="hidden" name="nivel" value="<?php echo $nivel; ?>">
                
                <div class="form-group">
                    <label for="add_nombre">Nombre del Grado:</label>
                    <input type="text" id="add_nombre" name="nombre" class="form-control" required placeholder="Ej: Primero A, Segundo B...">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="ocultarFormularioGrado()" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Grado
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Actualizar tiempo en tiempo real (por si es necesario, similar a view_headquarters.php)
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

    // Gestión de formularios y modales
    function mostrarFormularioGrado() {
        const modal = document.getElementById('formGrado');
        modal.style.display = 'flex';
        
        // Animar entrada
        const modalContent = modal.querySelector('.form-content');
        modalContent.style.animation = 'modalFadeIn 0.3s ease';
        
        // Enfocar el campo de nombre automáticamente
        setTimeout(() => {
            document.getElementById('add_nombre').focus();
        }, 300);
        
        // Prevenir scroll
        document.body.style.overflow = 'hidden';
    }

    function ocultarFormularioGrado() {
        const modal = document.getElementById('formGrado');
        const modalContent = modal.querySelector('.form-content');
        
        // Animar salida
        modalContent.style.animation = 'modalFadeIn 0.25s ease reverse';
        
        // Esperar a que termine la animación
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restaurar scroll
        }, 250);
    }

    function mostrarEditModal(gradoId, nombre) {
        document.getElementById('grado_id').value = gradoId;
        document.getElementById('nombre').value = nombre;
        
        const modal = document.getElementById('editModal');
        modal.style.display = 'flex';
        
        // Animar entrada
        const modalContent = modal.querySelector('.form-content');
        modalContent.style.animation = 'modalFadeIn 0.3s ease';
        
        // Enfocar el campo de nombre automáticamente
        setTimeout(() => {
            document.getElementById('nombre').focus();
            document.getElementById('nombre').select();
        }, 300);
        
        // Prevenir scroll
        document.body.style.overflow = 'hidden';
    }

    function ocultarEditModal() {
        const modal = document.getElementById('editModal');
        const modalContent = modal.querySelector('.form-content');
        
        // Animar salida
        modalContent.style.animation = 'modalFadeIn 0.25s ease reverse';
        
        // Esperar a que termine la animación
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restaurar scroll
        }, 250);
    }

    function confirmarEliminacion(id) {
        if (confirm('¿Está seguro que desea eliminar este grado? Esta acción no se puede deshacer.')) {
            // Mostrar indicador de carga
            const button = event.currentTarget;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            // Establecer los valores en el formulario
            document.getElementById('delete_grado_id').value = id;
            
            // Enviar el formulario después de un pequeño retraso para mostrar el spinner
            setTimeout(() => {
                document.getElementById('deleteForm').submit();
            }, 500);
        }
    }
    
    function editarMultigrado() {
        // Redirigir a una página de configuración multigrado
        window.location.href = 'configure_multigrado.php?sede_id=<?php echo $sede_id; ?>&nivel=<?php echo $nivel; ?>';
    }
    
    // Cerrar modales con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ocultarFormularioGrado();
            ocultarEditModal();
        }
    });
    
    // Cerrar modales al hacer clic fuera
    document.querySelectorAll('.form-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                if (this.id === 'formGrado') {
                    ocultarFormularioGrado();
                } else if (this.id === 'editModal') {
                    ocultarEditModal();
                }
            }
        });
    });
    
    // Mostrar alertas temporales
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            setTimeout(() => {
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        }
    });
    </script>
</body>
</html>