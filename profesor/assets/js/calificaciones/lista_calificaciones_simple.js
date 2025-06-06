/**
 * =====================================================================================
 * SISTEMA DE GESTIÓN DE CALIFICACIONES - JAVASCRIPT PRINCIPAL
 * =====================================================================================
 * 
 * Maneja todas las funcionalidades de la vista de lista de calificaciones:
 * - Selector de períodos académicos
 * - Filtros de búsqueda y nivel/grado
 * - Cambio de vistas (tarjetas, compacta, lista)
 * - Navegación entre grupos
 * - Animaciones y efectos visuales
 * 
 * @author Tu Nombre
 * @version 3.0
 * @since 2024
 * 
 * =====================================================================================
 */

// =====================================================================================
// 1. VARIABLES GLOBALES Y CONFIGURACIÓN
// =====================================================================================

let currentView = 'cards';
let grupos = [];
let gruposFiltrados = [];
let filtroActual = {
    busqueda: '',
    nivel: '',
    grado: ''
};

// Configuración de la aplicación
const CONFIG = {
    animationDuration: 300,
    debounceDelay: 300,
    toastDuration: 3000,
    maxRetries: 3
};

// =====================================================================================
// 2. INICIALIZACIÓN
// =====================================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎓 Inicializando sistema de calificaciones...');
    
    try {
        inicializarElementos();
        configurarEventListeners();
        inicializarGrupos();
        inicializarFiltros();
        configurarAtajosTeclado();
        
        console.log('✅ Sistema inicializado correctamente');
        showToast('Sistema de calificaciones cargado', 'success');
        
    } catch (error) {
        console.error('❌ Error al inicializar:', error);
        showToast('Error al cargar el sistema', 'error');
    }
});

// =====================================================================================
// 3. INICIALIZACIÓN DE ELEMENTOS
// =====================================================================================

function inicializarElementos() {
    // Verificar elementos críticos
    const elementosCriticos = [
        'gruposContainer',
        'searchGrupo',
        'filterNivel',
        'filterGrado',
        'togglePeriodos'
    ];
    
    elementosCriticos.forEach(id => {
        const elemento = document.getElementById(id);
        if (!elemento) {
            console.warn(`⚠️ Elemento ${id} no encontrado`);
        }
    });
    
    // Configurar vista inicial
    const savedView = localStorage.getItem('calificaciones_view') || 'cards';
    cambiarVista(savedView);
}

// =====================================================================================
// 4. GESTIÓN DEL SELECTOR DE PERÍODOS
// =====================================================================================

function configurarEventListeners() {
    // === SELECTOR DE PERÍODOS ===
    const togglePeriodos = document.getElementById('togglePeriodos');
    const periodosDropdown = document.getElementById('periodosDropdown');
    
    if (togglePeriodos && periodosDropdown) {
        // Toggle del dropdown
        togglePeriodos.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleDropdown();
        });
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!togglePeriodos.contains(e.target) && !periodosDropdown.contains(e.target)) {
                cerrarDropdown();
            }
        });
        
        // Manejar teclas de escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && periodosDropdown.classList.contains('show')) {
                cerrarDropdown();
            }
        });
        
        // Navegación con teclado en el dropdown
        periodosDropdown.addEventListener('keydown', function(e) {
            manejarNavegacionTeclado(e);
        });
    }
    
    // === FILTROS DE BÚSQUEDA ===
    const searchInput = document.getElementById('searchGrupo');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            filtroActual.busqueda = this.value.toLowerCase().trim();
            aplicarFiltros();
        }, CONFIG.debounceDelay));
    }
    
    const filterNivel = document.getElementById('filterNivel');
    if (filterNivel) {
        filterNivel.addEventListener('change', function() {
            filtroActual.nivel = this.value;
            aplicarFiltros();
        });
    }
    
    const filterGrado = document.getElementById('filterGrado');
    if (filterGrado) {
        filterGrado.addEventListener('change', function() {
            filtroActual.grado = this.value;
            aplicarFiltros();
        });
    }
    
    // === BOTONES DE VISTA ===
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const vista = this.dataset.view;
            if (vista) {
                cambiarVista(vista);
            }
        });
    });
    
    // === LIMPIAR FILTROS ===
    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', limpiarFiltros);
    }
    
    // === NAVEGACIÓN EN GRUPOS ===
    configurarNavegacionGrupos();
}

// =====================================================================================
// 5. FUNCIONES DEL DROPDOWN DE PERÍODOS
// =====================================================================================

function toggleDropdown() {
    const dropdown = document.getElementById('periodosDropdown');
    const toggleBtn = document.getElementById('togglePeriodos');
    
    if (!dropdown || !toggleBtn) return;
    
    const isOpen = dropdown.classList.contains('show');
    
    if (isOpen) {
        cerrarDropdown();
    } else {
        abrirDropdown();
    }
}

