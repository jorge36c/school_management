Utils.log('success', 'Elementos DOM inicializados');
            return true;
            
        } catch (error) {
            Utils.handleError(error, 'DOMManager.init');
            return false;
        }
    }
    
    getElement(key) {
        return this.elements[key] || null;
    }
    
    validateCriticalElements() {
        const critical = ['gruposContainer', 'togglePeriodos'];
        const missing = critical.filter(key => !this.elements[key]);
        
        if (missing.length > 0) {
            Utils.log('error', 'Elementos críticos faltantes', missing);
            return false;
        }
        
        return true;
    }
}

// =====================================================================================
// 7. GESTIÓN DE PERÍODOS ACADÉMICOS
// =====================================================================================

class PeriodoManager {
    constructor(domManager) {
        this.dom = domManager;
        this.isOpen = false;
    }
    
    init() {
        const toggleBtn = this.dom.getElement('togglePeriodos');
        const dropdown = this.dom.getElement('periodosDropdown');
        
        if (!toggleBtn || !dropdown) {
            Utils.log('warn', 'Elementos del selector de períodos no encontrados');
            return false;
        }
        
        this.setupEventListeners();
        Utils.log('success', 'Selector de períodos inicializado');
        return true;
    }
    
    setupEventListeners() {
        const toggleBtn = this.dom.getElement('togglePeriodos');
        const dropdown = this.dom.getElement('periodosDropdown');
        
        // Toggle principal
        toggleBtn.addEventListener('click', this.handleToggle.bind(this));
        toggleBtn.addEventListener('keydown', this.handleToggleKeydown.bind(this));
        
        // Items del dropdown
        const items = dropdown.querySelectorAll('.periodo-item');
        items.forEach((item, index) => {
            item.setAttribute('data-index', index);
            item.addEventListener('click', this.handleItemClick.bind(this));
            item.addEventListener('keydown', this.handleItemKeydown.bind(this));
        });
        
        // Cerrar al hacer clic fuera
        document.addEventListener('click', this.handleClickOutside.bind(this));
        
        // Escape para cerrar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    handleToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    async open() {
        if (this.isOpen) return;
        
        const toggleBtn = this.dom.getElement('togglePeriodos');
        const dropdown = this.dom.getElement('periodosDropdown');
        
        this.isOpen = true;
        appState.setState({ periodoDropdownOpen: true });
        
        // Actualizar ARIA
        toggleBtn.setAttribute('aria-expanded', 'true');
        
        // Mostrar dropdown
        dropdown.classList.add(CONFIG.CLASSES.show);
        
        // Animar icono
        const chevron = toggleBtn.querySelector('.fa-chevron-down');
        if (chevron) {
            chevron.style.transform = 'rotate(180deg)';
        }
        
        // Enfocar primer elemento
        await Utils.delay(CONFIG.ANIMATION_DURATION);
        const firstItem = dropdown.querySelector('.periodo-item');
        if (firstItem) {
            firstItem.focus();
        }
        
        Utils.log('info', 'Dropdown de períodos abierto');
    }
    
    close() {
        if (!this.isOpen) return;
        
        const toggleBtn = this.dom.getElement('togglePeriodos');
        const dropdown = this.dom.getElement('periodosDropdown');
        
        this.isOpen = false;
        appState.setState({ periodoDropdownOpen: false });
        
        // Actualizar ARIA
        toggleBtn.setAttribute('aria-expanded', 'false');
        
        // Ocultar dropdown
        dropdown.classList.remove(CONFIG.CLASSES.show);
        
        // Restaurar icono
        const chevron = toggleBtn.querySelector('.fa-chevron-down');
        if (chevron) {
            chevron.style.transform = '';
        }
        
        Utils.log('info', 'Dropdown de períodos cerrado');
    }
    
    handleToggleKeydown(e) {
        switch (e.key) {
            case 'Enter':
            case ' ':
                e.preventDefault();
                this.handleToggle(e);
                break;
                
            case 'ArrowDown':
                e.preventDefault();
                if (!this.isOpen) {
                    this.open();
                } else {
                    const first = this.dom.getElement('periodosDropdown').querySelector('.periodo-item');
                    if (first) first.focus();
                }
                break;
                
            case 'Escape':
                this.close();
                break;
        }
    }
    
    async handleItemClick(e) {
        e.preventDefault();
        
        const periodoId = e.target.dataset.periodoId || 
                         e.target.closest('.periodo-item')?.dataset.periodoId;
        
        if (!periodoId) {
            Utils.log('warn', 'ID de período no encontrado');
            return;
        }
        
        await this.selectPeriodo(periodoId);
    }
    
    handleItemKeydown(e) {
        const dropdown = this.dom.getElement('periodosDropdown');
        const items = dropdown.querySelectorAll('.periodo-item');
        const currentIndex = parseInt(e.target.dataset.index);
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex]?.focus();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                items[prevIndex]?.focus();
                break;
                
            case 'Enter':
            case ' ':
                e.preventDefault();
                this.handleItemClick(e);
                break;
                
            case 'Escape':
                this.close();
                this.dom.getElement('togglePeriodos')?.focus();
                break;
                
            case 'Home':
                e.preventDefault();
                items[0]?.focus();
                break;
                
            case 'End':
                e.preventDefault();
                items[items.length - 1]?.focus();
                break;
        }
    }
    
    handleClickOutside(e) {
        const toggleBtn = this.dom.getElement('togglePeriodos');
        const dropdown = this.dom.getElement('periodosDropdown');
        
        if (!this.isOpen) return;
        
        if (!toggleBtn?.contains(e.target) && !dropdown?.contains(e.target)) {
            this.close();
        }
    }
    
    async selectPeriodo(periodoId) {
        try {
            loadingManager.show('Cambiando período académico...');
            
            // Construir nueva URL
            const url = new URL(window.location);
            url.searchParams.set('periodo_id', periodoId);
            
            // Guardar estado
            this.saveCurrentState();
            
            // Delay mínimo para UX
            await Utils.delay(CONFIG.LOADING_MIN_TIME);
            
            // Disparar evento personalizado
            document.dispatchEvent(new CustomEvent(CONFIG.EVENTS.periodChanged, {
                detail: { periodoId }
            }));
            
            // Navegar
            window.location.href = url.toString();
            
        } catch (error) {
            loadingManager.hide();
            Utils.handleError(error, 'PeriodoManager.selectPeriodo');
            toastManager.show('Error al cambiar período', 'error');
        }
    }
    
    saveCurrentState() {
        const state = {
            currentView: appState.getState('currentView'),
            filtros: appState.getState('filtros'),
            timestamp: Date.now()
        };
        
        try {
            sessionStorage.setItem('periodo_change_state', JSON.stringify(state));
        } catch (error) {
            Utils.log('warn', 'No se pudo guardar el estado antes del cambio de período');
        }
    }
}

