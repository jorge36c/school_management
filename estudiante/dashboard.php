<?php
session_start();

// Variables para el layout
$page_title = 'Dashboard';
$page_description = 'Bienvenido a tu panel de control';

if (!isset($_SESSION['estudiante_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';
$base_url = '/school_management';

try {
    // Obtener información del estudiante
    $stmt = $pdo->prepare("
        SELECT 
            e.*, 
            g.nombre as grado_nombre,
            s.nombre as sede_nombre
        FROM estudiantes e
        LEFT JOIN grados g ON e.grado_id = g.id
        LEFT JOIN sedes s ON g.sede_id = s.id
        WHERE e.id = ?
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener promedio general
    $stmt = $pdo->prepare("
        SELECT COALESCE(ROUND(AVG(valor), 1), 0) as promedio
        FROM calificaciones 
        WHERE estudiante_id = ? AND estado = 'activo'
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $promedio = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'];

    // Obtener total de materias
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM asignaciones_profesor ap
        WHERE ap.grado_id = ? AND ap.estado = 'activo'
    ");
    $stmt->execute([$estudiante['grado_id']]);
    $total_materias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtener tareas pendientes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pendientes
        FROM tareas t
        INNER JOIN asignaciones_profesor ap ON t.asignacion_id = ap.id
        WHERE ap.grado_id = ? 
        AND t.estado = 'activo'
        AND t.fecha_vencimiento >= CURRENT_DATE
        AND t.fecha_entrega IS NULL
    ");
    $stmt->execute([$estudiante['grado_id']]);
    $tareas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['pendientes'];

} catch(PDOException $e) {
    error_log("Error en dashboard.php: " . $e->getMessage());
    $promedio = 0.0;
    $total_materias = 0;
    $tareas_pendientes = 0;
}

// Contenido específico de la página
ob_start(); 
?>

<div class="dashboard-container">
    <div class="header-section">
        <h1>Dashboard</h1>
        <p>Bienvenido a tu panel de control</p>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-value"><?php echo number_format($promedio, 1); ?></div>
            <div class="stat-label">
                Promedio General
                <div><?php echo $total_materias; ?> materias</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-value"><?php echo $tareas_pendientes; ?></div>
            <div class="stat-label">
                Tareas Pendientes
                <div>Por entregar</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-value"><?php echo htmlspecialchars($estudiante['grado_nombre'] ?? 'No disponible'); ?></div>
            <div class="stat-label">
                Grado
                <div><?php echo htmlspecialchars($estudiante['sede_nombre'] ?? 'No disponible'); ?></div>
            </div>
        </div>
    </div>

    <div class="info-container">
        <div class="info-card">
            <h3><i class="fas fa-clock"></i> Actividad Reciente</h3>
            <!-- Contenido de actividad reciente -->
        </div>

        <div class="info-card">
            <h3><i class="fas fa-calendar"></i> Próximas Entregas</h3>
            <!-- Contenido de próximas entregas -->
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Estilos específicos del dashboard
$page_styles = "
    .dashboard-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    .header-section {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        text-align: center;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        background: rgba(79, 70, 229, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        color: #4f46e5;
        font-size: 1.25rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0.5rem 0;
    }

    .stat-label {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .info-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    .info-card {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .info-card h3 {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #1f2937;
        margin: 0 0 1rem 0;
        font-size: 1.25rem;
        justify-content: center;
    }

    @media (max-width: 1024px) {
        .stats-container {
            grid-template-columns: repeat(2, 1fr);
        }
        .info-container {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .stats-container {
            grid-template-columns: 1fr;
        }
    }
";

// Incluir el layout base
include __DIR__ . '/layouts/main.php';
?>