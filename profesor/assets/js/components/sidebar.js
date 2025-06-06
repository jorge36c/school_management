// profesor/assets/js/components/sidebar.js

// Variables para los elementos DOM
let sidebar;
let sidebarToggle;
let sidebarOverlay;
let mainContent;
let countdownInterval;
let lastValues = {};

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar referencias a elementos DOM
    sidebar = document.getElementById('sidebar');
    sidebarToggle = document.getElementById('sidebarToggle');
    sidebarOverlay = document.getElementById('sidebarOverlay');
    mainContent = document.querySelector('.main-content');

    // Verificar si hay preferencia guardada para el estado del sidebar
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    if (sidebarState === 'true' && sidebar) {
        sidebar.classList.add('collapsed');
        if (mainContent) {
            mainContent.classList.add('expanded');
        }
    }

    // Manejar el toggle del sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    // Cerrar sidebar en móviles al hacer clic en el overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebarMobile);
    }

    // Ajustar sidebar en cambio de tamaño de ventana
    window.addEventListener('resize', handleResize);

    // Iniciar el contador si hay periodo activo
    iniciarCountdown();
    
    // Soporte para teclas de accesibilidad
    document.addEventListener('keydown', handleKeyboard);
});

/**
 * Función para alternar el estado del sidebar
 */
function toggleSidebar() {
    if (!sidebar) return;
    
    sidebar.classList.toggle('collapsed');
    if (mainContent) {
        mainContent.classList.toggle('expanded');
    }
    
    // Para móviles
    sidebar.classList.toggle('active');
    
    // Guardar preferencia
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
}

/**
 * Cierra el sidebar en vista móvil
 */
function closeSidebarMobile() {
    if (!sidebar) return;
    sidebar.classList.remove('active');
}

/**
 * Maneja el redimensionamiento de la ventana
 */
function handleResize() {
    if (!sidebar) return;
    if (window.innerWidth > 991.98 && sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
    }
}

/**
 * Maneja eventos de teclado para accesibilidad
 */
function handleKeyboard(e) {
    if (!sidebar) return;
    // ESC cierra el sidebar en móviles
    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
    }
}

/**
 * Inicia el contador de tiempo para el periodo
 */
function iniciarCountdown() {
    // Obtener referencias a los elementos del contador
    const diasElement = document.getElementById('dias');
    const horasElement = document.getElementById('horas');
    const minutosElement = document.getElementById('minutos');
    const segundosElement = document.getElementById('segundos');
    
    // Si no existen los elementos, salir
    if (!diasElement || !horasElement || !minutosElement || !segundosElement) {
        return;
    }
    
    // Guardar valores iniciales
    lastValues = {
        dias: diasElement.textContent,
        horas: horasElement.textContent,
        minutos: minutosElement.textContent,
        segundos: segundosElement.textContent
    };
    
    // Obtener la fecha de fin del periodo desde el atributo data
    const periodoActual = document.getElementById('periodoActual');
    if (!periodoActual || periodoActual.textContent === 'No hay periodo activo') {
        return;
    }
    
    // Si ya hay un intervalo activo, detenerlo
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    // Buscar la fecha de fin en el DOM (puedes pasar este dato como un atributo data)
    const fechaFinElement = document.querySelector('[data-fecha-fin]');
    const fechaFin = fechaFinElement ? fechaFinElement.dataset.fechaFin : null;
    
    if (!fechaFin) {
        return;
    }
    
    countdownInterval = setInterval(() => {
        const ahora = new Date().getTime();
        const fin = new Date(fechaFin).getTime();
        const diferencia = fin - ahora;
        
        if (diferencia <= 0) {
            // Si el periodo ya terminó, detener el contador
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            diasElement.textContent = '00';
            horasElement.textContent = '00';
            minutosElement.textContent = '00';
            segundosElement.textContent = '00';
            return;
        }
        
        // Calcular tiempo
        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);
        
        // Actualizar elementos con animación sólo si cambian
        const nuevosValores = {
            dias: dias.toString().padStart(2, '0'),
            horas: horas.toString().padStart(2, '0'),
            minutos: minutos.toString().padStart(2, '0'),
            segundos: segundos.toString().padStart(2, '0')
        };
        
        if (lastValues.dias !== nuevosValores.dias) {
            actualizarElementoConAnimacion(diasElement, nuevosValores.dias);
            lastValues.dias = nuevosValores.dias;
        }
        
        if (lastValues.horas !== nuevosValores.horas) {
            actualizarElementoConAnimacion(horasElement, nuevosValores.horas);
            lastValues.horas = nuevosValores.horas;
        }
        
        if (lastValues.minutos !== nuevosValores.minutos) {
            actualizarElementoConAnimacion(minutosElement, nuevosValores.minutos);
            lastValues.minutos = nuevosValores.minutos;
        }
        
        if (lastValues.segundos !== nuevosValores.segundos) {
            actualizarElementoConAnimacion(segundosElement, nuevosValores.segundos);
            lastValues.segundos = nuevosValores.segundos;
        }
    }, 1000);
}

/**
 * Actualiza un elemento con animación
 * @param {HTMLElement} elemento - Elemento a actualizar
 * @param {string} nuevoValor - Nuevo valor a mostrar
 */
function actualizarElementoConAnimacion(elemento, nuevoValor) {
    if (!elemento) return;
    
    elemento.classList.remove('number-changed');
    elemento.textContent = nuevoValor;
    
    // Forzar reflow para reiniciar animación
    void elemento.offsetWidth;
    
    elemento.classList.add('number-changed');
}

// Exponer función para el botón en el topbar
window.toggleSidebar = toggleSidebar;