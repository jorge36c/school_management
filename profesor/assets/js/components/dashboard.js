// profesor/assets/js/components/dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    // Configurar gráfico de porcentaje de calificaciones
    setupGradesChart();
    
    // Formatear números para mejor legibilidad
    formatNumbers();
    
    // Actualizar animaciones y efectos visuales
    initializeAnimations();
    
    // Detectar si el sidebar está colapsado para ajustar el contenido
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && sidebar.classList.contains('collapsed')) {
        mainContent.classList.add('expanded');
    }
});

/**
 * Configura el gráfico circular para el porcentaje de estudiantes calificados
 */
function setupGradesChart() {
    const gradesChart = document.querySelector('.grades-chart');
    const percentageElement = document.querySelector('.grades-percentage');
    
    if (gradesChart && percentageElement) {
        const percentage = parseInt(percentageElement.textContent);
        
        // Aplicar gradiente de color según el porcentaje
        let color = '#ef4444'; // rojo (bajo)
        
        if (percentage >= 75) {
            color = '#10b981'; // verde (alto)
        } else if (percentage >= 50) {
            color = '#3b82f6'; // azul (medio)
        } else if (percentage >= 25) {
            color = '#f59e0b'; // naranja (medio-bajo)
        }
        
        // Aplicar el gradiente cónico para el gráfico circular
        gradesChart.style.background = `conic-gradient(
            ${color} ${percentage}%, 
            var(--gray-light) 0%
        )`;
    }
}

/**
 * Formatea números grandes con separadores de miles
 */
function formatNumbers() {
    document.querySelectorAll('.stat-value').forEach(el => {
        const value = el.textContent.trim();
        // Solo formatear si es un número y no tiene decimales
        if (!isNaN(parseFloat(value)) && !value.includes('.')) {
            const numValue = parseInt(value);
            try {
                el.textContent = numValue.toLocaleString('es-ES');
            } catch (e) {
                // Fallback en caso de que el navegador no soporte la localización 'es-ES'
                el.textContent = numValue.toLocaleString();
            }
        }
    });
}

/**
 * Inicializa animaciones y efectos visuales
 */
function initializeAnimations() {
    // Aplicar retraso en las animaciones para entrada escalonada
    const animatedElements = document.querySelectorAll('.animate-fade-in, .animate-slide-up');
    
    let delay = 0;
    animatedElements.forEach((element, index) => {
        if (!element.classList.contains('delay-100') && 
            !element.classList.contains('delay-200') && 
            !element.classList.contains('delay-300') && 
            !element.classList.contains('delay-400') && 
            !element.classList.contains('delay-500')) {
            
            delay = 0.1 * index;
            element.style.animationDelay = `${delay}s`;
        }
    });
    
    // Añadir efecto hover a las tarjetas de estadísticas
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-6px)';
            this.style.boxShadow = 'var(--shadow-lg)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = 'var(--shadow-md)';
        });
    });
}

/**
 * Maneja la navegación rápida a las secciones principales
 * @param {string} section - Sección a la que navegar
 */
function navigateTo(section) {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '/school_management/profesor';
    
    switch(section) {
        case 'calificaciones':
            window.location.href = `${baseUrl}/views/calificaciones/lista_calificaciones.php`;
            break;
        case 'asistencia':
            window.location.href = `${baseUrl}/views/asistencia/control_asistencia.php`;
            break;
        case 'grupos':
            window.location.href = `${baseUrl}/views/grupos/mis_grupos.php`;
            break;
        default:
            break;
    }
}