function abrirDropdown() {
    const dropdown = document.getElementById('periodosDropdown');
    const toggleBtn = document.getElementById('togglePeriodos');
    
    if (!dropdown || !toggleBtn) return;
    
    // Añadir clase show
    dropdown.classList.add('show');
    
    // Actualizar icono
    const icon = toggleBtn.querySelector('.fa-chevron-down');
    if (icon) {
        icon.classList.add('fa-rotate-180');
    }
    
    // Enfocar primer elemento
    setTimeout(() => {
        const primerItem = dropdown.querySelector('.periodo-item');
        if (primerItem) {
            primerItem.focus();
        }
    }, 100);
    
    console.log('📅 Dropdown de períodos abierto');
}

function cerrarDropdown() {
    const dropdown = document.getElementById('periodosDropdown');
    const toggleBtn = document.getElementById('togglePeriodos');
    
    if (!dropdown || !toggleBtn) return;
    
    // Remover clase show
    dropdown.classList.remove('show');
    
    // Restaurar icono
    const icon = toggleBtn.querySelector('.fa-chevron-down');
    if (icon) {
        icon.classList.remove('fa-rotate-180');
    }
    
    console.log('📅 Dropdown de períodos cerrado');
}

function manejarNavegacionTeclado(e) {
    const items = document.querySelectorAll('.periodo-item');
    const currentIndex = Array.from(items).findIndex(item => item === document.activeElement);
    
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
            if (document.activeElement.classList.contains('periodo-item')) {
                window.location.href = document.activeElement.href;
            }
            break;
            
        case 'Escape':
            cerrarDropdown();
            document.getElementById('togglePeriodos')?.focus();
            break;
    }
}

// =====================================================================================
// 6. GESTIÓN DE GRUPOS Y FILTROS
// =====================================================================================

function inicializarGrupos() {
    const gruposCards = document.querySelectorAll('.grupo-card');
    grupos = Array.from(gruposCards).map(card => {
        return {
            elemento: card,
            nivel: card.dataset.nivel || '',
            grado: card.dataset.grado || '',
            materia: card.dataset.materia || '',
            totalEstudiantes: parseInt(card.dataset.totalEstudiantes) || 0,
            estudiantesCalificados: parseInt(card.dataset.estudiantesCalificados) || 0,
            porcentaje: parseInt(card.dataset.porcentaje) || 0,
            href: card.dataset.href || '#'
        };
    });
    
    gruposFiltrados = [...grupos];
    console.log(`📚 Inicializados ${grupos.length} grupos`);
}

function aplicarFiltros() {
    gruposFiltrados = grupos.filter(grupo => {
        // Filtro por búsqueda
        if (filtroActual.busqueda) {
            const texto = `${grupo.nivel} ${grupo.materia}`.toLowerCase();
            if (!texto.includes(filtroActual.busqueda)) {
                return false;
            }
        }
        
        // Filtro por nivel
        if (filtroActual.nivel && grupo.nivel !== filtroActual.nivel) {
            return false;
        }
        
        // Filtro por grado
        if (filtroActual.grado && grupo.grado !== filtroActual.grado) {
            return false;
        }
        
        return true;
    });
    
    mostrarGruposFiltrados();
    actualizarEstadoVacio();
    
    console.log(`🔍 Filtros aplicados: ${gruposFiltrados.length}/${grupos.length} grupos`);
}

function mostrarGruposFiltrados() {
    grupos.forEach(grupo => {
        const mostrar = gruposFiltrados.includes(grupo);
        grupo.elemento.style.display = mostrar ? '' : 'none';
        
        if (mostrar) {
            grupo.elemento.classList.add('fade-in');
        } else {
            grupo.elemento.classList.remove('fade-in');
        }
    });
}

function actualizarEstadoVacio() {
    const emptyState = document.getElementById('emptyResults');
    const container = document.getElementById('gruposContainer');
    
    if (!emptyState || !container) return;
    
    if (gruposFiltrados.length === 0) {
        emptyState.style.display = 'block';
        container.style.display = 'none';
    } else {
        emptyState.style.display = 'none';
        container.style.display = '';
    }
}

function limpiarFiltros() {
    // Resetear filtros
    filtroActual = {
        busqueda: '',
        nivel: '',
        grado: ''
    };
    
    // Limpiar campos
    const searchInput = document.getElementById('searchGrupo');
    const filterNivel = document.getElementById('filterNivel');
    const filterGrado = document.getElementById('filterGrado');
    
    if (searchInput) searchInput.value = '';
    if (filterNivel) filterNivel.value = '';
    if (filterGrado) filterGrado.value = '';
    
    // Aplicar filtros
    aplicarFiltros();
    
    showToast('Filtros limpiados', 'info');
}

// =====================================================================================
// 7. GESTIÓN DE VISTAS
// =====================================================================================

