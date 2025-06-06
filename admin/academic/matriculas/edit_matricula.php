<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

// Obtener ID de la matrícula
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$id) {
    header('Location: list_matriculas.php?error=ID de matrícula no válido');
    exit();
}

try {
    // Obtener datos de la matrícula
    $stmt = $pdo->prepare("SELECT m.*, 
            e.nombre as estudiante_nombre, 
            e.apellido as estudiante_apellido 
            FROM matriculas m 
            INNER JOIN estudiantes e ON m.estudiante_id = e.id 
            WHERE m.id = ?");
    $stmt->execute([$id]);
    $matricula = $stmt->fetch();

    if(!$matricula) {
        header('Location: list_matriculas.php?error=Matrícula no encontrada');
        exit();
    }

    // Obtener lista de estudiantes para el select
    $stmt = $pdo->query("SELECT id, nombre, apellido FROM estudiantes WHERE estado = 'Activo' ORDER BY nombre, apellido");
    $estudiantes = $stmt->fetchAll();

    // Procesar el formulario cuando se envía
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $estudiante_id = $_POST['estudiante_id'];
        $grado = $_POST['grado'];
        $periodo = $_POST['periodo'];
        $fecha_matricula = $_POST['fecha_matricula'];
        $estado = $_POST['estado'];
        $observaciones = $_POST['observaciones'];

        $sql = "UPDATE matriculas SET 
                estudiante_id = ?,
                grado = ?,
                periodo = ?,
                fecha_matricula = ?,
                estado = ?,
                observaciones = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$estudiante_id, $grado, $periodo, $fecha_matricula, $estado, $observaciones, $id])) {
            header('Location: list_matriculas.php?message=Matrícula actualizada exitosamente');
            exit();
        }
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
    <title>Editar Matrícula - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/admin.css">
    <style>
        /* Estilos específicos para el formulario */
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,...");
            background-repeat: no-repeat;
            background-position: right 8px center;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .status-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head> 
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Sistema Escolar</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="../../dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>Gestión de Usuarios</span>
                    </li>
                    <li>
                        <a href="../../users/list_teachers.php">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Profesores</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../users/list_students.php">
                            <i class="fas fa-user-graduate"></i>
                            <span>Estudiantes</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../users/list_parents.php">
                            <i class="fas fa-users"></i>
                            <span>Padres de Familia</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>SEDES</span>
                    </li>
                    <li>
                        <a href="../../users/list_headquarters.php">
                            <i class="fas fa-building"></i>
                            <span>Lista de Sedes</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>PLANEACIÓN ACADÉMICA</span>
                    </li>
                    <li>
                        <a href="../list_dba.php">
                            <i class="fas fa-book"></i>
                            <span>DBA</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>MATRÍCULAS</span>
                    </li>
                    <li>
                        <a href="list_matriculas.php" class="active">
                            <i class="fas fa-user-plus"></i>
                            <span>Matrículas</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <button id="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <i class="fas fa-user-plus"></i>
                        <span>/ Matrículas / Editar</span>
                    </div>
                </div>
                <div class="top-bar-right">
                    <div class="top-bar-time">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></span>
                            <span class="user-role">Administrador</span>
                        </div>
                        <div class="user-menu">
                            <a href="../../../auth/logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido Principal -->
            <div class="content-wrapper">
                <div class="form-container">
                    <div class="form-header">
                        <h2>Editar Matrícula</h2>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="estudiante_id">Estudiante:</label>
                            <select name="estudiante_id" id="estudiante_id" class="form-control" required>
                                <option value="">Seleccione un estudiante</option>
                                <?php foreach($estudiantes as $estudiante): ?>
                                    <option value="<?php echo $estudiante['id']; ?>" 
                                            <?php echo $estudiante['id'] == $matricula['estudiante_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="grado">Grado:</label>
                            <input type="text" id="grado" name="grado" class="form-control" 
                                   value="<?php echo htmlspecialchars($matricula['grado']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="periodo">Periodo:</label>
                            <input type="text" id="periodo" name="periodo" class="form-control" 
                                   value="<?php echo htmlspecialchars($matricula['periodo']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_matricula">Fecha de Matrícula:</label>
                            <input type="date" id="fecha_matricula" name="fecha_matricula" class="form-control" 
                                   value="<?php echo $matricula['fecha_matricula']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado:</label>
                            <select name="estado" id="estado" class="status-select" required>
                                <option value="Pendiente" <?php echo $matricula['estado'] == 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="Activa" <?php echo $matricula['estado'] == 'Activa' ? 'selected' : ''; ?>>Activa</option>
                                <option value="Inactiva" <?php echo $matricula['estado'] == 'Inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="observaciones">Observaciones:</label>
                            <textarea id="observaciones" name="observaciones" class="form-control"><?php 
                                echo htmlspecialchars($matricula['observaciones']); 
                            ?></textarea>
                        </div>

                        <div class="form-actions">
                            <a href="list_matriculas.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Actualizar reloj
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('current-time').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });
    </script>
</body>
</html>