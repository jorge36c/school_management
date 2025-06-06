<?php
// Obtener el título de la página basado en la sede si está disponible
$pageTitle = isset($sede) ? htmlspecialchars($sede['nombre']) . ' - Gestión de Sede' : 'Gestión de Sede';

// Obtener el entorno (desarrollo/producción)
$isProduction = false; // Cambiar a true en producción

// Versión de los archivos para cache busting
$version = '1.0.0';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="description" content="Sistema de gestión escolar - Administración de sedes">
<meta name="author" content="Tu Nombre o Empresa">

<!-- Título de la página -->
<title><?php echo $pageTitle; ?></title>

<!-- Favicon -->
<link rel="icon" type="image/png" href="/school_management/assets/images/favicon.png">

<!-- Fuentes -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- Estilos principales -->
<?php if ($isProduction): ?>
    <!-- Versión minificada para producción -->
    <link rel="stylesheet" href="/school_management/admin/academic/headquarters/assets/css/sede.min.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="/school_management/admin/academic/headquarters/assets/css/components.min.css?v=<?php echo $version; ?>">
<?php else: ?>
    <!-- Versión de desarrollo -->
    <link rel="stylesheet" href="/school_management/admin/academic/headquarters/assets/css/sede.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="/school_management/admin/academic/headquarters/assets/css/components.css?v=<?php echo $version; ?>">
<?php endif; ?>

<!-- Estilos específicos para el módulo de sedes -->
<style>
    :root {
        --primary-color: #3b82f6;
        --primary-light: #60a5fa;
        --primary-dark: #2563eb;
        --secondary-color: #64748b;
        --success-color: #22c55e;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --background-color: #f8fafc;
        --card-background: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --hover-bg: #f1f5f9;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    /* Estilos críticos que deben cargarse inmediatamente */
    .loading {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .loading::after {
        content: '';
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Prevención de FOUC (Flash of Unstyled Content) */
    .no-fouc {
        display: none;
    }
</style>

<!-- Scripts críticos -->
<script>
    // Prevención de FOUC
    document.documentElement.className = 'no-fouc';
    window.addEventListener('load', function() {
        document.documentElement.className = '';
    });

    // Variables globales
    window.schoolManagement = {
        baseUrl: '/school_management',
        isProduction: <?php echo $isProduction ? 'true' : 'false'; ?>,
        version: '<?php echo $version; ?>'
    };
</script>

<!-- Configuración de seguridad -->
<meta http-equiv="Content-Security-Policy" content="
    default-src 'self';
    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com;
    font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com;
    img-src 'self' data: https:;
    script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com;
">

<!-- Soporte para navegadores antiguos -->
<!--[if IE]>
    <link rel="stylesheet" href="/school_management/assets/css/ie-support.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-polyfill/7.12.1/polyfill.min.js"></script>
<![endif]-->