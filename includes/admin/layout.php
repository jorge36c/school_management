<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sistema Escolar'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/school_management/assets/css/admin/layout.css">
    <link rel="stylesheet" href="/school_management/assets/css/admin/components.css">
    <link rel="stylesheet" href="/school_management/assets/css/admin/tables.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="admin-container">
        <?php 
        $base_path = $_SERVER['DOCUMENT_ROOT'] . '/school_management';
        include $base_path . '/includes/admin/sidebar.php';
        include $base_path . '/includes/admin/top_bar.php';
        ?>
        <div class="main-content">
            <div class="content-wrapper">
                <?php echo $content; ?>
            </div>
        </div>
    </div>

    <script src="/school_management/assets/js/admin/common.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 