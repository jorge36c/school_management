<?php
/**
 * Modelo para gestionar tipos de notas
 */
class TipoNotaModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtiene los tipos de notas sin agrupar por categoría
     */
    public function obtenerTiposNotasRaw($asignacionId) {
        $stmt = $this->pdo->prepare("
            SELECT id, nombre, porcentaje, COALESCE(categoria, 'TAREAS') as categoria
            FROM tipos_notas
            WHERE asignacion_id = ?
            AND estado = 'activo'
            ORDER BY categoria, id
        ");
        $stmt->execute([$asignacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene todos los tipos de notas para una asignación agrupados por categoría
     */
    public function obtenerTiposNotas($asignacionId) {
        $tipos_notas_raw = $this->obtenerTiposNotasRaw($asignacionId);
        
        // Agrupar por categoría
        $tipos_notas = [
            'TAREAS' => [],
            'EVALUACIONES' => [],
            'AUTOEVALUACION' => []
        ];
        
        foreach ($tipos_notas_raw as $tipo) {
            $categoria = $tipo['categoria'];
            if (!isset($tipos_notas[$categoria])) {
                $tipos_notas[$categoria] = [];
            }
            $tipos_notas[$categoria][] = $tipo;
        }
        
        return $tipos_notas;
    }
    
    /**
     * Guarda un tipo de nota
     */
    public function guardarTipoNota($nombre, $porcentaje, $categoria, $asignacionId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO tipos_notas (
                nombre,
                porcentaje,
                categoria,
                asignacion_id,
                estado,
                fecha_creacion
            ) VALUES (?, ?, ?, ?, 'activo', NOW())
        ");
        
        $stmt->execute([
            $nombre,
            $porcentaje,
            $categoria,
            $asignacionId
        ]);
        
        return [
            'success' => true,
            'message' => 'Tipo de nota guardado correctamente',
            'id' => $this->pdo->lastInsertId()
        ];
    }
    
    /**
     * Actualiza un tipo de nota
     */
    public function actualizarTipoNota($id, $nombre, $porcentaje) {
        $stmt = $this->pdo->prepare("
            UPDATE tipos_notas
            SET nombre = ?, porcentaje = ?, fecha_modificacion = NOW()
            WHERE id = ? AND estado = 'activo'
        ");
        
        $stmt->execute([
            $nombre,
            $porcentaje,
            $id
        ]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Tipo de nota no encontrado o sin cambios'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Tipo de nota actualizado correctamente'
        ];
    }
      /**
     * Elimina un tipo de nota
     */
    public function eliminarTipoNota($id) {
        $stmt = $this->pdo->prepare("
            UPDATE tipos_notas
            SET estado = 'inactivo', fecha_modificacion = NOW()
            WHERE id = ? AND estado = 'activo'
        ");
        
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Tipo de nota no encontrado o ya eliminado'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Tipo de nota eliminado correctamente'
        ];
    }
    
    /**
     * Calcula los totales por categoría de tipos de notas
     * 
     * @param array $tipos_notas Array de tipos de notas agrupados por categoría
     * @return array Porcentajes por categoría
     */
    public function calcularTotalesPorCategoria($tipos_notas) {
        $porcentajes = [];
        
        // Iterar por cada categoría
        foreach ($tipos_notas as $categoria => $tipos) {
            if (!is_array($tipos) || empty($tipos)) {
                continue;
            }
            
            $total_porcentaje = 0;
            foreach ($tipos as $tipo) {
                $porcentaje = isset($tipo['porcentaje']) ? floatval($tipo['porcentaje']) : 0;
                $total_porcentaje += $porcentaje;
            }
            
            $porcentajes[$categoria] = $total_porcentaje;
        }
        
        return $porcentajes;
    }
}