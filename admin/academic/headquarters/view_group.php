<?php
session_start();
if(!isset($_SESSION['admin_id'])) { header('Location: ../../../auth/login.php'); exit(); }

require_once '../../../config/database.php';

$grupo_id = $_GET['id'] ?? null;

if (!$grupo_id) { header('Location: ../../users/list_headquarters.php'); exit(); }

try {
    // Obtener información del grupo
    $sql = "SELECT g.*, s.nombre as sede_nombre, n.nivel 
            FROM grupos g 
            JOIN sedes s ON g.sede_id = s.id 
            JOIN niveles_sede n ON g.sede_id = n.sede_id AND g.nivel = n.nivel 
            WHERE g.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$grupo_id]);
    $grupo = $stmt->fetch();

    if (!$grupo) { header('Location: ../../users/list_headquarters.php'); exit(); }

    // Obtener estudiantes del grupo
    $sql = "SELECT * FROM estudiantes WHERE grupo_id = ? ORDER BY apellidos, nombres";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$grupo_id]);
    $estudiantes = $stmt->fetchAll();

    // Obtener estudiantes disponibles para agregar al grupo
    $sql = "SELECT * FROM estudiantes 
            WHERE sede_id = ? 
            AND nivel = ? 
            AND (grupo_id IS NULL OR grupo_id != ?) 
            ORDER BY apellidos, nombres";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$grupo['sede_id'], $grupo['nivel'], $grupo_id]);
    $estudiantes_disponibles = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo <?php echo htmlspecialchars($grupo['nombre']); ?> - Estudiantes</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../../assets/css/variables.css">
    <link rel="stylesheet" href="../../../assets/css/common.css">
    <link rel="stylesheet" href="../../../assets/css/layouts.css">
    <link rel="stylesheet" href="../../../assets/css/components/top_bar.css">
    <link rel="stylesheet" href="../../../assets/css/pages/view_group.css">
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../../sidebar.php'; ?>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-info">
                    <div class="page-title">
                        <i class="fas fa-users"></i>
                        Grupo <?php echo htmlspecialchars($grupo['nombre']); ?>
                    </div>
                    <div class="page-subtitle">
                        <?php echo htmlspecialchars($grupo['sede_nombre']); ?> - 
                        Nivel <?php echo ucfirst($grupo['nivel']); ?>
                    </div>
                </div>

                <div class="top-bar-actions">
                    <div class="time-display">
                        <i class="far fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <a href="edit_group.php?id=<?php echo $grupo_id; ?>" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </div>

            <div class="group-content">
                <!-- Estadísticas -->
                <div class="stats-section">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($estudiantes); ?></div>
                        <div class="stat-label">
                            <i class="fas fa-user-graduate"></i>
                            Estudiantes
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $grupo['capacidad']; ?></div>
                        <div class="stat-label">
                            <i class="fas fa-users"></i>
                            Capacidad Máxima
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php echo $grupo['capacidad'] - count($estudiantes); ?>
                        </div>
                        <div class="stat-label">
                            <i class="fas fa-chair"></i>
                            Cupos Disponibles
                        </div>
                    </div>
                </div>

                <!-- Lista de estudiantes -->
                <div class="students-section">
                    <div class="section-header">
                        <h2>Estudiantes del Grupo</h2>
                        <button class="btn-add" onclick="mostrarFormularioAsignar()">
                            <i class="fas fa-user-plus"></i>
                            Asignar Estudiante
                        </button>
                    </div>

                    <!-- Formulario para asignar estudiante (oculto por defecto) -->
                    <div id="formAsignar" class="assign-form" style="display: none;">
                        <form method="POST" action="assign_student.php">
                            <input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>">
                            <div class="form-group">
                                <label for="estudiante_id">Seleccionar Estudiante:</label>
                                <select id="estudiante_id" name="estudiante_id" required>
                                    <option value="">Seleccione un estudiante...</option>
                                    <?php foreach($estudiantes_disponibles as $estudiante): ?>
                                        <option value="<?php echo $estudiante['id']; ?>">
                                            <?php echo htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-save">
                                    <i class="fas fa-save"></i>
                                    Asignar
                                </button>
                                <button type="button" class="btn-cancel" onclick="ocultarFormularioAsignar()">
                                    <i class="fas fa-times"></i>
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tabla de estudiantes -->
                    <div class="students-table">
                        <?php if (count($estudiantes) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Apellidos</th>
                                        <th>Nombres</th>
                                        <th>Documento</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($estudiantes as $estudiante): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($estudiante['apellidos']); ?></td>
                                            <td><?php echo htmlspecialchars($estudiante['nombres']); ?></td>
                                            <td><?php echo htmlspecialchars($estudiante['documento']); ?></td>
                                            <td>
                                                <span class="estado-badge <?php echo $estudiante['estado']; ?>">
                                                    <?php echo ucfirst($estudiante['estado']); ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <button onclick="window.location.href='view_student.php?id=<?php echo $estudiante['id']; ?>'" class="btn-view">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="removerEstudiante(<?php echo $estudiante['id']; ?>)" class="btn-remove">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-students">No hay estudiantes asignados a este grupo</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
        
        setInterval(updateTime, 1000);
        updateTime();

        // Funciones para gestionar el formulario
        function mostrarFormularioAsignar() {
            document.getElementById('formAsignar').style.display = 'block';
        }

        function ocultarFormularioAsignar() {
            document.getElementById('formAsignar').style.display = 'none';
        }

        function removerEstudiante(id) {
            if(confirm('¿Está seguro que desea remover este estudiante del grupo?')) {
                window.location.href = 'remove_student.php?id=' + id + '&grupo_id=<?php echo $grupo_id; ?>';
            }
        }
    </script>
</body>
</html> 