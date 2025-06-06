<?php
session_start();

// Variables para el layout
$page_title = 'Calificaciones';
$page_description = 'Consulta tus calificaciones y progreso académico';

if(!isset($_SESSION['estudiante_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

try {
    // Primero obtener la información del estudiante
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

    if (!$estudiante) {
        throw new Exception('No se encontró la información del estudiante');
    }

    // Obtener las materias asignadas al grado del estudiante
    $stmt = $pdo->prepare("
        SELECT 
            m.id as materia_id,
            m.nombre as materia_nombre,
            ap.id as asignacion_id,
            p.nombre as profesor_nombre,
            p.apellido as profesor_apellido,
            g.nombre as grado_nombre,
            g.nivel
        FROM materias m
        INNER JOIN asignaciones_profesor ap ON m.id = ap.materia_id
        INNER JOIN profesores p ON ap.profesor_id = p.id
        INNER JOIN grados g ON ap.grado_id = g.id
        WHERE ap.grado_id = ?
        AND ap.estado = 'activo'
        ORDER BY m.nombre
    ");
    
    $stmt->execute([$estudiante['grado_id']]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener promedio para cada materia
    foreach ($materias as &$materia) {
        // En lugar del cálculo simple, obtener el promedio usando la misma lógica de ponderación por categoría
        $asignacion_id = $materia['asignacion_id'];
        
        // 1. Obtener tipos de notas y calificaciones para esta asignación
        $stmt = $pdo->prepare("
            SELECT 
                tn.id, 
                tn.nombre, 
                tn.porcentaje,
                COALESCE(tn.categoria, 'TAREAS') as categoria,
                c.valor
            FROM tipos_notas tn
            LEFT JOIN calificaciones c ON tn.id = c.tipo_nota_id 
                AND c.estudiante_id = ? 
                AND c.estado = 'activo'
            WHERE tn.asignacion_id = ?
            AND tn.estado = 'activo'
        ");
        $stmt->execute([$_SESSION['estudiante_id'], $asignacion_id]);
        $datos_notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Agrupar por categoría
        $notas_por_categoria = [
            'TAREAS' => [],
            'EVALUACIONES' => [],
            'AUTOEVALUACION' => []
        ];
        
        foreach ($datos_notas as $nota) {
            $categoria = $nota['categoria'];
            if (!isset($notas_por_categoria[$categoria])) {
                $notas_por_categoria[$categoria] = [];
            }
            $notas_por_categoria[$categoria][] = $nota;
        }
        
        // 3. Calcular promedio por categoría
        $promedios_categoria = [
            'TAREAS' => ['nota' => 0, 'porcentaje_usado' => 0, 'porcentaje_total' => 0],
            'EVALUACIONES' => ['nota' => 0, 'porcentaje_usado' => 0, 'porcentaje_total' => 0],
            'AUTOEVALUACION' => ['nota' => 0, 'porcentaje_usado' => 0, 'porcentaje_total' => 0]
        ];
        
        foreach ($notas_por_categoria as $categoria => $notas) {
            $suma_ponderada = 0;
            $suma_porcentajes = 0;
            $porcentaje_total = 0;
            
            foreach ($notas as $nota) {
                $porcentaje_total += floatval($nota['porcentaje']);
                
                if ($nota['valor'] !== null) {
                    $valor = floatval($nota['valor']);
                    $porcentaje = floatval($nota['porcentaje']);
                    
                    $suma_ponderada += ($valor * $porcentaje);
                    $suma_porcentajes += $porcentaje;
                }
            }
            
            if ($suma_porcentajes > 0) {
                $promedios_categoria[$categoria]['nota'] = $suma_ponderada / $suma_porcentajes;
                $promedios_categoria[$categoria]['porcentaje_usado'] = $suma_porcentajes;
            }
            $promedios_categoria[$categoria]['porcentaje_total'] = $porcentaje_total;
        }
        
        // 4. Calcular promedio final con ponderación 40/50/10
        // Reemplaza la sección del cálculo de promedio en la línea ~93-146 con este código:

// Constantes de ponderación para categorías (exactamente igual que en obtener_notas_estudiante.php)
$pesos_categoria = [
    'TAREAS' => 0.4, // 40%
    'EVALUACIONES' => 0.5, // 50%
    'AUTOEVALUACION' => 0.1 // 10%
];

// Obtener tipos de notas
$stmt = $pdo->prepare("
    SELECT 
        tn.id, 
        tn.nombre, 
        tn.porcentaje,
        COALESCE(tn.categoria, 'TAREAS') as categoria
    FROM tipos_notas tn
    WHERE tn.asignacion_id = ?
    AND tn.estado = 'activo'
    ORDER BY tn.categoria, tn.nombre
");
$stmt->execute([$asignacion_id]);
$tipos_notas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener calificaciones
$stmt = $pdo->prepare("
    SELECT 
        c.tipo_nota_id, 
        c.valor
    FROM calificaciones c
    INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
    WHERE c.estudiante_id = ?
    AND tn.asignacion_id = ?
    AND c.estado = 'activo'
    AND tn.estado = 'activo'
");
$stmt->execute([$_SESSION['estudiante_id'], $asignacion_id]);
$calificaciones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convertir a formato utilizable
$calificaciones = [];
foreach ($calificaciones_raw as $nota) {
    $calificaciones[$nota['tipo_nota_id']] = $nota['valor'];
}

// Organizar tipos de notas por categoría
$tipos_notas = [
    'TAREAS' => [],
    'EVALUACIONES' => [],
    'AUTOEVALUACION' => []
];

foreach ($tipos_notas_raw as $tipo) {
    $categoria = $tipo['categoria'];
    if (isset($tipos_notas[$categoria])) {
        $tipos_notas[$categoria][] = $tipo;
    }
}

// Calcular promedios por categoría
$promedios_categoria = [
    'TAREAS' => ['nota' => 0, 'porcentaje_usado' => 0, 'porcentaje_total' => 0],
    'EVALUACIONES' => ['nota' => 0, 'porcentaje_usado' => 0, 'porcentaje_total' => 0],
    'AUTOEVALUACION' => ['nota' => 0, 'porcentaje_usado' => 0, 'porcentaje_total' => 0]
];

foreach ($tipos_notas as $categoria => $tipos) {
    $nota_categoria = 0;
    $porcentaje_usado = 0;
    $porcentaje_total = 0;
    
    foreach ($tipos as $tipo) {
        $porcentaje_total += floatval($tipo['porcentaje']);
        
        if (isset($calificaciones[$tipo['id']])) {
            $valor = floatval($calificaciones[$tipo['id']]);
            $porcentaje = floatval($tipo['porcentaje']);
            
            $nota_categoria += ($valor * $porcentaje);
            $porcentaje_usado += $porcentaje;
        }
    }
    
    if ($porcentaje_usado > 0) {
        $promedios_categoria[$categoria]['nota'] = $nota_categoria / $porcentaje_usado;
    }
    $promedios_categoria[$categoria]['porcentaje_usado'] = $porcentaje_usado;
    $promedios_categoria[$categoria]['porcentaje_total'] = $porcentaje_total;
}

// Calcular promedio final
$definitiva = 0;
$peso_aplicado = 0;

foreach ($promedios_categoria as $categoria => $datos) {
    if ($datos['porcentaje_usado'] > 0) {
        $definitiva += $datos['nota'] * $pesos_categoria[$categoria];
        $peso_aplicado += $pesos_categoria[$categoria];
    }
}

$promedio_final = ($peso_aplicado > 0) ? ($definitiva / $peso_aplicado) : 0;
$porcentaje_completo = true;

foreach ($promedios_categoria as $categoria => $datos) {
    if ($datos['porcentaje_usado'] < $datos['porcentaje_total'] || $datos['porcentaje_total'] == 0) {
        $porcentaje_completo = false;
    }
}

// Asignar el promedio calculado a la materia
$materia['promedio'] = number_format($promedio_final, 1);
$materia['porcentaje_completo'] = $porcentaje_completo;
    }
    unset($materia);

} catch(Exception $e) {
    error_log("Error en calificaciones.php: " . $e->getMessage());
    $error_message = $e->getMessage();
    $materias = [];
}

// Contenido específico de la página
ob_start(); 
?>

<!-- HTML específico de calificaciones -->
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

    <h1 class="page-title">Registro de Calificaciones</h1>

    <!-- Sección de asignaturas -->
    <div class="asignaturas-section">
        <h2>Mis Asignaturas</h2>
        <div class="asignaturas-grid">
            <?php if (empty($materias)): ?>
                <div class="empty-state">
                    <i class="fas fa-books"></i>
                    <p>No hay asignaturas asignadas para tu grado en este momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($materias as $materia): ?>
                    <div class="asignatura-card">
                        <div class="asignatura-info">
                            <div class="asignatura-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="asignatura-details">
                                <h3><?php echo htmlspecialchars($materia['materia_nombre']); ?></h3>
                                <p class="profesor-info">
                                    <i class="fas fa-user-tie"></i>
                                    Prof. <?php echo htmlspecialchars($materia['profesor_nombre'] . ' ' . $materia['profesor_apellido']); ?>
                                </p>
                                <p class="grado-info">
                                    <i class="fas fa-graduation-cap"></i>
                                    <?php echo htmlspecialchars($materia['grado_nombre']); ?> - 
                                    <?php echo htmlspecialchars(ucfirst($materia['nivel'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="asignatura-stats">
                            <div class="stat">
                                <span class="stat-label">Promedio</span>
                                <span class="stat-value <?php echo $materia['promedio'] >= 4.0 ? 'high' : ($materia['promedio'] >= 3.0 ? 'medium' : 'low'); ?>">
                                    <?php echo $materia['promedio']; ?>
                                </span>
                            </div>
                        </div>
                        <button onclick="obtenerNotas(<?php echo $materia['asignacion_id']; ?>, '<?php echo htmlspecialchars($materia['materia_nombre']); ?>')" 
                            class="btn-ver-calificaciones"
                            data-asignacion="<?php echo $materia['asignacion_id']; ?>"
                            data-materia="<?php echo htmlspecialchars($materia['materia_nombre']); ?>">
                            <i class="fas fa-chart-line"></i>
                            Ver Calificaciones
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de notas -->
    <div id="notasModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="notasDetalle"></div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Estilos específicos de calificaciones
$page_styles = "
    .student-info {
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
    }

    .info-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .info-card h2 {
        margin: 0 0 1rem 0;
        color: #2c3e50;
        font-size: 1.5rem;
    }

    .info-details {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
    }

    .info-item i {
        color: #3498db;
    }

    .asignaturas-section {
        padding: 1.5rem;
    }

    .asignaturas-section h2 {
        color: #2c3e50;
        margin-bottom: 1rem;
    }

    .asignaturas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .asignatura-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .asignatura-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .asignatura-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .asignatura-icon {
        width: 50px;
        height: 50px;
        background: #3498db;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .asignatura-details h3 {
        margin: 0;
        color: #2c3e50;
        font-size: 1.1rem;
    }

    .profesor-info {
        margin: 0.5rem 0 0;
        color: #64748b;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .grado-info {
        margin: 0.25rem 0 0;
        color: #64748b;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .asignatura-stats {
        padding: 1rem 0;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 1rem;
    }

    .stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .stat-label {
        color: #64748b;
    }

    .stat-value {
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
    }

    .stat-value.high { background: #dcfce7; color: #166534; }
    .stat-value.medium { background: #fef9c3; color: #854d0e; }
    .stat-value.low { background: #fee2e2; color: #991b1b; }

    .btn-ver-calificaciones {
        display: inline-block;
        background: #3498db;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        width: 100%;
        text-align: center;
    }

    .btn-ver-calificaciones:hover {
        background: #2980b9;
        transform: translateY(-2px);
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        border-radius: 10px;
        width: 80%;
        max-width: 700px;
        position: relative;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .modal-header {
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-radius: 10px 10px 0 0;
        border-bottom: 1px solid #dee2e6;
        position: relative;
    }

    .modal-header h2 {
        margin: 0;
        color: #2c3e50;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .close {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
    }

    .close:hover {
        color: #333;
    }

    .categoria-titulo {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 1rem 0 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
        color: #2c3e50;
    }

    .notas-categoria {
        margin-bottom: 1.5rem;
    }

    .categoria-porcentaje {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: normal;
    }

    .nota-item {
        display: flex;
        justify-content: space-between;
        padding: 12px;
        border-bottom: 1px solid #eee;
        align-items: center;
    }

    .nota-nombre {
        color: #4a5568;
        font-size: 0.95rem;
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

    .neutral {
        color: #4b5563;
        background: #f3f4f6;
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

    .promedio-categoria {
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        padding: 0.75rem;
        background: #f8fafc;
        border-radius: 8px;
        margin-top: 0.5rem;
    }

    .resumen-definitivo {
        margin-top: 2rem;
        padding: 1rem;
        background: #f1f5f9;
        border-radius: 8px;
        border-left: 4px solid #3498db;
        text-align: center;
    }

    .resumen-titulo {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #1e293b;
    }
    
    .nota-final {
        font-size: 2rem;
        font-weight: 700;
        padding: 0.5rem 2rem;
        display: inline-block;
        border-radius: 8px;
        margin-top: 0.5rem;
    }
    
    .loading-spinner {
        text-align: center;
        padding: 2rem;
        color: #64748b;
    }
    
    .info-message {
        text-align: center;
        padding: 2rem;
        color: #3b82f6;
        background: #eff6ff;
        border-radius: 8px;
    }
    
    .error-message {
        text-align: center;
        padding: 2rem;
        color: #dc2626;
        background: #fee2e2;
        border-radius: 8px;
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

// Script en línea
$page_scripts = <<<EOT
function obtenerNotas(asignacionId, materiaNombre) {
    var modal = document.getElementById('notasModal');
    var modalTitle = document.getElementById('modalTitle');
    var notasDetalle = document.getElementById('notasDetalle');
    
    modalTitle.innerText = materiaNombre;
    modal.style.display = 'block';
    notasDetalle.innerHTML = '<div class="loading-spinner">Cargando calificaciones...</div>';
    
    // Hacer la petición AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'obtener_notas_estudiante.php?asignacion_id=' + asignacionId, true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    var html = '';
                    var notasPorCategoria = {
                        'TAREAS': [],
                        'EVALUACIONES': [],
                        'AUTOEVALUACION': []
                    };
                    
                    // Organizar por categoría
                    response.notas.forEach(function(nota) {
                        var categoria = nota.categoria || 'TAREAS';
                        if (!notasPorCategoria[categoria]) {
                            notasPorCategoria[categoria] = [];
                        }
                        notasPorCategoria[categoria].push(nota);
                    });
                    // ... continuación del script en línea donde quedamos ...

                    // Mostrar las notas por categoría
                    if (notasPorCategoria.TAREAS.length > 0) {
                        html += '<div class="notas-categoria">';
                        html += '<h3 class="categoria-titulo">TAREAS, TRABAJOS, CUADERNOS <span class="categoria-porcentaje">(40%)</span></h3>';
                        
                        notasPorCategoria.TAREAS.forEach(function(nota) {
                            var valor = nota.valor ? parseFloat(nota.valor).toFixed(1) : 'N/A';
                            var valorClass = nota.valor ? (parseFloat(nota.valor) >= 3.0 ? 'aprobado' : 'reprobado') : 'neutral';
                            
                            html += '<div class="nota-item">';
                            html += '<div class="nota-nombre">' + nota.nombre + ' (' + nota.porcentaje + '%)</div>';
                            html += '<div class="nota-valor ' + valorClass + '">' + valor + '</div>';
                            html += '</div>';
                        });
                        
                        html += '</div>';
                    }
                    
                    if (notasPorCategoria.EVALUACIONES.length > 0) {
                        html += '<div class="notas-categoria">';
                        html += '<h3 class="categoria-titulo">EVALUACIONES <span class="categoria-porcentaje">(50%)</span></h3>';
                        
                        notasPorCategoria.EVALUACIONES.forEach(function(nota) {
                            var valor = nota.valor ? parseFloat(nota.valor).toFixed(1) : 'N/A';
                            var valorClass = nota.valor ? (parseFloat(nota.valor) >= 3.0 ? 'aprobado' : 'reprobado') : 'neutral';
                            
                            html += '<div class="nota-item">';
                            html += '<div class="nota-nombre">' + nota.nombre + ' (' + nota.porcentaje + '%)</div>';
                            html += '<div class="nota-valor ' + valorClass + '">' + valor + '</div>';
                            html += '</div>';
                        });
                        
                        html += '</div>';
                    }
                    
                    if (notasPorCategoria.AUTOEVALUACION.length > 0) {
                        html += '<div class="notas-categoria">';
                        html += '<h3 class="categoria-titulo">AUTO EVALUACIÓN <span class="categoria-porcentaje">(10%)</span></h3>';
                        
                        notasPorCategoria.AUTOEVALUACION.forEach(function(nota) {
                            var valor = nota.valor ? parseFloat(nota.valor).toFixed(1) : 'N/A';
                            var valorClass = nota.valor ? (parseFloat(nota.valor) >= 3.0 ? 'aprobado' : 'reprobado') : 'neutral';
                            
                            html += '<div class="nota-item">';
                            html += '<div class="nota-nombre">' + nota.nombre + ' (' + nota.porcentaje + '%)</div>';
                            html += '<div class="nota-valor ' + valorClass + '">' + valor + '</div>';
                            html += '</div>';
                        });
                        
                        html += '</div>';
                    }
                    
                    // Mostrar la definitiva
                    if (response.definitiva !== undefined) {
                        var notaClass = parseFloat(response.definitiva) >= 3.0 ? 'aprobado' : 'reprobado';
                        var porcentajeCompleto = response.porcentaje_completo || false;
                        var tituloNota = porcentajeCompleto ? 'Nota Final' : 'Nota Parcial';
                        
                        html += '<div class="resumen-definitivo">';
                        html += '<div class="resumen-titulo">' + tituloNota + '</div>';
                        html += '<div class="nota-final ' + notaClass + '">' + parseFloat(response.definitiva).toFixed(1) + '</div>';
                        html += '</div>';
                    }
                    
                    if (html === '') {
                        html = '<p class="info-message">No hay calificaciones registradas para esta materia.</p>';
                    }
                    
                    notasDetalle.innerHTML = html;
                } else {
                    notasDetalle.innerHTML = '<p class="error-message">' + (response.message || 'Error desconocido') + '</p>';
                    console.error('Error en la respuesta:', response);
                }
            } catch (e) {
                notasDetalle.innerHTML = '<p class="error-message">Error al procesar la respuesta del servidor</p>';
                console.error('Error al analizar JSON:', e, 'Respuesta:', xhr.responseText);
            }
        } else {
            notasDetalle.innerHTML = '<p class="error-message">Error al cargar calificaciones (Código: ' + xhr.status + ')</p>';
            console.error('Error HTTP:', xhr.status);
        }
    };
    
    xhr.onerror = function() {
        notasDetalle.innerHTML = '<p class="error-message">Error de conexión</p>';
        console.error('Error de conexión');
    };
    
    xhr.send();
}

// Cerrar modal
document.querySelector('.close').onclick = function() {
    document.getElementById('notasModal').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('notasModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
EOT;

// Agregar referencia al archivo de JavaScript externo
$additional_scripts = '<script src="assets/js/calificaciones.js"></script>';

// Incluir el layout base
include __DIR__ . '/layouts/main.php';
?>
