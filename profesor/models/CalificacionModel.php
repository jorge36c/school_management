<?php
/**
 * Modelo para gestionar calificaciones
 */
class CalificacionModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
      /**
     * Obtiene calificaciones de un estudiante por asignación
     */
    public function obtenerCalificacionesEstudiante($estudianteId, $asignacionId, $periodoId = null) {
        // Si no se especifica un periodo, obtener el periodo activo
        if ($periodoId === null) {
            $periodoId = $this->obtenerPeriodoActivo();
        }
        
        // Si no hay periodo, no mostrar calificaciones
        if (!$periodoId) {
            return [];
        }
        
        // Obtener el periodo seleccionado
        $stmt = $this->pdo->prepare("
            SELECT id, estado_periodo 
            FROM periodos_academicos 
            WHERE id = ?
        ");
        $stmt->execute([$periodoId]);
        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
          // Si el periodo está en curso (activo), mostrar las calificaciones actuales
        if ($periodo && $periodo['estado_periodo'] === 'en_curso') {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id,
                    c.tipo_nota_id,
                    c.valor,
                    c.observacion,
                    DATE_FORMAT(c.fecha_registro, '%d/%m/%Y %H:%i') as fecha,
                    tn.nombre as tipo_nota,
                    tn.porcentaje
                FROM calificaciones c
                INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                WHERE c.estudiante_id = ?
                AND tn.asignacion_id = ?
                AND c.estado = 'activo'
                AND tn.estado = 'activo'
                AND (c.periodo_actual_id = ? OR c.periodo_actual_id IS NULL)
                ORDER BY tn.categoria, c.fecha_registro DESC
            ");
            $stmt->execute([$estudianteId, $asignacionId, $periodoId]);
        } else {
            // Si el periodo está finalizado, mostrar las calificaciones del historial
            $stmt = $this->pdo->prepare("
                SELECT 
                    h.calificacion_id as id,
                    h.tipo_nota_id,
                    h.valor,
                    NULL as observacion,
                    DATE_FORMAT(h.fecha_registro, '%d/%m/%Y %H:%i') as fecha,
                    tn.nombre as tipo_nota,
                    tn.porcentaje
                FROM historial_calificaciones h
                INNER JOIN tipos_notas tn ON h.tipo_nota_id = tn.id
                WHERE h.estudiante_id = ?
                AND tn.asignacion_id = ?
                AND h.periodo_id = ?
                AND tn.estado = 'activo'
                ORDER BY tn.categoria, h.fecha_registro DESC
            ");
            $stmt->execute([$estudianteId, $asignacionId, $periodoId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el periodo académico activo
     */
    private function obtenerPeriodoActivo() {
        $stmt = $this->pdo->prepare("
            SELECT id 
            FROM periodos_academicos 
            WHERE estado_periodo = 'en_curso' 
            AND estado = 'activo'
            LIMIT 1
        ");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['id'] : null;
    }    /**
     * Obtiene calificaciones para múltiples estudiantes
     */
    public function obtenerCalificacionesMultiplesEstudiantes($estudianteIds, $asignacionId, $periodoId = null) {
        if (empty($estudianteIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($estudianteIds) - 1) . '?';
        
        // Si no se especifica un periodo, obtener el periodo activo
        if ($periodoId === null) {
            $periodoId = $this->obtenerPeriodoActivo();
        }
        
        // Si no hay periodo, no mostrar calificaciones
        if (!$periodoId) {
            return [];
        }
        
        // Obtener el periodo seleccionado
        $stmt = $this->pdo->prepare("
            SELECT id, estado_periodo 
            FROM periodos_academicos 
            WHERE id = ?
        ");
        $stmt->execute([$periodoId]);
        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
          // Si el periodo está en curso (activo), mostrar las calificaciones actuales
        if ($periodo && $periodo['estado_periodo'] === 'en_curso') {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.estudiante_id,
                    c.tipo_nota_id,
                    c.valor
                FROM calificaciones c
                INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                WHERE c.estudiante_id IN ($placeholders)
                AND tn.asignacion_id = ?
                AND c.estado = 'activo'
                AND tn.estado = 'activo'
                AND (c.periodo_actual_id = ? OR c.periodo_actual_id IS NULL)
            ");
            
            $params = array_merge($estudianteIds, [$asignacionId, $periodoId]);
            $stmt->execute($params);
        } else {
            // Si el periodo está finalizado, mostrar las calificaciones del historial
            $stmt = $this->pdo->prepare("
                SELECT 
                    h.estudiante_id,
                    h.tipo_nota_id,
                    h.valor
                FROM historial_calificaciones h
                INNER JOIN tipos_notas tn ON h.tipo_nota_id = tn.id
                WHERE h.estudiante_id IN ($placeholders)
                AND tn.asignacion_id = ?
                AND h.periodo_id = ?
                AND tn.estado = 'activo'
            ");
            
            $params = array_merge($estudianteIds, [$asignacionId, $periodoId]);
            $stmt->execute($params);
        }
        $calificaciones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar por estudiante
        $calificaciones = [];
        foreach ($calificaciones_raw as $cal) {
            if (!isset($calificaciones[$cal['estudiante_id']])) {
                $calificaciones[$cal['estudiante_id']] = [];
            }
            $calificaciones[$cal['estudiante_id']][$cal['tipo_nota_id']] = $cal['valor'];
        }
        
        return $calificaciones;
    }
      /**
     * Guarda una calificación
     */    public function guardarCalificacion($estudianteId, $tipoNotaId, $valor) {
        // Obtenemos el periodo activo para asociarlo a la calificación
        $periodoId = $this->obtenerPeriodoActivo();
          // Verificar si ya existe una calificación
        $stmt = $this->pdo->prepare("
            SELECT id, valor FROM calificaciones
            WHERE estudiante_id = ? 
            AND tipo_nota_id = ? 
            AND estado = 'activo'
            AND (periodo_actual_id = ? OR periodo_actual_id IS NULL)
        ");        $stmt->execute([$estudianteId, $tipoNotaId, $periodoId]);
        $calificacion_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($calificacion_existente) {
            // Actualizar calificación existente
            $stmt = $this->pdo->prepare("
                UPDATE calificaciones 
                SET valor = ?, fecha_registro = NOW(), periodo_actual_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$valor, $periodoId, $calificacion_existente['id']]);
            return [
                'success' => true,
                'message' => 'Calificación actualizada correctamente',
                'id' => $calificacion_existente['id'],
                'nuevo' => false
            ];
        } else {            // Crear nueva calificación
            $stmt = $this->pdo->prepare("
                INSERT INTO calificaciones (
                    estudiante_id, 
                    tipo_nota_id, 
                    valor, 
                    estado,
                    fecha_registro,
                    periodo_actual_id
                ) VALUES (?, ?, ?, 'activo', NOW(), ?)
            ");
            $stmt->execute([
                $estudianteId,
                $tipoNotaId,
                $valor,
                $periodoId
            ]);
            return [
                'success' => true,
                'message' => 'Calificación guardada correctamente',
                'id' => $this->pdo->lastInsertId(),
                'nuevo' => true
            ];
        }
    }
      /**
     * Guarda múltiples calificaciones
     */
    public function guardarCalificacionesMultiple($calificaciones) {
        if (empty($calificaciones)) {
            return [
                'success' => false,
                'message' => 'No hay calificaciones para guardar'
            ];
        }
        
        $this->pdo->beginTransaction();
        
        try {
            $nuevas = 0;
            $actualizadas = 0;
            
            foreach ($calificaciones as $calificacion) {
                $resultado = $this->guardarCalificacion(
                    $calificacion['estudiante_id'],
                    $calificacion['tipo_nota_id'],
                    $calificacion['valor']
                );
                
                if (!$resultado['success']) {
                    throw new Exception('Error al guardar calificación: ' . $resultado['message']);
                }
                
                if (isset($resultado['nuevo']) && $resultado['nuevo']) {
                    $nuevas++;
                } else {
                    $actualizadas++;
                }
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Calificaciones guardadas correctamente: $nuevas nuevas, $actualizadas actualizadas",
                'nuevas' => $nuevas,
                'actualizadas' => $actualizadas
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error al guardar calificaciones: ' . $e->getMessage()
            ];
        }
    }
}