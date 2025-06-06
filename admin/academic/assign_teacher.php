<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $profesor_id = $_POST['profesor_id'];
        $grupo_id = $_POST['grupo_id'];
        $asignatura_id = $_POST['asignatura_id'];
        $periodo_id = $_POST['periodo_id'];

        // Verificar si ya existe la asignación
        $stmt = $pdo->prepare("
            SELECT id FROM asignaciones_profesor 
            WHERE profesor_id = ? AND grupo_id = ? AND asignatura_id = ? AND periodo_id = ?
        ");
        $stmt->execute([$profesor_id, $grupo_id, $asignatura_id, $periodo_id]);
        
        if ($stmt->fetch()) {
            throw new Exception('Esta asignación ya existe');
        }

        // Crear la asignación
        $stmt = $pdo->prepare("
            INSERT INTO asignaciones_profesor 
            (profesor_id, grupo_id, asignatura_id, periodo_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$profesor_id, $grupo_id, $asignatura_id, $periodo_id]);

        header('Location: list_assignments.php?success=Asignación creada exitosamente');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener listas para los selects
$profesores = $pdo->query("
    SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo 
    FROM profesores 
    WHERE estado = 'activo'
    ORDER BY nombre_completo
")->fetchAll();

// Obtener grupos con información completa
$grupos = $pdo->query("
    SELECT 
        g.id,
        CONCAT(g.grado, ' ', g.nombre, ' - ', g.nivel, ' (', s.nombre, ')') as nombre_completo
    FROM grupos g
    INNER JOIN sedes s ON g.sede_id = s.id
    WHERE g.estado = 'activo'
    ORDER BY s.nombre, g.nivel, g.grado, g.nombre
")->fetchAll();

$asignaturas = $pdo->query("
    SELECT id, nombre 
    FROM asignaturas 
    WHERE estado = 'activo'
    ORDER BY nombre
")->fetchAll();

$periodos = $pdo->query("
    SELECT 
        p.id,
        CONCAT(p.nombre, ' (', al.ano, ')') as nombre_completo
    FROM periodos_academicos p
    INNER JOIN anos_lectivos al ON p.ano_lectivo_id = al.id
    WHERE p.estado = 'activo'
    ORDER BY al.ano DESC, p.numero_periodo ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Profesor</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <div class="container">
        <h2>Asignar Profesor a Grupo</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="assignment-form">
            <div class="form-group">
                <label>Profesor</label>
                <select name="profesor_id" required class="form-control">
                    <option value="">Seleccione un profesor</option>
                    <?php foreach ($profesores as $profesor): ?>
                        <option value="<?php echo $profesor['id']; ?>">
                            <?php echo htmlspecialchars($profesor['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Grupo</label>
                <select name="grupo_id" required class="form-control">
                    <option value="">Seleccione un grupo</option>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?php echo $grupo['id']; ?>">
                            <?php echo htmlspecialchars($grupo['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Asignatura</label>
                <select name="asignatura_id" required class="form-control">
                    <option value="">Seleccione una asignatura</option>
                    <?php foreach ($asignaturas as $asignatura): ?>
                        <option value="<?php echo $asignatura['id']; ?>">
                            <?php echo htmlspecialchars($asignatura['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Periodo Académico</label>
                <select name="periodo_id" required class="form-control">
                    <option value="">Seleccione un periodo</option>
                    <?php foreach ($periodos as $periodo): ?>
                        <option value="<?php echo $periodo['id']; ?>">
                            <?php echo htmlspecialchars($periodo['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Asignación</button>
        </form>
    </div>
</body>
</html> 