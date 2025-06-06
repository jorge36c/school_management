<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Mensaje de error y éxito
$error = null;
$success = null;

// Obtener las sedes activas
try {
    $query_sedes = "SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre ASC";
    $sedes = $pdo->query($query_sedes)->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error al cargar las sedes: " . $e->getMessage();
}

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Debug: Imprimir los datos recibidos
        error_log("Datos POST recibidos:");
        error_log("sede_id: " . ($_POST['sede_id'] ?? 'no set'));
        error_log("nivel: " . ($_POST['nivel'] ?? 'no set'));
        error_log("grado_id: " . ($_POST['grado_id'] ?? 'no set'));

        $pdo->beginTransaction();

        // Validación y limpieza de datos
        $usuario = trim($_POST['usuario']);
        $password = trim($_POST['password']);
        $nombre = trim($_POST['nombres']);
        $apellido = trim($_POST['apellidos']);
        $documento_tipo = trim($_POST['tipo_documento']);
        $documento_numero = trim($_POST['numero_documento']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
        $genero = trim($_POST['genero']);
        $direccion = trim($_POST['direccion']);
        $email = trim($_POST['email']) ?: null;
        $sede_id = $_POST['sede_id'] ?? null;
        $nivel = $_POST['nivel'] ?? null;
        $grado_id = !empty($_POST['grado_id']) ? $_POST['grado_id'] : null;

        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE usuario = ? OR email = ? OR documento_numero = ?");
        $stmt->execute([$usuario, $email, $documento_numero]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Ya existe un estudiante con ese usuario, email o número de documento');
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar estudiante con grado_id
        $stmt = $pdo->prepare("
            INSERT INTO estudiantes (
                usuario, password, nombre, apellido, 
                documento_tipo, documento_numero, 
                fecha_nacimiento, genero, direccion,
                email, estado, sede_id, nivel, grado_id
            ) VALUES (
                :usuario, :password, :nombre, :apellido,
                :documento_tipo, :documento_numero,
                :fecha_nacimiento, :genero, :direccion,
                :email, 'Activo', :sede_id, :nivel, :grado_id
            )
        ");
        
        $stmt->execute([
            ':usuario' => $usuario,
            ':password' => $password_hash,
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':documento_tipo' => $documento_tipo,
            ':documento_numero' => $documento_numero,
            ':fecha_nacimiento' => $fecha_nacimiento,
            ':genero' => $genero,
            ':direccion' => $direccion,
            ':email' => $email,
            ':sede_id' => $sede_id,
            ':nivel' => $nivel,
            ':grado_id' => $grado_id
        ]);

        // Registrar en el historial de cambios de grado
        $estudiante_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("
            INSERT INTO historial_cambios_grado 
            (estudiante_id, grado_nuevo, fecha_cambio) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$estudiante_id, $grado_id]);

        $pdo->commit();
        header('Location: list_students.php?success=1');
        exit();

    } catch(Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Configurar el breadcrumb
$breadcrumb_path = [
    '/admin/dashboard.php' => 'Inicio',
    '/admin/users/list_students.php' => 'Estudiantes',
    '/admin/users/create_student.php' => 'Nuevo Estudiante'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Estudiante - Sistema Escolar</title>
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
        <?php include '../sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../components/topbar.php'; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin: 1rem;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="content-card">
                <div class="card-header">
                    <h2>Crear Nuevo Estudiante</h2>
                    <p>Complete todos los campos requeridos para registrar un nuevo estudiante en el sistema</p>
                </div>

                <form method="POST" id="studentForm">
                    <div class="form-sections">
                        <!-- Información de Cuenta -->
                        <div class="form-section">
                            <h3>
                                <i class="fas fa-user-shield"></i>
                                Información de Cuenta
                            </h3>
                            
                            <div class="form-group">
                                <label for="usuario">Usuario</label>
                                <input type="text" id="usuario" name="usuario" required>
                                <small class="form-text">Este será el nombre de usuario para iniciar sesión</small>
                            </div>

                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <input type="password" id="password" name="password" required>
                                <small class="form-text">La contraseña debe tener al menos 6 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="email">Email (Opcional)</label>
                                <input type="email" id="email" name="email">
                            </div>

                            <div class="form-group">
                                <label for="sede_id">Sede</label>
                                <select name="sede_id" id="sede_id" required onchange="cargarNiveles()">
                                    <option value="">Seleccione una sede...</option>
                                    <?php foreach ($sedes as $sede): ?>
                                        <option value="<?php echo $sede['id']; ?>">
                                            <?php echo htmlspecialchars($sede['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="nivel">Nivel Educativo</label>
                                <select name="nivel" id="nivel" required onchange="cargarGrados()">
                                    <option value="">Seleccione un nivel...</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="grado_id">Grado</label>
                                <select name="grado_id" id="grado_id" required>
                                    <option value="">Seleccione un grado...</option>
                                </select>
                            </div>
                        </div>

                        <!-- Información Personal -->
                        <div class="form-section">
                            <h3>
                                <i class="fas fa-address-card"></i>
                                Información Personal
                            </h3>

                            <div class="form-group">
                                <label for="nombres">Nombres</label>
                                <input type="text" id="nombres" name="nombres" required>
                            </div>

                            <div class="form-group">
                                <label for="apellidos">Apellidos</label>
                                <input type="text" id="apellidos" name="apellidos" required>
                            </div>

                            <div class="form-group">
                                <label for="tipo_documento">Tipo de Documento</label>
                                <select id="tipo_documento" name="tipo_documento" required>
                                    <option value="">Seleccione...</option>
                                    <option value="TI">Tarjeta de Identidad</option>
                                    <option value="CC">Cédula de Ciudadanía</option>
                                    <option value="RC">Registro Civil</option>
                                    <option value="CE">Cédula de Extranjería</option>
                                    <option value="PASAPORTE">Pasaporte</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="numero_documento">Número de Documento</label>
                                <input type="text" id="numero_documento" name="numero_documento" required>
                                <small class="form-text">Solo números, sin puntos ni guiones</small>
                            </div>

                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
                                <small class="form-text">La edad debe estar entre 3 y 20 años</small>
                            </div>

                            <div class="form-group">
                                <label for="genero">Género</label>
                                <select id="genero" name="genero" required>
                                    <option value="">Seleccione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                    <option value="O">Otro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="direccion">Dirección</label>
                                <input type="text" id="direccion" name="direccion" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="list_students.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Estudiante
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

    // Validación del formulario
    document.getElementById('studentForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const documento = document.getElementById('numero_documento').value;
        const fechaNacimiento = new Date(document.getElementById('fecha_nacimiento').value);
        const hoy = new Date();
        const edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
        
        let isValid = true;
        const errores = [];

        if (password.length < 6) {
            errores.push('La contraseña debe tener al menos 6 caracteres');
            isValid = false;
        }

        if (!/^\d+$/.test(documento)) {
            errores.push('El número de documento debe contener solo números');
            isValid = false;
        }

        if (edad < 3 || edad > 20) {
            errores.push('La edad del estudiante debe estar entre 3 y 20 años');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            alert(errores.join('\n'));
        }
    });

    // Configurar límites de fecha de nacimiento
    window.addEventListener('load', function() {
        const fechaNacimientoInput = document.getElementById('fecha_nacimiento');
        const hoy = new Date();
        const fechaMinima = new Date(hoy.getFullYear() - 20, hoy.getMonth(), hoy.getDate());
        const fechaMaxima = new Date(hoy.getFullYear() - 3, hoy.getMonth(), hoy.getDate());

        fechaNacimientoInput.max = fechaMaxima.toISOString().split('T')[0];
        fechaNacimientoInput.min = fechaMinima.toISOString().split('T')[0];
    });

    // Permitir solo números en el campo de documento
    document.getElementById('numero_documento').addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });

    // Confirmación al cancelar
    document.querySelector('.btn-secondary').addEventListener('click', function(e) {
        const form = document.querySelector('form');
        const hasChanges = Array.from(form.elements).some(element => element.value !== '');
        
        if (hasChanges) {
            if (!confirm('¿Está seguro que desea cancelar? Se perderán los datos ingresados.')) {
                e.preventDefault();
            }
        }
    });

    // Cargar niveles al seleccionar sede
    function cargarNiveles() {
        const sede_id = document.getElementById('sede_id').value;
        if (sede_id) {
            fetch(`get_niveles.php?sede_id=${sede_id}`)
                .then(response => response.json())
                .then(data => {
                    const nivelSelect = document.getElementById('nivel');
                    nivelSelect.innerHTML = '<option value="">Seleccione un nivel...</option>';
                    data.forEach(nivel => {
                        nivelSelect.innerHTML += `<option value="${nivel}">${nivel.charAt(0).toUpperCase() + nivel.slice(1)}</option>`;
                    });
                });
        }
    }

    // Cargar grados al seleccionar nivel
    function cargarGrados() {
        const sede_id = document.getElementById('sede_id').value;
        const nivel = document.getElementById('nivel').value;
        if (sede_id && nivel) {
            fetch(`get_grados.php?sede_id=${sede_id}&nivel=${nivel}`)
                .then(response => response.json())
                .then(data => {
                    const gradoSelect = document.getElementById('grado_id');
                    gradoSelect.innerHTML = '<option value="">Seleccione un grado...</option>';
                    data.forEach(grado => {
                        gradoSelect.innerHTML += `<option value="${grado.id}">${grado.nombre}</option>`;
                    });
                });
        }
    }
    </script>
</body>
</html>