function cambiarVista(nuevaVista) {
    if (!['cards', 'compact', 'list'].includes(nuevaVista)) {
        console.warn(`⚠️ Vista inválida: ${nuevaVista}`);
        return;
    }
    
    const container = document.getElementById('gruposContainer');
    if (!container) return;
    
    // Remover clases de vista anteriores
    container.classList.remove('view-cards', 'view-compact', 'view-list');
    
    // Añadir nueva clase de vista
    container.classList.add(`view-${nuevaVista}`);
    
    // Actualizar botones
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.view === nuevaVista) {
            btn.classList.add('active');
        }
    });
    
    // Actualizar contenido de las tarjetas para la nueva vista
    actualizarContenidoVista(nuevaVista);
    
    // Guardar preferencia
    localStorage.setItem('calificaciones_view', nuevaVista);
    currentView = nuevaVista;
    
    console.log(`👁️ Vista cambiada a: ${nuevaVista}`);
    showToast(`Vista ${nuevaVista} activada`, 'info');
}

function actualizarContenidoVista(vista) {
    grupos.forEach(grupo => {
        const card = grupo.elemento;
        regenerarContenidoTarjeta(card, vista);
    });
}

function regenerarContenidoTarjeta(card, vista) {
    // Esta función regenera el contenido de la tarjeta según la vista
    // El contenido ya está definido en el PHP, solo necesitamos asegurar
    // que las clases CSS correctas estén aplicadas
    
    switch (vista) {
        case 'cards':
            // Vista de tarjetas - contenido completo
            break;
            
        case 'compact':
            // Vista compacta - contenido reducido
            break;
            
        case 'list':
            // Vista de lista - contenido horizontal
            break;
    }
}

// =====================================================================================
// 8. NAVEGACIÓN EN GRUPOS
// =====================================================================================

function configurarNavegacionGrupos() {
    grupos.forEach(grupo => {
        const card = grupo.elemento;
        
        // Click en tarjeta
        card.addEventListener('click', function(e) {
            // Evitar navegación si se hace clic en un botón
            if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                return;
            }
            
            navegarAGrupo(grupo.href);
        });
        
        // Enter en tarjeta enfocada
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                navegarAGrupo(grupo.href);
            }
        });
        
        // Efectos de hover mejorados
        card.addEventListener('mouseenter', function() {
            this.style.transform = currentView === 'cards' ? 'translateY(-4px)' : 
                                  currentView === 'compact' ? 'translateY(-2px)' : 
                                  'translateX(4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
}

function navegarAGrupo(href) {
    if (!href || href === '#') {
        showToast('Enlace no disponible', 'warning');
        return;
    }
    
    // Mostrar loading
    mostrarLoading();
    
    // Navegar con un pequeño delay para mostrar el loading
    setTimeout(() => {
        window.location.href = href;
    }, 200);
}

// =====================================================================================
// 9. ATAJOS DE TECLADO
// =====================================================================================

function configurarAtajosTeclado() {
    document.addEventListener('keydown', function(e) {
        // Solo procesar si no estamos en un input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
            return;
        }
        
        // Ctrl + números para cambiar vista
        if (e.ctrlKey) {
            switch (e.key) {
                case '1':
                    e.preventDefault();
                    cambiarVista('cards');
                    break;
                case '2':
                    e.preventDefault();
                    cambiarVista('compact');
                    break;
                case '3':
                    e.preventDefault();
                    cambiarVista('list');
                    break;
                case 'f':
                case 'F':
                    e.preventDefault();
                    document.getElementById('searchGrupo')?.focus();
                    break;
            }
        }
        
        // Escape para limpiar filtros
        if (e.key === 'Escape') {
            limpiarFiltros();
        }
    });
}

// =====================================================================================
// 10. FUNCIONES DE UTILIDAD
// =====================================================================================

function debounce(func, wait) {
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

function mostrarLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.classList.add('active');
    }
}

function ocultarLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

function showToast(mensaje, tipo = 'info') {
    // Crear container si no existe
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(container);
    }
    
    // Crear toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.style.cssText = `
        background: ${tipo === 'success' ? '#10b981' : 
                     tipo === 'error' ? '#ef4444' : 
                     tipo === 'warning' ? '#fbbf24' : '#4361ee'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
    `;
    toast.textContent = mensaje;
    
    container.appendChild(toast);
    
    // Animar entrada
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto-remover
    setTimeout(() => {
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, CONFIG.toastDuration);
}

// =====================================================================================
// 11. FUNCIONES GLOBALES PARA COMPATIBILIDAD
// =====================================================================================

// Hacer funciones disponibles globalmente
window.showToast = showToast;
window.cambiarVista = cambiarVista;
window.limpiarFiltros = limpiarFiltros;
window.navegarAGrupo = navegarAGrupo;

// =====================================================================================
// 12. MANEJO DE ERRORES
// =====================================================================================

window.addEventListener('error', function(e) {
    console.error('💥 Error en lista_calificaciones.js:', e.error);
    showToast('Ha ocurrido un error inesperado', 'error');
});

// Manejo de errores de promesas no capturadas
window.addEventListener('unhandledrejection', function(e) {
    console.error('💥 Promesa rechazada no manejada:', e.reason);
    showToast('Error de conexión', 'error');
});

console.log('📱 lista_calificaciones_simple.js cargado correctamente');