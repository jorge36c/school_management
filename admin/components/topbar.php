<?php
// Función para obtener el saludo según la hora
function obtenerSaludo() {
    $hora = date('H');
    if ($hora < 12) return "¡Buenos días";
    else if ($hora < 19) return "¡Buenas tardes";
    else return "¡Buenas noches";
}

// Definir la base URL si no está definida
if (!isset($base_url)) {
    $base_url = '/school_management';
}

// Obtener la información de la página actual para el breadcrumb
$current_path = $_SERVER['PHP_SELF'];
$path_parts = explode('/', trim($current_path, '/'));

// Mapeo de secciones para el breadcrumb
$section_names = [
    'dashboard' => 'Dashboard',
    'academic' => 'Académico',
    'periods' => 'Periodos',
    'users' => 'Usuarios',
    'students' => 'Estudiantes',
    'teachers' => 'Profesores',
    'headquarters' => 'Sedes'
];

// Determinar título de la página y construir breadcrumb
$breadcrumb = [];
$breadcrumb[] = ['url' => '/school_management/admin/dashboard.php', 'text' => 'Inicio'];

$current_url = '';
foreach ($path_parts as $part) {
    if ($part == 'school_management' || $part == 'admin') continue;
    
    $current_url .= "/$part";
    $page_name = str_replace('.php', '', $part);
    
    // Determinar nombre legible de la sección
    $display_name = $section_names[$page_name] ?? ucfirst(str_replace('_', ' ', $page_name));
    
    if (strpos($part, '.php') !== false) {
        // Es el archivo final (página actual)
        $page_title = $display_name;
    } else {
        $url = "/school_management/admin{$current_url}";
        $breadcrumb[] = ['url' => $url, 'text' => $display_name];
    }
}

// Si no se pudo determinar el título, usar un valor predeterminado basado en el archivo
if (!isset($page_title)) {
    $current_page = end($path_parts);
    $page_title = ucfirst(str_replace(['.php', '_'], ['', ' '], $current_page));
}

// URL de cierre de sesión (definida una sola vez)
$logout_url = $base_url . '/auth/logout.php';
?>

<div class="top-bar" id="topBar">
    <div class="left-section">
        <button id="menuToggle" class="menu-toggle" aria-label="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="breadcrumb-container">
            <h2 class="page-title"><?php echo $page_title; ?></h2>
            
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <?php foreach ($breadcrumb as $index => $item): ?>
                    <?php if ($index > 0): ?>
                        <span class="breadcrumb-separator">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($index == count($breadcrumb) - 1): ?>
                        <span class="breadcrumb-item current"><?php echo $item['text']; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $item['url']; ?>" class="breadcrumb-item">
                            <?php echo $item['text']; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    
    <div class="right-section">
        <div class="datetime-display">
            <div class="date-section">
                <i class="fas fa-calendar-alt"></i>
                <span id="current-date">Cargando fecha...</span>
            </div>
            <div class="time-section">
                <i class="fas fa-clock"></i>
                <span id="current-time">00:00</span>
            </div>
        </div>
        
        <!-- Botón para abrir configuración de tema -->
        <button class="config-toggle-btn" id="themeConfigBtn" aria-label="Configurar apariencia">
            <i class="fas fa-palette"></i>
        </button>
        
        <div class="user-section">
            <div class="greeting">
                <span><?php echo obtenerSaludo(); ?>,</span>
                <span class="admin-name">
                    <?php echo $_SESSION['admin_nombre'] ?? 'Administrador'; ?>
                </span>
            </div>
            
            <div class="user-menu">
                <div class="avatar-wrapper" tabindex="0" role="button" aria-expanded="false" aria-haspopup="true">
                    <div class="user-avatar">
                        <?php
                        $admin_name = $_SESSION['admin_nombre'] ?? 'Administrador';
                        $initials = '';
                        $words = explode(' ', $admin_name);
                        foreach ($words as $word) {
                            if (!empty($word)) {
                                $initials .= strtoupper(substr($word, 0, 1));
                                if (strlen($initials) >= 2) break;
                            }
                        }
                        echo $initials ?: 'A';
                        ?>
                    </div>
                </div>
                
                <div class="menu-dropdown">
                    <a href="<?php echo $logout_url; ?>" class="menu-item text-red">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Topbar - ajustado para coincidir con el dashboard */