// =====================================================================================
// 8. GESTIÓN DE GRUPOS Y FILTROS
// =====================================================================================

class GruposManager {
    constructor(domManager) {
        this.dom = domManager;
        this.grupos = [];
        this.gruposFiltrados = [];
    }
    
    init() {
        this.loadGrupos();
        this.setupFilters();
        this.setupViews();
        this.initializeFiltersFromURL();
        
        // Suscribirse a cambios de filtros
        appState.subscribe('filtros', this.onFiltrosChanged.bind(this));
        
        Utils.log('success', 'Gestión de grupos inicializada');
        return true;
    }
    
    loadGrupos() {
        const grupoCards = this.dom.getElement('grupoCards');
        
        this.grupos = Array.from(grupoCards).map((card, index) => {
            return {
                id: card.id || `grupo-${index}`,
                elemento: card,
                nivel: Utils.getDataAttribute(card, 'nivel'),
                grado: Utils.getDataAttribute(card, 'grado'),
                materia: Utils.getDataAttribute(card, 'materia'),
                sede: Utils.getDataAttribute(card, 'sede'),
                totalEstudiantes: parseInt(Utils.getDataAttribute(card, 'total-estudiantes')) || 0,
                estudiantesCalificados: parseInt(Utils.getDataAttribute(card, 'estudiantes-calificados')) || 0,
                porcentaje: parseInt(Utils.getDataAttribute(card, 'porcentaje')) || 0,
                estado: Utils.getDataAttribute(card, 'estado'),
                href: Utils.getDataAttribute(card, 'href') || '#',
                visible: true,
                searchText: this.generateSearchText(card)
            };
        });
        
        this.gruposFiltrados = [...this.grupos];
        
        // Actualizar estado
        appState.setState({ 
            grupos: this.grupos,
            gruposFiltrados: this.gruposFiltrados
        });
        
        Utils.log('info', `Cargados ${this.grupos.length} grupos`);
    }
    
    generateSearchText(card) {
        const nivel = Utils.getDataAttribute(card, 'nivel');
        const grado = Utils.getDataAttribute(card, 'grado');
        const materia = Utils.getDataAttribute(card, 'materia');
        const sede = Utils.getDataAttribute(card, 'sede');
        
        return Utils.sanitizeText(`${nivel} ${grado} ${materia} ${sede}`);
    }
    
    setupFilters() {
        const searchInput = this.dom.getElement('searchGrupo');
        const filterNivel = this.dom.getElement('filterNivel');
        const filterGrado = this.dom.getElement('filterGrado');
        const filterEstado = this.dom.getElement('filterEstado');
        const clearFilters = this.dom.getElement('clearFilters');
        
        // Búsqueda con debounce
        if (searchInput) {
            const debouncedSearch = Utils.debounce((value) => {
                this.updateFilter('busqueda', Utils.sanitizeText(value));
            }, CONFIG.DEBOUNCE_DELAY);
            
            searchInput.addEventListener('input', (e) => debouncedSearch(e.target.value));
            searchInput.addEventListener('clear', () => debouncedSearch(''));
        }
        
        // Filtros por categoría
        if (filterNivel) {
            filterNivel.addEventListener('change', (e) => {
                this.updateFilter('nivel', e.target.value);
            });
        }
        
        if (filterGrado) {
            filterGrado.addEventListener('change', (e) => {
                this.updateFilter('grado', e.target.value);
            });
        }
        
        if (filterEstado) {
            filterEstado.addEventListener('change', (e) => {
                this.updateFilter('estado', e.target.value);
            });
        }
        
        // Limpiar filtros
        if (clearFilters) {
            clearFilters.addEventListener('click', this.clearFilters.bind(this));
        }
        
        const clearFiltersEmpty = document.getElementById('clearFiltersEmpty');
        if (clearFiltersEmpty) {
            clearFiltersEmpty.addEventListener('click', this.clearFilters.bind(this));
        }
    }
    
    updateFilter(key, value) {
        const currentFiltros = appState.getState('filtros');
        const newFiltros = { ...currentFiltros, [key]: value };
        
        appState.setState({ filtros: newFiltros });
    }
    
    onFiltrosChanged(newFiltros) {
        this.aplicarFiltros(newFiltros);
    }
    
    aplicarFiltros(filtros = null) {
        const filtrosActuales = filtros || appState.getState('filtros');
        const { busqueda, nivel, grado, estado } = filtrosActuales;
        
        this.gruposFiltrados = this.grupos.filter(grupo => {
            // Filtro por búsqueda
            if (busqueda && !grupo.searchText.includes(busqueda)) {
                return false;
            }
            
            // Filtro por nivel
            if (nivel && grupo.nivel !== nivel) {
                return false;
            }
            
            // Filtro por grado
            if (grado && grupo.grado !== grado) {
                return false;
            }
            
            // Filtro por estado
            if (estado && grupo.estado !== estado) {
                return false;
            }
            
            return true;
        });
        
        this.updateVisibility();
        this.updateEmptyState();
        this.saveFiltersToURL();
        
        // Disparar evento personalizado
        document.dispatchEvent(new CustomEvent(CONFIG.EVENTS.filtersApplied, {
            detail: { 
                filters: filtrosActuales, 
                resultCount: this.gruposFiltrados.length 
            }
        }));
        
        Utils.log('info', `Filtros aplicados: ${this.gruposFiltrados.length}/${this.grupos.length} grupos`);
    }
    
    updateVisibility() {
        this.grupos.forEach((grupo, index) => {
            const shouldShow = this.gruposFiltrados.includes(grupo);
            grupo.visible = shouldShow;
            
            if (shouldShow) {
                grupo.elemento.style.display = '';
                // Animación escalonada
                setTimeout(() => {
                    grupo.elemento.classList.add(CONFIG.CLASSES.fadeIn);
                }, index * 50);
            } else {
                grupo.elemento.style.display = 'none';
                grupo.elemento.classList.remove(CONFIG.CLASSES.fadeIn);
            }
        });
    }
    
