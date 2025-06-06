<?php
require_once 'BoletinGenerator.class.php';

class BoletinController {
    private $pdo;
    private $generator;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->generator = new BoletinGenerator($pdo);
    }

    public function generar() {
        try {
            $this->validarPermisos();
            
            $params = $this->validarParametros($_GET);
            
            // Verificar si la extensión GD está habilitada
            if (!extension_loaded('gd')) {
                // Si no está habilitada GD, mostrar advertencia pero intentar generar
                error_log("Advertencia: La extensión GD no está habilitada. El PDF podría generarse sin imágenes.");
            }
            
            $pdf = $this->generator->generarBoletin($params);
            
            // Registrar la generación (opcional)
            try {
                $this->registrarGeneracion($_SESSION['admin_id'], $params);
            } catch (Exception $e) {
                error_log("Error al registrar actividad: " . $e->getMessage());
            }
            
            $this->enviarPDF($pdf);
            
        } catch (Exception $e) {
            $this->manejarError($e);
        }
    }

    public function generarBoletinIndividual($params) {
        try {
            // Obtener datos del estudiante
            $estudiante = $this->obtenerDatosEstudiante($params['estudiante_id']);
            
            // Obtener datos del grado
            $grado_info = $this->obtenerDatosGrado($params);
            
            // Obtener periodo actual
            $periodo_actual = $this->obtenerDatosPeriodo($params['periodo_id']);
            
            // Obtener materias y calificaciones
            $materias = $this->obtenerMateriasYCalificaciones($params);
            
            // Calcular estadísticas
            $estadisticas = $this->calcularEstadisticas($materias, $params);
            
            // Preparar datos para la plantilla
            $datos_boletin = [
                'estudiante' => $estudiante,
                'grado_info' => $grado_info,
                'periodo_actual' => $periodo_actual,
                'materias' => $materias,
                'estadisticas' => $estadisticas
            ];
            
            // Incluir la plantilla
            include 'boletin_template.php';
            
        } catch (Exception $e) {
            throw new Exception("Error generando boletín individual: " . $e->getMessage());
        }
    }

    private function validarPermisos() {
        if (!isset($_SESSION['admin_id'])) {
            throw new Exception('Acceso no autorizado');
        }
    }

    private function validarParametros($input) {
        $params = [
            'sede_id' => filter_var($input['sede_id'], FILTER_VALIDATE_INT),
            'nivel' => filter_var($input['nivel'], FILTER_SANITIZE_STRING),
            'grado' => filter_var($input['grado'], FILTER_SANITIZE_STRING),
            'periodo_id' => filter_var($input['periodo_id'], FILTER_VALIDATE_INT)
        ];

        foreach ($params as $key => $value) {
            if (!$value) {
                throw new Exception("Parámetro inválido: $key");
            }
        }

        return $params;
    }

    private function registrarGeneracion($usuarioId, $params) {
        // Verificar si la tabla existe
        try {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM registros_actividad LIMIT 1
            ");
            $stmt->execute();
            
            // Si llegamos aquí, la tabla existe
            $stmt = $this->pdo->prepare("
                INSERT INTO registros_actividad 
                (usuario_id, accion, detalles) 
                VALUES (?, 'Generación de boletín', ?)
            ");
            $stmt->execute([$usuarioId, json_encode($params)]);
        } catch (Exception $e) {
            // Si la tabla no existe, solo logueamos el error
            error_log("La tabla registros_actividad no existe: " . $e->getMessage());
        }
    }

    private function enviarPDF($pdf) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="boletin.pdf"');
        echo $pdf->output();
        exit;
    }

    private function manejarError($e) {
        error_log($e->getMessage());
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }

    private function obtenerDatosEstudiante($estudiante_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                e.*,
                g.nombre as grado_nombre,
                s.nombre as sede_nombre
            FROM estudiantes e
            JOIN grados g ON e.grado_id = g.id
            JOIN sedes s ON g.sede_id = s.id
            WHERE e.id = ?
            AND e.estado = 'activo'
        ");
        $stmt->execute([$estudiante_id]);
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$estudiante) {
            throw new Exception("No se encontró el estudiante");
        }
        
        return $estudiante;
    }

    private function obtenerDatosGrado($params) {
        $stmt = $this->pdo->prepare("
            SELECT g.*, s.nombre as sede_nombre 
            FROM grados g
            JOIN sedes s ON g.sede_id = s.id
            WHERE g.sede_id = ? 
            AND g.nivel = ? 
            AND g.nombre = ?
            AND g.estado = 'activo'
        ");
        
        $stmt->execute([
            $params['sede_id'],
            $params['nivel'],
            $params['grado']
        ]);
        
        $grado = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grado) {
            throw new Exception("No se encontró información del grado");
        }
        
        return $grado;
    }

    private function obtenerDatosPeriodo($periodo_id) {
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
        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$periodo) {
            throw new Exception("No se encontró información del periodo");
        }
        
        return $periodo;
    }

    private function obtenerMateriasYCalificaciones($params) {
        try {
            // Primero obtenemos el grado_id del estudiante
            $stmt = $this->pdo->prepare("
                SELECT grado_id 
                FROM estudiantes 
                WHERE id = ? 
                AND estado = 'activo'
            ");
            $stmt->execute([$params['estudiante_id']]);
            $grado_id = $stmt->fetchColumn();

            if (!$grado_id) {
                throw new Exception("No se encontró el grado del estudiante");
            }

            // Obtener el periodo académico actual
            $periodo_actual = $this->obtenerDatosPeriodo($params['periodo_id']);
            $ano_lectivo_id = $periodo_actual['ano_lectivo_id'];
            $periodo_actual_num = $periodo_actual['numero_periodo'];

            // Obtenemos todas las materias del grado
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.id,
                    m.nombre as materia,
                    m.descripcion,
                    m.intensidad_horaria,
                    CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre
                FROM materias m
                JOIN asignaciones_profesor ap ON m.id = ap.materia_id
                JOIN profesores p ON ap.profesor_id = p.id
                WHERE ap.grado_id = ?
                AND m.estado = 'activo'
                GROUP BY m.id
                ORDER BY m.nombre
            ");
            
            $stmt->execute([$grado_id]);
            $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($materias)) {
                error_log("No se encontraron materias para el grado ID: " . $grado_id);
                return [];
            }

            // Para cada materia, obtenemos:
            // 1. Los tipos de notas del periodo actual
            // 2. Las calificaciones del periodo actual
            // 3. Las calificaciones de periodos anteriores
            $materiasConNotas = [];

            foreach ($materias as $materia) {
                $materia_id = $materia['id'];
                $materiasConNotas[$materia_id] = [
                    'id' => $materia_id,
                    'nombre' => $materia['materia'],
                    'profesor' => $materia['profesor_nombre'],
                    'notas_parciales' => [],
                    'definitiva' => 0,
                    'periodos' => [],  // Aquí almacenaremos las calificaciones de otros periodos
                    'desempeno' => ''
                ];

                // Obtener los tipos de notas y calificaciones del periodo actual
                $stmt = $this->pdo->prepare("
                    SELECT 
                        tn.id as tipo_nota_id,
                        tn.nombre as tipo_nota,
                        tn.porcentaje,
                        COALESCE(c.valor, 0) as calificacion
                    FROM asignaciones_profesor ap
                    JOIN tipos_notas tn ON tn.asignacion_id = ap.id
                    LEFT JOIN calificaciones c ON c.tipo_nota_id = tn.id 
                        AND c.estudiante_id = ?
                        AND c.periodo_id = ?
                    WHERE ap.materia_id = ?
                    AND ap.grado_id = ?
                    AND tn.estado = 'activo'
                    ORDER BY tn.nombre
                ");
                
                $stmt->execute([
                    $params['estudiante_id'],
                    $params['periodo_id'],
                    $materia_id,
                    $grado_id
                ]);
                
                $notas_parciales = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Procesar notas parciales del periodo actual
                $definitiva = 0;
                $total_porcentaje = 0;
                
                foreach ($notas_parciales as $nota) {
                    $materiasConNotas[$materia_id]['notas_parciales'][] = [
                        'tipo' => $nota['tipo_nota'],
                        'valor' => floatval($nota['calificacion']),
                        'porcentaje' => floatval($nota['porcentaje'])
                    ];
                    
                    $definitiva += (floatval($nota['calificacion']) * floatval($nota['porcentaje']) / 100);
                    $total_porcentaje += floatval($nota['porcentaje']);
                }
                
                // Calcular definitiva del periodo actual
                $materiasConNotas[$materia_id]['definitiva'] = $total_porcentaje > 0 ? 
                    round($definitiva * (100 / $total_porcentaje), 1) : 0;
                
                // Obtener calificaciones de periodos anteriores del mismo año lectivo
                $stmt = $this->pdo->prepare("
                    SELECT 
                        pa.numero_periodo,
                        AVG(c.valor) as promedio
                    FROM calificaciones c
                    JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                    JOIN asignaciones_profesor ap ON tn.asignacion_id = ap.id
                    JOIN periodos_academicos pa ON c.periodo_id = pa.id
                    WHERE c.estudiante_id = ?
                    AND ap.materia_id = ?
                    AND ap.grado_id = ?
                    AND pa.ano_lectivo_id = ?
                    AND pa.id != ?
                    AND c.estado = 'activo'
                    GROUP BY pa.numero_periodo
                    ORDER BY pa.numero_periodo
                ");
                
                $stmt->execute([
                    $params['estudiante_id'],
                    $materia_id,
                    $grado_id,
                    $ano_lectivo_id,
                    $params['periodo_id']
                ]);
                
                $periodos_anteriores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Almacenar calificaciones de periodos anteriores
                foreach ($periodos_anteriores as $periodo) {
                    $numero_periodo = $periodo['numero_periodo'];
                    $materiasConNotas[$materia_id]['periodos'][$numero_periodo] = round(floatval($periodo['promedio']), 1);
                }

                // También guardar la calificación del periodo actual en el array de periodos
                $materiasConNotas[$materia_id]['periodos'][$periodo_actual_num] = $materiasConNotas[$materia_id]['definitiva'];
                
                // Determinar desempeño basado en la definitiva
                $nota = $materiasConNotas[$materia_id]['definitiva'];
                if ($nota >= 4.6) {
                    $materiasConNotas[$materia_id]['desempeno'] = 'superior';
                } elseif ($nota >= 4.0) {
                    $materiasConNotas[$materia_id]['desempeno'] = 'alto';
                } elseif ($nota >= 3.0) {
                    $materiasConNotas[$materia_id]['desempeno'] = 'basico';
                } else {
                    $materiasConNotas[$materia_id]['desempeno'] = 'bajo';
                }
            }

            return $materiasConNotas;

        } catch (PDOException $e) {
            error_log("Error en obtenerMateriasYCalificaciones: " . $e->getMessage());
            throw new Exception("Error al obtener materias y calificaciones: " . $e->getMessage());
        }
    }

    private function procesarCalificaciones($resultados) {
        $materias = [];
        
        foreach ($resultados as $row) {
            $materia_id = $row['id'];
            
            if (!isset($materias[$materia_id])) {
                $materias[$materia_id] = [
                    'nombre' => $row['materia'],
                    'profesor' => $row['profesor_nombre'],
                    'notas_parciales' => [],
                    'definitiva' => 0,
                    'desempeno' => ''  // Agregamos el campo desempeño
                ];
            }
            
            // Agregar nota parcial
            $materias[$materia_id]['notas_parciales'][] = [
                'tipo' => $row['tipo_nota'],
                'valor' => floatval($row['calificacion']),
                'porcentaje' => floatval($row['porcentaje'])
            ];
        }
        
        // Calcular definitivas y desempeños
        foreach ($materias as &$materia) {
            $definitiva = 0;
            $total_porcentaje = 0;
            
            foreach ($materia['notas_parciales'] as $nota) {
                $definitiva += ($nota['valor'] * $nota['porcentaje'] / 100);
                $total_porcentaje += $nota['porcentaje'];
            }
            
            // Calcular nota final ajustada al porcentaje total
            $materia['definitiva'] = $total_porcentaje > 0 ? 
                round($definitiva * (100 / $total_porcentaje), 1) : 0;
                
            // Determinar desempeño
            $nota = $materia['definitiva'];
            if ($nota >= 4.6) {
                $materia['desempeno'] = 'superior';
            } elseif ($nota >= 4.0) {
                $materia['desempeno'] = 'alto';
            } elseif ($nota >= 3.0) {
                $materia['desempeno'] = 'basico';
            } else {
                $materia['desempeno'] = 'bajo';
            }
        }
        
        return $materias;
    }

    private function calcularEstadisticas($materias, $params) {
        $total = 0;
        $perdidas = 0;
        
        foreach ($materias as $materia) {
            $total += $materia['definitiva'];
            if ($materia['definitiva'] < 3.0) {
                $perdidas++;
            }
        }
        
        $promedio = count($materias) > 0 ? round($total / count($materias), 1) : 0;
        
        // Calcular puesto
        $puesto = $this->calcularPuesto($params['estudiante_id'], $promedio);
        
        return [
            'promedio_general' => $promedio,
            'puesto' => $puesto,
            'asignaturas_perdidas' => $perdidas
        ];
    }

    private function calcularPuesto($estudiante_id, $promedio) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) + 1 as puesto
            FROM estudiantes e2
            WHERE e2.grado_id = (SELECT grado_id FROM estudiantes WHERE id = ?)
            AND (
                SELECT AVG(c2.valor)
                FROM calificaciones c2
                WHERE c2.estudiante_id = e2.id
            ) > ?
        ");
        
        $stmt->execute([$estudiante_id, $promedio]);
        return $stmt->fetchColumn();
    }
}