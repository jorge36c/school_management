/**
 * Controlador de vistas de grupos
 * Maneja las diferentes vistas de grupos (cuadrícula, compacta y lista)
 * y funcionalidades de filtrado y búsqueda
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const gruposContainer = document.getElementById('gruposContainer');
    const gruposCards = document.querySelectorAll('.grupo-card');
    const gruposTable = document.querySelector('.grupos-table');
    const emptyResults = document.getElementById('emptyResults');
    const searchInput = document.getElementById('searchGrupo');
    const filterNivel = document.getElementById('filterNivel');
    const filterSede = document.getElementById('filterSede');
    const clearFiltersBtn = document.getElementById('clearFilters');
    
    // Botones de cambio de vista
    const gridViewBtn = document.getElementById('gridViewBtn');
    const compactViewBtn = document.getElementById('compactViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    
    // Variables de estado
    let currentView = 'grid'; // 'grid', 'compact', 'list'
    
    // Inicialización
    initializeView();
    initializeEventListeners();
    
    /**
     * Inicializa la vista por defecto
     */
    function initializeView() {
        // Establecer vista por defecto (cuadrícula)
        setView('grid');
        
        // Comprobar si hay filtros guardados en sessionStorage
        const savedNivel = sessionStorage.getItem('filterNivel');
        const savedSede = sessionStorage.getItem('filterSede');
        const savedSearch = sessionStorage.getItem('searchGrupo');
        const savedView = sessionStorage.getItem('currentView');
        
        // Aplicar filtros guardados si existen
        if (savedNivel) {
            filterNivel.value = savedNivel;
        }
        
        if (savedSede) {
            filterSede.value = savedSede;
        }
        
        if (savedSearch) {
            searchInput.value = savedSearch;
        }
        
        if (savedView) {
            setView(savedView);
        }
        
        // Aplicar filtros iniciales
        applyFilters();
    }
    
    /**
     * Inicializa los event listeners
     */
    function initializeEventListeners() {
        // Eventos de cambio de vista
        if (gridViewBtn) {
            gridViewBtn.addEventListener('click', () => setView('grid'));
        }
        if (compactViewBtn) {
            compactViewBtn.addEventListener('click', () => setView('compact'));
        }
        if (listViewBtn) {
            listViewBtn.addEventListener('click', () => setView('list'));
        }
        
        // Eventos de filtrado
        if (searchInput) {
            searchInput.addEventListener('input', handleSearch);
        }
        if (filterNivel) {
            filterNivel.addEventListener('change', handleFilter);
        }
        if (filterSede) {
            filterSede.addEventListener('change', handleFilter);
        }
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearFilters);
        }
        
        // Animación al hacer scroll
        window.addEventListener('scroll', handleScroll);
    }
    
    /**
     * Cambia entre las diferentes vistas disponibles
     */
    function setView(viewType) {
        // Verificar que todos los botones existen
        if (!gridViewBtn || !compactViewBtn || !listViewBtn || !gruposContainer) {
            console.warn('Algunos elementos de la interfaz no se encuentran en el DOM');
            return;
        }
        
        // Desactivar todos los botones
        gridViewBtn.classList.remove('active');
        compactViewBtn.classList.remove('active');
        listViewBtn.classList.remove('active');
        
        // Actualizar variable de estado
        currentView = viewType;
        sessionStorage.setItem('currentView', viewType);
        
        // Mostrar vista correspondiente
        switch(viewType) {
            case 'grid':
                gridViewBtn.classList.add('active');
                gruposContainer.className = 'grupos-grid animate-fade-in';
                gruposCards.forEach(card => {
                    card.classList.remove('compact');
                    card.style.display = '';
                });
                if (gruposTable) gruposTable.style.display = 'none';
                break;
                
            case 'compact':
                compactViewBtn.classList.add('active');
                gruposContainer.className = 'grupos-compact animate-fade-in';
                gruposCards.forEach(card => {
                    card.classList.add('compact');
                    card.style.display = '';
                });
                if (gruposTable) gruposTable.style.display = 'none';
                break;
                
            case 'list':
                listViewBtn.classList.add('active');
                if (gruposTable) {
                    gruposTable.style.display = 'table';
                    gruposCards.forEach(card => {
                        card.style.display = 'none';
                    });
                } else {
                    // Si no hay tabla, volver a vista de cuadrícula
                    gridViewBtn.classList.add('active');
                    gruposContainer.className = 'grupos-grid animate-fade-in';
                    gruposCards.forEach(card => {
                        card.classList.remove('compact');
                        card.style.display = '';
                    });
                }
                break;
        }
        
        // Volver a aplicar filtros para mantener la consistencia
        applyFilters();
    }
    
    /**
     * Maneja el evento de búsqueda
     */
    function handleSearch() {
        applyFilters();
        sessionStorage.setItem('searchGrupo', searchInput.value);
    }
    
    /**
     * Maneja el evento de filtrado
     */
    function handleFilter() {
        applyFilters();
        sessionStorage.setItem('filterNivel', filterNivel.value);
        sessionStorage.setItem('filterSede', filterSede.value);
    }
    
    /**
     * Aplica los filtros de búsqueda y nivel
     */
    function applyFilters() {
        if (!searchInput || !filterNivel || !filterSede) {
            console.warn('Elementos de filtrado no disponibles');
            return;
        }
        
        const searchTerm = searchInput.value.toLowerCase();
        const nivelFilter = filterNivel.value.toLowerCase();
        const sedeFilter = filterSede.value;
        
        let visibleCount = 0;
        
        // Filtrar tarjetas
        gruposCards.forEach(card => {
            // Verificar que los atributos de datos existan
            const cardNivel = card.dataset.nivel ? card.dataset.nivel.toLowerCase() : '';
            const cardSede = card.dataset.sede || '';
            
            // Buscar en contenido de texto
            let cardTitle = '';
            let cardMateria = '';
            
            const titleElement = card.querySelector('h3');
            if (titleElement) {
                cardTitle = titleElement.textContent.toLowerCase();
            }
            
            const materiaElement = card.querySelector('.materia');
            if (materiaElement) {
                cardMateria = materiaElement.textContent.toLowerCase();
            }
            
            const matchesSearch = searchTerm === '' || 
                cardTitle.includes(searchTerm) || 
                cardMateria.includes(searchTerm);
                
            const matchesNivel = nivelFilter === '' || cardNivel === nivelFilter;
            const matchesSede = sedeFilter === '' || cardSede === sedeFilter;
            
            if (matchesSearch && matchesNivel && matchesSede) {
                if (currentView !== 'list') {
                    card.style.display = '';
                }
                visibleCount++;
            } else {
                if (currentView !== 'list') {
                    card.style.display = 'none';
                }
            }
        });
        
        // Filtrar filas de tabla si está visible
        if (gruposTable) {
            const tableRows = gruposTable.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                let rowNivel = '';
                let rowGrado = '';
                let rowMateria = '';
                let rowSede = '';
                
                // Seleccionar contenido de las celdas con seguridad
                if (row.cells[0]) {
                    const nivelElement = row.cells[0].querySelector('.nivel-pill');
                    if (nivelElement && nivelElement.parentElement) {
                        rowNivel = nivelElement.parentElement.textContent.toLowerCase();
                    }
                    rowGrado = row.cells[0].textContent.toLowerCase();
                }
                
                if (row.cells[1]) {
                    rowMateria = row.cells[1].textContent.toLowerCase();
                }
                
                if (row.cells[2]) {
                    rowSede = row.cells[2].textContent;
                }
                
                const matchesSearch = searchTerm === '' || 
                    rowGrado.includes(searchTerm) || 
                    rowMateria.includes(searchTerm);
                    
                const matchesNivel = nivelFilter === '' || rowNivel.includes(nivelFilter);
                const matchesSede = sedeFilter === '' || rowSede === sedeFilter;
                
                if (matchesSearch && matchesNivel && matchesSede) {
                    if (currentView === 'list') {
                        row.style.display = '';
                    }
                    // No incrementamos visibleCount aquí ya que contamos por tarjetas
                } else {
                    if (currentView === 'list') {
                        row.style.display = 'none';
                    }
                }
            });
        }
        
        // Mostrar mensaje de no resultados si es necesario
        if (emptyResults) {
            if (visibleCount === 0) {
                emptyResults.style.display = 'block';
                if (gruposContainer) gruposContainer.style.display = 'none';
                if (gruposTable) gruposTable.style.display = 'none';
            } else {
                emptyResults.style.display = 'none';
                if (gruposContainer) gruposContainer.style.display = '';
                if (currentView === 'list' && gruposTable) {
                    gruposTable.style.display = 'table';
                }
            }
        }
    }
    
    /**
     * Limpia todos los filtros aplicados
     */
    function clearFilters() {
        if (searchInput) searchInput.value = '';
        if (filterNivel) filterNivel.value = '';
        if (filterSede) filterSede.value = '';
        
        // Limpiar sessionStorage
        sessionStorage.removeItem('searchGrupo');
        sessionStorage.removeItem('filterNivel');
        sessionStorage.removeItem('filterSede');
        
        // Aplicar filtros (mostrará todos los elementos)
        applyFilters();
    }
    
    /**
     * Maneja la animación al hacer scroll
     */
    function handleScroll() {
        const scrollY = window.scrollY;
        const viewportHeight = window.innerHeight;
        
        document.querySelectorAll('.animate-on-scroll').forEach(element => {
            const elementTop = element.getBoundingClientRect().top + scrollY;
            
            if (scrollY > elementTop - viewportHeight + 100) {
                element.classList.add('animated');
            }
        });
    }
});