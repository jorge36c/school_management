<?php
/**
 * =====================================================================================
 * SISTEMA DE GESTIÓN DE CALIFICACIONES - LISTA DE GRUPOS
 * =====================================================================================
 * 
 * Lista principal de grupos asignados al profesor con funcionalidades de:
 * - Visualización de grupos por períodos académicos
 * - Estadísticas de calificación por grupo
 * - Filtros por nivel y grado (actualizado)
 * - Búsqueda avanzada
 * - Soporte para enseñanza unigrado y multigrado
 * - Tres vistas funcionales: Tarjetas, Compacta y Lista
 * 
 * @author Tu Nombre
 * @version 3.0
 * @since 2024
 * 
 * =====================================================================================
 */

// =====================================================================================
// 1. CONFIGURACIÓN INICIAL Y SEGURIDAD
// =====================================================================================

// Configuración de errores para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicializar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['profesor_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Configurar timezone
date_default_timezone_set('America/Bogota');

// =====================================================================================
// 2. DEPENDENCIAS Y CONFIGURACIÓN
// =====================================================================================

// Base URL para rutas del profesor
$profesor_base_url = '/school_management/profesor';

// Conexión a base de datos
require_once $_SERVER['DOCUMENT_ROOT'] . '/school_management/config/database.php';

// =====================================================================================
// 3. CONSTANTES DEL SISTEMA
// =====================================================================================

define('PORCENTAJE_BAJO', 30);
define('PORCENTAJE_MEDIO', 60);
define('TIPO_UNIGRADO', 'unigrado');
define('TIPO_MULTIGRADO', 'multigrado');

// Estados de período
define('PERIODO_EN_CURSO', 'en_curso');
define('PERIODO_FINALIZADO', 'finalizado');
define('PERIODO_PROXIMO', 'proximo');

// Estados de estudiante
define('ESTUDIANTE_ACTIVO', 'Activo');
define('ESTUDIANTE_INACTIVO', 'Inactivo');

// =====================================================================================
// 4. FUNCIONES AUXILIARES
// =====================================================================================

/**
 * Calcula la definitiva de un estudiante basada en sus notas y tipos de notas
 */
function calcularDefinitiva($notas_estudiante, $tipos_notas) {
    $definitiva = 0;
    $porcentaje_total = 0;
    
    foreach ($tipos_notas as $tipo) {
        $valor = isset($notas_estudiante[$tipo['id']]) ? 
                floatval($notas_estudiante[$tipo['id']]) : 0;
        $porcentaje = floatval($tipo['porcentaje']);
        $porcentaje_total += $porcentaje;
        
        $contribucion = ($valor * $porcentaje) / 100;
        $definitiva += $contribucion;
    }
    
    if ($porcentaje_total > 0) {
        return number_format(($definitiva * 100) / $porcentaje_total, 1);
    }
    
    return '0.0';
}

/**
 * Obtiene el color asociado a un nivel educativo
 */
function getNivelColor($nivel) {
    $colors = [
        'preescolar' => '#06d6a0',
        'primaria'   => '#ef476f',
        'secundaria' => '#118ab2',
        'media'      => '#ffd166'
    ];
    
    return $colors[strtolower($nivel)] ?? '#3498db';
}

/**
 * Obtiene el icono FontAwesome asociado a un nivel educativo
 */
function getNivelIcon($nivel) {
    $icons = [
        'preescolar' => 'fas fa-baby',
        'primaria'   => 'fas fa-child',
        'secundaria' => 'fas fa-user-graduate',
        'media'      => 'fas fa-user-tie'
    ];
    
    return $icons[strtolower($nivel)] ?? 'fas fa-user-graduate';
}

/**
 * Determina la clase CSS para la barra de progreso según el porcentaje
 */
function getProgressClass($porcentaje) {
    if ($porcentaje > PORCENTAJE_MEDIO) {
        return 'progress-high';
    } elseif ($porcentaje > PORCENTAJE_BAJO) {
        return 'progress-medium';
    } else {
        return 'progress-low';
    }
}

/**
 * Extrae el número de grado del nombre del grado
 */
function extraerNumeroGrado($nombre_grado) {
    if (preg_match('/(\d+)/', $nombre_grado, $matches)) {
        return $matches[1];
    } elseif (stripos($nombre_grado, 'once') !== false) {
        return '11';
    } elseif (stripos($nombre_grado, 'décimo') !== false) {
        return '10';
    }
    return '';
}

/**
 * Obtiene el nombre completo del grado basado en el número
 */
function obtenerNombreGrado($numero) {
    $nombres = [
        '1' => 'Primero',
        '2' => 'Segundo', 
        '3' => 'Tercero',
        '4' => 'Cuarto',
        '5' => 'Quinto',
        '6' => 'Sexto',
        '7' => 'Séptimo',
        '8' => 'Octavo',
        '9' => 'Noveno',
        '10' => 'Décimo',
        '11' => 'Once'
    ];
    return $nombres[$numero] ?? "Grado $numero";
}

