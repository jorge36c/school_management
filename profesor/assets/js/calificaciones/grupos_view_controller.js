/**
 * Controlador de vistas de grupos
 * Versión 2.0 - Gestión avanzada de vistas y filtros
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
    
    // Contadores de estadísticas
    const totalGruposCounter = document.getElementById('totalGruposCounter');
    
    // Botones de cambio de vista
    const gridViewBtn = document.getElementById('gridViewBtn');
    const compactViewBtn = document.getElementById('compactViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    
    // Variables de estado
    let currentView = localStorage.getItem('currentView') || 'grid'; // 'grid', 'compact', 'list'
    let filteredCards = [...gruposCards]; // Array de tarjetas filtradas
    let isAnimating = false; // Para prevenir animaciones simultáneas
    
    // Inicialización
    initializeView();
    initializeEventListeners();
    initializeSortable();
    initializeStats();
    
    /**
     * Inicializa la vista por defecto y recupera filtros guardados
     */
    function initializeView() {
        // Establecer vista guardada o predeterminada
        setView(currentView);
        
        // Comprobar filtros guardados
        const savedNivel = localStorage.getItem('filterNivel');
        const savedSede = localStorage.getItem('filterSede');
        const savedSearch = localStorage.getItem('searchGrupo');
        
        // Aplicar filtros guardados
        if (filterNivel && savedNivel) {
            filterNivel.value = savedNivel;
        }
        
        if (filterSede && savedSede) {
            filterSede.value = savedSede;
        }
        
        if (searchInput && savedSearch) {
            searchInput.value = savedSearch;
        }
        
        // Aplicar filtros iniciales con animación
        setTimeout(() => {
            applyFilters(true); // true = animación suave
        }, 300);
    }
    
    /**
     * Inicializa los listeners de eventos
     */
    function initializeEventListeners() {
        // Eventos de cambio de vista con feedback visual
        if (gridViewBtn) {
            gridViewBtn.addEventListener('click', () => {
                setView('grid');
                window.showToast('Vista de cuadrícula activada', 'info', 1500);
            });
        }
        
        if (compactViewBtn) {
            compactViewBtn.addEventListener('click', () => {
                setView('compact');
                window.showToast('Vista compacta activada', 'info', 1500);
            });
        }
        
        if (listViewBtn) {
            listViewBtn.addEventListener('click', () => {
                setView('list');
                window.showToast('Vista de lista activada', 'info', 1500);
            });
        }
        
        // Eventos de filtrado con debounce
        if (searchInput) {
            let debounceTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(handleSearch, 300);
            });
            
            // Limpiar búsqueda con Escape
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    handleSearch();
                }
            });
        }
        
        // Eventos de selección de filtros
        if (filterNivel) {
            filterNivel.addEventListener('change', handleFilter);
        }
        
        if (filterSede) {
            filterSede.addEventListener('change', handleFilter);
        }
        
        // Botón para limpiar filtros
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearFilters);
        }
        
        // Ordenación de resultados
        const sortOptions = document.querySelectorAll('.sort-option');
        sortOptions.forEach(option => {
            option.addEventListener('click', handleSort);
        });
    }
    
    /**
     * Inicializa el sistema de estadísticas
     */
    function initializeStats() {
        // Actualizar contadores
        updateStatsCounters();
        
        // Inicializar gráficos de nivel si existe el contenedor
        const nivelStatsChart = document.getElementById('nivelStatsChart');
        if (nivelStatsChart) {
            renderNivelesChart();
        }
        
        // Inicializar estadísticas de progreso
        const progressStatsChart = document.getElementById('progressStatsChart');
        if (progressStatsChart) {
            renderProgressChart();
        }
    }
    
    /**
     * Inicializa la función de arrastrar y soltar para reordenar grupos
     * Opcional según requerimientos
     */
    function initializeSortable() {
        // Verificar si se debe habilitar la ordenación personalizada
        const enableCustomOrder = localStorage.getItem('enableCustomOrder') === 'true';
        const sortableContainer = document.querySelector('.grupos-grid');
        
        if (sortableContainer && enableCustomOrder) {
            // Implementación básica para demostración
            let draggedItem = null;
            
            gruposCards.forEach(card => {
                card.setAttribute('draggable', 'true');
                
                card.addEventListener('dragstart', function() {
                    draggedItem = this;
                    setTimeout(() => {
                        this.classList.add('dragging');
                    }, 0);
                });
                
                card.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                });
                
                card.addEventListener('dragover', function(e) {
                    e.preventDefault();
                });
                
                card.addEventListener('dragenter', function(e) {
                    e.preventDefault();
                    if (this !== draggedItem) {
                        this.classList.add('drag-over');
                    }
                });
                
                card.addEventListener('dragleave', function() {
                    this.classList.remove('drag-over');
                });
                
                card.addEventListener('drop', function(e) {
                    e.preventDefault();
                    if (this !== draggedItem) {
                        const allCards = [...sortableContainer.querySelectorAll('.grupo-card')];
                        const draggedIndex = allCards.indexOf(draggedItem);
                        const thisIndex = allCards.indexOf(this);
                        
                        if (draggedIndex < thisIndex) {
                            sortableContainer.insertBefore(draggedItem, this.nextSibling);
                        } else {
                            sortableContainer.insertBefore(draggedItem, this);
                        }
                        
                        // Guardar nuevo orden
                        saveCustomOrder();
                        
                        this.classList.remove('drag-over');
                    }
                });
            });
            
            // Botón de activación de ordenación personalizada
            const toggleOrderBtn = document.getElementById('toggleCustomOrder');
            if (toggleOrderBtn) {
                toggleOrderBtn.addEventListener('click', function() {
                    const newState = !localStorage.getItem('enableCustomOrder');
                    localStorage.setItem('enableCustomOrder', newState);
                    window.location.reload(); // Recargar para aplicar cambios
                });
            }
        }
    }
    
    /**
     * Guarda el orden personalizado de tarjetas
     */
    function saveCustomOrder() {
        const container = document.querySelector('.grupos-grid');
        if (!container) return;
        
        const cardOrder = [...container.querySelectorAll('.grupo-card')].map(card => card.id);
        localStorage.setItem('gruposCardOrder', JSON.stringify(cardOrder));
    }
    
    /**
     * Aplica el orden guardado de tarjetas
     */
    function applyCustomOrder() {
        const container = document.querySelector('.grupos-grid');
        if (!container) return;
        
        const savedOrder = localStorage.getItem('gruposCardOrder');
        if (!savedOrder) return;
        
        try {
            const orderArray = JSON.parse(savedOrder);
            const fragment = document.createDocumentFragment();
            
            orderArray.forEach(id => {
                const card = document.getElementById(id);
                if (card) {
                    fragment.appendChild(card);
                }
            });
            
            // Aplicar nuevo orden
            container.innerHTML = '';
            container.appendChild(fragment);
            
        } catch (e) {
            console.error('Error al aplicar orden personalizado:', e);
        }
    }
    
    /**
     * Cambia la vista actual y actualiza la UI
     */
    function setView(viewType) {
        // Verificar elementos necesarios
        if (!gruposContainer) return;
        
        // Guardar preferencia
        currentView = viewType;
        localStorage.setItem('currentView', viewType);
        
        // Actualizar clases CSS
        gruposContainer.className = 'grupos-container';
        gruposContainer.classList.add(`view-${viewType}`);
        
        // Actualizar botones de vista
        const viewButtons = [gridViewBtn, compactViewBtn, listViewBtn];
        const viewTypes = ['grid', 'compact', 'list'];
        
        viewButtons.forEach((btn, index) => {
            if (btn) {
                if (viewTypes[index] === viewType) {
                    btn.classList.add('active');
                    btn.setAttribute('aria-pressed', 'true');
                } else {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-pressed', 'false');
                }
            }
        });
        
        // Optimizaciones específicas por tipo de vista
        switch (viewType) {
            case 'grid':
                // Vista cuadrícula: efecto de tarjetas
                gruposCards.forEach(card => {
                    card.classList.add('grid-card');
                    card.classList.remove('compact-card', 'list-card');
                });
                break;
                
            case 'compact':
                // Vista compacta: estilo minimalista
                gruposCards.forEach(card => {
                    card.classList.add('compact-card');
                    card.classList.remove('grid-card', 'list-card');
                });
                break;
                
            case 'list':
                // Vista lista: optimizada para revisar
                gruposCards.forEach(card => {
                    card.classList.add('list-card');
                    card.classList.remove('grid-card', 'compact-card');
                });
                break;
        }
        
        // Reordenar según filtros actuales
        applyFilters(true);
    }
    
    /**
     * Maneja el evento de búsqueda con algoritmo mejorado
     */
    function handleSearch() {
        if (!searchInput) return;
        
        const term = (searchInput.value || '').trim().toLowerCase();
        localStorage.setItem('searchGrupo', term);
        
        applyFilters();
    }
    
    /**
     * Maneja los cambios en filtros de nivel y sede
     */
    function handleFilter() {
        // Guardar filtros seleccionados
        if (filterNivel) {
            localStorage.setItem('filterNivel', filterNivel.value);
        }
        
        if (filterSede) {
            localStorage.setItem('filterSede', filterSede.value);
        }
        
        applyFilters();
    }
    
    /**
     * Maneja la ordenación de resultados
     */
    function handleSort(e) {
        const sortBy = e.currentTarget.getAttribute('data-sort');
        if (!sortBy) return;
        
        // Actualizar botones de ordenación
        document.querySelectorAll('.sort-option').forEach(btn => {
            btn.classList.remove('active');
        });
        e.currentTarget.classList.add('active');
        
        // Guardar preferencia
        localStorage.setItem('sortOption', sortBy);
        
        // Ordenar tarjetas
        sortCards(sortBy);
        
        // Actualizar UI con animación
        updateUIAfterFilter(true);
    }
    
    /**
     * Ordena las tarjetas según criterio seleccionado
     */
    function sortCards(sortBy) {
        if (!gruposContainer) return;
        
        // Convertir NodeList a Array para ordenar
        filteredCards = [...filteredCards];
        
        switch (sortBy) {
            case 'nivel-asc':
                filteredCards.sort((a, b) => {
                    return a.getAttribute('data-nivel').localeCompare(
                        b.getAttribute('data-nivel')
                    );
                });
                break;
                
            case 'nivel-desc':
                filteredCards.sort((a, b) => {
                    return b.getAttribute('data-nivel').localeCompare(
                        a.getAttribute('data-nivel')
                    );
                });
                break;
                
            case 'sede-asc':
                filteredCards.sort((a, b) => {
                    return a.getAttribute('data-sede').localeCompare(
                        b.getAttribute('data-sede')
                    );
                });
                break;
                
            case 'progress-desc':
                filteredCards.sort((a, b) => {
                    const progressA = a.querySelector('.progress-bar');
                    const progressB = b.querySelector('.progress-bar');
                    
                    const valueA = progressA ? parseInt(progressA.getAttribute('aria-valuenow'), 10) : 0;
                    const valueB = progressB ? parseInt(progressB.getAttribute('aria-valuenow'), 10) : 0;
                    
                    return valueB - valueA;
                });
                break;
                
            case 'progress-asc':
                filteredCards.sort((a, b) => {
                    const progressA = a.querySelector('.progress-bar');
                    const progressB = b.querySelector('.progress-bar');
                    
                    const valueA = progressA ? parseInt(progressA.getAttribute('aria-valuenow'), 10) : 0;
                    const valueB = progressB ? parseInt(progressB.getAttribute('aria-valuenow'), 10) : 0;
                    
                    return valueA - valueB;
                });
                break;
        }
    }
    
    /**
     * Limpia todos los filtros aplicados
     */
    function clearFilters() {
        // Resetear valores de filtros
        if (searchInput) searchInput.value = '';
        if (filterNivel) filterNivel.value = '';
        if (filterSede) filterSede.value = '';
        
        // Limpiar almacenamiento
        localStorage.removeItem('searchGrupo');
        localStorage.removeItem('filterNivel');
        localStorage.removeItem('filterSede');
        
        // Aplicar reseteo con animación
        applyFilters(true);
        
        // Notificación
        if (window.showToast) {
            window.showToast('Filtros eliminados', 'info');
        }
        
        // Dar foco a la búsqueda
        if (searchInput) searchInput.focus();
    }
    
    /**
     * Aplica todos los filtros seleccionados con animación opcional
     */
    function applyFilters(animate = false) {
        if (!gruposContainer || !gruposCards.length) return;
        
        // Prevenir múltiples animaciones simultáneas
        if (isAnimating) return;
        isAnimating = animate;
        
        // Obtener valores de filtro
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const nivelFilter = filterNivel ? filterNivel.value : '';
        const sedeFilter = filterSede ? filterSede.value : '';
        
        // Aplicar filtros combinados
        filteredCards = [...gruposCards].filter(card => {
            let matches = true;
            
            // Filtro por término de búsqueda
            if (searchTerm) {
                const cardText = card.textContent.toLowerCase();
                matches = cardText.includes(searchTerm);
            }
            
            // Filtro por nivel
            if (matches && nivelFilter) {
                const cardNivel = card.getAttribute('data-nivel');
                matches = cardNivel === nivelFilter;
            }
            
            // Filtro por sede
            if (matches && sedeFilter) {
                const cardSede = card.getAttribute('data-sede');
                matches = cardSede === sedeFilter;
            }
            
            return matches;
        });
        
        // Aplicar ordenación guardada
        const savedSort = localStorage.getItem('sortOption');
        if (savedSort) {
            sortCards(savedSort);
        }
        
        // Actualizar la UI
        updateUIAfterFilter(animate);
    }
    
    /**
     * Actualiza la UI después de filtrar/ordenar con animaciones
     */
    function updateUIAfterFilter(animate = false) {
        if (!gruposContainer) return;
        
        // Actualizar estado de resultados vacíos
        if (emptyResults) {
            if (filteredCards.length === 0) {
                emptyResults.style.display = 'flex';
            } else {
                emptyResults.style.display = 'none';
            }
        }
        
        // Actualizar contadores
        updateStatsCounters();
        
        // Método de actualización según animación
        if (animate) {
            animateFilterChange();
        } else {
            applyFilterResults();
        }
    }
    
    /**
     * Aplica los resultados de filtro directamente
     */
    function applyFilterResults() {
        const grid = document.querySelector('.grupos-grid');
        if (!grid) return;
        
        // Ocultar todas las tarjetas
        gruposCards.forEach(card => {
            card.style.display = 'none';
        });
        
        // Mostrar solo las filtradas en el orden correcto
        filteredCards.forEach(card => {
            card.style.display = '';
            grid.appendChild(card); // Mover al final para mantener orden
        });
        
        // Aplicar orden personalizado si está habilitado
        if (localStorage.getItem('enableCustomOrder') === 'true') {
            applyCustomOrder();
        }
        
        isAnimating = false;
    }
    
    /**
     * Anima los cambios de filtro con transiciones suaves
     */
    function animateFilterChange() {
        const grid = document.querySelector('.grupos-grid');
        if (!grid) {
            isAnimating = false;
            return;
        }
        
        // Ocultar con animación
        const cardsToHide = [...gruposCards].filter(card => !filteredCards.includes(card));
        
        // Ocultar tarjetas excluidas
        cardsToHide.forEach(card => {
            card.classList.add('animate-out');
        });
        
        // Esperar a que finalice la animación
        setTimeout(() => {
            // Ocultar tarjetas que salieron
            cardsToHide.forEach(card => {
                card.style.display = 'none';
                card.classList.remove('animate-out');
            });
            
            // Mostrar y posicionar tarjetas filtradas
            filteredCards.forEach((card, index) => {
                card.style.display = '';
                card.style.transform = 'translateY(20px)';
                card.style.opacity = '0';
                grid.appendChild(card); // Mover al final para mantener orden
                
                // Animar entrada con retraso secuencial
                setTimeout(() => {
                    card.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                    
                    // Limpiar después de animar
                    setTimeout(() => {
                        card.style.transform = '';
                        card.style.opacity = '';
                        card.style.transition = '';
                    }, 300);
                }, index * 30);
            });
            
            // Finalizar estado de animación
            setTimeout(() => {
                isAnimating = false;
            }, filteredCards.length * 30 + 300);
            
        }, cardsToHide.length ? 300 : 0);
    }
    
    /**
     * Actualiza los contadores estadísticos
     */
    function updateStatsCounters() {
        // Actualizar contador total de grupos visibles
        if (totalGruposCounter) {
            totalGruposCounter.textContent = filteredCards.length;
            
            // Destacar cambio con animación
            totalGruposCounter.classList.add('counter-updated');
            setTimeout(() => {
                totalGruposCounter.classList.remove('counter-updated');
            }, 1000);
        }
        
        // Actualizar estadísticas por niveles
        updateNivelStats();
    }
    
    /**
     * Actualiza las estadísticas por nivel
     */
    function updateNivelStats() {
        const nivelCounters = {};
        const nivelesContainer = document.getElementById('nivelesStats');
        
        if (!nivelesContainer) return;
        
        // Contar tarjetas por nivel
        filteredCards.forEach(card => {
            const nivel = card.getAttribute('data-nivel');
            if (nivel) {
                nivelCounters[nivel] = (nivelCounters[nivel] || 0) + 1;
            }
        });
        
        // Actualizar contenedor de estadísticas
        let statsHTML = '';
        Object.keys(nivelCounters).forEach(nivel => {
            statsHTML += `
                <div class="nivel-stat">
                    <span class="nivel-name">${nivel}</span>
                    <span class="nivel-count">${nivelCounters[nivel]}</span>
                </div>
            `;
        });
        
        // Si no hay estadísticas, mostrar mensaje vacío
        if (Object.keys(nivelCounters).length === 0) {
            statsHTML = '<div class="empty-stats">No hay datos para mostrar</div>';
        }
        
        nivelesContainer.innerHTML = statsHTML;
        
        // Actualizar gráficos si existen
        renderNivelesChart();
    }
    
    /**
     * Renderiza gráfico de distribución por niveles
     */
    function renderNivelesChart() {
        const chartContainer = document.getElementById('nivelStatsChart');
        if (!chartContainer) return;
        
        // Implementar visualización simple de barras
        const nivelCounters = {};
        
        // Contar por nivel
        filteredCards.forEach(card => {
            const nivel = card.getAttribute('data-nivel');
            if (nivel) {
                nivelCounters[nivel] = (nivelCounters[nivel] || 0) + 1;
            }
        });
        
        // Generar HTML del gráfico simple
        let chartHTML = '<div class="simple-chart">';
        
        Object.keys(nivelCounters).forEach(nivel => {
            const percentage = Math.round((nivelCounters[nivel] / filteredCards.length) * 100);
            const nivelColor = getNivelColor(nivel);
            
            chartHTML += `
                <div class="chart-bar-container">
                    <div class="chart-label">${nivel}</div>
                    <div class="chart-bar" style="width: ${percentage}%; background-color: ${nivelColor};">
                        <span class="chart-value">${nivelCounters[nivel]}</span>
                    </div>
                </div>
            `;
        });
        
        chartHTML += '</div>';
        chartContainer.innerHTML = chartHTML;
    }
    
    /**
     * Renderiza gráfico de progreso de calificaciones
     */
    function renderProgressChart() {
        const chartContainer = document.getElementById('progressStatsChart');
        if (!chartContainer) return;
        
        // Estadísticas de progreso
        let totalEstudiantes = 0;
        let totalCalificados = 0;
        
        // Sumar estudiantes y calificados
        filteredCards.forEach(card => {
            const progressBar = card.querySelector('.progress-bar');
            if (progressBar) {
                const progressValue = parseInt(progressBar.getAttribute('aria-valuenow'), 10);
                const statValue = card.querySelector('.stat-value');
                
                if (statValue) {
                    const countText = statValue.textContent;
                    const match = countText.match(/(\d+) de (\d+)/);
                    
                    if (match && match.length === 3) {
                        const calificados = parseInt(match[1], 10);
                        const total = parseInt(match[2], 10);
                        
                        totalCalificados += calificados;
                        totalEstudiantes += total;
                    }
                }
            }
        });
        
        // Calcular porcentaje general
        const totalPercentage = totalEstudiantes > 0 ? 
            Math.round((totalCalificados / totalEstudiantes) * 100) : 0;
        
        // Generar HTML del gráfico circular
        let progressClass = 'progress-low';
        if (totalPercentage > 60) {
            progressClass = 'progress-high';
        } else if (totalPercentage > 30) {
            progressClass = 'progress-medium';
        }
        
        chartContainer.innerHTML = `
            <div class="circular-progress ${progressClass}">
                <div class="progress-circle" style="--percentage: ${totalPercentage}">
                    <div class="progress-value">${totalPercentage}%</div>
                </div>
                <div class="progress-label">
                    <div class="progress-title">Progreso general</div>
                    <div class="progress-stats">${totalCalificados} de ${totalEstudiantes} estudiantes</div>
                </div>
            </div>
        `;
    }
    
    /**
     * Obtiene el color para un nivel educativo
     */
    function getNivelColor(nivel) {
        const colors = {
            'preescolar': 'var(--color-preescolar)',
            'primaria': 'var(--color-primaria)',
            'secundaria': 'var(--color-secundaria)',
            'media': 'var(--color-media)'
        };
        
        return colors[nivel.toLowerCase()] || 'var(--color-primary)';
    }
});
