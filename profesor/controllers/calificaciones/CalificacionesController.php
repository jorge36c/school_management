<?php
/**
 * Controlador para el módulo de calificaciones
 * 
 * Este controlador maneja la lógica de negocio para la gestión de calificaciones,
 * incluyendo carga de datos, cálculos y operaciones CRUD.
 * 
 * @version 2.0
 */

// Incluir dependencias con rutas absolutas usando __DIR__
require_once __DIR__ . '/../../models/CalificacionModel.php';
require_once __DIR__ . '/../../models/EstudianteModel.php';
require_once __DIR__ . '/../../models/TipoNotaModel.php';
require_once __DIR__ . '/../../../helpers/CalificacionesHelper.php'; // Ruta corregida
require_once __DIR__ . '/../../../config/database.php';

/**
 * Controlador para el módulo de calificaciones
 */
class CalificacionesController {
    private $calificacionModel;
    private $estudianteModel;
    private $tipoNotaModel;
    private $pdo;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Conexión a la base de datos
     */
    public function __construct($pdo) {
        $this->calificacionModel = new CalificacionModel($pdo);
        $this->estudianteModel = new EstudianteModel($pdo);
        $this->tipoNotaModel = new TipoNotaModel($pdo);
        $this->pdo = $pdo;
    }
    
    /**
     * Carga los datos necesarios para ver estudiantes y calificaciones
     * 
     * @param int $profesorId ID del profesor
     * @param int $gradoId ID del grado
     * @param int $materiaId ID de la materia
     * @param bool $esMultigrado Indica si es un grupo multigrado
     * @param string $nivel Nivel educativo (para multigrados)
     * @param int $sedeId ID de la sede (para multigrados)
     * @param int $periodoId ID del periodo académico
     * @return array Datos para la vista
     */
    public function cargarDatosCalificaciones($profesorId, $gradoId, $materiaId, $esMultigrado = false, $nivel = '', $sedeId = 0, $periodoId = null) {
        if ($esMultigrado) {
            return $this->cargarDatosMultigrado($profesorId, $nivel, $sedeId, $materiaId, $periodoId);
        } else {
            return $this->cargarDatosGrado($profesorId, $gradoId, $materiaId, $periodoId);
        }
    }
    
