<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Verificar la conexión con la base de datos
if (!$pdo) {
    $_SESSION['error'] = 'Error de conexión con la base de datos.';
    header('Location: list_students.php');
    exit();
}

// Verificar si el id del estudiante está presente
if (!isset($_GET['id'])) {
    header('Location: list_students.php');
    exit();
}

$id = $_GET['id'];

try {
    // Obtener los datos del estudiante
    $sql = "SELECT * FROM estudiantes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if (!$stmt) {
        $_SESSION['error'] = 'Error al preparar la consulta SQL.';
        header('Location: list_students.php');
        exit();
    }
    $stmt->execute([$id]);
    $estudiante = $stmt->fetch();

    if (!$estudiante) {
        $_SESSION['error'] = "Estudiante no encontrado.";
        header('Location: list_students.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al obtener los datos del estudiante: " . $e->getMessage();
    header('Location: list_students.php');
    exit();
}

// Actualizar los datos del estudiante si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $documento_tipo = $_POST['documento_tipo'];
    $documento_numero = $_POST['documento_numero'];
    $nombre_acudiente = $_POST['nombre_acudiente'];
    $telefono_acudiente = $_POST['telefono_acudiente'];

    try {
        $sql = "UPDATE estudiantes SET nombre = ?, apellido = ?, documento_tipo = ?, documento_numero = ?, nombre_acudiente = ?, telefono_acudiente = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellido, $documento_tipo, $documento_numero, $nombre_acudiente, $telefono_acudiente, $id]);

        $_SESSION['mensaje'] = "Datos del estudiante actualizados con éxito.";
        header('Location: list_students.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar los datos del estudiante: " . $e->getMessage();
        header('Location: edit_student.php?id=' . $id);
        exit();
    }
}
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
</head>
<body>
<div class="admin-container">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['mensaje'])): ?>
    <div class="success-message">
        <?php echo htmlspecialchars($_SESSION['mensaje']); unset($_SESSION['mensaje']); ?>
    </div>
<?php endif; ?>

    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <button id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumb">
                    <i class="fas fa-user-graduate"></i>
                    <span>/ Editar Estudiante</span>
                </div>
            </div>
            <div class="top-bar-right">
                <a href="../../auth/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user-graduate text-primary"></i>
                        Editar Estudiante
                    </h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($estudiante['nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($estudiante['apellido']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="documento_tipo">Tipo de Documento</label>
                            <input type="text" id="documento_tipo" name="documento_tipo" value="<?php echo htmlspecialchars($estudiante['documento_tipo']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="documento_numero">Número de Documento</label>
                            <input type="text" id="documento_numero" name="documento_numero" value="<?php echo htmlspecialchars($estudiante['documento_numero']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="nombre_acudiente">Nombre del Acudiente</label>
                            <input type="text" id="nombre_acudiente" name="nombre_acudiente" value="<?php echo htmlspecialchars($estudiante['nombre_acudiente']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="telefono_acudiente">Teléfono del Acudiente</label>
                            <input type="text" id="telefono_acudiente" name="telefono_acudiente" value="<?php echo htmlspecialchars($estudiante['telefono_acudiente']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="list_students.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
    });
</script>
</body>
</html>
