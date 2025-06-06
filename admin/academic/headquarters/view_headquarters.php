<?php
// Primero incluir config.php
require_once '../../../includes/config.php';

// DESPUÉS de incluir config.php, iniciar la sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['admin_id'])) {
    header('Location: /school_management/auth/login.php');
    exit();
}

// Obtener el ID de la sede
$sede_id = $_GET['id'] ?? null;

if (!$sede_id) {
    header('Location: ../../users/list_headquarters.php');
    exit();
}

try {
    // Obtener datos de la sede
    $sql = "SELECT * FROM sedes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$sede_id]);
    // Corregir el método para obtener resultados
    $result = $stmt->get_result();
    $sede = $result->fetch_assoc();

    if (!$sede) {
        header('Location: ../../users/list_headquarters.php');
        exit();
    }

    // Obtener estadísticas para cada nivel
    $stats = [];
    $niveles = ['preescolar', 'primaria', 'secundaria', 'media'];

    foreach ($niveles as $nivel) {
        // Contar estudiantes
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM estudiantes 
            WHERE sede_id = ? 
            AND nivel = ? 
            AND estado = 'Activo'
        ");
        $stmt->execute([$sede_id, $nivel]);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $estudiantes = $row['count'];

        // Contar grados
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM grados 
            WHERE sede_id = ? 
            AND nivel = ?
        ");
        $stmt->execute([$sede_id, $nivel]);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $grados = $row['count'];

        $stats[$nivel] = [
            'estudiantes' => $estudiantes,
            'grados' => $grados
        ];
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Configurar el título de la página y el breadcrumb
$page_title = "Ver Sede";
$current_page = 'View Headquarters';
$breadcrumb_path = [
    'Inicio',
    'Sedes',
    'View Headquarters'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Sede - <?php echo htmlspecialchars($sede['nombre']); ?></title>
    
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
    }

    body {
        background-color: #f8fafc;
        color: var(--dark);
        font-family: 'Inter', sans-serif;
    }

    /* === TOP BAR === */
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 2rem;
        background: linear-gradient(to right, var(--primary), var(--primary-dark));
        color: white;
        border-radius: var(--radius-lg);
        margin: 1rem 1rem 2rem 1rem;
        box-shadow: var(--shadow-md);
    }

    .page-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title {
        font-size: 1.1rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }

    .time-display {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .back-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: white;
        text-decoration: none;
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-md);
        transition: var(--transition-fast);
        background-color: rgba(255, 255, 255, 0.1);
    }

    .back-button:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: translateX(-3px);
    }

    /* === SEDE HEADER === */
    .sede-header {
        background: white;
        padding: 1.75rem;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        margin-bottom: 1.75rem;
        margin-left: 1rem;
        margin-right: 1rem;
        position: relative;
        overflow: hidden;
    }
    
    .sede-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 4px;
        width: 100%;
        background: linear-gradient(to right, var(--primary), var(--primary-light));
    }

    .sede-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .sede-title i {
        color: var(--primary);
        font-size: 1.75rem;
        background-color: var(--primary-50);
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-lg);
    }

    .sede-title h1 {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--dark);
        letter-spacing: -0.01em;
    }

    .sede-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.75rem;
        margin-bottom: 1.25rem;
    }

    .sede-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--gray);
        font-size: 0.95rem;
        padding: 0.5rem 0.75rem;
        background-color: var(--gray-lighter);
        border-radius: var(--radius-md);
    }

    .sede-meta i {
        color: var(--primary);
    }

    .edit-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background-color: var(--primary-50);
        color: var(--primary);
        border: none;
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        text-decoration: none;
        border-radius: var(--radius-md);
        transition: var(--transition);
    }

    .edit-button:hover {
        background-color: var(--primary-100);
        transform: translateY(-2px);
    }

    .edit-button i {
        font-size: 0.9rem;
    }

    /* === SECTION HEADER === */
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.75rem;
        padding: 0 1rem;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
        letter-spacing: -0.01em;
    }

    .section-title i {
        color: var(--primary);
        background-color: var(--primary-50);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
        font-size: 1.1rem;
    }

    .add-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--primary-50);
        color: var(--primary);
        border: none;
        font-weight: 600;
        cursor: pointer;
        padding: 0.75rem 1.25rem;
        transition: var(--transition);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
    }

    .add-button:hover {
        background: var(--primary-100);
        transform: translateY(-2px);
    }

    .add-button i {
        font-size: 0.9rem;
    }

    /* === NIVELES GRID === */
    .niveles-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
        padding: 0 1rem 1.5rem 1rem;
    }

    .nivel-card {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        border: 1px solid var(--gray-light);
        position: relative;
    }

    .nivel-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 4px;
        width: 100%;
    }

    .nivel-preescolar::before { background: var(--info); }
    .nivel-primaria::before { background: var(--success); }
    .nivel-secundaria::before { background: var(--purple); }
    .nivel-media::before { background: var(--warning); }

    .nivel-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .nivel-header {
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .nivel-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: var(--transition);
    }

    .nivel-card:hover .nivel-icon {
        transform: scale(1.05);
    }

    .nivel-preescolar .nivel-icon {
        background: var(--info-100);
        color: var(--info);
    }

    .nivel-primaria .nivel-icon {
        background: var(--success-100);
        color: var(--success);
    }

    .nivel-secundaria .nivel-icon {
        background: var(--purple-100);
        color: var(--purple);
    }

    .nivel-media .nivel-icon {
        background: var(--warning-100);
        color: var(--warning);
    }

    .nivel-info {
        flex: 1;
    }

    .nivel-name {
        margin: 0 0 0.75rem 0;
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--dark);
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
    }

    .nivel-stats {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--gray);
        font-size: 0.95rem;
        padding: 0.35rem 0;
    }

    .stat-item i {
        width: 28px;
        height: 28px;
        background-color: var(--gray-lighter);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
        color: var(--dark);
        font-size: 0.85rem;
    }

    .nivel-preescolar .stat-item i {
        background-color: var(--info-50);
        color: var(--info);
    }

    .nivel-primaria .stat-item i {
        background-color: var(--success-50);
        color: var(--success);
    }

    .nivel-secundaria .stat-item i {
        background-color: var(--purple-50);
        color: var(--purple);
    }

    .nivel-media .stat-item i {
        background-color: var(--warning-50);
        color: var(--warning);
    }

    .nivel-actions {
        padding: 1.5rem;
        border-top: 1px solid var(--gray-light);
        background-color: var(--gray-lighter);
    }

    .btn-manage {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.875rem;
        background: var(--dark);
        color: white;
        border: none;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        box-shadow: var(--shadow-sm);
    }

    .nivel-preescolar .btn-manage { background: var(--info); }
    .nivel-primaria .btn-manage { background: var(--success); }
    .nivel-secundaria .btn-manage { background: var(--purple); }
    .nivel-media .btn-manage { background: var(--warning); }

    .btn-manage:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        filter: brightness(1.1);
    }

    .action-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
    }

    .btn-action {
        background: transparent;
        border: none;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-md);
        transition: var(--transition-fast);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-disable {
        color: var(--warning);
    }

    .btn-disable:hover {
        background-color: var(--warning-50);
    }

    .btn-delete {
        color: var(--danger);
    }

    .btn-delete:hover {
        background-color: var(--danger-50);
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

    .modal-content h3 {
        margin-top: 0;
        margin-bottom: 1.75rem;
        font-size: 1.5rem;
        color: var(--dark);
        position: relative;
        padding-bottom: 0.75rem;
        letter-spacing: -0.01em;
    }

    .modal-content h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--primary);
        border-radius: 3px;
    }

    .form-group {
        margin-bottom: 1.75rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
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
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px var(--primary-100);
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
        background: var(--primary);
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
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
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

    /* === SKELETON LOADING === */
    .skeleton {
        animation: skeletonPulse 1.5s infinite ease-in-out;
        background: linear-gradient(90deg, var(--gray-light) 0%, var(--gray-lighter) 50%, var(--gray-light) 100%);
        background-size: 200% 100%;
        border-radius: var(--radius-md);
    }

    @keyframes skeletonPulse {
        0% { background-position: 0% 0; }
        100% { background-position: -200% 0; }
    }

    /* === TOOLTIPS === */
    [data-tooltip] {
        position: relative;
    }

    [data-tooltip]:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: var(--dark);
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        white-space: nowrap;
        z-index: 10;
        margin-bottom: 10px;
        box-shadow: var(--shadow-md);
    }

    [data-tooltip]:hover::before {
        content: '';
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: var(--dark);
        margin-bottom: 0px;
    }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        .sede-meta {
            flex-direction: column;
            gap: 0.75rem;
        }

        .niveles-container {
            grid-template-columns: 1fr;
        }
        
        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .add-button {
            align-self: stretch;
            justify-content: center;
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

    /* === CONFIGURACIÓN TIPO DE ENSEÑANZA === */
    .config-icon {
        background: none;
        border: none;
        color: var(--gray);
        cursor: pointer;
        font-size: 0.9rem;
        padding: 0.25rem;
        margin-left: 0.5rem;
        border-radius: 50%;
        transition: var(--transition-fast);
    }

    .config-icon:hover {
        color: var(--primary);
        background-color: var(--primary-50);
        transform: rotate(30deg);
    }

    .tipo-ensenanza-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-md);
        margin-left: 0.5rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .badge-unigrado {
        background-color: var(--info-50);
        color: var(--info);
    }

    .badge-multigrado {
        background-color: var(--warning-50);
        color: var(--warning);
    }

    .badge-hibrido {
        background-color: var(--purple-50);
        color: var(--purple);
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../../sidebar.php'; ?>

        <div class="main-content">
            <?php include '../../components/topbar.php'; ?>

            <!-- Alertas -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <?php 
                        $success_message = '';
                        switch ($_GET['success']) {
                            case 'nivel_eliminado':
                                $success_message = 'El nivel ha sido eliminado correctamente.';
                                break;
                            case 'nivel_deshabilitado':
                                $success_message = 'El nivel ha sido deshabilitado correctamente.';
                                break;
                            case 'config_actualizada':
                                $success_message = 'La configuración de tipo de enseñanza ha sido actualizada.';
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
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></div>
                </div>
            <?php endif; ?>

            <!-- Header de la Sede -->
            <div class="sede-header fade-in">
                <div class="sede-title">
                    <i class="fas fa-building"></i>
                    <h1><?php echo htmlspecialchars($sede['nombre']); ?></h1>
                </div>
                <div class="sede-meta">
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($sede['direccion'] ?? 'Vereda ' . $sede['nombre']); ?></span>
                    <?php if (!empty($sede['telefono'])): ?>
                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($sede['telefono']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($sede['email'])): ?>
                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($sede['email']); ?></span>
                    <?php endif; ?>
                </div>
                <a href="edit_headquarters.php?id=<?php echo $sede['id']; ?>" class="edit-button">
                    <i class="fas fa-edit"></i> Editar Sede
                </a>
            </div>

            <!-- Sección Niveles Educativos -->
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-layer-group"></i>
                    Niveles Educativos
                </h2>
                <button class="add-button" onclick="mostrarFormularioNivel()">
                    <i class="fas fa-plus"></i> Agregar Nivel
                </button>
            </div>

            <!-- Grid de Niveles -->
            <div class="niveles-container">
                <!-- Preescolar -->
                <div class="nivel-card nivel-preescolar fade-in" style="animation-delay: 0.1s">
                    <div class="nivel-header">
                        <div class="nivel-icon">
                            <i class="fas fa-child"></i>
                        </div>
                        <div class="nivel-info">
                            <h3 class="nivel-name">
                            Preescolar
                                <button class="config-icon" onclick="mostrarConfiguracionNivel('preescolar')" data-tooltip="Configurar tipo de enseñanza">
                                    <i class="fas fa-cog"></i>
                                </button>
                                
                                <?php
                                // Mostrar insignia de tipo de enseñanza si está configurado
                                $stmt = $conn->prepare("
                                    SELECT tipo_ensenanza FROM niveles_configuracion 
                                    WHERE sede_id = ? AND nivel = 'preescolar'
                                ");
                                $stmt->bind_param('i', $sede_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $config = $result->fetch_assoc();
                                    $tipo_ensenanza = $config['tipo_ensenanza'];
                                    $tipo_texto = ucfirst($tipo_ensenanza);
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
                                    
                                    echo "<span class='tipo-ensenanza-badge badge-$tipo_ensenanza'>";
                                    echo "<i class='fas $icon_class'></i> $tipo_texto";
                                    echo "</span>";
                                }
                                ?>
                            </h3>
                            <div class="nivel-stats">
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $stats['preescolar']['estudiantes'] ?? 0; ?> estudiantes</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-chalkboard"></i>
                                    <span><?php echo $stats['preescolar']['grados'] ?? 0; ?> grados</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="nivel-actions">
                        <a href="view_level.php?sede_id=<?php echo $sede_id; ?>&nivel=preescolar" class="btn-manage">
                            <i class="fas fa-arrow-right"></i>
                            <span>Gestionar Grados</span>
                        </a>
                        <div class="action-buttons">
                            <button class="btn-action btn-disable" onclick="confirmarDeshabilitarNivel('preescolar')" data-tooltip="Deshabilitar nivel">
                                <i class="fas fa-ban"></i> Deshabilitar
                            </button>
                            <button class="btn-action btn-delete" onclick="confirmarEliminacionNivel('preescolar')" data-tooltip="Eliminar nivel">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Primaria -->
                <div class="nivel-card nivel-primaria fade-in" style="animation-delay: 0.2s">
                    <div class="nivel-header">
                        <div class="nivel-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="nivel-info">
                            <h3 class="nivel-name">
                                Primaria
                                <button class="config-icon" onclick="mostrarConfiguracionNivel('primaria')" data-tooltip="Configurar tipo de enseñanza">
                                    <i class="fas fa-cog"></i>
                                </button>
                                
                                <?php
                                // Mostrar insignia de tipo de enseñanza si está configurado
                                $stmt = $conn->prepare("
                                    SELECT tipo_ensenanza FROM niveles_configuracion 
                                    WHERE sede_id = ? AND nivel = 'primaria'
                                ");
                                $stmt->bind_param('i', $sede_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $config = $result->fetch_assoc();
                                    $tipo_ensenanza = $config['tipo_ensenanza'];
                                    $tipo_texto = ucfirst($tipo_ensenanza);
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
                                    
                                    echo "<span class='tipo-ensenanza-badge badge-$tipo_ensenanza'>";
                                    echo "<i class='fas $icon_class'></i> $tipo_texto";
                                    echo "</span>";
                                }
                                ?>
                            </h3>
                            <div class="nivel-stats">
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $stats['primaria']['estudiantes'] ?? 0; ?> estudiantes</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-chalkboard"></i>
                                    <span><?php echo $stats['primaria']['grados'] ?? 0; ?> grados</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="nivel-actions">
                        <a href="view_level.php?sede_id=<?php echo $sede_id; ?>&nivel=primaria" class="btn-manage">
                            <i class="fas fa-arrow-right"></i>
                            <span>Gestionar Grados</span>
                        </a>
                        <div class="action-buttons">
                            <button class="btn-action btn-disable" onclick="confirmarDeshabilitarNivel('primaria')" data-tooltip="Deshabilitar nivel">
                                <i class="fas fa-ban"></i> Deshabilitar
                            </button>
                            <button class="btn-action btn-delete" onclick="confirmarEliminacionNivel('primaria')" data-tooltip="Eliminar nivel">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Secundaria -->
                <div class="nivel-card nivel-secundaria fade-in" style="animation-delay: 0.3s">
                    <div class="nivel-header">
                        <div class="nivel-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="nivel-info">
                            <h3 class="nivel-name">
                                Secundaria
                                <button class="config-icon" onclick="mostrarConfiguracionNivel('secundaria')" data-tooltip="Configurar tipo de enseñanza">
                                    <i class="fas fa-cog"></i>
                                </button>
                                
                                <?php
                                // Mostrar insignia de tipo de enseñanza si está configurado
                                $stmt = $conn->prepare("
                                    SELECT tipo_ensenanza FROM niveles_configuracion 
                                    WHERE sede_id = ? AND nivel = 'secundaria'
                                ");
                                $stmt->bind_param('i', $sede_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $config = $result->fetch_assoc();
                                    $tipo_ensenanza = $config['tipo_ensenanza'];
                                    $tipo_texto = ucfirst($tipo_ensenanza);
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
                                    
                                    echo "<span class='tipo-ensenanza-badge badge-$tipo_ensenanza'>";
                                    echo "<i class='fas $icon_class'></i> $tipo_texto";
                                    echo "</span>";
                                }
                                ?>
                            </h3>
                            <div class="nivel-stats">
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $stats['secundaria']['estudiantes'] ?? 0; ?> estudiantes</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-chalkboard"></i>
                                    <span><?php echo $stats['secundaria']['grados'] ?? 0; ?> grados</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="nivel-actions">
                        <a href="view_level.php?sede_id=<?php echo $sede_id; ?>&nivel=secundaria" class="btn-manage">
                            <i class="fas fa-arrow-right"></i>
                            <span>Gestionar Grados</span>
                        </a>
                        <div class="action-buttons">
                            <button class="btn-action btn-disable" onclick="confirmarDeshabilitarNivel('secundaria')" data-tooltip="Deshabilitar nivel">
                                <i class="fas fa-ban"></i> Deshabilitar
                            </button>
                            <button class="btn-action btn-delete" onclick="confirmarEliminacionNivel('secundaria')" data-tooltip="Eliminar nivel">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Media -->
                <div class="nivel-card nivel-media fade-in" style="animation-delay: 0.4s">
                    <div class="nivel-header">
                        <div class="nivel-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="nivel-info">
                            <h3 class="nivel-name">
                                Media
                                <button class="config-icon" onclick="mostrarConfiguracionNivel('media')" data-tooltip="Configurar tipo de enseñanza">
                                    <i class="fas fa-cog"></i>
                                </button>
                                
                                <?php
                                // Mostrar insignia de tipo de enseñanza si está configurado
                                $stmt = $conn->prepare("
                                    SELECT tipo_ensenanza FROM niveles_configuracion 
                                    WHERE sede_id = ? AND nivel = 'media'
                                ");
                                $stmt->bind_param('i', $sede_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $config = $result->fetch_assoc();
                                    $tipo_ensenanza = $config['tipo_ensenanza'];
                                    $tipo_texto = ucfirst($tipo_ensenanza);
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
                                    
                                    echo "<span class='tipo-ensenanza-badge badge-$tipo_ensenanza'>";
                                    echo "<i class='fas $icon_class'></i> $tipo_texto";
                                    echo "</span>";
                                }
                                ?>
                            </h3>
                            <div class="nivel-stats">
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $stats['media']['estudiantes'] ?? 0; ?> estudiantes</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-chalkboard"></i>
                                    <span><?php echo $stats['media']['grados'] ?? 0; ?> grados</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="nivel-actions">
                        <a href="view_level.php?sede_id=<?php echo $sede_id; ?>&nivel=media" class="btn-manage">
                            <i class="fas fa-arrow-right"></i>
                            <span>Gestionar Grados</span>
                        </a>
                        <div class="action-buttons">
                            <button class="btn-action btn-disable" onclick="confirmarDeshabilitarNivel('media')" data-tooltip="Deshabilitar nivel">
                                <i class="fas fa-ban"></i> Deshabilitar
                            </button>
                            <button class="btn-action btn-delete" onclick="confirmarEliminacionNivel('media')" data-tooltip="Eliminar nivel">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para eliminar nivel -->
    <form id="deleteNivelForm" method="POST" action="delete_level.php" style="display: none;">
        <input type="hidden" name="nivel" id="delete_nivel_name">
        <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
    </form>

    <!-- Modal para agregar nivel -->
    <div id="formNivel" class="modal">
        <div class="modal-content">
            <h3>Agregar Nuevo Nivel</h3>
            <form action="add_level.php" method="POST">
                <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
                
                <div class="form-group">
                    <label for="nivel">Nivel:</label>
                    <select name="nivel" id="nivel" class="form-control" required>
                        <option value="">Seleccione un nivel...</option>
                        <option value="preescolar">Preescolar</option>
                        <option value="primaria">Primaria</option>
                        <option value="secundaria">Secundaria</option>
                        <option value="media">Media</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="ocultarFormularioNivel()" class="btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Nivel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para configurar tipo de enseñanza -->
    <div id="configNivelModal" class="modal">
        <div class="modal-content">
            <h3>Configurar Tipo de Enseñanza</h3>
            <form action="update_teaching_type.php" method="POST">
                <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
                <input type="hidden" name="nivel" id="config_nivel_name">
                
                <div class="form-group">
                    <label for="tipo_ensenanza">Tipo de Enseñanza:</label>
                    <select name="tipo_ensenanza" id="tipo_ensenanza" class="form-control" required>
                        <option value="unigrado">Unigrado (un solo grado)</option>
                        <option value="multigrado">Multigrado (varios grados en un aula)</option>
                        <option value="hibrido">Híbrido (mezcla de ambos)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="observaciones">Observaciones (opcional):</label>
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="ocultarConfiguracionNivel()" class="btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funciones para gestionar niveles
        function mostrarFormularioNivel() {
            document.getElementById('formNivel').style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevenir scroll
            
            // Añadir animación de entrada
            const modalContent = document.querySelector('.modal-content');
            modalContent.style.animation = 'modalFadeIn 0.3s ease';
        }

        function ocultarFormularioNivel() {
            const modal = document.getElementById('formNivel');
            
            // Añadir animación de salida
            const modalContent = document.querySelector('.modal-content');
            modalContent.style.animation = 'modalFadeIn 0.3s ease reverse';
            
            // Esperar a que termine la animación
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Restaurar scroll
            }, 250);
        }

        function confirmarEliminacionNivel(nivel) {
            const capitalizedNivel = nivel.charAt(0).toUpperCase() + nivel.slice(1);
            
            if (confirm(`¿Está seguro que desea eliminar el nivel ${capitalizedNivel}? Esta acción no se puede deshacer.`)) {
                document.getElementById('delete_nivel_name').value = nivel;
                document.getElementById('deleteNivelForm').submit();
            }
        }
        
        function confirmarDeshabilitarNivel(nivel) {
            const capitalizedNivel = nivel.charAt(0).toUpperCase() + nivel.slice(1);
            
            if (confirm(`¿Está seguro que desea deshabilitar el nivel ${capitalizedNivel}? Esto desactivará todos los grados asociados.`)) {
                // Mostrar indicador de carga
                const button = event.currentTarget;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                button.disabled = true;
                
                fetch('disable_level.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sede_id: <?php echo $sede_id; ?>,
                        nivel: nivel
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'view_headquarters.php?id=<?php echo $sede_id; ?>&success=nivel_deshabilitado';
                    } else {
                        button.innerHTML = originalText;
                        button.disabled = false;
                        window.location.href = 'view_headquarters.php?id=<?php echo $sede_id; ?>&error=' + encodeURIComponent(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    button.innerHTML = originalText;
                    button.disabled = false;
                    alert('Error al deshabilitar el nivel');
                });
            }
        }
        
        // Funciones para gestionar la configuración de tipo de enseñanza
        function mostrarConfiguracionNivel(nivel) {
            document.getElementById('config_nivel_name').value = nivel;
            document.getElementById('configNivelModal').style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevenir scroll
            
            // Cargar configuración actual del nivel (AJAX)
            fetch('get_teaching_type.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sede_id: <?php echo $sede_id; ?>,
                    nivel: nivel
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('tipo_ensenanza').value = data.tipo_ensenanza || 'unigrado';
                    document.getElementById('observaciones').value = data.observaciones || '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
            
            // Añadir animación de entrada
            const modalContent = document.querySelector('#configNivelModal .modal-content');
            modalContent.style.animation = 'modalFadeIn 0.3s ease';
        }

        function ocultarConfiguracionNivel() {
            const modal = document.getElementById('configNivelModal');
            
            // Añadir animación de salida
            const modalContent = document.querySelector('#configNivelModal .modal-content');
            modalContent.style.animation = 'modalFadeIn 0.3s ease reverse';
            
            // Esperar a que termine la animación
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Restaurar scroll
            }, 250);
        }
        
        // Cerrar modales con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ocultarFormularioNivel();
                ocultarConfiguracionNivel();
            }
        });
        
        // Cerrar modales al hacer clic fuera de ellos
        document.getElementById('formNivel').addEventListener('click', function(e) {
            if (e.target === this) {
                ocultarFormularioNivel();
            }
        });
        
        document.getElementById('configNivelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                ocultarConfiguracionNivel();
            }
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