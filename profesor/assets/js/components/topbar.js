// profesor/assets/js/components/topbar.js

document.addEventListener('DOMContentLoaded', function() {
    // Referencia al botón de menú en el top bar
    const menuToggle = document.getElementById('menuToggle');
    
    // Conectar con el sidebar
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            // Intentar varias formas de interactuar con el sidebar
            if (window.toggleSidebar) {
                window.toggleSidebar();
            } else if (document.getElementById('sidebar')) {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('collapsed');
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.classList.toggle('expanded');
                }
            }
        });
    }
    
    /**
     * Actualiza la fecha y hora actual con formato localizado
     */
    function updateDateTime() {
        const now = new Date();
        const dateElement = document.getElementById('current-date');
        const timeElement = document.getElementById('current-time');
        
        if (dateElement) {
            // Formatear fecha en español
            const dateOptions = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            try {
                const dateFormatted = now.toLocaleDateString('es-ES', dateOptions);
                // Capitalizar primera letra
                const dateCapitalized = dateFormatted.charAt(0).toUpperCase() + dateFormatted.slice(1);
                dateElement.textContent = dateCapitalized;
            } catch (e) {
                // Fallback en caso de que el navegador no soporte la localización 'es-ES'
                dateElement.textContent = now.toLocaleDateString();
            }
        }
        
        if (timeElement) {
            // Verificar si el contenido va a cambiar
            const prevTime = timeElement.textContent;
            
            // Formatear hora (con AM/PM)
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            };
            
            try {
                const timeFormatted = now.toLocaleTimeString('es-ES', timeOptions);
                
                // Si la hora cambió, añadir clase para animación
                if (prevTime !== timeFormatted) {
                    timeElement.classList.add('updating');
                    setTimeout(() => {
                        timeElement.classList.remove('updating');
                    }, 500);
                }
                
                timeElement.textContent = timeFormatted;
            } catch (e) {
                // Fallback en caso de que el navegador no soporte la localización 'es-ES'
                timeElement.textContent = now.toLocaleTimeString();
            }
        }
    }
    
    // Actualizar inmediatamente y luego cada segundo
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
    // Dropdown del usuario - accesibilidad por teclado
    const avatarWrapper = document.querySelector('.avatar-wrapper');
    const menuDropdown = document.querySelector('.menu-dropdown');
    
    if (avatarWrapper && menuDropdown) {
        // Al hacer clic en el avatar mostrar el menú
        avatarWrapper.addEventListener('click', function() {
            const expanded = avatarWrapper.getAttribute('aria-expanded') === 'true';
            avatarWrapper.setAttribute('aria-expanded', !expanded);
            
            if (!expanded) {
                menuDropdown.style.opacity = '1';
                menuDropdown.style.visibility = 'visible';
                menuDropdown.style.transform = 'translateY(0)';
            } else {
                menuDropdown.style.opacity = '0';
                menuDropdown.style.visibility = 'hidden';
                menuDropdown.style.transform = 'translateY(10px)';
            }
        });
        
        // Manejar evento de teclado
        avatarWrapper.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                
                const expanded = avatarWrapper.getAttribute('aria-expanded') === 'true';
                avatarWrapper.setAttribute('aria-expanded', !expanded);
                
                if (!expanded) {
                    menuDropdown.style.opacity = '1';
                    menuDropdown.style.visibility = 'visible';
                    menuDropdown.style.transform = 'translateY(0)';
                    
                    // Enfocar el primer elemento del menú
                    const firstMenuItem = menuDropdown.querySelector('.menu-item');
                    if (firstMenuItem) {
                        firstMenuItem.focus();
                    }
                } else {
                    menuDropdown.style.opacity = '0';
                    menuDropdown.style.visibility = 'hidden';
                    menuDropdown.style.transform = 'translateY(10px)';
                }
            }
            
            // Cerrar con Escape
            if (e.key === 'Escape') {
                avatarWrapper.setAttribute('aria-expanded', 'false');
                menuDropdown.style.opacity = '0';
                menuDropdown.style.visibility = 'hidden';
                menuDropdown.style.transform = 'translateY(10px)';
                avatarWrapper.focus();
            }
        });
        
        // Cerrar el menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!avatarWrapper.contains(e.target) && !menuDropdown.contains(e.target)) {
                avatarWrapper.setAttribute('aria-expanded', 'false');
                menuDropdown.style.opacity = '0';
                menuDropdown.style.visibility = 'hidden';
                menuDropdown.style.transform = 'translateY(10px)';
            }
        });
    }
});