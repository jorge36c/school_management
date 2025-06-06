<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Boletín de Calificaciones</title>
    <style>
        /* AJUSTE MANUAL 1: Puedes modificar estos valores para aumentar o disminuir 
           el tamaño general del texto y márgenes del documento */
        body {
            font-family: Arial, sans-serif;
            font-size: 11px; /* Modificable: Tamaño base de texto */
            line-height: 1.4; 
            color: #333;
            margin: 0;
            padding: 15px; /* Modificable: Margen general de la página */
        }

        .header {
            position: relative;
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px; /* Modificable: Espacio después del encabezado */
        }

        .logo-container {
            width: 650px;
            text-align: center;
        }

        /* AJUSTE MANUAL 2: Tamaño del logo */
        .logo-img {
            display: block;
            max-width: 60px; /* Modificable: Ancho máximo del logo */
            max-height: 60px; /* Modificable: Alto máximo del logo */
            object-fit: contain;
            margin-top: -5px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .school-info {
            flex-grow: 1;
            text-align: center;
        }
        
        /* AJUSTE MANUAL 3: Tamaño del nombre de la escuela */
        .school-name {
            font-size: 20px; /* Modificable: Tamaño del nombre de la escuela */
            font-weight: bold;
            color: #1e40af;
            margin: 0;
            padding: 0;
        }
        
        .report-title {
            font-size: 13px; /* Modificable: Tamaño del título del boletín */
            margin-top: 4px;
            color: #555;
        }
        
        .divider {
            height: 2px;
            background-color: #1e40af;
            margin-bottom: 12px; /* Modificable: Espacio después de la línea divisoria */
        }

        /* AJUSTE MANUAL 4: Ajustes de la tabla de información del estudiante */
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px; /* Modificable: Espacio después de tabla de info estudiante */
        }

        .student-info-table td {
            border: 1px solid #ccc;
            padding: 6px; /* Modificable: Espaciado dentro de la tabla info estudiante */
        }

        /* AJUSTE MANUAL 5: Ajustes de la tabla de calificaciones */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0; /* Modificable: Margen antes y después de tabla calificaciones */
        }

        .grades-table th {
            background-color: #1e40af;
            color: white;
            padding: 3px 4px; /* Modificable: Espaciado dentro del encabezado tabla */
            text-align: left;
            font-size: 11px; /* Modificable: Tamaño texto encabezado */
        }

        .grades-table td {
            padding: 4px; /* Modificable: Espaciado dentro de las celdas */
            border-bottom: 1px solid #ddd;
        }
        
        .grades-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* AJUSTE MANUAL 6: Ajustes de las insignias de desempeño */
        .performance-badge {
            padding: 3px 6px; /* Modificable: Espaciado dentro de las insignias */
            border-radius: 3px;
            color: white;
            font-size: 9px; /* Modificable: Tamaño texto insignias */
            display: inline-block;
            text-align: center;
        }

        .superior { background-color: #059669; }
        .alto { background-color: #3b82f6; }
        .basico { background-color: #f59e0b; }
        .bajo { background-color: #dc2626; }

        /* AJUSTE MANUAL 7: Ajustes de la firma */
        .signature-line {
            border-top: 1px solid black;
            margin-top: 20px; /* Modificable: Espacio antes de la firma */
            margin-bottom: 3px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* AJUSTE MANUAL 8: Ajustes de la tabla de estadísticas */
        .stats-table {
            width: 100%;
            margin: 12px 0; /* Modificable: Margen antes y después de la tabla estadísticas */
            background-color: #f8fafc;
            border-collapse: collapse;
        }
        
        .stats-table td {
            text-align: center;
            padding: 8px; /* Modificable: Espaciado dentro de las celdas estadísticas */
            border: 1px solid #e2e8f0;
        }
        
        .stats-value {
            font-size: 16px; /* Modificable: Tamaño texto valores estadísticas */
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .stats-label {
            font-size: 9px; /* Modificable: Tamaño texto etiquetas estadísticas */
            color: #64748b;
        }
        
        .signature {
            margin-top: 20px; /* Modificable: Espacio antes de la firma */
            text-align: center;
        }

        /* AJUSTE MANUAL 9: Ajustes especiales para celdas de periodo */
        .period-cell {
            text-align: center;
            padding: 3px 2px !important; /* Modificable: Espaciado celdas periodos */
            font-size: 10px; /* Modificable: Tamaño texto celdas periodos */
        }
        
        /* AJUSTE MANUAL 10: Ajustes de la información de acceso */
        .platform-info {
            margin-top: 15px; /* Modificable: Espacio antes de info de acceso */
            border-top: 1px dashed #ccc;
            padding-top: 8px; /* Modificable: Espacio después de línea divisoria */
            text-align: center;
            font-size: 9px; /* Modificable: Tamaño texto info acceso */
        }
        
        .platform-info p {
            margin: 2px 0; /* Modificable: Espaciado entre líneas info acceso */
        }
    </style>
</head>
<body>
    <!-- Encabezado con logo -->
    <div class="header">
        <div class="logo-container">
            <?php
            // Si GD no está disponible, mostrar texto alternativo
            if (!extension_loaded('gd')) {
                echo '<div style="text-align:center; font-weight:bold; font-size:14px; margin-top: 10px;">I.E. SANTA ANA</div>';
            } else {
                // Usar la ruta que sabemos que funciona
                $logo_path = __DIR__ . '/../../assets/img/school_logo.png';
                
                // Verificar que exista el archivo
                if (file_exists($logo_path)) {
                    // Leer el archivo y convertirlo a base64
                    $image_data = @file_get_contents($logo_path);
                    if ($image_data !== false) {
                        // Si pudimos leer el archivo, mostrarlo en base64
                        $base64_image = base64_encode($image_data);
                        echo '<img src="data:image/png;base64,' . $base64_image . '" alt="Logo Escolar" class="logo-img">';
                    } else {
                        // Si no pudimos leer el archivo, mostrar un mensaje de error
                        echo '<div style="text-align:center; font-weight:bold; font-size:14px;">SANTA ANA</div>';
                    }
                } else {
                    // Si no existe el archivo, mostrar un mensaje alternativo
                    echo '<div style="text-align:center; font-weight:bold; font-size:14px;">SANTA ANA</div>';
                }
            }
            ?>
        </div>
        <div class="school-info">
            <div class="school-name">
                I.E. Santa Ana Sede: <?php echo htmlspecialchars($grado_info['sede_nombre'] ?? 'Santa Ana'); ?>
            </div>
            <div class="report-title">
                Boletín de Calificaciones - <?php echo htmlspecialchars($periodo_actual['periodo_nombre'] ?? 'Periodo 1'); ?> 
                - <?php echo htmlspecialchars($periodo_actual['ano_lectivo_nombre'] ?? 'Año Lectivo 2025'); ?>
            </div>
        </div>
    </div>
    
    <div class="divider"></div>

    <table class="student-info-table">
        <tr>
            <td width="33%">
                <strong>Estudiante:</strong><br>
                <?php echo htmlspecialchars(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? '')); ?>
            </td>
            <td width="33%">
                <strong>Grado:</strong><br>
                <?php echo htmlspecialchars($estudiante['grado_nombre'] ?? ''); ?>
            </td>
            <td width="33%">
                <strong>Fecha:</strong><br>
                <?php echo date('d/m/Y'); ?>
            </td>
        </tr>
    </table>

    <!-- AJUSTE MANUAL 11: Aquí puedes modificar los anchos de las columnas -->
    <table class="grades-table">
        <thead>
            <tr>
                <th width="30%">Asignatura</th> <!-- Modificable: Ancho columna asignatura -->
                <th width="20%">Docente</th> <!-- Modificable: Ancho columna docente -->
                <?php
                // Mostrar encabezados para todos los periodos posibles (1-4)
                for ($i = 1; $i <= 4; $i++) {
                    echo '<th width="7%" class="period-cell">P' . $i . '</th>'; // Modificable: Ancho columnas periodos
                }
                ?>
                <th width="8%" class="period-cell">Acum.</th> <!-- Modificable: Ancho columna acumulado -->
                <th width="10%" class="period-cell">Desempeño</th> <!-- Modificable: Ancho columna desempeño -->
            </tr>
        </thead>
        <tbody>
            <?php if (isset($materias) && is_array($materias)): ?>
                <?php foreach ($materias as $materia): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($materia['nombre'] ?? ''); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($materia['profesor'] ?? ''); ?></td>
                    <?php 
                    // Obtener el número de periodo actual
                    $periodo_actual_num = $periodo_actual['numero_periodo'] ?? 0;
                    
                    // Mostrar notas de cada periodo (1-4)
                    for ($i = 1; $i <= 4; $i++) {
                        echo '<td class="period-cell">';
                        
                        // Si es el periodo actual, mostrar la nota definitiva del periodo
                        if ($i == $periodo_actual_num) {
                            echo number_format($materia['definitiva'] ?? 0, 1);
                        } 
                        // Si hay datos de otros periodos, mostrarlos
                        else if (isset($materia['periodos'][$i])) {
                            echo number_format($materia['periodos'][$i], 1);
                        } 
                        // Si no hay datos, mostrar guión
                        else {
                            echo '-';
                        }
                        
                        echo '</td>';
                    }
                    
                    // Calcular y mostrar acumulado (promedio de todos los periodos)
                    $notas_disponibles = [];
                    $suma_notas = 0;
                    
                    // Agregar la nota del periodo actual
                    if ($periodo_actual_num > 0) {
                        // Si estamos en un periodo específico, usar su nota para el acumulado
                        $notas_disponibles[$periodo_actual_num] = $materia['definitiva'] ?? 0;
                    }
                    
                    // Agregar notas de otros periodos
                    if (isset($materia['periodos']) && is_array($materia['periodos'])) {
                        foreach ($materia['periodos'] as $num_periodo => $nota) {
                            // Solo agregar si no es el periodo actual (para evitar duplicados)
                            if ($num_periodo != $periodo_actual_num) {
                                $notas_disponibles[$num_periodo] = $nota;
                            }
                        }
                    }
                    
                    // Calcular acumulado (suma de periodos / 4, independientemente de cuántos hay)
                    $suma_notas = array_sum($notas_disponibles);
                    $acumulado = round($suma_notas / 4, 1);
                    
                    echo '<td class="period-cell" style="font-weight: bold;">';
                    echo number_format($acumulado, 1);
                    echo '</td>';
                    
                    // Determinar qué nota usar para el desempeño según el periodo actual
                    $nota_para_desempeno = 0;
                    
                    // En periodos 1, 2 y 3 usamos la nota del periodo actual
                    // En periodo 4 usamos el acumulado de todos los periodos
                    if ($periodo_actual_num < 4) {
                        $nota_para_desempeno = $materia['definitiva'] ?? 0;
                    } else {
                        $nota_para_desempeno = $acumulado;
                    }
                    
                    // Determinar desempeño basado en la nota seleccionada
                    $desempeno = '';
                    $clase = '';
                    
                    if ($nota_para_desempeno >= 4.6) {
                        $desempeno = 'Superior';
                        $clase = 'superior';
                    } elseif ($nota_para_desempeno >= 4.0) {
                        $desempeno = 'Alto';
                        $clase = 'alto';
                    } elseif ($nota_para_desempeno >= 3.0) {
                        $desempeno = 'Básico';
                        $clase = 'basico';
                    } else {
                        $desempeno = 'Bajo';
                        $clase = 'bajo';
                    }
                    ?>
                    <td style="text-align: center;">
                        <div class="performance-badge <?php echo $clase; ?>">
                            <?php echo $desempeno; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 12px;">
                        No hay datos de calificaciones disponibles
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="stats-table">
        <tr>
            <td width="25%">
                <div class="stats-value">
                    <?php echo number_format($datos_boletin['estadisticas']['promedio_general'] ?? 0, 2); ?>
                </div>
                <div class="stats-label">Promedio General</div>
            </td>
            <td width="25%">
                <div class="stats-value">
                    <?php echo $datos_boletin['estadisticas']['puesto'] ?? 0; ?>°
                </div>
                <div class="stats-label">Puesto en el Curso</div>
            </td>
            <td width="25%">
                <div class="stats-value">
                    <?php echo $datos_boletin['estadisticas']['asignaturas_perdidas'] ?? 0; ?>
                </div>
                <div class="stats-label">Asignaturas por Mejorar</div>
            </td>
            <td width="25%">
                <div class="stats-value">
                    &nbsp;
                </div>
                <div class="stats-label">Comportamiento</div>
            </td>
        </tr>
    </table>

    <div class="signature">
        <div class="signature-line"></div>
        <strong>Director(a) de Grupo</strong><br>
        <small>Docente Titular</small>
    </div>
    
    <!-- AJUSTE MANUAL 12: Información de acceso a la plataforma en formato compacto -->
    <div class="platform-info">
        <p style="color: #1e40af;"><strong>ACCESO A LA PLATAFORMA EDUCATIVA</strong></p>
        <p>Ingrese a <strong>www.iesantana.com.co</strong> con las siguientes credenciales:<br>
        Usuario: <strong><?php echo htmlspecialchars($estudiante['documento_numero'] ?? ''); ?></strong> | 
        Contraseña: <strong><?php echo htmlspecialchars($estudiante['documento_numero'] ?? ''); ?></strong></p>
    </div>
</body>
</html>