    updateEmptyState() {
        const emptyResults = this.dom.getElement('emptyResults');
        const gruposContainer = this.dom.getElement('gruposContainer');
        
        if (!emptyResults || !gruposContainer) return;
        
        const filtrosActuales = appState.getState('filtros');
        const hasActiveFilters = Object.values(filtrosActuales).some(value => value !== '');
        const hasResults = this.gruposFiltrados.length > 0;
        
        if (!hasResults && hasActiveFilters) {
            emptyResults.style.display = 'block';
            gruposContainer.style.opacity = '0.5';
        } else {
            emptyResults.style.display = 'none';
            gruposContainer.style.opacity = '';
        }
    }
    
    clearFilters() {
        // Resetear filtros
        const filtrosVacios = {
            busqueda: '',
            nivel: '',
            grado: '',
            estado: ''
        };
        
        appState.setState({ filtros: filtrosVacios });
        
        // Limpiar campos UI
        const searchInput = this.dom.getElement('searchGrupo');
        const filterNivel = this.dom.getElement('filterNivel');
        const filterGrado = this.dom.getElement('filterGrado');
        const filterEstado = this.dom.getElement('filterEstado');
        
        if (searchInput) searchInput.value = '';
        if (filterNivel) filterNivel.value = '';
        if (filterGrado) filterGrado.value = '';
        if (filterEstado) filterEstado.value = '';
        
        // Enfocar búsqueda
        if (searchInput) searchInput.focus();
        
        toastManager.show('Filtros limpiados', 'info');
        Utils.log('info', 'Filtros limpiados por el usuario');
    }
    
    initializeFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const filtrosIniciales = { ...appState.getState('filtros') };
        
        // Restaurar desde URL
        if (urlParams.has('search')) {
            const searchValue = urlParams.get('search');
            filtrosIniciales.busqueda = Utils.sanitizeText(searchValue);
            const searchInput = this.dom.getElement('searchGrupo');
            if (searchInput) searchInput.value = searchValue;
        }
        
        ['nivel', 'grado', 'estado'].forEach(key => {
            if (urlParams.has(key)) {
                const value = urlParams.get(key);
                filtrosIniciales[key] = value;
                const filterElement = this.dom.getElement(`filter${key.charAt(0).toUpperCase() + key.slice(1)}`);
                if (filterElement) filterElement.value = value;
            }
        });
        
        // Aplicar filtros si hay alguno activo
        if (Object.values(filtrosIniciales).some(value => value !== '')) {
            appState.setState({ filtros: filtrosIniciales });
            Utils.log('info', 'Filtros inicializados desde URL');
        }
    }
    
    saveFiltersToURL() {
        const url = new URL(window.location);
        const filtros = appState.getState('filtros');
        
        // Limpiar parámetros existentes
        ['search', 'nivel', 'grado', 'estado'].forEach(param => {
            url.searchParams.delete(param);
        });
        
        // Añadir filtros activos
        if (filtros.busqueda) url.searchParams.set('search', filtros.busqueda);
        if (filtros.nivel) url.searchParams.set('nivel', filtros.nivel);
        if (filtros.grado) url.searchParams.set('grado', filtros.grado);
        if (filtros.estado) url.searchParams.set('estado', filtros.estado);
        
        // Actualizar URL sin recargar
        window.history.replaceState(null, '', url.toString());
    }
    
    setupViews() {
        const viewButtons = this.dom.getElement('viewButtons');
        
        viewButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const vista = e.currentTarget.dataset.view;
                if (vista) {
                    this.changeView(vista);
                }
            });
        });
        
        // Establecer vista inicial
        this.changeView(appState.getState('currentView'));
        
        // Suscribirse a cambios de vista
        appState.subscribe('currentView', this.onViewChanged.bind(this));
    }
    
    async changeView(newView) {
        if (!['cards', 'compact', 'list'].includes(newView)) {
            Utils.log('warn', `Vista inválida: ${newView}`);
            return;
        }
        
        const currentView = appState.getState('currentView');
        if (currentView === newView) return;
        
        const gruposContainer = this.dom.getElement('gruposContainer');
        if (!gruposContainer) return;
        
        try {
            // Transición suave
            gruposContainer.style.opacity = '0.7';
            
            // Remover clases anteriores
            gruposContainer.classList.remove('view-cards', 'view-compact', 'view-list');
            
            await Utils.delay(100);
            
            // Añadir nueva clase
            gruposContainer.classList.add(`view-${newView}`);
            
            // Actualizar estado
            appState.setState({ currentView: newView });
            
            // Actualizar botones
            this.updateViewButtons(newView);
            
            // Restaurar opacidad
            gruposContainer.style.opacity = '';
            
            // Guardar preferencia
            localStorage.setItem('calificaciones_view', newView);
            
            Utils.log('success', `Vista cambiada a: ${newView}`);
            toastManager.show(`Vista ${this.getViewDisplayName(newView)} activada`, 'info');
            
        } catch (error) {
            gruposContainer.style.opacity = '';
            Utils.handleError(error, 'GruposManager.changeView');
        }
    }
    
    onViewChanged(newView) {
        this.updateViewButtons(newView);
        
        // Disparar evento personalizado
        document.dispatchEvent(new CustomEvent(CONFIG.EVENTS.viewChanged, {
            detail: { view: newView }
        }));
    }
    
    updateViewButtons(activeView) {
        const viewButtons = this.dom.getElement('viewButtons');
        
        viewButtons.forEach(btn => {
            btn.classList.remove(CONFIG.CLASSES.active);
            if (btn.dataset.view === activeView) {
                btn.classList.add(CONFIG.CLASSES.active);
            }
        });
    }
    
    getViewDisplayName(view) {
        const names = {
            'cards': 'Tarjetas',
            'compact': 'Compacta',
            'list': 'Lista'
        };
        return names[view] || view;
    }
}

// =====================================================================================
// 9. GESTIÓN DE NAVEGACIÓN
// =====================================================================================

class NavigationManager {
    constructor(domManager, gruposManager) {
        this.dom = domManager;
        this.grupos = gruposManager;
    }
    
    init() {
        this.setupGroupNavigation();
        this.setupKeyboardShortcuts();
        
        Utils.log('success', 'Navegación inicializada');
        return true;
    }
    
