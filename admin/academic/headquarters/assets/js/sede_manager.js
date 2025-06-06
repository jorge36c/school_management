const SedeManager = {
    init() {
        // Inicializar eventos necesarios
        this.setupEventListeners();
    },

    setupEventListeners() {
        // Event listener para mostrar el modal de creación
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-crear-nivel')) {
                const sedeId = e.target.dataset.sedeId;
                this.showCreateModal(sedeId);
            }
        });

        // Event listener para cerrar el modal
        document.addEventListener('click', (e) => {
            if (e.target.matches('.close-modal, .modal-backdrop')) {
                this.closeModal();
            }
        });
    },

    showCreateModal(sedeId) {
        // Prevenir la apertura de múltiples modales
        if (document.querySelector('.modal-backdrop')) {
            console.warn('Modal ya está abierto.');
            return;
        }

        const modalHtml = `
            <div class="modal-backdrop"></div>
            <div class="modal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Crear Nivel Educativo</h5>
                            <button type="button" class="close close-modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="createLevelForm">
                                <input type="hidden" name="sede_id" value="${sedeId}">
                                <div class="form-group">
                                    <label for="nombre">Nivel</label>
                                    <select name="nombre" class="form-control" required>
                                        <option value="">Seleccione un nivel</option>
                                        <option value="preescolar">Preescolar</option>
                                        <option value="primaria">Primaria</option>
                                        <option value="secundaria">Secundaria</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary close-modal">
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="SedeManager.createLevel()">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Agregar el modal y backdrop al DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Mostrar el modal
        const modal = document.querySelector('.modal');
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        console.log('Modal de creación de nivel mostrado.');
    },

    closeModal() {
        const modal = document.querySelector('.modal');
        const backdrop = document.querySelector('.modal-backdrop');
        if (modal && backdrop) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            setTimeout(() => {
                backdrop.remove();
                modal.remove();
                console.log('Modal cerrado y eliminado del DOM.');
            }, 200);
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    SedeManager.init();
});
