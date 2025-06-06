/**
 * tipos_notas.js
 * Gestiona la creación, edición y eliminación de tipos de notas
 * 
 * Ruta: assets/js/calificaciones/tipos_notas.js
 */

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos DOM
    const btnGestionarTiposNotas = document.getElementById('btnGestionarTiposNotas');
    const modalTiposNotas = document.getElementById('modalTiposNotas');
    const modalAgregarTipo = document.getElementById('modalAgregarTipo');
    const btnAddTipo = document.querySelectorAll('.btn-add-tipo');
    const formTipoNota = document.getElementById('formTipoNota');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const toast = document.getElementById('toast');
    const closeButtons = document.querySelectorAll('.close');
    
    // Variables para almacenar el estado
    let tiposNotas = {};
    const esMultigrado = document.getElementById('es_multigrado')?.value === '1';
    
    // Función para mostrar el toast
    function showToast(message, isSuccess = true) {
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = toast.querySelector('i');
        
        toastMessage.textContent = message;
        
        if (isSuccess) {
            toast.classList.remove('toast-error');
            toast.classList.add('toast-success');
            toastIcon.className = 'fas fa-check-circle';
        } else {
            toast.classList.remove('toast-success');
            toast.classList.add('toast-error');
            toastIcon.className = 'fas fa-exclamation-circle';
        }
        
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // Función para mostrar el overlay de carga
    function toggleLoading(show) {
        if (show) {
            loadingOverlay.style.display = 'flex';
        } else {
            loadingOverlay.style.display = 'none';
        }
    }
    
    // Gestión de tipos de notas
    if (btnGestionarTiposNotas) {
        // Abrir modal de tipos de notas
        btnGestionarTiposNotas.addEventListener('click', function() {
            cargarTiposNotas();
            modalTiposNotas.style.display = 'flex';
        });
    }
    
    // Función para cargar los tipos de notas
    function cargarTiposNotas() {
        toggleLoading(true);
        
        // Construir la URL con los parámetros según el tipo
        let url = 'obtener_tipos_notas.php?asignacion_id=' + document.getElementById('asignacion_id').value;
        
        if (esMultigrado) {
            url = `obtener_tipos_notas.php?nivel=${document.getElementById('nivel').value}&sede_id=${document.getElementById('sede_id').value}&materia_id=${document.getElementById('materia_id').value}&es_multigrado=1`;
        }
        
        fetch(url)
        .then(response => response.json())
        .then(data => {
            toggleLoading(false);
            
            if (data.success) {
                tiposNotas = data.tipos_notas;
                
                // Actualizar la UI para cada categoría
                for (const categoria in tiposNotas) {
                    actualizarCategoriaUI(categoria, tiposNotas[categoria]);
                }
            } else {
                showToast('Error al cargar tipos de notas: ' + data.message, false);
            }
        })
        .catch(error => {
            toggleLoading(false);
            showToast('Error de conexión: ' + error, false);
            console.error('Error:', error);
        });
    }
    
    // Función para actualizar la UI de una categoría
    function actualizarCategoriaUI(categoria, tipos) {
        const contenedor = document.getElementById('tiposNotas' + categoria);
        const progresoEl = document.getElementById('progreso' + categoria);
        
        if (!contenedor || !progresoEl) return;
        
        // Limpiar el contenedor
        contenedor.innerHTML = '';
        
        // Calcular el porcentaje total
        let porcentajeTotal = 0;
        tipos.forEach(tipo => {
            porcentajeTotal += parseFloat(tipo.porcentaje);
        });
        
        // Actualizar la barra de progreso
        let maxValue = 40; // Por defecto TAREAS
        if (categoria === 'EVALUACIONES') maxValue = 50;
        if (categoria === 'AUTOEVALUACION') maxValue = 10;
        
        const porcentaje = Math.min(100, (porcentajeTotal / maxValue) * 100);
        progresoEl.style.width = porcentaje + '%';
        progresoEl.textContent = porcentajeTotal + '%';
        progresoEl.setAttribute('aria-valuenow', porcentajeTotal);
        
        if (porcentaje > 100) {
            progresoEl.classList.add('progress-warning');
        } else if (porcentaje === 100) {
            progresoEl.classList.add('progress-success');
        } else {
            progresoEl.classList.add('progress-info');
        }
        
        // Agregar cada tipo de nota al contenedor
        tipos.forEach(tipo => {
            const tipoEl = document.createElement('div');
            tipoEl.className = 'tipo-nota-item';
            
            tipoEl.innerHTML = `
                <div class="tipo-info">
                    <span class="tipo-nombre">${tipo.nombre}</span>
                    <span class="tipo-porcentaje">${tipo.porcentaje}%</span>
                </div>
                <div class="tipo-actions">
                    <button type="button" class="btn-edit-tipo" data-id="${tipo.id}" data-categoria="${categoria}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn-delete-tipo" data-id="${tipo.id}" data-categoria="${categoria}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            contenedor.appendChild(tipoEl);
            
            // Agregar event listeners a los botones
            tipoEl.querySelector('.btn-edit-tipo').addEventListener('click', function() {
                editarTipoNota(tipo.id, tipo.nombre, tipo.porcentaje, categoria);
            });
            
            tipoEl.querySelector('.btn-delete-tipo').addEventListener('click', function() {
                confirmarEliminarTipoNota(tipo.id, tipo.nombre);
            });
        });
    }
    
    // Event listeners para agregar nuevo tipo de nota
    btnAddTipo.forEach(btn => {
        btn.addEventListener('click', function() {
            const categoria = this.dataset.categoria;
            agregarTipoNota(categoria);
        });
    });
    
    // Función para abrir el modal de agregar tipo de nota
    function agregarTipoNota(categoria) {
        document.getElementById('categoria_tipo').value = categoria;
        document.getElementById('tipo_id').value = '';
        document.getElementById('edit_mode').value = '0';
        document.getElementById('nombreTipoNota').value = '';
        document.getElementById('porcentajeTipoNota').value = '';
        
        document.getElementById('tituloModalAgregar').textContent = 'Agregar Tipo de Nota - ' + categoria;
        
        modalAgregarTipo.style.display = 'flex';
        document.getElementById('nombreTipoNota').focus();
    }
    
    // Función para editar un tipo de nota
    function editarTipoNota(id, nombre, porcentaje, categoria) {
        document.getElementById('categoria_tipo').value = categoria;
        document.getElementById('tipo_id').value = id;
        document.getElementById('edit_mode').value = '1';
        document.getElementById('nombreTipoNota').value = nombre;
        document.getElementById('porcentajeTipoNota').value = porcentaje;
        
        document.getElementById('tituloModalAgregar').textContent = 'Editar Tipo de Nota - ' + categoria;
        
        modalAgregarTipo.style.display = 'flex';
        document.getElementById('nombreTipoNota').focus();
    }
    
    // Función para confirmar eliminación de tipo de nota
    function confirmarEliminarTipoNota(id, nombre) {
        if (confirm(`¿Está seguro que desea eliminar el tipo de nota "${nombre}"? Esta acción no se puede deshacer.`)) {
            eliminarTipoNota(id);
        }
    }
    
    // Función para eliminar un tipo de nota
    function eliminarTipoNota(id) {
        toggleLoading(true);
        
        const formData = new FormData();
        formData.append('tipo_id', id);
        
        if (esMultigrado) {
            formData.append('es_multigrado', '1');
            formData.append('nivel', document.getElementById('nivel').value);
            formData.append('sede_id', document.getElementById('sede_id').value);
            formData.append('materia_id', document.getElementById('materia_id').value);
        }
        
        fetch('eliminar_tipo_nota.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            toggleLoading(false);
            
            if (data.success) {
                showToast('Tipo de nota eliminado correctamente');
                // Recargar los tipos de notas
                cargarTiposNotas();
            } else {
                showToast('Error al eliminar: ' + data.message, false);
            }
        })
        .catch(error => {
            toggleLoading(false);
            showToast('Error de conexión: ' + error, false);
            console.error('Error:', error);
        });
    }
    
    // Evento submit del formulario de tipo de nota
    formTipoNota.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nombre = document.getElementById('nombreTipoNota').value.trim();
        const porcentaje = parseFloat(document.getElementById('porcentajeTipoNota').value);
        const categoria = document.getElementById('categoria_tipo').value;
        const tipoId = document.getElementById('tipo_id').value;
        const editMode = document.getElementById('edit_mode').value === '1';
        
        if (!nombre || isNaN(porcentaje) || porcentaje <= 0 || porcentaje > 100) {
            showToast('Por favor complete todos los campos correctamente', false);
            return;
        }
        
        // Guardar el tipo de nota
        guardarTipoNota(nombre, porcentaje, categoria, tipoId, editMode);
    });
    
    // Función para guardar un tipo de nota
    function guardarTipoNota(nombre, porcentaje, categoria, tipoId, editMode) {
        toggleLoading(true);
        
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('porcentaje', porcentaje);
        formData.append('categoria', categoria);
        
        if (editMode) {
            formData.append('tipo_id', tipoId);
            formData.append('edit_mode', '1');
        } else {
            formData.append('asignacion_id', document.getElementById('asignacion_id').value);
        }
        
        if (esMultigrado) {
            formData.append('es_multigrado', '1');
            formData.append('nivel', document.getElementById('nivel').value);
            formData.append('sede_id', document.getElementById('sede_id').value);
            formData.append('materia_id', document.getElementById('materia_id').value);
        }
        
        fetch('guardar_tipo_nota.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            toggleLoading(false);
            
            if (data.success) {
                showToast(editMode ? 'Tipo de nota actualizado correctamente' : 'Tipo de nota agregado correctamente');
                // Cerrar el modal
                modalAgregarTipo.style.display = 'none';
                // Recargar los tipos de notas
                cargarTiposNotas();
            } else {
                showToast('Error: ' + data.message, false);
            }
        })
        .catch(error => {
            toggleLoading(false);
            showToast('Error de conexión: ' + error, false);
            console.error('Error:', error);
        });
    }
    
    // Cerrar modales
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Cerrar modales al hacer clic fuera del contenido
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
});