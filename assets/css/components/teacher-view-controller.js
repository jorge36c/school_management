document.addEventListener('DOMContentLoaded', function() {
    console.log('Teacher view controller loaded');
    
    // Botones para cambio de vista
    const gridViewBtn = document.querySelector('#gridViewBtn');
    const compactViewBtn = document.querySelector('#compactViewBtn');
    const listViewBtn = document.querySelector('#listViewBtn');
    const teachersContainer = document.querySelector('#teachersContainer');
    
    console.log('Elements:', {gridViewBtn, compactViewBtn, listViewBtn, teachersContainer});
    
    // Si alguno de los elementos no existe, salir
    if (!gridViewBtn || !compactViewBtn || !listViewBtn || !teachersContainer) {
        console.error('Missing elements for view control');
        return;
    }
    
    // Cambiar vista
    function changeView(viewType) {
        console.log('Changing view to:', viewType);
        
        // Quitar clases active de todos los botones
        gridViewBtn.classList.remove('active');
        compactViewBtn.classList.remove('active');
        listViewBtn.classList.remove('active');
        
        // Agregar clase active al botón correspondiente
        if (viewType === 'grid') {
            gridViewBtn.classList.add('active');
        } else if (viewType === 'compact') {
            compactViewBtn.classList.add('active');
        } else if (viewType === 'list') {
            listViewBtn.classList.add('active');
        }
        
        // Remover todas las clases de vista del contenedor
        teachersContainer.classList.remove('grid-view', 'compact-view', 'list-view');
        
        // Agregar la clase de vista correspondiente
        teachersContainer.classList.add(viewType + '-view');
        
        // Guardar preferencia
        localStorage.setItem('teacherViewPreference', viewType);
    }
    
    // Asignar event listeners
    gridViewBtn.addEventListener('click', function(e) {
        e.preventDefault();
        changeView('grid');
    });
    
    compactViewBtn.addEventListener('click', function(e) {
        e.preventDefault();
        changeView('compact');
    });
    
    listViewBtn.addEventListener('click', function(e) {
        e.preventDefault();
        changeView('list');
    });
    
    // Cargar vista guardada o usar grid por defecto
    const savedView = localStorage.getItem('teacherViewPreference') || 'grid';
    changeView(savedView);
});