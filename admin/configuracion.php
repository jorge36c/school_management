<?php
/**
 * Sistema de Gestión Escolar - Configuración del Sistema
 * 
 * Este archivo permite configurar los aspectos principales del sistema,
 * incluyendo la información institucional y la apariencia del sidebar.
 * 
 * @author  [Tu nombre]
 * @version 1.0
 */

// ==========================================
// INICIALIZACIÓN Y VERIFICACIÓN DE SEGURIDAD
// ==========================================

// Detectar y cargar el archivo de configuración
$config_file = '';
$possible_paths = [
    '../includes/config.php',
    __DIR__ . '/../includes/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/school_management/includes/config.php'
];

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $config_file = $path;
        break;
    }
}

// Cargar configuración o mostrar error
if (!empty($config_file)) {
    require_once($config_file);
} else {
    die("<div class='alert alert-danger m-4'>Error: No se pudo encontrar el archivo de configuración. Verifique la instalación del sistema.</div>");
}

// Iniciar sesión si aún no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación - redirigir si no está autenticado
if (!isset($_SESSION['admin_id'])) {
    header('Location: /school_management/auth/login.php');
    exit;
}

// ==========================================
// VERIFICACIÓN Y CREACIÓN DE LA TABLA
// ==========================================

// Verificar si existe la tabla de configuración
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'configuracion'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}

