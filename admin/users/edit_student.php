<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: list_students.php');
    exit();
}

// Obtener información del estudiante
try {
    $stmt = $pdo->prepare("
        SELECT e.*, s.nombre as sede_nombre, g.nombre as grado_nombre 
        FROM estudiantes e 
        LEFT JOIN sedes s ON e.sede_id = s.id 
        LEFT JOIN grados g ON e.grado_id = g.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $estudiante = $stmt->fetch();

    if (!$estudiante) {
        header('Location: list_students.php');
        exit();
    }

    // Obtener sedes activas
    $stmt = $pdo->query("SELECT * FROM sedes WHERE estado = 'activo' ORDER BY nombre");
    $sedes = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $nombre = trim($_POST['nombres']);
        $apellido = trim($_POST['apellidos']);
        $email = trim($_POST['email']) ?: null;
        $documento_tipo = trim($_POST['tipo_documento']);
        $documento_numero = trim($_POST['numero_documento']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
        $genero = trim($_POST['genero']);
        $direccion = trim($_POST['direccion']);
        $sede_id = $_POST['sede_id'] ?? null;
        $nivel = $_POST['nivel'] ?? null;
        $grado_id = !empty($_POST['grado_id']) ? $_POST['grado_id'] : null;

        // Verificar duplicados
        $stmt = $pdo->prepare("
            SELECT id FROM estudiantes 
            WHERE (documento_numero = ?) 
            AND id != ?
        ");
        $stmt->execute([$documento_numero, $id]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Ya existe un estudiante con ese número de documento');
        }

        // Verificar si el estudiante está cambiando de grado
        $stmt = $pdo->prepare("SELECT grado_id FROM estudiantes WHERE id = ?");
        $stmt->execute([$id]);
        $grado_actual = $stmt->fetchColumn();

        // Actualizar estudiante
        $stmt = $pdo->prepare("
            UPDATE estudiantes SET 
                nombre = ?, apellido = ?, email = ?,
                documento_tipo = ?, documento_numero = ?,
                fecha_nacimiento = ?, genero = ?, direccion = ?,
                sede_id = ?, nivel = ?, grado_id = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $nombre, $apellido, $email,
            $documento_tipo, $documento_numero,
            $fecha_nacimiento, $genero, $direccion,
            $sede_id, $nivel, $grado_id,
            $id
        ]);

        // Si el grado cambió, registrar en el historial
        if ($grado_actual != $grado_id) {
            $stmt = $pdo->prepare("
                INSERT INTO historial_cambios_grado 
                (estudiante_id, grado_anterior, grado_nuevo, fecha_cambio) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$id, $grado_actual, $grado_id]);
        }

        $pdo->commit();
        header('Location: list_students.php?success=2');
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
    '/admin/users/edit_student.php' => 'Editar Estudiante'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estudiante - Sistema Escolar</title>
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
        }

        .student-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../components/topbar.php'; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="margin: 1rem;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="content-card">
                <div class="card-header">
                    <h2>Editar Información del Estudiante</h2>
                </div>

                <div class="student-info">
                    <p><strong>Estudiante:</strong> <?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></p>
                    <p><strong>Documento:</strong> <?php echo htmlspecialchars($estudiante['documento_tipo'] . ' ' . $estudiante['documento_numero']); ?></p>
                </div>

                <form method="post" action="">
                    <div class="form-sections">
                        <!-- Información Personal -->
                        <div class="form-section">
                            <h3><i class="fas fa-user-circle"></i> Información Personal</h3>
                            
                            <div class="form-group">
                                <label for="nombres">Nombres</label>
                                <input type="text" id="nombres" name="nombres" 
                                       value="<?php echo htmlspecialchars($estudiante['nombre']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="apellidos">Apellidos</label>
                                <input type="text" id="apellidos" name="apellidos" 
                                       value="<?php echo htmlspecialchars($estudiante['apellido']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="tipo_documento">Tipo de Documento</label>
                                <select id="tipo_documento" name="tipo_documento" required>
                                    <option value="">Seleccione tipo...</option>
                                    <option value="TI" <?php echo ($estudiante['documento_tipo'] == 'TI') ? 'selected' : ''; ?>>Tarjeta de Identidad</option>
                                    <option value="CC" <?php echo ($estudiante['documento_tipo'] == 'CC') ? 'selected' : ''; ?>>Cédula de Ciudadanía</option>
                                    <option value="RC" <?php echo ($estudiante['documento_tipo'] == 'RC') ? 'selected' : ''; ?>>Registro Civil</option>
                                    <option value="CE" <?php echo ($estudiante['documento_tipo'] == 'CE') ? 'selected' : ''; ?>>Cédula de Extranjería</option>
                                    <option value="PASAPORTE" <?php echo ($estudiante['documento_tipo'] == 'PASAPORTE') ? 'selected' : ''; ?>>Pasaporte</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="numero_documento">Número de Documento</label>
                                <input type="text" id="numero_documento" name="numero_documento" 
                                       value="<?php echo htmlspecialchars($estudiante['documento_numero']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" 
                                       value="<?php echo htmlspecialchars($estudiante['fecha_nacimiento']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="genero">Género</label>
                                <select id="genero" name="genero" required>
                                    <option value="">Seleccione género...</option>
                                    <option value="M" <?php echo ($estudiante['genero'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="F" <?php echo ($estudiante['genero'] == 'F') ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="O" <?php echo ($estudiante['genero'] == 'O') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="direccion">Dirección</label>
                                <input type="text" id="direccion" name="direccion" 
                                       value="<?php echo htmlspecialchars($estudiante['direccion']); ?>">
                            </div>
                        </div>

                        <!-- Información Académica -->
                        <div class="form-section">
                            <h3><i class="fas fa-graduation-cap"></i> Información Académica</h3>
                            
                            <div class="form-group">
                                <label for="email">Email (Opcional)</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($estudiante['email'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="sede_id">Sede</label>
                                <select id="sede_id" name="sede_id" required onchange="cargarNiveles()">
                                    <option value="">Seleccione sede...</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT id, nombre FROM sedes WHERE estado = 'activo'");
                                    while ($sede = $stmt->fetch()) {
                                        $selected = ($sede['id'] == $estudiante['sede_id']) ? 'selected' : '';
                                        echo "<option value='{$sede['id']}' {$selected}>{$sede['nombre']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="nivel">Nivel</label>
                                <select id="nivel" name="nivel" required onchange="cargarGrados()">
                                    <option value="">Seleccione nivel...</option>
                                    <?php
                                    $niveles = ['preescolar', 'primaria', 'secundaria', 'media'];
                                    foreach ($niveles as $niv) {
                                        $selected = ($niv == $estudiante['nivel']) ? 'selected' : '';
                                        echo "<option value='{$niv}' {$selected}>" . ucfirst($niv) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="grado_id">Grado</label>
                                <select id="grado_id" name="grado_id" required>
                                    <option value="">Seleccione grado...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="list_students.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
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

    // Cargar niveles y grados al cargar la página
    window.addEventListener('load', function() {
        if (document.getElementById('sede_id').value) {
            cargarNiveles();
        }
    });

    function cargarNiveles() {
        const sede_id = document.getElementById('sede_id').value;
        if (sede_id) {
            fetch(`get_niveles.php?sede_id=${sede_id}`)
                .then(response => response.json())
                .then(data => {
                    const nivelSelect = document.getElementById('nivel');
                    nivelSelect.innerHTML = '<option value="">Seleccione nivel...</option>';
                    data.forEach(nivel => {
                        const selected = nivel === '<?php echo $estudiante['nivel']; ?>' ? 'selected' : '';
                        nivelSelect.innerHTML += `<option value="${nivel}" ${selected}>${nivel.charAt(0).toUpperCase() + nivel.slice(1)}</option>`;
                    });
                    // Si hay un nivel seleccionado, cargar los grados
                    if (nivelSelect.value) {
                        cargarGrados();
                    }
                });
        }
    }

    function cargarGrados() {
        const sede_id = document.getElementById('sede_id').value;
        const nivel = document.getElementById('nivel').value;
        if (sede_id && nivel) {
            fetch(`get_grados.php?sede_id=${sede_id}&nivel=${nivel}`)
                .then(response => response.json())
                .then(data => {
                    const gradoSelect = document.getElementById('grado_id');
                    gradoSelect.innerHTML = '<option value="">Seleccione grado...</option>';
                    data.forEach(grado => {
                        const selected = grado.id === '<?php echo $estudiante['grado_id']; ?>' ? 'selected' : '';
                        gradoSelect.innerHTML += `<option value="${grado.id}" ${selected}>${grado.nombre}</option>`;
                    });
                });
        }
    }
    </script>
</body>
</html>