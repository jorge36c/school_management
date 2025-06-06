<?php
session_start();
// Si ya está logueado, redirigir al dashboard
if(isset($_SESSION['admin_id'])) {
    header('Location: ../admin/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - Sistema Escolar</title>
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
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1>Administrador</h1>
                <p>Ingresa tus credenciales para continuar</p>
            </div>

            <?php if(isset($_GET['error'])): ?>
            <div class="error-message show">
                <i class="fas fa-exclamation-circle"></i>
                <span>Usuario o contraseña incorrectos</span>
            </div>
            <?php endif; ?>

            <form action="authenticate.php" method="POST">
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
</body>
</html>