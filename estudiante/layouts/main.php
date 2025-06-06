<?php
// Verificar si el usuario está autenticado
if (!isset($_SESSION['estudiante_id'])) {
    header('Location: /school_management/auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sistema Escolar'; ?></title>
    
    <!-- Estilos comunes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos base -->
    <style>
        :root {
            --main-spacing: 1.5rem;
            --content-max-width: 1200px;
            --sidebar-width: 280px;
            --header-height: 70px;
            
            /* Colores consistentes */
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --bg-sidebar: #ffffff;
            --bg-hover: #f1f5f9;
            --bg-active: #e0e7ff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --bg-content: #f8fafc;
            
            /* Sombras */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-content);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        .content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: var(--main-spacing);
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: var(--main-spacing);
            box-shadow: var(--shadow-sm);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Card base para contenido */
        .content-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            margin-bottom: var(--main-spacing);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                padding: 1rem;
            }

            .sidebar.collapsed + .content-wrapper {
                margin-left: 0;
            }
        }
    </style>

    <!-- Estilos específicos de la página -->
    <?php if (isset($page_styles)): ?>
        <style><?php echo $page_styles; ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="main-container">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1 class="page-title"><?php echo $page_title ?? 'Página'; ?></h1>
                <?php if (isset($page_description)): ?>
                    <p class="page-description"><?php echo $page_description; ?></p>
                <?php endif; ?>
            </div>

            <!-- Contenido específico de la página -->
            <?php echo $content ?? ''; ?>
        </div>
    </div>

    <!-- Scripts específicos de la página -->
    <?php if (isset($page_scripts)): ?>
        <script><?php echo $page_scripts; ?></script>
    <?php endif; ?>
</body>
</html> 