<?php
session_start();
require_once '../../../config/database.php';

// Obtener información del profesor
$profesor_id = $_GET['profesor_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo 
    FROM profesores 
    WHERE id = ? AND estado = 'activo'
");
$stmt->execute([$profesor_id]);
$profesor = $stmt->fetch();

if (!$profesor) {
    header('Location: ../../users/list_teachers.php?error=Profesor no encontrado');
    exit();
}

// Obtener sedes
$sedes = $pdo->query("SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre")->fetchAll();

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

// Obtener asignaciones actuales
$stmt = $pdo->prepare("
    SELECT 
        ap.id as asignacion_id,
        g.nombre as grado_nombre,
        g.nivel,
        s.nombre as sede_nombre
    FROM asignaciones_profesor ap
    INNER JOIN grados g ON ap.grado_id = g.id
    INNER JOIN sedes s ON g.sede_id = s.id
    WHERE ap.profesor_id = ? AND ap.estado = 'activo'
    ORDER BY s.nombre, g.nivel, g.nombre
");
$stmt->execute([$profesor_id]);
$asignaciones = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Niveles</title>
    <link rel="stylesheet" href="../../../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: transparent;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .grados-container {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
        }
        .grado-item {
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 4px;
            background: #f8f9fa;
        }
        .grado-item:hover {
            background: #e9ecef;
        }
        .btn-primary {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1rem;
        }
        .btn-primary:hover {
            background-color: #27ae60;
        }
        .asignaciones-actuales {
            margin-bottom: 20px;
        }
        .asignacion-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .btn-delete {
            color: #dc3545;
            cursor: pointer;
            background: none;
            border: none;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>Asignar Niveles - <?php echo htmlspecialchars($profesor['nombre_completo']); ?></h3>
        
        <?php if (!empty($asignaciones)): ?>
        <div class="asignaciones-actuales">
            <h4>Asignaciones Actuales</h4>
            <?php foreach ($asignaciones as $asignacion): ?>
                <div class="asignacion-item">
                    <span>
                        <?php echo htmlspecialchars($asignacion['sede_nombre']); ?> - 
                        <?php echo htmlspecialchars($asignacion['nivel']); ?> - 
                        <?php echo htmlspecialchars($asignacion['grado_nombre']); ?>
                    </span>
                    <button class="btn-delete" onclick="eliminarAsignacion(<?php echo $asignacion['asignacion_id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form id="assignmentForm" method="POST" action="save_assignment.php">
            <input type="hidden" name="profesor_id" value="<?php echo $profesor_id; ?>">
            
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

            <div class="form-group">
                <label>Grados Disponibles</label>
                <div id="gradosList" class="grados-container">
                    <!-- Los grados se cargarán aquí dinámicamente -->
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Asignación</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sedeSelect = document.getElementById('sedeSelect');
            const nivelSelect = document.getElementById('nivelSelect');
            const gradosList = document.getElementById('gradosList');

            function cargarGrados() {
                const sede_id = sedeSelect.value;
                const nivel = nivelSelect.value;

                if (!sede_id || !nivel) {
                    gradosList.innerHTML = '<div class="grado-item">Seleccione una sede y un nivel</div>';
                    return;
                }

                fetch(`get_grupos.php?sede_id=${sede_id}&nivel=${nivel}`)
                    .then(response => response.json())
                    .then(grados => {
                        if (grados.length === 0) {
                            gradosList.innerHTML = '<div class="grado-item">No hay grados disponibles</div>';
                            return;
                        }

                        gradosList.innerHTML = grados.map(grado => `
                            <div class="grado-item">
                                <label>
                                    <input type="radio" name="grado_id" value="${grado.id}" required>
                                    ${grado.nombre}
                                </label>
                            </div>
                        `).join('');
                    });
            }

            sedeSelect.addEventListener('change', cargarGrados);
            nivelSelect.addEventListener('change', cargarGrados);
        });

        function eliminarAsignacion(id) {
            if (confirm('¿Está seguro de eliminar esta asignación?')) {
                fetch('delete_assignment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar la asignación');
                    }
                });
            }
        }

        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);

            fetch('save_assignment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recargar la página para mostrar la nueva asignación
                    window.location.reload();
                } else {
                    alert(data.error || 'Error al guardar la asignación');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        });
    </script>
</body>
</html> 