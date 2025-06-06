<?php
/**
 * Clase auxiliar para operaciones relacionadas con calificaciones
 * Versión con debugging para resolver errores de índices indefinidos
 */
class CalificacionesHelper {
    /**
     * Calcula estadísticas de calificaciones
     */
    public static function calcularEstadisticas($estudiantes, $tipos_notas) {
        // Agregar verificación de estructura
        error_log("Estructura de tipos_notas: " . print_r($tipos_notas, true));
        
        $total_estudiantes = count($estudiantes);
        $suma_definitivas = 0;
        $aprobados = 0;
        $reprobados = 0;
        
        foreach ($estudiantes as $estudiante) {
            $calificaciones = isset($estudiante["calificaciones"]) ? $estudiante["calificaciones"] : [];
            $resultado = self::calcularDefinitiva($calificaciones, $tipos_notas);
            $definitiva = floatval($resultado["definitiva"]);
            
            $suma_definitivas += $definitiva;
            
            if ($definitiva >= 3.0) {
                $aprobados++;
            } else {
                $reprobados++;
            }
        }
        
        $promedio_general = $total_estudiantes > 0 ? $suma_definitivas / $total_estudiantes : 0;
        
        return [
            "total_estudiantes" => $total_estudiantes,
            "promedio_general" => $promedio_general,
            "estudiantes_aprobados" => $aprobados,
            "estudiantes_reprobados" => $reprobados
        ];
    }
    
    /**
     * Calcula la definitiva de un estudiante con validación exhaustiva de claves
     */
    public static function calcularDefinitiva($calificaciones, $tipos_notas) {
        if (empty($tipos_notas)) {
            return ["definitiva" => "0.0", "categorias" => []];
        }
        
        $definitiva = 0;
        $porcentaje_total = 0;
        $categorias = [];
        
        // Detectar si es array asociativo o indexado
        $is_associative = array_keys($tipos_notas) !== range(0, count($tipos_notas) - 1);
        
        // Si ya está agrupado por categoría
        if ($is_associative) {
            foreach ($tipos_notas as $categoria => $tipos) {
                if (is_array($tipos)) {
                    $suma_categoria = 0;
                    $porcentaje_categoria = 0;
                    
                    foreach ($tipos as $tipo) {
                        if (is_array($tipo)) {
                            $tipo_id = isset($tipo["id"]) ? $tipo["id"] : null;
                            $tipo_porcentaje = isset($tipo["porcentaje"]) ? floatval($tipo["porcentaje"]) : 0;
                            
                            if ($tipo_id !== null) {
                                $valor = isset($calificaciones[$tipo_id]) ? floatval($calificaciones[$tipo_id]) : 0;
                                $porcentaje_categoria += $tipo_porcentaje;
                                $suma_categoria += $valor * $tipo_porcentaje;
                            }
                        }
                    }
                    
                    // Valor final de la categoría
                    $valor_categoria = $porcentaje_categoria > 0 ? $suma_categoria / $porcentaje_categoria : 0;
                    
                    // Contribución de la categoría a la definitiva (usando valores por defecto para categorías)
                    $porcentaje_predeterminado = 0;
                    if ($categoria == 'TAREAS') $porcentaje_predeterminado = 40;
                    else if ($categoria == 'EVALUACIONES') $porcentaje_predeterminado = 50;
                    else if ($categoria == 'AUTOEVALUACION') $porcentaje_predeterminado = 10;
                    
                    $contribucion_categoria = ($valor_categoria * $porcentaje_predeterminado) / 100;
                    $definitiva += $contribucion_categoria;
                    $porcentaje_total += $porcentaje_predeterminado;
                    
                    // Guardar resultados por categoría
                    $categorias[$categoria] = [
                        "valor" => $valor_categoria,
                        "porcentaje" => $porcentaje_predeterminado
                    ];
                }
            }
        } else {
            // Si es un array plano de tipos
            $tipos_por_categoria = [];
            
            // Agrupar tipos de notas por categoría
            foreach ($tipos_notas as $tipo) {
                if (is_array($tipo) && isset($tipo["categoria"])) {
                    $categoria = $tipo["categoria"];
                    if (!isset($tipos_por_categoria[$categoria])) {
                        $tipos_por_categoria[$categoria] = ["tipos" => [], "porcentaje_total" => 0];
                    }
                    $tipos_por_categoria[$categoria]["tipos"][] = $tipo;
                    $tipos_por_categoria[$categoria]["porcentaje_total"] += isset($tipo["porcentaje"]) ? floatval($tipo["porcentaje"]) : 0;
                }
            }
            
            // Calcular para cada categoría
            foreach ($tipos_por_categoria as $categoria => $datos) {
                $suma_categoria = 0;
                $porcentaje_categoria = 0;
                
                foreach ($datos["tipos"] as $tipo) {
                    $tipo_id = isset($tipo["id"]) ? $tipo["id"] : null;
                    $tipo_porcentaje = isset($tipo["porcentaje"]) ? floatval($tipo["porcentaje"]) : 0;
                    
                    if ($tipo_id !== null) {
                        $valor = isset($calificaciones[$tipo_id]) ? floatval($calificaciones[$tipo_id]) : 0;
                        $porcentaje_categoria += $tipo_porcentaje;
                        $suma_categoria += $valor * $tipo_porcentaje;
                    }
                }
                
                // Valor final de la categoría
                $valor_categoria = $porcentaje_categoria > 0 ? $suma_categoria / $porcentaje_categoria : 0;
                
                // Contribución de la categoría a la definitiva
                $contribucion_categoria = ($valor_categoria * $datos["porcentaje_total"]) / 100;
                $definitiva += $contribucion_categoria;
                $porcentaje_total += $datos["porcentaje_total"];
                
                // Guardar resultados por categoría
                $categorias[$categoria] = [
                    "valor" => $valor_categoria,
                    "porcentaje" => $datos["porcentaje_total"]
                ];
            }
        }
        
        // Ajustar la definitiva según el porcentaje total
        $definitiva_ajustada = $porcentaje_total > 0 ? ($definitiva * 100) / $porcentaje_total : 0;
        
        return [
            "definitiva" => number_format($definitiva_ajustada, 1),
            "categorias" => $categorias
        ];
    }
    
