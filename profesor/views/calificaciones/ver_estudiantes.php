<?php
/**
 * Vista para visualizar y gestionar calificaciones de estudiantes
 * Versión simplificada para solucionar errores
 */

// Verificar si hay parámetros necesarios
if (!isset($_GET['grado_id']) || !isset($_GET['materia_id'])) {
    header('Location: lista_calificaciones.php');
    exit;
}

// Activar el reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicializar variables
$grado_id = intval($_GET['grado_id']);
$materia_id = intval($_GET['materia_id']);
$sede_id = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : 0;
$es_multigrado = isset($_GET['es_multigrado']) && $_GET['es_multigrado'] == '1';
$nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';

// Asegurar que siempre tengamos un periodo_id, incluso si no viene en la URL
session_start();
$periodo_id = isset($_GET['periodo_id']) ? intval($_GET['periodo_id']) : 
             (isset($_SESSION['ultimo_periodo_id']) ? $_SESSION['ultimo_periodo_id'] : 0);

$error = null;
$estudiantes = [];
$tipos_notas_raw = [];
$tipos_notas = [];
$grado = [];
$periodo_actual = null;
$estadisticas = [
    'total_estudiantes' => 0,
    'promedio_general' => 0,
    'estudiantes_aprobados' => 0,
    'estudiantes_reprobados' => 0
];

// Categorías para los iconos
$iconos_categoria = [
    'TAREAS' => 'book',
    'EVALUACIONES' => 'clipboard-check',
    'AUTOEVALUACION' => 'user-check',
    'OTROS' => 'star' // Valor por defecto
];

// Conexión a la base de datos
try {
    require_once __DIR__ . '/../../../config/database.php';
    
    // Crear una nueva conexión PDO básica
    $host = 'localhost';
    $dbname = 'school_management';
    $username = 'root';
    $password = '';
    
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // Si no podemos conectar, seguimos con el archivo pero mostramos el error
    $error = "Error de conexión a la base de datos: " . $e->getMessage();
    $db = null;
}

// Funciones de ayuda para calificaciones
class CalificacionesHelperSimple {
    public static function calcularDefinitiva($calificaciones, $tipos_notas) {
        $definitiva = 0;
        $total_peso = 0;
        $categorias = [];
        
        // Si no hay tipos de notas, devolvemos 0
        if (empty($tipos_notas)) {
            return ["definitiva" => "0.0", "categorias" => []];
        }
        
        // Calcular por categoría
        foreach ($tipos_notas as $categoria => $tipos) {
            if (!is_array($tipos) || empty($tipos)) continue;
            
            $sum_cat = 0;
            $peso_cat = 0;
            
            foreach ($tipos as $tipo) {
                $id = isset($tipo['id']) ? $tipo['id'] : 0;
                $valor = isset($calificaciones[$id]) ? floatval($calificaciones[$id]) : 0;
                $peso = isset($tipo['porcentaje']) ? floatval($tipo['porcentaje']) : 0;
                
                $sum_cat += $valor * $peso;
                $peso_cat += $peso;
            }
            
            $valor_cat = $peso_cat > 0 ? $sum_cat / $peso_cat : 0;
            
            // Pesos por categoría predeterminados
            $peso_categoria = 0;
            if ($categoria == 'TAREAS') $peso_categoria = 40;
            elseif ($categoria == 'EVALUACIONES') $peso_categoria = 50;
            elseif ($categoria == 'AUTOEVALUACION') $peso_categoria = 10;
            else $peso_categoria = 10; // Otras categorías
            
            $definitiva += $valor_cat * ($peso_categoria / 100);
            $total_peso += $peso_categoria;
            
            $categorias[$categoria] = [
                "valor" => $valor_cat,
                "porcentaje" => $peso_categoria
            ];
        }
        
        // Ajustar si el total de pesos no suma 100
        if ($total_peso > 0 && $total_peso != 100) {
            $definitiva = ($definitiva / $total_peso) * 100;
        }
        
        return [
            "definitiva" => number_format($definitiva, 1),
            "categorias" => $categorias
        ];
    }
    