// Crear la tabla si no existe
if (!$table_exists) {
    $create_table_sql = "CREATE TABLE `configuracion` (
        `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_name` varchar(255) DEFAULT 'Sistema Escolar',
        `school_logo` varchar(255) DEFAULT NULL,
        `address` varchar(255) DEFAULT NULL,
        `current_year` int(11) DEFAULT NULL,
        `year_status` enum('activo','planificacion','cerrado') DEFAULT 'activo',
        `smtp_host` varchar(255) DEFAULT NULL,
        `smtp_port` int(11) DEFAULT NULL,
        `session_timeout` int(11) DEFAULT 30,
        `max_login_attempts` int(11) DEFAULT 3,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `sede` varchar(255) DEFAULT 'Sede Principal',
        `favicon` varchar(255) DEFAULT NULL,
        `sidebar_color` varchar(7) DEFAULT '#1a2b40',
        `sidebar_text_color` varchar(7) DEFAULT '#FFFFFF',
        `sidebar_style` varchar(20) DEFAULT 'default'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    try {
        if ($conn->query($create_table_sql) === TRUE) {
            // Insertar registro inicial
            $insert_sql = "INSERT INTO `configuracion` 
                          (`id`, `school_name`, `sede`, `sidebar_color`, `sidebar_text_color`, `sidebar_style`) 
                          VALUES (1, 'Sistema Escolar', 'Sede Principal', '#1a2b40', '#FFFFFF', 'default')";
            
            $conn->query($insert_sql);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        die("<div class='alert alert-danger m-4'>Error al crear la tabla de configuración: " . $e->getMessage() . "</div>");
    }
}

// ==========================================
// OBTENER CONFIGURACIÓN ACTUAL
// ==========================================

// Cargar configuración desde la base de datos
$sql_config = "SELECT * FROM configuracion WHERE id = 1";
$config_result = $conn->query($sql_config);

// Insertar configuración por defecto si no hay registros
if ($config_result->num_rows == 0) {
    $insert_default = "INSERT INTO configuracion 
                      (id, school_name, sede, sidebar_color, sidebar_text_color, sidebar_style) 
                      VALUES (1, 'Sistema Escolar', 'Sede Principal', '#1a2b40', '#FFFFFF', 'default')";
    $conn->query($insert_default);
    $config_result = $conn->query($sql_config);
}

$config = $config_result->fetch_assoc();

// ==========================================
// PROCESAR FORMULARIO
// ==========================================

// Inicializar mensajes
$success_message = "";
$error_message = "";

// Procesar el formulario cuando se envía
if (isset($_POST['guardar_config'])) {
    try {
        // Recoger y validar datos del formulario
        $school_name = trim($conn->real_escape_string($_POST['school_name']));
        $address = trim($conn->real_escape_string($_POST['address']));
        $sede = trim($conn->real_escape_string($_POST['sede']));
        $sidebar_color = trim($conn->real_escape_string($_POST['sidebar_color']));
        $sidebar_text_color = trim($conn->real_escape_string($_POST['sidebar_text_color']));
        $sidebar_style = trim($conn->real_escape_string($_POST['sidebar_style']));
        
        // Validaciones básicas
        if (empty($school_name)) {
            throw new Exception("El nombre de la institución es obligatorio.");
        }
        
        // Validar colores hexadecimales
        if (!preg_match('/^#[a-f0-9]{6}$/i', $sidebar_color)) {
            throw new Exception("El color del sidebar debe ser un valor hexadecimal válido (ej. #1a2b40).");
        }
        
        if (!preg_match('/^#[a-f0-9]{6}$/i', $sidebar_text_color)) {
            throw new Exception("El color del texto debe ser un valor hexadecimal válido (ej. #FFFFFF).");
        }
        
        // Validar estilo
        $valid_styles = ['default', 'gradient', 'flat'];
        if (!in_array($sidebar_style, $valid_styles)) {
            throw new Exception("El estilo seleccionado no es válido.");
        }
        
        // Procesar el logo si se ha subido uno nuevo
        $logo_path = $config['school_logo']; // Mantener el logo actual por defecto
        
        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
            // Validar tipo de archivo
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['school_logo']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($filetype, $allowed)) {
                throw new Exception("Tipo de archivo no permitido. Sólo se permiten JPG, JPEG, PNG y GIF.");
            }
            
            // Validar tamaño (máximo 2MB)
            if ($_FILES['school_logo']['size'] > 2097152) {
                throw new Exception("El archivo es demasiado grande. El tamaño máximo permitido es 2MB.");
            }
            
            // Crear un nombre único para el archivo
            $newname = 'school_logo_' . time() . '.' . $filetype;
            
            // Determinar la ruta para guardar el archivo
            $img_dir = '../assets/img/';
            if (!is_dir($img_dir)) {
                if (!file_exists('../assets/')) {
                    mkdir('../assets/', 0777, true);
                }
                mkdir($img_dir, 0777, true);
            }
            
            $target = $img_dir . $newname;
            
            // Mover el archivo subido
            if (!move_uploaded_file($_FILES['school_logo']['tmp_name'], $target)) {
                throw new Exception("Error al subir el archivo. Revise los permisos del directorio.");
            }
            
            $logo_path = '/school_management/assets/img/' . $newname;
            
            // Eliminar el logo anterior
            if (!empty($config['school_logo']) && $config['school_logo'] != '/school_management/assets/img/default_logo.png') {
                $old_logo = '..' . $config['school_logo'];
                if (file_exists($old_logo)) {
                    unlink($old_logo);
                }
            }
        }
        
        // Actualizar la configuración en la base de datos
        $conn->begin_transaction();
        
        $sql_update_config = "UPDATE configuracion SET 
                            school_name = ?, 
                            school_logo = ?, 
                            address = ?, 
                            sede = ?,
                            sidebar_color = ?,
                            sidebar_text_color = ?,
                            sidebar_style = ?,
                            updated_at = NOW()
                          WHERE id = 1";
        
        $stmt = $conn->prepare($sql_update_config);
        $stmt->bind_param("sssssss", $school_name, $logo_path, $address, $sede, $sidebar_color, $sidebar_text_color, $sidebar_style);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la configuración: " . $stmt->error);
        }
        
        // Registrar actividad en el log si existe la tabla
        $table_log_exists = $conn->query("SHOW TABLES LIKE 'actividad_log'")->num_rows > 0;
        
        if ($table_log_exists) {
            $accion = 'actualizar_configuracion';
            $descripcion = 'Actualización de configuración del sistema';
            $admin_id = $_SESSION['admin_id'];
            
            $sql_log = "INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id) 
                        VALUES ('configuracion', 1, ?, ?, ?)";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("ssi", $accion, $descripcion, $admin_id);
            $stmt_log->execute();
            $stmt_log->close();
        }
        
        $stmt->close();
        $conn->commit();
        
        // Recargar los datos de configuración
        $config_result = $conn->query("SELECT * FROM configuracion WHERE id = 1");
        $config = $config_result->fetch_assoc();
        
        // Mensaje de éxito
        $success_message = "La configuración ha sido actualizada correctamente.";
        
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        $error_message = $e->getMessage();
    }
}

