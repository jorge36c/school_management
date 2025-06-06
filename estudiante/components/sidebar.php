<?php
/**
 * Componente Sidebar para el módulo de estudiantes
 * Diseño moderno y responsivo
 */

$base_url = '/school_management';
?>

<!-- Primero cargar el script del contador -->
<script>
let countdownInterval;

function actualizarPeriodoActivo() {
    const baseUrl = '<?php echo $base_url; ?>';
    fetch(`${baseUrl}/admin/get_periodo_activo.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const periodo = data.periodo;
                document.getElementById('periodoActual').textContent = 
                    `${periodo.ano_lectivo} - Periodo ${periodo.numero_periodo}`;
                iniciarCountdown(periodo.fecha_fin);
            } else {
                document.getElementById('periodoActual').textContent = 'No hay periodo activo';
                detenerCountdown();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('periodoActual').textContent = 'Error al cargar periodo';
        });
}

function iniciarCountdown(fechaFin) {
    detenerCountdown();
    countdownInterval = setInterval(() => {
        const ahora = new Date().getTime();
        const fin = new Date(fechaFin).getTime();
        const diferencia = fin - ahora;

        if (diferencia <= 0) {
            detenerCountdown();
            actualizarPeriodoActivo();
            return;
        }

        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

        actualizarElementoConAnimacion('dias', dias.toString().padStart(2, '0'));
        actualizarElementoConAnimacion('horas', horas.toString().padStart(2, '0'));
        actualizarElementoConAnimacion('minutos', minutos.toString().padStart(2, '0'));
        actualizarElementoConAnimacion('segundos', segundos.toString().padStart(2, '0'));
    }, 1000);
}

function actualizarElementoConAnimacion(id, nuevoValor) {
    const elemento = document.getElementById(id);
    if (elemento && elemento.textContent !== nuevoValor) {
        elemento.classList.add('number-changed');
        elemento.textContent = nuevoValor;
        setTimeout(() => elemento.classList.remove('number-changed'), 300);
    }
}

function detenerCountdown() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
}

// Iniciar el contador cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
    actualizarPeriodoActivo();
    // Actualizar cada 5 minutos
    setInterval(actualizarPeriodoActivo, 300000);
});
</script>

<!-- Luego el HTML del sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Sistema Escolar</span>
        </div>
    </div>

    <div class="sidebar-search">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar..." aria-label="Search">
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-section">
                <span>PRINCIPAL</span>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/estudiante/dashboard.php" 
                   class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-section">
                <span>ACADÉMICO</span>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/estudiante/calificaciones.php" 
                   class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'calificaciones.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <span>Calificaciones</span>
                </a>
            </li>

            <li class="nav-section">
                <span>MI CUENTA</span>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/estudiante/perfil.php" 
                   class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'perfil.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
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

<!-- Finalmente los estilos -->
<style>
:root {
    --sidebar-width: 280px;
    --sidebar-bg: #1a237e;
    --text-color: #ffffff;
    --item-hover: rgba(255, 255, 255, 0.1);
    --item-active: rgba(255, 255, 255, 0.2);
}

.sidebar {
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--sidebar-bg);
    color: var(--text-color);
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    z-index: 1000;
}

.sidebar-header {
    padding: 1rem;
    display: flex;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
}

.sidebar-search {
    padding: 0.5rem 1rem;
    margin-bottom: 1rem;
}

.search-wrapper {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-wrapper input {
    background: none;
    border: none;
    color: var(--text-color);
    width: 100%;
    outline: none;
}

.search-wrapper input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.nav-section {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.7);
}

.nav-item a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.2s;
}

.nav-item a:hover {
    background: var(--item-hover);
}

.nav-item a.active {
    background: var(--item-active);
}

/* Estilos para el contador de periodo */
.periodo-countdown {
    margin: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
}

.periodo-info h3 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.countdown-container {
    background: rgba(0, 0, 0, 0.2);
    padding: 0.75rem;
    border-radius: 0.375rem;
    margin-top: 0.5rem;
}

.countdown-header {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    opacity: 0.9;
}

.countdown-timer {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
    text-align: center;
}

.countdown-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.countdown-item span {
    font-size: 1.25rem;
    font-weight: bold;
}

.countdown-item label {
    font-size: 0.75rem;
    opacity: 0.7;
}

.number-changed {
    animation: numberPulse 0.3s ease-out;
}

@keyframes numberPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}
</style>