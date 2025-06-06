
<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/school_management/config/database.php';

// Verificar método y autenticación
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   exit(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

if (!isset($_SESSION['admin_id'])) {
   http_response_code(401); 
   exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

// Validar datos requeridos
if (!isset($_POST['sede_id']) || !isset($_POST['nombre'])) {
   http_response_code(400);
   exit(json_encode(['success' => false, 'message' => 'Faltan datos requeridos']));
}

try {
   $pdo = Database::connect();
   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
   // Iniciar transacción
   $pdo->beginTransaction();

   // Validar sede
   $stmt = $pdo->prepare("SELECT id FROM sedes WHERE id = ? AND estado = 'activo'");
   $stmt->execute([$_POST['sede_id']]);
   if (!$stmt->fetch()) {
       throw new Exception('Sede no válida o inactiva');
   }

   // Validar nombre del nivel
   $nombre = strtolower($_POST['nombre']);
   if (!in_array($nombre, ['preescolar', 'primaria', 'secundaria'])) {
       throw new Exception('Nombre de nivel no válido');
   }

   // Verificar duplicados
   $stmt = $pdo->prepare("
       SELECT id FROM niveles_educativos 
       WHERE sede_id = ? AND nombre = ? AND estado = 'activo'
   ");
   $stmt->execute([$_POST['sede_id'], $nombre]);
   if ($stmt->fetch()) {
       throw new Exception('Este nivel ya existe en la sede');
   }

   // Obtener siguiente orden
   $stmt = $pdo->prepare("
       SELECT MAX(orden) as max_orden 
       FROM niveles_educativos 
       WHERE sede_id = ?
   ");
   $stmt->execute([$_POST['sede_id']]);
   $result = $stmt->fetch(PDO::FETCH_ASSOC);
   $orden = ($result['max_orden'] ?? 0) + 1;

   // Generar código único
   $codigo = sprintf(
       '%s_%s_%d', 
       $nombre, 
       $_POST['sede_id'], 
       $orden
   );

   // Insertar nivel
   $stmt = $pdo->prepare("
       INSERT INTO niveles_educativos (
           sede_id, nombre, codigo, orden, color_tema, estado
       ) VALUES (?, ?, ?, ?, 'blue', 'activo')
   ");
   
   $stmt->execute([
       $_POST['sede_id'],
       $nombre,
       $codigo,
       $orden
   ]);
   
   $nivelId = $pdo->lastInsertId();

   // Registrar actividad
   $stmt = $pdo->prepare("
       INSERT INTO actividad_log (
           tabla, registro_id, accion, descripcion, usuario_id, fecha
       ) VALUES (
           'niveles_educativos', ?, 'crear', ?, ?, NOW()
       )
   ");
   
   $descripcion = "Creación de nivel educativo: " . ucfirst($nombre);
   $stmt->execute([$nivelId, $descripcion, $_SESSION['admin_id']]);

   // Confirmar transacción
   $pdo->commit();

   echo json_encode([
       'success' => true,
       'message' => 'Nivel educativo creado exitosamente',
       'nivel_id' => $nivelId
   ]);

} catch (Exception $e) {
   // Revertir cambios si hay error
   if (isset($pdo)) {
       $pdo->rollBack();
   }
   
   http_response_code(400);
   echo json_encode([
       'success' => false,
       'message' => $e->getMessage()
   ]);
} finally {
   Database::disconnect();
}
?>