    /**
     * Carga datos para un grado específico
     * 
     * @param int $profesorId ID del profesor
     * @param int $gradoId ID del grado
     * @param int $materiaId ID de la materia
     * @param int $periodoId ID del periodo académico
     * @return array Datos para la vista
     */    private function cargarDatosGrado($profesorId, $gradoId, $materiaId, $periodoId = null) {
        // Obtener información del grado y asignación
        $stmt = $this->pdo->prepare("
            SELECT 
                ap.id as asignacion_id,
                ap.materia_id,
                g.id as grado_id,
                g.nombre as grado_nombre,
                g.nivel,
                s.id as sede_id,
                s.nombre as sede_nombre,
                m.nombre as materia_nombre
            FROM asignaciones_profesor ap
            INNER JOIN grados g ON ap.grado_id = g.id
            INNER JOIN sedes s ON g.sede_id = s.id
            INNER JOIN materias m ON ap.materia_id = m.id
            WHERE g.id = :grado_id 
            AND ap.profesor_id = :profesor_id
            AND ap.materia_id = :materia_id
            AND ap.estado = 'activo'
            -- Eliminamos la restricción de estado del grado para evitar problemas con grados históricos
            LIMIT 1
        ");

        $stmt->execute([
            ':grado_id' => $gradoId,
            ':profesor_id' => $profesorId,
            ':materia_id' => $materiaId
        ]);

        $grado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$grado) {
            // Intentamos buscar sin la restricción de profesor_id para verificar si existe la asignación
            $stmt = $this->pdo->prepare("
                SELECT 
                    ap.id as asignacion_id,
                    ap.materia_id,
                    g.id as grado_id,
                    g.nombre as grado_nombre,
                    g.nivel,
                    s.id as sede_id,
                    s.nombre as sede_nombre,
                    m.nombre as materia_nombre
                FROM asignaciones_profesor ap
                INNER JOIN grados g ON ap.grado_id = g.id
                INNER JOIN sedes s ON g.sede_id = s.id
                INNER JOIN materias m ON ap.materia_id = m.id
                WHERE g.id = :grado_id 
                AND ap.materia_id = :materia_id
                AND ap.estado = 'activo'
                LIMIT 1
            ");
            
            $stmt->execute([
                ':grado_id' => $gradoId,
                ':materia_id' => $materiaId
            ]);
            
            $grado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$grado) {
                throw new Exception('No tiene acceso a este grado o materia, o no existe la asignación.');
            } else {
                throw new Exception('No tiene permiso para acceder a esta asignación. Por favor contacte al administrador.');
            }
        }

        // Obtener estudiantes
        $estudiantes = $this->estudianteModel->obtenerEstudiantesPorGrado($gradoId);
        
        // Obtener tipos de notas
        $tipos_notas = $this->tipoNotaModel->obtenerTiposNotas($grado['asignacion_id']);
        
        // Obtener calificaciones filtrando por periodo
        if (!empty($estudiantes)) {
            $estudianteIds = array_column($estudiantes, 'id');
            $calificaciones = $this->calificacionModel->obtenerCalificacionesMultiplesEstudiantes(
                $estudianteIds, 
                $grado['asignacion_id'],
                $periodoId
            );
            // Asignar calificaciones a cada estudiante
            foreach ($estudiantes as &$estudiante) {
                $estudiante['calificaciones'] = $calificaciones[$estudiante['id']] ?? [];
            }
        }
        
        // Calcular porcentajes por categoría
        $porcentajes_categoria = $this->tipoNotaModel->calcularTotalesPorCategoria($tipos_notas);
        
        // Calcular estadísticas
        $estadisticas = CalificacionesHelper::calcularEstadisticas($estudiantes, $tipos_notas);
        
        return [
            'grado' => $grado,
            'estudiantes' => $estudiantes,
            'tipos_notas' => $tipos_notas,
            'porcentajes_categoria' => $porcentajes_categoria,
            'estadisticas' => $estadisticas
        ];
    }
    