/**
 * Sanitiza y valida un ID entero - VERSIÓN MEJORADA
 */
function validarId($id) {
    // Convertir a string para manejar casos edge
    $id = trim((string)$id);
    
    // Verificar que no esté vacío
    if (empty($id)) {
        return null;
    }
    
    // Validar que sea numérico
    if (!is_numeric($id)) {
        return null;
    }
    
    // Convertir a entero
    $id = (int)$id;
    
    // Verificar que sea positivo
    return ($id > 0) ? $id : null;
}

/**
 * Registra errores en el log del sistema
 */
function registrarError($mensaje, $excepcion = null) {
    $log_message = "[" . date('Y-m-d H:i:s') . "] " . $mensaje;
    if ($excepcion) {
        $log_message .= " - " . $excepcion->getMessage() . " en " . $excepcion->getFile() . ":" . $excepcion->getLine();
    }
    error_log($log_message);
}

// =====================================================================================
// 5. CLASE PRINCIPAL - GESTOR DE CALIFICACIONES
// =====================================================================================

class CalificacionesListManager {
    private $pdo;
    private $profesor_id;
    private $datos = [];
    private $errores = [];
    
    public function __construct($pdo, $profesor_id) {
        $this->pdo = $pdo;
        $this->profesor_id = $profesor_id;
        $this->inicializarDatos();
    }
    
    /**
     * Inicializa la estructura de datos
     */
    private function inicializarDatos() {
        $this->datos = [
            'profesor' => null,
            'ano_activo' => null,
            'periodos' => [],
            'periodo_activo' => null,
            'grupos' => [],
            'estadisticas' => [
                'total_estudiantes' => 0,
                'estudiantes_unicos' => [],
                'nivel_counts' => [],
                'grado_counts' => [],
                'sedes' => []
            ]
        ];
    }
    
    /**
     * Procesa toda la información necesaria para la vista
     */
    public function procesarDatos() {
        try {
            $this->obtenerInformacionProfesor();
            $this->obtenerAnoLectivoActivo();
            $this->obtenerPeriodosAcademicos();
            $this->determinarPeriodoActivo();
            $this->obtenerGruposAsignados();
            $this->calcularEstadisticas();
            
            return empty($this->errores);
            
        } catch (Exception $e) {
            registrarError("Error al procesar datos de calificaciones", $e);
            $this->errores[] = "Error al procesar los datos: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Obtiene información del profesor logueado
     */
    private function obtenerInformacionProfesor() {
        $stmt = $this->pdo->prepare("
            SELECT id, nombre, apellido, email 
            FROM profesores 
            WHERE id = ? AND estado = 'activo'
        ");
        $stmt->execute([$this->profesor_id]);
        $profesor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profesor) {
            throw new Exception("No se encontró información del profesor o el profesor está inactivo");
        }
        
        $_SESSION['profesor_nombre'] = $profesor['nombre'];
        $_SESSION['profesor_apellido'] = $profesor['apellido'];
        
        $this->datos['profesor'] = $profesor;
    }
    
    /**
     * Obtiene el año lectivo activo
     */
    private function obtenerAnoLectivoActivo() {
        $stmt = $this->pdo->prepare("
            SELECT id, nombre, fecha_inicio, fecha_fin
            FROM anos_lectivos
            WHERE estado = 'activo'
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $ano_activo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ano_activo) {
            throw new Exception("No hay un año lectivo activo configurado");
        }
        
        $this->datos['ano_activo'] = $ano_activo;
    }
    
    /**
     * Obtiene todos los períodos académicos del año activo - VERSIÓN MEJORADA
     */
    private function obtenerPeriodosAcademicos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, 
                    nombre, 
                    numero_periodo,
                    fecha_inicio, 
                    fecha_fin,
                    estado_periodo,
                    porcentaje_calificacion
                FROM periodos_academicos 
                WHERE ano_lectivo_id = ? AND estado = 'activo'
                ORDER BY numero_periodo ASC
            ");
            $stmt->execute([$this->datos['ano_activo']['id']]);
            $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Validar que tenemos períodos
            if (empty($periodos)) {
                $this->errores[] = "No hay períodos académicos configurados para el año lectivo activo";
            } else {
                // Validar integridad de los datos de períodos
                foreach ($periodos as $key => $periodo) {
                    if (empty($periodo['id']) || empty($periodo['nombre'])) {
                        unset($periodos[$key]);
                        continue;
                    }
                    
                    // Asegurar que las fechas sean válidas
                    if (!strtotime($periodo['fecha_inicio']) || !strtotime($periodo['fecha_fin'])) {
                        $periodos[$key]['fecha_inicio'] = date('Y-m-d');
                        $periodos[$key]['fecha_fin'] = date('Y-m-d', strtotime('+3 months'));
                    }
                }
            }
            
            $this->datos['periodos'] = array_values($periodos); // Reindexar array
            
        } catch (PDOException $e) {
            registrarError("Error al obtener períodos académicos", $e);
            $this->errores[] = "Error al cargar los períodos académicos";
            $this->datos['periodos'] = [];
        }
    }
    
