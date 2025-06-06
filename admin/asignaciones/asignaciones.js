// Variables globales
let currentProfesorId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2 para mejorar los selectores
    $('.form-select').select2({
        width: '100%'
    });

    // Cargar asignaciones iniciales
    cargarTodasLasAsignaciones();

    // Configurar búsqueda de profesor
    const buscarInput = document.getElementById('buscarProfesor');
    buscarInput.addEventListener('input', function(e) {
        const busqueda = e.target.value.toLowerCase();
        filtrarProfesores(busqueda);
    });

    // Escuchar cambios en el período
    document.getElementById('periodo').addEventListener('change', function() {
        cargarTodasLasAsignaciones();
    });
});

// Función para filtrar profesores
function filtrarProfesores(busqueda) {
    const cards = document.querySelectorAll('.profesor-card');
    cards.forEach(card => {
        const nombre = card.querySelector('.card-header h5').textContent.toLowerCase();
        card.style.display = nombre.includes(busqueda) ? '' : 'none';
    });
}

// Función para abrir el modal de asignación
function asignarGrupo(profesorId) {
    currentProfesorId = profesorId;
    document.getElementById('profesor_id').value = profesorId;
    
    // Cargar asignaturas del profesor
    cargarAsignaturas(profesorId);
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('modalAsignacion'));
    modal.show();
}

// Función para cargar asignaturas
function cargarAsignaturas(profesorId) {
    fetch(`get_asignaturas.php?profesor_id=${profesorId}`)
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector('select[name="asignatura_id"]');
            select.innerHTML = '';
            data.forEach(asignatura => {
                const option = new Option(asignatura.nombre, asignatura.id);
                select.add(option);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Función para guardar la asignación
function guardarAsignacion() {
    const formData = new FormData(document.getElementById('formAsignacion'));
    formData.append('periodo_id', document.getElementById('periodo').value);

    fetch('guardar_asignacion.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar la lista de asignaciones
            cargarAsignacionesProfesor(currentProfesorId);
            // Cerrar el modal
            bootstrap.Modal.getInstance(document.getElementById('modalAsignacion')).hide();
            // Mostrar mensaje de éxito
            mostrarMensaje('Asignación guardada exitosamente', 'success');
        } else {
            mostrarMensaje(data.message || 'Error al guardar la asignación', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error al procesar la solicitud', 'danger');
    });
}

// Función para cargar todas las asignaciones
function cargarTodasLasAsignaciones() {
    const periodoId = document.getElementById('periodo').value;
    const profesores = document.querySelectorAll('.profesor-card');
    
    profesores.forEach(card => {
        const profesorId = card.querySelector('.btn-primary').getAttribute('onclick').match(/\d+/)[0];
        cargarAsignacionesProfesor(profesorId);
    });
}

// Función para cargar asignaciones de un profesor
function cargarAsignacionesProfesor(profesorId) {
    const periodoId = document.getElementById('periodo').value;
    
    fetch(`get_asignaciones.php?profesor_id=${profesorId}&periodo_id=${periodoId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.querySelector(`.profesor-card [onclick*="${profesorId}"]`)
                .closest('.card')
                .querySelector('.asignaciones-list');
            
            container.innerHTML = generarHTMLAsignaciones(data);
        })
        .catch(error => console.error('Error:', error));
}

// Función para generar HTML de asignaciones
function generarHTMLAsignaciones(asignaciones) {
    if (asignaciones.length === 0) {
        return '<p class="text-muted">No hay asignaciones</p>';
    }

    return `
        <div class="list-group">
            ${asignaciones.map(asig => `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${asig.grupo_nombre}</strong><br>
                        <small>${asig.asignatura_nombre}</small>
                    </div>
                    <button class="btn btn-danger btn-sm" 
                            onclick="eliminarAsignacion(${asig.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('')}
        </div>
    `;
}

// Función para eliminar asignación
function eliminarAsignacion(asignacionId) {
    if (!confirm('¿Está seguro de eliminar esta asignación?')) return;

    fetch('eliminar_asignacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: asignacionId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarTodasLasAsignaciones();
            mostrarMensaje('Asignación eliminada exitosamente', 'success');
        } else {
            mostrarMensaje(data.message || 'Error al eliminar la asignación', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error al procesar la solicitud', 'danger');
    });
}

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container').insertAdjacentElement('afterbegin', alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
} 