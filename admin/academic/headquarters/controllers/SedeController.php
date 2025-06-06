<?php
require_once __DIR__ . '/../models/Sede.php';

class SedeController {
    private $sede;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->sede = new Sede($pdo);
    }
    
    public function viewSede($id) {
        if (!$id) {
            throw new InvalidArgumentException("ID de sede no proporcionado");
        }

        try {
            // Obtener información básica de la sede
            $sedeInfo = $this->sede->getSedeInfo($id);
            if (!$sedeInfo) {
                throw new RuntimeException("Sede no encontrada");
            }
            
            // Obtener estadísticas detalladas y niveles educativos
            $estadisticas = $this->getEstadisticasDetalladas($id);
            $niveles = $this->sede->getNiveles($id);
            
            return [
                'sede' => $sedeInfo,
                'estadisticas' => $estadisticas,
                'niveles' => $niveles
            ];
        } catch (PDOException $e) {
            error_log("Error en viewSede: " . $e->getMessage());
            throw new RuntimeException("Error al obtener la información de la sede");
        }
    }

    private function getEstadisticasDetalladas($sedeId) {
        try {
            // Estadísticas de estudiantes
            $estudiantes = [
                'total' => $this->sede->contarEstudiantes($sedeId),
                'activos' => $this->sede->contarEstudiantesPorEstado($sedeId, 'Activo'),
                'inactivos' => $this->sede->contarEstudiantesPorEstado($sedeId, 'Inactivo'),
                'por_genero' => $this->sede->contarEstudiantesPorGenero($sedeId)
            ];

            // Estadísticas de profesores
            $profesores = [
                'total' => $this->sede->contarProfesores($sedeId),
                'activos' => $this->sede->contarProfesoresPorEstado($sedeId, 'activo'),
                'inactivos' => $this->sede->contarProfesoresPorEstado($sedeId, 'inactivo')
            ];

            // Estadísticas de grupos y niveles
            $grupos = [
                'total' => $this->sede->contarGrupos($sedeId),
                'por_nivel' => $this->sede->contarGruposPorNivel($sedeId)
            ];

            // Estadísticas de matrículas
            $matriculas = [
                'total' => $this->sede->contarMatriculas($sedeId),
                'activas' => $this->sede->contarMatriculasPorEstado($sedeId, 'Activa'),
                'pendientes' => $this->sede->contarMatriculasPorEstado($sedeId, 'Pendiente')
            ];

            return [
                'estudiantes' => $estudiantes,
                'profesores' => $profesores,
                'grupos' => $grupos,
                'matriculas' => $matriculas,
                'ultima_actualizacion' => date('Y-m-d H:i:s')
            ];
        } catch (PDOException $e) {
            error_log("Error en getEstadisticasDetalladas: " . $e->getMessage());
            throw new RuntimeException("Error al obtener las estadísticas detalladas de la sede");
        }
    }
}