    /**
     * Carga datos para un grupo multigrado
     * 
     * @param int $profesorId ID del profesor
     * @param string $nivel Nivel educativo
     * @param int $sedeId ID de la sede
     * @param int $materiaId ID de la materia
     * @param int $periodoId ID del periodo académico
     * @return array Datos para la vista
     */
    private function cargarDatosMultigrado($profesorId, $nivel, $sedeId, $materiaId, $periodoId) {
        // Obtener información básica
        $stmt = $this->pdo->prepare("
            SELECT 
                s.id as sede_id,
                s.nombre as sede_nombre,
                m.id as materia_id,
                m.nombre as materia_nombre,
                ? as nivel
            FROM sedes s, materias m
            WHERE s.id = ? AND m.id = ?
            LIMIT 1
        ");
        
        $stmt->execute([$nivel, $sedeId, $materiaId]);
        $info_multigrado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$info_multigrado) {
            throw new Exception('No se pudo obtener la información del grupo multigrado');
        }
        
        // Construir objeto de grado
        $grado = [
            'sede_id' => $sedeId,
            'sede_nombre' => $info_multigrado['sede_nombre'],
            'materia_id' => $materiaId,
            'materia_nombre' => $info_multigrado['materia_nombre'],
            'nivel' => $nivel,
            'grado_nombre' => 'Grupo Multigrado - ' . ucfirst($nivel),
            'es_multigrado' => true
        ];
        
        // Obtener grados asociados
        $stmt = $this->pdo->prepare("
            SELECT id, nombre
            FROM grados
            WHERE sede_id = ? AND nivel = ? AND estado = 'activo'
        ");
        $stmt->execute([$sedeId, $nivel]);
        $grados_asociados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($grados_asociados)) {
            throw new Exception('No hay grados activos asociados a este nivel y sede');
        }
        
        // Obtener ID de las asignaciones para estos grados
        $gradoIds = array_column($grados_asociados, 'id');
        $placeholders = str_repeat('?,', count($gradoIds) - 1) . '?';
        
        $stmt = $this->pdo->prepare("
            SELECT id as asignacion_id, grado_id
            FROM asignaciones_profesor
            WHERE profesor_id = ?
            AND materia_id = ?
            AND grado_id IN ($placeholders)
            AND estado = 'activo'
        ");
        
        $params = [$profesorId, $materiaId];
        $params = array_merge($params, $gradoIds);
        
        $stmt->execute($params);
        $asignaciones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($asignaciones_raw)) {
            throw new Exception('No tiene asignaciones para esta materia en los grados de este nivel');
        }
        
        // Organizar asignaciones por grado_id
        $asignaciones = [];
        foreach ($asignaciones_raw as $asignacion) {
            $asignaciones[$asignacion['grado_id']] = $asignacion['asignacion_id'];
        }
        
        // Usar la primera asignación para tipos de notas
        $grado['asignacion_id'] = reset($asignaciones);
        
        // Obtener estudiantes de todos los grados asociados
        $estudiantes = $this->estudianteModel->obtenerEstudiantesMultigrado($sedeId, $nivel);
        
        // Obtener tipos de notas
        $tipos_notas = $this->tipoNotaModel->obtenerTiposNotas($grado['asignacion_id']);
        
        // Obtener calificaciones para todos los estudiantes
        if (!empty($estudiantes)) {
            $estudianteIds = array_column($estudiantes, 'id');
            $asignacionIds = array_values($asignaciones);            // Necesitamos una función especial para multigrado que combine las calificaciones de diferentes asignaciones
            $calificaciones = $this->obtenerCalificacionesMultigrado($estudianteIds, $asignacionIds);
            
            // Asignar calificaciones a cada estudiante
            foreach ($estudiantes as &$estudiante) {
                $estudiante['calificaciones'] = $calificaciones[$estudiante['id']] ?? [];
            }
        }
        
        // Calcular porcentajes por categoría
        $porcentajes_categoria = $this->tipoNotaModel->calcularTotalesPorCategoria($tipos_notas);
        
        // Calcular estadísticas
        $estadisticas = CalificacionesHelper::calcularEstadisticas($estudiantes, $tipos_notas);
        
        // Añadir información adicional para multigrado
        $grado['grados_asociados'] = $grados_asociados;
        
        return [
            'grado' => $grado,
            'estudiantes' => $estudiantes,
            'tipos_notas' => $tipos_notas,
            'porcentajes_categoria' => $porcentajes_categoria,
            'estadisticas' => $estadisticas,
            'asignaciones' => $asignaciones
        ];
    }    /**
     * Obtiene calificaciones para un grupo multigrado
     * Esta función es necesaria porque estamos combinando calificaciones de diferentes asignaciones
     * 
     * @param array $estudianteIds IDs de estudiantes
     * @param array $asignacionIds IDs de asignaciones
     * @param int $periodoId ID del periodo académico
     * @return array Calificaciones organizadas por estudiante
     */    private function obtenerCalificacionesMultigrado($estudianteIds, $asignacionIds, $periodoId = null) {
        if (empty($estudianteIds) || empty($asignacionIds)) {
            return [];
        }
        
        // Si no se especifica un periodo, obtener el periodo activo
        if ($periodoId === null) {
            $periodoId = $this->obtenerPeriodoActivo();
        }
        
        // Si no hay periodo, no mostrar calificaciones
        if (!$periodoId) {
            return [];
        }
        
        $placeholdersEst = str_repeat('?,', count($estudianteIds) - 1) . '?';
        $placeholdersAsig = str_repeat('?,', count($asignacionIds) - 1) . '?';
        
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
            $query = "
                SELECT 
                    c.estudiante_id,
                    c.tipo_nota_id,
                    c.valor
                FROM calificaciones c
                INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                WHERE c.estudiante_id IN ($placeholdersEst)
                AND tn.asignacion_id IN ($placeholdersAsig)
                AND c.estado = 'activo'
                AND tn.estado = 'activo'";
                
            $stmt = $this->pdo->prepare($query);
            $params = array_merge($estudianteIds, $asignacionIds);
        } else {
            // Si el periodo está finalizado, mostrar las calificaciones del historial
            $query = "
                SELECT 
                    h.estudiante_id,
                    h.tipo_nota_id,
                    h.valor
                FROM historial_calificaciones h
                INNER JOIN tipos_notas tn ON h.tipo_nota_id = tn.id
                WHERE h.estudiante_id IN ($placeholdersEst)
                AND tn.asignacion_id IN ($placeholdersAsig)
                AND h.periodo_id = ?
                AND tn.estado = 'activo'";
                
            $stmt = $this->pdo->prepare($query);
            $params = array_merge($estudianteIds, $asignacionIds, [$periodoId]);
        }
        
