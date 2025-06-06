<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Escolar</title>
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1 class="main-title">Sistema de Gestión Escolar</h1>
        
        <div class="card">
            <h2 class="select-title">Seleccione su tipo de usuario</h2>
            
            <div class="roles-grid">
                <!-- Administrador -->
                <div class="role-card" onclick="window.location.href='auth/login.php'">
                    <div class="role-icon">
                        <i class="fas fa-user-shield fa-xl"></i>
                    </div>
                    <h3 class="role-title">Administrador</h3>
                    <p class="role-description">Gestión completa del sistema</p>
                </div>

                <!-- Profesor -->
                <div class="role-card" onclick="window.location.href='auth/profesor_login.php'">
                    <div class="role-icon">
                        <i class="fas fa-chalkboard-teacher fa-xl"></i>
                    </div>
                    <h3 class="role-title">Profesor</h3>
                    <p class="role-description">Gestión de notas y cursos</p>
                </div>

                <!-- Estudiante -->
                <div class="role-card" onclick="window.location.href='auth/estudiante_login.php'">
                    <div class="role-icon">
                        <i class="fas fa-user-graduate fa-xl"></i>
                    </div>
                    <h3 class="role-title">Estudiante</h3>
                    <p class="role-description">Consulta de notas y materias</p>
                </div>

                <!-- Padre de Familia -->
                <div class="role-card" onclick="window.location.href='auth/padre_login.php'">
                    <div class="role-icon">
                        <i class="fas fa-users fa-xl"></i>
                    </div>
                    <h3 class="role-title">Padre de Familia</h3>
                    <p class="role-description">Seguimiento académico</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Script opcional para hacer la transición más suave -->
    <script>
        // Añadir efecto de hover mediante JavaScript
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('mouseover', () => {
                card.style.cursor = 'pointer';
            });
        });

        // Opcional: Añadir efecto de click
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', (e) => {
                // Añadir efecto visual antes de la redirección
                card.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    card.style.transform = 'scale(1)';
                }, 100);
            });
        });
    </script>
</body>
</html>