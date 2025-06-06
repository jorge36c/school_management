<?php
// Verificar la sesión del profesor
if (!isset($_SESSION['profesor_id'])) {
    header("Location: /school_management/auth/profesor_login.php");
    exit;
}

// Definir base_url si no está definido
if (!isset($profesor_base_url)) {
    $profesor_base_url = '/school_management/profesor';
}

// Título de la página por defecto
if (!isset($pageTitle)) {
    $pageTitle = 'Sistema de Gestión Escolar';
}

// Breadcrumb por defecto
if (!isset($breadcrumb)) {
    $breadcrumb = [
        ['url' => $profesor_base_url . '/dashboard.php', 'text' => 'Dashboard']
    ];
    
    // Añadir página actual al breadcrumb si tiene título
    if (isset($pageTitle)) {
        $breadcrumb[] = ['url' => '#', 'text' => $pageTitle];
    }
}

// URL para cerrar sesión
$logout_url = '/school_management/auth/logout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo $profesor_base_url; ?>">
    <title><?php echo $pageTitle; ?> | Panel del Profesor</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/dashboard.css">
    <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/sidebar.css">
    <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/topbar.css">
    
    <!-- Estilos específicos para asistencia -->
    <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/asistencia.css">
    
    <!-- Estilos específicos de la página (si se definen) -->
    <?php if (isset($pageStyles)): ?>
    <style><?php echo $pageStyles; ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="admin-container">
        <?php include_once __DIR__ . '/../components/sidebar.php'; ?>
        <div class="main-content">
            <?php include_once __DIR__ . '/../components/topbar.php'; ?>
            <div class="content-wrapper">
                <!-- Aquí comienza el contenido de la página -->