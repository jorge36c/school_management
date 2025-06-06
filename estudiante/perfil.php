<?php
session_start();

// Variables para el layout
$page_title = 'Mi Perfil';
$page_description = 'Gestiona tu información personal';

if (!isset($_SESSION['estudiante_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

try {
    // Consulta completa del estudiante con todas sus relaciones
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            g.id as grado_id,
            g.nombre as grado_nombre,
            g.nivel as nivel_educativo,
            s.nombre as sede_nombre,
            CONCAT(e.nombre, ' ', e.apellido) as nombre_completo
        FROM estudiantes e
        LEFT JOIN grados g ON e.grado_id = g.id
        LEFT JOIN sedes s ON g.sede_id = s.id
        WHERE e.id = ?
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calcular promedio y otras estadísticas...
    $stmt = $pdo->prepare("
        SELECT AVG(valor) as promedio 
        FROM calificaciones 
        WHERE estudiante_id = ? AND estado = 'activo'
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $promedio = number_format($stmt->fetch(PDO::FETCH_ASSOC)['promedio'] ?? 0, 1);

} catch(PDOException $e) {
    error_log("Error en perfil.php: " . $e->getMessage());
    $promedio = '0.0';
}

// Contenido específico de la página
ob_start(); 
?>

<div class="profile-content">
    <!-- Cabecera del Perfil -->
    <div class="profile-header content-card">
        <div class="profile-header-content">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($estudiante['nombre_completo']); ?></h2>
                <p class="profile-subtitle">
                    <?php echo htmlspecialchars($estudiante['nivel_educativo'] . ' - ' . $estudiante['grado_nombre']); ?>
                </p>
            </div>
        </div>
        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $promedio; ?></div>
                <div class="stat-label">Promedio General</div>
            </div>
        </div>
    </div>

    <!-- Información Personal -->
    <div class="content-card">
        <h3 class="section-title">Información Personal</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Documento</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($estudiante['documento_tipo'] . ': ' . $estudiante['documento_numero']); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Correo</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($estudiante['email'] ?? 'No registrado'); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Teléfono</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($estudiante['telefono'] ?? 'No registrado'); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Sede</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($estudiante['sede_nombre']); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Dirección</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($estudiante['direccion'] ?? 'No registrada'); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha de Nacimiento</span>
                <span class="info-value">
                    <?php echo $estudiante['fecha_nacimiento'] ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) : 'No registrada'; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Información del Acudiente -->
    <div class="content-card">
        <h3 class="section-title">Información del Acudiente</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Nombre del Acudiente</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($estudiante['nombre_acudiente'] ?? 'No registrado'); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Teléfono del Acudiente</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($estudiante['telefono_acudiente'] ?? 'No registrado'); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Email del Acudiente</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($estudiante['email_acudiente'] ?? 'No registrado'); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Estilos específicos del perfil
$page_styles = "
    .profile-content {
        max-width: 1200px;
        margin: 0 auto;
    }

    .profile-header {
        padding: 2rem;
    }

    .profile-header-content {
        display: flex;
        align-items: center;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        background: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .profile-avatar i {
        font-size: 2.5rem;
        color: white;
    }

    .profile-info h2 {
        font-size: 1.8rem;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .profile-subtitle {
        color: var(--text-secondary);
        font-size: 1.1rem;
    }

    .profile-stats {
        display: flex;
        gap: 2rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 600;
        color: var(--primary-color);
    }

    .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .section-title {
        font-size: 1.25rem;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .info-label {
        color: var(--text-secondary);
        font-size: 0.875rem;
        font-weight: 500;
    }

    .info-value {
        color: var(--text-primary);
        font-size: 1rem;
        padding: 0.5rem;
        background: var(--bg-hover);
        border-radius: 0.375rem;
    }

    @media (max-width: 768px) {
        .profile-header-content {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .profile-avatar {
            margin: 0 auto;
        }

        .profile-stats {
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }
    }
";

// Incluir el layout base
include __DIR__ . '/layouts/main.php';
?>