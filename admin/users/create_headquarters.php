<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    // Verificar si la conexión a la base de datos se estableció correctamente
    if (!isset($pdo)) {
        throw new Exception('Error al conectar con la base de datos.');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = trim($_POST['nombre']);
        $codigo_dane = trim($_POST['codigo_dane']);
        $direccion = trim($_POST['direccion']);
        $telefono = trim($_POST['telefono']);

        // Verificar si la sede ya existe
        $stmt = $pdo->prepare("SELECT id FROM sedes WHERE nombre = ?");
        $stmt->execute([$nombre]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Ya existe una sede con este nombre.');
        }

        // Insertar sede
        $stmt = $pdo->prepare("INSERT INTO sedes (nombre, codigo_dane, direccion, telefono, estado) VALUES (?, ?, ?, ?, 'activo')");
        
        if ($stmt->execute([$nombre, $codigo_dane, $direccion, $telefono])) {
            $sede_id = $pdo->lastInsertId();
            
            // Registrar en el log
            $stmt = $pdo->prepare("INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha) 
                                  VALUES ('sedes', ?, 'crear', ?, ?, NOW())");
            $log_descripcion = "Creación de sede: $nombre";
            $stmt->execute([$sede_id, $log_descripcion, $_SESSION['admin_id']]);
            
            header('Location: list_headquarters.php?success=1');
            exit();
        } else {
            throw new Exception('Error al crear la sede. Verifique los datos ingresados.');
        }
    }
} catch(Exception $e) {
    $error = $e->getMessage();
}

// Configurar el breadcrumb
$breadcrumb_path = [
    '/admin/dashboard.php' => 'Inicio',
    '/admin/users/list_headquarters.php' => 'Sedes',
    '/admin/users/create_headquarters.php' => 'Crear Sede'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Sede - Sistema Escolar</title>
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
                        <i class="fas fa-building"></i>
                        Crear Nueva Sede
                    </h2>
                    <a href="list_headquarters.php" class="header-action">
                        <i class="fas fa-arrow-left"></i>
                        <span>Volver a la lista</span>
                    </a>
                </div>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="create-headquarters-form">
                    <div class="form-group">
                        <label for="nombre" class="required-label">Nombre de la Sede</label>
                        <input type="text" 
                               id="nombre"
                               name="nombre" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="codigo_dane">Código DANE</label>
                        <input type="text" 
                               id="codigo_dane"
                               name="codigo_dane" 
                               placeholder="Ingrese el código DANE de la sede">
                    </div>

                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <input type="text" 
                               id="direccion"
                               name="direccion" 
                               placeholder="Ingrese la dirección de la sede">
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" 
                               id="telefono"
                               name="telefono" 
                               placeholder="Ingrese el teléfono de la sede">
                    </div>

                    <div class="form-actions">
                        <a href="list_headquarters.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Sede
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
        document.getElementById('create-headquarters-form').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();

            let isValid = true;
            const errores = [];

            if (!nombre) {
                errores.push('El nombre de la sede es obligatorio');
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