    public static function calcularCompletitud($calificaciones, $tipos_notas) {
        $total_tipos = 0;
        $tipos_con_nota = 0;
        
        foreach ($tipos_notas as $categoria => $tipos) {
            if (!is_array($tipos)) continue;
            
            foreach ($tipos as $tipo) {
                if (!isset($tipo['id'])) continue;
                
                $id = $tipo['id'];
                $total_tipos++;
                
                if (isset($calificaciones[$id]) && $calificaciones[$id] !== '') {
                    $tipos_con_nota++;
                }
            }
        }
        
        $porcentaje = $total_tipos > 0 ? ($tipos_con_nota / $total_tipos) * 100 : 0;
        
        return [
            'porcentaje' => $porcentaje,
            'completo' => $porcentaje >= 100
        ];
    }
    
    public static function getColorClase($nota) {
        $nota = floatval($nota);
        
        if ($nota >= 4.6) return 'nota-excelente';
        if ($nota >= 4.0) return 'nota-buena';
        if ($nota >= 3.0) return 'nota-aceptable';
        if ($nota >= 1.0) return 'nota-baja';
        return 'nota-reprobada';
    }
    
    public static function calcularEstadisticas($estudiantes, $tipos_notas) {
        $total = count($estudiantes);
        $suma = 0;
        $aprobados = 0;
        $reprobados = 0;
        
        foreach ($estudiantes as $est) {
            $cal = isset($est['calificaciones']) ? $est['calificaciones'] : [];
            $def = self::calcularDefinitiva($cal, $tipos_notas);
            $val = floatval($def['definitiva']);
            
            $suma += $val;
            if ($val >= 3.0) $aprobados++;
            else $reprobados++;
        }
        
        return [
            'total_estudiantes' => $total,
            'promedio_general' => $total > 0 ? $suma / $total : 0,
            'estudiantes_aprobados' => $aprobados,
            'estudiantes_reprobados' => $reprobados
        ];
    }
}

// Datos de ejemplo (usados si no podemos obtener datos reales)
$tipos_notas_ejemplo = [
    'TAREAS' => [
        ['id' => 1, 'nombre' => 'Tarea 1', 'porcentaje' => 20],
        ['id' => 2, 'nombre' => 'Tarea 2', 'porcentaje' => 20]
    ],
    'EVALUACIONES' => [
        ['id' => 3, 'nombre' => 'Evaluación 1', 'porcentaje' => 25],
        ['id' => 4, 'nombre' => 'Evaluación 2', 'porcentaje' => 25]
    ],
    'AUTOEVALUACION' => [
        ['id' => 5, 'nombre' => 'Autoevaluación', 'porcentaje' => 10]
    ]
];

$estudiantes_ejemplo = [
    ['id' => 1, 'nombre' => 'Juan', 'apellido' => 'Pérez', 'calificaciones' => [1 => 4.5, 2 => 3.8, 3 => 4.0]],
    ['id' => 2, 'nombre' => 'María', 'apellido' => 'González', 'calificaciones' => [1 => 3.2, 2 => 3.5, 3 => 4.5]]
];

