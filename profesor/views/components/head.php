<?php
// Definir base_url si no está definido
if (!isset($profesor_base_url)) {
    $profesor_base_url = '/school_management/profesor';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo $profesor_base_url; ?>">
    <title><?php echo $page_title ?? 'Sistema Escolar'; ?> | <?php echo htmlspecialchars($nombre_completo ?? 'Profesor'); ?></title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos base -->
    <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/sidebar.css">
    <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/topbar.css">
    <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/dashboard.css">
    
    <!-- Estilos adicionales específicos de la página -->
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>