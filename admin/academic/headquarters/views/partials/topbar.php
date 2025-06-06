<?php
if (!isset($sede)) {
    die('Error: No se han proporcionado los datos de la sede');
}
?>
<div class="top-bar">
    <div class="page-title">
        <i class="fas fa-building"></i>
        Gestión de Sede
    </div>
    
    <div class="user-section">
        <!-- Sección del reloj -->
        <div class="time-display">
            <i class="fas fa-clock"></i>
            <span id="current-time"></span>
        </div>
        
        <!-- Sección del usuario -->
        <div class="user-info">
            <i class="fas fa-user"></i>
            <span><?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Administrador'; ?></span>
        </div>
        
        <!-- Botón de cerrar sesión -->
        <a href="/school_management/auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Cerrar Sesión
        </a>
    </div>
</div>

<!-- Breadcrumb fuera de la barra superior (si es necesario dejarlo en una posición diferente, opcional) -->
<div class="breadcrumb">
    <a href="/school_management/admin/users/list_headquarters.php">
        <i class="fas fa-building"></i>
        Sedes
    </a>
    <i class="fas fa-chevron-right"></i>
    <span><?php echo htmlspecialchars($sede['nombre']); ?></span>
</div>

<style>
/* Estilos específicos para la barra superior */
.top-bar {
    background: #1e293b; /* Color original */
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: auto;
    border-radius: 20px;
    box-shadow: var(--shadow-md);
    color: white;
}

.page-title {
    font-size: 1.5rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-title i {
    color: #60a5fa;
}

.user-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.time-display {
    background: rgba(255, 255, 255, 0.15);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.user-info {
    background: rgba(255, 255, 255, 0.15);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.logout-btn {
    background: #ef4444;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: transform 0.2s, background 0.3s ease;
}

.logout-btn:hover {
    background: #dc2626;
    transform: scale(1.05);
}

/* Breadcrumb fuera de la barra superior */
.breadcrumb {
    margin: 1rem 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    color: #64748b;
}

.breadcrumb a {
    color: #60a5fa;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.breadcrumb i {
    font-size: 0.75rem;
    color: #93c5fd;
}

@media (max-width: 768px) {
    .top-bar {
        flex-direction: column;
        padding: 1.5rem;
        gap: 1rem;
        text-align: center;
    }

    .user-section {
        gap: 1rem;
        flex-direction: column;
    }

    .breadcrumb {
        justify-content: center;
    }
}
</style>

<script>
// Actualizar el reloj en tiempo real
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('current-time').textContent = timeString;
}

// Iniciar el reloj
updateTime();
setInterval(updateTime, 1000);
</script>
