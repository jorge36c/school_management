/**
 * ver_estudiantes.js - Script mejorado para la gestión de calificaciones
 * 
 * Este archivo contiene todas las funcionalidades JavaScript necesarias para
 * la interfaz de gestión de calificaciones, incluyendo:
 * - Cambio de pestañas entre vistas de tabla y tarjetas
 * - Gestión de tipos de notas 
 * - Guardar calificaciones
 * - Calcular notas definitivas
 * - Exportar datos
 */

$(document).ready(function() {
    // Variables globales
    const asignacionId = $('#asignacion_id').val();
    let tiposNotas = {
        'TAREAS': [],
        'EVALUACIONES': [],
        'AUTOEVALUACION': []
    };
    
    // Inicializaciones
    initTabSwitching();
    initModals();
    initTiposNotasManagement();
    initAutoSave();
    
    /**
     * Inicializa el cambio entre pestañas
     */
    function initTabSwitching() {
        $('.tab-btn').on('click', function() {
            const targetView = $(this).data('view');
            
            // Cambiar estado activo en botones
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Cambiar contenido visible
            $('.tab-content').removeClass('active');
            $(`#${targetView}`).addClass('active');
            
            // Almacenar preferencia del usuario
            localStorage.setItem('preferedView', targetView);
        });
        
        // Recuperar preferencia guardada
        const savedView = localStorage.getItem('preferedView');
        if (savedView) {
            $(`.tab-btn[data-view="${savedView}"]`).click();
        }
    }
    
    /**
     * Inicializa los modales
     */
    function initModals() {
        // Abrir modal de tipos de notas
        $('#btnGestionarTiposNotas').on('click', function() {
            cargarTiposNotas();
            $('#modalTiposNotas').fadeIn(200);
        });
        
        // Cerrar cualquier modal
        $('.close').on('click', function() {
            $(this).closest('.modal').fadeOut(200);
        });
        
        // Cerrar al hacer clic fuera del contenido
        $('.modal').on('click', function(e) {
            if ($(e.target).hasClass('modal')) {
                $(this).fadeOut(200);
            }
        });
        
        // Prevenir cierre al hacer clic dentro del contenido
        $('.modal-content').on('click', function(e) {
            e.stopPropagation();
        });
    }
    
    /**
     * Inicializa la gestión de tipos de notas
     */
    function initTiposNotasManagement() {
        // Botón de agregar tipo de nota
        $('.btn-add-tipo').on('click', function() {
            const categoria = $(this).data('categoria');
            
            // Resetear formulario
            $('#formTipoNota')[0].reset();
            $('#tipo_id').val('');
            $('#edit_mode').val('0');
            $('#categoria_tipo').val(categoria);
            
            // Cambiar título según categoría
            $('#tituloModalAgregar').text(`Agregar tipo de nota - ${categoria}`);
            
            // Mostrar modal
            $('#modalAgregarTipo').fadeIn(200);
        });
        
        // Guardar tipo de nota
        $('#formTipoNota').on('submit', function(e) {
            e.preventDefault();
            
            const tipoId = $('#tipo_id').val();
            const editMode = $('#edit_mode').val() === '1';
            const categoria = $('#categoria_tipo').val();
            const nombre = $('#nombreTipoNota').val();
            const porcentaje = $('#porcentajeTipoNota').val();
            
            if (!nombre || !porcentaje) {
                showToast('Todos los campos son obligatorios', 'error');
                return;
            }
            
            // Mostrar overlay de carga
            $('#loadingOverlay').fadeIn(100);
            
            // Realizar petición AJAX
            $.ajax({
                url: 'guardar_tipo_nota.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    asignacion_id: asignacionId,
                    tipo_id: tipoId,
                    categoria: categoria,
                    nombre: nombre,
                    porcentaje: porcentaje,
                    edit_mode: editMode ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        $('#modalAgregarTipo').fadeOut(200);
                        showToast(response.message || 'Tipo de nota guardado correctamente');
                        
                        // Recargar tipos de notas
                        cargarTiposNotas();
                        
                        // Recargar página después de un breve retraso
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(response.message || 'Error al guardar', 'error');
                    }
                },
                error: function() {
                    showToast('Error de conexión', 'error');
                },
                complete: function() {
                    $('#loadingOverlay').fadeOut(100);
                }
            });
        });
        
        // Editar tipo de nota (delegación de eventos)
        $(document).on('click', '.edit-tipo-btn', function() {
            const tipoId = $(this).data('tipo-id');
            const categoria = $(this).data('categoria');
            const nombre = $(this).data('nombre');
            const porcentaje = $(this).data('porcentaje');
            
            // Llenar formulario
            $('#tipo_id').val(tipoId);
            $('#edit_mode').val('1');
            $('#categoria_tipo').val(categoria);
            $('#nombreTipoNota').val(nombre);
            $('#porcentajeTipoNota').val(porcentaje);
            
            // Cambiar título
            $('#tituloModalAgregar').text(`Editar tipo de nota - ${categoria}`);
            
            // Mostrar modal
            $('#modalAgregarTipo').fadeIn(200);
        });
        
        // Eliminar tipo de nota (delegación de eventos)
        $(document).on('click', '.delete-tipo-btn', function() {
            const tipoId = $(this).data('tipo-id');
            $('#eliminar_tipo_id').val(tipoId);
            
            // Mostrar modal de confirmación
            $('#modalConfirmarEliminar').fadeIn(200);
        });
        
        // Confirmar eliminación
        $('#btnConfirmarEliminar').on('click', function() {
            const tipoId = $('#eliminar_tipo_id').val();
            
            if (!tipoId) return;
            
            // Mostrar overlay de carga
            $('#loadingOverlay').fadeIn(100);
            
            // Realizar petición AJAX
            $.ajax({
                url: 'eliminar_tipo_nota.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    tipo_id: tipoId
                },
                success: function(response) {
                    if (response.success) {
                        $('#modalConfirmarEliminar').fadeOut(200);
                        showToast(response.message || 'Tipo de nota eliminado correctamente');
                        
                        // Recargar tipos de notas
                        cargarTiposNotas();
                        
                        // Recargar página después de un breve retraso
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(response.message || 'Error al eliminar', 'error');
                    }
                },
                error: function() {
                    showToast('Error de conexión', 'error');
                },
                complete: function() {
                    $('#loadingOverlay').fadeOut(100);
                }
            });
        });
    }
    
    /**
     * Carga los tipos de notas desde el servidor
     */
    function cargarTiposNotas() {
        // Mostrar overlay de carga
        $('#loadingOverlay').fadeIn(100);
        
        // Realizar petición AJAX
        $.ajax({
            url: 'obtener_tipos_notas.php',
            type: 'GET',
            dataType: 'json',
            data: {
                asignacion_id: asignacionId
            },
            success: function(response) {
                if (response.success) {
                    tiposNotas = {
                        'TAREAS': [],
                        'EVALUACIONES': [],
                        'AUTOEVALUACION': []
                    };
                    
                    // Organizar tipos de notas por categoría
                    response.tipos_notas.forEach(function(tipo) {
                        const categoria = tipo.categoria || 'TAREAS';
                        
                        // Asegurarse de que la categoría existe
                        if (!tiposNotas[categoria]) {
                            tiposNotas[categoria] = [];
                        }
                        
                        tiposNotas[categoria].push(tipo);
                    });
                    
                    // Actualizar UI para cada categoría
                    updateTiposNotasUI('TAREAS', tiposNotas.TAREAS);
                    updateTiposNotasUI('EVALUACIONES', tiposNotas.EVALUACIONES);
                    updateTiposNotasUI('AUTOEVALUACION', tiposNotas.AUTOEVALUACION);
                    
                    // Actualizar porcentajes
                    updatePorcentajesCategorias();
                } else {
                    showToast(response.message || 'Error al cargar tipos de notas', 'error');
                }