        $stmt->execute($params);
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
     * 
     * @param int $estudianteId ID del estudiante
     * @param int $tipoNotaId ID del tipo de nota
     * @param float $valor Valor de la calificación
     * @return array Resultado de la operación
     */
    public function guardarCalificacion($estudianteId, $tipoNotaId, $valor) {
        // Primero obtenemos el id de la asignación correspondiente a este tipo de nota
        $stmt = $this->pdo->prepare("
            SELECT asignacion_id 
            FROM tipos_notas 
            WHERE id = ? AND estado = 'activo'
        ");
        $stmt->execute([$tipoNotaId]);
        $tipoNota = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tipoNota) {
            return [
                'success' => false,
                'message' => 'Tipo de nota no encontrado o inactivo'
            ];
        }
        
        $asignacionId = $tipoNota['asignacion_id'];
        
        // Guardar la calificación
        $resultado = $this->calificacionModel->guardarCalificacion($estudianteId, $tipoNotaId, $valor);
        
        if (!$resultado['success']) {
            return $resultado;
        }
        
        // Obtener todos los tipos de notas para esta asignación
        $tipos_notas = $this->tipoNotaModel->obtenerTiposNotas($asignacionId);
        
        // Obtener todas las calificaciones del estudiante
        $stmt = $this->pdo->prepare("
            SELECT c.tipo_nota_id, c.valor
            FROM calificaciones c
            INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
            WHERE c.estudiante_id = ? 
            AND tn.asignacion_id = ?
            AND c.estado = 'activo'
            AND tn.estado = 'activo'
        ");
        
        $stmt->execute([$estudianteId, $asignacionId]);
        $calificaciones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar calificaciones
        $calificaciones = [];
        foreach ($calificaciones_raw as $cal) {
            $calificaciones[$cal['tipo_nota_id']] = $cal['valor'];
        }
        
        // Calcular la definitiva
        $resultadoDefinitiva = CalificacionesHelper::calcularDefinitiva($calificaciones, $tipos_notas);
        
        return [
            'success' => true,
            'message' => 'Calificación guardada correctamente',
            'definitiva' => $resultadoDefinitiva['definitiva'],
            'categorias' => $resultadoDefinitiva['categorias']
        ];
    }
      /**
     * Guarda múltiples calificaciones
     * 
     * @param array $calificaciones Array de calificaciones a guardar
     * @return array Resultado de la operación
     */
    public function guardarCalificacionesMultiple($calificaciones) {
        return $this->calificacionModel->guardarCalificacionesMultiple($calificaciones);
    }

    /**
     * Obtiene el periodo académico activo
     * @return int|null ID del periodo activo o null si no hay periodo activo
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
    }
}