.top-bar {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 56px;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: margin-left var(--transition);
    position: relative;
    z-index: 10;
}

/* Sección izquierda - Título y breadcrumb */
.left-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.menu-toggle {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.menu-toggle:hover {
    background: rgba(255, 255, 255, 0.2);
}

.menu-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

.breadcrumb-container {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: white;
    margin: 0;
    line-height: 1.3;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
}

.breadcrumb-item {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb-item:hover {
    color: white;
}

.breadcrumb-item.current {
    color: white;
    font-weight: 500;
}

.breadcrumb-separator {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.7rem;
    line-height: 1;
    display: flex;
    align-items: center;
}

/* Sección derecha - Fecha, hora y usuario */
.right-section {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.datetime-display {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: rgba(0, 0, 0, 0.15);
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    color: white;
    font-size: 0.8rem;
}

.date-section, .time-section {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.date-section i, .time-section i {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.8rem;
}

.user-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.greeting {
    display: flex;
    flex-direction: column;
    text-align: right;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
}

.admin-name {
    font-weight: 600;
    color: white;
}

.user-menu {
    position: relative;
}

.avatar-wrapper {
    position: relative;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.avatar-wrapper:hover {
    transform: scale(1.05);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.user-avatar:hover {
    background: rgba(255, 255, 255, 0.3);
}

.menu-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.2s ease-in-out;
    z-index: 1000;
    overflow: hidden;
}

.user-menu:hover .menu-dropdown,
.avatar-wrapper:focus + .menu-dropdown,
.menu-dropdown:hover {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.menu-item:hover {
    background: var(--bg-light);
}

.menu-item i {
    width: 16px;
    text-align: center;
    font-size: 0.85rem;
}

.text-red {
    color: var(--danger-color);
}

.text-red:hover {
    background: rgba(220, 38, 38, 0.1) !important;
}

.text-red i {
    color: var(--danger-color);
}

/* Toggle Button de Configuración */
.config-toggle-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background-color: rgba(0, 0, 0, 0.15);
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.config-toggle-btn:hover {
    background-color: rgba(0, 0, 0, 0.25);
    transform: rotate(30deg);
}

.config-toggle-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

/* Estilos responsivos */
@media (max-width: 991.98px) {
    .top-bar {
        margin-left: 0;
    }
    
    .greeting {
        display: none;
    }
    
    .datetime-display {
        flex-direction: column;
        gap: 0.25rem;
        align-items: flex-start;
        padding: 0.4rem 0.5rem;
    }
    
    .date-section, .time-section {
        font-size: 0.7rem;
    }
}

@media (max-width: 767.98px) {
    .datetime-display {
        display: none;
    }
    
    .breadcrumb {
        display: none;
    }
}

@media (max-width: 575.98px) {
    .page-title {
        font-size: 1rem;
    }
    
    .top-bar {
        padding: 0.5rem 0.75rem;
        height: 50px;
    }
}

/* Estilos para cuando el sidebar está colapsado */
.sidebar-collapsed ~ .main-content .top-bar {
    margin-left: 0;
}

/* Animación para el tiempo */
@keyframes pulseTime {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

#current-time.updating {
    animation: pulseTime 0.5s ease-in-out;
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referencia al botón de menú en el top bar
    const menuToggle = document.getElementById('menuToggle');
    
    // Conectar con el sidebar (asumiendo que está presente en la página)
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Sincronizar los botones de toggle
    menuToggle.addEventListener('click', function() {
    // Llamar a la función expuesta por el sidebar
    if (window.toggleSidebar) {
        window.toggleSidebar();
    }
});
    
    /**
     * Actualiza la fecha y hora actual con formato localizado
     */
    function updateDateTime() {
        const now = new Date();
        const dateElement = document.getElementById('current-date');
        const timeElement = document.getElementById('current-time');
        
        if (dateElement) {
            // Formatear fecha en español
            const dateOptions = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            const dateFormatted = now.toLocaleDateString('es-ES', dateOptions);
            // Capitalizar primera letra
            const dateCapitalized = dateFormatted.charAt(0).toUpperCase() + dateFormatted.slice(1);
            
            dateElement.textContent = dateCapitalized;
        }
        
        if (timeElement) {
            // Verificar si el contenido va a cambiar
            const prevTime = timeElement.textContent;
            
            // Formatear hora (con AM/PM)
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            };
            
            const timeFormatted = now.toLocaleTimeString('es-ES', timeOptions);
            
            // Si la hora cambió, añadir clase para animación
            if (prevTime !== timeFormatted) {
                timeElement.classList.add('updating');
                setTimeout(() => {
                    timeElement.classList.remove('updating');
                }, 500);
            }
            
            timeElement.textContent = timeFormatted;
        }
    }
    
    // Actualizar inmediatamente y luego cada segundo
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
    // Dropdown del usuario - accesibilidad por teclado
    const avatarWrapper = document.querySelector('.avatar-wrapper');
    const menuDropdown = document.querySelector('.menu-dropdown');
    
    if (avatarWrapper && menuDropdown) {
        // Al hacer clic en el avatar mostrar el menú
        avatarWrapper.addEventListener('click', function() {
            const expanded = avatarWrapper.getAttribute('aria-expanded') === 'true';
            avatarWrapper.setAttribute('aria-expanded', !expanded);
            
            if (!expanded) {
                menuDropdown.style.opacity = '1';
                menuDropdown.style.visibility = 'visible';
                menuDropdown.style.transform = 'translateY(0)';
            } else {
                menuDropdown.style.opacity = '0';
                menuDropdown.style.visibility = 'hidden';
                menuDropdown.style.transform = 'translateY(10px)';
            }
        });
        
        // Manejar evento de teclado
        avatarWrapper.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                
                const expanded = avatarWrapper.getAttribute('aria-expanded') === 'true';
                avatarWrapper.setAttribute('aria-expanded', !expanded);
                
                if (!expanded) {
                    menuDropdown.style.opacity = '1';
                    menuDropdown.style.visibility = 'visible';
                    menuDropdown.style.transform = 'translateY(0)';
                    
                    // Enfocar el primer elemento del menú
                    const firstMenuItem = menuDropdown.querySelector('.menu-item');
                    if (firstMenuItem) {
                        firstMenuItem.focus();
                    }
                } else {
                    menuDropdown.style.opacity = '0';
                    menuDropdown.style.visibility = 'hidden';
                    menuDropdown.style.transform = 'translateY(10px)';
                }
            }
            
            // Cerrar con Escape
            if (e.key === 'Escape') {
                avatarWrapper.setAttribute('aria-expanded', 'false');
                menuDropdown.style.opacity = '0';
                menuDropdown.style.visibility = 'hidden';
                menuDropdown.style.transform = 'translateY(10px)';
                avatarWrapper.focus();
            }
        });
        
        // Cerrar el menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!avatarWrapper.contains(e.target) && !menuDropdown.contains(e.target)) {
                avatarWrapper.setAttribute('aria-expanded', 'false');
                menuDropdown.style.opacity = '0';
                menuDropdown.style.visibility = 'hidden';
                menuDropdown.style.transform = 'translateY(10px)';
            }
        });
    }
});
</script>

<!-- Estilos y scripts para la configuración del tema -->
<link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/theme-config.css">
<script src="<?php echo $base_url; ?>/assets/js/theme-config.js"></script>

<script>
// Este script se encarga de inicializar el configurador de tema cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Configurar el botón para abrir el modal de configuración
    const themeConfigBtn = document.getElementById('themeConfigBtn');
    
    if (themeConfigBtn) {
        console.log('Botón de configuración encontrado, configurando evento...');
        
        themeConfigBtn.addEventListener('click', function() {
            console.log('Botón de configuración clickeado');
            
            // Verificar si el ThemeConfigManager está cargado
            if (typeof ThemeConfigManager === 'undefined') {
                console.error('ThemeConfigManager no está cargado');
                alert('Error: No se pudo cargar el configurador de tema');
                return;
            }
            
            // Si el ThemeManager no existe, lo creamos
            if (!window.themeManager) {
                console.log('Creando nueva instancia de ThemeConfigManager');
                window.themeManager = new ThemeConfigManager();
            }
            
            // Abrir el modal
            window.themeManager.openConfigModal();
        });
    } else {
        console.error('No se encontró el botón de configuración con ID themeConfigBtn');
    }
});
</script>