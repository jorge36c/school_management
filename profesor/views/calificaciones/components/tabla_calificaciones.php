<!-- Formulario para guardar calificaciones -->
<form id="form-calificaciones" class="form-calificaciones">
    <input type="hidden" name="asignacion_id" id="asignacion_id" value="<?php echo htmlspecialchars($grado['asignacion_id'] ?? ''); ?>">
    <?php if (isset($esMultigrado) && $esMultigrado): ?>
    <input type="hidden" name="es_multigrado" id="es_multigrado" value="1">
    <input type="hidden" name="nivel" id="nivel" value="<?php echo htmlspecialchars($nivel); ?>">
    <input type="hidden" name="sede_id" id="sede_id" value="<?php echo $sede_id; ?>">
    <input type="hidden" name="materia_id" id="materia_id" value="<?php echo $materia_id; ?>">
    <?php endif; ?>
    
    <div class="table-responsive">
        <table class="calificaciones-table">
            <thead>
                <tr>
                    <th style="width: 180px; min-width: 180px; text-align: left;">Estudiante</th>
                    <?php foreach ($tipos_notas as $categoria => $tipos): ?>
                        <?php foreach ($tipos as $tipo): ?>
                            <th class="nota-header <?php echo strtolower($categoria); ?>">
                                <div class="nota-header-content">
                                    <i class="fas fa-<?php echo $iconos_categoria[$categoria]; ?>"></i>
                                    <span><?php echo htmlspecialchars($tipo['nombre']); ?></span>
                                    <small><?php echo $tipo['porcentaje']; ?>%</small>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <th>Definitiva</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estudiantes as $estudiante): 
                    $resultado = CalificacionesHelper::calcularDefinitiva($estudiante['calificaciones'], $tipos_notas);
                    $definitiva = $resultado['definitiva'];
                ?>
                    <tr data-student-id="<?php echo $estudiante['id']; ?>">
                        <td style="text-align: left;">
                            <div class="student-info-cell">
                                <div class="student-photo">                                    <?php if (!empty($estudiante['foto_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($estudiante['foto_url']); ?>" alt="Foto de <?php echo htmlspecialchars($estudiante['nombres']); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($estudiante['nombres'], 0, 1) . substr($estudiante['apellidos'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="student-details">
                                    <div class="student-name" title="<?php echo htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']); ?>">
                                        <?php echo htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <?php foreach ($tipos_notas as $categoria => $tipos): ?>
                            <?php foreach ($tipos as $tipo): ?>
                                <td>
                                    <div class="nota-item">
                                        <input type="number" 
                                            class="grade-input <?php echo strtolower($categoria); ?>" 
                                            name="notas[<?php echo $estudiante['id']; ?>][<?php echo $tipo['id']; ?>]" 
                                            value="<?php echo isset($estudiante['calificaciones'][$tipo['id']]) ? $estudiante['calificaciones'][$tipo['id']] : ''; ?>" 
                                            min="0" 
                                            max="5" 
                                            step="0.1"
                                            data-estudiante-id="<?php echo $estudiante['id']; ?>"
                                            data-tipo-nota-id="<?php echo $tipo['id']; ?>"
                                            data-categoria="<?php echo $categoria; ?>">
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        
                        <td>
                            <div class="definitiva-cell">
                                <span class="final-grade <?php echo CalificacionesHelper::getColorClase((float)$definitiva); ?>">
                                    <?php echo $definitiva; ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn-guardar-todas" id="btnGuardarTodasNotas">
            <i class="fas fa-save"></i> Guardar Todas las Notas
        </button>
    </div>
</form>