<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

class MatriculaManager {
    private $pdo;
    private $error;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getEstadisticas() {
        try {
            return [
                'activas' => $this->contarMatriculas('Activa'),
                'pendientes' => $this->contarMatriculas('Pendiente'),
                'inactivas' => $this->contarMatriculas('Inactiva')
            ];
        } catch (PDOException $e) {
            $this->error = "Error al obtener estadísticas: " . $e->getMessage();
            return [
                'activas' => 0,
                'pendientes' => 0,
                'inactivas' => 0
            ];
        }
    }

    private function contarMatriculas($estado) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM matriculas 
                WHERE estado = ?
            ");
            $stmt->execute([$estado]);
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getGradosDisponibles() {
        try {
            $stmt = $this->pdo->query("
                SELECT DISTINCT grado 
                FROM matriculas 
                ORDER BY grado ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (PDOException $e) {
            $this->error = "Error al obtener grados: " . $e->getMessage();
            return [];
        }
    }

    public function getError() {
        return $this->error;
    }
}

// Inicialización
$manager = new MatriculaManager($pdo);
$estadisticas = $manager->getEstadisticas();
$grados = $manager->getGradosDisponibles();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Matrículas - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/admin.css">
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

        /* Navbar/Header Superior */
        .top-bar {
            background: #1e293b;
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
            color: white;
            font-size: 0.9rem;
        }

        .breadcrumb i {
            margin-right: 0.25rem;
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

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
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
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* Botón de Cerrar Sesión */
        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        /* Contenido Principal */
        .content-wrapper {
            padding: 2rem;
        }

        /* Título Principal */
        .main-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .title-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .title-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }
        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            color: #1e293b;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        /* Filters Section */
        .filters-section {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .filters-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
        }

        .filter-select,
        .filter-input {
            padding: 0.625rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            min-width: 200px;
            color: #1e293b;
        }

        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .btn-search {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.625rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Utilities */
        .text-warning { color: #f59e0b; }
        .text-success { color: #10b981; }
        .text-danger { color: #ef4444; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../../sidebar.php'; ?>

        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="top-bar-left">
                    <button id="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <i class="fas fa-graduation-cap"></i>
                        <span>/ Matrículas</span>
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
                            <span class="user-name">Administrador</span>
                            <span class="user-role">Administrador</span>
                        </div>
                    </div>
                    <a href="../../../auth/logout.php" class="btn-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>

            <div class="content-wrapper">
                <!-- Stats Section -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-title">
                            <i class="fas fa-clock text-warning"></i>
                            Pendientes
                        </div>
                        <div class="stat-number">
                            <?php echo $estadisticas['pendientes']; ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-title">
                            <i class="fas fa-check-circle text-success"></i>
                            Activas
                        </div>
                        <div class="stat-number">
                            <?php echo $estadisticas['activas']; ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-title">
                            <i class="fas fa-times-circle text-danger"></i>
                            Inactivas
                        </div>
                        <div class="stat-number">
                            <?php echo $estadisticas['inactivas']; ?>
                        </div>
                    </div>
                </div>

                <!-- Main Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-graduation-cap"></i>
                            Matrículas
                        </h2>
                        <a href="create_matricula.php" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            Nueva Matrícula
                        </a>
                    </div>

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" class="filters-form">
                            <div class="filter-group">
                                <label class="filter-label">Filtrar por</label>
                                <select class="filter-select" name="grado">
                                    <option value="">Todos los grados</option>
                                    <?php foreach ($grados as $grado): ?>
                                        <option value="<?php echo htmlspecialchars($grado); ?>">
                                            <?php echo htmlspecialchars($grado); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">Búsqueda</label>
                                <input type="text" class="filter-input" name="busqueda" 
                                       placeholder="Ingrese su búsqueda...">
                            </div>

                            <button type="submit" class="btn-search">
                                <i class="fas fa-search"></i>
                                Buscar
                            </button>
                        </form>
                    </div>

                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap"></i>
                        <p>No se encontraron matrículas</p>
                    </div>
                </div>
            </div>
        </main>
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
        
        updateTime();
        setInterval(updateTime, 1000);

        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
            const mainContent = document.querySelector('.main-content');
            mainContent.style.marginLeft = mainContent.style.marginLeft === '0px' ? '260px' : '0px';
        });

        // Animaciones de entrada
        function animateElements() {
            const elements = [
                ...document.querySelectorAll('.stat-card'),
                document.querySelector('.card')
            ];

            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.3s ease-out';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        // Aplicar animaciones al cargar la página
        document.addEventListener('DOMContentLoaded', animateElements);

        // Manejar responsive
        function handleResponsive() {
            const container = document.querySelector('.admin-container');
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth <= 768) {
                container.classList.add('sidebar-collapsed');
                mainContent.style.marginLeft = '0';
            } else {
                container.classList.remove('sidebar-collapsed');
                mainContent.style.marginLeft = '260px';
            }
        }

        window.addEventListener('resize', handleResponsive);
        handleResponsive();

        // Mejorar interacción de filtros
        const filterSelect = document.querySelector('.filter-select');
        const filterInput = document.querySelector('.filter-input');

        [filterSelect, filterInput].forEach(element => {
            element.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
                this.parentElement.style.transition = 'transform 0.2s ease';
            });

            element.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>

    <style>
        /* Animaciones */
        @keyframes fadeIn {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mejoras visuales adicionales */
        #sidebar-toggle {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0.5rem;
            font-size: 1.25rem;
            transition: transform 0.2s ease;
        }

        #sidebar-toggle:hover {
            transform: scale(1.1);
        }

        .btn-primary, 
        .btn-danger, 
        .btn-search {
            transition: all 0.2s ease;
        }

        .btn-primary:hover, 
        .btn-danger:hover, 
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Scrollbar personalizado */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Responsive adicional */
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-form {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .top-bar {
                padding: 0.75rem;
            }

            .user-info {
                display: none;
            }

            .btn-primary,
            .btn-danger,
            .btn-search {
                width: 100%;
                justify-content: center;
            }
        }

        /* Focus states mejorados */
        .btn:focus,
        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
        }
    </style>
</body>
</html>