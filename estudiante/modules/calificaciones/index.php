<?php
session_start();

// Variables para el layout
$page_title = 'Calificaciones';
$page_description = 'Consulta tus calificaciones y progreso académico';

if(!isset($_SESSION['estudiante_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

try {
    // Obtener información del estudiante
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.nombre as estudiante_nombre,
            e.apellido as estudiante_apellido,
            g.id as grado_id,
            g.nombre as grado_nombre,
            g.nivel,
            s.nombre as sede_nombre
        FROM estudiantes e
        INNER JOIN grados g ON e.grado_id = g.id
        INNER JOIN sedes s ON g.sede_id = s.id
        WHERE e.id = ? AND e.estado = 'Activo'
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener materias y calificaciones
    $stmt = $pdo->prepare("
        SELECT 
            m.id as materia_id,
            m.nombre as materia_nombre,
            ap.id as asignacion_id,
            p.nombre as profesor_nombre,
            g.nombre as grado_nombre,
            g.nivel,
            (
                SELECT ROUND(
                    SUM(c.valor * (tn2.porcentaje / 100)), 1)
                FROM calificaciones c
                INNER JOIN tipos_notas tn2 ON c.tipo_nota_id = tn2.id
                WHERE c.estudiante_id = ? 
                AND tn2.asignacion_id = ap.id
                AND tn2.estado = 'activo'
                AND c.estado = 'activo'
            ) as promedio
        FROM materias m
        INNER JOIN asignaciones_profesor ap ON m.id = ap.materia_id
        INNER JOIN profesores p ON ap.profesor_id = p.id
        INNER JOIN grados g ON ap.grado_id = g.id
        WHERE ap.grado_id = ?
        AND ap.estado = 'activo'
        ORDER BY m.nombre
    ");
    
    $stmt->execute([$_SESSION['estudiante_id'], $estudiante['grado_id']]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error en calificaciones/index.php: " . $e->getMessage());
    $error_message = "Error al cargar los datos";
    $materias = [];
}

// Contenido específico de la página
ob_start(); 
?>

<div class="grades-container">
    <!-- Información del estudiante -->
    <div class="student-info">
        <div class="info-card">
            <h2><?php echo htmlspecialchars($estudiante['estudiante_nombre'] . ' ' . $estudiante['estudiante_apellido']); ?></h2>
            <div class="info-details">
                <span class="info-item">
                    <i class="fas fa-school"></i>
                    Sede: <?php echo htmlspecialchars($estudiante['sede_nombre']); ?>
                </span>
                <span class="info-item">
                    <i class="fas fa-graduation-cap"></i>
                    Grado: <?php echo htmlspecialchars($estudiante['grado_nombre']); ?>
                </span>
                <span class="info-item">
                    <i class="fas fa-layer-group"></i>
                    Nivel: <?php echo htmlspecialchars(ucfirst($estudiante['nivel'])); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Grid de Asignaturas -->
    <div class="asignaturas-grid">
        <?php foreach ($materias as $materia): ?>
            <div class="asignatura-card">
                <div class="asignatura-header">
                    <h3><?php echo htmlspecialchars($materia['materia_nombre']); ?></h3>
                    <span class="profesor">Prof. <?php echo htmlspecialchars($materia['profesor_nombre']); ?></span>
                </div>
                <div class="asignatura-body">
                    <div class="promedio">
                        <span class="valor <?php echo ($materia['promedio'] >= 3.0) ? 'aprobado' : 'reprobado'; ?>">
                            <?php echo number_format($materia['promedio'], 1); ?>
                        </span>
                        <span class="label">Promedio</span>
                    </div>
                    <button class="ver-notas" 
                            onclick="mostrarNotas('<?php echo htmlspecialchars($materia['materia_nombre']); ?>', 
                                                <?php echo $materia['asignacion_id']; ?>)">
                        Ver Notas
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal para mostrar notas -->
    <div id="notasModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle"></h2>
            <div id="notasDetalle"></div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Estilos específicos de calificaciones
$page_styles = "
    .grades-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .student-info {
        margin-bottom: 2rem;
    }

    .info-card {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: var(--shadow-sm);
    }

    .info-details {
        display: flex;
        gap: 2rem;
        margin-top: 1rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-secondary);
    }

    .info-item i {
        color: var(--primary-color);
    }

    .asignaturas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .asignatura-card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .asignatura-header {
        padding: 1.5rem;
        background: var(--primary-color);
        color: white;
    }

    .asignatura-header h3 {
        margin: 0;
        font-size: 1.25rem;
    }

    .profesor {
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .asignatura-body {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .promedio {
        text-align: center;
    }

    .promedio .valor {
        font-size: 2rem;
        font-weight: bold;
        display: block;
    }

    .promedio .label {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    .ver-notas {
        padding: 0.5rem 1rem;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .ver-notas:hover {
        background: var(--primary-hover);
    }

    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 2rem;
        width: 90%;
        max-width: 600px;
        border-radius: 0.5rem;
        position: relative;
    }

    .close {
        position: absolute;
        right: 1rem;
        top: 1rem;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .nota-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }

    .nota-nombre {
        color: var(--text-secondary);
    }

    .nota-valor {
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 4px;
        min-width: 50px;
        text-align: center;
    }

    .aprobado {
        color: #059669;
        background: #ecfdf5;
    }

    .reprobado {
        color: #dc2626;
        background: #fef2f2;
    }

    .definitiva {
        margin-top: 1rem;
        border-top: 2px solid #eee;
        border-bottom: none !important;
    }

    .definitiva .nota-nombre {
        font-size: 1.1rem;
    }

    .definitiva .nota-valor {
        font-size: 1.1rem;
    }

    @media (max-width: 768px) {
        .asignaturas-grid {
            grid-template-columns: 1fr;
        }

        .info-details {
            flex-direction: column;
            gap: 1rem;
        }

        .modal-content {
            width: 95%;
            margin: 2% auto;
        }
    }
";

// Scripts específicos de calificaciones
$page_scripts = "
    function mostrarNotas(nombreMateria, asignacionId) {
        document.getElementById('modalTitle').textContent = nombreMateria;
        const modal = document.getElementById('notasModal');
        const notasDetalle = document.getElementById('notasDetalle');
        
        modal.style.display = 'block';
        notasDetalle.innerHTML = '<div class=\"loading\">Cargando...</div>';
        
        fetch('../modules/calificaciones/obtener_notas.php?asignacion_id=' + asignacionId)
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.notas)) {
                    let html = '';
                    data.notas.forEach(function(nota) {
                        const valor = nota.valor === null ? 'N/A' : parseFloat(nota.valor).toFixed(1);
                        const valorClass = nota.valor === null ? '' : 
                                         (parseFloat(nota.valor) >= 3.0 ? 'aprobado' : 'reprobado');
                        
                        html += `
                            <div class='nota-item'>
                                <div class='nota-nombre'>
                                    \${nota.nombre} (\${nota.porcentaje}%)
                                </div>
                                <div class='nota-valor \${valorClass}'>
                                    \${valor}
                                </div>
                            </div>
                        `;
                    });

                    if (data.definitiva !== null) {
                        html += `
                            <div class='nota-item definitiva'>
                                <div class='nota-nombre'>
                                    <strong>Definitiva</strong>
                                </div>
                                <div class='nota-valor \${data.definitiva >= 3.0 ? 'aprobado' : 'reprobado'}'>
                                    <strong>\${data.definitiva.toFixed(1)}</strong>
                                </div>
                            </div>
                        `;
                    }

                    notasDetalle.innerHTML = html;
                } else {
                    notasDetalle.innerHTML = '<p class=\"info-message\">No hay notas registradas para esta materia</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notasDetalle.innerHTML = '<p class=\"error\">Error al cargar las notas</p>';
            });
    }

    // Cerrar modal
    document.querySelector('.close').onclick = function() {
        document.getElementById('notasModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('notasModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
";

// Incluir el layout base
include '../../../layouts/main.php';
?> 