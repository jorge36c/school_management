/**
 * Controlador para manejar el filtrado de sedes
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const sedesContainer = document.getElementById('sedesContainer');
    const sedeCards = document.querySelectorAll('.sede-card');
    const sedeRows = document.querySelectorAll('.sedes-table tbody tr');
    
    // Inicializar filtros desde localStorage
    initFiltersFromStorage();
    
    // Configurar eventos para filtros
    setupFilterEvents();
    
    /**
     * Inicializa filtros desde localStorage
     */
    function initFiltersFromStorage() {
        const savedSearch = localStorage.getItem('sedeSearchTerm') || '';
        const savedStatus = localStorage.getItem('sedeStatusFilter') || '';
        const savedType = localStorage.getItem('sedeTypeFilter') || '';
        
        // Establecer valores de filtros guardados
        if(searchInput) searchInput.value = savedSearch;
        if(statusFilter) statusFilter.value = savedStatus;
        if(typeFilter) typeFilter.value = savedType;
        
        // Aplicar filtros iniciales si hay valores guardados
        if(savedSearch || savedStatus || savedType) {
            filterSedes();
        }
    }
    
    /**
     * Configura eventos para los filtros
     */
    function setupFilterEvents() {
        if(searchInput) {
            searchInput.addEventListener('input', function() {
                localStorage.setItem('sedeSearchTerm', this.value);
                filterSedes();
            });
        }
        
        if(statusFilter) {
            statusFilter.addEventListener('change', function() {
                localStorage.setItem('sedeStatusFilter', this.value);
                filterSedes();
            });
        }
        
        if(typeFilter) {
            typeFilter.addEventListener('change', function() {
                localStorage.setItem('sedeTypeFilter', this.value);
                filterSedes();
            });
        }
    }
    
    /**
     * Filtra las sedes basado en los criterios seleccionados
     */
    function filterSedes() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const statusValue = statusFilter ? statusFilter.value : '';
        const typeValue = typeFilter ? typeFilter.value : '';
        
        let visibleCount = 0;
        
        // Filtrar tarjetas
        sedeCards.forEach(card => {
            const sedeName = card.querySelector('.sede-name').textContent.toLowerCase();
            const sedeCode = card.querySelector('.sede-code').textContent.toLowerCase();
            const addressElement = card.querySelector('.info-row:first-child .info-value');
            const sedeAddress = addressElement ? addressElement.textContent.toLowerCase() : '';
            const sedeEstado = card.dataset.estado;
            const sedeTipo = card.dataset.tipo;
            
            // Verificar si cumple todos los criterios
            const matchesSearch = !searchTerm || 
                                sedeName.includes(searchTerm) || 
                                sedeCode.includes(searchTerm) || 
                                sedeAddress.includes(searchTerm);
                                
            const matchesStatus = !statusValue || sedeEstado === statusValue;
            const matchesType = !typeValue || sedeTipo === typeValue;
            
            // Mostrar u ocultar tarjeta
            if (matchesSearch && matchesStatus && matchesType) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Filtrar filas de tabla
        sedeRows.forEach(row => {
            const sedeName = row.cells[0].textContent.toLowerCase();
            const sedeCode = row.cells[1].textContent.toLowerCase();
            const sedeAddress = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
            const sedeEstado = row.dataset.estado;
            const sedeTipo = row.dataset.tipo;
            
            // Verificar si cumple todos los criterios
            const matchesSearch = !searchTerm || 
                                sedeName.includes(searchTerm) || 
                                sedeCode.includes(searchTerm) || 
                                sedeAddress.includes(searchTerm);
                                
            const matchesStatus = !statusValue || sedeEstado === statusValue;
            const matchesType = !typeValue || sedeTipo === typeValue;
            
            // Mostrar u ocultar fila
            if (matchesSearch && matchesStatus && matchesType) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Mostrar mensaje si no hay resultados
        showNoResultsMessage(visibleCount === 0);
    }
    
    /**
     * Muestra u oculta el mensaje de no resultados
     * @param {boolean} show - Indica si se debe mostrar el mensaje
     */
    function showNoResultsMessage(show) {
        let noResultsElement = document.getElementById('noResultsMessage');
        
        if (show) {
            if (!noResultsElement) {
                noResultsElement = document.createElement('div');
                noResultsElement.id = 'noResultsMessage';
                noResultsElement.className = 'no-results';
                noResultsElement.innerHTML = `
                    <div class="no-results-icon"><i class="fas fa-search"></i></div>
                    <div class="no-results-title">No se encontraron resultados</div>
                    <div class="no-results-text">No hay sedes que coincidan con los criterios de búsqueda.</div>
                    <button class="clear-filters-btn" id="clearFiltersBtn">
                        <i class="fas fa-filter"></i> Limpiar filtros
                    </button>
                `;
                
                sedesContainer.appendChild(noResultsElement);
                
                // Evento para limpiar filtros
                document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
            }
        } else if (noResultsElement) {
            noResultsElement.remove();
        }
    }
    
    /**
     * Limpia todos los filtros aplicados
     */
    function clearFilters() {
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        if (typeFilter) typeFilter.value = '';
        
        localStorage.removeItem('sedeSearchTerm');
        localStorage.removeItem('sedeStatusFilter');
        localStorage.removeItem('sedeTypeFilter');
        
        filterSedes();
    }
});