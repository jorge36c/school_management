const SedeManager = {
    init() {
        this.bindEvents();
        this.setupNotifications();
    },

    bindEvents() {
        // Botones de crear nivel
        document.querySelectorAll('[data-action="crear-nivel"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const sedeId = e.target.dataset.sedeId;
                this.showCreateModal(sedeId);
            });
        });
    },

    showCreateModal(sedeId) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Nivel Educativo</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form id="createLevelForm">
                        <div class="modal-body">
                            <input type="hidden" name="sede_id" value="${sedeId}">
                            <div class="form-group">
                                <label>Nivel</label>
                                <select name="nombre" class="form-control" required>
                                    <option value="">Seleccione un nivel</option>
                                    <option value="preescolar">Preescolar</option>
                                    <option value="primaria">Primaria</option>
                                    <option value="secundaria">Secundaria</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // Inicializar eventos del modal
        const form = modal.querySelector('#createLevelForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleFormSubmit(form, modal);
        });

        // Cerrar modal
        modal.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal(modal));
        });

        // Mostrar modal con animación
        setTimeout(() => modal.classList.add('show'), 50);
    },

    async handleFormSubmit(form, modal) {
        try {
            const formData = new FormData(form);
            const response = await fetch('/school_management/admin/academic/headquarters/create_level.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Nivel educativo creado exitosamente', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    },

    closeModal(modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    },

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
            <button onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        document.body.appendChild(notification);
        requestAnimationFrame(() => notification.classList.add('show'));

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => SedeManager.init());