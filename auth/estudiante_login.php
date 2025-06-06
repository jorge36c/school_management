<?php
session_start();
require_once '../config/database.php';

// Inicializar mensaje de error
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Buscar el estudiante por usuario
        $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug
        error_log("Intento de login - Usuario: " . $usuario);
        error_log("Datos del estudiante encontrado: " . print_r($estudiante, true));

        if ($estudiante && password_verify($password, $estudiante['password'])) {
            // Login exitoso
            $_SESSION['estudiante_id'] = $estudiante['id'];
            $_SESSION['estudiante_nombre'] = $estudiante['nombre'];
            $_SESSION['estudiante_apellido'] = $estudiante['apellido'];

            // Debug
            error_log("Login exitoso - ID: " . $estudiante['id']);
            error_log("Sesión establecida: " . print_r($_SESSION, true));

            header('Location: ../estudiante/dashboard.php');
            exit();
        } else {
            $error = 'Usuario o contraseña incorrectos';
            error_log("Login fallido - Usuario o contraseña incorrectos");
        }
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        $error = 'Error al intentar iniciar sesión';
    }
}

// Si ya está logueado, redirigir al dashboard de estudiante
if(isset($_SESSION['estudiante_id'])) {
    header('Location: ../estudiante/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Estudiante - Sistema Escolar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="waves">
        <svg class="waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none">
            <defs>
                <path id="wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
            </defs>
            <g class="parallax">
                <use href="#wave" x="48" y="0" fill="rgba(255,255,255,0.7" />
                <use href="#wave" x="48" y="3" fill="rgba(255,255,255,0.5)" />
                <use href="#wave" x="48" y="5" fill="rgba(255,255,255,0.3)" />
                <use href="#wave" x="48" y="7" fill="#fff" />
            </g>
        </svg>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="school-logo">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h1>Estudiante</h1>
                <p>Ingresa tus credenciales para continuar</p>
            </div>

            <?php if(isset($_GET['error'])): ?>
            <div class="error-message show">
                <i class="fas fa-exclamation-circle"></i>
                <span>Usuario o contraseña incorrectos</span>
            </div>
            <?php endif; ?>

            <form action="authenticate.php" method="POST">
                <!-- Campo oculto para identificar que es login de estudiante -->
                <input type="hidden" name="role" value="estudiante">

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               name="username" 
                               placeholder="Usuario"
                               required 
                               autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               placeholder="Contraseña"
                               required>
                        <span class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <span>Iniciar Sesión</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> 
                <span>Volver al inicio</span>
            </a>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>

    <?php
    // Cuando el login es exitoso
    if(isset($estudiante)):
        $_SESSION['estudiante_id'] = $estudiante['id']; // Asegúrate de que este es el ID correcto
    endif;
    ?>
</body>
</html>