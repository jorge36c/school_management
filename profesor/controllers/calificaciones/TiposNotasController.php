<?php
require_once __DIR__ . '/../../models/TipoNotaModel.php';

/**
 * Controlador para gestionar tipos de notas
 */
class TiposNotasController {
    private $tipoNotaModel;
    private $pdo;
    
    public function __construct($pdo) {
        $this->tipoNotaModel = new TipoNotaModel($pdo);
        $this->pdo = $pdo;
    }
    
    /**
     * Obtiene tipos de notas para una asignación
     */
    public function obtenerTiposNotas($asignacionId, $esMultigrado = false, $nivel = '', $sedeId = 0, $materiaId = 0) {
        try {
            if ($esMultigrado) {
                // Para multigrado, primero obtener asignaciones relacionadas
                $stmt = $this->pdo->prepare("
                    SELECT id FROM grados
                    WHERE sede_id = ? AND nivel = ? AND estado = 'activo'
                ");
                $stmt->execute([$sedeId, $nivel]);
                $grados = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($grados)) {
                    throw new Exception('No hay grados activos asociados a este nivel y sede');
                }
                
                // Obtener ID de las asignaciones para la primera asignación (representante)
                $placeholders = str_repeat('?,', count($grados) - 1) . '?';
                
                $stmt = $this->pdo->prepare("
                    SELECT ap.id
                    FROM asignaciones_profesor ap
                    WHERE ap.profesor_id = ?
                    AND ap.materia_id = ?
                    AND ap.grado_id IN ($placeholders)
                    AND ap.estado = 'activo'
                    LIMIT 1
                ");
                
                $params = [$_SESSION['profesor_id'], $materiaId];
                $params = array_merge($params, $grados);
                
                $stmt->execute($params);
                $asignacion = $stmt->fetchColumn();
                
                if (!$asignacion) {
                    throw new Exception('No tiene asignaciones para esta materia en los grados de este nivel');
                }
                
                $asignacionId = $asignacion;
            }
            
            // Obtener los tipos de notas
            $tipos_notas = $this->tipoNotaModel->obtenerTiposNotasRaw($asignacionId);
            
            // Calcular totales por categoría
            $totales_categoria = $this->calcularTotalesPorCategoria($tipos_notas);
            
            return [
                'success' => true,
                'tipos_notas' => $tipos_notas,
                'totales_categoria' => $totales_categoria
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcula los totales por categoría
     */
    private function calcularTotalesPorCategoria($tipos_notas) {
        $totales = [
            'TAREAS' => 0,
            'EVALUACIONES' => 0,
            'AUTOEVALUACION' => 0
        ];
        
        foreach ($tipos_notas as $tipo) {
            $categoria = $tipo['categoria'];
            if (isset($totales[$categoria])) {
                $totales[$categoria] += floatval($tipo['porcentaje']);
            }
        }
        
        return $totales;
    }
    
    /**
     * Guarda un tipo de nota
     */
    public function guardarTipoNota($nombre, $porcentaje, $categoria, $asignacionId) {
        try {
            // Validaciones básicas
            if (empty($nombre) || $porcentaje <= 0 || empty($categoria) || $asignacionId <= 0) {
                throw new Exception('Datos incompletos para guardar el tipo de nota');
            }
            
            // Guardar tipo de nota
            $resultado = $this->tipoNotaModel->guardarTipoNota($nombre, $porcentaje, $categoria, $asignacionId);
            
            return $resultado;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al guardar el tipo de nota: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza un tipo de nota
     */
    public function actualizarTipoNota($id, $nombre, $porcentaje) {
        try {
            // Validaciones básicas
            if (empty($id) || empty($nombre) || $porcentaje <= 0) {
                throw new Exception('Datos incompletos para actualizar el tipo de nota');
            }
            
            // Actualizar tipo de nota
            $resultado = $this->tipoNotaModel->actualizarTipoNota($id, $nombre, $porcentaje);
            
            return $resultado;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar el tipo de nota: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina un tipo de nota
     */
    public function eliminarTipoNota($id) {
        try {
            // Validación básica
            if (empty($id)) {
                throw new Exception('ID de tipo de nota no proporcionado');
            }
            
            // Verificar si existen calificaciones asociadas
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM calificaciones
                WHERE tipo_nota_id = ? AND estado = 'activo'
            ");
            $stmt->execute([$id]);
            $calificaciones = $stmt->fetchColumn();
            
            if ($calificaciones > 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar este tipo de nota porque tiene calificaciones asociadas'
                ];
            }
            
            // Eliminar tipo de nota
            $resultado = $this->tipoNotaModel->eliminarTipoNota($id);
            
            return $resultado;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar el tipo de nota: ' . $e->getMessage()
            ];
        }
    }
}