<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

$grupo_id = $_GET['id'] ?? null;
$nivel_id = $_GET['nivel_id'] ?? null;

if (!$grupo_id) {
    header('Location: ../../users/list_headquarters.php');
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre']);
        $grado = trim($_POST['grado']);
        $capacidad = (int)$_POST['capacidad'];
        $estado = $_POST['estado'];
        $sede_id = (int)$_POST['sede_id'];
        $nivel_id = (int)$_POST['nivel_id'];

        $sql = "UPDATE grupos SET nombre = ?, grado = ?, capacidad = ?, estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$nombre, $grado, $capacidad, $estado, $grupo_id])) {
            header('Location: view_level.php?sede_id='.$sede_id.'&nivel_id='.$nivel_id); exit();
        }
    }

    $sql = "SELECT g.*, s.nombre as sede_nombre, n.nivel, n.id as nivel_id FROM grupos g JOIN sedes s ON g.sede_id = s.id JOIN niveles_sede n ON g.sede_id = n.sede_id AND g.nivel = n.nivel WHERE g.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$grupo_id]);
    $grupo = $stmt->fetch();

    if (!$grupo) {
        header('Location: ../../users/list_headquarters.php');
        exit();
    }

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Grupo - <?php echo htmlspecialchars($grupo['nombre']); ?></title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../../assets/css/variables.css">
    <link rel="stylesheet" href="../../../assets/css/common.css">
    <link rel="stylesheet" href="../../../assets/css/layouts.css">
    <link rel="stylesheet" href="../../../assets/css/components/top_bar.css">
    <link rel="stylesheet" href="../../../assets/css/pages/edit_group.css">
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../../sidebar.php'; ?>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-info">
                    <div class="page-title">
                        <i class="fas fa-users"></i>
                        Editar Grupo
                    </div>
                    <div class="page-subtitle">
                        <?php echo htmlspecialchars($grupo['sede_nombre']); ?> - 
                        Nivel <?php echo ucfirst($grupo['nivel']); ?>
                    </div>
                </div>

                <div class="top-bar-actions">
                    <div class="time-display">
                        <i class="far fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <a href="view_level.php?sede_id=<?php echo $grupo['sede_id']; ?>&nivel_id=<?php echo $nivel_id; ?>" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </div>

            <div class="edit-group-content">
                <div class="edit-form-container">
                    <form method="POST" class="edit-form">
                        <input type="hidden" name="sede_id" value="<?php echo $grupo['sede_id']; ?>">
                        <input type="hidden" name="nivel_id" value="<?php echo $nivel_id; ?>">

                        <div class="form-group">
                            <label for="nombre">Nombre del Grupo</label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($grupo['nombre']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="grado">Grado</label>
                            <input type="text" id="grado" name="grado" value="<?php echo htmlspecialchars($grupo['grado']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="capacidad">Capacidad</label>
                            <input type="number" id="capacidad" name="capacidad" value="<?php echo $grupo['capacidad']; ?>" min="1" max="100" required>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado" required>
                                <option value="activo" <?php echo $grupo['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactivo" <?php echo $grupo['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i>
                                Guardar Cambios
                            </button>
                            <a href="view_level.php?sede_id=<?php echo $grupo['sede_id']; ?>&nivel_id=<?php echo $nivel_id; ?>" class="btn-cancel">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Acciones adicionales -->
                <div class="additional-actions">
                    <a href="view_group.php?id=<?php echo $grupo_id; ?>" class="btn-action">
                        <i class="fas fa-users"></i>
                        Gestionar Estudiantes
                    </a>
                    <a href="group_schedule.php?id=<?php echo $grupo_id; ?>" class="btn-action">
                        <i class="fas fa-calendar-alt"></i>
                        Horario del Grupo
                    </a>
                    <a href="group_subjects.php?id=<?php echo $grupo_id; ?>" class="btn-action">
                        <i class="fas fa-book"></i>
                        Asignaturas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Actualizar reloj
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html> 