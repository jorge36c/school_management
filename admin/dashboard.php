<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

// Inicializar variables
$total_estudiantes = 0;
$total_profesores = 0;
$total_sedes = 0;
$config = [];
$actividades = [];
$admin_name = $_SESSION['admin_name'] ?? 'Administrador';

try {
    $pdo->beginTransaction();

    // Obtener datos del administrador
    $stmt = $pdo->prepare("SELECT nombre FROM administradores WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    $_SESSION['admin_nombre'] = $admin['nombre'] ?? 'Administrador';

    // Estadísticas generales
    $stmt = $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'Activo'");
    $total_estudiantes = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM profesores WHERE estado = 'activo'");
    $total_profesores = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM sedes WHERE estado = 'activo'");
    $total_sedes = $stmt->fetchColumn();

    // Obtener año lectivo activo
    $stmt = $pdo->query("SELECT * FROM anos_lectivos WHERE estado = 'activo' ORDER BY id DESC LIMIT 1");
    $ano_lectivo = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['ano' => date('Y')];

    // Obtener periodo académico activo
    $stmt = $pdo->query("SELECT * FROM periodos_academicos WHERE estado_periodo = 'en_curso' ORDER BY id DESC LIMIT 1");
    $periodo_actual = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nombre' => 'No definido'];

    // Obtener distribución de estudiantes por nivel educativo
    $stmt = $pdo->query("SELECT nivel, COUNT(*) as total FROM estudiantes WHERE estado = 'Activo' GROUP BY nivel");
    $distribucion_nivel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir a formato para gráficos
    $niveles = [];
    $totales_nivel = [];
    $colores_nivel = [
        'preescolar' => '#4F46E5', // Indigo
        'primaria' => '#10B981',   // Emerald
        'secundaria' => '#F59E0B', // Amber
        'media' => '#EF4444'       // Red
    ];
    
    $colores_array = [];
    $bordes_array = [];
    
    foreach($distribucion_nivel as $nivel) {
        $niveles[] = ucfirst($nivel['nivel'] ?? 'No definido');
        $totales_nivel[] = intval($nivel['total']);
        $colores_array[] = $colores_nivel[$nivel['nivel'] ?? ''] ?? '#6B7280';
        $bordes_array[] = isset($colores_nivel[$nivel['nivel']]) ? darken_color($colores_nivel[$nivel['nivel']], 10) : '#4B5563';
    }

    // Obtener distribución de estudiantes por sede
    $stmt = $pdo->query("
        SELECT s.id, s.nombre, s.estado, 
               COUNT(e.id) as total_estudiantes,
               (SELECT COUNT(p.id) FROM profesores p WHERE p.sede_id = s.id AND p.estado = 'activo') as total_profesores
        FROM sedes s
        LEFT JOIN estudiantes e ON s.id = e.sede_id AND e.estado = 'Activo'
        GROUP BY s.id
        ORDER BY s.estado DESC, total_estudiantes DESC
    ");
    $distribucion_sede = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener distribución de estudiantes por grado
    $stmt = $pdo->query("
        SELECT g.nombre, COUNT(e.id) as total 
        FROM estudiantes e
        JOIN grados g ON e.grado_id = g.id
        WHERE e.estado = 'Activo'
        GROUP BY e.grado_id
        ORDER BY total DESC
        LIMIT 8
    ");
    $distribucion_grado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener promedio de calificaciones
    $stmt = $pdo->query("
        SELECT AVG(c.valor) as promedio 
        FROM calificaciones c
        JOIN tipos_notas tn ON c.tipo_nota_id = tn.id 
        WHERE c.estado = 'activo'
    ");
    $promedio_calificaciones = $stmt->fetchColumn() ?: 0;
    $promedio_calificaciones = number_format($promedio_calificaciones, 1);

    // Obtener estadísticas de progreso de notas por profesor
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.nombre, 
            p.apellido,
            COUNT(DISTINCT c.id) as total_calificaciones,
            COUNT(DISTINCT a.id) as total_asignaciones
        FROM profesores p
        LEFT JOIN asignaciones_profesor a ON p.id = a.profesor_id
        LEFT JOIN tipos_notas tn ON a.id = tn.asignacion_id
        LEFT JOIN calificaciones c ON tn.id = c.tipo_nota_id
        WHERE p.estado = 'activo'
        GROUP BY p.id
        ORDER BY total_calificaciones DESC
        LIMIT 5
    ");
    $progreso_profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Configuración del sistema
    $stmt = $pdo->query("SELECT * FROM configuracion LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'school_name' => 'Sistema Escolar',
        'address' => 'Dirección no configurada',
        'current_year' => date('Y')
    ];

    // Historial completo de actividades (con paginación)
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    // Contar total de registros para paginación
    $stmt = $pdo->query("SELECT COUNT(*) FROM actividad_log");
    $total_actividades = $stmt->fetchColumn();
    $total_pages = ceil($total_actividades / $per_page);
    
    // Obtener registros paginados
    $stmt = $pdo->query("SELECT al.*, u.nombre as usuario_nombre, 
                         CASE 
                            WHEN al.tabla = 'estudiantes' THEN 'Estudiantes'
                            WHEN al.tabla = 'profesores' THEN 'Profesores'
                            WHEN al.tabla = 'asignaturas' THEN 'Asignaturas'
                            WHEN al.tabla = 'materias' THEN 'Materias'
                            WHEN al.tabla = 'sedes' THEN 'Sedes'
                            WHEN al.tabla = 'grados' THEN 'Grados'
                            WHEN al.tabla = 'configuracion' THEN 'Configuración'
                            ELSE al.tabla
                         END as tabla_nombre
                         FROM actividad_log al
                         LEFT JOIN administradores u ON al.usuario_id = u.id
                         ORDER BY al.fecha DESC
                         LIMIT $per_page OFFSET $offset");
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay actividades reales, usar datos de ejemplo
    if (empty($actividades)) {
        $actividades = [
            ['descripcion' => 'Inicio de sesión del administrador', 'fecha' => date('Y-m-d H:i:s'), 'tabla_nombre' => 'Sistema', 'usuario_nombre' => 'Administrador'],
            ['descripcion' => 'Actualización de datos del estudiante', 'fecha' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'tabla_nombre' => 'Estudiantes', 'usuario_nombre' => 'Administrador'],
            ['descripcion' => 'Registro de nuevo profesor', 'fecha' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'tabla_nombre' => 'Profesores', 'usuario_nombre' => 'Administrador']
        ];
    }

    // Obtener datos de rendimiento académico por nivel
    $stmt = $pdo->query("
        SELECT 
            e.nivel,
            AVG(c.valor) as promedio
        FROM calificaciones c
        JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
        JOIN estudiantes e ON c.estudiante_id = e.id
        WHERE e.estado = 'Activo' AND c.estado = 'activo'
        GROUP BY e.nivel
    ");
    $rendimiento_nivel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar datos para gráfico de rendimiento
    $niveles_rendimiento = [];
    $valores_rendimiento = [];
    
    foreach ($rendimiento_nivel as $item) {
        $niveles_rendimiento[] = ucfirst($item['nivel'] ?? 'No definido');
        $valores_rendimiento[] = round(floatval($item['promedio']), 1);
    }

    $pdo->commit();
} catch(PDOException $e) {
    $pdo->rollBack();
    error_log("Error en dashboard: " . $e->getMessage());
    $total_estudiantes = 0;
    $total_profesores = 0;
    $total_sedes = 0;
    $promedio_calificaciones = 0;
    $config = [
        'school_name' => 'Sistema Escolar',
        'address' => 'Error al cargar dirección',
        'current_year' => date('Y')
    ];
    $actividades = [];
    $distribucion_nivel = [];
    $distribucion_sede = [];
    $distribucion_grado = [];
    $progreso_profesores = [];
    $rendimiento_nivel = [];
    $niveles_rendimiento = [];
    $valores_rendimiento = [];
}

// Función helper para formatear fechas en español
function formatearFechaEspanol($fecha) {
    $dias = array("Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado");
    $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    
    $dia = $dias[date('w', strtotime($fecha))];
    $numero = date('d', strtotime($fecha));
    $mes = $meses[date('n', strtotime($fecha)) - 1];
    $anio = date('Y', strtotime($fecha));
    
    return "$dia, $numero de $mes $anio";
}

// Función para oscurecer un color para bordes
function darken_color($hex, $percent) {
    $hex = ltrim($hex, '#');
    $rgb = array_map('hexdec', str_split($hex, 2));
    
    foreach ($rgb as &$color) {
        $color = max(0, min(255, $color - $color * ($percent / 100)));
    }
    
    return '#' . implode('', array_map(function($val) {
        return sprintf('%02x', $val);
    }, $rgb));
}

// Función para formatear tiempo transcurrido
function tiempo_transcurrido($fecha) {
    $time_diff = time() - strtotime($fecha);
    if($time_diff < 60) {
        return 'Hace '.round($time_diff).' segundos';
    } elseif($time_diff < 3600) {
        return 'Hace '.round($time_diff/60).' minutos';
    } elseif($time_diff < 86400) {
        return 'Hace '.round($time_diff/3600).' horas';
    } elseif($time_diff < 604800) {
        return 'Hace '.round($time_diff/86400).' días';
    } else {
        return date('d/m/Y', strtotime($fecha));
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($config['school_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --primary-color: #1a2b40;
            --primary-light: #2563eb;
            --secondary-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --danger-color: #ef4444;
            --purple-color: #8b5cf6;
            --pink-color: #ec4899;
            --text-primary: #0f172a; /* Color de texto más oscuro */
            --text-secondary: #334155; /* Color de texto secundario más oscuro */
            --text-muted: #64748b; /* Color muted más visible */
            --bg-light: #f3f4f6;
            --bg-white: #ffffff;
            --border-color: #e5e7eb;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            --transition: all 0.3s ease;
            --border-radius: 0.75rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .dashboard-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .dashboard-actions {
            display: flex;
            gap: 0.75rem;
        }

        .dashboard-content {
            margin-top: 1rem;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            background-position: right center;
            opacity: 0.2;
            z-index: 0;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .welcome-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .welcome-info {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .welcome-info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .welcome-info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .welcome-info-text {
            display: flex;
            flex-direction: column;
        }

        .welcome-info-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .welcome-info-value {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .welcome-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.8rem;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background-color: white;
            color: var(--primary-color);
        }

        .btn-outline {
            background-color: transparent;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            background-color: #f9fafb;
        }

        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            transition: var(--transition);
        }

        .stat-card.primary::after { background-color: var(--primary-light); }
        .stat-card.success::after { background-color: var(--success-color); }
        .stat-card.warning::after { background-color: var(--warning-color); }
        .stat-card.info::after { background-color: var(--info-color); }
        .stat-card.purple::after { background-color: var(--purple-color); }
        .stat-card.pink::after { background-color: var(--pink-color); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.875rem;
            color: #334155; /* Color más oscuro para mejor legibilidad */
            margin-bottom: 0.25rem;
            font-weight: 500; /* Un poco más de peso para mejor legibilidad */
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card.primary .stat-icon { background: #eff6ff; color: var(--primary-light); }
        .stat-card.success .stat-icon { background: #ecfdf5; color: var(--success-color); }
        .stat-card.warning .stat-icon { background: #fffbeb; color: var(--warning-color); }
        .stat-card.info .stat-icon { background: #eff6ff; color: var(--info-color); }
        .stat-card.purple .stat-icon { background: #f5f3ff; color: var(--purple-color); }
        .stat-card.pink .stat-icon { background: #fdf2f8; color: var(--pink-color); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1rem;
        }

        .dashboard-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            height: 100%;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .card-lg {
            grid-column: span 8;
        }

        .card-md {
            grid-column: span 4;
        }

        .card-sm {
            grid-column: span 6;
        }

        .card-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a; /* Título mucho más oscuro para mejor contraste */
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: var(--primary-light);
        }

        .card-actions {
            display: flex;
            gap: 0.5rem;
        }

        .card-action {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-light);
            color: var(--text-secondary);
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .card-action:hover {
            background: var(--primary-light);
            color: white;
        }

        .card-content {
            padding: 1rem;
            flex: 1;
            overflow: auto;
        }

        .chart-container {
            width: 100%;
            height: 250px;
            position: relative;
        }

        /* Estilos para tablas */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            font-weight: 600;
            color: #0f172a;
            background-color: #f1f5f9;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .data-table th, 
        .data-table td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background-color: var(--bg-light);
        }

        .data-table td {
            font-size: 0.875rem;
        }

        .data-table.compact {
            margin: 0;
            font-size: 0.85rem;
        }
        
        .data-table.compact th, 
        .data-table.compact td {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        .table-container {
            overflow-x: auto;
            margin: -0.25rem;
            padding: 0.25rem;
        }

        .empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 2.5rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-state-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-state-description {
            font-size: 0.875rem;
        }
        .progress-bar {
            height: 6px;
            background-color: var(--bg-light);
            border-radius: 3px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        .progress-fill.primary { background-color: var(--primary-light); }
        .progress-fill.success { background-color: var(--success-color); }
        .progress-fill.warning { background-color: var(--warning-color); }
        .progress-fill.info { background-color: var(--info-color); }
        .progress-fill.purple { background-color: var(--purple-color); }
        .progress-fill.pink { background-color: var(--pink-color); }

        .progress-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .progress-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-title {
            font-weight: 500;
            font-size: 0.875rem;
            color: #0f172a; /* Color más oscuro para mejor legibilidad */
        }

        .progress-value {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .activity-icon {
            display: inline-block;
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        
        .activity-desc {
            display: inline-block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
            vertical-align: middle;
            color: #0f172a; /* Color más oscuro para mejor legibilidad */
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            text-transform: capitalize;
            background-color: #f1f5f9; /* Fondo más claro */
            color: #334155; /* Texto más oscuro */
            border: 1px solid #e2e8f0; /* Añadir borde sutil */
        }
        
        .badge.success {
            background-color: var(--successLight, #D1FAE5);
            color: var(--success-color);
        }
        
        .badge.danger {
            background-color: var(--dangerLight, #FEE2E2);
            color: var(--danger-color);
        }
        
        .badge.warning {
            background-color: var(--warningLight, #FEF3C7);
            color: var(--warning-color);
        }
        
        .badge.info {
            background-color: var(--infoLight, #DBEAFE);
            color: var(--info-color);
        }
        
        .text-success { color: var(--success-color); }
        .text-danger { color: var(--danger-color); }
        .text-warning { color: var(--warning-color); }
        .text-info { color: var(--info-color); }
        .text-muted { color: var(--text-muted); }
        
        /* Padding en las tarjetas */
        .card-content.p-0 {
            padding: 0;
        }
        
        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.25rem;
            padding: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .pagination-item {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            font-size: 0.875rem;
            background: var(--bg-light);
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .pagination-item:hover {
            background: var(--primary-light);
            color: white;
        }
        
        .pagination-item.active {
            background: var(--primary-light);
            color: white;
            font-weight: 600;
        }
        
        .percentage-label {
            font-size: 0.75rem; 
            color: #334155; /* Color más oscuro para mejor legibilidad */
            display: inline-block;
            margin-top: 0.25rem;
            font-weight: 500; /* Añadir peso para mejor visibilidad */
        }

        .progress-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .metric-card {
            background: var(--bg-light);
            border-radius: 0.5rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }

        .metric-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        /* Media queries */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(6, 1fr);
            }
            
            .card-lg {
                grid-column: span 6;
            }
            
            .card-md {
                grid-column: span 3;
            }
            
            .card-sm {
                grid-column: span 3;
            }
        }

        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .card-lg {
                grid-column: span 3;
            }
            
            .card-md {
                grid-column: span 3;
            }
            
            .card-sm {
                grid-column: span 3;
            }

            .progress-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .card-lg, .card-md, .card-sm {
                grid-column: span 1;
            }
            
            .welcome-actions {
                flex-wrap: wrap;
            }
            
            .welcome-info {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .dashboard-card {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'components/topbar.php'; ?>

            <!-- Contenido del Dashboard -->
            <div class="dashboard-content">
                <!-- Banner de bienvenida -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h1 class="welcome-title">Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></h1>
                        <p class="welcome-subtitle">Panel de administración <?php echo htmlspecialchars($config['school_name']); ?> - Año escolar <?php echo htmlspecialchars($config['current_year']); ?></p>
                        
                        <div class="welcome-info">
                            <div class="welcome-info-item">
                                <div class="welcome-info-icon">
                                    <i class="fas fa-calendar-days"></i>
                                </div>
                                <div class="welcome-info-text">
                                    <span class="welcome-info-label">Periodo actual</span>
                                    <span class="welcome-info-value"><?php echo htmlspecialchars($periodo_actual['nombre'] ?? 'No definido'); ?></span>
                                </div>
                            </div>
                            <div class="welcome-info-item">
                                <div class="welcome-info-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="welcome-info-text">
                                    <span class="welcome-info-label">Estudiantes activos</span>
                                    <span class="welcome-info-value"><?php echo number_format($total_estudiantes); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="welcome-actions">
                            <a href="/school_management/admin/users/create_student.php" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i>
                                Nuevo Estudiante
                            </a>
                            <a href="/school_management/admin/users/create_teacher.php" class="btn btn-primary">
                                <i class="fas fa-user-tie"></i>
                                Nuevo Profesor
                            </a>
                            <a href="/school_management/admin/academic/create_materia.php" class="btn btn-primary">
                                <i class="fas fa-book"></i>
                                Nueva Asignatura
                            </a>
                            <a href="/school_management/admin/users/create_headquarters.php" class="btn btn-primary">
                                <i class="fas fa-school"></i>
                                Nueva Sede
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Estudiantes</div>
                                <div class="stat-value"><?php echo number_format($total_estudiantes); ?></div>
                                <div class="stat-description">Estudiantes matriculados</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Profesores</div>
                                <div class="stat-value"><?php echo number_format($total_profesores); ?></div>
                                <div class="stat-description">Profesores activos</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Sedes</div>
                                <div class="stat-value"><?php echo number_format($total_sedes); ?></div>
                                <div class="stat-description">Sedes activas</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-school"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Calificación</div>
                                <div class="stat-value"><?php echo $promedio_calificaciones; ?></div>
                                <div class="stat-description">Promedio general</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grilla de tarjetas principal -->
                <div class="dashboard-grid">
                    <!-- Primera fila con gráficos y rendimiento -->
                    <div class="dashboard-card card-sm">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-chart-pie"></i>
                                Distribución por Nivel
                            </h2>
                        </div>
                        <div class="card-content">
                            <div class="chart-container" style="height: 200px">
                                <canvas id="studentDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card card-sm">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-graduation-cap"></i>
                                Estudiantes por Grado
                            </h2>
                        </div>
                        <div class="card-content p-0">
                            <?php if(empty($distribucion_grado)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <h3 class="empty-state-title">Sin datos</h3>
                                    <p class="empty-state-description">No hay información de grados</p>
                                </div>
                            <?php else: ?>
                                <div class="table-container">
                                    <table class="data-table compact">
                                        <thead>
                                            <tr>
                                                <th>Grado</th>
                                                <th style="text-align: right;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Limitar a mostrar solo 6 filas para mantener diseño compacto
                                            $max_rows = min(6, count($distribucion_grado));
                                            for($i = 0; $i < $max_rows; $i++): 
                                                $grado = $distribucion_grado[$i];
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($grado['nombre']); ?></strong></td>
                                                <td style="text-align: right;"><?php echo number_format($grado['total']); ?></td>
                                            </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-card card-sm">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-chart-bar"></i>
                                Rendimiento Académico
                            </h2>
                        </div>
                        <div class="card-content">
                            <div class="chart-container" style="height: 200px">
                                <canvas id="rendimientoNivelChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card card-sm">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-tasks"></i>
                                Progreso de Notas
                            </h2>
                        </div>
                        <div class="card-content">
                            <?php if(empty($progreso_profesores)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <h3 class="empty-state-title">Sin datos</h3>
                                    <p class="empty-state-description">No hay información disponible</p>
                                </div>
                            <?php else: ?>
                                <div class="progress-list">
                                    <?php
                                    // Limitar a mostrar solo 4 profesores para mantener diseño compacto
                                    $max_profs = min(4, count($progreso_profesores));
                                    for($i = 0; $i < $max_profs; $i++): 
                                        $profesor = $progreso_profesores[$i];
                                        // Calcular el porcentaje de progreso
                                        $total_asignaciones = max(1, $profesor['total_asignaciones']);
                                        $progreso = min(100, ($profesor['total_calificaciones'] / $total_asignaciones) * 100);
                                        $progreso = round($progreso);
                                        
                                        // Determinar color basado en progreso
                                        $color_class = 'primary';
                                        if($progreso >= 80) {
                                            $color_class = 'success';
                                        } elseif($progreso >= 50) {
                                            $color_class = 'warning';
                                        } elseif($progreso < 50) {
                                            $color_class = 'info';
                                        }
                                    ?>
                                    <div class="progress-item">
                                        <div class="progress-header">
                                            <div class="progress-title"><?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?></div>
                                            <div class="progress-value"><?php echo $progreso; ?>%</div>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill <?php echo $color_class; ?>" style="width: <?php echo $progreso; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Segunda fila con distribución de sedes e historial -->
                    <div class="dashboard-card card-lg">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-building"></i>
                                Distribución por Sedes
                            </h2>
                            <div class="card-actions">
                                <a href="/school_management/admin/users/sedes.php" class="card-action" title="Administrar sedes">
                                    <i class="fas fa-cog"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-content p-0">
                            <?php if(empty($distribucion_sede)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-school"></i>
                                    </div>
                                    <h3 class="empty-state-title">Sin datos de sedes</h3>
                                    <p class="empty-state-description">No hay sedes registradas en el sistema</p>
                                </div>
                            <?php else: ?>
                                <div class="table-container">
                                    <table class="data-table compact">
                                        <thead>
                                            <tr>
                                                <th>Sede</th>
                                                <th>Estudiantes</th>
                                                <th>Profesores</th>
                                                <th>Estado</th>
                                                <th width="30%">Distribución</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_estudiantes_dist = array_sum(array_column($distribucion_sede, 'total_estudiantes'));
                                            $colors = ['primary', 'success', 'warning', 'info', 'purple', 'pink'];
                                            
                                            foreach($distribucion_sede as $index => $sede): 
                                                $porcentaje = $total_estudiantes_dist > 0 ? round(($sede['total_estudiantes'] / $total_estudiantes_dist) * 100, 1) : 0;
                                                $color = $colors[$index % count($colors)];
                                                $estado_class = $sede['estado'] == 'activo' ? 'success' : 'danger';
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($sede['nombre']); ?></strong></td>
                                                <td><?php echo number_format($sede['total_estudiantes']); ?></td>
                                                <td><?php echo number_format($sede['total_profesores']); ?></td>
                                                <td><span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($sede['estado']); ?></span></td>
                                                <td>
                                                    <div class="progress-bar">
                                                        <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $porcentaje; ?>%"></div>
                                                    </div>
                                                    <span class="percentage-label"><?php echo $porcentaje; ?>%</span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-card card-md">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-history"></i>
                                Historial de Cambios
                            </h2>
                            <div class="card-actions">
                                <a href="activity_log.php" class="card-action" title="Ver historial completo">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-content p-0">
                            <?php if(empty($actividades)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <h3 class="empty-state-title">Sin registros</h3>
                                    <p class="empty-state-description">No hay actividades registradas</p>
                                </div>
                            <?php else: ?>
                                <div class="table-container">
                                    <table class="data-table compact">
                                        <thead>
                                            <tr>
                                                <th>Acción</th>
                                                <th>Módulo</th>
                                                <th>Usuario</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $icons = [
                                                'crear' => '<i class="fas fa-plus-circle text-success"></i>',
                                                'actualizar' => '<i class="fas fa-edit text-info"></i>',
                                                'eliminar' => '<i class="fas fa-trash text-danger"></i>',
                                                'cambiar' => '<i class="fas fa-sync text-warning"></i>',
                                                'deshabilitar' => '<i class="fas fa-ban text-danger"></i>',
                                                'habilitar' => '<i class="fas fa-check-circle text-success"></i>',
                                                'default' => '<i class="fas fa-circle-notch text-muted"></i>'
                                            ];
                                            
                                            foreach($actividades as $actividad): 
                                                // Determinar el icono basado en la descripción
                                                $action_type = 'default';
                                                foreach(array_keys($icons) as $action) {
                                                    if(stripos($actividad['descripcion'], $action) !== false) {
                                                        $action_type = $action;
                                                        break;
                                                    }
                                                }
                                                
                                                $icon = $icons[$action_type];
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="activity-icon"><?php echo $icon; ?></span>
                                                    <span class="activity-desc"><?php echo htmlspecialchars($actividad['descripcion']); ?></span>
                                                </td>
                                                <td><span class="badge"><?php echo htmlspecialchars($actividad['tabla_nombre'] ?? '-'); ?></span></td>
                                                <td><?php echo htmlspecialchars($actividad['usuario_nombre'] ?? 'Sistema'); ?></td>
                                                <td><small><?php echo tiempo_transcurrido($actividad['fecha']); ?></small></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php if($total_pages > 1): ?>
                                <div class="pagination">
                                    <?php if($page > 1): ?>
                                        <a href="?page=<?php echo ($page - 1); ?>" class="pagination-item">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Mostrar máximo 5 páginas
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $start_page + 4);
                                    
                                    for($i = $start_page; $i <= $end_page; $i++): 
                                    ?>
                                        <a href="?page=<?php echo $i; ?>" class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if($page < $total_pages): ?>
                                        <a href="?page=<?php echo ($page + 1); ?>" class="pagination-item">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar paleta de colores personalizada
            const colorPalette = {
                primary: '#4F46E5',
                success: '#10B981',
                warning: '#F59E0B',
                danger: '#EF4444',
                info: '#3B82F6',
                purple: '#8B5CF6',
                pink: '#EC4899',
                primaryLight: '#C7D2FE',
                successLight: '#D1FAE5',
                warningLight: '#FEF3C7',
                dangerLight: '#FEE2E2',
                infoLight: '#DBEAFE'
            };
            
            // Inicializar el gráfico de distribución de estudiantes
            const studentDistributionCtx = document.getElementById('studentDistributionChart');
            if (studentDistributionCtx) {
                new Chart(studentDistributionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($niveles ?: ['Primaria', 'Secundaria', 'Media']); ?>,
                        datasets: [{
                            label: 'Estudiantes por Nivel',
                            data: <?php echo json_encode($totales_nivel ?: [45, 35, 20]); ?>,
                            backgroundColor: <?php echo json_encode($colores_array ?: [colorPalette.primary, colorPalette.success, colorPalette.warning, colorPalette.danger]); ?>,
                            borderColor: <?php echo json_encode($bordes_array ?: [colorPalette.primary, colorPalette.success, colorPalette.warning, colorPalette.danger]); ?>,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        family: "'Inter', sans-serif",
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: '#fff',
                                titleColor: '#1f2937',
                                bodyColor: '#6b7280',
                                borderColor: '#e5e7eb',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: {
                                    family: "'Inter', sans-serif",
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    family: "'Inter', sans-serif"
                                },
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.formattedValue;
                                        const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                        const percentage = Math.round((context.raw / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Inicializar el gráfico de rendimiento por nivel
            const rendimientoNivelCtx = document.getElementById('rendimientoNivelChart');
            if (rendimientoNivelCtx) {
                // Convertir datos de rendimiento por nivel
                const nivelesRendimiento = <?php echo json_encode($niveles_rendimiento ?: ['Preescolar', 'Primaria', 'Secundaria', 'Media']); ?>;
                const promediosRendimiento = <?php echo json_encode($valores_rendimiento ?: [4.2, 3.9, 3.7, 3.5]); ?>;
                
                new Chart(rendimientoNivelCtx, {
                    type: 'bar',
                    data: {
                        labels: nivelesRendimiento,
                        datasets: [{
                            label: 'Promedio',
                            data: promediosRendimiento,
                            backgroundColor: [
                                colorPalette.primaryLight,
                                colorPalette.successLight,
                                colorPalette.warningLight,
                                colorPalette.infoLight
                            ],
                            borderColor: [
                                colorPalette.primary,
                                colorPalette.success,
                                colorPalette.warning,
                                colorPalette.info
                            ],
                            borderWidth: 2,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#fff',
                                titleColor: '#1f2937',
                                bodyColor: '#6b7280',
                                borderColor: '#e5e7eb',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: {
                                    family: "'Inter', sans-serif",
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    family: "'Inter', sans-serif"
                                },
                                callbacks: {
                                    label: function(context) {
                                        return `Promedio: ${context.formattedValue}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5,
                                ticks: {
                                    stepSize: 1,
                                    font: {
                                        family: "'Inter', sans-serif",
                                        size: 11
                                    }
                                },
                                grid: {
                                    color: '#e5e7eb',
                                    drawBorder: false
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        family: "'Inter', sans-serif",
                                        size: 11
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>