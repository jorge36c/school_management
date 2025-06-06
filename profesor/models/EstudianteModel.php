<?php // Datos de estudiantes ?>
<?php
/**
 * Modelo para gestionar estudiantes
 */
class EstudianteModel {
    private $pdo;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Conexión a la base de datos
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtiene los estudiantes de un grado específico
     * 
     * @param int $gradoId ID del grado
     * @return array Lista de estudiantes
     */    public function obtenerEstudiantesPorGrado($gradoId) {
        try {
            error_log("DEBUG - Buscando estudiantes para grado_id: " . $gradoId);
            
            // Verificar que el grado exista
            $gradoStmt = $this->pdo->prepare("SELECT nombre FROM grados WHERE id = :grado_id");
            $gradoStmt->execute([':grado_id' => $gradoId]);
            $grado = $gradoStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$grado) {
                error_log("DEBUG - El grado con ID $gradoId no existe");
            } else {
                error_log("DEBUG - Grado encontrado: " . $grado['nombre']);
            }
              $stmt = $this->pdo->prepare("
                SELECT 
                    e.id, 
                    e.nombre as nombres, 
                    e.apellido as apellidos, 
                    e.documento_tipo, 
                    e.documento_numero,
                    e.genero,
                    e.fecha_nacimiento,
                    '' as foto
                FROM estudiantes e
                WHERE e.grado_id = :grado_id AND e.estado = 'Activo'
                ORDER BY e.apellido, e.nombre
            ");
            $stmt->execute([':grado_id' => $gradoId]);
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG - Se encontraron " . count($result) . " estudiantes para el grado " . $gradoId);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error al obtener estudiantes por grado: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene información detallada de un estudiante
     * 
     * @param int $estudianteId ID del estudiante
     * @return array|false Datos del estudiante o false si no existe
     */
    public function obtenerEstudiantePorId($estudianteId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.*,
                    g.nombre as grado_nombre,
                    s.nombre as sede_nombre
                FROM estudiantes e
                LEFT JOIN grados g ON e.grado_id = g.id
                LEFT JOIN sedes s ON g.sede_id = s.id
                WHERE e.id = :estudiante_id
            ");
            $stmt->execute([':estudiante_id' => $estudianteId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener estudiante por ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca estudiantes por nombre, apellido o documento
     * 
     * @param string $query Texto de búsqueda
     * @param int $profesorId ID del profesor para filtrar solo sus estudiantes
     * @return array Lista de estudiantes
     */
    public function buscarEstudiantes($query, $profesorId = null) {
        try {
            $params = [':query' => "%$query%"];
            $profesorFilter = "";
            
            // Si se provee ID de profesor, filtrar solo estudiantes asignados a ese profesor
            if ($profesorId) {
                $profesorFilter = "AND e.grado_id IN (
                    SELECT DISTINCT ap.grado_id
                    FROM asignaciones_profesor ap
                    WHERE ap.profesor_id = :profesor_id AND ap.estado = 'activo'
                )";
                $params[':profesor_id'] = $profesorId;
            }
              $stmt = $this->pdo->prepare("
                SELECT 
                    e.id, 
                    e.nombre as nombres, 
                    e.apellido as apellidos, 
                    e.documento_tipo,
                    e.documento_numero,
                    g.nombre as grado_nombre,
                    s.nombre as sede_nombre
                FROM estudiantes e
                LEFT JOIN grados g ON e.grado_id = g.id
                LEFT JOIN sedes s ON g.sede_id = s.id
                WHERE 
                    (e.nombre LIKE :query OR
                    e.apellido LIKE :query OR
                    e.documento_numero LIKE :query OR
                    CONCAT(e.nombre, ' ', e.apellido) LIKE :query)
                    AND e.estado = 'Activo'
                    $profesorFilter
                ORDER BY e.apellidos, e.nombres
                LIMIT 50
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al buscar estudiantes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene estudiantes para multigrado
     */
    public function obtenerEstudiantesMultigrado($sedeId, $nivel) {
        // Primero obtener los grados asociados a este nivel y sede
        $stmt = $this->pdo->prepare("
            SELECT id FROM grados
            WHERE sede_id = ? AND nivel = ? AND estado = 'activo'
        ");
        
        $stmt->execute([$sedeId, $nivel]);
        $gradoIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($gradoIds)) {
            return [];
        }
        
        // Obtener estudiantes de estos grados
        $placeholders = str_repeat('?,', count($gradoIds) - 1) . '?';
          $stmt = $this->pdo->prepare("
            SELECT 
                e.id,
                e.nombre,
                e.apellido as apellido,
                e.documento_numero,
                e.grado_id,
                g.nombre as grado_nombre,
                '' as foto_url
            FROM estudiantes e
            JOIN grados g ON e.grado_id = g.id
            WHERE e.grado_id IN ($placeholders)
            AND e.estado = 'Activo'
            ORDER BY g.nombre, e.apellido, e.nombre
        ");
        
        $stmt->execute($gradoIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualizar foto de estudiante
     */
    public function actualizarFotoEstudiante($estudianteId, $fotoUrl) {
        // Verificar si ya existe una foto
        $stmt = $this->pdo->prepare("
            SELECT id FROM estudiantes_fotos
            WHERE estudiante_id = ? AND estado = 'activo'
        ");
        
        $stmt->execute([$estudianteId]);
        $fotoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fotoExistente) {
            // Actualizar foto existente
            $stmt = $this->pdo->prepare("
                UPDATE estudiantes_fotos
                SET foto_url = ?, fecha_modificacion = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$fotoUrl, $fotoExistente['id']]);
        } else {
            // Crear nueva foto
            $stmt = $this->pdo->prepare("
                INSERT INTO estudiantes_fotos (
                    estudiante_id,
                    foto_url,
                    estado,
                    fecha_creacion
                ) VALUES (?, ?, 'activo', NOW())
            ");
            
            $stmt->execute([$estudianteId, $fotoUrl]);
        }
        
        return [
            'success' => true,
            'message' => 'Foto actualizada correctamente',
            'foto_url' => $fotoUrl
        ];
    }
    
    /**
     * Eliminar foto de estudiante
     */
    public function eliminarFotoEstudiante($estudianteId) {
        $stmt = $this->pdo->prepare("
            UPDATE estudiantes_fotos
            SET estado = 'inactivo', fecha_modificacion = NOW()
            WHERE estudiante_id = ? AND estado = 'activo'
        ");
        
        $stmt->execute([$estudianteId]);
        
        return [
            'success' => true,
            'message' => 'Foto eliminada correctamente'
        ];
    }
}
?>