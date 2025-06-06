/**
 * Theme Configuration Manager
 * Permite cambiar dinámicamente la configuración visual del sistema
 */
class ThemeConfigManager {
    constructor() {
        // Referencias DOM
        this.sidebar = document.getElementById('sidebar');
        this.topBar = document.getElementById('topBar');
        
        // Estado actual
        this.currentConfig = {
            sidebarColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim(),
            sidebarTextColor: getComputedStyle(document.documentElement).getPropertyValue('--text-color').trim(),
            sidebarStyle: 'default',
            schoolName: document.querySelector('.logo span')?.textContent || 'Sistema Escolar',
            logoUrl: ''
        };
        
        // Configuración predeterminada (fallback)
        this.defaultConfig = {
            sidebarColor: '#1a2b40',
            sidebarTextColor: '#FFFFFF',
            sidebarStyle: 'default',
            schoolName: 'Sistema Escolar',
            logoUrl: ''
        };
        
        // Inicializar
        this.init();
    }
    
    /**
     * Inicializa el administrador de temas
     */
    init() {
        // Cargar configuración actual
        this.loadCurrentConfig();
        
        // Aplicar tema actual
        this.applyTheme(this.currentConfig);
    }
    
    /**
     * Carga la configuración actual desde las variables CSS
     */
    loadCurrentConfig() {
        // Las variables CSS ya están cargadas en el constructor
        console.log('Configuración actual cargada:', this.currentConfig);
    }
    
    /**
     * Abre el modal de configuración del tema
     */
    openConfigModal() {
        console.log('Abriendo modal de configuración');
        // Crear modal dinámicamente si no existe
        let configModal = document.getElementById('themeConfigModal');
        if (!configModal) {
            configModal = this.createConfigModal();
            document.body.appendChild(configModal);
        }
        
        // Actualizar valores en el modal con la configuración actual
        this.populateModalValues();
        
        // Mostrar modal
        configModal.style.display = 'flex';
        setTimeout(() => {
            configModal.classList.add('show');
        }, 10);
        
        // Enfocar primer elemento
        setTimeout(() => {
            const firstInput = configModal.querySelector('input, select');
            if (firstInput) firstInput.focus();
        }, 300);
    }
    
