<?php
/**
 * Barra superior con controles y breadcrumb
 */

// Definir base_url si no está definido
if (!isset($profesor_base_url)) {
    $profesor_base_url = '/school_management/profesor';
}

// Incluir configuración si no está incluida
require_once $_SERVER['DOCUMENT_ROOT'] . '/school_management/config/config.php';

// Función para obtener el saludo según la hora
function obtenerSaludo() {
    $hora = date('H');
    if ($hora < 12) return "¡Buenos días";
    else if ($hora < 19) return "¡Buenas tardes";
    else return "¡Buenas noches";
}

// Obtener la información de la página actual para el breadcrumb
$current_path = $_SERVER['PHP_SELF'];
$path_parts = explode('/', trim($current_path, '/'));

// Determinar título de la página y construir breadcrumb
$breadcrumb = [];
$breadcrumb[] = ['url' => $profesor_base_url . '/dashboard.php', 'text' => 'Inicio'];

$current_url = '';
foreach ($path_parts as $part) {
    if ($part == 'school_management' || $part == 'profesor') continue;
    
    $current_url .= "/$part";
    $page_name = str_replace('.php', '', $part);
    
    // Determinar nombre legible de la sección
    $display_name = $section_names[$page_name] ?? ucfirst(str_replace('_', ' ', $page_name));
    
    if (strpos($part, '.php') !== false) {
        // Es el archivo final (página actual)
        $page_title = isset($page_title) ? $page_title : $display_name;
    } else {
        $url = "$profesor_base_url$current_url";
        $breadcrumb[] = ['url' => $url, 'text' => $display_name];
    }
}

// URL de cierre de sesión
$logout_url = '../auth/logout.php';

// Obtener nombre del profesor de la sesión
$profesor_nombre = $_SESSION['profesor_nombre'] ?? '';
$profesor_apellido = $_SESSION['profesor_apellido'] ?? '';
$nombre_completo = trim("$profesor_nombre $profesor_apellido");

// Si no tenemos el nombre completo, intentamos obtenerlo del objeto $profesor si existe
if (empty($nombre_completo) && isset($profesor) && is_array($profesor)) {
    $nombre_completo = trim($profesor['nombre'] . ' ' . $profesor['apellido']);
}

// Obtener la configuración para los colores del topbar
try {
    if (!isset($pdo)) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/school_management/config/database.php';
    }
    
    $stmt = $pdo->prepare("SELECT * FROM configuracion WHERE id = 1");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no hay resultados, usar valores predeterminados
    if (!$config) {
        $config = array(
            'sidebar_color' => '#4f46e5',
            'sidebar_text_color' => '#FFFFFF'
        );
    }
    
    // Calcular color secundario para degradados
    $primary_color = $config['sidebar_color'];
    $primary_light = adjustBrightness($primary_color, 30);
    
} catch (Exception $e) {
    // Error al cargar la configuración, usar valores predeterminados
    $primary_color = '#4f46e5';
    $primary_light = '#818cf8';
}

// Función para ajustar el brillo si no está definida
if (!function_exists('adjustBrightness')) {
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
}
?>

<!-- Estilos dinámicos para el topbar -->
<style>
:root {
    --primary: <?php echo $primary_color; ?>;
    --primary-light: <?php echo $primary_light; ?>;
}
</style>

<!-- Contenedor con margen superior para la topbar -->
<div class="topbar-container">
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
            
            <div class="user-section">
                <div class="greeting">
                    <span><?php echo obtenerSaludo(); ?>,</span>
                    <span class="profesor-name">
                        <?php echo htmlspecialchars($nombre_completo ?: 'Profesor'); ?>
                    </span>
                </div>
                
                <div class="user-menu">
                    <div class="avatar-wrapper" tabindex="0" role="button" aria-expanded="false" aria-haspopup="true">
                        <div class="user-avatar">
                            <?php
                            $initials = '';
                            $words = explode(' ', $nombre_completo);
                            foreach ($words as $word) {
                                if (!empty($word)) {
                                    $initials .= strtoupper(substr($word, 0, 1));
                                    if (strlen($initials) >= 2) break;
                                }
                            }
                            echo $initials ?: 'P';
                            ?>
                        </div>
                    </div>
                    
                    <div class="menu-dropdown">
                        <a href="<?php echo $profesor_base_url; ?>/views/perfil/index.php" class="menu-item">
                            <i class="fas fa-user-circle"></i>
                            <span>Mi Perfil</span>
                        </a>
                        <a href="<?php echo $profesor_base_url; ?>/views/perfil/cambiar_password.php" class="menu-item">
                            <i class="fas fa-key"></i>
                            <span>Cambiar Contraseña</span>
                        </a>
                        <div class="menu-divider"></div>
                        <a href="<?php echo $logout_url; ?>" class="menu-item text-red">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Cerrar Sesión</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incluir archivo CSS -->
<link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/topbar.css">