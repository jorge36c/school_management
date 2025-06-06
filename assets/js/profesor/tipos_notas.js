/**
 * tipos_notas.js - Script para gestionar los tipos de notas
 * 
 * Este script maneja las operaciones CRUD para los tipos de notas:
 * - Mostrar el modal de tipos de notas
 * - Cargar tipos de notas desde el servidor
 * - Agregar nuevos tipos de notas
 * - Editar tipos de notas existentes
 * - Eliminar tipos de notas
 */

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos DOM
    const btnGestionarTiposNotas = document.getElementById('btnGestionarTiposNotas');
    const modalTiposNotas = document.getElementById('modalTiposNotas');
    const modalAgregarTipo = document.getElementById('modalAgregarTipo');
    const modalConfirmarEliminar = document.getElementById('modalConfirmarEliminar');
    
    // Verificar que existan los elementos necesarios
    if (!btnGestionarTiposNotas) {
        console.error('No se encontró el botón de gestionar tipos de notas');
        return;
    }
    
    // Inicializar eventos
    btnGestionarTiposNotas.addEventListener('click', function() {
        cargarTiposNotas();
    });
    
    // Cerrar modales con la X
    document.querySelectorAll('.close, .cerrar-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) modal.style.display = 'none';
        });
    });
    
    // Cerrar modales al hacer clic fuera del contenido
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
    
    /**
     * Carga los tipos de notas para mostrar en el modal
     */
    function cargarTiposNotas() {
        // Obtener parámetros necesarios
        const asignacionId = document.getElementById('asignacion_id')?.value;
        const esMultigrado = document.getElementById('es_multigrado')?.value === '1';
        const nivel = document.getElementById('nivel')?.value;
        const sedeId = document.getElementById('sede_id')?.value;
        const materiaId = document.getElementById('materia_id')?.value;
        const gradoId = document.getElementById('grado_id')?.value;
        
        // Mostrar overlay de carga
        showLoading(true);
        
        // Construir URL para la petición
        fetch('../../../profesor/api/calificaciones/guardar_tipo_nota.php', {
            method: 'POST',
            body: formData
        })
        
        if (esMultigrado) {
            url += `es_multigrado=1&nivel=${nivel}&sede_id=${sedeId}&materia_id=${materiaId}&grado_id=${gradoId}`;
        } else {
            url += `asignacion_id=${asignacionId}`;
        }
        
        // Realizar petición AJAX
        fetch(url)
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                
                if (data.success) {
                    // Actualizar el contenido del modal
                    actualizarModalTiposNotas(data.tipos_notas, data.totales_categoria);
                    
                    // Mostrar el modal
                    if (modalTiposNotas) modalTiposNotas.style.display = 'flex';
                    
                    // Asignar eventos a los botones
                    asignarEventosBotones();
                } else {
                    showToast(data.message || 'Error al cargar tipos de notas', true);
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('Error de conexión', true);
            });
    }
    
    /**
     * Actualiza el contenido del modal con los tipos de notas recibidos
     */
    function actualizarModalTiposNotas(tiposNotas, totalesCategorias) {
        const categorias = ['TAREAS', 'EVALUACIONES', 'AUTOEVALUACION'];
        const limites = { 'TAREAS': 40, 'EVALUACIONES': 50, 'AUTOEVALUACION': 10 };
        
        categorias.forEach(categoria => {
            const contenedor = document.getElementById(`tiposNotas${categoria}`);
            const porcentajeElement = document.getElementById(`porcentaje${categoria}`);
            const progresoElement = document.getElementById(`progreso${categoria}`);
            
            if (contenedor) {
                // Limpiar contenedor
                contenedor.innerHTML = '';
                
                // Filtrar tipos de notas por categoría
                const tiposCategoriaRaw = tiposNotas.filter(tipo => tipo.categoria === categoria);
                
                if (tiposCategoriaRaw.length === 0) {
                    contenedor.innerHTML = `
                        <div class="empty-list-message">
                            <i class="fas fa-info-circle"></i>
                            No hay tipos de notas configurados en esta categoría.
                        </div>
                    `;
                } else {
                    tiposCategoriaRaw.forEach(tipo => {
                        const tipoItem = document.createElement('div');
                        tipoItem.className = 'tipo-nota-item';
                        tipoItem.dataset.id = tipo.id;
                        
                        tipoItem.innerHTML = `
                            <div class="tipo-info">
                                <strong>${tipo.nombre}</strong>
                                <span>${tipo.porcentaje}%</span>
                            </div>
                            <div class="tipo-actions">
                                <button type="button" class="btn-edit" data-id="${tipo.id}" data-nombre="${tipo.nombre}" data-porcentaje="${tipo.porcentaje}" data-categoria="${categoria}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn-delete" data-id="${tipo.id}" data-nombre="${tipo.nombre}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        
                        contenedor.appendChild(tipoItem);
                    });
                }
            }
            
            // Actualizar porcentaje y barra de progreso
            const totalCategoria = totalesCategorias[categoria] || 0;
            
            if (porcentajeElement) {
                porcentajeElement.textContent = `${totalCategoria}%`;
            }
            
            if (progresoElement) {
                const porcentajeCompletado = Math.min(100, (totalCategoria / limites[categoria]) * 100);
                progresoElement.style.width = `${porcentajeCompletado}%`;
                
                // Clases según el porcentaje
                progresoElement.className = 'progress-fill';
                
                if (porcentajeCompletado === 100) {
                    progresoElement.classList.add('progress-success');
                } else if (porcentajeCompletado > 100) {
                    progresoElement.classList.add('progress-danger');
                } else if (porcentajeCompletado >= 75) {
                    progresoElement.classList.add('progress-warning');
                } else {
                    progresoElement.classList.add('progress-primary');
                }
            }
        });
    }
    
    /**
     * Asigna eventos a los botones de acción
     */
    function asignarEventosBotones() {
        // Botones para agregar nuevo tipo
        document.querySelectorAll('.btn-add-tipo').forEach(btn => {
            btn.addEventListener('click', function() {
                const categoria = this.dataset.categoria;
                prepararModalAgregar(categoria);
            });
        });
        
        // Botones para editar tipo
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nombre = this.dataset.nombre;
                const porcentaje = this.dataset.porcentaje;
                const categoria = this.dataset.categoria;
                
                prepararModalEditar(id, nombre, porcentaje, categoria);
            });
        });
        
        // Botones para eliminar tipo
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nombre = this.dataset.nombre;
                
                prepararModalEliminar(id, nombre);
            });
        });
        
        // Formulario para guardar/editar tipo
        const formTipoNota = document.getElementById('formTipoNota');
        if (formTipoNota) {
            formTipoNota.addEventListener('submit', function(e) {
                e.preventDefault();
                guardarTipoNota();
            });
        }
        
        // Botón para confirmar eliminación
        const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
        if (btnConfirmarEliminar) {
            btnConfirmarEliminar.addEventListener('click', function() {
                const tipoId = document.getElementById('eliminar_tipo_id').value;
                eliminarTipoNota(tipoId);
            });
        }
    }
    
    /**
     * Prepara el modal para agregar un nuevo tipo de nota
     */
    function prepararModalAgregar(categoria) {
        if (!modalAgregarTipo) return;
        
        // Restablecer formulario
        const form = document.getElementById('formTipoNota');
        if (form) form.reset();
        
        // Establecer modo de creación
        document.getElementById('tipo_id').value = '';
        document.getElementById('edit_mode').value = '0';
        document.getElementById('categoria_tipo').value = categoria;
        
        // Actualizar título
        document.getElementById('tituloModalAgregar').innerHTML = `
            <i class="fas fa-plus-circle"></i> Agregar Tipo de Nota - ${categoria}
        `;
        
        // Mostrar modal
        modalAgregarTipo.style.display = 'flex';
    }
    
    /**
     * Prepara el modal para editar un tipo de nota existente
     */
    function prepararModalEditar(id, nombre, porcentaje, categoria) {
        if (!modalAgregarTipo) return;
        
        // Establecer modo de edición
        document.getElementById('tipo_id').value = id;
        document.getElementById('edit_mode').value = '1';
        document.getElementById('categoria_tipo').value = categoria;
        document.getElementById('nombreTipoNota').value = nombre;
        document.getElementById('porcentajeTipoNota').value = porcentaje;
        
        // Actualizar título
        document.getElementById('tituloModalAgregar').innerHTML = `
            <i class="fas fa-edit"></i> Editar Tipo de Nota - ${categoria}
        `;
        
        // Mostrar modal
        modalAgregarTipo.style.display = 'flex';
    }
    
    /**
     * Prepara el modal para confirmar eliminación de un tipo de nota
     */
    function prepararModalEliminar(id, nombre) {
        if (!modalConfirmarEliminar) return;
        
        // Establecer ID a eliminar
        document.getElementById('eliminar_tipo_id').value = id;
        
        // Actualizar mensaje de confirmación
        const mensaje = modalConfirmarEliminar.querySelector('p');
        if (mensaje) {
            mensaje.textContent = `¿Está seguro que desea eliminar el tipo de nota "${nombre}"?`;
        }
        
        // Mostrar modal
        modalConfirmarEliminar.style.display = 'flex';
    }
    
    /**
     * Guarda un tipo de nota (nuevo o edición)
     */
    function guardarTipoNota() {
        // Obtener datos del formulario
        const tipoId = document.getElementById('tipo_id').value;
        const editMode = document.getElementById('edit_mode').value === '1';
        const categoria = document.getElementById('categoria_tipo').value;
        const nombre = document.getElementById('nombreTipoNota').value.trim();
        const porcentaje = parseFloat(document.getElementById('porcentajeTipoNota').value);
        
        // Validaciones básicas
        if (!nombre || nombre.length < 3) {
            showToast('El nombre debe tener al menos 3 caracteres', true);
            return;
        }
        
        if (isNaN(porcentaje) || porcentaje <= 0 || porcentaje > 100) {
            showToast('El porcentaje debe ser un número entre 1 y 100', true);
            return;
        }
        
        // Preparar datos para enviar
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('porcentaje', porcentaje);
        formData.append('categoria', categoria);
        formData.append('asignacion_id', document.getElementById('asignacion_id').value);
        
        if (editMode) {
            formData.append('tipo_id', tipoId);
            formData.append('edit_mode', '1');
        }
        
        // Mostrar overlay de carga
        showLoading(true);
        
        // Realizar petición AJAX
        fetch('../../../profesor/api/calificaciones/guardar_tipo_nota.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                // Cerrar modal
                if (modalAgregarTipo) modalAgregarTipo.style.display = 'none';
                
                // Mostrar mensaje de éxito
                showToast(data.message || 'Tipo de nota guardado correctamente');
                
                // Recargar tipos de notas
                cargarTiposNotas();
            } else {
                showToast(data.message || 'Error al guardar tipo de nota', true);
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Error:', error);
            showToast('Error de conexión', true);
        });
    }
    
    /**
     * Elimina un tipo de nota
     */
    function eliminarTipoNota(tipoId) {
        if (!tipoId) {
            showToast('ID de tipo de nota no válido', true);
            return;
        }
        
        // Mostrar overlay de carga
        showLoading(true);
        
        // Realizar petición AJAX
        fetch('../../../profesor/api/calificaciones/eliminar_tipo_nota.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: tipoId })
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                // Cerrar modal
                if (modalConfirmarEliminar) modalConfirmarEliminar.style.display = 'none';
                
                // Mostrar mensaje de éxito
                showToast(data.message || 'Tipo de nota eliminado correctamente');
                
                // Recargar tipos de notas
                cargarTiposNotas();
            } else {
                showToast(data.message || 'Error al eliminar tipo de nota', true);
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Error:', error);
            showToast('Error de conexión', true);
        });
    }
    
    /**
     * Muestra u oculta el overlay de carga
     */
    function showLoading(show) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = show ? 'flex' : 'none';
        }
    }
    
    /**
     * Muestra una notificación toast
     */
    function showToast(mensaje, esError = false) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = toast.querySelector('.toast-icon i');
        const toastTitle = toast.querySelector('.toast-title');
        
        if (toastMessage) toastMessage.textContent = mensaje;
        
        if (esError) {
            toast.classList.add('toast-error');
            if (toastIcon) toastIcon.className = 'fas fa-exclamation-circle';
            if (toastTitle) toastTitle.textContent = 'Error';
        } else {
            toast.classList.remove('toast-error');
            if (toastIcon) toastIcon.className = 'fas fa-check-circle';
            if (toastTitle) toastTitle.textContent = 'Éxito';
        }
        
        toast.classList.add('show');
        
        // Ocultar después de 3 segundos
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
});