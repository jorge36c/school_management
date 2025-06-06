<?php
// Obtener el saludo según la hora
function obtenerSaludo() {
    $hora = date('H');
    if ($hora < 12) return "Buenos días";
    else if ($hora < 19) return "Buenas tardes";
    else return "Buenas noches";
}

// Obtener información del usuario actual
$nombre_usuario = isset($_SESSION['admin_nombre']) ? $_SESSION['admin_nombre'] : 'Administrador';
?>

<header class="top-bar">
    <div class="left-section">
        <button id="menu-toggle-btn" class="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h2>
    </div>
    
    <div class="right-section">
        <div class="datetime-display d-none d-md-flex">
            <div class="date-section me-3">
                <i class="fas fa-calendar-alt me-2"></i>
                <span id="current-date">Cargando...</span>
            </div>
            <div class="time-section">
                <i class="fas fa-clock me-2"></i>
                <span id="current-time">00:00</span>
            </div>
        </div>
        
        <div class="user-section">
            <div class="greeting d-none d-lg-block">
                <span><?php echo obtenerSaludo(); ?>,</span>
                <span class="ms-1 fw-bold"><?php echo htmlspecialchars($nombre_usuario); ?></span>
            </div>
            
            <div class="dropdown">
                <button class="btn dropdown-toggle user-menu-btn" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?php
                        // Obtener iniciales del nombre
                        $iniciales = '';
                        $palabras = explode(' ', $nombre_usuario);
                        foreach ($palabras as $palabra) {
                            if (!empty($palabra)) {
                                $iniciales .= strtoupper(substr($palabra, 0, 1));
                                if (strlen($iniciales) >= 2) break;
                            }
                        }
                        echo $iniciales ?: 'A';
                        ?>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                    <li><a class="dropdown-item" href="<?php echo $base_url ?? '/school_management'; ?>/admin/configuracion.php"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo $base_url ?? '/school_management'; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<style>
.top-bar {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    padding: 0.75rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 65px;
    border-radius: 12px;
    margin: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    color: white;
}

.left-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.menu-toggle {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.menu-toggle:hover {
    background: rgba(255, 255, 255, 0.2);
}

.page-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0;
}

.right-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.datetime-display {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    color: white;
}

.user-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.greeting {
    color: white;
    font-size: 0.95rem;
}

.user-menu-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    padding: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.user-menu-btn:hover,
.user-menu-btn:focus {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.user-menu-btn::after {
    display: none;
}

.user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

@media (max-width: 768px) {
    .page-title {
        font-size: 1.2rem;
    }
    
    .top-bar {
        padding: 0.5rem 1rem;
    }
}

@keyframes updateTime {
    0% { opacity: 0.7; }
    50% { opacity: 1; }
    100% { opacity: 0.7; }
}

#current-time.updating {
    animation: updateTime 0.5s;
}
</style>

<script>
// Conectar el botón de menú del topbar con el toggle del sidebar
document.addEventListener('DOMContentLoaded', function() {
    const menuToggleBtn = document.getElementById('menu-toggle-btn');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (menuToggleBtn && sidebarToggle) {
        menuToggleBtn.addEventListener('click', function() {
            // Simular clic en el botón del sidebar
            sidebarToggle.click();
        });
    }
    
    // Actualizar fecha y hora
    function updateDateTime() {
        const now = new Date();
        const dateElement = document.getElementById('current-date');
        const timeElement = document.getElementById('current-time');
        
        if (dateElement) {
            // Formatear fecha en español
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            let dateStr = now.toLocaleDateString('es-ES', options);
            // Capitalizar primera letra
            dateStr = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
            dateElement.textContent = dateStr;
        }
        
        if (timeElement) {
            const prevTime = timeElement.textContent;
            const timeStr = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            
            if (prevTime !== timeStr) {
                timeElement.classList.add('updating');
                setTimeout(() => timeElement.classList.remove('updating'), 500);
            }
            
            timeElement.textContent = timeStr;
        }
    }
    
    // Inicializar y programar actualizaciones
    updateDateTime();
    setInterval(updateDateTime, 1000);
});
</script>