// ==========================================
// PREPARAR INTERFAZ DE USUARIO
// ==========================================

// Título de la página
$page_title = "Configuración del Sistema";

// Rutas a los archivos de inclusión
$header_file = '';
$sidebar_file = '';
$topbar_file = '';
$footer_file = '';

// Buscar las ubicaciones correctas de los archivos de inclusión
$include_paths = [
    'base' => '../includes/',
    'root' => $_SERVER['DOCUMENT_ROOT'] . '/school_management/includes/'
];

foreach ($include_paths as $base_path) {
    if (file_exists($base_path . 'header.php')) {
        $header_file = $base_path . 'header.php';
        $sidebar_file = $base_path . 'sidebar.php';
        $topbar_file = $base_path . 'topbar.php';
        $footer_file = $base_path . 'footer.php';
        break;
    }
}

// Incluir archivos de plantilla
if (!empty($header_file) && file_exists($header_file)) include($header_file);
if (!empty($sidebar_file) && file_exists($sidebar_file)) include($sidebar_file);
?>

<div class="main-content">
    <?php 
    // Verificar que el topbar existe antes de incluirlo
    if (!empty($topbar_file) && file_exists($topbar_file)) {
        include($topbar_file);
    } else {
        // Topbar simplificado si no se encuentra el archivo
        echo '<div class="container-fluid py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">' . $page_title . '</h1>
                <a href="' . $base_url . '/auth/logout.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Cerrar sesión
                </a>
            </div>
        </div>';
    }
    ?>

    <div class="container-fluid py-4">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Configuración del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data" id="configForm">
                            <!-- Nav tabs para organizar el contenido -->
                            <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" 
                                            type="button" role="tab" aria-controls="general" aria-selected="true">
                                        <i class="fas fa-school me-2"></i>Información General
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" 
                                            type="button" role="tab" aria-controls="appearance" aria-selected="false">
                                        <i class="fas fa-palette me-2"></i>Apariencia
                                    </button>
                                </li>
                            </ul>
                            
                            <!-- Tab panes -->
                            <div class="tab-content">
                                <!-- Pestaña de Información General -->
                                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="school_name" class="form-label">Nombre de la Institución</label>
                                                <input type="text" class="form-control" id="school_name" name="school_name" 
                                                       value="<?php echo htmlspecialchars($config['school_name']); ?>" required>
                                                <small class="text-muted">Este nombre aparecerá en el sidebar y en los reportes</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="sede" class="form-label">Sede Principal</label>
                                                <input type="text" class="form-control" id="sede" name="sede" 
                                                       value="<?php echo htmlspecialchars($config['sede']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="address" class="form-label">Dirección</label>
                                                <input type="text" class="form-control" id="address" name="address" 
                                                       value="<?php echo htmlspecialchars($config['address']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="school_logo" class="form-label">Logo de la Institución</label>
                                                <input type="file" class="form-control" id="school_logo" name="school_logo">
                                                <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG, GIF. Máximo 2MB.</small>
                                            </div>
                                            
                                            <?php if (!empty($config['school_logo'])): ?>
                                                <div class="mt-3">
                                                    <label class="form-label">Logo Actual</label>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars($config['school_logo']); ?>" alt="Logo actual" 
                                                             class="img-thumbnail me-3" style="max-height: 80px;">
                                                        <span class="text-muted small">
                                                            <?php echo basename($config['school_logo']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña de Apariencia -->
                                <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                                    <h5 class="mb-3">Personalización del Sidebar</h5>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="sidebar_color" class="form-label">Color Principal</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-fill-drip"></i></span>
                                                    <input type="color" class="form-control form-control-color" id="sidebar_color" name="sidebar_color" 
                                                           value="<?php echo htmlspecialchars($config['sidebar_color'] ?: '#1a2b40'); ?>">
                                                    <input type="text" class="form-control" id="sidebar_color_hex" 
                                                           value="<?php echo htmlspecialchars($config['sidebar_color'] ?: '#1a2b40'); ?>">
                                                </div>
                                                <small class="text-muted">Color base para el sidebar</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="sidebar_text_color" class="form-label">Color del Texto</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-font"></i></span>
                                                    <input type="color" class="form-control form-control-color" id="sidebar_text_color" name="sidebar_text_color" 
                                                           value="<?php echo htmlspecialchars($config['sidebar_text_color'] ?: '#FFFFFF'); ?>">
                                                    <input type="text" class="form-control" id="sidebar_text_color_hex" 
                                                           value="<?php echo htmlspecialchars($config['sidebar_text_color'] ?: '#FFFFFF'); ?>">
                                                </div>
                                                <small class="text-muted">Color para el texto y los iconos</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Estilo del Sidebar</label>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="sidebar_style" id="style_default" value="default" 
                                                               <?php echo ($config['sidebar_style'] == 'default' || empty($config['sidebar_style'])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="style_default">Predeterminado</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="sidebar_style" id="style_gradient" value="gradient" 
                                                               <?php echo ($config['sidebar_style'] == 'gradient') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="style_gradient">Degradado</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="sidebar_style" id="style_flat" value="flat" 
                                                               <?php echo ($config['sidebar_style'] == 'flat') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="style_flat">Plano</label>
                                                    </div>
                                                </div>
                                                <small class="text-muted">Determina cómo se aplican los colores</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card border">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">Vista Previa</h6>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary refresh-preview">
                                                        <i class="fas fa-sync-alt"></i> Actualizar
                                                    </button>
                                                </div>
                                                <div class="card-body">
                                                    <div id="sidebar-preview" style="width: 100%; height: 200px; border-radius: 8px; overflow: hidden; 
                                                                                    background: linear-gradient(135deg, #1a2b40, #2563eb); color: white; 
                                                                                    display: flex; flex-direction: column; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                                        <div style="height: 60px; background: rgba(0,0,0,0.2); display: flex; align-items: center; padding: 0 15px; 
                                                                    border-bottom: 1px solid rgba(255,255,255,0.1);">
                                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                                <i class="fas fa-graduation-cap" style="font-size: 24px;"></i>
                                                                <span style="font-weight: bold; font-size: 18px;"><?php echo htmlspecialchars($config['school_name']); ?></span>
                                                            </div>
                                                        </div>
                                                        <div style="padding: 15px; overflow-y: auto; flex: 1;">
                                                            <div style="margin-bottom: 15px; color: rgba(255,255,255,0.7); font-size: 12px; font-weight: bold; text-transform: uppercase;">
                                                                GENERAL
                                                            </div>
                                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding: 10px; 
                                                                        border-radius: 5px; background: rgba(255,255,255,0.15); border-left: 3px solid #fff;">
                                                                <i class="fas fa-home"></i>
                                                                <span>Dashboard</span>
                                                            </div>
                                                            
                                                            <div style="margin: 15px 0; color: rgba(255,255,255,0.7); font-size: 12px; font-weight: bold; text-transform: uppercase;">
                                                                ACADÉMICO
                                                            </div>
                                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding: 10px; border-radius: 5px;">
                                                                <i class="fas fa-book"></i>
                                                                <span>Asignaturas</span>
                                                            </div>
                                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding: 10px; border-radius: 5px;">
                                                                <i class="fas fa-users"></i>
                                                                <span>Estudiantes</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                                </a>
                                <button type="submit" name="guardar_config" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Configuración
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-control-color {
    min-width: 3rem;
}

.nav-tabs .nav-link.active {
    border-color: transparent;
    border-bottom: 2px solid #2563eb;
    background-color: transparent;
    color: #2563eb;
    font-weight: 500;
}

.nav-tabs .nav-link {
    color: #6c757d;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link:hover:not(.active) {
    background-color: rgba(37, 99, 235, 0.05);
    border-color: transparent;
}

/* Estilos para los selectores de color */
.input-group .form-control-color {
    border-radius: 0;
}

.input-group .form-control-color::-webkit-color-swatch-wrapper {
    padding: 0;
}

.input-group .form-control-color::-webkit-color-swatch {
    border: none;
    border-radius: 0;
}

/* Animación para la vista previa */
.preview-update {
    animation: preview-pulse 0.5s;
}

@keyframes preview-pulse {
    0% { transform: scale(0.98); opacity: 0.8; }
    50% { transform: scale(1.01); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos
    const sidebarColor = document.getElementById('sidebar_color');
    const sidebarColorHex = document.getElementById('sidebar_color_hex');
    const sidebarTextColor = document.getElementById('sidebar_text_color');
    const sidebarTextColorHex = document.getElementById('sidebar_text_color_hex');
    const styleOptions = document.querySelectorAll('input[name="sidebar_style"]');
    const preview = document.getElementById('sidebar-preview');
    const schoolNameInput = document.getElementById('school_name');
    const refreshButton = document.querySelector('.refresh-preview');
    
    // Vincular el selector de color con el campo de texto hexadecimal
    sidebarColor.addEventListener('input', function() {
        sidebarColorHex.value = this.value;
        updatePreview();
    });
    
    sidebarTextColor.addEventListener('input', function() {
        sidebarTextColorHex.value = this.value;
        updatePreview();
    });
    
    // También permitir entrada manual de color hexadecimal
    sidebarColorHex.addEventListener('input', function() {
        const color = this.value;
        if (/^#[0-9A-F]{6}$/i.test(color)) {
            sidebarColor.value = color;
            updatePreview();
        }
    });
    
    sidebarTextColorHex.addEventListener('input', function() {
        const color = this.value;
        if (/^#[0-9A-F]{6}$/i.test(color)) {
            sidebarTextColor.value = color;
            updatePreview();
        }
    });
    
    // Actualizar cuando cambia el estilo del sidebar
    styleOptions.forEach(option => {
        option.addEventListener('change', updatePreview);
    });
    
    // Actualizar cuando cambia el nombre de la institución
    schoolNameInput.addEventListener('input', function() {
        // Actualizar// Actualizar cuando cambia el nombre de la institución
    schoolNameInput.addEventListener('input', function() {
        // Actualizar el nombre en la vista previa
        const nameElement = preview.querySelector('span');
        if (nameElement) {
            nameElement.textContent = this.value || 'Sistema Escolar';
        }
    });
    
    // Botón para refrescar la vista previa con efecto de animación
    refreshButton.addEventListener('click', function() {
        preview.classList.add('preview-update');
        updatePreview();
        setTimeout(() => {
            preview.classList.remove('preview-update');
        }, 500);
    });
    
    /**
     * Actualiza la vista previa del sidebar con la configuración actual
     */
    function updatePreview() {
        const primaryColor = sidebarColor.value;
        const textColor = sidebarTextColor.value;
        const style = document.querySelector('input[name="sidebar_style"]:checked').value;
        const schoolName = schoolNameInput.value || 'Sistema Escolar';
        
        // Actualizar color del texto
        preview.style.color = textColor;
        
        // Actualizar el nombre de la institución
        const nameElement = preview.querySelector('span');
        if (nameElement) {
            nameElement.textContent = schoolName;
        }
        
        // Actualizar iconos y otros elementos de texto
        const icons = preview.querySelectorAll('i');
        icons.forEach(icon => {
            icon.style.color = textColor;
        });
        
        // Actualizar el elemento activo
        const activeItem = preview.querySelector('div[style*="background: rgba(255,255,255,0.15)"]');
        if (activeItem) {
            activeItem.style.borderLeftColor = textColor;
        }
        
        // Calcular color secundario (ligeramente más claro)
        const secondaryColor = adjustBrightness(primaryColor, 30);
        
        // Actualizar fondo según el estilo
        if (style === 'flat') {
            preview.style.background = primaryColor;
        } else if (style === 'gradient') {
            preview.style.background = `linear-gradient(135deg, ${primaryColor}, ${secondaryColor})`;
        } else { // default
            preview.style.background = `linear-gradient(to bottom, ${primaryColor}, ${secondaryColor})`;
        }
    }
    
    /**
     * Ajusta el brillo de un color hexadecimal
     * @param {string} hex - Color hexadecimal (con o sin #)
     * @param {number} steps - Cantidad de ajuste (-255 a 255)
     * @returns {string} - Color hexadecimal ajustado
     */
    function adjustBrightness(hex, steps) {
        // Eliminar # si está presente
        hex = hex.replace('#', '');
        
        // Convertir a RGB
        let r = parseInt(hex.substring(0, 2), 16);
        let g = parseInt(hex.substring(2, 4), 16);
        let b = parseInt(hex.substring(4, 6), 16);
        
        // Ajustar brillo
        r = Math.min(255, Math.max(0, r + steps));
        g = Math.min(255, Math.max(0, g + steps));
        b = Math.min(255, Math.max(0, b + steps));
        
        // Convertir de vuelta a hex
        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
    }
    
    // Inicializar la vista previa
    updatePreview();
    
    // Validación del formulario antes de enviar
    document.getElementById('configForm').addEventListener('submit', function(e) {
        // Validar el nombre de la institución
        if (!schoolNameInput.value.trim()) {
            e.preventDefault();
            alert('El nombre de la institución es obligatorio.');
            schoolNameInput.focus();
            return false;
        }
        
        // Validar colores hexadecimales
        const hexColorRegex = /^#[0-9A-F]{6}$/i;
        
        if (!hexColorRegex.test(sidebarColorHex.value)) {
            e.preventDefault();
            alert('El color del sidebar debe ser un valor hexadecimal válido (ej. #1a2b40).');
            sidebarColorHex.focus();
            return false;
        }
        
        if (!hexColorRegex.test(sidebarTextColorHex.value)) {
            e.preventDefault();
            alert('El color del texto debe ser un valor hexadecimal válido (ej. #FFFFFF).');
            sidebarTextColorHex.focus();
            return false;
        }
        
        return true;
    });
    
    // Cambiar entre pestañas mediante URL hash
    function checkHash() {
        const hash = window.location.hash;
        if (hash) {
            const tabId = hash.substring(1);
            const tab = document.querySelector(`#${tabId}-tab`);
            if (tab) {
                tab.click();
            }
        }
    }
    
    // Verificar hash al cargar la página
    checkHash();
    
    // Actualizar hash cuando cambia la pestaña
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function (e) {
            const targetId = e.target.getAttribute('data-bs-target').substring(1);
            window.location.hash = targetId;
        });
    });
    
    // Auto cerrar alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(alert => {
                // Verificar si bootstrap está disponible
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } else {
                    // Fallback si bootstrap no está disponible
                    alert.style.display = 'none';
                }
            });
        }, 5000);
    }
});
</script>

<?php 
// Incluir el footer si se encontró
if (!empty($footer_file) && file_exists($footer_file)) {
    include($footer_file);
}
?>