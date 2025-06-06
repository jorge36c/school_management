<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

$error = null;
$success = null;

if (!isset($_GET['id'])) {
    header('Location: list_teachers.php');
    exit();
}

$id = $_GET['id'];

// Definir el breadcrumb para esta página
$breadcrumb_path = [
    '/school_management/admin/dashboard.php' => 'Dashboard',
    '/school_management/admin/users/list_teachers.php' => 'Profesores',
    '#' => 'Editar Profesor'
];

try {
    // Iniciamos transacción
    $pdo->beginTransaction();

    // Obtener sedes activas
    $stmt_sedes = $pdo->query("SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre");
    $sedes = $stmt_sedes->fetchAll(PDO::FETCH_ASSOC);

    // Obtener asignaturas activas
    $stmt_asignaturas = $pdo->query("SELECT id, nombre FROM asignaturas WHERE estado = 'activo' ORDER BY nombre ASC");
    $asignaturas = $stmt_asignaturas->fetchAll(PDO::FETCH_ASSOC);

    // Obtener datos del profesor
    $stmt = $pdo->prepare("SELECT * FROM profesores WHERE id = ?");
    $stmt->execute([$id]);
    $profesor = $stmt->fetch();

    if (!$profesor) {
        throw new Exception("Profesor no encontrado.");
    }

    // Si es una petición POST, actualizar los datos
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar datos
        if (empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['email'])) {
            throw new Exception("Todos los campos son obligatorios");
        }

        // Preparar la consulta de actualización
        $sql = "UPDATE profesores SET 
                nombre = :nombre, 
                apellido = :apellido, 
                email = :email, 
                especialidad = :especialidad,
                sede_id = :sede_id, 
                telefono = :telefono
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        
        // Ejecutar la actualización con los datos validados
        $result = $stmt->execute([
            ':nombre' => trim($_POST['nombre']),
            ':apellido' => trim($_POST['apellido']),
            ':email' => trim($_POST['email']),
            ':especialidad' => $_POST['asignatura'],
            ':sede_id' => $_POST['sede_id'],
            ':telefono' => trim($_POST['telefono']),
            ':id' => $id
        ]);

        // Actualizar contraseña si se proporcionó una nueva
        if (!empty($_POST['new_password'])) {
            $password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE profesores SET password = ? WHERE id = ?");
            $stmt->execute([$password_hash, $id]);
        }

        // Registrar la actividad sin especificar el campo fecha (usará el DEFAULT CURRENT_TIMESTAMP)
        $log_stmt = $pdo->prepare("
            INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id)
            VALUES (:tabla, :registro_id, :accion, :descripcion, :usuario_id)
        ");
        
        $log_stmt->execute([
            ':tabla' => 'profesores',
            ':registro_id' => $id,
            ':accion' => 'actualizar',
            ':descripcion' => "Actualización de profesor: {$_POST['nombre']} {$_POST['apellido']}",
            ':usuario_id' => $_SESSION['admin_id']
        ]);

        $pdo->commit();
        header('Location: list_teachers.php?success=1');
        exit();
    }

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en edit_teacher.php: " . $e->getMessage());
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Profesor - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            background: #f8fafc;
        }

        .content-wrapper {
            padding: 2rem;
        }

        .edit-form {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .form-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .form-section {
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            text-decoration: none;
        }

        .btn-danger {
            background: #ff4d4f;
            color: white;
            border: none;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-bar {
            background: #2c3e50;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ecf0f1;
            font-size: 0.9rem;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .top-bar-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            background: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: white;
        }

        .user-role {
            font-size: 0.875rem;
            color: #94a3b8;
        }

    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <?php include '../components/topbar.php'; ?>
            <div class="content-wrapper">
                <div class="edit-form">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Profesor actualizado exitosamente
                        </div>
                    <?php endif; ?>

                    <div class="form-header">
                        <h2>Editar Profesor</h2>
                        <p>Actualice la información del profesor</p>
                    </div>

                    <form id="editForm" method="POST" class="edit-form">
                        <div class="form-grid">
                            <div class="form-section">
                                <h3>
                                    <i class="fas fa-user-shield"></i>
                                    Información de Cuenta
                                </h3>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="new_password">Nueva Contraseña (dejar en blanco para mantener la actual)</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="sede_id">Sede</label>
                                    <select id="sede_id" name="sede_id" class="form-control" required>
                                        <option value="">Seleccione una sede...</option>
                                        <?php foreach ($sedes as $sede): ?>
                                            <option value="<?php echo $sede['id']; ?>" 
                                                    <?php echo ($profesor['sede_id'] == $sede['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sede['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>
                                    <i class="fas fa-user"></i>
                                    Información Personal
                                </h3>

                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['nombre']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['apellido']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="asignatura">Asignatura</label>
                                    <select id="asignatura" name="asignatura" required class="form-control">
                                        <option value="">Seleccione una asignatura</option>
                                        <?php foreach ($asignaturas as $asignatura): ?>
                                            <option value="<?php echo htmlspecialchars($asignatura['id']); ?>" 
                                                    <?php echo ($asignatura['id'] == $profesor['especialidad']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($asignatura['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text" id="telefono" name="telefono" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['telefono']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="list_teachers.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-ES', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                hour12: true 
            });
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });

        document.getElementById('editForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            
            if (newPassword && newPassword.length < 6) {
                e.preventDefault();
                alert('La nueva contraseña debe tener al menos 6 caracteres');
                return;
            }

            const telefono = document.getElementById('telefono').value;
            if (!/^\d+$/.test(telefono)) {
                e.preventDefault();
                alert('El teléfono debe contener solo números');
                return;
            }
        });

        document.getElementById('telefono').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>
