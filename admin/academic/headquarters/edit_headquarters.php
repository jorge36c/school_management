<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

// Obtener el ID de la sede
$sede_id = $_GET['id'] ?? null;

if (!$sede_id) {
    header('Location: ../../users/list_headquarters.php');
    exit();
}

try {
    // Obtener datos de la sede
    $sql = "SELECT * FROM sedes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sede_id]);
    $sede = $stmt->fetch();

    if (!$sede) {
        header('Location: ../../users/list_headquarters.php');
        exit();
    }

    // Procesar el formulario cuando se envía
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = $_POST['nombre'];
        $codigo_dane = $_POST['codigo_dane'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];
        $tipo_ensenanza = $_POST['tipo_ensenanza'];
        $estado = $_POST['estado'];

        $sql = "UPDATE sedes SET 
        nombre = ?, 
        codigo_dane = ?,
        direccion = ?,
        telefono = ?,
        tipo_ensenanza = ?,
        estado = ?
        WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $codigo_dane, $direccion, $telefono, $tipo_ensenanza, $estado, $sede_id]);

        // Redirigir con mensaje de éxito
        header('Location: view_headquarters.php?id=' . $sede_id . '&success=sede_actualizada');
        exit();
    }

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Configurar el título de la página y el breadcrumb
$page_title = "Editar Sede";
$current_page = 'Edit Headquarters';
$breadcrumb_path = [
    'Inicio',
    'Sedes',
    'Editar ' . ($sede['nombre'] ?? '')
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Sede - <?php echo htmlspecialchars($sede['nombre']); ?></title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos base -->
    <link rel="stylesheet" href="../../../assets/css/variables.css">
    <link rel="stylesheet" href="../../../assets/css/common.css">
    <link rel="stylesheet" href="../../../assets/css/layouts.css">
    
    <style>
    :root {
        --primary: #4f46e5;
        --primary-light: #6366f1;
        --primary-dark: #4338ca;
        --primary-50: rgba(79, 70, 229, 0.05);
        --primary-100: rgba(79, 70, 229, 0.1);
        --success: #10b981;
        --success-50: rgba(16, 185, 129, 0.05);
        --danger: #ef4444;
        --danger-50: rgba(239, 68, 68, 0.05);
        --warning: #f59e0b;
        --warning-50: rgba(245, 158, 11, 0.05);
        --info: #3b82f6;
        --info-50: rgba(59, 130, 246, 0.05);
        --dark: #1f2937;
        --light: #f9fafb;
        --gray: #6b7280;
        --gray-light: #e5e7eb;
        --gray-lighter: #f3f4f6;
        
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        
        --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        
        --transition: all 0.2s ease;
    }

    body {
        background-color: #f8fafc;
        color: var(--dark);
        font-family: 'Inter', sans-serif;
    }

    /* === CONTENT WRAPPER === */
    .content-wrapper {
        padding: 1rem;
        max-width: 1000px;
        margin: 0 auto;
    }

    /* === FORM CONTAINER === */
    .edit-form-container {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        margin-bottom: 1rem;
        position: relative;
    }

    .edit-form-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 4px;
        width: 100%;
        background: linear-gradient(to right, var(--primary), var(--primary-light));
    }

    .edit-form-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--gray-light);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .edit-form-header i {
        font-size: 1.25rem;
        color: var(--primary);
        background-color: var(--primary-50);
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
    }

    .edit-form-header h2 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--dark);
    }

    .edit-form {
        padding: 1.5rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .form-grid .form-group.full-width {
        grid-column: span 2;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.35rem;
        font-weight: 600;
        color: var(--dark);
        font-size: 0.9rem;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.6rem 0.8rem;
        border: 1px solid var(--gray-light);
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        transition: var(--transition);
        background-color: var(--light);
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px var(--primary-50);
    }

    .form-group .help-text {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.8rem;
        color: var(--gray);
    }

    .form-actions {
        padding-top: 1rem;
        border-top: 1px solid var(--gray-light);
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: var(--transition);
        border: none;
    }

    .btn-save {
        background: var(--primary);
        color: white;
        box-shadow: var(--shadow-sm);
    }

    .btn-save:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    .btn-cancel {
        background: var(--gray-light);
        color: var(--dark);
        text-decoration: none;
    }

    .btn-cancel:hover {
        background: var(--gray);
        color: white;
    }

    /* === ALERTS === */
    .alert {
        padding: 1rem;
        border-radius: var(--radius-md);
        margin: 0 1rem 1rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-error {
        background-color: var(--danger-50);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-grid .form-group.full-width {
            grid-column: span 1;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../../sidebar.php'; ?>

        <div class="main-content">
            <?php include '../../components/topbar.php'; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php endif; ?>

            <div class="content-wrapper">
                <div class="edit-form-container fade-in">
                    <div class="edit-form-header">
                        <i class="fas fa-building"></i>
                        <h2>Editar <?php echo htmlspecialchars($sede['nombre']); ?></h2>
                    </div>
                    
                    <form method="POST" class="edit-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre">Nombre de la Sede *</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($sede['nombre']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="codigo_dane">Código DANE *</label>
                                <input type="text" id="codigo_dane" name="codigo_dane" value="<?php echo htmlspecialchars($sede['codigo_dane']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="direccion">Dirección *</label>
                                <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($sede['direccion']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="telefono">Teléfono *</label>
                                <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($sede['telefono']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="tipo_ensenanza">Tipo de Enseñanza *</label>
                                <select id="tipo_ensenanza" name="tipo_ensenanza" required>
                                    <option value="regular" <?php echo $sede['tipo_ensenanza'] === 'regular' ? 'selected' : ''; ?>>Regular</option>
                                    <option value="multigrado" <?php echo $sede['tipo_ensenanza'] === 'multigrado' ? 'selected' : ''; ?>>Multigrado</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="estado">Estado *</label>
                                <select id="estado" name="estado" required>
                                    <option value="activo" <?php echo $sede['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo $sede['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="view_headquarters.php?id=<?php echo $sede_id; ?>" class="btn btn-cancel">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-save">
                                <i class="fas fa-save"></i>
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Actualizar reloj en caso de que esté en el topbar
    function updateTime() {
        const now = new Date();
        if (document.getElementById('current-time')) {
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }
    }
    
    setInterval(updateTime, 1000);
    updateTime();

    // Confirmación antes de cancelar si hay cambios
    document.querySelector('.btn-cancel').addEventListener('click', function(e) {
        // Comprobar si el formulario ha sido modificado
        const formElements = document.querySelectorAll('input, select');
        let formModified = false;
        
        formElements.forEach(element => {
            if (element.value !== element.defaultValue) {
                formModified = true;
            }
        });
        
        if (formModified && !confirm('¿Está seguro que desea cancelar? Los cambios no guardados se perderán.')) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>