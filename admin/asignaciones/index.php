<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar que sea administrador
if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

// Obtener datos necesarios
try {
    // Obtener profesores activos
    $stmt = $pdo->prepare("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo 
                          FROM profesores 
                          WHERE estado = 'activo'");
    $stmt->execute();
    $profesores = $stmt->fetchAll();

    // Obtener períodos académicos activos
    $stmt = $pdo->prepare("SELECT id, nombre 
                          FROM periodos_academicos 
                          WHERE estado = 'activo'");
    $stmt->execute();
    $periodos = $stmt->fetchAll();

    // Obtener grupos disponibles
    $stmt = $pdo->prepare("SELECT g.*, s.nombre as sede_nombre 
                          FROM grupos g 
                          JOIN sedes s ON g.sede_id = s.id 
                          WHERE g.estado = 'activo'");
    $stmt->execute();
    $grupos = $stmt->fetchAll();

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignaciones - Sistema Escolar</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/layouts.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_icon = 'fas fa-chalkboard-teacher';
            $page_title = 'Asignaciones';
            $page_subtitle = 'Gestión de Asignaciones';
            include '../../includes/top_bar.php'; 
            ?>

            <div class="content-wrapper">
                <!-- Panel de Filtros -->
                <div class="filters-section">
                    <div class="card">
                        <div class="card-header">
                            <h3>Filtros</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Período Académico</label>
                                <select id="periodo" class="form-control">
                                    <?php foreach($periodos as $periodo): ?>
                                        <option value="<?= $periodo['id'] ?>"><?= $periodo['nombre'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Buscar Profesor</label>
                                <input type="text" id="buscarProfesor" class="form-control" 
                                       placeholder="Nombre del profesor...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Profesores -->
                <div class="data-section">
                    <div class="grid-container" id="listaProfesores">
                        <?php foreach($profesores as $profesor): ?>
                        <div class="profesor-card">
                            <div class="card">
                                <div class="card-header">
                                    <h5><?= htmlspecialchars($profesor['nombre_completo']) ?></h5>
                                </div>
                                <div class="card-body">
                                    <button class="btn btn-primary" 
                                            onclick="asignarGrupo(<?= $profesor['id'] ?>)">
                                        <i class="fas fa-plus"></i>
                                        Asignar Grupo
                                    </button>
                                    <div class="asignaciones-list mt-3">
                                        <!-- Aquí se cargarán las asignaciones -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Asignación -->
    <div class="modal" id="modalAsignacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar Grupo</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formAsignacion">
                        <input type="hidden" id="profesor_id" name="profesor_id">
                        <div class="mb-3">
                            <label>Grupo</label>
                            <select name="grupo_id" class="form-select">
                                <?php foreach($grupos as $grupo): ?>
                                    <option value="<?= $grupo['id'] ?>">
                                        <?= $grupo['nombre'] ?> - <?= $grupo['sede_nombre'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Asignatura</label>
                            <select name="asignatura_id" class="form-select">
                                <!-- Se cargará dinámicamente -->
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarAsignacion()">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/jquery-3.6.0.min.js"></script>
    <script src="asignaciones.js"></script>
</body>
</html> 