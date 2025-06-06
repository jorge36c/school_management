<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

$error = '';
$success = '';
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar si el estudiante existe
try {
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM estudiantes WHERE id = ?");
    $stmt->execute([$student_id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$estudiante) {
        $error = "Estudiante no encontrado.";
    }
} catch (PDOException $e) {
    error_log("Error al buscar estudiante: " . $e->getMessage());
    $error = "Error al buscar estudiante.";
}

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validar que las contraseñas no estén vacías y coincidan
    if (empty($password)) {
        $error = "La contraseña no puede estar vacía.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Encriptar la contraseña y actualizar en la base de datos
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE estudiantes SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashed_password, $student_id]);
            
            if ($result) {
                $success = "La contraseña ha sido actualizada exitosamente.";
                
                // Registrar la acción en el log de actividad
                $admin_id = $_SESSION['admin_id'];
                $descripcion = "Cambio de contraseña para el estudiante: {$estudiante['nombre']} {$estudiante['apellido']}";
                
                $stmt = $pdo->prepare("INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id) 
                                        VALUES ('estudiantes', ?, 'cambiar_password', ?, ?)");
                $stmt->execute([$student_id, $descripcion, $admin_id]);
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=list_students.php");
            } else {
                $error = "No se pudo actualizar la contraseña.";
            }
        } catch (PDOException $e) {
            error_log("Error al actualizar contraseña: " . $e->getMessage());
            $error = "Error al actualizar la contraseña en la base de datos.";
        }
    }
}

// Configurar el breadcrumb
$breadcrumb_path = [
    '/admin/dashboard.php' => 'Inicio',
    '/admin/users/list_students.php' => 'Estudiantes',
    '/admin/users/change_password.php' => 'Cambiar Contraseña'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña de Estudiante - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 2rem auto;
        }

        .card-header {
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
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
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
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

        .student-info {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../components/topbar.php'; ?>

            <div class="content">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Cambiar Contraseña de Estudiante</h2>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php elseif (!$error && $estudiante): ?>
                        <div class="student-info">
                            <p><strong>Estudiante:</strong> <?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></p>
                        </div>

                        <form method="post" action="">
                            <div class="form-group">
                                <label for="password">Nueva Contraseña</label>
                                <input type="password" id="password" name="password" required>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirmar Contraseña</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>

                            <div class="form-actions">
                                <a href="list_students.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    function updateDateTime() {
        const now = new Date();
        
        // Formatear fecha
        const dateOptions = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        document.getElementById('current-date').textContent = 
            now.toLocaleDateString('es-ES', dateOptions);
        
        // Formatear hora
        const timeOptions = { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        };
        document.getElementById('current-time').textContent = 
            now.toLocaleTimeString('es-ES', timeOptions);
    }

    // Actualizar fecha y hora cada segundo
    updateDateTime();
    setInterval(updateDateTime, 1000);
    </script>
</body>
</html>