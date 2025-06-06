<?php
class AsistenciaModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener todos los estudiantes de un grado con su estado de asistencia para una fecha
     */
    public function obtenerEstudiantesConAsistencia($gradoId, $fecha) {
        try {
            $sql = "
                SELECT 
                    e.id,
                    e.nombres,
                    e.apellidos,
                    e.documento_tipo,
                    e.documento_numero,
                    a.estado
                FROM 
                    estudiantes e
                LEFT JOIN 
                    asistencias a ON e.id = a.estudiante_id AND a.fecha = :fecha
                WHERE 
                    e.grado_id = :grado_id AND
                    e.estado = 'Activo'
                ORDER BY 
                    e.apellidos ASC, e.nombres ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':grado_id', $gradoId, PDO::PARAM_INT);
            $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error al obtener estudiantes con asistencia: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener información del grado
     */
    public function obtenerInfoGrado($gradoId) {
        try {
            $sql = "
                SELECT 
                    g.id,
                    g.nombre,
                    s.nombre as sede_nombre,
                    s.id as sede_id
                FROM 
                    grados g
                JOIN 
                    sedes s ON g.sede_id = s.id
                WHERE 
                    g.id = :grado_id
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':grado_id', $gradoId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error al obtener información del grado: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener todos los grados asignados a un profesor
     */
    public function obtenerGradosProfesor($profesorId) {
        try {
            $sql = "
                SELECT DISTINCT 
                    g.id,
                    g.nombre,
                    s.nombre as sede_nombre
                FROM 
                    asignaciones_profesor ap
                JOIN 
                    grados g ON ap.grado_id = g.id
                JOIN 
                    sedes s ON g.sede_id = s.id
                WHERE 
                    ap.profesor_id = :profesor_id AND
                    ap.estado = 'activo'
                ORDER BY 
                    s.nombre ASC, g.nombre ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':profesor_id', $profesorId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error al obtener grados del profesor: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Registrar la asistencia de un estudiante
     */
    public function registrarAsistencia($estudianteId, $gradoId, $fecha, $estado, $profesorId) {
        try {
            // Primero verificamos si ya existe un registro para este estudiante en esta fecha
            $sqlCheck = "SELECT id FROM asistencias WHERE estudiante_id = :estudiante_id AND fecha = :fecha LIMIT 1";
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->bindParam(':estudiante_id', $estudianteId, PDO::PARAM_INT);
            $stmtCheck->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $stmtCheck->execute();
            
            $existingRecord = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            // Iniciamos transacción
            $this->pdo->beginTransaction();
            
            if ($existingRecord) {
                // Actualizar registro existente
                $sqlUpdate = "
                    UPDATE asistencias 
                    SET 
                        estado = :estado,
                        profesor_id = :profesor_id,
                        updated_at = NOW()
                    WHERE 
                        id = :id
                ";
                
                $stmtUpdate = $this->pdo->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':estado', $estado, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':profesor_id', $profesorId, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':id', $existingRecord['id'], PDO::PARAM_INT);
                $result = $stmtUpdate->execute();
            } else {
                // Crear nuevo registro
                $sqlInsert = "
                    INSERT INTO asistencias (
                        estudiante_id,
                        grado_id,
                        fecha,
                        estado,
                        profesor_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        :estudiante_id,
                        :grado_id,
                        :fecha,
                        :estado,
                        :profesor_id,
                        NOW(),
                        NOW()
                    )
                ";
                
                $stmtInsert = $this->pdo->prepare($sqlInsert);
                $stmtInsert->bindParam(':estudiante_id', $estudianteId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':grado_id', $gradoId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':fecha', $fecha, PDO::PARAM_STR);
                $stmtInsert->bindParam(':estado', $estado, PDO::PARAM_STR);
                $stmtInsert->bindParam(':profesor_id', $profesorId, PDO::PARAM_INT);
                $result = $stmtInsert->execute();
            }
            
            // Confirmamos la transacción
            if ($result) {
                $this->pdo->commit();
                return true;
            } else {
                $this->pdo->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError("Error al registrar asistencia: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar asistencia para múltiples estudiantes
     */
    public function registrarAsistenciaMultiple($datos) {
        try {
            $estudiantes = $datos['estudiantes'];
            $estados = $datos['estados'];
            $gradoId = $datos['grado_id'];
            $fecha = $datos['fecha'];
            $profesorId = $datos['profesor_id'];
            
            // Iniciamos transacción
            $this->pdo->beginTransaction();
            
            $totalExito = 0;
            
            foreach ($estudiantes as $index => $estudianteId) {
                $estado = isset($estados[$index]) ? $estados[$index] : 'presente';
                
                $result = $this->registrarAsistencia(
                    $estudianteId,
                    $gradoId,
                    $fecha,
                    $estado,
                    $profesorId
                );
                
                if ($result) {
                    $totalExito++;
                }
            }
            
            // Confirmamos la transacción
            $this->pdo->commit();
            
            return [
                'total' => count($estudiantes),
                'exitosos' => $totalExito
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError("Error al registrar asistencia múltiple: " . $e->getMessage());
            return [
                'total' => count($estudiantes),
                'exitosos' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar reporte de asistencia para un período
     */
    public function generarReporteAsistencia($gradoId, $fechaInicio, $fechaFin) {
        try {
            // Obtener información del grado
            $infoGrado = $this->obtenerInfoGrado($gradoId);
            
            if (!$infoGrado) {
                return ['error' => 'No se encontró información del grado'];
            }
            
            // Obtener estadísticas de asistencia por estudiante
            $sql = "
                SELECT 
                    e.id as estudiante_id,
                    e.nombres,
                    e.apellidos,
                    e.documento_tipo,
                    e.documento_numero,
                    SUM(CASE WHEN a.estado = 'presente' THEN 1 ELSE 0 END) as presentes,
                    SUM(CASE WHEN a.estado = 'ausente' THEN 1 ELSE 0 END) as ausentes,
                    SUM(CASE WHEN a.estado = 'tardanza' THEN 1 ELSE 0 END) as tardanzas,
                    SUM(CASE WHEN a.estado = 'justificado' THEN 1 ELSE 0 END) as justificados
                FROM 
                    estudiantes e
                LEFT JOIN 
                    asistencias a ON e.id = a.estudiante_id 
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                WHERE 
                    e.grado_id = :grado_id 
                    AND e.estado = 'Activo'
                GROUP BY 
                    e.id, e.nombres, e.apellidos, e.documento_tipo, e.documento_numero
                ORDER BY 
                    e.apellidos ASC, e.nombres ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':grado_id', $gradoId, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            
            $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener las fechas con registros en el período
            $sqlFechas = "
                SELECT DISTINCT 
                    fecha 
                FROM 
                    asistencias 
                WHERE 
                    grado_id = :grado_id 
                    AND fecha BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY 
                    fecha ASC
            ";
            
            $stmtFechas = $this->pdo->prepare($sqlFechas);
            $stmtFechas->bindParam(':grado_id', $gradoId, PDO::PARAM_INT);
            $stmtFechas->bindParam(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmtFechas->bindParam(':fecha_fin', $fechaFin, PDO::PARAM_STR);
            $stmtFechas->execute();
            
            $fechas = $stmtFechas->fetchAll(PDO::FETCH_COLUMN);
            
            return [
                'info_grado' => $infoGrado,
                'estadisticas' => $estadisticas,
                'fechas' => $fechas,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ];
        } catch (PDOException $e) {
            $this->logError("Error al generar reporte de asistencia: " . $e->getMessage());
            return ['error' => 'Error al generar el reporte de asistencia'];
        }
    }
    
    /**
     * Obtener detalle de asistencia para un estudiante
     */
    public function obtenerDetalleAsistencia($estudianteId, $fechaInicio, $fechaFin) {
        try {
            // Obtener información del estudiante
            $sqlEstudiante = "
                SELECT 
                    id, 
                    nombres, 
                    apellidos, 
                    documento_tipo, 
                    documento_numero,
                    grado_id
                FROM 
                    estudiantes 
                WHERE 
                    id = :estudiante_id
            ";
            
            $stmtEstudiante = $this->pdo->prepare($sqlEstudiante);
            $stmtEstudiante->bindParam(':estudiante_id', $estudianteId, PDO::PARAM_INT);
            $stmtEstudiante->execute();
            
            $estudiante = $stmtEstudiante->fetch(PDO::FETCH_ASSOC);
            
            if (!$estudiante) {
                return ['error' => 'No se encontró el estudiante'];
            }
            
            // Obtener registros de asistencia
            $sqlAsistencias = "
                SELECT 
                    fecha, 
                    estado
                FROM 
                    asistencias 
                WHERE 
                    estudiante_id = :estudiante_id 
                    AND fecha BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY 
                    fecha ASC
            ";
            
            $stmtAsistencias = $this->pdo->prepare($sqlAsistencias);
            $stmtAsistencias->bindParam(':estudiante_id', $estudianteId, PDO::PARAM_INT);
            $stmtAsistencias->bindParam(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmtAsistencias->bindParam(':fecha_fin', $fechaFin, PDO::PARAM_STR);
            $stmtAsistencias->execute();
            
            $asistencias = $stmtAsistencias->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener información del grado
            $infoGrado = $this->obtenerInfoGrado($estudiante['grado_id']);
            
            return [
                'estudiante' => $estudiante,
                'grado' => $infoGrado,
                'asistencias' => $asistencias,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ];
        } catch (PDOException $e) {
            $this->logError("Error al obtener detalle de asistencia: " . $e->getMessage());
            return ['error' => 'Error al obtener el detalle de asistencia'];
        }
    }
    
    /**
     * Registrar errores en el log
     */
    private function logError($message) {
        $logFile = __DIR__ . '/../../../logs/asistencia.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
?>