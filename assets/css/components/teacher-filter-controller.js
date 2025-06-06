document.addEventListener('DOMContentLoaded', function() {
    // Elementos de filtro
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const sedeFilter = document.getElementById('sedeFilter');
    
    // Aplicar filtros
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const sedeValue = sedeFilter.value;
        
        // Filtrar tarjetas de profesores
        const teacherCards = document.querySelectorAll('.teacher-card');
        let visibleCards = 0;
        
        teacherCards.forEach(card => {
            const teacherName = card.querySelector('.teacher-name').textContent.toLowerCase();
            const teacherInfo = Array.from(card.querySelectorAll('.info-value')).map(el => el.textContent.toLowerCase()).join(' ');
            const teacherStatus = card.getAttribute('data-estado');
            const teacherSede = card.getAttribute('data-sede');
            
            const matchesSearch = searchTerm === '' || teacherName.includes(searchTerm) || teacherInfo.includes(searchTerm);
            const matchesStatus = statusValue === '' || teacherStatus === statusValue;
            const matchesSede = sedeValue === '0' || teacherSede === sedeValue;
            
            const isVisible = matchesSearch && matchesStatus && matchesSede;
            card.style.display = isVisible ? '' : 'none';
            
            if (isVisible) visibleCards++;
        });
        
        // Filtrar filas de tabla
        const tableRows = document.querySelectorAll('.teachers-table tbody tr');
        let visibleRows = 0;
        
        tableRows.forEach(row => {
            const rowContent = row.textContent.toLowerCase();
            const rowStatus = row.getAttribute('data-estado');
            const rowSede = row.getAttribute('data-sede');
            
            const matchesSearch = searchTerm === '' || rowContent.includes(searchTerm);
            const matchesStatus = statusValue === '' || rowStatus === statusValue;
            const matchesSede = sedeValue === '0' || rowSede === sedeValue;
            
            const isVisible = matchesSearch && matchesStatus && matchesSede;
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) visibleRows++;
        });
        
        // Mostrar mensaje de no resultados si no hay elementos visibles
        const noResultsElement = document.querySelector('.no-results');
        
        if (visibleCards === 0 && visibleRows === 0 && (teacherCards.length > 0 || tableRows.length > 0)) {
            // Crear mensaje solo si no existe
            if (!noResultsElement) {
                const containerDiv = document.createElement('div');
                containerDiv.className = 'no-results';
                
                containerDiv.innerHTML = `
                    <div class="no-results-icon"><i class="fas fa-search"></i></div>
                    <div class="no-results-title">No se encontraron profesores</div>
                    <div class="no-results-text">Intenta con otros términos de búsqueda o ajusta los filtros.</div>
                    <button class="btn-primary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </button>
                `;
                
                const teachersContainer = document.getElementById('teachersContainer');
                if (teachersContainer.firstChild) {
                    teachersContainer.insertBefore(containerDiv, teachersContainer.firstChild);
                } else {
                    teachersContainer.appendChild(containerDiv);
                }
            }
        } else if (noResultsElement) {
            // Ocultar mensaje si existen elementos visibles
            noResultsElement.remove();
        }
    }
    
    // Event listeners para filtros
    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    sedeFilter.addEventListener('change', applyFilters);
    
    // Función para limpiar filtros (expuesta globalmente)
    window.clearFilters = function() {
        searchInput.value = '';
        statusFilter.value = '';
        sedeFilter.value = '0';
        applyFilters();
    };
    
    // Aplicar filtros iniciales
    applyFilters();
    
    // Opcional: Búsqueda con botón Enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyFilters();
        }
    });
});