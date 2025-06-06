<!-- Modal para exportar e importar calificaciones -->
<div class="modal" id="modalExportarImportar">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-file-export"></i> Exportar / Importar Calificaciones</h2>
            <button class="close" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body">
            <div class="tabs">
                <div class="tab-header">
                    <button class="tab-btn active" data-tab="exportar"><i class="fas fa-file-export"></i> Exportar</button>
                    <button class="tab-btn" data-tab="importar"><i class="fas fa-file-import"></i> Importar</button>
                </div>
                
                <div class="tab-content active" id="exportar-tab">
                    <h3>Exportar Calificaciones</h3>
                    <p>Exporte las calificaciones actuales a un archivo Excel.</p>
                    
                    <div class="form-group">
                        <label>Formato de Exportación:</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="formato_exportacion" value="completo" checked>
                                Completo (Todos los datos)
                            </label>
                            <label>
                                <input type="radio" name="formato_exportacion" value="simple">
                                Simple (Solo calificaciones)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="btnExportarExcel">
                            <i class="fas fa-download"></i> Exportar a Excel
                        </button>
                    </div>
                </div>
                
                <div class="tab-content" id="importar-tab">
                    <h3>Importar Calificaciones</h3>
                    <p>Seleccione un archivo Excel con las calificaciones para importar.</p>
                    <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> IMPORTANTE: El archivo debe seguir el formato de la plantilla de exportación.</p>
                    
                    <div class="form-group">
                        <label for="archivoImportar">Seleccionar Archivo:</label>
                        <div class="file-input-container">
                            <input type="file" id="archivoImportar" accept=".xlsx,.xls">
                            <div class="file-input-controls">
                                <button type="button" class="btn btn-secondary" id="btnDescargarPlantilla">
                                    <i class="fas fa-file-download"></i> Descargar Plantilla
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="import-preview" id="importPreview">
                        <h4>Vista Previa de Importación</h4>
                        <div class="preview-content">
                            <p class="text-muted">No hay archivo seleccionado</p>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="btnImportarExcel" disabled>
                            <i class="fas fa-upload"></i> Importar Calificaciones
                        </button>
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