    setupGroupNavigation() {
        const grupos = appState.getState('grupos');
        
        grupos.forEach(grupo => {
            const card = grupo.elemento;
            
            // Click en tarjeta
            card.addEventListener('click', (e) => this.handleGroupClick(e, grupo));
            
            // Teclado en tarjeta
            card.addEventListener('keydown', (e) => this.handleGroupKeydown(e, grupo));
            
            // Efectos hover mejorados
            this.setupHoverEffects(card);
        });
    }
    
    handleGroupClick(e, grupo) {
        // Evitar navegación si se hace clic en botones o enlaces
        if (e.target.tagName === 'BUTTON' || 
            e.target.closest('button') || 
            e.target.closest('.btn-action') ||
            e.target.closest('a') ||
            e.target.closest('.dropdown-menu')) {
            return;
        }
        
        this.navigateToGroup(grupo);
    }
    
    handleGroupKeydown(e, grupo) {
        switch (e.key) {
            case 'Enter':
            case ' ':
                e.preventDefault();
                this.navigateToGroup(grupo);
                break;
                
            case 'ArrowRight':
            case 'ArrowDown':
                e.preventDefault();
                this.focusNextGroup(grupo);
                break;
                
            case 'ArrowLeft':
            case 'ArrowUp':
                e.preventDefault();
                this.focusPreviousGroup(grupo);
                break;
        }
    }
    
    setupHoverEffects(card) {
        let hoverTimeout;
        
        card.addEventListener('mouseenter', () => {
            clearTimeout(hoverTimeout);
            this.applyHoverTransform(card);
        });
        
        card.addEventListener('mouseleave', () => {
            hoverTimeout = setTimeout(() => {
                card.style.transform = '';
            }, 150);
        });
    }
    
    applyHoverTransform(card) {
        const currentView = appState.getState('currentView');
        const transforms = {
            'cards': 'translateY(-4px)',
            'compact': 'translateY(-2px)',
            'list': 'translateX(6px)'
        };
        
        card.style.transform = transforms[currentView] || '';
    }
    
    async navigateToGroup(grupo) {
        if (!grupo.href || grupo.href === '#') {
            toastManager.show('Enlace no disponible', 'warning');
            return;
        }
        
        try {
            loadingManager.show('Cargando grupo...');
            
            // Guardar estado de navegación
            this.saveNavigationState();
            
            await Utils.delay(CONFIG.LOADING_MIN_TIME);
            
            // Navegar
            window.location.href = grupo.href;
            
        } catch (error) {
            loadingManager.hide();
            Utils.handleError(error, 'NavigationManager.navigateToGroup');
            toastManager.show('Error al cargar el grupo', 'error');
        }
    }
    
    focusNextGroup(currentGroup) {
        const visibleGroups = appState.getState('gruposFiltrados');
        const currentIndex = visibleGroups.findIndex(g => g.id === currentGroup.id);
        const nextIndex = (currentIndex + 1) % visibleGroups.length;
        
        if (visibleGroups[nextIndex]) {
            visibleGroups[nextIndex].elemento.focus();
        }
    }
    
    focusPreviousGroup(currentGroup) {
        const visibleGroups = appState.getState('gruposFiltrados');
        const currentIndex = visibleGroups.findIndex(g => g.id === currentGroup.id);
        const prevIndex = currentIndex > 0 ? currentIndex - 1 : visibleGroups.length - 1;
        
        if (visibleGroups[prevIndex]) {
            visibleGroups[prevIndex].elemento.focus();
        }
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Solo procesar si no estamos en un input
            if (e.target.tagName === 'INPUT' || 
                e.target.tagName === 'SELECT' || 
                e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            // Atajos con Ctrl
            if (e.ctrlKey) {
                switch (e.key) {
                    case '1':
                        e.preventDefault();
                        this.grupos.changeView('cards');
                        break;
                    case '2':
                        e.preventDefault();
                        this.grupos.changeView('compact');
                        break;
                    case '3':
                        e.preventDefault();
                        this.grupos.changeView('list');
                        break;
                    case 'f':
                    case 'F':
                        e.preventDefault();
                        this.dom.getElement('searchGrupo')?.focus();
                        break;
                    case 'h':
                    case 'H':
                        e.preventDefault();
                        this.showHelp();
                        break;
                }
            }
            
            // Atajos sin modificadores
            switch (e.key) {
                case 'Escape':
                    if (appState.getState('periodoDropdownOpen')) {
                        // Se maneja en PeriodoManager
                    } else {
                        this.grupos.clearFilters();
                    }
                    break;
                    
                case '/':
                    e.preventDefault();
                    this.dom.getElement('searchGrupo')?.focus();
                    break;
                    
                case 'F1':
                    e.preventDefault();
                    this.showHelp();
                    break;
            }
        });
    }
    
    showHelp() {
        toastManager.show('Consulta la consola para ver todos los atajos disponibles', 'info', 6000);
        
        console.group('📋 Atajos de Teclado Disponibles');
        console.log('Ctrl + 1/2/3: Cambiar vista (Tarjetas/Compacta/Lista)');
        console.log('Ctrl + F: Buscar');
        console.log('/: Buscar (alternativo)');
        console.log('Escape: Limpiar filtros o cerrar dropdown');
        console.log('F1 o Ctrl + H: Mostrar esta ayuda');
        console.log('Flechas: Navegar entre grupos');
        console.log('Enter/Espacio: Seleccionar grupo');
        console.groupEnd();
    }
    
    saveNavigationState() {
        const state = {
            view: appState.getState('currentView'),
            filters: appState.getState('filtros'),
            scrollPosition: window.scrollY,
            timestamp: Date.now()
        };
        
        try {
            sessionStorage.setItem('navigation_state', JSON.stringify(state));
        } catch (error) {
            Utils.log('warn', 'No se pudo guardar el estado de navegación');
        }
    }
}

// =====================================================================================
// 10. APLICACIÓN PRINCIPAL
// =====================================================================================

class CalificacionesApp {
    constructor() {
        this.initialized = false;
        this.components = {};
    }
    
    async init() {
        try {
            Utils.log('info', '🎓 Iniciando Sistema de Calificaciones Optimizado v4.0...');
            
            // Verificar requisitos del navegador
            if (!this.checkBrowserSupport()) {
                throw new Error('Navegador no compatible');
            }
            
            // Inicializar componentes
            await this.initializeComponents();
            
            // Configurar eventos globales
            this.setupGlobalEvents();
            
            // Marcar como inicializado
            this.initialized = true;
            document.documentElement.classList.add('calificaciones-ready');
            
            // Mostrar mensajes de bienvenida
            this.showWelcomeMessages();
            
            // Disparar evento personalizado
            document.dispatchEvent(new CustomEvent(CONFIG.EVENTS.ready));
            
            Utils.log('success', '✅ Sistema inicializado correctamente');
            toastManager.show('Sistema de calificaciones cargado correctamente', 'success');
            
            return true;
            
        } catch (error) {
            Utils.handleError(error, 'CalificacionesApp.init');
            this.showFallbackInterface();
            return false;
        }
    }
    
