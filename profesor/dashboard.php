<?php
session_start();
if(!isset($_SESSION['profesor_id'])) {
    header('Location: ../auth/profesor_login.php');
    exit();
}

// Definir base_url
$profesor_base_url = '/school_management/profesor';

require_once '../config/database.php';

try {
    // Obtener información del profesor actual
    $stmt = $pdo->prepare("
        SELECT id, nombre, apellido, email, especialidad, telefono, sede_id
        FROM profesores WHERE id = ?
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $profesor = $stmt->fetch();

    // Obtener nombre de la sede
    if (!empty($profesor['sede_id'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM sedes WHERE id = ?");
        $stmt->execute([$profesor['sede_id']]);
        $sede = $stmt->fetch();
        $profesor['sede_nombre'] = $sede ? $sede['nombre'] : 'No asignada';
    } else {
        $profesor['sede_nombre'] = 'No asignada';
    }

    // Obtener total de estudiantes asignados al profesor
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.id) 
        FROM estudiantes e 
        INNER JOIN asignaciones_profesor ap ON e.grado_id = ap.grado_id 
        WHERE ap.profesor_id = ? AND ap.estado = 'activo' AND e.estado = 'Activo'
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $total_estudiantes = $stmt->fetchColumn();

    // Obtener total de materias que imparte
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT materia_id) 
        FROM asignaciones_profesor 
        WHERE profesor_id = ? AND estado = 'activo'
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $total_materias = $stmt->fetchColumn();
    
    // Obtener total de grupos
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT grado_id) 
        FROM asignaciones_profesor 
        WHERE profesor_id = ? AND estado = 'activo'
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $total_grupos = $stmt->fetchColumn();
    
    // Obtener periodo académico actual
    $stmt = $pdo->prepare("
        SELECT id, nombre, fecha_inicio, fecha_fin
        FROM periodos_academicos 
        WHERE estado = 'activo' AND estado_periodo = 'en_curso'
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $periodo_activo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener últimas actividades
    $stmt = $pdo->prepare("
        SELECT 
            al.id,
            al.accion,
            al.descripcion,
            al.fecha
        FROM actividad_log al
        WHERE al.usuario_id = ?
        ORDER BY al.fecha DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener total de calificaciones registradas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM calificaciones c 
        INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id 
        INNER JOIN asignaciones_profesor ap ON tn.asignacion_id = ap.id 
        WHERE ap.profesor_id = ? AND c.estado = 'activo'
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $total_calificaciones = $stmt->fetchColumn();

    // Obtener total de estudiantes calificados (con al menos una nota)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT c.estudiante_id) 
        FROM calificaciones c 
        INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id 
        INNER JOIN asignaciones_profesor ap ON tn.asignacion_id = ap.id 
        WHERE ap.profesor_id = ? AND c.estado = 'activo'
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $estudiantes_calificados = $stmt->fetchColumn();

    // Obtener distribución de calificaciones por rango
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN c.valor BETWEEN 0 AND 2.9 THEN 'bajo'
                WHEN c.valor BETWEEN 3.0 AND 3.9 THEN 'basico'
                WHEN c.valor BETWEEN 4.0 AND 4.5 THEN 'alto'
                WHEN c.valor BETWEEN 4.6 AND 5.0 THEN 'superior'
                ELSE 'otro'
            END as rango,
            COUNT(*) as cantidad
        FROM calificaciones c
        INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
        INNER JOIN asignaciones_profesor ap ON tn.asignacion_id = ap.id
        WHERE ap.profesor_id = ? AND c.estado = 'activo'
        GROUP BY rango
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $distribucion_calificaciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Calcular porcentaje de estudiantes calificados
    $porcentaje_calificados = $total_estudiantes > 0 ? round(($estudiantes_calificados / $total_estudiantes) * 100) : 0;

} catch(PDOException $e) {
    error_log("Error en dashboard del profesor: " . $e->getMessage());
    $error = "Error al obtener estadísticas";
    $total_estudiantes = 0;
    $total_materias = 0;
    $total_grupos = 0;
    $total_calificaciones = 0;
    $estudiantes_calificados = 0;
    $porcentaje_calificados = 0;
    $distribucion_calificaciones = [];
    $actividades = [];
    $periodo_activo = null;
}

// Función para formatear fechas
function formatearFecha($fecha) {
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

// Función para calcular días restantes
function calcularDiasRestantes($fecha_fin) {
    $hoy = time();
    $fin = strtotime($fecha_fin);
    $diferencia = $fin - $hoy;
    
    return max(0, floor($diferencia / (60 * 60 * 24)));
}

// Definir el título de la página para el encabezado
$page_title = 'Dashboard';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo $profesor_base_url; ?>">
    <title>Dashboard | <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Incluir archivos CSS -->
    <link rel="stylesheet" href="<?php echo $profesor_base_url; ?>/assets/css/components/dashboard.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/views/components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/views/components/topbar.php'; ?>
            
            <div class="content-wrapper">
                <!-- Perfil del profesor -->
                <section class="teacher-profile animate-fade-in">
                    <div class="profile-picture">
                        <?php echo strtoupper(substr($profesor['nombre'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name"><?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?></h1>
                        <div class="profile-role">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Profesor</span>
                        </div>
                        
                        <div class="profile-details">
                            <div class="profile-detail">
                                <i class="fas fa-envelope"></i>
                                <div class="profile-detail-content">
                                    <span class="profile-detail-label">Email</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($profesor['email']); ?></span>
                                </div>
                            </div>
                            
                            <div class="profile-detail">
                                <i class="fas fa-phone"></i>
                                <div class="profile-detail-content">
                                    <span class="profile-detail-label">Teléfono</span>
                                    <span class="profile-detail-value"><?php echo $profesor['telefono'] ? htmlspecialchars($profesor['telefono']) : 'No registrado'; ?></span>
                                </div>
                            </div>
                            
                            <div class="profile-detail">
                                <i class="fas fa-school"></i>
                                <div class="profile-detail-content">
                                    <span class="profile-detail-label">Sede</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($profesor['sede_nombre']); ?></span>
                                </div>
                            </div>
                            
                            <div class="profile-detail">
                                <i class="fas fa-book"></i>
                                <div class="profile-detail-content">
                                    <span class="profile-detail-label">Especialidad</span>
                                    <span class="profile-detail-value"><?php echo $profesor['especialidad'] ? htmlspecialchars($profesor['especialidad']) : 'No registrada'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Periodo académico actual -->
                <?php if ($periodo_activo): 
                    $dias_total = ceil((strtotime($periodo_activo['fecha_fin']) - strtotime($periodo_activo['fecha_inicio'])) / (60 * 60 * 24));
                    $dias_transcurridos = ceil((time() - strtotime($periodo_activo['fecha_inicio'])) / (60 * 60 * 24));
                    $dias_transcurridos = max(0, min($dias_transcurridos, $dias_total));
                    $porcentaje_completado = ($dias_total > 0) ? min(100, ($dias_transcurridos / $dias_total) * 100) : 0;
                    $dias_restantes = calcularDiasRestantes($periodo_activo['fecha_fin']);
                ?>
                <section class="period-info animate-fade-in delay-100">
                    <div class="period-header">
                        <div class="period-title">
                            <h2><i class="fas fa-calendar-alt"></i> Periodo Académico Actual</h2>
                            <p><?php echo htmlspecialchars($periodo_activo['nombre']); ?></p>
                        </div>
                        <div class="period-badge">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $dias_restantes; ?> días restantes</span>
                        </div>
                    </div>
                    
                    <div class="period-dates">
                        <div class="period-date">
                            <span class="period-date-label">Fecha Inicio</span>
                            <span class="period-date-value"><?php echo formatearFecha($periodo_activo['fecha_inicio']); ?></span>
                        </div>
                        
                        <div class="period-date">
                            <span class="period-date-label">Progreso</span>
                            <span class="period-date-value"><?php echo round($porcentaje_completado); ?>%</span>
                        </div>
                        
                        <div class="period-date">
                            <span class="period-date-label">Fecha Fin</span>
                            <span class="period-date-value"><?php echo formatearFecha($periodo_activo['fecha_fin']); ?></span>
                        </div>
                    </div>
                    
                    <div class="period-progress">
                        <div class="period-progress-bar" style="width: <?php echo $porcentaje_completado; ?>%"></div>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Estadísticas básicas -->
                <section class="stats-grid animate-fade-in delay-200">
                    <div class="stat-card">
                        <div class="stat-icon stat-students">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $total_estudiantes; ?></div>
                            <div class="stat-label">Estudiantes</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-subjects">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $total_materias; ?></div>
                            <div class="stat-label">Materias</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-groups">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $total_grupos; ?></div>
                            <div class="stat-label">Grupos</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-grades">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $total_calificaciones; ?></div>
                            <div class="stat-label">Calificaciones registradas</div>
                        </div>
                    </div>
                </section>
                
                <!-- Estado de las calificaciones -->
                <section class="dashboard-section grades-status-section animate-slide-up delay-300">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-clipboard-check"></i> Estado de Calificaciones
                        </h2>
                    </div>
                    
                    <div class="section-body">
                        <div class="grades-status-content">
                            <div class="grades-chart">
                                <div class="grades-percentage"><?php echo $porcentaje_calificados; ?>%</div>
                            </div>
                            
                            <div class="grades-details">
                                <h3 class="grades-title">Estudiantes Calificados</h3>
                                <p class="grades-description">
                                    De los <?php echo $total_estudiantes; ?> estudiantes asignados, <?php echo $estudiantes_calificados; ?> 
                                    han recibido al menos una calificación en el periodo actual.
                                </p>
                                
                                <div class="grades-stats">
                                    <div class="grades-stat">
                                        <span class="grades-stat-value"><?php echo $estudiantes_calificados; ?></span>
                                        <span class="grades-stat-label">Estudiantes calificados</span>
                                    </div>
                                    
                                    <div class="grades-stat">
                                        <span class="grades-stat-value"><?php echo max(0, $total_estudiantes - $estudiantes_calificados); ?></span>
                                        <span class="grades-stat-label">Estudiantes sin calificar</span>
                                    </div>
                                    
                                    <div class="grades-stat">
                                        <span class="grades-stat-value"><?php echo number_format($total_calificaciones / max(1, $estudiantes_calificados), 1); ?></span>
                                        <span class="grades-stat-label">Promedio de notas por estudiante</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <div class="dashboard-sections">
                    <!-- Distribución de calificaciones -->
                    <section class="dashboard-section distribution-section animate-slide-up delay-400">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-chart-pie"></i> Distribución de Calificaciones
                            </h2>
                        </div>
                        
                        <div class="section-body">
                            <?php if (!empty($distribucion_calificaciones)): ?>
                                <div class="distribution-grid">
                                    <div class="distribution-item distribution-bajo">
                                        <div class="distribution-value"><?php echo $distribucion_calificaciones['bajo'] ?? 0; ?></div>
                                        <div class="distribution-label">Bajo</div>
                                        <div class="distribution-range">0.0 - 2.9</div>
                                    </div>
                                    
                                    <div class="distribution-item distribution-basico">
                                        <div class="distribution-value"><?php echo $distribucion_calificaciones['basico'] ?? 0; ?></div>
                                        <div class="distribution-label">Básico</div>
                                        <div class="distribution-range">3.0 - 3.9</div>
                                    </div>
                                    
                                    <div class="distribution-item distribution-alto">
                                        <div class="distribution-value"><?php echo $distribucion_calificaciones['alto'] ?? 0; ?></div>
                                        <div class="distribution-label">Alto</div>
                                        <div class="distribution-range">4.0 - 4.5</div>
                                    </div>
                                    
                                    <div class="distribution-item distribution-superior">
                                        <div class="distribution-value"><?php echo $distribucion_calificaciones['superior'] ?? 0; ?></div>
                                        <div class="distribution-label">Superior</div>
                                        <div class="distribution-range">4.6 - 5.0</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-bar"></i>
                                    <h3>Sin calificaciones</h3>
                                    <p>No hay datos de calificaciones disponibles para mostrar.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    
                    <!-- Actividades recientes -->
                    <section class="dashboard-section activity-section animate-slide-up delay-500">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-history"></i> Actividades Recientes
                            </h2>
                        </div>
                        
                        <div class="section-body">
                            <?php if (!empty($actividades)): ?>
                                <div class="activity-list">
                                    <?php foreach ($actividades as $actividad): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <?php
                                                    $icon = 'fa-bolt';
                                                    if ($actividad['accion'] === 'crear') {
                                                        $icon = 'fa-plus';
                                                    } elseif ($actividad['accion'] === 'editar') {
                                                        $icon = 'fa-edit';
                                                    } elseif ($actividad['accion'] === 'eliminar') {
                                                        $icon = 'fa-trash';
                                                    }
                                                ?>
                                                <i class="fas <?php echo $icon; ?>"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-text"><?php echo htmlspecialchars($actividad['descripcion']); ?></div>
                                                <div class="activity-time"><?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <h3>Sin actividad reciente</h3>
                                    <p>Tus actividades recientes aparecerán aquí.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                
                <!-- Estadísticas adicionales -->
                <section class="dashboard-section animate-slide-up">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-chart-line"></i> Estadísticas Académicas
                        </h2>
                    </div>
                    
                    <div class="section-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon stat-calificados">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value">
                                        <?php 
                                            $promedio_general = 0;
                                            if ($total_calificaciones > 0) {
                                                // Calcular promedio general (suma de todos los valores / total de calificaciones)
                                                $stmt = $pdo->prepare("
                                                    SELECT AVG(c.valor) as promedio
                                                    FROM calificaciones c
                                                    INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                                                    INNER JOIN asignaciones_profesor ap ON tn.asignacion_id = ap.id
                                                    WHERE ap.profesor_id = ? AND c.estado = 'activo'
                                                ");
                                                $stmt->execute([$_SESSION['profesor_id']]);
                                                $promedio_general = $stmt->fetchColumn();
                                            }
                                            echo number_format($promedio_general, 1);
                                        ?>
                                    </div>
                                    <div class="stat-label">Promedio general</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: var(--danger);">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value">
                                        <?php
                                            // Calcular estudiantes en riesgo (promedio menor a 3.0)
                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(DISTINCT e.id)
                                                FROM estudiantes e
                                                INNER JOIN calificaciones c ON e.id = c.estudiante_id
                                                INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                                                INNER JOIN asignaciones_profesor ap ON tn.asignacion_id = ap.id
                                                WHERE ap.profesor_id = ?
                                                AND c.estado = 'activo'
                                                AND e.estado = 'Activo'
                                                GROUP BY e.id
                                                HAVING AVG(c.valor) < 3.0
                                            ");
                                            $stmt->execute([$_SESSION['profesor_id']]);
                                            $estudiantes_riesgo = $stmt->rowCount();
                                            echo $estudiantes_riesgo;
                                        ?>
                                    </div>
                                    <div class="stat-label">Estudiantes en riesgo</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #6366f1;">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value">
                                        <?php
                                            // Calcular estudiantes destacados (promedio mayor a 4.5)
                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(DISTINCT e.id)
                                                FROM estudiantes e
                                                INNER JOIN calificaciones c ON e.id = c.estudiante_id
                                                INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                                                INNER JOIN asignaciones_profesor ap ON tn.asignacion_id = ap.id
                                                WHERE ap.profesor_id = ?
                                                AND c.estado = 'activo'
                                                AND e.estado = 'Activo'
                                                GROUP BY e.id
                                                HAVING AVG(c.valor) >= 4.5
                                            ");
                                            $stmt->execute([$_SESSION['profesor_id']]);
                                            $estudiantes_destacados = $stmt->rowCount();
                                            echo $estudiantes_destacados;
                                        ?>
                                    </div>
                                    <div class="stat-label">Estudiantes destacados</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #0ea5e9;">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value">
                                        <?php
                                            // Obtener la fecha de la última calificación registrada
                                            $stmt = $pdo->prepare("
                                                SELECT MAX(c.fecha_registro) as ultima_fecha
                                                FROM calificaciones c
                                                INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                                                INNER JOIN asignaciones_profesor ap ON tn.asignacion_id = ap.id
                                                WHERE ap.profesor_id = ? AND c.estado = 'activo'
                                            ");
                                            $stmt->execute([$_SESSION['profesor_id']]);
                                            $ultima_fecha = $stmt->fetchColumn();
                                            
                                            if ($ultima_fecha) {
                                                $dias_ultima = ceil((time() - strtotime($ultima_fecha)) / (60 * 60 * 24));
                                                echo $dias_ultima;
                                            } else {
                                                echo "-";
                                            }
                                        ?>
                                    </div>
                                    <div class="stat-label">Días desde última calificación</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Incluir archivos JavaScript al final para mejor rendimiento -->
    <script src="<?php echo $profesor_base_url; ?>/assets/js/components/sidebar.js"></script>
    <script src="<?php echo $profesor_base_url; ?>/assets/js/components/topbar.js"></script>
    <script src="<?php echo $profesor_base_url; ?>/assets/js/components/dashboard.js"></script>
</body>
</html>