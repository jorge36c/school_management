<!-- Modal para gestionar tipos de notas -->
<div class="modal" id="modalTiposNotas">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-cog"></i> Gestionar Tipos de Notas</h2>
            <button class="close" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body">
            <div class="tipos-notas-container">
                <!-- Tareas -->
                <div class="categoria-box">
                    <div class="categoria-header">
                        <h3><i class="fas fa-book"></i> TAREAS (40%)</h3>
                        <div class="progress-container">
                            <div class="progress-info">
                                <span id="porcentajeTAREAS">0%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progresoTAREAS" style="width: 0%"></div>
                            </div>
                        </div>
                        <button type="button" class="btn-add-tipo" data-categoria="TAREAS">
                            <i class="fas fa-plus-circle"></i> Agregar
                        </button>
                    </div>
                    <div class="categoria-content" id="tiposNotasTAREAS">
                        <!-- Aquí se cargarán los tipos de notas de TAREAS -->
                        <div class="empty-list-message">
                            <i class="fas fa-info-circle"></i>
                            Cargando tipos de notas...
                        </div>
                    </div>
                </div>
                
                <!-- Evaluaciones -->
                <div class="categoria-box">
                    <div class="categoria-header">
                        <h3><i class="fas fa-clipboard-check"></i> EVALUACIONES (50%)</h3>
                        <div class="progress-container">
                            <div class="progress-info">
                                <span id="porcentajeEVALUACIONES">0%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progresoEVALUACIONES" style="width: 0%"></div>
                            </div>
                        </div>
                        <button type="button" class="btn-add-tipo" data-categoria="EVALUACIONES">
                            <i class="fas fa-plus-circle"></i> Agregar
                        </button>
                    </div>
                    <div class="categoria-content" id="tiposNotasEVALUACIONES">
                        <!-- Aquí se cargarán los tipos de notas de EVALUACIONES -->
                        <div class="empty-list-message">
                            <i class="fas fa-info-circle"></i>
                            Cargando tipos de notas...
                        </div>
                    </div>
                </div>
                
                <!-- Autoevaluación -->
                <div class="categoria-box">
                    <div class="categoria-header">
                        <h3><i class="fas fa-user-check"></i> AUTOEVALUACIÓN (10%)</h3>
                        <div class="progress-container">
                            <div class="progress-info">
                                <span id="porcentajeAUTOEVALUACION">0%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progresoAUTOEVALUACION" style="width: 0%"></div>
                            </div>
                        </div>
                        <button type="button" class="btn-add-tipo" data-categoria="AUTOEVALUACION">
                            <i class="fas fa-plus-circle"></i> Agregar
                        </button>
                    </div>
                    <div class="categoria-content" id="tiposNotasAUTOEVALUACION">
                        <!-- Aquí se cargarán los tipos de notas de AUTOEVALUACIÓN -->
                        <div class="empty-list-message">
                            <i class="fas fa-info-circle"></i>
                            Cargando tipos de notas...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary cerrar-modal">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>