    async initializeComponents() {
        const initSteps = [
            { name: 'DOM', component: 'domManager', class: DOMManager },
            { name: 'Períodos', component: 'periodoManager', class: PeriodoManager, deps: ['domManager'] },
            { name: 'Grupos', component: 'gruposManager', class: GruposManager, deps: ['domManager'] },
            { name: 'Navegación', component: 'navigationManager', class: NavigationManager, deps: ['domManager', 'gruposManager'] }
        ];
        
        for (const step of initSteps) {
            Utils.log('info', `Inicializando ${step.name}...`);
            
            try {
                // Crear instancia con dependencias
                const deps = step.deps ? step.deps.map(dep => this.components[dep]) : [];
                this.components[step.component] = new step.class(...deps);
                
                // Inicializar componente
                const result = await this.components[step.component].init();
                
                if (result === false) {
                    Utils.log('warn', `${step.name} falló pero continuamos`);
                }
                
            } catch (error) {
                Utils.handleError(error, `${step.name} Initialization`);
                throw new Error(`Error inicializando ${step.name}`);
            }
        }
    }
    
    checkBrowserSupport() {
        const required = [
            'querySelector',
            'addEventListener',
            'classList',
            'JSON',
            'localStorage',
            'Promise',
            'fetch'
        ];
        
        const missing = required.filter(feature => {
            if (feature === 'fetch') {
                return !window.fetch;
            }
            return !(feature in window) && !(feature in document);
        });
        
        if (missing.length > 0) {
            Utils.log('error', 'Características del navegador faltantes', missing);
            return false;
        }
        
        return true;
    }
    
