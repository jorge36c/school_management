<?php
session_start();
if(!isset($_SESSION['admin_id'])) { header('Location: ../../../auth/login.php'); exit(); }

require_once '../../../config/database.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Estudiante</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../../assets/css/variables.css">
    <link rel="stylesheet" href="../../../assets/css/common.css">
    <link rel="stylesheet" href="../../../assets/css/layouts.css">
    <link rel="stylesheet" href="../../../assets/css/components/top_bar.css">
    <link rel="stylesheet" href="../../../assets/css/pages/create_student.css">
    
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
                    <h2>Crear Nuevo Estudiante</h2>
                    <p>Complete todos los campos requeridos</p>
                </div>
                <div class="top-bar-actions">
                    <div class="time-display">
                        <i class="far fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                </div>
            </div>

            <div class="content-wrapper">
                <form method="POST" action="save_student.php" class="create-form">
                    <div class="form-sections">
                        <!-- Información de Cuenta -->
                        <div class="form-section">
                            <h3><i class="fas fa-user-circle"></i> Información de Cuenta</h3>
                            <div class="form-group">
                                <label for="usuario">Usuario</label>
                                <input type="text" id="usuario" name="usuario" required>
                            </div>
                            <div class="form-group">
                                <label for="contrasena">Contraseña</label>
                                <input type="password" id="contrasena" name="contrasena" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="sede_id">Sede</label>
                                <select id="sede_id" name="sede_id" required onchange="cargarNiveles(this.value)">
                                    <option value="">Seleccione una sede...</option>
                                    <?php
                                    $sql = "SELECT * FROM sedes ORDER BY nombre";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute();
                                    $sedes = $stmt->fetchAll();
                                    
                                    foreach($sedes as $sede): ?>
                                        <option value="<?php echo $sede['id']; ?>">
                                            <?php echo htmlspecialchars($sede['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="nivel">Nivel</label>
                                <select id="nivel" name="nivel" required onchange="cargarGrupos()" disabled>
                                    <option value="">Seleccione un nivel...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="grupo_id">Grupo</label>
                                <select id="grupo_id" name="grupo_id" disabled>
                                    <option value="">Seleccione un grupo...</option>
                                </select>
                            </div>
                        </div>

                        <!-- Información Personal -->
                        <div class="form-section">
                            <h3><i class="fas fa-address-card"></i> Información Personal</h3>
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
                                    <option value="CC">Cédula de Ciudadanía</option>
                                    <option value="TI">Tarjeta de Identidad</option>
                                    <option value="RC">Registro Civil</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="documento">Número de Documento</label>
                                <input type="text" id="documento" name="documento" required>
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

                        <!-- Información Académica -->
                        <div class="form-section">
                            <h3>
                                <i class="fas fa-school"></i>
                                Información Académica
                            </h3>

                            <div class="form-group">
                                <label for="sede_id">Sede</label>
                                <select id="sede_id" name="sede_id" class="form-control" required onchange="cargarNiveles(this.value)">
                                    <option value="">Seleccione una sede...</option>
                                    <?php
                                    $sql = "SELECT * FROM sedes WHERE estado = 'activo' ORDER BY nombre";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute();
                                    $sedes = $stmt->fetchAll();
                                    
                                    foreach($sedes as $sede): ?>
                                        <option value="<?php echo $sede['id']; ?>">
                                            <?php echo htmlspecialchars($sede['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="nivel">Nivel</label>
                                <select id="nivel" name="nivel" class="form-control" required onchange="cargarGrupos()" disabled>
                                    <option value="">Seleccione un nivel...</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="grupo_id">Grupo</label>
                                <select id="grupo_id" name="grupo_id" class="form-control" disabled>
                                    <option value="">Seleccione un grupo...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="window.location.href='list_students.php'" class="btn-cancel">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            Guardar Estudiante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function cargarNiveles(sedeId) {
        const nivelSelect = document.getElementById('nivel');
        const grupoSelect = document.getElementById('grupo_id');
        
        nivelSelect.disabled = true;
        grupoSelect.disabled = true;
        nivelSelect.innerHTML = '<option value="">Seleccione un nivel...</option>';
        grupoSelect.innerHTML = '<option value="">Seleccione un grupo...</option>';
        
        if (sedeId) {
            fetch(`get_niveles.php?sede_id=${sedeId}`)
                .then(response => response.json())
                .then(niveles => {
                    niveles.forEach(nivel => {
                        const option = document.createElement('option');
                        option.value = nivel.nivel;
                        option.textContent = nivel.nivel.charAt(0).toUpperCase() + nivel.nivel.slice(1);
                        nivelSelect.appendChild(option);
                    });
                    nivelSelect.disabled = false;
                })
                .catch(error => console.error('Error:', error));
        }
    }

    function cargarGrupos() {
        const sedeId = document.getElementById('sede_id').value;
        const nivel = document.getElementById('nivel').value;
        const grupoSelect = document.getElementById('grupo_id');
        
        grupoSelect.disabled = true;
        grupoSelect.innerHTML = '<option value="">Seleccione un grupo...</option>';
        
        if (sedeId && nivel) {
            fetch(`get_grupos.php?sede_id=${sedeId}&nivel=${nivel}`)
                .then(response => response.json())
                .then(grupos => {
                    grupos.forEach(grupo => {
                        const option = document.createElement('option');
                        option.value = grupo.id;
                        option.textContent = grupo.nombre;
                        grupoSelect.appendChild(option);
                    });
                    grupoSelect.disabled = false;
                })
                .catch(error => console.error('Error:', error));
        }
    }

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
    </script>
</body>
</html> 