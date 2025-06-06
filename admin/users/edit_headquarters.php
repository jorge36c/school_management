<?php
$base_url = '/school_management';

function isActivePage($path) {
    return strpos($_SERVER['PHP_SELF'], $path) !== false ? 'active' : '';
}

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

    // Obtener los datos de la sede
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM sedes WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $sede = $stmt->fetch();

        if (!$sede) {
            throw new Exception('Sede no encontrada.');
        }
    } else {
        throw new Exception('ID de sede no proporcionado.');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $codigo_dane = trim($_POST['codigo_dane']);
        $direccion = trim($_POST['direccion']);
        $telefono = trim($_POST['telefono']);

        // Verificar si el nombre ya existe para otra sede
        $stmt = $pdo->prepare("SELECT id FROM sedes WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Ya existe otra sede con este nombre.');
        }

        // Actualizar sede
        $sql = "UPDATE sedes SET nombre = ?, codigo_dane = ?, direccion = ?, telefono = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nombre, $codigo_dane, $direccion, $telefono, $id])) {
            header('Location: list_headquarters.php?message=Sede actualizada exitosamente');
            exit();
        } else {
            throw new Exception('Error al actualizar la sede. Verifique los datos ingresados.');
        }
    }
} catch(Exception $e) {
    echo '<div style="color: red; text-align: center; margin-top: 20px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Sede - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-primary);
        }

        .logo i {
            font-size: 1.5rem;
            color: #3b82f6;
        }

        .logo span {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .sidebar-nav {
            padding: 1.5rem 1rem;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-category {
            padding: 1.5rem 0.5rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .sidebar-nav li:not(.nav-category) {
            margin-bottom: 0.25rem;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .menu-icon {
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            color: white;
        }

        .menu-icon i {
            font-size: 1rem;
        }

        .sidebar-nav a:hover {
            background: #f8fafc;
        }

        .sidebar-nav li.active a {
            background: #f8fafc;
            color: #3b82f6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        /* Scrollbar personalizado */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 2px;
        }

        .content-wrapper {
            padding: 20px;
            margin-left: var(--sidebar-width);
        }

        .create-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #2c3e50;
            font-size: 1.8em;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #3498db;
            width: 20px;
            text-align: center;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95em;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border: none;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(149, 165, 166, 0.3);
        }

        .top-bar {
            background: #2c3e50;
            color: white;
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 20px;
            margin-left: var(--sidebar-width);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .breadcrumb {
            color: #ecf0f1;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .top-bar-time {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ecf0f1;
            font-size: 0.9rem;
            padding: 5px 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            border: 2px solid #ecf0f1;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.8rem;
            color: #bdc3c7;
        }

        .logout-btn {
            background: #c0392b;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #e74c3c;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Sistema Escolar</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <!-- Dashboard -->
                    <li class="<?php echo isActivePage('dashboard.php'); ?>">
                        <a href="<?php echo $base_url; ?>/admin/dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Gestión de Usuarios -->
                    <li class="nav-category">
                        <span>GESTIÓN DE USUARIOS</span>
                    </li>

                    <li class="<?php echo isActivePage('list_teachers.php'); ?>">
                        <a href="<?php echo $base_url; ?>/admin/users/list_teachers.php">
                            <div class="menu-icon" style="background: #818cf8;">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <span>Profesores</span>
                        </a>
                    </li>

                    <li class="<?php echo isActivePage('list_students.php'); ?>">
                        <a href="<?php echo $base_url; ?>/admin/users/list_students.php">
                            <div class="menu-icon" style="background: #4ade80;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <span>Estudiantes</span>
                        </a>
                    </li>

                    <li class="<?php echo isActivePage('list_parents.php'); ?>">
                        <a href="<?php echo $base_url; ?>/admin/users/list_parents.php">
                            <div class="menu-icon" style="background: #a78bfa;">
                                <i class="fas fa-users"></i>
                            </div>
                            <span>Padres de Familia</span>
                        </a>
                    </li>

                    <!-- Sedes -->
                    <li class="nav-category">
                        <span>SEDES</span>
                    </li>

                    <li class="<?php echo isActivePage('list_headquarters.php'); ?>">
                        <a href="<?php echo $base_url; ?>/admin/users/list_headquarters.php">
                            <div class="menu-icon" style="background: #fbbf24;">
                                <i class="fas fa-building"></i>
                            </div>
                            <span>Lista de Sedes</span>
                        </a>
                    </li>

                    <!-- Planeación Académica -->
                    <li class="nav-category">
                        <span>PLANEACIÓN ACADÉMICA</span>
                    </li>

                    <li class="<?php echo isActivePage('list_materias.php'); ?>">
                        <a href="<?php echo $base_url; ?>/admin/academic/list_materias.php">
                            <div class="menu-icon" style="background: #60a5fa;">
                                <i class="fas fa-book"></i>
                            </div>
                            <span>Asignaturas</span>
                        </a>    
                    </li>

                    <!-- Gestión Académica -->
                    <li class="nav-category">
                        <span>GESTIÓN ACADÉMICA</span>
                    </li>

                    <li class="<?php echo isActivePage('academic_year_management.php'); ?>">
                        <a href="<?php echo $base_url; ?>/admin/academic/periods/academic_year_management.php">
                            <div class="menu-icon" style="background: #f43f5e;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <span>Períodos Académicos</span>
                        </a>
                    </li>

                    <li class="<?php echo isActivePage('list_matriculas.php'); ?>">
                        <a href="<?php echo $base_url; ?>/admin/academic/matriculas/list_matriculas.php">
                            <div class="menu-icon" style="background: #14b8a6;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <span>Matrículas</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <div class="top-bar-left">
                    <button id="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <i class="fas fa-building"></i>
                        <span>/ Sedes / Editar</span>
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
                            <a href="../../auth/logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="create-form">
                    <div class="form-header">
                        <h2>Editar Sede</h2>
                        <p>Modifique la información de la sede</p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($sede['id']); ?>">

                        <div class="form-group">
                            <label for="nombre">
                                <i class="fas fa-building"></i>
                                Nombre de la Sede
                            </label>
                            <input type="text" id="nombre" name="nombre" class="form-control" 
                                   value="<?php echo htmlspecialchars($sede['nombre']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="codigo_dane">
                                <i class="fas fa-id-badge"></i>
                                Código DANE
                            </label>
                            <input type="text" id="codigo_dane" name="codigo_dane" class="form-control" 
                                   value="<?php echo htmlspecialchars($sede['codigo_dane']); ?>" placeholder="Ingrese el código DANE de la sede">
                        </div>

                        <div class="form-group">
                            <label for="direccion">
                                <i class="fas fa-map-marker-alt"></i>
                                Dirección
                            </label>
                            <input type="text" id="direccion" name="direccion" class="form-control" 
                                   value="<?php echo htmlspecialchars($sede['direccion']); ?>" placeholder="Ingrese la dirección de la sede">
                        </div>

                        <div class="form-group">
                            <label for="telefono">
                                <i class="fas fa-phone"></i>
                                Teléfono
                            </label>
                            <input type="text" id="telefono" name="telefono" class="form-control" 
                                   value="<?php echo htmlspecialchars($sede['telefono']); ?>" placeholder="Ingrese el teléfono de la sede">
                        </div>

                        <div class="form-actions">
                            <a href="list_headquarters.php" class="btn btn-secondary">
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
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('current-time').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });
    </script>
</body>
</html>
