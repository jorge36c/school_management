<?php
// Definir base_url si no está definido
if (!isset($profesor_base_url)) {
    $profesor_base_url = '/school_management/profesor';
}

// Incluir la configuración de base de datos con ruta absoluta
require_once $_SERVER['DOCUMENT_ROOT'] . '/school_management/config/database.php';

// Función para verificar páginas activas
function isActivePage($path) {
    return strpos($_SERVER['PHP_SELF'], $path) !== false ? 'active' : '';
}

// Obtener la configuración de la base de datos para personalizar el sidebar
try {
    // Obtener la configuración más reciente
    $stmt = $pdo->prepare("SELECT * FROM configuracion WHERE id = 1");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no hay resultados, usar valores predeterminados
    if (!$config) {
        $config = array(
            'school_name' => 'Sistema Escolar',
            'sidebar_color' => '#8e24aa', // Púrpura como en la imagen
            'sidebar_text_color' => '#FFFFFF', // Texto blanco
            'sidebar_style' => 'flat'
        );
    }
    
    // Obtener periodo activo directamente desde la base de datos
    $stmt = $pdo->prepare("
        SELECT id, nombre, ano_lectivo_id, numero_periodo, fecha_inicio, fecha_fin, estado_periodo
        FROM periodos_academicos 
        WHERE estado = 'activo' AND estado_periodo = 'en_curso'
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $periodo_activo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener información del año lectivo si hay periodo activo
    if ($periodo_activo) {
        $stmt = $pdo->prepare("SELECT ano FROM anos_lectivos WHERE id = ?");
        $stmt->execute([$periodo_activo['ano_lectivo_id']]);
        $ano_lectivo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ano_lectivo) {
            $periodo_activo['ano_lectivo'] = $ano_lectivo['ano'];
        } else {
            $periodo_activo['ano_lectivo'] = date('Y');
        }
    }
    
} catch (Exception $e) {
    // Error al cargar la configuración, usar valores predeterminados
    $config = array(
        'school_name' => 'Sistema Escolar',
        'sidebar_color' => '#8e24aa', // Púrpura como en la imagen
        'sidebar_text_color' => '#FFFFFF', // Texto blanco
        'sidebar_style' => 'flat'
    );
    $periodo_activo = null;
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

// Color para elementos activos
$active_bg_color = '#2196f3'; // Azul como en la imagen

// Estructura de navegación para profesor
$navigation = [
    'secciones' => [
        [
            'titulo' => 'GENERAL',
            'items' => [
                [
                    'url' => '/dashboard.php',
                    'icono' => 'fa-home',
                    'texto' => 'Dashboard'
                ]
            ]
        ],
        [
            'titulo' => 'ACADÉMICO',
            'items' => [
                [
                    'url' => '/views/calificaciones/lista_calificaciones.php',
                    'icono' => 'fa-star',
                    'texto' => 'Calificaciones'
                ],
                [
                    'url' => '/views/asistencia/index.php',
                    'icono' => 'fa-clipboard-check',
                    'texto' => 'Asistencia'
                ]
            ]
        ],
        [
            'titulo' => 'RECURSOS',
            'items' => [
                [
                    'url' => '/views/recursos/index.php',
                    'icono' => 'fa-book-open',
                    'texto' => 'Material Didáctico'
                ],
                [
                    'url' => '/views/mensajes/index.php',
                    'icono' => 'fa-envelope',
                    'texto' => 'Mensajes'
                ]
            ]
        ],
        [
            'titulo' => 'CONFIGURACIÓN',
            'items' => [
                [
                    'url' => '/views/perfil/index.php',
                    'icono' => 'fa-user-cog',
                    'texto' => 'Mi Perfil'
                ]
            ]
        ],
    ]
];

// Función para calcular días restantes (renombrada para evitar conflictos)
function calcularDiasRestantesSidebar($fecha_fin) {
    if (!$fecha_fin) return 0;
    
    $hoy = new DateTime();
    $fin = new DateTime($fecha_fin);
    $diferencia = $hoy->diff($fin);
    
    // Si la fecha ya pasó, devolver 0
    if ($diferencia->invert) {
        return 0;
    }
    
    return $diferencia->days;
}

// Datos iniciales para el contador
$dias_restantes = 0;
$periodo_texto = 'No hay periodo activo';

// Configurar datos de periodo si existe
if ($periodo_activo) {
    $dias_restantes = calcularDiasRestantesSidebar($periodo_activo['fecha_fin']);
    $periodo_texto = $periodo_activo['ano_lectivo'] . ' - Periodo ' . $periodo_activo['numero_periodo'];
}
?>

<!-- Agregar estilos dinámicos directamente en la página -->
<style>
:root {
    /* Paleta de colores dinámica desde la configuración */
    --primary-color: <?php echo $config['sidebar_color']; ?>;
    --secondary-color: <?php echo $secondary_color; ?>;
    --active-color: <?php echo $active_bg_color; ?>;
    --text-color: <?php echo $config['sidebar_text_color']; ?>;
    --text-muted: rgba(255, 255, 255, 0.7);
    --accent-color: #fbbf24;
    --danger-color: #dc2626;
    --success-color: #10b981;
}
</style>

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
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach($navigation['secciones'] as $seccion): ?>
                <li class="nav-section">
                    <span><?php echo $seccion['titulo']; ?></span>
                </li>
                <?php foreach($seccion['items'] as $item): 
                    $isActive = isActivePage($item['url']);
                ?>
                    <li class="nav-item">
                        <a href="<?php echo $profesor_base_url . $item['url']; ?>" 
                           class="nav-link <?php echo $isActive; ?>">
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
            <p id="periodoActual"><?php echo htmlspecialchars($periodo_texto); ?></p>
        </div>
        <div class="countdown-container">
            <div class="countdown-header">Tiempo restante:</div>
            <div class="countdown-timer" <?php if ($periodo_activo): ?>data-fecha-fin="<?php echo $periodo_activo['fecha_fin']; ?>"<?php endif; ?>>
                <div class="countdown-item">
                    <span id="dias"><?php echo str_pad($dias_restantes, 2, '0', STR_PAD_LEFT); ?></span>
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

<!-- Incluir archivo CSS -->
<link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/sidebar.css">