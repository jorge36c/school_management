<?php
// Definir base_url
$base_url = '/school_management';

// Función para verificar páginas activas
function isActivePage($path) {
    return strpos($_SERVER['PHP_SELF'], $path) !== false ? 'active' : '';
}

// Obtener la configuración de la base de datos para personalizar el sidebar
try {
    // Incluir la configuración de base de datos
    require_once __DIR__ . '/../config/database.php';
    
    // Obtener la configuración más reciente
    $stmt = $pdo->prepare("SELECT * FROM configuracion WHERE id = 1");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no hay resultados, usar valores predeterminados
    if (!$config) {
        $config = array(
            'school_name' => 'Sistema Escolar',
            'sidebar_color' => '#1a2b40',
            'sidebar_text_color' => '#FFFFFF',
            'sidebar_style' => 'default'
        );
    }
} catch (Exception $e) {
    // Error al cargar la configuración, usar valores predeterminados
    $config = array(
        'school_name' => 'Sistema Escolar',
        'sidebar_color' => '#1a2b40',
        'sidebar_text_color' => '#FFFFFF',
        'sidebar_style' => 'default'
    );
}

// Calcular color secundario para degradados
function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

$secondary_color = adjustBrightness($config['sidebar_color'], 30);

// Estructura de navegación simplificada
$navigation = [
    'secciones' => [
        [
            'titulo' => 'GENERAL',
            'items' => [
                [
                    'url' => '/admin/dashboard.php',
                    'icono' => 'fa-home',
                    'texto' => 'Dashboard'
                ]
            ]
        ],
        [
            'titulo' => 'ACADÉMICO',
            'items' => [
                [
                    'url' => '/admin/academic/list_dba.php',
                    'icono' => 'fa-book',
                    'texto' => 'Asignaturas'
                ],
                [
                    'url' => '/admin/periods/list_periods.php',
                    'icono' => 'fa-calendar-alt',
                    'texto' => 'Periodos'
                ]
            ]
        ],
        [
            'titulo' => 'USUARIOS',
            'items' => [
                [
                    'url' => '/admin/users/list_teachers.php',
                    'icono' => 'fa-chalkboard-teacher',
                    'texto' => 'Profesores'
                ],
                [
                    'url' => '/admin/users/list_students.php',
                    'icono' => 'fa-user-graduate',
                    'texto' => 'Estudiantes'
                ]
            ]
        ],
        [
            'titulo' => 'SEDES',
            'items' => [
                [
                    'url' => '/admin/users/list_headquarters.php',
                    'icono' => 'fa-building',
                    'texto' => 'Gestión de Sedes'
                ]
            ]
        ],
    ]
];
?>

<aside class="sidebar <?php echo $config['sidebar_style']; ?>" id="sidebar" data-style="<?php echo $config['sidebar_style']; ?>">
    <div class="sidebar-header">
        <div class="logo" data-logo="<?php echo htmlspecialchars($config['school_logo'] ?? ''); ?>">
            <?php if (!empty($config['school_logo'])): ?>
                <img src="<?php echo htmlspecialchars($config['school_logo']); ?>" alt="Logo" class="school-logo">
            <?php else: ?>
                <i class="fas fa-graduation-cap"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($config['school_name']); ?></span>
        </div>
        <!-- Eliminado el botón de toggle del sidebar -->
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach($navigation['secciones'] as $seccion): ?>
                <li class="nav-section">
                    <span><?php echo $seccion['titulo']; ?></span>
                </li>
                <?php foreach($seccion['items'] as $item): ?>
                    <li class="nav-item">
                        <a href="<?php echo $base_url . $item['url']; ?>" 
                           class="nav-link <?php echo isActivePage($item['url']); ?>">
                            <i class="fas <?php echo $item['icono']; ?>"></i>
                            <span><?php echo $item['texto']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Componente del contador de periodo -->
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
    
    <!-- Overlay para dispositivos móviles -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</aside>

<style>
:root {
    /* Paleta de colores dinámica desde la configuración */
    --primary-color: <?php echo $config['sidebar_color']; ?>;
    --secondary-color: <?php echo $secondary_color; ?>;
    --text-color: <?php echo $config['sidebar_text_color']; ?>;
    --text-muted: <?php echo adjustBrightness($config['sidebar_text_color'], -30); ?>;
    --sidebar-width: 280px;
    --sidebar-collapsed-width: 70px;
    --transition-speed: 0.25s;
    --border-radius: 8px;
}

.sidebar {
    width: var(--sidebar-width);
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    color: var(--text-color);
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    overflow: hidden;
    transition: width var(--transition-speed) ease-in-out;
}

.sidebar.flat {
    background: var(--primary-color) !important;
}

.sidebar.gradient {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
}

.sidebar.default {
    background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color)) !important;
}

