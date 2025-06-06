class Calificaciones {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getEstudianteInfo($estudiante_id) {
        $stmt = $this->db->prepare("
            SELECT 
                e.*, 
                g.nombre as grado_nombre,
                g.nivel,
                s.nombre as sede_nombre
            FROM estudiantes e
            JOIN grados g ON e.grado_id = g.id
            JOIN sedes s ON g.sede_id = s.id
            WHERE e.id = ? AND e.estado = 'activo'
        ");
        $stmt->execute([$estudiante_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCalificacionesPorMateria($estudiante_id) {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                m.nombre as materia_nombre,
                m.id as materia_id,
                CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre
            FROM calificaciones c
            JOIN materias m ON c.materia_id = m.id
            JOIN profesores p ON m.profesor_id = p.id
            WHERE c.estudiante_id = ? AND c.estado = 'activo'
            ORDER BY m.nombre, c.fecha DESC
        ");
        $stmt->execute([$estudiante_id]);
        $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por materias
        $materias = [];
        foreach ($calificaciones as $calificacion) {
            $materia_id = $calificacion['materia_id'];
            if (!isset($materias[$materia_id])) {
                $materias[$materia_id] = [
                    'id' => $materia_id,
                    'nombre' => $calificacion['materia_nombre'],
                    'profesor' => $calificacion['profesor_nombre'],
                    'calificaciones' => [],
                    'promedio' => 0
                ];
            }
            $materias[$materia_id]['calificaciones'][] = $calificacion;
        }

        // Calcular promedio por materia
        foreach ($materias as &$materia) {
            $total = count($materia['calificaciones']);
            if ($total > 0) {
                $suma = array_sum(array_column($materia['calificaciones'], 'valor'));
                $materia['promedio'] = $suma / $total;
            }
        }

        return $materias;
    }

    public function calcularEstadisticas($materias) {
        $estadisticas = [
            'promedio_general' => 0,
            'mejor_materia' => null,
            'materia_baja' => null,
            'total_evaluaciones' => 0,
            'aprobadas' => 0,
            'reprobadas' => 0
        ];

        if (empty($materias)) {
            return $estadisticas;
        }

        $suma_promedios = 0;
        $mejor_promedio = 0;
        $peor_promedio = 10;

        foreach ($materias as $materia) {
            $suma_promedios += $materia['promedio'];
            
            // Mejor y peor materia
            if ($materia['promedio'] > $mejor_promedio) {
                $mejor_promedio = $materia['promedio'];
                $estadisticas['mejor_materia'] = $materia['nombre'];
            }
            if ($materia['promedio'] < $peor_promedio) {
                $peor_promedio = $materia['promedio'];
                $estadisticas['materia_baja'] = $materia['nombre'];
            }

            // Conteo de evaluaciones
            foreach ($materia['calificaciones'] as $calificacion) {
                $estadisticas['total_evaluaciones']++;
                if ($calificacion['valor'] >= 3.0) {
                    $estadisticas['aprobadas']++;
                } else {
                    $estadisticas['reprobadas']++;
                }
            }
        }

        $estadisticas['promedio_general'] = $suma_promedios / count($materias);
        return $estadisticas;
    }
} 