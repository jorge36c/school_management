<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Obtener las sedes activas
$query_sedes = "SELECT id, nombre, direccion FROM sedes WHERE estado = 'activo' ORDER BY nombre ASC";
$sedes = $pdo->query($query_sedes)->fetchAll(PDO::FETCH_ASSOC);

// Obtener las asignaturas activas
$query_asignaturas = "SELECT id, nombre FROM asignaturas WHERE estado = 'activo' ORDER BY nombre ASC";
$asignaturas = $pdo->query($query_asignaturas)->fetchAll(PDO::FETCH_ASSOC);

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $usuario = trim($_POST['usuario']);
        $password = trim($_POST['password']);
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $email = trim($_POST['email']);
        $especialidad = trim($_POST['asignatura']);
        $sede_id = trim($_POST['sede']);
        $telefono = trim($_POST['telefono']);

        // Verificar si el usuario o email ya existe
        $stmt = $pdo->prepare("SELECT id FROM profesores WHERE usuario = ? OR email = ?");
        $stmt->execute([$usuario, $email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('El usuario o email ya existe en el sistema.');
        }

        // Crear hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Consulta para insertar el nuevo profesor
        $stmt = $pdo->prepare("INSERT INTO profesores (usuario, password, nombre, apellido, email, especialidad, sede_id, telefono, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo')");
        
        if ($stmt->execute([$usuario, $password_hash, $nombre, $apellido, $email, $especialidad, $sede_id, $telefono])) {
            header('Location: ../users/list_teachers.php?success=1');
            exit();
        } else {
            throw new Exception('Error al crear el profesor. Por favor, intente nuevamente.');
        }

    } catch(Exception $e) {
        // Mostrar el mensaje de error directamente en la página para mayor claridad
        $error_message = $e->getMessage();
    }
}

// Configurar el breadcrumb
$breadcrumb_path = [
    '/admin/dashboard.php' => 'Inicio',
    '/admin/users/list_teachers.php' => 'Profesores',
    '/admin/users/create_teacher.php' => 'Crear Profesor'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Profesor - Sistema Escolar</title>
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
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .form-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .form-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .form-section h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #1f2937;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.75rem;
        }

        .form-section h3 i {
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

        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        .time-display {
            background: #f8fafc;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4b5563;
            font-size: 0.875rem;
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
            .form-sections {
                grid-template-columns: 1fr;
            }

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
                    <h2>Crear Nuevo Profesor</h2>
                    <p>Complete todos los campos requeridos para registrar un nuevo profesor en el sistema</p>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="teacher-form">
                    <div class="form-sections">
                        <!-- Información de Cuenta -->
                        <div class="form-section">
                            <h3>
                                <i class="fas fa-user-shield"></i>
                                Información de Cuenta
                            </h3>
                            
                            <div class="form-group">
                                <label for="usuario" class="required-label">Usuario</label>
                                <input type="text" id="usuario" name="usuario" required>
                                <small class="form-text">Este será el nombre de usuario para iniciar sesión</small>
                            </div>

                            <div class="form-group">
                                <label for="password" class="required-label">Contraseña</label>
                                <input type="password" id="password" name="password" required>
                                <small class="form-text">La contraseña debe tener al menos 6 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="email" class="required-label">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>

                        <!-- Información Personal y Profesional -->
                        <div class="form-section">
                            <h3>
                                <i class="fas fa-user"></i>
                                Información Personal y Profesional
                            </h3>
                            
                            <div class="form-group">
                                <label for="nombre" class="required-label">Nombre</label>
                                <input type="text" id="nombre" name="nombre" required>
                            </div>

                            <div class="form-group">
                                <label for="apellido" class="required-label">Apellido</label>
                                <input type="text" id="apellido" name="apellido" required>
                            </div>

                            <div class="form-group">
                                <label for="asignatura" class="required-label">Especialidad</label>
                                <select id="asignatura" name="asignatura" required>
                                    <option value="">Seleccione una especialidad</option>
                                    <?php foreach ($asignaturas as $asignatura): ?>
                                        <option value="<?php echo htmlspecialchars($asignatura['id']); ?>">
                                            <?php echo htmlspecialchars($asignatura['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="sede" class="required-label">Sede</label>
                                <select id="sede" name="sede" required>
                                    <option value="">Seleccione una sede</option>
                                    <?php foreach ($sedes as $sede): ?>
                                        <option value="<?php echo htmlspecialchars($sede['id']); ?>">
                                            <?php echo htmlspecialchars($sede['nombre'] . ' - ' . $sede['direccion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="telefono" class="required-label">Celular</label>
                                <input type="tel" id="telefono" name="telefono" required>
                                <small class="form-text">Número de contacto (solo dígitos)</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="../users/list_teachers.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Profesor
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

        // Validación de formulario
        document.getElementById('teacher-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const telefono = document.getElementById('telefono').value;

            let isValid = true;
            const errores = [];

            if (password.length < 6) {
                errores.push('La contraseña debe tener al menos 6 caracteres');
                isValid = false;
            }

            if (!/^\d+$/.test(telefono)) {
                errores.push('El Celular debe contener solo números');
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