    /**
     * Cierra el modal de configuración
     */
    closeConfigModal() {
        const modal = document.getElementById('themeConfigModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }
    
    /**
     * Crea el modal de configuración del tema
     */
    createConfigModal() {
        const modal = document.createElement('div');
        modal.id = 'themeConfigModal';
        modal.className = 'theme-config-modal';
        
        modal.innerHTML = `
            <div class="modal-backdrop"></div>
            <div class="modal-container">
                <div class="modal-header">
                    <h2>Configuración de Apariencia</h2>
                    <button type="button" class="close-btn" aria-label="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="themeConfigForm">
                        <div class="config-section">
                            <h3>Información Institucional</h3>
                            
                            <div class="form-group">
                                <label for="schoolName">Nombre de la Institución</label>
                                <input type="text" id="schoolName" class="form-control" 
                                       placeholder="Nombre de la institución">
                            </div>
                            
                            <div class="form-group">
                                <label for="schoolLogo">Logo de la Institución</label>
                                <div class="logo-upload-container">
                                    <div class="current-logo" id="currentLogoPreview">
                                        <img id="logoPreview" src="#" alt="Logo de la institución">
                                        <div class="no-logo-text">
                                            <i class="fas fa-school"></i>
                                            <span>Sin logo</span>
                                        </div>
                                    </div>
                                    <div class="logo-upload-controls">
                                        <input type="file" id="schoolLogo" class="form-control-file" 
                                               accept="image/png, image/jpeg, image/gif" style="display:none;">
                                        <button type="button" id="uploadLogoBtn" class="btn btn-outline">
                                            <i class="fas fa-upload"></i> Subir nuevo logo
                                        </button>
                                        <button type="button" id="removeLogoBtn" class="btn btn-outline text-danger">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h3>Personalización del Tema</h3>
                            
                            <div class="form-group">
                                <label for="sidebarColor">Color principal</label>
                                <div class="color-picker-container">
                                    <input type="color" id="sidebarColor" class="form-control color-picker">
                                    <input type="text" id="sidebarColorText" class="form-control color-text" 
                                           placeholder="#RRGGBB">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sidebarTextColor">Color de texto</label>
                                <div class="color-picker-container">
                                    <input type="color" id="sidebarTextColor" class="form-control color-picker">
                                    <input type="text" id="sidebarTextColorText" class="form-control color-text" 
                                           placeholder="#RRGGBB">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sidebarStyle">Estilo de degradado</label>
                                <select id="sidebarStyle" class="form-control">
                                    <option value="default">Estándar (Superior a Inferior)</option>
                                    <option value="gradient">Diagonal (Esquina a Esquina)</option>
                                    <option value="flat">Sólido (Sin Degradado)</option>
                                </select>
                            </div>
                            
                            <div class="theme-preview" id="themePreview">
                                <div class="preview-sidebar">
                                    <div class="preview-header">
                                        <div class="preview-logo">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>Logo</span>
                                        </div>
                                    </div>
                                    <div class="preview-nav">
                                        <div class="preview-nav-item active"></div>
                                        <div class="preview-nav-item"></div>
                                        <div class="preview-nav-item"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelConfigBtn">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveConfigBtn">Guardar cambios</button>
                </div>
            </div>
        `;
        
        // Agregar event listeners
        setTimeout(() => {
            // Cerrar modal
            const closeBtn = modal.querySelector('.close-btn');
            const backdrop = modal.querySelector('.modal-backdrop');
            const cancelBtn = modal.querySelector('#cancelConfigBtn');
            
            if (closeBtn) closeBtn.addEventListener('click', () => this.closeConfigModal());
            if (backdrop) backdrop.addEventListener('click', () => this.closeConfigModal());
            if (cancelBtn) cancelBtn.addEventListener('click', () => this.closeConfigModal());
            
            // Input de color y texto sincronizados
            const sidebarColor = modal.querySelector('#sidebarColor');
            const sidebarColorText = modal.querySelector('#sidebarColorText');
            const sidebarTextColor = modal.querySelector('#sidebarTextColor');
            const sidebarTextColorText = modal.querySelector('#sidebarTextColorText');
            
            if (sidebarColor && sidebarColorText) {
                sidebarColor.addEventListener('input', (e) => {
                    sidebarColorText.value = e.target.value;
                    this.updatePreview();
                });
                
                sidebarColorText.addEventListener('input', (e) => {
                    const colorValue = e.target.value;
                    if (/^#[0-9A-F]{6}$/i.test(colorValue)) {
                        sidebarColor.value = colorValue;
                        this.updatePreview();
                    }
                });
            }
            
            if (sidebarTextColor && sidebarTextColorText) {
                sidebarTextColor.addEventListener('input', (e) => {
                    sidebarTextColorText.value = e.target.value;
                    this.updatePreview();
                });
                
                sidebarTextColorText.addEventListener('input', (e) => {
                    const colorValue = e.target.value;
                    if (/^#[0-9A-F]{6}$/i.test(colorValue)) {
                        sidebarTextColor.value = colorValue;
                        this.updatePreview();
                    }
                });
            }
            
            // Cambio de estilo
            const sidebarStyle = modal.querySelector('#sidebarStyle');
            if (sidebarStyle) {
                sidebarStyle.addEventListener('change', () => this.updatePreview());
            }
            
            // Manejo de logo
            const uploadLogoBtn = modal.querySelector('#uploadLogoBtn');
            const removeLogoBtn = modal.querySelector('#removeLogoBtn');
            const schoolLogo = modal.querySelector('#schoolLogo');
            
            if (uploadLogoBtn && schoolLogo) {
                uploadLogoBtn.addEventListener('click', () => {
                    schoolLogo.click();
                });
            }
            
            if (schoolLogo) {
                schoolLogo.addEventListener('change', (e) => {
                    if (e.target.files && e.target.files[0]) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const logoPreview = modal.querySelector('#logoPreview');
                            if (logoPreview) {
                                logoPreview.src = e.target.result;
                                logoPreview.style.display = 'block';
                                modal.querySelector('.no-logo-text').style.display = 'none';
                            }
                        };
                        reader.readAsDataURL(e.target.files[0]);
                    }
                });
            }
            
            if (removeLogoBtn) {
                removeLogoBtn.addEventListener('click', () => {
                    const logoPreview = modal.querySelector('#logoPreview');
                    const schoolLogo = modal.querySelector('#schoolLogo');
                    if (logoPreview) {
                        logoPreview.src = '#';
                        logoPreview.style.display = 'none';
                        modal.querySelector('.no-logo-text').style.display = 'flex';
                    }
                    if (schoolLogo) {
                        schoolLogo.value = '';
                    }
                });
            }
            
            // Guardar configuración
            const saveBtn = modal.querySelector('#saveConfigBtn');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => this.saveConfig());
            }
            
        }, 100);
        
        return modal;
    }
    
    /**
     * Actualiza la vista previa del tema
     */
    updatePreview() {
        const preview = document.getElementById('themePreview');
        if (!preview) return;
        
        const sidebarColorValue = document.getElementById('sidebarColor').value;
        const textColorValue = document.getElementById('sidebarTextColor').value;
        const styleValue = document.getElementById('sidebarStyle').value;
        
        // Calcular color secundario
        const secondaryColor = this.adjustBrightness(sidebarColorValue, 30);
        
        // Aplicar estilo
        const previewSidebar = preview.querySelector('.preview-sidebar');
        
        if (styleValue === 'flat') {
            previewSidebar.style.background = sidebarColorValue;
        } else if (styleValue === 'gradient') {
            previewSidebar.style.background = `linear-gradient(135deg, ${sidebarColorValue}, ${secondaryColor})`;
        } else {
            previewSidebar.style.background = `linear-gradient(to bottom, ${sidebarColorValue}, ${secondaryColor})`;
        }
        
        // Cambiar color de texto
        previewSidebar.style.color = textColorValue;
    }
    
    /**
     * Llena el modal con los valores actuales
     */
    populateModalValues() {
        const modal = document.getElementById('themeConfigModal');
        if (!modal) return;
        
        // Llenar nombre
        const schoolNameInput = modal.querySelector('#schoolName');
        if (schoolNameInput) {
            schoolNameInput.value = this.currentConfig.schoolName;
        }
        
        // Llenar colores
        const sidebarColorInput = modal.querySelector('#sidebarColor');
        const sidebarColorText = modal.querySelector('#sidebarColorText');
        const sidebarTextColorInput = modal.querySelector('#sidebarTextColor');
        const sidebarTextColorText = modal.querySelector('#sidebarTextColorText');
        const sidebarStyleSelect = modal.querySelector('#sidebarStyle');
        
        if (sidebarColorInput && sidebarColorText) {
            sidebarColorInput.value = this.currentConfig.sidebarColor;
            sidebarColorText.value = this.currentConfig.sidebarColor;
        }
        
        if (sidebarTextColorInput && sidebarTextColorText) {
            sidebarTextColorInput.value = this.currentConfig.sidebarTextColor;
            sidebarTextColorText.value = this.currentConfig.sidebarTextColor;
        }
        
        if (sidebarStyleSelect) {
            sidebarStyleSelect.value = this.currentConfig.sidebarStyle;
        }
        
        // Mostrar logo si existe
        const logoPreview = modal.querySelector('#logoPreview');
        const noLogoText = modal.querySelector('.no-logo-text');
        
        if (logoPreview && noLogoText) {
            if (this.currentConfig.logoUrl) {
                logoPreview.src = this.currentConfig.logoUrl;
                logoPreview.style.display = 'block';
                noLogoText.style.display = 'none';
            } else {
                logoPreview.style.display = 'none';
                noLogoText.style.display = 'flex';
            }
        }
        
        // Actualizar vista previa
        this.updatePreview();
    }
    
    /**
     * Guarda la configuración del tema
     */
    saveConfig() {
        const modal = document.getElementById('themeConfigModal');
        if (!modal) return;
        
        // Obtener valores del formulario
        const formData = new FormData();
        formData.append('action', 'update_theme');
        formData.append('school_name', modal.querySelector('#schoolName').value);
        formData.append('sidebar_color', modal.querySelector('#sidebarColor').value);
        formData.append('sidebar_text_color', modal.querySelector('#sidebarTextColor').value);
        formData.append('sidebar_style', modal.querySelector('#sidebarStyle').value);
        
        // Logo si se seleccionó un archivo
        const logoInput = modal.querySelector('#schoolLogo');
        if (logoInput && logoInput.files && logoInput.files[0]) {
            formData.append('school_logo', logoInput.files[0]);
        }
        
        // Crear objeto temporal con configuración actual
        const newConfig = {
            schoolName: modal.querySelector('#schoolName').value,
            sidebarColor: modal.querySelector('#sidebarColor').value,
            sidebarTextColor: modal.querySelector('#sidebarTextColor').value,
            sidebarStyle: modal.querySelector('#sidebarStyle').value,
            logoUrl: this.currentConfig.logoUrl
        };
        
        // Verificar si hay un logo seleccionado
        if (logoInput && logoInput.files && logoInput.files[0]) {
            // Crear una URL temporal para la vista previa
            const tempUrl = URL.createObjectURL(logoInput.files[0]);
            newConfig.logoUrl = tempUrl;
        }
        
        // Mostrar loader
        this.showLoader('Guardando configuración...');
        
        console.log('Enviando configuración al servidor...');
        
        // Enviar al servidor
        fetch('/school_management/admin/update_settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Respuesta recibida:', response.status);
            return response.text().then(text => {
                // Primero imprimir el texto para depuración
                console.log('Respuesta completa:', text);
                
                try {
                    // Intentar parsear como JSON
                    return JSON.parse(text);
                } catch (e) {
                    // Si no es JSON válido, lanzar error con el texto original
                    throw new Error('Respuesta no válida: ' + text);
                }
            });
        })
        .then(data => {
            if (data.success) {
                console.log('Configuración guardada exitosamente');
                // Actualizar configuración local
                this.currentConfig = newConfig;
                
                // Aplicar cambios
                this.applyTheme(this.currentConfig);
                
                // Cerrar modal
                this.closeConfigModal();
                
                // Mostrar notificación de éxito
                this.showNotification('Configuración guardada correctamente', 'success');
            } else {
                throw new Error(data.message || 'Error al guardar la configuración');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('Error al guardar la configuración: ' + error.message, 'error');
        })
        .finally(() => {
            this.hideLoader();
        });
    }
    
    /**
     * Aplica el tema con la configuración proporcionada
     * @param {Object} config - Configuración del tema
     */
    applyTheme(config) {
        // Aplicar colores a variables CSS
        document.documentElement.style.setProperty('--primary-color', config.sidebarColor);
        document.documentElement.style.setProperty('--text-color', config.sidebarTextColor);
        
        // Calcular color secundario
        const secondaryColor = this.adjustBrightness(config.sidebarColor, 30);
        document.documentElement.style.setProperty('--secondary-color', secondaryColor);
        
        // Calcular color de texto muted
        const textMuted = this.adjustBrightness(config.sidebarTextColor, -30);
        document.documentElement.style.setProperty('--text-muted', textMuted);
        
        // Aplicar estilo de sidebar
        if (this.sidebar) {
            this.sidebar.classList.remove('flat', 'gradient', 'default');
            this.sidebar.classList.add(config.sidebarStyle);
        }
        
        // Actualizar nombre de la institución
        const logoSpan = document.querySelector('.logo span');
        if (logoSpan) {
            logoSpan.textContent = config.schoolName;
        }
        
        // Actualizar logo si existe
        if (config.logoUrl) {
            // Buscar el contenedor del logo
            const logoContainer = document.querySelector('.logo');
            
            // Verificar si ya existe una imagen de logo
            let logoImg = logoContainer.querySelector('img.school-logo');
            
            if (!logoImg) {
                // Crear imagen de logo
                logoImg = document.createElement('img');
                logoImg.className = 'school-logo';
                logoImg.alt = 'Logo institucional';
                
                // Ocultar ícono predeterminado
                const defaultIcon = logoContainer.querySelector('i');
                if (defaultIcon) {
                    defaultIcon.style.display = 'none';
                }
                
                // Insertar imagen antes del texto
                logoContainer.insertBefore(logoImg, logoContainer.querySelector('span'));
            }
            
            // Actualizar fuente de la imagen
            logoImg.src = config.logoUrl;
        }
    }
    
    /**
     * Ajusta el brillo de un color hexadecimal
     * @param {string} hex - Color en formato hexadecimal (#RRGGBB)
     * @param {number} steps - Pasos para ajustar brillo (positivo = más claro, negativo = más oscuro)
     * @returns {string} Color ajustado en formato hexadecimal
     */
    adjustBrightness(hex, steps) {
        // Eliminar # si está presente
        hex = hex.replace('#', '');
        
        // Convertir a RGB
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        
        // Ajustar brillo
        const newR = Math.max(0, Math.min(255, r + steps));
        const newG = Math.max(0, Math.min(255, g + steps));
        const newB = Math.max(0, Math.min(255, b + steps));
        
        // Convertir de vuelta a hex
        return `#${newR.toString(16).padStart(2, '0')}${newG.toString(16).padStart(2, '0')}${newB.toString(16).padStart(2, '0')}`;
    }
    
    /**
     * Muestra un loader durante operaciones asíncronas
     * @param {string} message - Mensaje a mostrar
     */
    showLoader(message = 'Cargando...') {
        let loader = document.getElementById('systemLoader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'systemLoader';
            loader.className = 'system-loader';
            loader.innerHTML = `
                <div class="loader-backdrop"></div>
                <div class="loader-content">
                    <div class="spinner"></div>
                    <p class="loader-message">${message}</p>
                </div>
            `;
            document.body.appendChild(loader);
        } else {
            loader.querySelector('.loader-message').textContent = message;
            loader.style.display = 'flex';
        }
    }
    
    /**
     * Oculta el loader
     */
    hideLoader() {
        const loader = document.getElementById('systemLoader');
        if (loader) {
            loader.style.display = 'none';
        }
    }
    
    /**
     * Muestra una notificación
     * @param {string} message - Mensaje de la notificación
     * @param {string} type - Tipo de notificación ('success', 'error', 'warning', 'info')
     */
    showNotification(message, type = 'info') {
        // Crear contenedor de notificaciones si no existe
        let notifContainer = document.getElementById('notificationContainer');
        if (!notifContainer) {
            notifContainer = document.createElement('div');
            notifContainer.id = 'notificationContainer';
            notifContainer.className = 'notification-container';
            document.body.appendChild(notifContainer);
        }
        
        // Crear notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Icono según tipo
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'error') icon = 'times-circle';
        if (type === 'warning') icon = 'exclamation-triangle';
        
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="notification-content">
                <span class="notification-message">${message}</span>
            </div>
            <button type="button" class="notification-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Agregar a contenedor
        notifContainer.appendChild(notification);
        
        // Mostrar con animación
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto cerrar después de 5 segundos
        const closeTimeout = setTimeout(() => {
            this.closeNotification(notification);
        }, 5000);
        
        // Mantener abierto al pasar el mouse
        notification.addEventListener('mouseenter', () => {
            clearTimeout(closeTimeout);
        });
        
        // Cerrar al volver a salir el mouse después de 2 segundos
        notification.addEventListener('mouseleave', () => {
            setTimeout(() => {
                this.closeNotification(notification);
            }, 2000);
        });
        
        // Botón de cerrar
        const closeBtn = notification.querySelector('.notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.closeNotification(notification);
            });
        }
    }
    
    /**
     * Cierra una notificación con animación
     * @param {HTMLElement} notification - Elemento de notificación
     */
    closeNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}

// Exponer ThemeConfigManager globalmente
window.ThemeConfigManager = ThemeConfigManager;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('Inicializando ThemeConfigManager...');
    
    try {
        // Crear instancia global del administrador de temas
        window.themeManager = new ThemeConfigManager();
        console.log('ThemeConfigManager inicializado correctamente');
        
        // Configurar el botón de toggle
        const themeConfigBtn = document.getElementById('themeConfigBtn');
        if (themeConfigBtn) {
            console.log('Configurando botón de theme config');
            themeConfigBtn.addEventListener('click', () => {
                console.log('Botón clickeado, abriendo modal...');
                window.themeManager.openConfigModal();
            });
        } else {
            console.warn('No se encontró el botón de configuración de tema');
        }
    } catch (error) {
        console.error('Error al inicializar ThemeConfigManager:', error);
        alert('Hubo un error al cargar el configurador de tema. Por favor, revise la consola para más detalles.');
    }
});