    setupGlobalEvents() {
        // Guardar estado antes de salir
        window.addEventListener('beforeunload', () => {
            this.saveAppState();
        });
        
        // Manejar errores globales
        window.addEventListener('error', (e) => {
            Utils.handleError(e.error || new Error(e.message), 'Global Error Handler');
        });
        
        // Manejar promesas rechazadas
        window.addEventListener('unhandledrejection', (e) => {
            Utils.handleError(new Error(e.reason), 'Unhandled Promise Rejection');
            e.preventDefault(); // Prevenir logging por defecto
        });
        
        // Detectar cambios de conectividad
        window.addEventListener('online', () => {
            toastManager.show('Conexión restaurada', 'success');
        });
        
        window.addEventListener('offline', () => {
            toastManager.show('Sin conexión a internet', 'warning');
        });
        
        // Manejar cambios de tamaño de ventana
        const handleResize = Utils.throttle(() => {
            this.handleResize();
        }, 250);
        
        window.addEventListener('resize', handleResize);
        
        // Manejar cambios de visibilidad de página
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.handlePageHidden();
            } else {
                this.handlePageVisible();
            }
        });
    }
    
    handleResize() {
        // Cerrar dropdown en resize
        if (appState.getState('periodoDropdownOpen')) {
            this.components.periodoManager?.close();
        }
        
        // Ajustar para dispositivos móviles
        const isMobile = window.innerWidth < 768;
        document.body.classList.toggle('mobile-device', isMobile);
        
        Utils.log('info', 'Ventana redimensionada', { width: window.innerWidth, isMobile });
    }
    
    handlePageHidden() {
        // Pausar animaciones costosas cuando la página no es visible
        document.documentElement.classList.add('page-hidden');
        this.saveAppState();
    }
    
    handlePageVisible() {
        // Reanudar funcionalidad cuando la página vuelve a ser visible
        document.documentElement.classList.remove('page-hidden');
        
        // Verificar si necesitamos refrescar datos
        const lastUpdate = appState.getCache('last_data_update');
        if (!lastUpdate || Date.now() - lastUpdate > CONFIG.CACHE_DURATION) {
            this.refreshData();
        }
    }
    
    async refreshData() {
        try {
            // Aquí se podría implementar una actualización de datos en segundo plano
            appState.setCache('last_data_update', Date.now());
            Utils.log('info', 'Datos actualizados en segundo plano');
        } catch (error) {
            Utils.handleError(error, 'Data Refresh');
        }
    }
    
    showWelcomeMessages() {
        // Mostrar mensaje de primera visita
        const isFirstVisit = !sessionStorage.getItem('hasVisitedCalificaciones');
        if (isFirstVisit) {
            sessionStorage.setItem('hasVisitedCalificaciones', 'true');
            
            setTimeout(() => {
                toastManager.show(
                    '¡Bienvenido! Presiona F1 para ver los atajos disponibles',
                    'info',
                    6000
                );
            }, 1500);
        }
        
        // Mostrar mensaje de cambio de período si corresponde
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('periodo_id')) {
            setTimeout(() => {
                toastManager.show('Período académico actualizado', 'success');
            }, 800);
        }
        
        // Restaurar estado de sesión anterior si existe
        this.restoreSessionState();
    }
    
    restoreSessionState() {
        try {
            const savedState = sessionStorage.getItem('periodo_change_state');
            if (savedState) {
                const state = JSON.parse(savedState);
                
                // Verificar que no sea muy antiguo (5 minutos)
                if (Date.now() - state.timestamp < 5 * 60 * 1000) {
                    // Restaurar vista
                    if (state.view && this.components.gruposManager) {
                        setTimeout(() => {
                            this.components.gruposManager.changeView(state.view);
                        }, 500);
                    }
                    
                    Utils.log('info', 'Estado de sesión restaurado');
                }
                
                // Limpiar estado usado
                sessionStorage.removeItem('periodo_change_state');
            }
        } catch (error) {
            Utils.log('warn', 'Error al restaurar estado de sesión', error);
        }
    }
    
    saveAppState() {
        try {
            const state = {
                currentView: appState.getState('currentView'),
                filtros: appState.getState('filtros'),
                timestamp: Date.now()
            };
            
            localStorage.setItem('calificaciones_app_state', JSON.stringify(state));
            Utils.log('info', 'Estado de aplicación guardado');
        } catch (error) {
            Utils.log('warn', 'Error al guardar estado de aplicación', error);
        }
    }
    
    showFallbackInterface() {
        const fallbackHtml = `
            <div style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc2626;
                color: white;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                max-width: 400px;
                font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            ">
                <h4 style="margin: 0 0 8px 0; font-size: 16px;">⚠️ Error del Sistema</h4>
                <p style="margin: 0; font-size: 14px;">
                    Hubo un error al cargar el sistema. Por favor, recarga la página.
                </p>
                <button onclick="location.reload()" style="
                    background: white;
                    color: #dc2626;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    margin-top: 12px;
                    cursor: pointer;
                    font-weight: bold;
                    font-size: 14px;
                ">Recargar Página</button>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', fallbackHtml);
    }
    
    destroy() {
        try {
            this.saveAppState();
            
            // Limpiar event listeners
            Object.values(this.components).forEach(component => {
                if (component.destroy) {
                    component.destroy();
                }
            });
            
            // Limpiar toasts
            toastManager.clear();
            
            // Ocultar loading
            loadingManager.hide();
            
            // Marcar como no inicializado
            this.initialized = false;
            document.documentElement.classList.remove('calificaciones-ready');
            
            Utils.log('info', '🧹 Sistema destruido correctamente');
            
        } catch (error) {
            Utils.handleError(error, 'CalificacionesApp.destroy');
        }
    }
    
    // Métodos públicos para debugging
    getState() {
        return appState.getState();
    }
    
    getComponents() {
        return this.components;
    }
    
    isInitialized() {
        return this.initialized;
    }
}

// =====================================================================================
// 11. INSTANCIAS GLOBALES
// =====================================================================================

// Crear instancias globales
const toastManager = new ToastManager();
const loadingManager = new LoadingManager();
const app = new CalificacionesApp();

// =====================================================================================
// 12. FUNCIONES GLOBALES PARA COMPATIBILIDAD
// =====================================================================================

// Exponer funciones globales para compatibilidad con código PHP
window.showToast = (mensaje, tipo = 'info', duration) => toastManager.show(mensaje, tipo, duration);
window.cambiarVista = (vista) => app.components.gruposManager?.changeView(vista);
window.limpiarFiltros = () => app.components.gruposManager?.clearFilters();
window.navegarAGrupo = (href) => {
    const grupo = { href, id: 'external', elemento: null };
    return app.components.navigationManager?.navigateToGroup(grupo);
};

// Exponer objetos principales para debugging
window.CalificacionesDebug = {
    app,
    appState,
    toastManager,
    loadingManager,
    Utils,
    CONFIG
};

// Exponer eventos personalizados
window.CalificacionesEvents = CONFIG.EVENTS;

// =====================================================================================
// 13. INICIALIZACIÓN AUTOMÁTICA
// =====================================================================================

// Función de inicialización principal
async function initializeApp() {
    try {
        // Marcar inicio de carga
        document.documentElement.classList.add('app-loading');
        
        // Inicializar aplicación
        const success = await app.init();
        
        if (success) {
            document.documentElement.classList.remove('app-loading');
            document.documentElement.classList.add('app-ready');
            
            Utils.log('success', '🚀 Aplicación lista para usar');
        } else {
            throw new Error('Falló la inicialización de la aplicación');
        }
        
    } catch (error) {
        Utils.handleError(error, 'App Initialization');
        document.documentElement.classList.add('app-error');
        
        toastManager.show('Error al inicializar la aplicación', 'error');
    }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    // DOM ya está listo
    initializeApp();
}

// Cleanup al salir de la página
window.addEventListener('beforeunload', () => {
    if (app.isInitialized()) {
        app.destroy();
    }
});

// =====================================================================================
// 14. POLYFILLS Y COMPATIBILIDAD
// =====================================================================================

// Polyfill para CustomEvent (IE11)
if (!window.CustomEvent) {
    window.CustomEvent = function(event, params) {
        params = params || { bubbles: false, cancelable: false, detail: null };
        const evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    };
}

// Polyfill para requestAnimationFrame
if (!window.requestAnimationFrame) {
    window.requestAnimationFrame = function(callback) {
        return setTimeout(callback, 1000 / 60);
    };
}

// Polyfill para Object.assign
if (!Object.assign) {
    Object.assign = function(target, ...sources) {
        if (target == null) {
            throw new TypeError('Cannot convert undefined or null to object');
        }
        
        const to = Object(target);
        
        for (let index = 0; index < sources.length; index++) {
            const nextSource = sources[index];
            
            if (nextSource != null) {
                for (const nextKey in nextSource) {
                    if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                        to[nextKey] = nextSource[nextKey];
                    }
                }
            }
        }
        
        return to;
    };
}

// =====================================================================================
// 15. LOGGING Y ANALYTICS
// =====================================================================================

// Log de inicialización
Utils.log('success', '📱 Lista Calificaciones Optimizada v4.0 cargada');

// Performance monitoring
if (window.performance && window.performance.mark) {
    window.performance.mark('calificaciones-script-loaded');
}

// Configuración de analytics básica
if (window.gtag) {
    gtag('event', 'script_loaded', {
        'event_category': 'calificaciones',
        'event_label': 'v4.0'
    });
}

console.log(`
🎓 Sistema de Calificaciones Optimizado v4.0
============================================

✅ Script cargado correctamente
📚 Detectados ${document.querySelectorAll('.grupo-card').length} grupos
🔧 Presiona F1 para ver atajos disponibles
🐛 Usa window.CalificacionesDebug para debugging

Funciones globales disponibles:
• showToast(mensaje, tipo, duration)
• cambiarVista(vista)
• limpiarFiltros()
• navegarAGrupo(href)

Eventos personalizados:
• calificaciones:ready
• calificaciones:view-changed
• calificaciones:filters-applied
• calificaciones:period-changed
`);

// =====================================================================================
// 16. OPTIMIZACIONES DE PERFORMANCE
// =====================================================================================

// Lazy loading para imágenes (si las hubiera)
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// Preload crítico
const criticalResources = [
    '/school_management/assets/css/components/dashboard.css',
    '/school_management/assets/js/components/sidebar.js'
];

criticalResources.forEach(resource => {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.href = resource;
    link.as = resource.endsWith('.css') ? 'style' : 'script';
    document.head.appendChild(link);
});

// Service Worker registration (opcional)
if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
    navigator.serviceWorker.register('/school_management/sw.js')
        .then(registration => {
            Utils.log('info', 'Service Worker registrado', registration);
        })
        .catch(error => {
            Utils.log('warn', 'Service Worker falló', error);
        });
}

// =====================================================================================
// FIN DEL ARCHIVO
// =====================================================================================/**
 * =====================================================================================
 * SISTEMA DE GESTIÓN DE CALIFICACIONES - JAVASCRIPT OPTIMIZADO V4.0
 * =====================================================================================
 * 
 * JavaScript completamente reescrito y optimizado:
 * - Arquitectura modular con clases ES6
 * - Gestión de estado centralizada
 * - Performance optimizado
 * - Mejor manejo de errores
 * - Accesibilidad mejorada
 * - Sistema de filtros avanzado
 * - Vistas dinámicas optimizadas
 * 
 * @author Sistema Escolar
 * @version 4.0
 * @since 2024
 * 
 * =====================================================================================
 */

'use strict';

// =====================================================================================
// 1. CONFIGURACIÓN Y CONSTANTES
// =====================================================================================

const CONFIG = {
    // Timing
    DEBOUNCE_DELAY: 300,
    ANIMATION_DURATION: 300,
    TOAST_DURATION: 4000,
    LOADING_MIN_TIME: 800,
    
    // Límites
    MAX_TOASTS: 5,
    MAX_RETRIES: 3,
    CACHE_DURATION: 5 * 60 * 1000, // 5 minutos
    
    // Selectores
    SELECTORS: {
        gruposContainer: '#gruposContainer',
        togglePeriodos: '#togglePeriodos',
        periodosDropdown: '#periodosDropdown',
        searchGrupo: '#searchGrupo',
        filterNivel: '#filterNivel',
        filterGrado: '#filterGrado',
        filterEstado: '#filterEstado',
        viewButtons: '.view-btn',
        grupoCards: '.grupo-card',
        clearFilters: '#clearFilters',
        emptyResults: '#emptyResults',
        loadingOverlay: '.loading-overlay',
        toastContainer: '#toastContainer'
    },
    
    // Clases CSS
    CLASSES: {
        active: 'active',
        show: 'show',
        loading: 'loading',
        hidden: 'hidden',
        fadeIn: 'animate-fade-in'
    },
    
    // Eventos personalizados
    EVENTS: {
        ready: 'calificaciones:ready',
        viewChanged: 'calificaciones:view-changed',
        filtersApplied: 'calificaciones:filters-applied',
        periodChanged: 'calificaciones:period-changed'
    }
};

// =====================================================================================
// 2. UTILIDADES Y HELPERS
// =====================================================================================

class Utils {
    /**
     * Debounce para optimizar eventos frecuentes
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Throttle para limitar ejecución
     */
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    /**
     * Delay asíncrono
     */
    static delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Sanitizar texto para búsqueda
     */
    static sanitizeText(text) {
        if (typeof text !== 'string') return '';
        return text.toLowerCase()
                   .trim()
                   .normalize('NFD')
                   .replace(/[\u0300-\u036f]/g, ''); // Remover acentos
    }
    
    /**
     * Validar elemento DOM
     */
    static validateElement(selector) {
        const element = document.querySelector(selector);
        if (!element) {
            console.warn(`Elemento no encontrado: ${selector}`);
            return null;
        }
        return element;
    }
    
    /**
     * Obtener datos del dataset de forma segura
     */
    static getDataAttribute(element, key) {
        if (!element || !element.dataset) return '';
        return element.dataset[key] || '';
    }
    
    /**
     * Logging estructurado
     */
    static log(level, message, data = null) {
        const timestamp = new Date().toISOString();
        const emoji = { info: '📘', warn: '⚠️', error: '❌', success: '✅' };
        
        console[level](`${emoji[level] || '📝'} [${timestamp}] ${message}`, data || '');
        
        // Enviar a analytics si está disponible
        if (window.analytics) {
            window.analytics.track('CalificacionesLog', {
                level,
                message,
                data,
                timestamp
            });
        }
    }
    
    /**
     * Manejo centralizado de errores
     */
    static handleError(error, context = 'Sistema') {
        const errorInfo = {
            message: error.message || 'Error desconocido',
            stack: error.stack || 'Sin stack trace',
            context,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href
        };
        
        this.log('error', `Error en ${context}: ${errorInfo.message}`, errorInfo);
        
        // Reportar a servicio de errores
        if (window.errorReporter) {
            window.errorReporter.report(errorInfo);
        }
        
        return errorInfo;
    }
    
    /**
     * Crear elemento DOM con atributos
     */
    static createElement(tag, attributes = {}, children = []) {
        const element = document.createElement(tag);
        
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'dataset') {
                Object.entries(value).forEach(([dataKey, dataValue]) => {
                    element.dataset[dataKey] = dataValue;
                });
            } else {
                element.setAttribute(key, value);
            }
        });
        
        children.forEach(child => {
            if (typeof child === 'string') {
                element.textContent = child;
            } else {
                element.appendChild(child);
            }
        });
        
        return element;
    }
    
    /**
     * Escapar HTML para prevenir XSS
     */
    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// =====================================================================================
// 3. GESTIÓN DE ESTADO GLOBAL
// =====================================================================================

class AppState {
    constructor() {
        this.state = {
            // Vista actual
            currentView: localStorage.getItem('calificaciones_view') || 'cards',
            
            // Grupos y filtros
            grupos: [],
            gruposFiltrados: [],
            filtros: {
                busqueda: '',
                nivel: '',
                grado: '',
                estado: ''
            },
            
            // UI State
            isLoading: false,
            periodoDropdownOpen: false,
            
            // Elementos DOM cacheados
            elements: {},
            
            // Configuración
            config: window.APP_CONFIG || {}
        };
        
        this.subscribers = {};
        this.cache = new Map();
    }
    
    /**
     * Actualizar estado y notificar suscriptores
     */
    setState(newState, notify = true) {
        const prevState = { ...this.state };
        this.state = { ...this.state, ...newState };
        
        if (notify) {
            this.notifySubscribers(prevState, this.state);
        }
    }
    
    /**
     * Obtener valor del estado
     */
    getState(key = null) {
        return key ? this.state[key] : this.state;
    }
    
    /**
     * Suscribirse a cambios de estado
     */
    subscribe(key, callback) {
        if (!this.subscribers[key]) {
            this.subscribers[key] = [];
        }
        this.subscribers[key].push(callback);
        
        // Retornar función para desuscribirse
        return () => {
            this.subscribers[key] = this.subscribers[key].filter(cb => cb !== callback);
        };
    }
    
    /**
     * Notificar suscriptores
     */
    notifySubscribers(prevState, newState) {
        Object.keys(this.subscribers).forEach(key => {
            if (prevState[key] !== newState[key]) {
                this.subscribers[key].forEach(callback => {
                    try {
                        callback(newState[key], prevState[key]);
                    } catch (error) {
                        Utils.handleError(error, 'State Subscriber');
                    }
                });
            }
        });
    }
    
    /**
     * Cache con TTL
     */
    setCache(key, value, ttl = CONFIG.CACHE_DURATION) {
        this.cache.set(key, {
            value,
            expires: Date.now() + ttl
        });
    }
    
    getCache(key) {
        const cached = this.cache.get(key);
        if (!cached) return null;
        
        if (Date.now() > cached.expires) {
            this.cache.delete(key);
            return null;
        }
        
        return cached.value;
    }
}

// Instancia global del estado
const appState = new AppState();

// =====================================================================================
// 4. SISTEMA DE TOASTS
// =====================================================================================

class ToastManager {
    constructor() {
        this.activeToasts = new Set();
        this.container = null;
        this.init();
    }
    
    init() {
        this.container = this.createContainer();
    }
    
    createContainer() {
        let container = document.querySelector(CONFIG.SELECTORS.toastContainer);
        
        if (!container) {
            container = Utils.createElement('div', {
                id: 'toastContainer',
                className: 'toast-container',
                'aria-live': 'polite',
                'aria-label': 'Notificaciones del sistema'
            });
            
            Object.assign(container.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: '50',
                display: 'flex',
                flexDirection: 'column',
                gap: '12px',
                pointerEvents: 'none',
                maxWidth: '400px'
            });
            
            document.body.appendChild(container);
        }
        
        return container;
    }
    
    show(message, type = 'info', duration = CONFIG.TOAST_DURATION) {
        // Limitar toasts activos
        if (this.activeToasts.size >= CONFIG.MAX_TOASTS) {
            this.removeOldest();
        }
        
        const toast = this.createToast(message, type, duration);
        this.activeToasts.add(toast);
        this.container.appendChild(toast);
        
        // Animar entrada
        requestAnimationFrame(() => {
            toast.classList.add(CONFIG.CLASSES.show);
        });
        
        // Auto-remover
        setTimeout(() => this.remove(toast), duration);
        
        Utils.log('info', `Toast mostrado: ${message}`, { type, duration });
        return toast;
    }
    
    createToast(message, type, duration) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        const toast = Utils.createElement('div', {
            className: `toast toast-${type}`,
            role: 'alert',
            'aria-live': 'assertive'
        });
        
        Object.assign(toast.style, {
            background: colors[type] || colors.info,
            color: 'white',
            padding: '16px 20px',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            transform: 'translateX(400px)',
            opacity: '0',
            transition: 'all 0.3s ease',
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
            fontSize: '14px',
            fontWeight: '500',
            maxWidth: '100%',
            wordWrap: 'break-word',
            pointerEvents: 'auto',
            cursor: 'pointer'
        });
        
        toast.innerHTML = `
            <i class="${icons[type] || icons.info}" style="font-size: 16px; flex-shrink: 0;"></i>
            <span style="flex: 1;">${Utils.escapeHtml(message)}</span>
            <button type="button" class="toast-close" style="background: transparent; border: none; color: inherit; cursor: pointer; padding: 0; margin-left: 8px; opacity: 0.7; font-size: 16px;" aria-label="Cerrar notificación">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Event listeners
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.remove(toast);
        });
        
        toast.addEventListener('click', () => this.remove(toast));
        
        return toast;
    }
    
    remove(toast) {
        if (!toast || !toast.parentNode) return;
        
        toast.style.transform = 'translateX(400px)';
        toast.style.opacity = '0';
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            this.activeToasts.delete(toast);
        }, 300);
    }
    
    removeOldest() {
        const oldest = this.activeToasts.values().next().value;
        if (oldest) {
            this.remove(oldest);
        }
    }
    
    clear() {
        this.activeToasts.forEach(toast => this.remove(toast));
        this.activeToasts.clear();
    }
}

