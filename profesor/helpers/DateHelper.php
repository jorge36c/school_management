<?php
class DateHelper {
    /**
     * Obtener días laborables entre dos fechas (excluyendo sábados y domingos)
     */
    public static function obtenerDiasLaborables($fechaInicio, $fechaFin) {
        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);
        $diasLaborables = [];
        
        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $intervalo, $fin->modify('+1 day'));
        
        foreach ($periodo as $fecha) {
            $diaSemana = $fecha->format('N');
            // Si no es sábado (6) ni domingo (7)
            if ($diaSemana < 6) {
                $diasLaborables[] = $fecha->format('Y-m-d');
            }
        }
        
        return $diasLaborables;
    }
    
    /**
     * Obtener el nombre del mes en español
     */
    public static function obtenerNombreMes($numeroMes) {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];
        
        return $meses[(int)$numeroMes] ?? '';
    }
    
    /**
     * Obtener el nombre del día de la semana en español
     */
    public static function obtenerNombreDiaSemana($fecha) {
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];
        
        $diaSemana = date('N', strtotime($fecha));
        return $dias[$diaSemana] ?? '';
    }
    
    /**
     * Formatear fecha en formato legible en español
     */
    public static function formatearFecha($fecha) {
        $timestamp = strtotime($fecha);
        $dia = date('d', $timestamp);
        $mes = self::obtenerNombreMes(date('n', $timestamp));
        $anio = date('Y', $timestamp);
        
        return "$dia de $mes de $anio";
    }
    
    /**
     * Calcular la diferencia en días entre dos fechas
     */
    public static function calcularDiferenciaDias($fechaInicio, $fechaFin) {
        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);
        $diferencia = $inicio->diff($fin);
        
        return $diferencia->days + 1; // Incluir el día final
    }
}
?>