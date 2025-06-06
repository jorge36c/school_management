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
        <button class="sidebar-toggle" aria-label="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/student/dashboard.php" 
                   class="nav-link <?php echo isActivePage('dashboard.php'); ?>">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span>ACADÉMICO</span>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/student/grades/view_grades.php" 
                   class="nav-link <?php echo isActivePage('grades/view_grades.php'); ?>">
                    <i class="fas fa-star"></i>
                    <span>Calificaciones</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>/student/schedule/view_schedule.php" 
                   class="nav-link <?php echo isActivePage('schedule/view_schedule.php'); ?>">
                    <i class="fas fa-calendar"></i>
                    <span>Horario</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Componente del reloj - igual que en el admin sidebar -->
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
/* Los mismos estilos que en admin/sidebar.php */
:root {
    --primary-color: #2C3E50;
    --secondary-color: #3498DB;
    --accent-color: #FFC107;
    --danger-color: #e74c3c;
    --text-color: #ffffff;
    --text-muted: rgba(255, 255, 255, 0.6);
    --sidebar-width: 280px;
    --sidebar-collapsed-width: 70px;
    --header-height: 64px;
    --transition-speed: 0.3s;
    --border-radius: 0.5rem;
}

/* ... (resto de los estilos igual que en admin/sidebar.php) ... */
</style>

<script>
// Funciones del sidebar
document.querySelector('.sidebar-toggle').addEventListener('click', () => {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.toggle('collapsed');
    mainContent?.classList.toggle('expanded');
    sidebar.classList.toggle('active');
});

document.addEventListener('click', (e) => {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(e.target) && 
        !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('active');
    }
});

// Funciones del contador
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
    if (elemento.textContent !== nuevoValor) {
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

document.addEventListener('DOMContentLoaded', actualizarPeriodoActivo);
setInterval(actualizarPeriodoActivo, 300000);
</script> 