// =====================================================================================
// 5. GESTIÓN DE LOADING
// =====================================================================================

class LoadingManager {
    constructor() {
        this.isActive = false;
        this.overlay = null;
    }
    
    show(message = 'Cargando...') {
        if (this.isActive) return;
        
        this.isActive = true;
        
        if (!this.overlay) {
            this.createOverlay();
        }
        
        const messageEl = this.overlay.querySelector('.loading-text');
        if (messageEl) {
            messageEl.textContent = message;
        }
        
        this.overlay.classList.add(CONFIG.CLASSES.active);
        document.body.classList.add('loading-active');
        
        Utils.log('info', 'Loading mostrado', { message });
    }
    
    hide() {
        if (!this.isActive || !this.overlay) return;
        
        this.isActive = false;
        this.overlay.classList.remove(CONFIG.CLASSES.active);
        document.body.classList.remove('loading-active');
        
        Utils.log('info', 'Loading ocultado');
    }
    
    createOverlay() {
        this.overlay = Utils.createElement('div', {
            className: 'loading-overlay'
        });
        
        this.overlay.innerHTML = `
            <div class="loading-content">
                <div class="spinner"></div>
                <div class="loading-text">Cargando...</div>
            </div>
        `;
        
        document.body.appendChild(this.overlay);
    }
}

// =====================================================================================
// 6. GESTIÓN DE ELEMENTOS DOM
// =====================================================================================

class DOMManager {
    constructor() {
        this.elements = {};
    }
    
    init() {
        try {
            // Cachear elementos principales
            Object.entries(CONFIG.SELECTORS).forEach(([key, selector]) => {
                const element = document.querySelector(selector);
                this.elements[key] = element;
                
                if (!element && ['gruposContainer', 'togglePeriodos'].includes(key)) {
                    Utils.log('warn', `Elemento crítico no encontrado: ${selector}`);
                }
            });
            
            // Elementos adicionales
            this.elements.viewButtons = document.querySelectorAll(CONFIG.SELECTORS.viewButtons);
            this.elements.grupoCards = document.querySelectorAll(CONFIG.SELECTORS.grupoCards);
            
            // Actualizar estado
            appState.setState({ elements: this.elements });
            
            Utils.log('success', 'Elementos DOM inicializados');
            return true;