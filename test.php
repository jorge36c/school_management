<?php
// Cargar configuración
$config = require_once 'config/app.php';

// Establecer modo de depuración
define('DEBUG_MODE', $config['debug'] ?? false);

// Establecer zona horaria
date_default_timezone_set($config['timezone'] ?? 'UTC');

// Configurar límite de tiempo de ejecución
set_time_limit($config['max_execution_time'] ?? 30);

session_start();
// Verificación de autenticación
if (!isset($_SESSION['admin_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Control de acceso basado en roles
if (!in_array($_SESSION['rol'], ['admin', 'superadmin'])) {
    header('Location: unauthorized.php');
    exit();
}

require_once 'config/database.php';

// Configurar manejo de errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr en $errfile:$errline");
    if (DEBUG_MODE) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    } else {
        echo "<div class='error'>Ha ocurrido un error. Por favor, contacte al administrador.</div>";
    }
});

echo "<h2>Prueba de Conexión y Base de Datos</h2>";

class Logger {
    private static $logFile = 'logs/app.log';
    
    public static function log($message, $level = 'INFO') {
        try {
            // Crear directorio logs si no existe
            if (!file_exists('logs')) {
                mkdir('logs', 0777, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp][$level] $message" . PHP_EOL;
            
            // Verificar permisos de escritura
            if (!is_writable('logs') && file_exists('logs')) {
                chmod('logs', 0777);
            }
            
            file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            error_log("Error al escribir en el log: " . $e->getMessage());
        }
    }
}

// Uso en el código
Logger::log("Acceso a test.php - IP: " . $_SERVER['REMOTE_ADDR']);

// Validar IP
$allowedIPs = ['127.0.0.1', '::1']; // IPs permitidas
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    Logger::log("Intento de acceso no autorizado desde " . $_SERVER['REMOTE_ADDR'], 'WARNING');
    die('Acceso no autorizado');
}

// Validar método de petición
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Logger::log("Método no permitido: " . $_SERVER['REQUEST_METHOD'], 'WARNING');
    http_response_code(405);
    die('Método no permitido');
}

try {
    // Desactivar restricciones de clave foránea temporalmente
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    // Lista de tablas a eliminar basada en school_management.sql
    $tables = [
        'actividad_log',
        'administradores',
        'anos_lectivos',
        'asignaciones_profesor',
        'asignaturas',
        'asistencias',
        'calificaciones',
        'estudiantes',
        'grados',
        'grupos',
        'historial_cambios_grado',
        'matriculas',
        'niveles_sede',
        'periodos_academicos',
        'planeaciones',
        'profesores',
        'sedes',
        'tipos_notas'
    ];

    // Eliminar cada tabla
    foreach($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Tabla '$table' eliminada exitosamente<br>";
    }

    // Eliminar el procedimiento almacenado si existe
    $pdo->exec("DROP PROCEDURE IF EXISTS `crear_periodos_automaticos`");
    echo "Procedimiento almacenado 'crear_periodos_automaticos' eliminado<br>";

    // Reactivar restricciones de clave foránea
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "<br>¡Base de datos school_management limpiada exitosamente!";

} catch(PDOException $e) {
    die("Error al limpiar la base de datos: " . $e->getMessage());
}

// Constantes para configuración de contraseñas
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRES_SPECIAL', true);

// Función de validación de contraseña
function validatePassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    if (PASSWORD_REQUIRES_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    return true;
}

// Uso en las pruebas
$password_prueba = "admin123";
if (!validatePassword($password_prueba)) {
    echo "<div class='error'>La contraseña no cumple con los requisitos mínimos</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Sistema - Panel de Diagnóstico</title>
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fb;
            color: #2d3748;
            line-height: 1.6;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 1.8rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dashboard-title i {
            color: #4299e1;
        }

        .status-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .status-title {
            font-size: 1.2rem;
            font-weight: 500;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-success {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status-error {
            background: #fed7d7;
            color: #c53030;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table th {
            background: #f7fafc;
            font-weight: 500;
            color: #4a5568;
        }

        .error-message {
            background: #fff5f5;
            border-left: 4px solid #f56565;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0.375rem;
        }

        .success-message {
            background: #f0fff4;
            border-left: 4px solid #48bb78;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0.375rem;
        }

        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.375rem;
            overflow-x: auto;
            font-family: monospace;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .dashboard-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="fas fa-terminal"></i>
                Panel de Diagnóstico del Sistema
            </h1>
            <p>Herramienta de prueba y verificación del sistema</p>
        </div>

        <!-- Estado de la Conexión -->
        <div class="status-card">
            <div class="status-header">
                <h2 class="status-title">
                    <i class="fas fa-database"></i>
                    Estado de la Conexión
                </h2>
                <span class="status-badge status-success">
                    <i class="fas fa-check-circle"></i>
                    Conectado
                </span>
            </div>
            <div class="status-content">
                <!-- Aquí va el contenido de la prueba de conexión -->
            </div>
        </div>

        <!-- Usuarios del Sistema -->
        <div class="status-card">
            <div class="status-header">
                <h2 class="status-title">
                    <i class="fas fa-users"></i>
                    Usuarios del Sistema
                </h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Aquí se insertarán los usuarios dinámicamente -->
                </tbody>
            </table>
        </div>

        <!-- Resultados de las Pruebas -->
        <div class="status-card">
            <div class="status-header">
                <h2 class="status-title">
                    <i class="fas fa-vial"></i>
                    Resultados de las Pruebas
                </h2>
            </div>
            <!-- Aquí van los resultados de las pruebas -->
        </div>
    </div>

    <script>
        // Función para actualizar el estado de conexión
        function updateConnectionStatus(isConnected) {
            const badge = document.querySelector('.status-badge');
            if (isConnected) {
                badge.className = 'status-badge status-success';
                badge.innerHTML = '<i class="fas fa-check-circle"></i> Conectado';
            } else {
                badge.className = 'status-badge status-error';
                badge.innerHTML = '<i class="fas fa-times-circle"></i> Desconectado';
            }
        }

        // Función para mostrar mensajes de error
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            document.querySelector('.status-content').appendChild(errorDiv);
        }
    </script>
</body>
</html>