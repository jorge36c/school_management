<?php
// Iniciar sesión para verificar autenticación
session_start();

// Asegurarse de que la respuesta sea JSON
header('Content-Type: application/json');

try {
    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'No tiene autorización para realizar esta acción']);
        exit;
    }

    // Incluir configuración de base de datos
    require_once '../config/database.php';
    
    // Verificar que $pdo esté definido (debería estarlo después de incluir database.php)
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Configuración de base de datos incompleta');
    }

    // Verificar acción
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'update_theme') {
        // Obtener valores POST
        $school_name = isset($_POST['school_name']) ? $_POST['school_name'] : null;
        $sidebar_color = isset($_POST['sidebar_color']) ? $_POST['sidebar_color'] : null;
        $sidebar_text_color = isset($_POST['sidebar_text_color']) ? $_POST['sidebar_text_color'] : null;
        $sidebar_style = isset($_POST['sidebar_style']) ? $_POST['sidebar_style'] : null;
        
        // Validar datos
        if (empty($school_name)) {
            echo json_encode(['success' => false, 'message' => 'El nombre de la institución es obligatorio']);
            exit;
        }
        
        // Procesar imagen de logo si se ha subido
        $logo_url = null;
        
        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
            // Directorio de destino
            $target_dir = "../assets/img/";
            
            // Verificar que el directorio exista, si no, crearlo
            if (!file_exists($target_dir)) {
                if (!mkdir($target_dir, 0755, true)) {
                    throw new Exception('No se pudo crear el directorio para guardar imágenes');
                }
            }
            
            // Generar nombre de archivo único
            $file_extension = strtolower(pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION));
            $new_filename = "school_logo_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Validar tipo de archivo
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            if (!in_array($file_extension, $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos de imagen (JPG, PNG, GIF, SVG)']);
                exit;
            }
            
            // Validar tamaño (máximo 2MB)
            if ($_FILES['school_logo']['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'El tamaño máximo permitido es 2MB']);
                exit;
            }
            
            // Intentar cargar el archivo
            if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $target_file)) {
                // La ruta para guardar en la base de datos
                $logo_url = "/school_management/assets/img/" . $new_filename;
            } else {
                throw new Exception('Error al cargar el archivo. Verifique permisos del directorio.');
            }
        }
        
        // Preparar la consulta con PDO
        $sql = "UPDATE configuracion SET ";
        $params = [];
        $updateFields = [];
        
        if (!empty($school_name)) {
            $updateFields[] = "school_name = :school_name";
            $params[':school_name'] = $school_name;
        }
        
        if (!empty($sidebar_color)) {
            $updateFields[] = "sidebar_color = :sidebar_color";
            $params[':sidebar_color'] = $sidebar_color;
        }
        
        if (!empty($sidebar_text_color)) {
            $updateFields[] = "sidebar_text_color = :sidebar_text_color";
            $params[':sidebar_text_color'] = $sidebar_text_color;
        }
        
        if (!empty($sidebar_style)) {
            $updateFields[] = "sidebar_style = :sidebar_style";
            $params[':sidebar_style'] = $sidebar_style;
        }
        
        if (!empty($logo_url)) {
            $updateFields[] = "school_logo = :school_logo";
            $params[':school_logo'] = $logo_url;
        }
        
        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'message' => 'No se proporcionaron datos para actualizar']);
            exit;
        }
        
        $sql .= implode(", ", $updateFields) . " WHERE id = 1";
        
        // Preparar y ejecutar la consulta
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            // Registrar actividad
            $usuario_id = $_SESSION['admin_id'];
            
            // Consulta para registrar actividad
            $log_sql = "INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id) 
                        VALUES ('configuracion', 1, 'actualizar_configura', 'Actualización de configuración del sistema', :usuario_id)";
            
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([':usuario_id' => $usuario_id]);
            
            echo json_encode(['success' => true, 'message' => 'Configuración actualizada correctamente']);
        } else {
            throw new Exception('Error al actualizar la configuración');
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    }

} catch (Exception $e) {
    // Capturar cualquier error y devolver como JSON
    echo json_encode(['success' => false, 'message' => 'Error al guardar la configuración: ' . $e->getMessage()]);
}
?>