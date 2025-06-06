/**
 * Módulo para gestionar tipos de notas
 */
const tiposNotasModule = {
    initialized: false,
    
    init: function() {
        if (this.initialized) return;
        
        // Referencias a elementos DOM
        this.btnGestionarTiposNotas = document.getElementById('btnGestionarTiposNotas');
        this.modalTiposNotas = document.getElementById('modalTiposNotas');
        this.modalAgregarTipo = document.getElementById('modalAgregarTipo');
        this.modalConfirmarEliminar = document.getElementById('modalConfirmarEliminar');
        this.formTipoNota = document.getElementById('formTipoNota');
        this.btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
        
        // Inicializar eventos
        this.initEvents();
        
        this.initialized = true;
    },
    
    initEvents: function() {
        // Abrir modal de tipos de notas
        if (this.btnGestionarTiposNotas && this.modalTiposNotas) {
            this.btnGestionarTiposNotas.addEventListener('click', () => {
                this.cargarTiposNotas();
                this.modalTiposNotas.style.display = 'flex';
            });
        }
        
        // Cerrar modales
        document.querySelectorAll('.close, .cerrar-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                if (this.modalTiposNotas) this.modalTiposNotas.style.display = 'none';
                if (this.modalAgregarTipo) this.modalAgregarTipo.style.display = 'none';
                if (this.modalConfirmarEliminar) this.modalConfirmarEliminar.style.display = 'none';
            });
        });
        
        // Cerrar al hacer clic fuera
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
        
        // Botones para agregar tipos de notas (se agregan dinámicamente)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-add-tipo') || e.target.closest('.btn-add-tipo')) {
                const btn = e.target.classList.contains('btn-add-tipo') ? e.target : e.target.closest('.btn-add-tipo');
                const categoria = btn.dataset.categoria;
                this.abrirModalAgregar(categoria);
            }
        });
        
        // Guardar tipo de nota
        if (this.formTipoNota) {
            this.formTipoNota.addEventListener('submit', (e) => {
                e.preventDefault();
                this.guardarTipoNota();
            });
        }
        
        // Eliminar tipo de nota
        if (this.btnConfirmarEliminar) {
            this.btnConfirmarEliminar.addEventListener('click', () => {
                this.eliminarTipoNota();
            });
        }
    },
    
    cargarTiposNotas: function() {
        const asignacionId = document.getElementById('asignacion_id')?.value;
        const esMultigrado = document.getElementById('es_multigrado')?.value === '1';
        const nivel = document.getElementById('nivel')?.value;
        const sedeId = document.getElementById('sede_id')?.value;
        const materiaId = document.getElementById('materia_id')?.value;
        
        toggleLoading(true);
        
        let url = `../../api/calificaciones/obtener_tipos_notas.php?asignacion_id=${asignacionId}`;
        
        if (esMultigrado) {
            url = `../../api/calificaciones/obtener_tipos_notas.php?es_multigrado=1&nivel=${nivel}&sede_id=${sedeId}&materia_id=${materiaId}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                toggleLoading(false);
                
                if (data.success) {
                    this.actualizarVisualizacionTiposNotas(data.tipos_notas, data.totales_categoria);
                } else {
                    mostrarToast(data.message || 'Error al cargar los tipos de notas', true);
                }
            })
            .catch(error => {
                toggleLoading(false);
                console.error('Error:', error);
                mostrarToast('Error de conexión', true);
            });
    },
    
    actualizarVisualizacionTiposNotas: function(tiposNotas, totalesCategorias) {
        const categorias = ['TAREAS', 'EVALUACIONES', 'AUTOEVALUACION'];
        const limites = { 'TAREAS': 40, 'EVALUACIONES': 50, 'AUTOEVALUACION': 10 };
        
        categorias.forEach(categoria => {
            const contenedor = document.getElementById(`tiposNotas${categoria}`);
            const porcentajeElement = document.getElementById(`porcentaje${categoria}`);
            const progresoElement = document.getElementById(`progreso${categoria}`);
            
            if (contenedor) {
                // Limpiar contenedor
                contenedor.innerHTML = '';
                
                const tiposCategoria = tiposNotas[categoria] || [];
                
                if (tiposCategoria.length === 0) {
                    contenedor.innerHTML = `
                        <div class="empty-list-message">
                            <i class="fas fa-info-circle"></i>
                            No hay tipos de notas configurados en esta categoría.
                        </div>
                    `;
                } else {
                    tiposCategoria.forEach(tipo => {
                        const tipoItem = document.createElement('div');
                        tipoItem.className = 'tipo-nota-item';
                        tipoItem.dataset.id = tipo.id;
                        
                        tipoItem.innerHTML = `
                            <div class="tipo-info">
                                <strong>${tipo.nombre}</strong>
                                <span>${tipo.porcentaje}%</span>
                            </div>
                            <div class="tipo-actions">
                                <button type="button" class="btn-edit" data-id="${tipo.id}" data-categoria="${categoria}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn-delete" data-id="${tipo.id}" data-categoria="${categoria}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        
                        contenedor.appendChild(tipoItem);
                    });
                }
            }
            
            // Actualizar porcentajes
            const totalCategoria = totalesCategorias[categoria] || 0;
            if (porcentajeElement) {
                porcentajeElement.textContent = `${totalCategoria}%`;
            }
            
            if (progresoElement) {
                const porcentajeCompletado = Math.min(100, (totalCategoria / limites[categoria]) * 100);
                progresoElement.style.width = `${porcentajeCompletado}%`;
                
                // Cambiar color según progreso
                progresoElement.className = 'progress-primary';
                if (porcentajeCompletado > 100) {
                    progresoElement.classList.add('progress-danger');
                } else if (porcentajeCompletado === 100) {
                    progresoElement.classList.add('progress-success');
                } else if (porcentajeCompletado > 75) {
                    progresoElement.classList.add('progress-warning');
                }
            }
        });
        
        // Asignar eventos a los botones generados dinámicamente
        this.asignarEventosBotones();
    },
    
    asignarEventosBotones: function() {
        // Botones de editar
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const categoria = btn.dataset.categoria;
                const tipoItem = btn.closest('.tipo-nota-item');
                const nombre = tipoItem.querySelector('strong').textContent;
                const porcentaje = parseFloat(tipoItem.querySelector('span').textContent);
                
                this.abrirModalEditar(id, nombre, porcentaje, categoria);
            });
        });
        
        // Botones de eliminar
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const tipoItem = btn.closest('.tipo-nota-item');
                const nombre = tipoItem.querySelector('strong').textContent;
                
                this.abrirModalEliminar(id, nombre);
            });
        });
    },
    
    abrirModalAgregar: function(categoria) {
        if (!this.modalAgregarTipo) return;
        
        const tituloModal = document.getElementById('tituloModalAgregar');
        const tipoIdInput = document.getElementById('tipo_id');
        const editModeInput = document.getElementById('edit_mode');
        const categoriaTipoInput = document.getElementById('categoria_tipo');
        const nombreInput = document.getElementById('nombreTipoNota');
        const porcentajeInput = document.getElementById('porcentajeTipoNota');
        
        // Restablecer el formulario
        if (tipoIdInput) tipoIdInput.value = '';
        if (editModeInput) editModeInput.value = '0';
        if (categoriaTipoInput) categoriaTipoInput.value = categoria;
        if (nombreInput) nombreInput.value = '';
        if (porcentajeInput) porcentajeInput.value = '';
        
        // Actualizar título
        if (tituloModal) {
            tituloModal.innerHTML = `<i class="fas fa-plus-circle"></i> Agregar Tipo de Nota - ${categoria}`;
        }
        
        this.modalAgregarTipo.style.display = 'flex';
    },
    
    abrirModalEditar: function(id, nombre, porcentaje, categoria) {
        if (!this.modalAgregarTipo) return;
        
        const tituloModal = document.getElementById('tituloModalAgregar');
        const tipoIdInput = document.getElementById('tipo_id');
        const editModeInput = document.getElementById('edit_mode');
        const categoriaTipoInput = document.getElementById('categoria_tipo');
        const nombreInput = document.getElementById('nombreTipoNota');
        const porcentajeInput = document.getElementById('porcentajeTipoNota');
        
        // Completar el formulario con los datos existentes
        if (tipoIdInput) tipoIdInput.value = id;
        if (editModeInput) editModeInput.value = '1';
        if (categoriaTipoInput) categoriaTipoInput.value = categoria;
        if (nombreInput) nombreInput.value = nombre;
        if (porcentajeInput) porcentajeInput.value = porcentaje;
        
        // Actualizar título
        if (tituloModal) {
            tituloModal.innerHTML = `<i class="fas fa-edit"></i> Editar Tipo de Nota - ${categoria}`;
        }
        
        this.modalAgregarTipo.style.display = 'flex';
    },
    
    abrirModalEliminar: function(id, nombre) {
        if (!this.modalConfirmarEliminar) return;
        
        const mensajeElement = this.modalConfirmarEliminar.querySelector('p:first-child');
        const idInput = document.getElementById('eliminar_tipo_id');
        
        if (mensajeElement) {
            mensajeElement.textContent = `¿Está seguro que desea eliminar el tipo de nota "${nombre}"?`;
        }
        
        if (idInput) {
            idInput.value = id;
        }
        
        this.modalConfirmarEliminar.style.display = 'flex';
    },
    
    guardarTipoNota: function() {
        const tipoId = document.getElementById('tipo_id').value;
        const esEdicion = document.getElementById('edit_mode').value === '1';
        const categoria = document.getElementById('categoria_tipo').value;
        const nombre = document.getElementById('nombreTipoNota').value.trim();
        const porcentaje = parseFloat(document.getElementById('porcentajeTipoNota').value);
        const asignacionId = document.getElementById('asignacion_id')?.value;
        
        if (!nombre || isNaN(porcentaje) || porcentaje <= 0) {
            mostrarToast('Por favor complete todos los campos correctamente', true);
            return;
        }
        
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('porcentaje', porcentaje);
        formData.append('categoria', categoria);
        formData.append('asignacion_id', asignacionId);
        
        if (esEdicion) {
            formData.append('tipo_id', tipoId);
            formData.append('edit_mode', '1');
        }
        
        toggleLoading(true);
        
        fetch('../../api/calificaciones/guardar_tipo_nota.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            toggleLoading(false);
            
            if (data.success) {
                mostrarToast(data.message || 'Tipo de nota guardado correctamente');
                this.modalAgregarTipo.style.display = 'none';
                this.cargarTiposNotas(); // Recargar los tipos de notas
            } else {
                mostrarToast(data.message || 'Error al guardar el tipo de nota', true);
            }
        })
        .catch(error => {
            toggleLoading(false);
            console.error('Error:', error);
            mostrarToast('Error de conexión', true);
        });
    },
    
    eliminarTipoNota: function() {
        const tipoId = document.getElementById('eliminar_tipo_id').value;
        
        if (!tipoId) {
            mostrarToast('ID de tipo de nota no válido', true);
            return;
        }
        
        toggleLoading(true);
        
        fetch('../../api/calificaciones/eliminar_tipo_nota.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: tipoId })
        })
        .then(response => response.json())
        .then(data => {
            toggleLoading(false);
            
            if (data.success) {
                mostrarToast(data.message || 'Tipo de nota eliminado correctamente');
                this.modalConfirmarEliminar.style.display = 'none';
                this.cargarTiposNotas(); // Recargar los tipos de notas
            } else {
                mostrarToast(data.message || 'Error al eliminar el tipo de nota', true);
            }
        })
        .catch(error => {
            toggleLoading(false);
            console.error('Error:', error);
            mostrarToast('Error de conexión', true);
        });
    }
};