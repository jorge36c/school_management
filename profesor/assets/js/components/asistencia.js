/**
 * Script para el módulo de asistencia
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const formReportes = document.getElementById('formReportes');
    const formAsistencia = document.getElementById('formAsistencia');
    const btnExportarExcel = document.getElementById('btnExportarExcel');
    const marcarTodosPresenteBtn = document.getElementById('marcarTodosPresenteBtn');
    const marcarTodosAusenteBtn = document.getElementById('marcarTodosAusenteBtn');
    const verDetalleButtons = document.querySelectorAll('.ver-detalle');
    const modalDetalleAsistencia = document.getElementById('modalDetalleAsistencia');
    const detalleContenido = document.getElementById('detalleContenido');
    
    // Validación del formulario de reportes
    if (formReportes) {
        formReportes.addEventListener('submit', function(e) {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const gradoId = document.getElementById('grado_id').value;
            
            if (!gradoId) {
                e.preventDefault();
                showAlert('Debe seleccionar un grupo para generar el reporte', 'danger');
                return;
            }
            
            if (!fechaInicio || !fechaFin) {
                e.preventDefault();
                showAlert('Debe seleccionar fechas de inicio y fin', 'danger');
                return;
            }
            
            if (fechaInicio > fechaFin) {
                e.preventDefault();
                showAlert('La fecha inicial no puede ser mayor que la fecha final', 'danger');
                return;
            }
        });
    }
    
    // Validación del formulario de asistencia
    if (formAsistencia) {
        formAsistencia.addEventListener('submit', function(e) {
            const fecha = document.querySelector('input[name="fecha"]').value;
            const fechaActual = new Date().toISOString().split('T')[0];
            
            if (new Date(fecha) > new Date(fechaActual)) {
                e.preventDefault();
                showAlert('No se puede registrar asistencia para una fecha futura', 'danger');
                return;
            }
            
            // Mostrar indicador de carga
            showLoading();
        });
    }
    
    // Botones para marcar todos
    if (marcarTodosPresenteBtn) {
        marcarTodosPresenteBtn.addEventListener('click', function() {
            document.querySelectorAll('input[name="estados[]"][value="presente"]').forEach(radio => {
                radio.checked = true;
                const label = radio.closest('label');
                if (label) {
                    label.classList.add('active');
                    const btnGroup = label.closest('.btn-group');
                    if (btnGroup) {
                        btnGroup.querySelectorAll('label:not(.btn-outline-success)').forEach(l => {
                            l.classList.remove('active');
                        });
                    }
                }
            });
            
            showAlert('Todos marcados como presentes', 'success', 2000);
        });
    }
    
    if (marcarTodosAusenteBtn) {
        marcarTodosAusenteBtn.addEventListener('click', function() {
            document.querySelectorAll('input[name="estados[]"][value="ausente"]').forEach(radio => {
                radio.checked = true;
                const label = radio.closest('label');
                if (label) {
                    label.classList.add('active');
                    const btnGroup = label.closest('.btn-group');
                    if (btnGroup) {
                        btnGroup.querySelectorAll('label:not(.btn-outline-danger)').forEach(l => {
                            l.classList.remove('active');
                        });
                    }
                }
            });
            
            showAlert('Todos marcados como ausentes', 'danger', 2000);
        });
    }
    
    // Botones para ver detalle
    if (verDetalleButtons.length > 0) {
        verDetalleButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const estudianteId = this.getAttribute('data-estudiante-id');
                const estudianteNombre = this.getAttribute('data-estudiante-nombre');
                const fechaInicio = this.getAttribute('data-fecha-inicio');
                const fechaFin = this.getAttribute('data-fecha-fin');
                
                // Actualizar título del modal
                const modalTitle = document.getElementById('modalDetalleAsistenciaLabel');
                if (modalTitle) {
                    modalTitle.textContent = `Detalle de Asistencia: ${estudianteNombre}`;
                }
                
                // Mostrar modal con indicador de carga
                if (window.jQuery && window.jQuery.fn.modal) {
                    $('#modalDetalleAsistencia').modal('show');
                } else if (modalDetalleAsistencia) {
                    modalDetalleAsistencia.classList.add('show');
                    modalDetalleAsistencia.style.display = 'block';
                }
                
                // Preparar datos para enviar por POST
                const formData = new FormData();
                formData.append('estudiante_id', estudianteId);
                formData.append('fecha_inicio', fechaInicio);
                formData.append('fecha_fin', fechaFin);
                
                // Cargar detalles mediante Fetch API
                fetch('detalle_asistencia.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.text();
                })
                .then(data => {
                    if (detalleContenido) {
                        detalleContenido.innerHTML = data;
                    }
                })
                .catch(error => {
                    if (detalleContenido) {
                        detalleContenido.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> 
                                Error al cargar los detalles: ${error.message}
                            </div>
                        `;
                    }
                });
            });
        });
    }
    
    // Inicializar DataTable si existe
    const tablaReporte = document.getElementById('tablaReporte');
    if (tablaReporte && window.jQuery && window.jQuery.fn.DataTable) {
        $(tablaReporte).DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
            },
            pageLength: 10,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copiar',
                    className: 'btn btn-sm btn-outline-secondary'
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-sm btn-outline-success'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-sm btn-outline-danger'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-sm btn-outline-primary'
                }
            ]
        });
    }
    
    /**
     * Muestra una alerta personalizada
     */
    function showAlert(message, type = 'info', timeout = 5000) {
        // Crear elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'danger' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        
        // Agregar al DOM
        const container = document.querySelector('.container-fluid');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto cerrar después de timeout
            if (timeout > 0) {
                setTimeout(() => {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 150);
                }, timeout);
            }
        }
    }
    
    /**
     * Muestra indicador de carga
     */
    function showLoading() {
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading-overlay';
        loadingDiv.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
        `;
        
        document.body.appendChild(loadingDiv);
        
        return loadingDiv;
    }
});