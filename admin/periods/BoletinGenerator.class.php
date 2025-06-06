<?php
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class BoletinGenerator {
    private $pdo;
    private $startTime;
    private $options;
    private $datos_boletin;
    private $cache = [];

    // Constantes para configuración
    const ESTADO_ACTIVO = 'Activo';
    const NOTA_MINIMA_APROBACION = 3.0;
    const PAPEL_TAMANO = 'A4';
    const PAPEL_ORIENTACION = 'portrait';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->startTime = microtime(true);
        
        // Verificar si la extensión GD está instalada
        if (!extension_loaded('gd')) {
            error_log("La extensión GD de PHP no está instalada. DOMPDF requiere esta extensión para funcionar correctamente.");
        }
        
        $this->options = $this->configurarDOMPDF();
        $this->datos_boletin = [];
    }

    /**
     * Genera el boletín académico
     * @param array $params Parámetros necesarios (sede_id, nivel, grado, periodo_id)
     * @param string $formato Formato de salida ('pdf', 'html', 'excel')
     * @return mixed Resultado de la generación
     * @throws Exception Si hay errores en la generación
     */
    public function generarBoletin($params, $formato = 'pdf') {
        try {
            $this->validarParametrosRequeridos($params);
            $this->recolectarDatos($params);
            switch ($formato) {
                case 'pdf':
                    return $this->generarPDF();
                case 'html':
                    return $this->generarHTML($params);
                case 'excel':
                    return $this->generarExcel($params);
                default:
                    throw new Exception('Formato no soportado');
            }
        } catch (Exception $e) {
            $this->registrarError("Error en generación de boletín", $e);
            throw $e;
        }
    }

    /**
     * Valida que estén todos los parámetros necesarios
     */
    private function validarParametrosRequeridos($params) {
        $requeridos = ['sede_id', 'nivel', 'grado', 'periodo_id'];
        foreach ($requeridos as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                throw new Exception("Falta el parámetro requerido: $param");
            }
        }
    }

    /**
     * Recolecta todos los datos necesarios para el boletín
     */
    private function recolectarDatos($params) {
        $grado_info = $this->obtenerGradoInfo($params);
        $periodo_actual = $this->obtenerPeriodoInfo($params['periodo_id']);
        $estudiante = $this->obtenerEstudiante($params);
        $materias = $this->obtenerMaterias($estudiante);
        
        $this->datos_boletin = $this->prepararDatosBoletin(
            $estudiante, 
            $grado_info, 
            $periodo_actual, 
            $materias
        );
    }

    /**
     * Obtiene información del grado
     */
    private function obtenerGradoInfo($params) {
        $sql = "
            SELECT g.*, s.nombre as sede_nombre 
            FROM grados g
            JOIN sedes s ON g.sede_id = s.id
            WHERE g.sede_id = :sede_id 
            AND g.nivel = :nivel 
            AND g.nombre = :grado
            AND g.estado = :estado
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':sede_id' => $params['sede_id'],
            ':nivel' => $params['nivel'],
            ':grado' => $params['grado'],
            ':estado' => self::ESTADO_ACTIVO
        ]);

        $grado = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grado) {
            throw new Exception("No se encontró información del grado");
        }

        return $grado;
    }

    private function obtenerPeriodoInfo($periodo_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                pa.*,
                CONCAT('Periodo ', pa.numero_periodo) as periodo_nombre,
                al.nombre as ano_lectivo_nombre,
                al.id as ano_lectivo_id
            FROM periodos_academicos pa
            JOIN anos_lectivos al ON pa.ano_lectivo_id = al.id
            WHERE pa.id = ?
        ");
        $stmt->execute([$periodo_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function obtenerEstudiante($params) {
        $stmt = $this->pdo->prepare("
            SELECT e.*, g.nombre as grado_nombre, s.nombre as sede_nombre
            FROM estudiantes e
            JOIN grados g ON e.grado_id = g.id
            JOIN sedes s ON g.sede_id = s.id
            WHERE g.sede_id = ? 
            AND g.nivel = ? 
            AND g.nombre = ?
            AND e.estado = 'Activo'
            ORDER BY e.apellido, e.nombre
            LIMIT 1
        ");
        
        $stmt->execute([$params['sede_id'], $params['nivel'], $params['grado']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function obtenerMaterias($estudiante) {
        // Obtener todas las materias del estudiante para el periodo actual
        $stmt = $this->pdo->prepare("
            SELECT 
                m.id,
                m.nombre as materia,
                m.descripcion,
                m.intensidad_horaria,
                tn.id as tipo_nota_id,
                tn.nombre as tipo_nota,
                tn.porcentaje,
                COALESCE(c.valor, 0) as calificacion,
                CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre,
                pa.id as periodo_id,
                pa.numero_periodo,
                pa.descripcion as periodo_descripcion
            FROM materias m
            JOIN asignaciones_profesor ap ON m.id = ap.materia_id
            JOIN profesores p ON ap.profesor_id = p.id
            JOIN tipos_notas tn ON tn.asignacion_id = ap.id
            LEFT JOIN calificaciones c ON c.tipo_nota_id = tn.id 
                AND c.estudiante_id = ?
            LEFT JOIN periodos_academicos pa ON c.periodo_id = pa.id
            WHERE m.estado = 'activo'
            AND ap.grado_id = ?
            ORDER BY m.nombre, tn.nombre, pa.numero_periodo
        ");
        
        $stmt->execute([$estudiante['id'], $estudiante['grado_id']]);
        $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($calificaciones)) {
            error_log("No se encontraron calificaciones para el estudiante ID: " . $estudiante['id']);
        }
        
        // Obtener también calificaciones de todos los periodos para el acumulado
        $stmt = $this->pdo->prepare("
            SELECT 
                m.id,
                m.nombre as materia,
                m.descripcion,
                pa.numero_periodo,
                pa.id as periodo_id,
                COALESCE(AVG(c.valor), 0) as promedio_periodo
            FROM materias m
            JOIN asignaciones_profesor ap ON m.id = ap.materia_id
            JOIN tipos_notas tn ON tn.asignacion_id = ap.id
            LEFT JOIN calificaciones c ON c.tipo_nota_id = tn.id AND c.estudiante_id = ?
            LEFT JOIN periodos_academicos pa ON c.periodo_id = pa.id
            WHERE m.estado = 'activo' 
            AND ap.grado_id = ?
            AND pa.id IS NOT NULL
            GROUP BY m.id, m.nombre, m.descripcion, pa.numero_periodo, pa.id
            ORDER BY m.nombre, pa.numero_periodo
        ");
        
        $stmt->execute([$estudiante['id'], $estudiante['grado_id']]);
        $promedios_por_periodo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->procesarCalificacionesConAcumulado($calificaciones, $promedios_por_periodo);
    }

    /**
     * Procesa las calificaciones y calcula definitivas, incluyendo el acumulado de periodos
     */
    private function procesarCalificacionesConAcumulado($calificaciones, $promedios_por_periodo) {
        $materias = $this->agruparCalificacionesPorMateria($calificaciones);
        $materias = $this->calcularDefinitivas($materias);
        
        // Agregar los promedios por periodo a cada materia
        $promedios_agrupados = [];
        foreach ($promedios_por_periodo as $promedio) {
            $id_materia = $promedio['id'];
            $periodo_num = $promedio['numero_periodo'];
            
            if (!isset($promedios_agrupados[$id_materia])) {
                $promedios_agrupados[$id_materia] = [
                    'nombre' => $promedio['materia'],
                    'periodos' => []
                ];
            }
            
            $promedios_agrupados[$id_materia]['periodos'][$periodo_num] = round($promedio['promedio_periodo'], 1);
        }
        
        // Calcular el acumulado para cada materia
        foreach ($materias as $id_materia => &$materia) {
            if (isset($promedios_agrupados[$id_materia])) {
                $periodos = $promedios_agrupados[$id_materia]['periodos'];
                $materia['periodos'] = $periodos;
                
                // Calcular acumulado basado en los periodos disponibles
                $suma_periodos = array_sum($periodos);
                $num_periodos = count($periodos);
                $materia['acumulado'] = $num_periodos > 0 ? round($suma_periodos / $num_periodos, 1) : 0;
            } else {
                $materia['periodos'] = [];
                $materia['acumulado'] = 0;
            }
        }
        
        return $materias;
    }

    /**
     * Procesa las calificaciones y calcula definitivas
     */
    private function procesarCalificaciones($calificaciones) {
        $materias = $this->agruparCalificacionesPorMateria($calificaciones);
        return $this->calcularDefinitivas($materias);
    }

    /**
     * Agrupa las calificaciones por materia
     */
    private function agruparCalificacionesPorMateria($calificaciones) {
        $materias = [];
        foreach ($calificaciones as $cal) {
            $id_materia = $cal['id'];
            if (!isset($materias[$id_materia])) {
                $materias[$id_materia] = $this->inicializarMateria($cal);
            }
            $materias[$id_materia]['notas_parciales'][] = $this->formatearNota($cal);
        }
        return $materias;
    }

    /**
     * Inicializa la estructura de una materia
     */
    private function inicializarMateria($cal) {
        return [
            'nombre' => $cal['materia'],
            'descripcion' => $cal['descripcion'],
            'intensidad_horaria' => $cal['intensidad_horaria'],
            'profesor' => $cal['profesor_nombre'],
            'notas_parciales' => [],
            'definitiva' => 0
        ];
    }

    /**
     * Formatea una nota individual
     */
    private function formatearNota($cal) {
        return [
            'tipo' => $cal['tipo_nota'],
            'valor' => $cal['calificacion'],
            'porcentaje' => $cal['porcentaje']
        ];
    }

    private function calcularDefinitivas($materias) {
        foreach ($materias as &$materia) {
            $total_porcentaje = 0;
            $nota_acumulada = 0;
            foreach ($materia['notas_parciales'] as $nota) {
                $nota_acumulada += ($nota['valor'] * $nota['porcentaje'] / 100);
                $total_porcentaje += $nota['porcentaje'];
            }
            $materia['definitiva'] = $total_porcentaje > 0 ? $nota_acumulada : 0;
        }
        return $materias;
    }

    private function prepararDatosBoletin($estudiante, $grado_info, $periodo_actual, $materias) {
        if (!$estudiante || !$grado_info || !$periodo_actual) {
            throw new Exception('Faltan datos básicos para el boletín');
        }

        $estadisticas = $this->calcularEstadisticas($materias);

        return [
            'estudiante' => $estudiante,
            'grado_info' => $grado_info,
            'periodo' => $periodo_actual,
            'periodo_actual' => $periodo_actual,
            'materias' => array_values($materias),
            'estadisticas' => [
                'promedio_general' => $estadisticas['promedio_general'],
                'puesto' => $estadisticas['puesto'],
                'asignaturas_perdidas' => $estadisticas['asignaturas_perdidas']
            ],
            'observaciones' => '',
            'inasistencias' => 0
        ];
    }

    private function calcularEstadisticas($materias) {
        $total = 0;
        $perdidas = 0;
        
        foreach ($materias as $materia) {
            // Usar el acumulado si está disponible, sino usar la definitiva del periodo actual
            $valor_nota = isset($materia['acumulado']) ? $materia['acumulado'] : floatval($materia['definitiva']);
            
            $total += $valor_nota;
            if ($valor_nota < self::NOTA_MINIMA_APROBACION) {
                $perdidas++;
            }
        }
        
        $cantidad_materias = count($materias);
        
        return [
            'promedio_general' => $cantidad_materias > 0 ? round($total / $cantidad_materias, 2) : 0,
            'puesto' => 1, // Se puede implementar el cálculo del puesto si es necesario
            'asignaturas_perdidas' => $perdidas
        ];
    }

    /**
     * Genera el PDF final
     */
    private function generarPDF() {
        try {
            // Verificar si la extensión GD está instalada
            if (!extension_loaded('gd')) {
                throw new Exception('La extensión PHP GD es necesaria para generar PDFs pero no está instalada. Por favor, contacte al administrador del sistema para habilitarla.');
            }
            
            // Validar que tenemos todos los datos necesarios
            if (empty($this->datos_boletin)) {
                throw new Exception('No hay datos para generar el boletín');
            }

            // Extraer todas las variables necesarias para el template
            $estudiante = $this->datos_boletin['estudiante'];
            $grado_info = $this->datos_boletin['grado_info'];
            $periodo_actual = $this->datos_boletin['periodo_actual'];
            $materias = $this->datos_boletin['materias'];
            $estadisticas = $this->datos_boletin['estadisticas'];
            $datos_boletin = $this->datos_boletin; // Mantener la variable completa

            // Debug de datos
            error_log("Datos para el template:");
            error_log("Estudiante: " . print_r($estudiante, true));
            error_log("Grado info: " . print_r($grado_info, true));
            error_log("Periodo actual: " . print_r($periodo_actual, true));
            error_log("Materias: " . print_r($materias, true));
            error_log("Estadísticas: " . print_r($estadisticas, true));

            // Generar HTML
            ob_start();
            require 'boletin_template.php';
            $html = ob_get_clean();

            if (empty($html)) {
                throw new Exception('El template no generó contenido HTML');
            }

            // Configurar y renderizar PDF
            $dompdf = new Dompdf($this->options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper(self::PAPEL_TAMANO, self::PAPEL_ORIENTACION);
            $dompdf->render();

            return $dompdf;

        } catch (Exception $e) {
            $this->registrarError("Error generando PDF", $e);
            throw $e;
        }
    }

    private function configurarDOMPDF() {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        return $options;
    }

    /**
     * Registra errores de manera consistente
     */
    private function registrarError($mensaje, $excepcion) {
        $error_completo = $mensaje . ": " . $excepcion->getMessage();
        error_log($error_completo);
        if (defined('DEBUG') && DEBUG) {
            error_log($excepcion->getTraceAsString());
        }
    }

    /**
     * Obtiene el tiempo de generación
     */
    public function getGenerationTime() {
        return microtime(true) - $this->startTime;
    }

    private function getCachedData($key) {
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->fetchDataFromDB($key);
        }
        return $this->cache[$key];
    }

    private function fetchDataFromDB($key) {
        // Implementa la lógica para obtener datos de la base de datos
        // Esto es solo un ejemplo y debería ser reemplazado por la implementación real
        return [];
    }

    private function generarHTML($params) {
        // Implementa la lógica para generar HTML
        return '';
    }

    private function generarExcel($params) {
        // Implementa la lógica para generar Excel
        return '';
    }
}