// Obtener datos reales de la base de datos
try {
    // Verificar que tenemos una conexión a la base de datos
    if ($db) {
        // Obtener información del periodo seleccionado si existe
        if ($periodo_id > 0) {
            $stmtPeriodo = $db->prepare("
                SELECT 
                    id, 
                    nombre, 
                    fecha_inicio, 
                    fecha_fin,
                    estado_periodo,
                    numero_periodo
                FROM periodos_academicos 
                WHERE id = :periodo_id
            ");
            $stmtPeriodo->execute([':periodo_id' => $periodo_id]);
            $periodo_actual = $stmtPeriodo->fetch(PDO::FETCH_ASSOC);
        }
        
        // Si no se especificó un periodo o no se encontró, obtener el periodo activo
        if (!$periodo_actual) {
            $stmtPeriodo = $db->prepare("
                SELECT 
                    id, 
                    nombre, 
                    fecha_inicio, 
                    fecha_fin,
                    estado_periodo,
                    numero_periodo
                FROM periodos_academicos 
                WHERE estado_periodo = 'en_curso'
                ORDER BY id DESC 
                LIMIT 1
            ");
            $stmtPeriodo->execute();
            $periodo_actual = $stmtPeriodo->fetch(PDO::FETCH_ASSOC);
            
            // Si aún no hay periodo, tomar el último creado
            if (!$periodo_actual) {
                $stmtPeriodo = $db->prepare("
                    SELECT 
                        id, 
                        nombre, 
                        fecha_inicio, 
                        fecha_fin,
                        estado_periodo,
                        numero_periodo
                    FROM periodos_academicos 
                    ORDER BY id DESC 
                    LIMIT 1
                ");
                $stmtPeriodo->execute();
                $periodo_actual = $stmtPeriodo->fetch(PDO::FETCH_ASSOC);
            }
        }
          // Actualizar la variable global de periodo_id
        if ($periodo_actual) {
            $periodo_id = $periodo_actual['id'];
            // Guardar el periodo_id en la sesión para uso futuro
            $_SESSION['ultimo_periodo_id'] = $periodo_id;
        }

        // Cargar los datos reales a través del controlador
        require_once __DIR__ . '/../../controllers/calificaciones/CalificacionesController.php';
        
        // Inicializar el controlador
        $controller = new CalificacionesController($db);
          try {
            // Debugging para ver los parámetros
            error_log("DEBUG - cargarDatosCalificaciones Params: " . 
                "profesor_id=" . ($_SESSION['profesor_id'] ?? 0) . 
                ", grado_id=" . $grado_id . 
                ", materia_id=" . $materia_id . 
                ", es_multigrado=" . ($es_multigrado ? "true" : "false") . 
                ", nivel=" . $nivel . 
                ", sede_id=" . $sede_id . 
                ", periodo_id=" . $periodo_id);
            
            // Obtener datos desde el controlador
            $datosCalificaciones = $controller->cargarDatosCalificaciones(
                $_SESSION['profesor_id'] ?? 0,
                $grado_id,
                $materia_id,
                $es_multigrado,
                $nivel,
                $sede_id,
                $periodo_id // Pasar el periodo_id al controlador
            );
            
            // Debugging para ver resultados
            error_log("DEBUG - Resultados: " . 
                "estudiantes=" . (isset($datosCalificaciones['estudiantes']) ? count($datosCalificaciones['estudiantes']) : "no hay") .
                ", tipos_notas=" . (isset($datosCalificaciones['tipos_notas']) ? json_encode(array_keys($datosCalificaciones['tipos_notas'])) : "no hay"));
            
            // Asignar datos a las variables que usa la vista
            $grado = $datosCalificaciones['grado'];
            $estudiantes = $datosCalificaciones['estudiantes'];
            $tipos_notas = $datosCalificaciones['tipos_notas'];
            $estadisticas = $datosCalificaciones['estadisticas'];
            
        } catch (Exception $e) {
            $error = "Error al cargar datos: " . $e->getMessage();
        }
    } else {
        // No hay conexión a la base de datos, usar datos de ejemplo
        $grado = [
            'asignacion_id' => 1,
            'materia_id' => $materia_id,
            'grado_id' => $grado_id,
            'grado_nombre' => 'Grado de Prueba',
            'materia_nombre' => 'Materia de Prueba'
        ];
        
        $estudiantes = $estudiantes_ejemplo;
        $tipos_notas = $tipos_notas_ejemplo;
    }
} catch (Exception $e) {
    // Si algo falla, usamos los datos de ejemplo
    $error = $e->getMessage();
    $grado = [
        'asignacion_id' => 1,
        'materia_id' => $materia_id,
        'grado_id' => $grado_id,
        'grado_nombre' => 'Grado de Prueba',
        'materia_nombre' => 'Materia de Prueba'
    ];
    
    $estudiantes = $estudiantes_ejemplo;
    $tipos_notas = $tipos_notas_ejemplo;
}

// Calcular estadísticas
$estadisticas = CalificacionesHelperSimple::calcularEstadisticas($estudiantes, $tipos_notas);

// Variables para la vista
$esMultigrado = $es_multigrado;
$pageTitle = isset($grado['materia_nombre']) ? 'Calificaciones | ' . $grado['materia_nombre'] : 'Calificaciones';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../../assets/css/profesor/style.css">
    <link rel="stylesheet" href="../../../assets/css/profesor/ver_estudiantes.css">
    <!-- Eliminar esta línea que está causando errores -->
    <!-- <link rel="stylesheet" href="../../../assets/css/profesor/asistencia.css"> -->
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        
        <main class="main-content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <h3>Error</h3>
                        <p><?php echo htmlspecialchars($error); ?></p>
                        <a href="lista_calificaciones.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="calificaciones-container">
                <!-- Header -->
                <header class="page-header">
                    <div class="header-content">
                        <div class="header-title">
                            <h1><?php echo htmlspecialchars($grado['materia_nombre'] ?? 'Materia'); ?></h1>
                            <div class="header-subtitle">
                                <span class="badge badge-primary">
                                    <i class="fas fa-graduation-cap"></i>
                                    <?php echo htmlspecialchars($grado['grado_nombre'] ?? 'Grado'); ?>
                                </span>
                                <?php if ($esMultigrado): ?>
                                    <span class="badge badge-info">
                                        <i class="fas fa-layer-group"></i>
                                        Multigrado - <?php echo htmlspecialchars($nivel); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($periodo_actual): ?>
                                <span class="badge <?php echo $periodo_actual['estado_periodo'] === 'en_curso' ? 'badge-success' : 'badge-secondary'; ?>">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo htmlspecialchars($periodo_actual['nombre']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="header-actions">
                            <button class="btn btn-primary" id="btnGestionarTiposNotas">
                                <i class="fas fa-cog"></i> Gestionar Tipos de Notas
                            </button>
                            <a href="lista_calificaciones.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            <button class="btn btn-secondary" id="btnExportar">
                                <i class="fas fa-file-export"></i> Exportar / Importar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="stats-summary">
                        <div class="stat-card stat-estudiantes">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo $estadisticas['total_estudiantes']; ?></span>
                                <span class="stat-label">Estudiantes</span>
                            </div>
                        </div>
                        
                        <div class="stat-card stat-promedio">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo number_format($estadisticas['promedio_general'], 1); ?></span>
                                <span class="stat-label">Promedio</span>
                            </div>
                        </div>
                        
                        <div class="stat-card stat-aprobados">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-details">
                            <span class="stat-value"><?php echo $estadisticas['estudiantes_aprobados']; ?></span>
                                <span class="stat-label">Aprobados</span>
                            </div>
                        </div>
                        
                        <div class="stat-card stat-reprobados">
                            <div class="stat-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-details">
                            <span class="stat-value"><?php echo $estadisticas['estudiantes_reprobados']; ?></span>
                                <span class="stat-label">Reprobados</span>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Pestañas para cambiar entre tipos de vista -->
                <div class="tabbed-interface">
                    <div class="tab-nav">
                        <button class="tab-btn active" data-view="table">
                            <i class="fas fa-table"></i> Vista de Tabla
                        </button>
                        <button class="tab-btn" data-view="cards">
                            <i class="fas fa-th"></i> Vista de Tarjetas
                        </button>
                        <button class="tab-btn" data-view="asistencia">
                            <i class="fas fa-clipboard-check"></i> Control de Asistencia
                        </button>
                        <button class="tab-btn" data-view="fotos">
                            <i class="fas fa-camera"></i> Gestionar Fotos
                        </button>
                    </div>
                    
                    <!-- Vista de tabla (activa por defecto) -->
                    <div class="tab-content active" id="tableView">
                        <?php include __DIR__ . '/components/tab_table_view.php'; ?>
                    </div>
                    
                    <!-- Vista de tarjetas -->
                    <div class="tab-content" id="cardsView">
                        <?php include __DIR__ . '/components/tab_cards_view.php'; ?>
                    </div>
                    
                    <!-- Vista de asistencia -->
                    <div class="tab-content" id="asistenciaView">
                        <?php include __DIR__ . '/components/tab_asistencia_view.php'; ?>
                    </div>
                    
                    <!-- Vista de gestión de fotos -->
                    <div class="tab-content" id="fotosView">
                        <?php include __DIR__ . '/components/tab_fotos_view.php'; ?>
                    </div>
                </div>
            </div>

            <!-- Toast de notificaciones -->
            <div class="toast" id="toast">
                <div class="toast-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">Éxito</div>
                    <div id="toastMessage">Operación realizada correctamente</div>
                </div>
            </div>
            
            <!-- Overlay de carga -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner"></div>
            </div>
            
            <!-- Incluir modales necesarios -->
            <?php include __DIR__ . '/components/modals/modal_tipos_notas.php'; ?>
            <?php include __DIR__ . '/components/modals/modal_agregar_tipo.php'; ?>
            <?php include __DIR__ . '/components/modals/modal_confirmar_eliminar.php'; ?>
        </main>
    </div>
    
    <!-- Scripts mejorados -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Referencias a elementos DOM
            const tabButtons = document.querySelectorAll('.tab-btn');
            const toast = document.getElementById('toast');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const btnGestionarTiposNotas = document.getElementById('btnGestionarTiposNotas');
            const modalTiposNotas = document.getElementById('modalTiposNotas');
            const closeBtns = document.querySelectorAll('.close, .cerrar-modal');
            const btnFloatingSave = document.getElementById('btnFloatingSave');
            
            // Función global para cambiar entre pestañas
            window.cambiarPestana = function(viewType) {
                // Obtener la vista a mostrar si no se especificó
                viewType = viewType || 'table';
                
                // Actualizar botones
                tabButtons.forEach(btn => btn.classList.remove('active'));
                const activeTab = document.querySelector(`.tab-btn[data-view="${viewType}"]`);
                if (activeTab) {
                    activeTab.classList.add('active');
                }
                
                // Actualizar contenido
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                    if (content.id === viewType + 'View') {
                        content.classList.add('active');
                    }
                });
                
                // Guardar en sessionStorage la pestaña activa
                sessionStorage.setItem('activeTab', viewType);
            };
            
            // Inicializar cambio de pestañas
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Obtener la vista a mostrar
                    const viewType = this.dataset.view;
                    window.cambiarPestana(viewType);
                });
            });
            
            // Recuperar la pestaña activa guardada
            const savedTab = sessionStorage.getItem('activeTab');
            // También verificar el parámetro 'tab' en la URL
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam) {
                // Si hay un parámetro tab en la URL, usarlo y guardarlo en sessionStorage
                window.cambiarPestana(tabParam);
                // Eliminar el parámetro tab de la URL para evitar problemas en futuras navegaciones
                urlParams.delete('tab');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState(null, '', newUrl);
            } else if (savedTab) {
                // De lo contrario usar el almacenado en sessionStorage
                window.cambiarPestana(savedTab);
            }
            
            // Mostrar/ocultar modal de tipos de notas
            btnGestionarTiposNotas?.addEventListener('click', function() {
                modalTiposNotas.style.display = 'flex';
            });
            
            // Cerrar modales
            closeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                });
            });
            
            // Cerrar modales al hacer clic fuera
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.style.display = 'none';
                }
            });
            
            // Botón flotante para guardar
            btnFloatingSave?.addEventListener('click', function() {
                saveGrades();
            });
            
            // Funciones para mostrar toast y loading
            window.showToast = function(mensaje, esError = false) {
                if (!toast) return;
                
                const toastMessage = document.getElementById('toastMessage');
                const toastIcon = toast.querySelector('.toast-icon i');
                
                if (toastMessage) {
                    toastMessage.textContent = mensaje;
                }
                
                if (esError) {
                    toast.classList.add('toast-error');
                    if (toastIcon) {
                        toastIcon.className = 'fas fa-exclamation-circle';
                    }
                    toast.querySelector('.toast-title').textContent = 'Error';
                } else {
                    toast.classList.remove('toast-error');
                    if (toastIcon) {
                        toastIcon.className = 'fas fa-check-circle';
                    }
                    toast.querySelector('.toast-title').textContent = 'Éxito';
                }
                
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            };
            
            window.showLoading = function(show) {
                if (loadingOverlay) {
                    loadingOverlay.style.display = show ? 'flex' : 'none';
                }
            };
            
            // Sincronización de valores entre vistas
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('grade-input') || e.target.classList.contains('nota-input')) {
                    const estudianteId = e.target.dataset.estudianteId;
                    const tipoNotaId = e.target.dataset.tipoNotaId;
                    const valor = e.target.value;
                    
                    // Actualizar el otro input correspondiente en la otra vista
                    document.querySelectorAll(`input[data-estudiante-id="${estudianteId}"][data-tipo-nota-id="${tipoNotaId}"]`).forEach(input => {
                        if (input !== e.target) {
                            input.value = valor;
                        }
                    });
                }
            });
            
            // Función para guardar calificaciones
            function saveGrades() {
                // Recopilar todas las calificaciones de ambas vistas
                const inputs = document.querySelectorAll('.grade-input, .nota-input');
                const notasParaGuardar = [];
                
                inputs.forEach(input => {
                    if (input.value) {
                        const estudianteId = input.dataset.estudianteId;
                        const tipoNotaId = input.dataset.tipoNotaId;
                        
                        // Evitar duplicados
                        const yaExiste = notasParaGuardar.some(nota => 
                            nota.estudiante_id === estudianteId && nota.tipo_nota_id === tipoNotaId
                        );
                        
                        if (!yaExiste) {
                            notasParaGuardar.push({
                                estudiante_id: estudianteId,
                                tipo_nota_id: tipoNotaId,
                                valor: parseFloat(input.value)
                            });
                        }
                    }
                });
                
                if (notasParaGuardar.length === 0) {
                    showToast('No hay calificaciones para guardar', true);
                    return;
                }
                
                // Mostrar animación de carga
                showLoading(true);
                
                // Preparar datos para el envío
                const asignacionId = document.getElementById('asignacion_id')?.value || '0';
                // Obtener el periodo_id de la URL
                const urlParams = new URLSearchParams(window.location.search);
                const periodoId = urlParams.get('periodo_id') || '<?php echo $periodo_id; ?>';
                
                // Realizar una petición AJAX para guardar las calificaciones
                fetch('../../api/calificaciones/guardar_notas_multiple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        notas: notasParaGuardar,
                        asignacion_id: asignacionId,
                        periodo_id: periodoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        showToast(data.message || 'Calificaciones guardadas correctamente');
                    } else {
                        showToast(data.message || 'Error al guardar las calificaciones', true);
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error:', error);
                    showToast('Error de conexión', true);
                });
            }
        });
    </script>
    
    <!-- Script para gestionar tipos de notas -->
    <script src="../../../assets/js/profesor/tipos_notas.js"></script>
    <!-- <script src="../../../assets/js/profesor/asistencia.js"></script> -->
    
    <!-- Script para el control de asistencia -->
    <script src="../../../assets/js/asistencia/asistencia_controller.js"></script>
</body>
</html>