.sidebar-header {
    padding: 0 1.25rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 0, 0, 0.15);
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.logo i, .logo img {
    font-size: 1.5rem;
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.logo span {
    font-size: 1.25rem;
    font-weight: 600;
}

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-section {
    padding: 1rem 1.25rem 0.5rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
    color: var(--text-muted);
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    color: var(--text-color);
    text-decoration: none;
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
}

.nav-link:hover, .nav-link.active {
    background: rgba(255, 255, 255, 0.1);
    border-left-color: var(--text-color);
}

/* Componente del contador de periodo */
.periodo-countdown {
    background: rgba(0, 0, 0, 0.2);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.periodo-info {
    margin-bottom: 1rem;
}

.periodo-info h3 {
    color: var(--text-color);
    font-size: 1rem;
    margin: 0 0 0.5rem 0;
}

.periodo-info p {
    color: var(--text-color);
    opacity: 0.9;
    font-size: 0.875rem;
    margin: 0;
}

.countdown-container {
    background: rgba(0, 0, 0, 0.15);
    border-radius: var(--border-radius);
    padding: 0.75rem;
}

.countdown-header {
    color: var(--text-color);
    opacity: 0.8;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.countdown-timer {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
    text-align: center;
}

.countdown-item {
    background: rgba(0, 0, 0, 0.25);
    border-radius: var(--border-radius);
    padding: 0.5rem 0.25rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.countdown-item span {
    color: var(--text-color);
    font-size: 1.25rem;
    font-weight: 600;
    display: block;
}

.countdown-item label {
    color: var(--text-muted);
    font-size: 0.7rem;
    margin-top: 0.25rem;
    display: block;
    text-transform: uppercase;
}

/* Estado colapsado */
.sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
}

.sidebar.collapsed .logo span,
.sidebar.collapsed .nav-link span,
.sidebar.collapsed .nav-section,
.sidebar.collapsed .periodo-countdown {
    display: none;
}

.sidebar.collapsed .nav-link {
    padding: 0.75rem;
    justify-content: center;
}

/* Estilos responsive */
@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }
    
    .sidebar.active ~ .sidebar-overlay {
        display: block;
    }
}

.main-content {
    margin-left: var(--sidebar-width);
    transition: margin var(--transition-speed) ease;
}

.main-content.expanded {
    margin-left: var(--sidebar-collapsed-width);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');

    // Verificar estado guardado
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    if (sidebarState === 'true') {
        sidebar.classList.add('collapsed');
        if (mainContent) mainContent.classList.add('expanded');
    }

    // Cerrar en móviles
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }

    // Variables para el contador
    let countdownInterval;
    let lastValues = {
        dias: '00',
        horas: '00',
        minutos: '00',
        segundos: '00'
    };

    /**
     * Actualiza la información del periodo activo
     */
    function actualizarPeriodoActivo() {
        const baseUrl = '<?php echo $base_url; ?>';
        
        fetch(`${baseUrl}/admin/get_periodo_activo.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const periodo = data.periodo;
                    document.getElementById('periodoActual').textContent = 
                        `${periodo.ano_lectivo} - Periodo ${periodo.numero_periodo}`;
                    
                    // Iniciar countdown
                    iniciarCountdown(periodo.fecha_fin);
                } else {
                    document.getElementById('periodoActual').textContent = 'No hay periodo activo';
                    detenerCountdown();
                }
            })
            .catch(error => {
                console.log("Error:", error);
                document.getElementById('periodoActual').textContent = 'Sin información de periodo';
            });
    }

    /**
     * Inicia el contador de tiempo para el periodo
     * @param {string} fechaFin - Fecha de finalización del periodo
     */
    function iniciarCountdown(fechaFin) {
        // Detener countdown anterior si existe
        detenerCountdown();

        countdownInterval = setInterval(() => {
            const ahora = new Date().getTime();
            const fin = new Date(fechaFin).getTime();
            const diferencia = fin - ahora;

            if (diferencia <= 0) {
                detenerCountdown();
                document.getElementById('periodoActual').textContent = 'Periodo finalizado';
                
                // Verificar nuevo periodo después de un breve retraso
                setTimeout(actualizarPeriodoActivo, 10000);
                return;
            }

            // Calcular tiempo
            const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
            const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
            const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

            // Actualizar elementos
            document.getElementById('dias').textContent = dias.toString().padStart(2, '0');
            document.getElementById('horas').textContent = horas.toString().padStart(2, '0');
            document.getElementById('minutos').textContent = minutos.toString().padStart(2, '0');
            document.getElementById('segundos').textContent = segundos.toString().padStart(2, '0');
        }, 1000);
    }

    /**
     * Detiene el contador de tiempo
     */
    function detenerCountdown() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    }

    // Iniciar al cargar la página
    actualizarPeriodoActivo();
    
    // Actualizar cada 5 minutos
    setInterval(actualizarPeriodoActivo, 300000);
    
    // Exponer función para el botón en el topbar
    window.toggleSidebar = function() {
        sidebar.classList.toggle('collapsed');
        sidebar.classList.toggle('active');
        if (mainContent) mainContent.classList.toggle('expanded');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    };
});
</script>