<?php
require_once __DIR__ . '/../../models/AsistenciaModel.php';
require_once __DIR__ . '/../../helpers/DateHelper.php';

class AsistenciaController {
    private $model;
    
    public function __construct($pdo) {
        $this->model = new AsistenciaModel($pdo);
    }
    
    /**
     * Cargar datos para la página principal
     */
    public function cargarDatosPrincipal($profesorId) {
        try {
            $grados = $this->model->obtenerGradosProfesor($profesorId);
            
            return [
                'exito' => true,
                'grados' => $grados,
                'total_grados' => count($grados)
            ];
        } catch (Exception $e) {
            $this->logError("Error al cargar datos principales: " . $e->getMessage());
            return [
                'exito' => false,
                'error' => 'Error al cargar los datos principales: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cargar datos para el registro de asistencia
     */
    public function cargarDatosRegistroAsistencia($gradoId, $fecha) {
        try {
            // Obtener información del grado
            $grado = $this->model->obtenerInfoGrado($gradoId);
            
            if (!$grado) {
                return [
                    'exito' => false,
                    'error' => 'No se encontró información del grado seleccionado'
                ];
            }
            
            // Obtener estudiantes con su estado de asistencia
            $estudiantes = $this->model->obtenerEstudiantesConAsistencia($gradoId, $fecha);
            
            // Obtener nombre del día de la semana
            $nombreDia = DateHelper::obtenerNombreDiaSemana($fecha);
            
            return [
                'exito' => true,
                'grado' => $grado,
                'estudiantes' => $estudiantes,
                'total_estudiantes' => count($estudiantes),
                'fecha' => $fecha,
                'nombre_dia' => $nombreDia
            ];
        } catch (Exception $e) {
            $this->logError("Error al cargar datos de registro: " . $e->getMessage());
            return [
                'exito' => false,
                'error' => 'Error al cargar los datos de asistencia: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Guardar registro de asistencia
     */
    public function guardarAsistencia($datos) {
        try {
            // Validar datos recibidos
            if (!isset($datos['estudiantes']) || !is_array($datos['estudiantes']) || empty($datos['estudiantes'])) {
                return [
                    'exito' => false,
                    'error' => 'No se recibieron datos de estudiantes'
                ];
            }
            
            if (!isset($datos['grado_id']) || empty($datos['grado_id'])) {
                return [
                    'exito' => false,
                    'error' => 'El ID del grado es requerido'
                ];
            }
            
            if (!isset($datos['fecha']) || empty($datos['fecha'])) {
                return [
                    'exito' => false,
                    'error' => 'La fecha es requerida'
                ];
            }
            
            if (!isset($datos['profesor_id']) || empty($datos['profesor_id'])) {
                return [
                    'exito' => false,
                    'error' => 'El ID del profesor es requerido'
                ];
            }
            
            // Registrar asistencia
            $resultado = $this->model->registrarAsistenciaMultiple($datos);
            
            if (isset($resultado['error'])) {
                return [
                    'exito' => false,
                    'error' => 'Error al registrar la asistencia: ' . $resultado['error']
                ];
            }
            
            // Formatear mensaje de éxito
            $totalExitosos = $resultado['exitosos'];
            $totalEstudiantes = $resultado['total'];
            
            if ($totalExitosos === 0) {
                return [
                    'exito' => false,
                    'error' => 'No se pudo registrar ninguna asistencia'
                ];
            } elseif ($totalExitosos < $totalEstudiantes) {
                return [
                    'exito' => true,
                    'mensaje' => "Se registraron $totalExitosos de $totalEstudiantes asistencias correctamente",
                    'parcial' => true,
                    'registrados' => $totalExitosos,
                    'total' => $totalEstudiantes
                ];
            } else {
                return [
                    'exito' => true,
                    'mensaje' => "Se registró correctamente la asistencia de los $totalEstudiantes estudiantes",
                    'parcial' => false,
                    'registrados' => $totalExitosos,
                    'total' => $totalEstudiantes
                ];
            }
        } catch (Exception $e) {
            $this->logError("Error al guardar asistencia: " . $e->getMessage());
            return [
                'exito' => false,
                'error' => 'Error al procesar el registro de asistencia: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar reporte de asistencia
     */
    public function generarReporte($gradoId, $fechaInicio, $fechaFin) {
        try {
            // Validar fechas
            if ($fechaInicio > $fechaFin) {
                return [
                    'exito' => false,
                    'error' => 'La fecha inicial no puede ser mayor que la fecha final'
                ];
            }
            
            // Generar reporte
            $reporte = $this->model->generarReporteAsistencia($gradoId, $fechaInicio, $fechaFin);
            
            if (isset($reporte['error'])) {
                return [
                    'exito' => false,
                    'error' => $reporte['error']
                ];
            }
            
            return [
                'exito' => true,
                'reporte' => $reporte
            ];
        } catch (Exception $e) {
            $this->logError("Error al generar reporte: " . $e->getMessage());
            return [
                'exito' => false,
                'error' => 'Error al generar el reporte de asistencia: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener detalle de asistencia de un estudiante
     */
    public function obtenerDetalleAsistencia($estudianteId, $fechaInicio, $fechaFin) {
        try {
            // Validar fechas
            if ($fechaInicio > $fechaFin) {
                return [
                    'exito' => false,
                    'error' => 'La fecha inicial no puede ser mayor que la fecha final'
                ];
            }
            
            // Obtener detalle
            $detalle = $this->model->obtenerDetalleAsistencia($estudianteId, $fechaInicio, $fechaFin);
            
            if (isset($detalle['error'])) {
                return [
                    'exito' => false,
                    'error' => $detalle['error']
                ];
            }
            
            return [
                'exito' => true,
                'detalle' => $detalle
            ];
        } catch (Exception $e) {
            $this->logError("Error al obtener detalle: " . $e->getMessage());
            return [
                'exito' => false,
                'error' => 'Error al obtener el detalle de asistencia: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar errores en el log
     */
    private function logError($message) {
        $logFile = __DIR__ . '/../../../logs/asistencia_controller.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
?>