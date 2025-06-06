<!-- Modal para confirmar eliminación -->
<div class="modal" id="modalConfirmarEliminar">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
            <button class="close" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body">
            <p>¿Está seguro que desea eliminar este tipo de nota?</p>
            <p class="text-danger">Esta acción no se puede deshacer.</p>
            
            <input type="hidden" id="eliminar_tipo_id" value="">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary cerrar-modal">Cancelar</button>
            <button type="button" id="btnConfirmarEliminar" class="btn btn-danger">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>