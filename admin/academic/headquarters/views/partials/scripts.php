<?php
// Determinar si estamos en modo desarrollo o producción
$isProduction = false; // Cambiar a true en producción
$version = '1.0.0'; // Versión para cache busting
?>

<!-- Scripts principales -->
<script>
// Configuración global
window.appConfig = {
    baseUrl: '/school_management',
    apiUrl: '/school_management/api',
    isProduction: <?php echo $isProduction ? 'true' : 'false'; ?>,
    version: '<?php echo $version; ?>',
    sede: <?php echo json_encode([
        'id' => $sede['id'] ?? null,
        'nombre' => $sede['nombre'] ?? '',
        'tipo_ensenanza' => $sede['tipo_ensenanza'] ?? '',
    ]); ?>
};

// Utilidades comunes
const utils = {
    // Formateo de números
    formatNumber: (number) => {
        return new Intl.NumberFormat('es-CO').format(number);
    },

    // Formateo de fechas
    formatDate: (date) => {
        return new Intl.DateTimeFormat('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }).format(new Date(date));
    },

    // Formateo de moneda
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP'
        }).format(amount);
    },

    // Manejo de errores
    handleError: (error) => {
        console.error('Error:', error);
        notifications.show('Ha ocurrido un error', 'error');
    }
};

// Sistema de notificaciones
const notifications = {
    show: (message, type = 'info', duration = 5000) => {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                               type === 'error' ? 'exclamation-circle' :
                               type === 'warning' ? 'exclamation-triangle' : 
                               'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        requestAnimationFrame(() => notification.classList.add('show'));
        
        if (duration) {
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    }
};

// Manejador de peticiones HTTP
const http = {
    request: async (url, options = {}) => {
        try {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json'
                }
            };
            
            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }
            
            return data;
        } catch (error) {
            utils.handleError(error);
            throw error;
        }
    },
    
    get: (url) => http.request(url),
    
    post: (url, data) => http.request(url, {
        method: 'POST',
        body: JSON.stringify(data)
    }),
    
    put: (url, data) => http.request(url, {
        method: 'PUT',
        body: JSON.stringify(data)
    }),
    
    delete: (url) => http.request(url, {
        method: 'DELETE'
    })
};

// Manejador del reloj
const clockManager = {
    init: () => {
        const updateClock = () => {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-CO', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        };
        
        updateClock();
        setInterval(updateClock, 1000);
    }
};

// Manejador de formularios
const forms = {
    validate: (form) => {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });
        
        return isValid;
    },
    
    serialize: (form) => {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    }
};

// Manejador del modo oscuro
const darkMode = {
    toggle: () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
    },
    
    init: () => {
        const isDark = localStorage.getItem('darkMode') === 'true';
        if (isDark) {
            document.body.classList.add('dark-mode');
        }
    }
};

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    clockManager.init();
    darkMode.init();
    
    // Inicializar tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = el.dataset.tooltip;
        el.appendChild(tooltip);
    });
});
</script>

<!-- Scripts externos -->
<?php if ($isProduction): ?>
    <!-- Versiones minificadas para producción -->
    <script src="<?php echo $appConfig['baseUrl']; ?>/assets/js/vendor.min.js?v=<?php echo $version; ?>"></script>
    <script src="<?php echo $appConfig['baseUrl']; ?>/admin/academic/headquarters/assets/js/sede.min.js?v=<?php echo $version; ?>"></script>
<?php else: ?>
    <!-- Versiones de desarrollo -->
    <script src="<?php echo $appConfig['baseUrl']; ?>/assets/js/vendor.js?v=<?php echo $version; ?>"></script>
    <script src="<?php echo $appConfig['baseUrl']; ?>/admin/academic/headquarters/assets/js/sede.js?v=<?php echo $version; ?>"></script>
<?php endif; ?>

<!-- Estilos para notificaciones y tooltips -->
<style>
.notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: var(--shadow-md);
    transform: translateY(100%);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 1000;
    max-width: 400px;
}

.notification.show {
    transform: translateY(0);
    opacity: 1;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.notification.success i { color: var(--success-color); }
.notification.error i { color: var(--danger-color); }
.notification.warning i { color: var(--warning-color); }
.notification.info i { color: var(--primary-color); }

.tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

[data-tooltip]:hover .tooltip {
    opacity: 1;
}

.error {
    border-color: var(--danger-color) !important;
}

/* Estilos para modo oscuro */
.dark-mode {
    --background-color: #1a1a1a;
    --card-background: #2d2d2d;
    --text-primary: #ffffff;
    --text-secondary: #a0aec0;
    --border-color: #404040;
    --hover-bg: #333333;
}
</style>