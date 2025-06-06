<?php
$base_url = '/school_management';

function isActivePage($path) {
    return strpos($_SERVER['PHP_SELF'], $path) !== false ? 'active' : '';
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Portal Estudiante</span>
        </div>
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/estudiante/dashboard.php" 
                   class="nav-link <?php echo isActivePage('dashboard.php'); ?>">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span>ACADÉMICO1</span>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/estudiante/calificaciones.php" 
                   class="nav-link <?php echo isActivePage('calificaciones.php'); ?>">
                    <i class="fas fa-star"></i>
                    <span>Calificaciones111111111</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/estudiante/horario.php" 
                   class="nav-link <?php echo isActivePage('horario.php'); ?>">
                    <i class="fas fa-calendar"></i>
                    <span>Horario</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/estudiante/materias.php" 
                   class="nav-link <?php echo isActivePage('materias.php'); ?>">
                    <i class="fas fa-book"></i>
                    <span>Materias</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Contador de Periodo -->
    <div class="periodo-countdown">
        <div class="periodo-info">
            <h3>Periodo Actual</h3>
            <p id="periodoActual">Cargando...</p>
        </div>
        <div class="countdown-container">
            <div class="countdown-header">Tiempo restante:</div>
            <div class="countdown-timer">
                <div class="countdown-item">
                    <span id="dias">00</span>
                    <label>Días</label>
                </div>
                <div class="countdown-item">
                    <span id="horas">00</span>
                    <label>Horas</label>
                </div>
                <div class="countdown-item">
                    <span id="minutos">00</span>
                    <label>Min</label>
                </div>
                <div class="countdown-item">
                    <span id="segundos">00</span>
                    <label>Seg</label>
                </div>
            </div>
        </div>
    </div>
</aside>

<style>
:root {
    --sidebar-width: 280px;
    --primary-color: #1a237e;
    --secondary-color: #283593;
    --accent-color: #5c6bc0;
    --text-color: #ffffff;
    --text-muted: rgba(255, 255, 255, 0.7);
    --hover-bg: rgba(255, 255, 255, 0.1);
    --active-bg: rgba(255, 255, 255, 0.2);
    --transition-speed: 0.3s;
}

body {
    margin: 0;
    padding: 0;
    font-family: 'Arial', sans-serif;
    background-color: #f3f4f6;
}

.main-content {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    padding: 2rem;
    transition: margin-left var(--transition-speed);
}

.sidebar {
    width: var(--sidebar-width);
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
    color: var(--text-color);
    overflow-y: auto;
    transition: width var(--transition-speed);
    z-index: 1000;
}

.sidebar-header {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 0, 0, 0.1);
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.logo i {
    font-size: 1.5rem;
    color: var(--accent-color);
}

.logo span {
    font-size: 1.25rem;
    font-weight: 600;
}

.sidebar-toggle {
    background: transparent;
    border: none;
    color: var(--text-color);
    cursor: pointer;
    padding: 0.5rem;
    display: none;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 1rem 0;
}

.nav-section {
    padding: 1rem 1.5rem 0.5rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--text-muted);
    font-weight: 600;
    letter-spacing: 0.05em;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: var(--text-color);
    text-decoration: none;
    transition: all var(--transition-speed);
}

.nav-link i {
    width: 1.5rem;
    text-align: center;
    font-size: 1.1rem;
    transition: transform var(--transition-speed);
}

.nav-link:hover {
    background: var(--hover-bg);
}

.nav-link:hover i {
    transform: translateX(3px);
}

.nav-link.active {
    background: var(--active-bg);
    border-left: 3px solid var(--accent-color);
}

/* Estilos del contador */
.periodo-countdown {
    margin: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
}

.periodo-info h3 {
    font-size: 1rem;
    margin: 0 0 0.5rem 0;
}

.periodo-info p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.countdown-container {
    margin-top: 1rem;
    padding: 0.75rem;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 0.375rem;
}

.countdown-header {
    font-size: 0.75rem;
    margin-bottom: 0.5rem;
    color: var(--text-muted);
}

.countdown-timer {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
}

.countdown-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem;
    border-radius: 0.25rem;
}

.countdown-item span {
    display: block;
    font-size: 1.25rem;
    font-weight: bold;
    line-height: 1;
}

.countdown-item label {
    display: block;
    font-size: 0.625rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

@keyframes numberChange {
    0% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.number-changed {
    animation: numberChange 0.3s ease-out;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform var(--transition-speed);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: block;
    }

    .main-content {
        margin-left: 0;
    }
}
</style>

<script>
document.querySelector('.sidebar-toggle')?.addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('active');
});

// Cerrar sidebar al hacer clic fuera en móviles
document.addEventListener('click', (e) => {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(e.target) && 
        !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('active');
    }
});
</script> 