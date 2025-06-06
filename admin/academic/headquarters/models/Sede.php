<?php
class Sede {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Método que causaba el error - Obtiene información general de la sede
    public function getSedeInfo($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, 
                    (SELECT COUNT(*) FROM niveles_educativos WHERE sede_id = s.id) as total_niveles,
                    (SELECT COUNT(*) FROM grupos WHERE sede_id = s.id) as total_grupos,
                    (SELECT COUNT(*) FROM estudiantes WHERE sede_id = s.id) as total_estudiantes,
                    (SELECT COUNT(*) FROM profesores WHERE sede_id = s.id) as total_profesores
                FROM sedes s 
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getSedeInfo: " . $e->getMessage());
            throw new Exception("Error al obtener información de la sede");
        }
    }

    // Método para obtener niveles educativos
    public function getNiveles($sedeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT n.*, 
                    (SELECT COUNT(*) FROM grados WHERE nivel_id = n.id) as total_grados
                FROM niveles_educativos n 
                WHERE n.sede_id = ? 
                ORDER BY n.orden
            ");
            $stmt->execute([$sedeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getNiveles: " . $e->getMessage());
            return [];
        }
    }

    // Conteo de estudiantes
    public function contarEstudiantes($sedeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM estudiantes 
                WHERE sede_id = ? AND estado = 'Activo'
            ");
            $stmt->execute([$sedeId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en contarEstudiantes: " . $e->getMessage());
            return 0;
        }
    }

    // Conteo por estado de estudiantes
    public function contarEstudiantesPorEstado($sedeId, $estado) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM estudiantes 
                WHERE sede_id = ? AND estado = ?
            ");
            $stmt->execute([$sedeId, $estado]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en contarEstudiantesPorEstado: " . $e->getMessage());
            return 0;
        }
    }

    // Conteo por género
    public function contarEstudiantesPorGenero($sedeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT genero, COUNT(*) as total 
                FROM estudiantes 
                WHERE sede_id = ? AND estado = 'Activo'
                GROUP BY genero
            ");
            $stmt->execute([$sedeId]);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("Error en contarEstudiantesPorGenero: " . $e->getMessage());
            return [];
        }
    }

    // Conteo de profesores
    public function contarProfesores($sedeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM profesores 
                WHERE sede_id = ? AND estado = 'activo'
            ");
            $stmt->execute([$sedeId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en contarProfesores: " . $e->getMessage());
            return 0;
        }
    }

    // Conteo de profesores por estado
    public function contarProfesoresPorEstado($sedeId, $estado) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM profesores 
                WHERE sede_id = ? AND estado = ?
            ");
            $stmt->execute([$sedeId, $estado]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en contarProfesoresPorEstado: " . $e->getMessage());
            return 0;
        }
    }

    // Conteo de grupos
    public function contarGrupos($sedeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM grupos 
                WHERE sede_id = ? AND estado = 'activo'
            ");
            $stmt->execute([$sedeId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en contarGrupos: " . $e->getMessage());
            return 0;
        }
    }

    // Conteo de grupos por nivel
    public function contarGruposPorNivel($sedeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT n.nombre, COUNT(g.id) as total 
                FROM niveles_educativos n 
                LEFT JOIN grupos g ON g.sede_id = n.sede_id 
                WHERE n.sede_id = ? AND g.estado = 'activo'
                GROUP BY n.nombre
            ");
            $stmt->execute([$sedeId]);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("Error en contarGruposPorNivel: " . $e->getMessage());
            return [];
        }
    }

    // Conteo de matrículas
    public function contarMatriculas($sedeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(m.id) 
                FROM matriculas m 
                JOIN estudiantes e ON m.estudiante_id = e.id 
                WHERE e.sede_id = ?
            ");
            $stmt->execute([$sedeId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en contarMatriculas: " . $e->getMessage());
            return 0;
        }
    }

    // Conteo de matrículas por estado
    public function contarMatriculasPorEstado($sedeId, $estado) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(m.id) 
                FROM matriculas m 
                JOIN estudiantes e ON m.estudiante_id = e.id 
                WHERE e.sede_id = ? AND m.estado = ?
            ");
            $stmt->execute([$sedeId, $estado]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en contarMatriculasPorEstado: " . $e->getMessage());
            return 0;
        }
    }
}