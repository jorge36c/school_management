<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    // Obtener la lista de padres
    $stmt = $pdo->query("SELECT * FROM padres_familia ORDER BY nombre, apellido");
    $padres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error en la base de datos: " . $e->getMessage();
    $padres = []; // Inicializar como array vacío en caso de error
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/layouts.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        
        <div class="main-content">
            <?php 
            $page_icon = 'fas fa-users';
            $page_title = 'Padres';
            $page_subtitle = 'Lista';
            include '../../includes/top_bar.php'; 
            ?>

            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Gestión de Padres</h1>
                    <a href="create_parent.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Padre</span>
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Documento</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($padres)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        No hay padres registrados en el sistema
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($padres as $padre): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($padre['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($padre['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($padre['email']); ?></td>
                                        <td><?php echo htmlspecialchars($padre['telefono']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($padre['documento_tipo']) && !empty($padre['documento_numero'])) {
                                                echo htmlspecialchars($padre['documento_tipo'] . ': ' . $padre['documento_numero']);
                                            } else {
                                                echo "No registrado";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $padre['estado'] === 'activo' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($padre['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <button class="btn-action btn-edit" title="Editar"
                                                        onclick="location.href='edit_parent.php?id=<?php echo $padre['id']; ?>'">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-action btn-delete" title="Eliminar"
                                                        onclick="confirmarEliminacion(<?php echo $padre['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                now.toLocaleTimeString('es-ES', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
        }
        setInterval(updateTime, 1000);
        updateTime();

        function confirmarEliminacion(id) {
            if (confirm('¿Está seguro que desea eliminar este padre?')) {
                window.location.href = `delete_parent.php?id=${id}`;
            }
        }
    </script>
</body>
</html>