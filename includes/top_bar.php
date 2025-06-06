<header class="top-bar">
    <div class="top-bar-left">
        <button id="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <?php if (isset($page_icon)): ?>
                <i class="<?php echo $page_icon; ?>"></i>
            <?php endif; ?>
            <?php if (isset($page_title)): ?>
                <span><?php echo $page_title; ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="top-bar-right">
        <div class="top-bar-time">
            <i class="far fa-clock"></i>
            <span id="current-time"></span>
        </div>
        <?php if (isset($profesor)): ?>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($profesor['nombre_completo'] ?? ''); ?></span>
                <span class="user-role">Profesor</span>
            </div>
        <?php endif; ?>
    </div>
</header> 