    /**
     * Determina el período activo basado en parámetros URL o estado - VERSIÓN CORREGIDA
     */
    private function determinarPeriodoActivo() {
        $periodo_activo = null;
        
        // 1. Verificar si hay un período especificado en la URL
        if (isset($_GET['periodo_id']) && !empty($_GET['periodo_id'])) {
            $periodo_id = validarId($_GET['periodo_id']);
            if ($periodo_id) {
                foreach ($this->datos['periodos'] as $periodo) {
                    if ($periodo['id'] == $periodo_id) {
                        $periodo_activo = $periodo;
                        // Guardar en sesión para recordar la selección
                        $_SESSION['periodo_seleccionado'] = $periodo_id;
                        break;
                    }
                }
            }
        }
        
        // 2. Si no se encontró por URL, verificar sesión
        if (!$periodo_activo && isset($_SESSION['periodo_seleccionado'])) {
            $periodo_id = validarId($_SESSION['periodo_seleccionado']);
            if ($periodo_id) {
                foreach ($this->datos['periodos'] as $periodo) {
                    if ($periodo['id'] == $periodo_id) {
                        $periodo_activo = $periodo;
                        break;
                    }
                }
            }
        }
        
        // 3. Si no hay período en sesión, buscar el período en curso
        if (!$periodo_activo) {
            foreach ($this->datos['periodos'] as $periodo) {
                if ($periodo['estado_periodo'] === PERIODO_EN_CURSO) {
                    $periodo_activo = $periodo;
                    break;
                }
            }
        }
        
        // 4. Si no hay período en curso, tomar el más reciente
        if (!$periodo_activo && !empty($this->datos['periodos'])) {
            // Los períodos ya están ordenados por numero_periodo ASC, tomar el último
            $periodo_activo = end($this->datos['periodos']);
        }
        
        // 5. Validar que el período encontrado sea válido
        if ($periodo_activo) {
            // Verificar que el período tenga datos válidos
            if (empty($periodo_activo['id']) || empty($periodo_activo['nombre'])) {
                $periodo_activo = null;
            }
        }
        
        $this->datos['periodo_activo'] = $periodo_activo;
        
        // Log para debug
        if ($periodo_activo) {
            error_log("Período activo determinado: " . $periodo_activo['nombre'] . " (ID: " . $periodo_activo['id'] . ")");
        } else {
            error_log("No se pudo determinar un período activo");
        }
    }
    
