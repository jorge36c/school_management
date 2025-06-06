<?php
// Función para obtener el saludo según la hora
function obtenerSaludo() {
    $hora = date('H');
    if ($hora < 12) return "¡Buenos días";
    if ($hora < 19) return "¡Buenas tardes";
    return "¡Buenas noches";
}
?>

<div class="top-bar">
    <!-- Lado izquierdo -->
    <div class="top-bar-left">
        <button id="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <a href="/school_management/estudiante/dashboard.php" class="home-link">
                <i class="fas fa-home"></i>
            </a>
            <?php if (isset($breadcrumb_path) && is_array($breadcrumb_path)): ?>
                <?php foreach ($breadcrumb_path as $index => $item): ?>
                    <span class="separator">/</span>
                    <span class="breadcrumb-item"><?php echo $item; ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="separator">/</span>
                <span class="breadcrumb-item"><?php echo $page_title ?? 'Dashboard'; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lado derecho -->
    <div class="top-bar-right">
        <div class="datetime-display">
            <div class="date-section">
                <i class="fas fa-calendar-alt"></i>
                <span id="current-date"></span>
            </div>
            <div class="time-section">
                <i class="fas fa-clock"></i>
                <span id="current-time"></span>
            </div>
        </div>
        
        <div class="user-section">
            <div class="greeting">
                <span class="greeting-text">
                    <?php echo obtenerSaludo(); ?>,
                </span>
                <span class="student-name">
                    <?php echo htmlspecialchars($_SESSION['estudiante_nombre'] ?? 'Estudiante'); ?>
                </span>
            </div>
            
            <div class="user-menu">
                <div class="avatar-wrapper">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <?php if (isset($notifications_count) && $notifications_count > 0): ?>
                        <span class="notification-badge"><?php echo $notifications_count; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botón de cerrar sesión -->
            <a href="/school_management/auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</div>

<!-- Los mismos estilos y script del topbar original -->
<style>
/* Copiar los estilos del topbar.php original */
</style>

<script>
/* Copiar el script del topbar.php original */
</script> 