/**
 * Controlador para manejar las diferentes vistas de sedes (tarjetas, compacta, lista)
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos
    const gridViewBtn = document.getElementById('gridViewBtn');
    const compactViewBtn = document.getElementById('compactViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    const sedesContainer = document.getElementById('sedesContainer');
    const sedeCards = document.querySelectorAll('.sede-card');
    
    // Aplicar vista inicial basada en preferencias guardadas
    const viewMode = localStorage.getItem('sedeViewMode') || 'grid';
    applyViewMode(viewMode);
    
    // Configurar eventos para botones de vista
    if(gridViewBtn) {
        gridViewBtn.addEventListener('click', () => applyViewMode('grid'));
    }
    
    if(compactViewBtn) {
        compactViewBtn.addEventListener('click', () => applyViewMode('compact'));
    }
    
    if(listViewBtn) {
        listViewBtn.addEventListener('click', () => applyViewMode('list'));
    }
    
    // Animar tarjetas en la carga inicial
    animateCards();
    
    /**
     * Función para aplicar modo de vista
     * @param {string} mode - Modo de vista ('grid', 'compact', 'list')
     */
    function applyViewMode(mode) {
        // Guardar preferencia
        localStorage.setItem('sedeViewMode', mode);
        
        // Quitar clases activas de botones
        gridViewBtn.classList.remove('active');
        compactViewBtn.classList.remove('active');
        listViewBtn.classList.remove('active');
        
        // Quitar clases de vista del contenedor
        sedesContainer.classList.remove('compact-view', 'list-view');
        
        // Aplicar vista según modo
        switch(mode) {
            case 'grid':
                gridViewBtn.classList.add('active');
                break;
            case 'compact':
                sedesContainer.classList.add('compact-view');
                compactViewBtn.classList.add('active');
                break;
            case 'list':
                sedesContainer.classList.add('list-view');
                listViewBtn.classList.add('active');
                break;
        }
    }
    
    /**
     * Función para animar tarjetas en la carga inicial
     */
    function animateCards() {
        sedeCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50 + (index * 40)); // Efecto cascada
        });
    }
});

/**
 * Función para confirmar cambio de estado de una sede
 * @param {number} id - ID de la sede
 * @param {string} nuevoEstado - Nuevo estado ('activo', 'inactivo')
 */
function confirmarCambioEstado(id, nuevoEstado) {
    // Detener propagación del evento
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Personalizar mensajes según el estado
    const titulo = nuevoEstado === 'inactivo' ? '¿Inhabilitar sede?' : '¿Activar sede?';
    const mensaje = nuevoEstado === 'inactivo' 
        ? 'Esta acción desactivará la sede en el sistema. ¿Está seguro de continuar?' 
        : 'Esta acción activará la sede en el sistema. ¿Está seguro de continuar?';
    
    // Verificar si SweetAlert está disponible
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: titulo,
            text: mensaje,
            icon: nuevoEstado === 'inactivo' ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: nuevoEstado === 'inactivo' ? '#ef4444' : '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: nuevoEstado === 'inactivo' ? 'Sí, inhabilitar' : 'Sí, activar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `toggle_headquarters_status.php?id=${id}&estado=${nuevoEstado}`;
            }
        });
    } else {
        // Fallback a confirm estándar
        if (confirm(mensaje)) {
            window.location.href = `toggle_headquarters_status.php?id=${id}&estado=${nuevoEstado}`;
        }
    }
}