    /**
     * Obtiene todos los grupos asignados al profesor
     */
    private function obtenerGruposAsignados() {
        $stmt = $this->pdo->prepare("
            SELECT 
                ap.id as asignacion_id,
                g.id as grado_id,
                g.nombre as grado_nombre,
                g.nivel,
                s.nombre as sede_nombre,
                s.id as sede_id,
                m.nombre as materia_nombre,
                m.id as materia_id,
                (
                    SELECT COUNT(*)
                    FROM estudiantes e
                    WHERE e.grado_id = g.id
                    AND e.estado = ?
                ) as total_estudiantes,
                (
                    SELECT COUNT(DISTINCT c.estudiante_id)
                    FROM calificaciones c
                    INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                    WHERE tn.asignacion_id = ap.id
                    AND c.estado = 'activo'
                ) as estudiantes_calificados
            FROM asignaciones_profesor ap
            INNER JOIN grados g ON ap.grado_id = g.id
            INNER JOIN sedes s ON g.sede_id = s.id
            INNER JOIN materias m ON ap.materia_id = m.id
            WHERE ap.profesor_id = ? 
            AND ap.estado = 'activo'
            AND g.estado = 'activo'
            ORDER BY g.nivel, g.nombre, m.nombre
        ");
        $stmt->execute([ESTUDIANTE_ACTIVO, $this->profesor_id]);
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->procesarAsignaciones($asignaciones);
    }
    
    /**
     * Procesa las asignaciones para organizarlas según tipo de enseñanza
     */
    private function procesarAsignaciones($asignaciones) {
        $tipos_ensenanza = $this->obtenerTiposEnsenanza($asignaciones);
        
        $grupos = [];
        $grupos_multigrado = [];
        
        foreach ($asignaciones as $asignacion) {
            $sede_id = $asignacion['sede_id'];
            $nivel = $asignacion['nivel'];
            $tipo_ensenanza = $tipos_ensenanza[$sede_id][$nivel] ?? TIPO_UNIGRADO;
            
            $this->actualizarEstudiantesUnicos($asignacion['grado_id']);
            
            // Extraer número de grado para estadísticas
            $grado_numero = extraerNumeroGrado($asignacion['grado_nombre']);
            if ($grado_numero) {
                $this->datos['estadisticas']['grado_counts'][$grado_numero] = 
                    ($this->datos['estadisticas']['grado_counts'][$grado_numero] ?? 0) + 1;
            }
            
            if ($tipo_ensenanza === TIPO_MULTIGRADO) {
                $this->procesarGrupoMultigrado($asignacion, $grupos_multigrado);
            } else {
                $this->procesarGrupoUnigrado($asignacion, $grupos);
            }
        }
        
        $this->datos['grupos'] = array_merge($grupos, array_values($grupos_multigrado));
    }
    
    /**
     * Obtiene configuraciones de tipo de enseñanza por sede y nivel
     */
    private function obtenerTiposEnsenanza($asignaciones) {
        $sedes_niveles = [];
        $tipos_ensenanza = [];
        
        foreach ($asignaciones as $asignacion) {
            $sede_id = $asignacion['sede_id'];
            $nivel = $asignacion['nivel'];
            $sedes_niveles[$sede_id][$nivel] = true;
        }
        
        foreach ($sedes_niveles as $sede_id => $niveles) {
            foreach (array_keys($niveles) as $nivel) {
                $tipo = $this->obtenerTipoEnsenanzaNivel($sede_id, $nivel);
                $tipos_ensenanza[$sede_id][$nivel] = $tipo;
            }
        }
        
        return $tipos_ensenanza;
    }
    
    /**
     * Obtiene el tipo de enseñanza para una sede y nivel específicos
     */
    private function obtenerTipoEnsenanzaNivel($sede_id, $nivel) {
        $stmt = $this->pdo->prepare("
            SELECT tipo_ensenanza 
            FROM niveles_configuracion 
            WHERE sede_id = ? AND nivel = ?
        ");
        $stmt->execute([$sede_id, $nivel]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            return $config['tipo_ensenanza'];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT tipo_ensenanza 
            FROM sedes 
            WHERE id = ?
        ");
        $stmt->execute([$sede_id]);
        $sede_config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $sede_config['tipo_ensenanza'] ?? TIPO_UNIGRADO;
    }
    
    /**
     * Actualiza el conteo de estudiantes únicos
     */
    private function actualizarEstudiantesUnicos($grado_id) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM estudiantes 
            WHERE grado_id = ? AND estado = ?
        ");
        $stmt->execute([$grado_id, ESTUDIANTE_ACTIVO]);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($estudiantes as $estudiante_id) {
            $this->datos['estadisticas']['estudiantes_unicos'][$estudiante_id] = true;
        }
    }
    
    /**
     * Procesa un grupo multigrado
     */
    private function procesarGrupoMultigrado($asignacion, &$grupos_multigrado) {
        $sede_id = $asignacion['sede_id'];
        $nivel = $asignacion['nivel'];
        $materia_id = $asignacion['materia_id'];
        $key = $sede_id . '_' . $nivel . '_' . $materia_id;
        
        if (!isset($grupos_multigrado[$key])) {
            $grupos_multigrado[$key] = [
                'es_multigrado' => true,
                'sede_id' => $sede_id,
                'sede_nombre' => $asignacion['sede_nombre'],
                'nivel' => $nivel,
                'grado_nombre' => 'Grupo Multigrado - ' . ucfirst($nivel),
                'grado_numero' => '', // Los multigrado no tienen número específico
                'materia_id' => $materia_id,
                'materia_nombre' => $asignacion['materia_nombre'],
                'asignaciones' => [],
                'total_estudiantes' => 0,
                'estudiantes_calificados' => 0,
                'grados' => []
            ];
        }
        
        $grupos_multigrado[$key]['asignaciones'][] = $asignacion;
        $grupos_multigrado[$key]['total_estudiantes'] += $asignacion['total_estudiantes'];
        $grupos_multigrado[$key]['estudiantes_calificados'] += $asignacion['estudiantes_calificados'];
        $grupos_multigrado[$key]['grados'][$asignacion['grado_id']] = $asignacion['grado_nombre'];
        
        $this->calcularPorcentajeCalificacion($grupos_multigrado[$key]);
    }
    
    /**
     * Procesa un grupo unigrado
     */
    private function procesarGrupoUnigrado($asignacion, &$grupos) {
        // Agregar número de grado extraído
        $asignacion['grado_numero'] = extraerNumeroGrado($asignacion['grado_nombre']);
        $this->calcularPorcentajeCalificacion($asignacion);
        $grupos[] = $asignacion;
    }
    
    /**
     * Calcula el porcentaje de calificación para un grupo
     */
    private function calcularPorcentajeCalificacion(&$grupo) {
        if ($grupo['total_estudiantes'] > 0) {
            $grupo['porcentaje_calificado'] = round(
                ($grupo['estudiantes_calificados'] / $grupo['total_estudiantes']) * 100
            );
        } else {
            $grupo['porcentaje_calificado'] = 0;
        }
    }
    
    /**
     * Calcula estadísticas generales
     */
    private function calcularEstadisticas() {
        foreach ($this->datos['grupos'] as $grupo) {
            $nivel = $grupo['nivel'];
            $this->datos['estadisticas']['nivel_counts'][$nivel] = 
                ($this->datos['estadisticas']['nivel_counts'][$nivel] ?? 0) + 1;
            
            $this->datos['estadisticas']['sedes'][$grupo['sede_nombre']] = $grupo['sede_id'];
        }
        
        $this->datos['estadisticas']['total_estudiantes'] = 
            count($this->datos['estadisticas']['estudiantes_unicos']);
    }
    
    /**
     * Obtiene todos los datos procesados
     */
    public function getDatos() {
        return $this->datos;
    }
    
    /**
     * Obtiene los errores ocurridos durante el procesamiento
     */
    public function getErrores() {
        return $this->errores;
    }
    
    /**
     * Verifica si hay errores
     */
    public function tieneErrores() {
        return !empty($this->errores);
    }
}

// =====================================================================================
// 6. PROCESAMIENTO PRINCIPAL
// =====================================================================================

$datos_vista = [];
$error_mensaje = null;

try {
    $gestor = new CalificacionesListManager($pdo, $_SESSION['profesor_id']);
    
    if ($gestor->procesarDatos()) {
        $datos_vista = $gestor->getDatos();
    } else {
        $errores = $gestor->getErrores();
        $error_mensaje = implode('; ', $errores);
    }
    
} catch (PDOException $e) {
    registrarError("Error de base de datos en lista_calificaciones.php", $e);
    $error_mensaje = "Error de conexión a la base de datos. Por favor, inténtelo nuevamente.";
    
} catch (Exception $e) {
    registrarError("Error general en lista_calificaciones.php", $e);
    $error_mensaje = "Error interno del sistema. " . $e->getMessage();
}

// Extraer variables para uso en la vista
extract($datos_vista);

// Definir título de la página
$page_title = 'Lista de Calificaciones';

// Preparar niveles para filtros
$niveles = !empty($estadisticas['nivel_counts']) ? 
           array_keys($estadisticas['nivel_counts']) : [];
sort($niveles);

// Preparar grados para filtros
$grados_disponibles = !empty($estadisticas['grado_counts']) ? 
                     array_keys($estadisticas['grado_counts']) : [];
sort($grados_disponibles, SORT_NUMERIC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de gestión de calificaciones escolares">
    <meta name="base-url" content="<?php echo htmlspecialchars($profesor_base_url); ?>">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(session_id()); ?>">
    <title>Calificaciones | Sistema Escolar</title>
    
    <!-- ===== FUENTES Y ESTILOS EXTERNOS ===== -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
          crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- ===== ESTILOS DEL SISTEMA ===== -->
    <link rel="stylesheet" href="../../assets/css/components/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/components/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/components/topbar.css">
    <link rel="stylesheet" href="../../assets/css/calificaciones/lista_calificaciones_modern.css">
    
    <!-- ===== ESTILOS ADICIONALES PARA EL DROPDOWN ===== -->
    <style>
    /* Estilos adicionales para el dropdown de períodos */
    .ml-2 {
        margin-left: 0.5rem;
    }

    .btn-primary {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        color: var(--white);
        text-decoration: none;
    }

    .fa-chevron-down {
        transition: transform 0.3s ease;
    }

    .fa-chevron-down.fa-rotate-180 {
        transform: rotate(180deg);
    }

    /* Mejorar el dropdown */
    .periodos-dropdown {
        scrollbar-width: thin;
        scrollbar-color: var(--primary) transparent;
    }

    .periodos-dropdown::-webkit-scrollbar {
        width: 6px;
    }

    .periodos-dropdown::-webkit-scrollbar-track {
        background: transparent;
    }

    .periodos-dropdown::-webkit-scrollbar-thumb {
        background-color: var(--primary);
        border-radius: 3px;
    }

    /* Estados de loading */
    .loading-period {
        opacity: 0.6;
        pointer-events: none;
    }

    .loading-period::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid var(--primary);
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    </style>
</head>
<body data-role="profesor" data-user-id="<?php echo htmlspecialchars($_SESSION['profesor_id']); ?>">
    <div class="admin-container">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/topbar.php'; ?>

            <div class="content-wrapper">
                <!-- ===== ENCABEZADO PRINCIPAL ===== -->
                <div class="page-header animate-fade-in">
                    <div class="header-content">
                        <div class="header-title">
                            <h1>
                                <i class="fas fa-star"></i> 
                                Gestión de Calificaciones
                            </h1>
                            <div class="header-subtitle">
                                <?php if (!empty($ano_activo)): ?>
                                <span class="badge">
                                    <i class="fas fa-calendar-year"></i> 
                                    <?php echo htmlspecialchars($ano_activo['nombre']); ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($estadisticas['sedes'])): ?>
                                <span class="badge">
                                    <i class="fas fa-school"></i> 
                                    <?php 
                                    $num_sedes = count($estadisticas['sedes']);
                                    echo $num_sedes . ' ' . ($num_sedes == 1 ? 'Sede' : 'Sedes'); 
                                    ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- ===== SELECTOR DE PERÍODOS MEJORADO ===== -->
                        <?php if (!empty($periodos)): ?>
                        <div class="periodo-selector">
                            <button id="togglePeriodos" class="btn btn-primary" type="button" aria-expanded="false" aria-haspopup="true">
                                <i class="fas fa-calendar-alt"></i> 
                                <span class="periodo-texto">
                                    <?php 
                                    echo !empty($periodo_activo['nombre']) ? 
                                         htmlspecialchars($periodo_activo['nombre']) : 
                                         'Seleccionar Período'; 
                                    ?>
                                </span>
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                            
                            <div id="periodosDropdown" class="periodos-dropdown" role="menu" aria-labelledby="togglePeriodos">
                                <?php foreach ($periodos as $periodo): 
                                    $is_active = !empty($periodo_activo['id']) && 
                                                 $periodo['id'] == $periodo_activo['id'];
                                    
                                    // Construir URL con período manteniendo otros parámetros
                                    $url_params = $_GET;
                                    $url_params['periodo_id'] = $periodo['id'];
                                    $url_periodo = '?' . http_build_query($url_params);
                                ?>
                                <a href="<?php echo htmlspecialchars($url_periodo); ?>" 
                                   class="periodo-item <?php echo $is_active ? 'active' : ''; ?>"
                                   role="menuitem"
                                   data-periodo-id="<?php echo $periodo['id']; ?>">
                                    <div class="periodo-info">
                                        <span class="periodo-name">
                                            <?php echo htmlspecialchars($periodo['nombre']); ?>
                                        </span>
                                        <span class="periodo-dates">
                                            <?php 
                                            $fecha_inicio = date('d/m/Y', strtotime($periodo['fecha_inicio']));
                                            $fecha_fin = date('d/m/Y', strtotime($periodo['fecha_fin']));
                                            echo "$fecha_inicio - $fecha_fin"; 
                                            ?>
                                        </span>
                                    </div>
                                    <?php if ($periodo['estado_periodo'] === PERIODO_EN_CURSO): ?>
                                        <span class="periodo-badge en-curso">En curso</span>
                                    <?php elseif ($periodo['estado_periodo'] === PERIODO_FINALIZADO): ?>
                                        <span class="periodo-badge finalizado">Finalizado</span>
                                    <?php else: ?>
                                        <span class="periodo-badge">Próximo</span>
                                    <?php endif; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($error_mensaje): ?>
                    <!-- ===== MENSAJE DE ERROR ===== -->
                    <div class="alert alert-error animate-fade-in">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="alert-content">
                            <h3>Error al cargar los datos</h3>
                            <p><?php echo htmlspecialchars($error_mensaje); ?></p>
                            <button class="btn btn-sm btn-secondary" onclick="location.reload();">
                                <i class="fas fa-sync-alt"></i> Reintentar
                            </button>
                        </div>
                    </div>
                    
                <?php elseif (empty($grupos)): ?>
                    <!-- ===== ESTADO VACÍO ===== -->
                    <div class="empty-state animate-fade-in">
                        <i class="fas fa-folder-open"></i>
                        <h3>No hay grupos asignados</h3>
                        <p>
                            Actualmente no tienes grupos asignados para calificar en este período. 
                            Contacta con el administrador del sistema si crees que es un error.
                        </p>
                        <button class="btn btn-primary" onclick="location.reload();">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                    
                <?php else: ?>
                    <!-- ===== ESTADÍSTICAS GENERALES ===== -->
                    <div class="stats-summary animate-fade-in delay-100">
                        <div class="stat-card stat-grupos" style="--stat-color: #4f46e5;">
                            <div class="stat-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo count($grupos); ?></span>
                                <span class="stat-label">Grupos asignados</span>
                            </div>
                        </div>
                        
                        <div class="stat-card stat-estudiantes" style="--stat-color: #10b981;">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value">
                                    <?php echo $estadisticas['total_estudiantes'] ?? 0; ?>
                                </span>
                                <span class="stat-label">Total estudiantes</span>
                            </div>
                        </div>
                        
                        <?php if (!empty($estadisticas['nivel_counts'])): ?>
                            <?php foreach ($estadisticas['nivel_counts'] as $nivel => $count): ?>
                            <div class="stat-card" style="--stat-color: <?php echo getNivelColor($nivel); ?>">
                                <div class="stat-icon">
                                    <i class="<?php echo getNivelIcon($nivel); ?>"></i>
                                </div>
                                <div class="stat-details">
                                    <span class="stat-value"><?php echo $count; ?></span>
                                    <span class="stat-label">
                                        Grupos de <?php echo ucfirst($nivel); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- ===== BARRA DE FILTROS ACTUALIZADA ===== -->
                    <div class="filter-bar animate-fade-in delay-200">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   id="searchGrupo" 
                                   placeholder="Buscar por grado o materia..." 
                                   aria-label="Buscar grupo">
                        </div>
                        
                        <div class="filter-group">
                            <select id="filterNivel" class="filter-select" aria-label="Filtrar por nivel">
                                <option value="">Todos los niveles</option>
                                <?php foreach ($niveles as $nivel): ?>
                                <option value="<?php echo htmlspecialchars($nivel); ?>">
                                    <?php echo htmlspecialchars(ucfirst($nivel)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- NUEVO FILTRO POR GRADO -->
                            <select id="filterGrado" class="filter-select" aria-label="Filtrar por grado">
                                <option value="">Todos los grados</option>
                                <?php foreach ($grados_disponibles as $grado_num): ?>
                                <option value="<?php echo htmlspecialchars($grado_num); ?>">
                                    <?php echo htmlspecialchars(obtenerNombreGrado($grado_num)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <div class="view-toggle">
                                <button id="gridViewBtn" 
                                        class="view-btn active" 
                                        data-view="cards"
                                        title="Vista tarjetas">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button id="compactViewBtn" 
                                        class="view-btn" 
                                        data-view="compact"
                                        title="Vista compacta">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button id="listViewBtn" 
                                        class="view-btn" 
                                        data-view="list"
                                        title="Vista lista">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== CONTENEDOR DE GRUPOS CON TRES VISTAS ===== -->
                    <div id="gruposContainer" class="grupos-grid view-cards animate-fade-in delay-300">
                        <?php foreach ($grupos as $index => $grupo):
                            $porcentaje = $grupo['porcentaje_calificado'] ?? 0;
                            $progress_class = getProgressClass($porcentaje);
                            $es_multigrado = isset($grupo['es_multigrado']) && $grupo['es_multigrado'];
                            $grupo_id = 'grupo-' . $index;
                            
                            // Obtener número de grado
                            $grado_numero = $es_multigrado ? '' : ($grupo['grado_numero'] ?? extraerNumeroGrado($grupo['grado_nombre']));
                            
                            // Construir URL de calificación
                            if ($es_multigrado) {
                                $href_calificar = 'ver_estudiantes.php?nivel=' . urlencode($grupo['nivel']) . 
                                                 '&sede_id=' . $grupo['sede_id'] . 
                                                 '&tipo=multigrado&materia_id=' . $grupo['materia_id'];
                            } else {
                                $href_calificar = 'ver_estudiantes.php?grado_id=' . $grupo['grado_id'] . 
                                                 '&materia_id=' . $grupo['materia_id'];
                            }
                            
                            // Agregar período si está disponible
                            if (!empty($periodo_activo['id'])) {
                                $href_calificar .= (strpos($href_calificar, '?') !== false ? '&' : '?') . 'periodo_id=' . $periodo_activo['id'];
                            }
                        ?>
                        <div class="grupo-card <?php echo $es_multigrado ? 'multigrado' : ''; ?>" 
                                id="<?php echo $grupo_id; ?>"
                                data-nivel="<?php echo htmlspecialchars($grupo['nivel']); ?>" 
                                data-grado="<?php echo htmlspecialchars($grado_numero); ?>"
                                data-materia="<?php echo htmlspecialchars($grupo['materia_nombre']); ?>"
                                data-total-estudiantes="<?php echo $grupo['total_estudiantes']; ?>"
                                data-estudiantes-calificados="<?php echo $grupo['estudiantes_calificados']; ?>"
                                data-porcentaje="<?php echo $porcentaje; ?>"
                                data-href="<?php echo htmlspecialchars($href_calificar); ?>"
                                tabindex="0"
                                role="button"
                                aria-labelledby="<?php echo $grupo_id; ?>-title">
                            
                            <!-- Contenido inicial de la tarjeta (será regenerado por JavaScript) -->
                            <div class="grupo-header">
                                <span class="nivel-badge" style="background-color: <?php echo getNivelColor($grupo['nivel']); ?>">
                                    <i class="<?php echo getNivelIcon($grupo['nivel']); ?>"></i>
                                    <?php echo htmlspecialchars(ucfirst($grupo['nivel'])); ?>
                                </span>
                                
                                <h3 id="<?php echo $grupo_id; ?>-title">
                                    <?php echo htmlspecialchars($grupo['grado_nombre']); ?>
                                </h3>
                                
                                <?php if ($es_multigrado): ?>
                                    <span class="tipo-badge">
                                        <i class="fas fa-chalkboard-teacher"></i> 
                                        Multigrado
                                    </span>
                                    
                                    <?php if (!empty($grupo['grados'])): ?>
                                    <div class="grados-list">
                                        <?php foreach ($grupo['grados'] as $grado_nombre): ?>
                                            <span class="grado-chip">
                                                <?php echo htmlspecialchars($grado_nombre); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <span class="materia">
                                    <i class="fas fa-book"></i>
                                    <?php echo htmlspecialchars($grupo['materia_nombre']); ?>
                                </span>
                            </div>
                            
                            <div class="grupo-content">
                                <div class="grupo-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-users"></i>
                                        <span class="stat-value">
                                            <?php echo $grupo['total_estudiantes']; ?>
                                        </span>
                                        <span class="stat-label">Estudiantes</span>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span class="stat-value">
                                            <?php echo $grupo['estudiantes_calificados']; ?>
                                        </span>
                                        <span class="stat-label">Calificados</span>
                                        <div class="progress-container">
                                            <div class="progress-bar <?php echo $progress_class; ?>" 
                                                    style="width: <?php echo $porcentaje; ?>%"
                                                    role="progressbar" 
                                                    aria-valuenow="<?php echo $porcentaje; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grupo-actions">
                                    <a href="<?php echo htmlspecialchars($href_calificar); ?>" 
                                       class="btn-action">
                                        <i class="fas fa-<?php echo $es_multigrado ? 'users' : 'list-check'; ?>"></i>
                                        Calificar Grupo
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                   
                    <!-- ===== ESTADO VACÍO PARA FILTROS ===== -->
                    <div id="emptyResults" class="empty-state" style="display: none;">
                        <i class="fas fa-search"></i>
                        <h3>No se encontraron grupos</h3>
                        <p>
                            No hay grupos que coincidan con los filtros seleccionados. 
                            Intenta con diferentes criterios de búsqueda.
                        </p>
                        <button id="clearFilters" class="clear-filters-btn">
                            <i class="fas fa-times"></i> Limpiar filtros
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== OVERLAY DE CARGA ===== -->
    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>
    
    <!-- ===== CONTENEDOR DE TOASTS ===== -->
    <div id="toastContainer" class="toast-container"></div>
    
    <!-- ===== ELEMENTO PARA DATOS DEL PERÍODO ACTIVO ===== -->
    <div class="current-period" 
         data-periodo-id="<?php echo !empty($periodo_activo['id']) ? $periodo_activo['id'] : ''; ?>" 
         style="display:none;">
    </div>
    
    <!-- ===== SCRIPTS PRINCIPALES ===== -->
    <script src="../../assets/js/components/sidebar.js"></script>
    <script src="../../assets/js/components/topbar.js"></script>
    <script src="../../assets/js/calificaciones/lista_calificaciones_simple.js"></script>
    
    <!-- ===== CONFIGURACIÓN E INICIALIZACIÓN MEJORADA ===== -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Marcar como habilitado JavaScript
        document.documentElement.classList.add('js-enabled');
        
        // Configuración global de la aplicación
        window.APP_CONFIG = {
            profesorId: <?php echo json_encode($_SESSION['profesor_id']); ?>,
            periodoActivo: <?php echo json_encode($periodo_activo['id'] ?? null); ?>,
            periodoNombre: <?php echo json_encode($periodo_activo['nombre'] ?? ''); ?>,
            baseUrl: <?php echo json_encode($profesor_base_url); ?>,
            csrfToken: <?php echo json_encode(session_id()); ?>,
            totalGrupos: <?php echo count($grupos); ?>,
            totalEstudiantes: <?php echo $estadisticas['total_estudiantes'] ?? 0; ?>
        };
        
        console.log('Sistema de calificaciones inicializado', window.APP_CONFIG);
        
        // Verificar elementos del selector de períodos
        const toggleBtn = document.getElementById('togglePeriodos');
        const dropdown = document.getElementById('periodosDropdown');
        
        if (toggleBtn && dropdown) {
            console.log('✅ Elementos del selector de períodos encontrados');
        } else {
            console.error('❌ Elementos del selector de períodos no encontrados:', {
                toggleBtn: !!toggleBtn,
                dropdown: !!dropdown
            });
        }
        
        // Mostrar mensaje de éxito si se cambió el período
        <?php if (isset($_GET['periodo_id'])): ?>
        setTimeout(() => {
            if (window.showToast) {
                window.showToast('Período cambiado correctamente', 'success');
            }
        }, 500);
        <?php endif; ?>
        
        // Mostrar mensaje de bienvenida si es primera visita
        const isFirstVisit = !sessionStorage.getItem('hasVisitedCalificaciones');
        if (isFirstVisit) {
            sessionStorage.setItem('hasVisitedCalificaciones', 'true');
            setTimeout(() => {
                if (window.showToast) {
                    window.showToast('¡Bienvenido! Usa Ctrl+1/2/3 para cambiar vistas.', 'info');
                }
            }, 1000);
        }
    });
    
    // Manejo de errores globales
    window.addEventListener('error', (e) => {
        console.error('Error en la aplicación:', e.error);
        if (window.showToast) {
            window.showToast('Ha ocurrido un error inesperado', 'error');
        }
    });
    
    // Polyfill para navegadores antiguos
    if (!('IntersectionObserver' in window)) {
        document.write('<script src="https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver"><\/script>');
    }
    </script>
</body>
</html>