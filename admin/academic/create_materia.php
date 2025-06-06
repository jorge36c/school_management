<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Inicializar variables
$error = null;
$success = null;

// Obtener listas necesarias
try {
    $stmt = $pdo->prepare("SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre");
    $stmt->execute();
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM profesores WHERE estado = 'activo' ORDER BY nombre, apellido");
    $stmt->execute();
    $profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos
        $nombre = trim($_POST['nombre'] ?? '');
        $sede_id = (int)($_POST['sede_id'] ?? 0);
        $profesor_id = (int)($_POST['profesor_id'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $intensidad_horaria = (int)($_POST['intensidad_horaria'] ?? 1);

        // Validaciones
        $errors = [];
        if (empty($nombre)) $errors[] = "El nombre es requerido";
        if ($sede_id <= 0) $errors[] = "Debe seleccionar una sede";
        if ($profesor_id <= 0) $errors[] = "Debe seleccionar un profesor";

        if (!empty($errors)) {
            throw new Exception(implode('<br>', $errors));
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Insertar materia en la tabla asignaturas
        $stmt = $pdo->prepare("INSERT INTO asignaturas (nombre, descripcion, intensidad_horaria, sede_id, profesor_id, estado, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, 'activo', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$nombre, $descripcion, $intensidad_horaria, $sede_id, $profesor_id]);
        $asignatura_id = $pdo->lastInsertId();

        // Verificar si ya existe esta materia en la tabla materias
        $stmt = $pdo->prepare("SELECT id FROM materias WHERE nombre = ? AND estado = 'activo'");
        $stmt->execute([$nombre]);
        $materia = $stmt->fetch();

        if (!$materia) {
            // Si no existe, insertar también en la tabla materias
            $stmt = $pdo->prepare("INSERT INTO materias (nombre, descripcion, intensidad_horaria, estado, created_at) 
                                  VALUES (?, ?, ?, 'activo', CURRENT_TIMESTAMP)");
            $stmt->execute([$nombre, $descripcion, $intensidad_horaria]);
            $materia_id = $pdo->lastInsertId();
            
            // Registrar en log la creación en materias
            $stmt = $pdo->prepare("INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha) 
                                  VALUES ('materias', ?, 'crear', ?, ?, NOW())");
            $log_materias = "Creación de materia en tabla materias: $nombre";
            $stmt->execute([$materia_id, $log_materias, $_SESSION['admin_id']]);
        }

        // Registrar en log de actividad para asignaturas
        $stmt = $pdo->prepare("INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha) 
                              VALUES ('asignaturas', ?, 'crear', ?, ?, NOW())");
        $log_descripcion = "Creación de materia: $nombre";
        $stmt->execute([$asignatura_id, $log_descripcion, $_SESSION['admin_id']]);

        // Confirmar transacción
        $pdo->commit();
        
        header('Location: list_dba.php?success=1');
        exit();

    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Configurar el breadcrumb
$breadcrumb_path = [
    '/admin/dashboard.php' => 'Inicio',
    '/admin/academic/list_dba.php' => 'Materias',
    '/admin/academic/create_materia.php' => 'Nueva Materia'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Materia - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }

        .top-bar {
            border-radius: 20px;
            margin: 1rem 1rem 2rem 1rem;
            background: white;
            padding: 1.25rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 0 1rem;
        }

        .card-header {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header h2 i {
            color: #3b82f6;
            font-size: 1.25rem;
        }

        .card-header .header-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #4b5563;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .card-header .header-action:hover {
            color: #3b82f6;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .required-label::after {
            content: ' *';
            color: #ef4444;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .number-input {
            max-width: 150px;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .btn {
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.9375rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        /* Estilos específicos para select */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../../admin/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../../admin/components/topbar.php'; ?>

            <div class="content-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-book"></i>
                        Nueva Materia
                    </h2>
                    <a href="list_dba.php" class="header-action">
                        <i class="fas fa-arrow-left"></i>
                        <span>Volver a la lista</span>
                    </a>
                </div>

                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="subject-form">
                    <div class="form-group">
                        <label for="nombre" class="required-label">Nombre de la Materia</label>
                        <input type="text" 
                               id="nombre"
                               name="nombre" 
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                               placeholder="Ingrese el nombre de la materia"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" 
                                  name="descripcion" 
                                  placeholder="Descripción detallada de la materia (opcional)"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="intensidad_horaria" class="required-label">Intensidad Horaria</label>
                        <input type="number" 
                               id="intensidad_horaria"
                               name="intensidad_horaria" 
                               class="number-input"
                               value="<?php echo isset($_POST['intensidad_horaria']) ? (int)$_POST['intensidad_horaria'] : 1; ?>"
                               min="1" 
                               max="40" 
                               required>
                        <small>Horas por semana</small>
                    </div>

                    <div class="form-group">
                        <label for="sede_id" class="required-label">Sede</label>
                        <select id="sede_id" name="sede_id" required>
                            <option value="">Seleccione una sede</option>
                            <?php foreach($sedes as $sede): ?>
                                <option value="<?php echo $sede['id']; ?>"
                                        <?php echo isset($_POST['sede_id']) && $_POST['sede_id'] == $sede['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sede['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="profesor_id" class="required-label">Profesor</label>
                        <select id="profesor_id" name="profesor_id" required>
                            <option value="">Seleccione un profesor</option>
                            <?php foreach($profesores as $profesor): ?>
                                <option value="<?php echo $profesor['id']; ?>"
                                        <?php echo isset($_POST['profesor_id']) && $_POST['profesor_id'] == $profesor['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="list_dba.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Materia
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Actualizar reloj
        function updateDateTime() {
            const now = new Date();
            
            // Formatear fecha
            const dateOptions = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            if (document.getElementById('current-date')) {
                document.getElementById('current-date').textContent = now.toLocaleDateString('es-ES', dateOptions);
            }
            
            // Formatear hora
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            };
            
            if (document.getElementById('current-time')) {
                document.getElementById('current-time').textContent = now.toLocaleTimeString('es-ES', timeOptions);
            }
        }

        // Actualizar fecha y hora cada segundo
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Validación del formulario
        document.getElementById('subject-form').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const sede = document.getElementById('sede_id').value;
            const profesor = document.getElementById('profesor_id').value;
            const intensidad = document.getElementById('intensidad_horaria').value;

            let isValid = true;
            const errores = [];

            if (!nombre) {
                errores.push('El nombre de la materia es obligatorio');
                isValid = false;
            }

            if (!sede) {
                errores.push('Debe seleccionar una sede');
                isValid = false;
            }

            if (!profesor) {
                errores.push('Debe seleccionar un profesor');
                isValid = false;
            }

            if (intensidad < 1 || intensidad > 40) {
                errores.push('La intensidad horaria debe estar entre 1 y 40 horas');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                alert(errores.join('\n'));
            }
        });
    </script>
</body>
</html>