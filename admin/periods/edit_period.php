<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if(!isset($_GET['id'])) {
    error_log("No se proporcionó ID del periodo");
    header('Location: list_periods.php');
    exit();
}

$periodo_id = intval($_GET['id']);
error_log("Intentando editar periodo con ID: " . $_GET['id']);

try {
    // Obtener el periodo a editar
    $stmt = $pdo->prepare("
        SELECT 
            pa.id,
            pa.ano_lectivo_id,
            pa.numero_periodo,
            pa.fecha_inicio,
            pa.fecha_fin,
            pa.porcentaje_calificacion,
            pa.estado_periodo,
            pa.estado,
            al.nombre as ano_lectivo_nombre
        FROM periodos_academicos pa
        JOIN anos_lectivos al ON pa.ano_lectivo_id = al.id
        WHERE pa.id = ? 
        AND pa.estado = 'activo'
    ");
    
    $stmt->execute([$periodo_id]);
    $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug
    error_log("SQL Query: " . $stmt->queryString);
    error_log("Periodo ID buscado: " . $periodo_id);
    error_log("Datos del periodo: " . print_r($periodo, true));

    if ($periodo) {
        error_log("Periodo encontrado: " . print_r($periodo, true));
    } else {
        error_log("No se encontró el periodo con ID: " . $_GET['id']);
        header('Location: list_periods.php');
        exit();
    }

    // Obtener años lectivos para el select
    $stmt = $pdo->query("SELECT id, nombre FROM anos_lectivos WHERE estado = 'activo' ORDER BY nombre DESC");
    $anos_lectivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error al cargar periodo: " . $e->getMessage());
    header('Location: list_periods.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Periodo - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .edit-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 2rem auto;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .estado-badge {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .estado-badge.en_curso {
            background-color: #3b82f6;
            color: white;
        }

        .estado-badge.finalizado {
            background-color: #10b981;
            color: white;
        }

        .estado-badge.cerrado {
            background-color: #6b7280;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../components/topbar.php'; ?>

            <div class="content">
                <form class="edit-form" id="editPeriodoForm" onsubmit="return actualizarPeriodo(event)">
                    <div class="form-header">
                        <h2><i class="fas fa-edit"></i> Editar Periodo</h2>
                        <div class="estado-badge <?php echo $periodo['estado_periodo']; ?>">
                            <i class="fas fa-<?php echo $periodo['estado_periodo'] === 'en_curso' ? 'clock' : ($periodo['estado_periodo'] === 'finalizado' ? 'check-circle' : 'lock'); ?>"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $periodo['estado_periodo'])); ?>
                        </div>
                    </div>

                    <input type="hidden" name="id" value="<?php echo $periodo['id']; ?>">

                    <div class="form-group">
                        <label>Año Lectivo</label>
                        <select name="ano_lectivo_id" class="form-control" required>
                            <?php foreach ($anos_lectivos as $ano): ?>
                                <option value="<?php echo $ano['id']; ?>" 
                                    <?php echo $ano['id'] == $periodo['ano_lectivo_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ano['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Número de Periodo</label>
                        <input type="number" name="numero_periodo" class="form-control" 
                               value="<?php echo $periodo['numero_periodo']; ?>" required min="1" max="4">
                    </div>

                    <div class="form-group">
                        <label>Fecha de Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" 
                               value="<?php echo $periodo['fecha_inicio']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" 
                               value="<?php echo $periodo['fecha_fin']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Porcentaje de Calificación (%)</label>
                        <input type="number" name="porcentaje" class="form-control" 
                               value="<?php echo $periodo['porcentaje_calificacion']; ?>" 
                               required min="0" max="100" step="0.01">
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="list_periods.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    function actualizarPeriodo(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const data = {};
        formData.forEach((value, key) => data[key] = value);

        // Validar fechas
        const fechaInicio = new Date(data.fecha_inicio);
        const fechaFin = new Date(data.fecha_fin);
        
        if (fechaFin <= fechaInicio) {
            alert('La fecha de fin debe ser posterior a la fecha de inicio');
            return false;
        }

        fetch('update_period.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Periodo actualizado correctamente');
                window.location.href = 'list_periods.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el periodo');
        });

        return false;
    }
    </script>
</body>
</html> 