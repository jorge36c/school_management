<!-- Modal para agregar o editar tipo de nota -->
<div class="modal" id="modalAgregarTipo">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="tituloModalAgregar"><i class="fas fa-plus-circle"></i> Agregar Tipo de Nota</h2>
            <button class="close" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formTipoNota">
                <!-- Campos ocultos -->
                <input type="hidden" id="tipo_id" name="tipo_id" value="">
                <input type="hidden" id="edit_mode" name="edit_mode" value="0">
                <input type="hidden" id="categoria_tipo" name="categoria_tipo" value="">
                
                <div class="form-group">
                    <label for="nombreTipoNota">Nombre:</label>
                    <input type="text" id="nombreTipoNota" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="porcentajeTipoNota">Porcentaje (%):</label>
                    <input type="number" id="porcentajeTipoNota" name="porcentaje" class="form-control" min="1" max="100" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary cerrar-modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>