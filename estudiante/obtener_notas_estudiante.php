<?php
// Agregar al principio del archivo para verificar acceso
error_log("obtener_notas_estudiante.php fue accedido con parámetros: " . json_encode($_GET));

session_start();
require_once '../config/database.php';

// Configuración de headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Constantes
const CATEGORIAS = [
    'TAREAS' => ['peso' => 0.4, 'nombre' => 'Tareas, Trabajos, Cuadernos'],
    'EVALUACIONES' => ['peso' => 0.5, 'nombre' => 'Evaluaciones'],
    'AUTOEVALUACION' => ['peso' => 0.1, 'nombre' => 'Auto Evaluación']
];

// Función para validar la sesión
function validarSesion() {
    if (!isset($_SESSION['estudiante_id'])) {
        throw new Exception('No autorizado');
    }
}

// Función para validar el ID de asignación
function validarAsignacionId($id) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        throw new Exception('ID de asignación no válido');
    }
    return $id;
}

// Función para obtener tipos de notas
function obtenerTiposNotas($pdo, $asignacion_id) {
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
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener calificaciones
function obtenerCalificaciones($pdo, $estudiante_id, $asignacion_id) {
    $stmt = $pdo->prepare("
        SELECT 
            c.tipo_nota_id, 
            c.valor,
            tn.nombre,
            tn.porcentaje,
            COALESCE(tn.categoria, 'TAREAS') as categoria
        FROM calificaciones c
        INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
        WHERE c.estudiante_id = ?
        AND tn.asignacion_id = ?
        AND c.estado = 'activo'
        AND tn.estado = 'activo'
    ");
    $stmt->execute([$estudiante_id, $asignacion_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para calcular promedios por categoría
function calcularPromediosCategoria($tipos_notas, $calificaciones) {
    $promedios = array_fill_keys(array_keys(CATEGORIAS), ['nota' => 0, 'porcentaje_usado' => 0, 'porcentaje_total' => 0]);
    
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
            $promedios[$categoria]['nota'] = $nota_categoria / $porcentaje_usado;
        }
        $promedios[$categoria]['porcentaje_usado'] = $porcentaje_usado;
        $promedios[$categoria]['porcentaje_total'] = $porcentaje_total;
    }
    
    return $promedios;
}

// Función para calcular la nota definitiva
function calcularNotaDefinitiva($promedios_categoria) {
    $definitiva = 0;
    $peso_aplicado = 0;
    
    foreach ($promedios_categoria as $categoria => $datos) {
        if ($datos['porcentaje_usado'] > 0) {
            $definitiva += $datos['nota'] * CATEGORIAS[$categoria]['peso'];
            $peso_aplicado += CATEGORIAS[$categoria]['peso'];
        }
    }
    
    return $peso_aplicado > 0 ? $definitiva / $peso_aplicado : 0;
}

// Función para verificar si los porcentajes están completos
function verificarPorcentajesCompletos($promedios_categoria) {
    foreach ($promedios_categoria as $datos) {
        if ($datos['porcentaje_usado'] < $datos['porcentaje_total'] || $datos['porcentaje_total'] == 0) {
            return false;
        }
    }
    return true;
}

try {
    // Validar sesión y datos de entrada
    validarSesion();
    $asignacion_id = validarAsignacionId($_GET['asignacion_id'] ?? 0);
    
    // Obtener datos
    $tipos_notas_raw = obtenerTiposNotas($pdo, $asignacion_id);
    $calificaciones_raw = obtenerCalificaciones($pdo, $_SESSION['estudiante_id'], $asignacion_id);
    
    // Organizar datos
    $tipos_notas = array_fill_keys(array_keys(CATEGORIAS), []);
    foreach ($tipos_notas_raw as $tipo) {
        $categoria = $tipo['categoria'];
        if (isset($tipos_notas[$categoria])) {
            $tipos_notas[$categoria][] = $tipo;
        }
    }
    
    $calificaciones = array_column($calificaciones_raw, 'valor', 'tipo_nota_id');
    
    // Calcular promedios y notas
    $promedios_categoria = calcularPromediosCategoria($tipos_notas, $calificaciones);
    $definitiva = calcularNotaDefinitiva($promedios_categoria);
    $porcentaje_completo = verificarPorcentajesCompletos($promedios_categoria);
    
    // Preparar respuesta
    $notas_respuesta = array_map(function($tipo) use ($calificaciones) {
        return [
            'id' => $tipo['id'],
            'nombre' => $tipo['nombre'],
            'porcentaje' => $tipo['porcentaje'],
            'categoria' => $tipo['categoria'],
            'valor' => $calificaciones[$tipo['id']] ?? null
        ];
    }, $tipos_notas_raw);
    
    // Enviar respuesta
    echo json_encode([
        'success' => true,
        'notas' => $notas_respuesta,
        'definitiva' => round($definitiva, 1),
        'promedios_categoria' => $promedios_categoria,
        'porcentaje_completo' => $porcentaje_completo
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_notas_estudiante.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}