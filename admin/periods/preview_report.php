<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Obtener parámetros
$sede_id = $_GET['sede_id'] ?? null;
$nivel = $_GET['nivel'] ?? null;
$grado = $_GET['grado'] ?? null;
$periodo_id = $_GET['periodo_id'] ?? null;

// Si es preview, obtener el período activo
if ($periodo_id === 'preview') {
    $stmtPeriodo = $pdo->query("
        SELECT id 
        FROM periodos_academicos 
        WHERE estado = 'activo' 
        ORDER BY fecha_inicio DESC 
        LIMIT 1
    ");
    $periodo_id = $stmtPeriodo->fetchColumn();
}

try {
    // Obtener información del grado
    $stmt = $pdo->prepare("
        SELECT g.*, s.nombre as sede_nombre 
        FROM grados g
        JOIN sedes s ON g.sede_id = s.id
        WHERE g.sede_id = ? AND g.nivel = ? AND g.nombre = ?
    ");
    $stmt->execute([$sede_id, $nivel, $grado]);
    $grado_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$grado_info) {
        throw new Exception('No se encontró el grado especificado');
    }

    // Obtener estudiantes del grado
    $stmt = $pdo->prepare("
        SELECT e.*, g.nombre as grado_nombre
        FROM estudiantes e
        JOIN grados g ON e.grado_id = g.id
        WHERE g.sede_id = ? 
        AND g.nivel = ? 
        AND g.nombre = ?
        AND e.estado = 'activo'
        ORDER BY e.apellido, e.nombre
        LIMIT 1
    ");
    $stmt->execute([$sede_id, $nivel, $grado]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante) {
        throw new Exception('No se encontraron estudiantes en este grado');
    }

    // Agregar debug para ver qué está pasando
    error_log("Periodo ID: " . $periodo_id); // Verificar que periodo_id sea correcto

    // Obtener información del periodo actual
    $stmt = $pdo->prepare("
        SELECT 
            pa.id,
            pa.nombre as periodo_nombre,
            pa.fecha_inicio,
            pa.fecha_fin,
            al.nombre as ano_lectivo_nombre
        FROM periodos_academicos pa
        JOIN anos_lectivos al ON pa.ano_lectivo_id = al.id
        WHERE pa.id = ?
    ");
    $stmt->execute([$periodo_id]);
    $periodo_actual = $stmt->fetch(PDO::FETCH_ASSOC);

    // Agregar debug para ver el periodo_id
    error_log("Periodo ID que se está usando: " . $periodo_id);

    // Consulta separada para verificar los desempeños
    $stmt = $pdo->prepare("
        SELECT d.*, m.nombre as materia_nombre 
        FROM desempenos d
        JOIN materias m ON d.asignatura_id = m.id
        WHERE d.periodo_id = ? 
        AND d.estado = 'activo'
    ");
    $stmt->execute([$periodo_id]);
    $desempenos_debug = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Desempeños encontrados para periodo $periodo_id: " . print_r($desempenos_debug, true));

    // Modificar la consulta para obtener correctamente los desempeños
    $stmt = $pdo->prepare("
        SELECT 
            m.nombre as materia,
            m.id as materia_id,
            CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre,
            GROUP_CONCAT(
                CONCAT(tn.nombre, ':', COALESCE(c.valor, 'N/A'), ':', tn.porcentaje)
                ORDER BY tn.nombre
            ) as notas_info,
            (
                SELECT GROUP_CONCAT(
                    CONCAT(d.tipo, ':', d.descripcion)
                )
                FROM desempenos d 
                WHERE d.asignatura_id = m.id 
                AND (d.periodo_id = :periodo_id OR d.periodo_id = 0)  -- Incluir periodo 0 como default
                AND d.estado = 'activo'
            ) as desempenos_info
        FROM materias m
        JOIN asignaciones_profesor ap ON m.id = ap.materia_id
        JOIN profesores p ON ap.profesor_id = p.id
        JOIN tipos_notas tn ON ap.id = tn.asignacion_id
        LEFT JOIN calificaciones c ON tn.id = c.tipo_nota_id 
            AND c.estudiante_id = :estudiante_id
        WHERE ap.grado_id = :grado_id
        AND tn.estado = 'activo'
        GROUP BY m.id, m.nombre, p.nombre, p.apellido
    ");

    $stmt->execute([
        ':periodo_id' => $periodo_id,
        ':estudiante_id' => $estudiante['id'],
        ':grado_id' => $estudiante['grado_id']
    ]);

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug para ver qué datos estamos obteniendo
    error_log("Resultados de la consulta: " . print_r($resultados, true));

    // Procesar los resultados
    $materias = [];
    $promedio_general = 0;
    $materias_perdidas = 0;

    foreach ($resultados as $row) {
        $materia_nombre = $row['materia'];
        error_log("Procesando materia: $materia_nombre");
        error_log("Desempeños info: " . $row['desempenos_info']);
        
        if (!isset($materias[$materia_nombre])) {
            // Calcular definitiva
            $notas_array = explode(',', $row['notas_info']);
            $definitiva = 0;
            $total_porcentaje = 0;
            $notas_parciales = [];

            foreach ($notas_array as $nota_info) {
                list($tipo, $valor, $porcentaje) = explode(':', $nota_info);
                if ($valor !== 'N/A') {
                    $nota_ponderada = $valor * ($porcentaje/100);
                    $definitiva += $nota_ponderada;
                    $total_porcentaje += $porcentaje;
                }
                $notas_parciales[] = [
                    'tipo' => $tipo,
                    'valor' => $valor,
                    'porcentaje' => $porcentaje
                ];
            }

            // Ajustar definitiva según el total de porcentaje
            if ($total_porcentaje > 0) {
                $definitiva = ($definitiva * 100) / $total_porcentaje;
            }

            $materias[$materia_nombre] = [
                'nombre' => $materia_nombre,
                'profesor' => $row['profesor_nombre'],
                'desempenos' => [],
                'notas_parciales' => $notas_parciales,
                'definitiva' => round($definitiva, 1),
                'desempeno' => determinarDesempeno($definitiva)
            ];

            // Determinar desempeño basado en la definitiva
            if ($definitiva >= 4.6) {
                $materias[$materia_nombre]['desempeno'] = 'superior';
            } elseif ($definitiva >= 4.0) {
                $materias[$materia_nombre]['desempeno'] = 'alto';
            } elseif ($definitiva >= 3.0) {
                $materias[$materia_nombre]['desempeno'] = 'basico';
            } else {
                $materias[$materia_nombre]['desempeno'] = 'bajo';
                $materias_perdidas++;
            }

            $promedio_general += $definitiva;
        }
        
        // Debug para ver los desempeños de cada materia
        error_log("Desempeños para materia {$row['materia']}: " . $row['desempenos_info']);
        
        if (!empty($row['desempenos_info'])) {
            $desempenos_array = explode(',', $row['desempenos_info']);
            error_log("Desempeños array: " . print_r($desempenos_array, true));
            
            foreach ($desempenos_array as $desempeno_info) {
                error_log("Procesando desempeño: $desempeno_info");
                list($tipo, $descripcion) = explode(':', $desempeno_info);
                $materias[$materia_nombre]['desempenos'][] = [
                    'tipo' => $tipo,
                    'descripcion' => $descripcion
                ];
            }
        }
    }

    // Calcular promedio general
    $promedio_general = count($materias) > 0 ? 
        round($promedio_general / count($materias), 1) : 0;

    // Calcular puesto en el curso
    $stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as puesto
        FROM (
            SELECT e2.id, 
                   AVG(CASE WHEN c2.valor IS NOT NULL THEN c2.valor ELSE 0 END) as promedio
            FROM estudiantes e2
            LEFT JOIN calificaciones c2 ON e2.id = c2.estudiante_id
            WHERE e2.grado_id = ?
            GROUP BY e2.id
            HAVING promedio > ?
        ) rankings
    ");
    $stmt->execute([$estudiante['grado_id'], $promedio_general]);
    $puesto = $stmt->fetch(PDO::FETCH_ASSOC)['puesto'];

    // Preparar datos para la plantilla
    $datos_boletin = [
        'estudiante' => $estudiante,
        'grado_info' => $grado_info,
        'materias' => $materias,
        'estadisticas' => [
            'promedio_general' => $promedio_general,
            'puesto' => $puesto,
            'asignaturas_perdidas' => $materias_perdidas
        ],
        'periodo_actual' => $periodo_actual
    ];

    // Incluir la plantilla del boletín
    include 'boletin_template.php';

} catch (Exception $e) {
    error_log("Error en preview_report.php: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

// Función auxiliar para determinar el desempeño
function determinarDesempeno($nota) {
    if ($nota >= 4.6) return 'superior';
    if ($nota >= 4.0) return 'alto';
    if ($nota >= 3.0) return 'basico';
    return 'bajo';
}

// Debug para verificar
error_log("Materias y sus desempeños: " . print_r($materias, true));
?> 