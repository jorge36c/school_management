<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$id) {
    header('Location: list_dba.php?error=ID de planeación no válido');
    exit();
}

try {
    // Primero obtener la información del archivo
    $stmt = $pdo->prepare("SELECT archivo_url FROM planeaciones WHERE id = ?");
    $stmt->execute([$id]);
    $planeacion = $stmt->fetch();

    if($planeacion) {
        // Iniciar transacción
        $pdo->beginTransaction();

        // Eliminar el registro de la base de datos
        $stmt = $pdo->prepare("DELETE FROM planeaciones WHERE id = ?");
        $stmt->execute([$id]);

        // Si hay un archivo asociado, eliminarlo
        if($planeacion['archivo_url']) {
            $ruta_archivo = '../../uploads/dba/' . $planeacion['archivo_url'];
            if(file_exists($ruta_archivo)) {
                if(!unlink($ruta_archivo)) {
                    // Si no se puede eliminar el archivo, hacer rollback
                    $pdo->rollBack();
                    header('Location: list_dba.php?error=No se pudo eliminar el archivo físico');
                    exit();
                }
            }
        }

        // Confirmar transacción
        $pdo->commit();
        
        // Registrar en el log de actividad (opcional)
        $admin_id = $_SESSION['admin_id'];
        $log_sql = "INSERT INTO log_actividad (usuario_id, tipo_usuario, accion, detalles) 
                    VALUES (?, 'admin', 'eliminacion_planeacion', ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $detalles = "Se eliminó la planeación ID: $id";
        $log_stmt->execute([$admin_id, $detalles]);

        header('Location: list_dba.php?message=Planeación eliminada exitosamente');
        exit();

    } else {
        header('Location: list_dba.php?error=Planeación no encontrada');
        exit();
    }

} catch(PDOException $e) {
    // Si hay error, hacer rollback
    if($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $error_message = $e->getMessage();
    error_log("Error al eliminar planeación: " . $error_message);
    header('Location: list_dba.php?error=' . urlencode('Error al eliminar la planeación'));
    exit();
}
?>