    /**
     * Calcula la completitud de las calificaciones de un estudiante
     * Versión con validación de claves exhaustiva
     */
    public static function calcularCompletitud($calificaciones, $tipos_notas) {
        if (empty($tipos_notas)) {
            return ["porcentaje" => 0, "completo" => false];
        }
        
        $total_tipos = 0;
        $tipos_con_nota = 0;
        
        // Detectar si es array asociativo o indexado
        $is_associative = array_keys($tipos_notas) !== range(0, count($tipos_notas) - 1);
        
        // Si ya está agrupado por categoría
        if ($is_associative) {
            foreach ($tipos_notas as $tipos) {
                if (is_array($tipos)) {
                    foreach ($tipos as $tipo) {
                        if (is_array($tipo) && isset($tipo['id'])) {
                            $total_tipos++;
                            if (isset($calificaciones[$tipo['id']]) && $calificaciones[$tipo['id']] !== '') {
                                $tipos_con_nota++;
                            }
                        }
                    }
                }
            }
        } else {
            // Si es un array plano
            foreach ($tipos_notas as $tipo) {
                if (is_array($tipo) && isset($tipo['id'])) {
                    $total_tipos++;
                    if (isset($calificaciones[$tipo['id']]) && $calificaciones[$tipo['id']] !== '') {
                        $tipos_con_nota++;
                    }
                }
            }
        }
        
        $porcentaje = $total_tipos > 0 ? ($tipos_con_nota / $total_tipos) * 100 : 0;
        
        return [
            'porcentaje' => $porcentaje,
            'completo' => $porcentaje >= 100
        ];
    }
    
    /**
     * Obtiene la clase CSS basada en el valor de la calificación
     */
    public static function getColorClase($nota) {
        $nota = floatval($nota);
        
        if ($nota >= 4.6) {
            return 'nota-excelente';
        } elseif ($nota >= 4.0) {
            return 'nota-buena';
        } elseif ($nota >= 3.0) {
            return 'nota-aceptable';
        } elseif ($nota >= 1.0) {
            return 'nota-baja';
        } else {
            return 'nota-reprobada';
        }
    }
}