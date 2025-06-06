<?php
/**
 * Vista de tarjetas de estudiantes
 * Incluido desde ver_estudiantes.php
 */
?>
<div class="students-grid">
    <?php foreach ($estudiantes as $estudiante): 
        $resultado = CalificacionesHelperSimple::calcularDefinitiva($estudiante['calificaciones'] ?? [], $tipos_notas);
        $definitiva = $resultado['definitiva'];
        $completitud = CalificacionesHelperSimple::calcularCompletitud($estudiante['calificaciones'] ?? [], $tipos_notas);
        $porcentaje_completitud = $completitud['porcentaje'];
        $completo = $completitud['completo'];
    ?>
        <div class="student-card" data-student-id="<?php echo $estudiante['id']; ?>">
            <div class="card-header">                <div class="student-photo">
                    <?php if (!empty($estudiante['foto_url'])): ?>
                        <img src="<?php echo htmlspecialchars($estudiante['foto_url']); ?>" alt="Foto de <?php echo htmlspecialchars($estudiante['nombres']); ?>">
                    <?php else: ?>
                        <?php echo strtoupper(substr($estudiante['nombres'], 0, 1) . substr($estudiante['apellidos'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="student-info">
                    <h3 class="student-name" title="<?php echo htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']); ?>">
                        <?php echo htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']); ?>
                    </h3>
                </div>
                <div class="card-grade <?php echo CalificacionesHelperSimple::getColorClase((float)$definitiva); ?>">
                    <?php echo $definitiva; ?>
                </div>
            </div>
            <div class="card-body">
                <?php foreach ($tipos_notas as $categoria => $tipos): ?>
                    <div class="categoria-group">
                        <h4 class="categoria-title">
                            <i class="fas fa-<?php echo $iconos_categoria[$categoria] ?? 'star'; ?>"></i>
                            <?php echo $categoria; ?>
                        </h4>
                        <div class="notas-grid">
                            <?php foreach ($tipos as $tipo): ?>
                                <div class="nota-card-item">
                                    <div class="nota-card-label">
                                        <span><?php echo htmlspecialchars($tipo['nombre'] ?? 'Sin nombre'); ?></span>
                                        <span class="nota-card-percent"><?php echo isset($tipo['porcentaje']) ? $tipo['porcentaje'].'%' : '0%'; ?></span>
                                    </div>
                                    <input type="number" 
                                        class="nota-input <?php echo strtolower($categoria); ?>" 
                                        name="notas_card[<?php echo $estudiante['id']; ?>][<?php echo $tipo['id']; ?>]" 
                                        value="<?php echo isset($estudiante['calificaciones'][$tipo['id']]) ? $estudiante['calificaciones'][$tipo['id']] : ''; ?>" 
                                        min="0" 
                                        max="5" 
                                        step="0.1"
                                        data-estudiante-id="<?php echo $estudiante['id']; ?>"
                                        data-tipo-nota-id="<?php echo $tipo['id']; ?>"
                                        data-categoria="<?php echo $categoria; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer">
                <div class="completion-status">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $porcentaje_completitud; ?>%"></div>
                    </div>
                    <span class="status-badge <?php echo $completo ? 'status-complete' : 'status-incomplete'; ?>">
                        <i class="fas fa-<?php echo $completo ? 'check-circle' : 'clock'; ?>"></i>
                        <?php echo $completo ? 'Completo' : 'Pendiente'; ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>