<?php
session_start();
require_once '../../../config/database.php';

// Obtener sedes
$sedes = $pdo->query("SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre")->fetchAll();

// Obtener profesores
$profesores = $pdo->query("
    SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo 
    FROM profesores 
    WHERE estado = 'activo' 
    ORDER BY nombre
")->fetchAll();

// Obtener asignaturas
$asignaturas = $pdo->query("SELECT id, nombre FROM asignaturas WHERE estado = 'activo' ORDER BY nombre")->fetchAll();

// Obtener periodos activos
$periodos = $pdo->query("
    SELECT p.id, CONCAT(p.nombre, ' (', al.ano, ')') as nombre_completo
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
    <title>Asignación Académica</title>
    <link rel="stylesheet" href="../../../assets/css/admin.css">
    <style>
        .assignment-container {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .assignment-step {
            flex: 1;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .grupos-list {
            margin-top: 15px;
        }
        .grupo-item {
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .grupo-item:hover {
            background: #f5f5f5;
        }
        .grupo-item label {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Asignación Académica</h2>
        
        <form id="assignmentForm" method="POST" action="save_assignment.php">
            <div class="assignment-container">
                <!-- Paso 1: Selección inicial -->
                <div class="assignment-step">
                    <h3>Paso 1: Información Básica</h3>
                    
                    <div class="form-group">
                        <label>Profesor</label>
                        <select name="profesor_id" required class="form-control" id="profesorSelect">
                            <option value="">Seleccione un profesor</option>
                            <?php foreach ($profesores as $profesor): ?>
                                <option value="<?php echo $profesor['id']; ?>">
                                    <?php echo htmlspecialchars($profesor['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Sede</label>
                        <select name="sede_id" required class="form-control" id="sedeSelect">
                            <option value="">Seleccione una sede</option>
                            <?php foreach ($sedes as $sede): ?>
                                <option value="<?php echo $sede['id']; ?>">
                                    <?php echo htmlspecialchars($sede['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nivel</label>
                        <select name="nivel" required class="form-control" id="nivelSelect">
                            <option value="">Seleccione un nivel</option>
                            <option value="preescolar">Preescolar</option>
                            <option value="primaria">Primaria</option>
                            <option value="secundaria">Secundaria</option>
                            <option value="media">Media</option>
                        </select>
                    </div>
                </div>

                <!-- Paso 2: Grupos y Asignaturas -->
                <div class="assignment-step">
                    <h3>Paso 2: Selección de Grupos</h3>
                    <div id="gruposList" class="grupos-list">
                        <!-- Los grupos se cargarán aquí dinámicamente -->
                    </div>
                </div>

                <!-- Paso 3: Asignaturas y Periodo -->
                <div class="assignment-step">
                    <h3>Paso 3: Asignaturas y Periodo</h3>
                    
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
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Asignación</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sedeSelect = document.getElementById('sedeSelect');
            const nivelSelect = document.getElementById('nivelSelect');
            const gruposList = document.getElementById('gruposList');

            function cargarGrupos() {
                const sede_id = sedeSelect.value;
                const nivel = nivelSelect.value;

                if (!sede_id || !nivel) return;

                fetch(`get_grupos.php?sede_id=${sede_id}&nivel=${nivel}`)
                    .then(response => response.json())
                    .then(grupos => {
                        gruposList.innerHTML = grupos.map(grupo => `
                            <div class="grupo-item">
                                <label>
                                    <input type="checkbox" name="grupos[]" value="${grupo.id}">
                                    ${grupo.grado} ${grupo.nombre}
                                </label>
                            </div>
                        `).join('');
                    });
            }

            sedeSelect.addEventListener('change', cargarGrupos);
            nivelSelect.addEventListener('change', cargarGrupos);
        });
    </script>
</body>
</html> 