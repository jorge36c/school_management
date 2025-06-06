// Inicialización del módulo profesor
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initializeSidebar();
    initializeTopbar();
    initializeNotifications();
    
    // Configurar tema
    setupTheme();
});

// Gestión del sidebar
function initializeSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Cerrar sidebar en móviles al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                e.target !== sidebarToggle) {
                sidebar.classList.remove('active');
            }
        });
    }
}

// Gestión del topbar
function initializeTopbar() {
    // Gestionar dropdowns
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const button = dropdown.querySelector('button');
        
        if (button) {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // Cerrar otros dropdowns
                document.querySelectorAll('.dropdown.active').forEach(active => {
                    if (active !== dropdown) {
                        active.classList.remove('active');
                    }
                });
                
                dropdown.classList.toggle('active');
            });
        }
    });
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown.active').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    });
    
    // Evitar cierre al hacer clic dentro del menú
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    });
}

// Sistema de notificaciones
function initializeNotifications() {
    const notificationManager = new NotificationManager();
    
    // Marcar todas como leídas
    document.querySelectorAll('.mark-all-read').forEach(button => {
        button.addEventListener('click', () => {
            notificationManager.markAllAsRead();
        });
    });
}

// Gestión del tema
function setupTheme() {
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    
    // Escuchar cambios en el selector de tema
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('change', (e) => {
            const newTheme = e.target.checked ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
}

// Utilidades
const utils = {
    // Formatear fecha
    formatDate(date) {
        return new Intl.DateTimeFormat('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    },
    
    // Formatear número
    formatNumber(number, decimals = 0) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    },
    
    // Mostrar toast
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                 type === 'error' ? 'exclamation-circle' : 
                                 type === 'warning' ? 'exclamation-triangle' : 
                                 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="toast-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(toast);
        
        // Animar entrada
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });
        
        // Auto cerrar después de 5 segundos
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
        
        // Cerrar al hacer clic
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        });
    },
    
    // Validar formulario
    validateForm(form) {
        let isValid = true;
        const errors = [];
        
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                errors.push(`El campo ${field.name} es requerido`);
            } else {
                field.classList.remove('error');
            }
        });
        
        return { isValid, errors };
    },
    
    // Desactivar botón durante envío
    async submitButton(button, callback) {
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        
        try {
            await callback();
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }
};

// Exportar utilidades
window.utils = utils; 