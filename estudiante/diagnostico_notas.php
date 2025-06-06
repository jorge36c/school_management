<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['estudiante_id'])) {
    die("No has iniciado sesión");
}

$estudiante_id = $_SESSION['estudiante_id'];
$asignacion_id = isset($_GET['asignacion_id']) ? (int)$_GET['asignacion_id'] : 0;

if ($asignacion_id <= 0) {
    die("Asignación no válida");
}

echo "<h1>Diagnóstico de Calificaciones</h1>";
echo "<p>Estudiante ID: $estudiante_id</p>";
echo "<p>Asignación ID: $asignacion_id</p>";

// Verificar tipos de notas
$stmt = $pdo->prepare("
    SELECT id, nombre, porcentaje, categoria
    FROM tipos_notas
    WHERE asignacion_id = ? AND estado = 'activo'
");
$stmt->execute([$asignacion_id]);
$tipos_notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Tipos de Notas</h2>";
if (count($tipos_notas) > 0) {
    echo "<ul>";
    foreach ($tipos_notas as $tipo) {
        echo "<li>ID: {$tipo['id']} - {$tipo['nombre']} ({$tipo['porcentaje']}%) - Categoría: {$tipo['categoria']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No hay tipos de notas definidos para esta asignación</p>";
}

// Verificar calificaciones registradas
$stmt = $pdo->prepare("
    SELECT c.id, c.tipo_nota_id, c.valor, tn.nombre, tn.categoria
    FROM calificaciones c
    INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
    WHERE c.estudiante_id = ? AND tn.asignacion_id = ? AND c.estado = 'activo'
");
$stmt->execute([$estudiante_id, $asignacion_id]);
$calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Calificaciones Registradas</h2>";
if (count($calificaciones) > 0) {
    echo "<ul>";
    foreach ($calificaciones as $cal) {
        echo "<li>ID: {$cal['id']} - Tipo: {$cal['nombre']} - Valor: {$cal['valor']} - Categoría: {$cal['categoria']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No hay calificaciones registradas para este estudiante en esta asignación</p>";
}

// Verificar la configuración de la asignación
$stmt = $pdo->prepare("
    SELECT 
        ap.id, 
        ap.profesor_id, 
        ap.grado_id, 
        ap.materia_id,
        m.nombre as materia_nombre,
        p.nombre as profesor_nombre,
        p.apellido as profesor_apellido,
        g.nombre as grado_nombre
    FROM asignaciones_profesor ap
    INNER JOIN profesores p ON ap.profesor_id = p.id
    INNER JOIN materias m ON ap.materia_id = m.id
    INNER JOIN grados g ON ap.grado_id = g.id
    WHERE ap.id = ? AND ap.estado = 'activo'
");
$stmt->execute([$asignacion_id]);
$asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Información de la Asignación</h2>";
if ($asignacion) {
    echo "<p>ID: {$asignacion['id']}</p>";
    echo "<p>Materia: {$asignacion['materia_nombre']}</p>";
    echo "<p>Profesor: {$asignacion['profesor_nombre']} {$asignacion['profesor_apellido']}</p>";
    echo "<p>Grado: {$asignacion['grado_nombre']}</p>";
} else {
    echo "<p>No se encontró la asignación solicitada o no está activa</p>";
}

// Verificar que el estudiante pertenece al grado asociado a la asignación
if ($asignacion) {
    $stmt = $pdo->prepare("
        SELECT grado_id 
        FROM estudiantes 
        WHERE id = ? AND estado = 'Activo'
    ");
    $stmt->execute([$estudiante_id]);
    $estudiante_grado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Validación de Grado</h2>";
    if ($estudiante_grado) {
        echo "<p>Grado del estudiante: {$estudiante_grado['grado_id']}</p>";
        echo "<p>Grado de la asignación: {$asignacion['grado_id']}</p>";
        
        if ($estudiante_grado['grado_id'] == $asignacion['grado_id']) {
            echo "<p style='color:green;'>✓ El estudiante pertenece al grado correcto</p>";
        } else {
            echo "<p style='color:red;'>✗ El estudiante NO pertenece al grado asociado a esta asignación</p>";
        }
    } else {
        echo "<p style='color:red;'>No se pudo determinar el grado del estudiante o no está activo</p>";
    }
}
?>