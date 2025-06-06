// Módulo de gestión del tipo de enseñanza
const TeachingTypeManager = {
    // Estado del módulo
    state: {
        currentType: null,
        selectedType: null,
        sedeId: null,
        isLoading: false,
        hasChanges: false
    },

    // Elementos del DOM
    elements: {
        optionsContainer: null,
        saveButton: null,
        confirmModal: null
    },

    // Configuración
    config: {
        types: {
            unigrado: {
                icon: 'fa-chalkboard',
                title: 'Unigrado',
                description: 'Sistema tradicional donde cada grupo corresponde a un solo grado académico.',
                features: [
                    'Un grado por grupo',
                    'Enseñanza tradicional',
                    'Mayor especificidad'
                ]
            },
            multigrado: {
                icon: 'fa-users',
                title: 'Multigrado',
                description: 'Sistema flexible donde un grupo puede contener estudiantes de diferentes grados.',
                features: [
                    'Múltiples grados por grupo',
                    'Aprendizaje colaborativo',
                    'Mayor flexibilidad'
                ]
            }
        },
        endpoints: {
            save: '/school_management/admin/academic/headquarters/save_teaching_type.php'
        }
    },

    // Inicialización
    init() {
        this.state.sedeId = window.appConfig?.sede?.id;
        this.state.currentType = window.appConfig?.sede?.tipo_ensenanza;
        this.state.selectedType = this.state.currentType;

        this.initializeElements();
        this.initializeEventListeners();
        this.updateUI();
    },

    // Inicializar referencias a elementos del DOM
    initializeElements() {
        this.elements.optionsContainer = document.querySelector('.teaching-options');
        this.elements.saveButton = document.getElementById('btnGuardar');
        this.elements.confirmModal = document.getElementById('confirmModal');

        if (!this.elements.optionsContainer || !this.elements.saveButton) {
            console.error('Elementos requeridos no encontrados');
            return;
        }
    },

    // Inicializar event listeners
    initializeEventListeners() {
        // Event listeners para opciones de tipo de enseñanza
        const options = document.querySelectorAll('.teaching-option');
        options.forEach(option => {
            option.addEventListener('click', (e) => this.handleOptionClick(e));
        });

        // Event listener para el botón guardar
        this.elements.saveButton.addEventListener('click', () => this.handleSave());

        // Event listeners para el modal de confirmación
        if (this.elements.confirmModal) {
            const confirmBtn = this.elements.confirmModal.querySelector('.btn-primary');
            const cancelBtn = this.elements.confirmModal.querySelector('.btn-secondary');
            const closeBtn = this.elements.confirmModal.querySelector('.close-btn');

            confirmBtn?.addEventListener('click', () => this.confirmChange());
            cancelBtn?.addEventListener('click', () => this.closeModal());
            closeBtn?.addEventListener('click', () => this.closeModal());
        }
    },

    // Manejadores de eventos
    handleOptionClick(event) {
        const option = event.currentTarget;
        const newType = option.dataset.tipo;

        if (newType === this.state.selectedType) return;

        this.state.selectedType = newType;
        this.state.hasChanges = newType !== this.state.currentType;
        this.updateUI();
    },

    async handleSave() {
        if (!this.state.hasChanges) return;

        this.showModal();
    },

    async confirmChange() {
        try {
            this.setLoading(true);
            this.closeModal();

            const response = await this.saveTeachingType();

            if (response.success) {
                this.showNotification('Tipo de enseñanza actualizado correctamente', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(response.message || 'Error al actualizar el tipo de enseñanza');
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
            console.error('Error:', error);
        } finally {
            this.setLoading(false);
        }
    },

    // Métodos de UI
    updateUI() {
        // Actualizar estado visual de las opciones
        document.querySelectorAll('.teaching-option').forEach(option => {
            const isSelected = option.dataset.tipo === this.state.selectedType;
            option.classList.toggle('active', isSelected);
        });

        // Actualizar estado del botón guardar
        if (this.elements.saveButton) {
            this.elements.saveButton.disabled = !this.state.hasChanges;
        }
    },

    setLoading(loading) {
        this.state.isLoading = loading;
        
        if (this.elements.saveButton) {
            const btn = this.elements.saveButton;
            if (loading) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            } else {
                btn.disabled = !this.state.hasChanges;
                btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
            }
        }
    },

    showModal() {
        if (!this.elements.confirmModal) return;
        
        this.elements.confirmModal.style.display = 'flex';
        setTimeout(() => {
            this.elements.confirmModal.querySelector('.modal-content')
                ?.classList.add('show');
        }, 10);
    },

    closeModal() {
        if (!this.elements.confirmModal) return;

        const modalContent = this.elements.confirmModal.querySelector('.modal-content');
        modalContent?.classList.remove('show');
        
        setTimeout(() => {
            this.elements.confirmModal.style.display = 'none';
        }, 300);
    },

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${
                    type === 'success' ? 'check-circle' : 
                    type === 'error' ? 'exclamation-circle' :
                    type === 'warning' ? 'exclamation-triangle' : 
                    'info-circle'
                }"></i>
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
    },

    // Métodos de API
    async saveTeachingType() {
        try {
            const response = await fetch(this.config.endpoints.save, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sede_id: this.state.sedeId,
                    tipo_ensenanza: this.state.selectedType
                })
            });

            return await response.json();
        } catch (error) {
            throw new Error('Error al comunicarse con el servidor');
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => TeachingTypeManager.init());

// Exportar el módulo para uso global si es necesario
window.TeachingTypeManager = TeachingTypeManager;