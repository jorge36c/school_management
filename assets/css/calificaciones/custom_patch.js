/**
 * Script de corrección inmediata para la pantalla de calificaciones
 * Este script debe incluirse en la cabecera del HTML para arreglar problemas visuales
 * antes de que la página termine de cargar
 */
document.addEventListener('DOMContentLoaded', function() {
    // Comprobar y corregir problemas de estructura
    fixStructuralIssues();
    
    // Corregir problemas de estilos de sidebar y topbar
    fixSidebarTopbarIssues();
    
    // Verificar que los archivos CSS y JS están cargados correctamente
    verifyAssets();
    
    // Actualizar el DOM para mantener la consistencia
    updateDOMConsistency();
    
    /**
     * Corrige problemas estructurales en la página
     */
    function fixStructuralIssues() {
        // Corregir problema de header-actions faltante
        const headerContent = document.querySelector('.header-content');
        if (headerContent && !headerContent.querySelector('.header-actions')) {
            const headerActions = document.createElement('div');
            headerActions.className = 'header-actions';
            
            // Botón de exportar
            const exportBtn = document.createElement('button');
            exportBtn.className = 'btn btn-primary';
            exportBtn.id = 'exportBtn';
            exportBtn.innerHTML = '<i class="fas fa-file-export"></i> Exportar';
            
            // Añadir a header actions
            headerActions.appendChild(exportBtn);
            
            // Si hay período activo, añadir botón de informes
            const periodoActivo = document.querySelector('.badge-primary');
            if (periodoActivo) {
                const informesBtn = document.createElement('a');
                informesBtn.href = '../reportes/generar_informe.php';
                informesBtn.className = 'btn btn-secondary';
                informesBtn.innerHTML = '<i class="fas fa-chart-line"></i> Informes';
                headerActions.appendChild(informesBtn);
            }
            
            // Añadir al header
            headerContent.appendChild(headerActions);
        }
        
        // Asegurar que el botón exportar tenga event listener
        if (document.getElementById('exportBtn')) {
            document.getElementById('exportBtn').addEventListener('click', function() {
                const exportModal = document.getElementById('exportModal');
                if (exportModal) {
                    exportModal.style.display = 'block';
                }
            });
        }
    }
    
    /**
     * Corrige problemas de sidebar y topbar
     */
    function fixSidebarTopbarIssues() {
        // Agregar clase activa al menú calificaciones
        const menuCalificaciones = document.querySelector('.sidebar-menu a[href*="calificaciones"]');
        if (menuCalificaciones) {
            menuCalificaciones.classList.add('active');
            
            // Encontrar el elemento padre li y marcarlo como activo
            const parentLi = menuCalificaciones.closest('li');
            if (parentLi) {
                parentLi.classList.add('active');
            }
        }
        
        // Corregir problemas con topbar
        const topbar = document.querySelector('.topbar');
        if (topbar && window.innerWidth < 768) {
            topbar.style.position = 'sticky';
        }
    }
    
    /**
     * Verifica que los assets necesarios estén cargados
     */
    function verifyAssets() {
        // Lista de archivos CSS que deben estar cargados
        const requiredCSS = [
            '../../assets/css/components/dashboard.css',
            '../../assets/css/components/sidebar.css',
            '../../assets/css/components/topbar.css',
            '../../assets/css/calificaciones/lista_calificaciones.css',
            '../../assets/css/calificaciones/lista_calificaciones_vista.css'
        ];
        
        // Lista de archivos JS que deben estar cargados
        const requiredJS = [
            '../../assets/js/components/sidebar.js',
            '../../assets/js/components/topbar.js',
            '../../assets/js/calificaciones/lista_calificaciones.js',
            '../../assets/js/calificaciones/grupos_view_controller.js'
        ];
        
        // Verificar CSS
        let allCSSLoaded = true;
        requiredCSS.forEach(cssFile => {
            const isLoaded = Array.from(document.styleSheets).some(styleSheet => {
                try {
                    return styleSheet.href && styleSheet.href.includes(cssFile.replace('../../', ''));
                } catch (e) {
                    return false;
                }
            });
            
            if (!isLoaded) {
                allCSSLoaded = false;
                console.warn(`CSS file not loaded: ${cssFile}`);
                
                // Intentar cargar el CSS faltante
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = cssFile;
                document.head.appendChild(link);
            }
        });
        
        // Verificar JS
        let allJSLoaded = true;
        requiredJS.forEach(jsFile => {
            const scripts = Array.from(document.scripts);
            const isLoaded = scripts.some(script => script.src && script.src.includes(jsFile.replace('../../', '')));
            
            if (!isLoaded) {
                allJSLoaded = false;
                console.warn(`JS file not loaded: ${jsFile}`);
                
                // Intentar cargar el JS faltante (asíncrono para no bloquear)
                const script = document.createElement('script');
                script.src = jsFile;
                script.async = true;
                document.body.appendChild(script);
            }
        });
        
        // Si faltan assets, mostrar un mensaje al desarrollador
        if (!allCSSLoaded || !allJSLoaded) {
            console.warn('Some required assets are missing. Using fallback styles and scripts.');
        }
    }
    
    /**
     * Actualiza el DOM para mantener consistencia
     */
    function updateDOMConsistency() {
        // Corregir clases dinámicas
        document.querySelectorAll('.grupo-card').forEach(card => {
            // Asegurar que todos los elementos necesarios existan
            if (!card.querySelector('.grupo-header')) {
                const header = document.createElement('div');
                header.className = 'grupo-header';
                card.prepend(header);
            }
            
            if (!card.querySelector('.grupo-content')) {
                const content = document.createElement('div');
                content.className = 'grupo-content';
                card.appendChild(content);
            }
            
            // Arreglar datos de nivel y sede
            if (!card.hasAttribute('data-nivel') && card.querySelector('.nivel-badge')) {
                const nivelText = card.querySelector('.nivel-badge').textContent.trim().toLowerCase();
                card.setAttribute('data-nivel', nivelText);
            }
            
            if (!card.hasAttribute('data-sede') && card.querySelector('.sede-nombre')) {
                card.setAttribute('data-sede', card.querySelector('.sede-nombre').textContent.trim());
            }
        });
        
        // Verificar que existe la tabla para vista de lista
        const gruposContainer = document.getElementById('gruposContainer');
        if (gruposContainer && !gruposContainer.querySelector('.grupos-table')) {
            // Crear tabla para vista de lista
            createListViewTable();
        }
        
        // Verificar y corregir elementos vacíos
        if (!document.getElementById('emptyResults')) {
            createEmptyResultsElement();
        }
    }
    
    /**
     * Crea el elemento de tabla para vista de lista
     */
    function createListViewTable() {
        const gruposContainer = document.getElementById('gruposContainer');
        if (!gruposContainer) return;
        
        const grupos = gruposContainer.querySelectorAll('.grupo-card');
        if (grupos.length === 0) return;
        
        // Crear tabla
        const table = document.createElement('table');
        table.className = 'grupos-table';
        table.style.display = 'none';
        
        // Crear encabezado
        const thead = document.createElement('thead');
        thead.innerHTML = `
            <tr>
                <th>Grado</th>
                <th>Materia</th>
                <th>Sede</th>
                <th>Estudiantes</th>
                <th>Progreso</th>
                <th>Acciones</th>
            </tr>
        `;
        table.appendChild(thead);
        
        // Crear cuerpo
        const tbody = document.createElement('tbody');
        
        // Rellenar con datos de las tarjetas
        grupos.forEach(grupo => {
            const tr = document.createElement('tr');
            
            // Determine if it's multigrado
            const esMultigrado = grupo.classList.contains('multigrado');
            tr.setAttribute('data-multigrado', esMultigrado ? 'true' : 'false');
            
            const gradoNombre = grupo.querySelector('h3').textContent.trim();
            const materiaNombre = grupo.querySelector('.materia').textContent.trim();
            const sedeNombre = grupo.getAttribute('data-sede') || 'N/A';
            const nivel = grupo.getAttribute('data-nivel') || '';
            
            // Buscar datos de estudiantes
            let totalEstudiantes = '0';
            let estudiantesCalificados = '0';
            let porcentaje = 0;
            
            const statValue = grupo.querySelector('.stat-value');
            if (statValue) {
                const estudiantesText = statValue.textContent.trim();
                const match = estudiantesText.match(/(\d+)\s+de\s+(\d+)/);
                if (match) {
                    estudiantesCalificados = match[1];
                    totalEstudiantes = match[2];
                    
                    porcentaje = totalEstudiantes > 0 ? 
                        (parseInt(estudiantesCalificados) / parseInt(totalEstudiantes) * 100) : 0;
                }
            }
            
            // Determinar clase de progreso
            let progressClass = 'progress-low';
            if (porcentaje > 60) {
                progressClass = 'progress-high';
            } else if (porcentaje > 30) {
                progressClass = 'progress-medium';
            }
            
            // Get nivel color
            let nivelColor = '#6c5ce7';
            if (nivel === 'preescolar') nivelColor = '#4ECDC4';
            if (nivel === 'primaria') nivelColor = '#FF6B6B';
            if (nivel === 'secundaria') nivelColor = '#1A535C';
            if (nivel === 'media') nivelColor = '#FFE66D';
            
            // Get nivel icon
            let nivelIcon = 'fas fa-user-graduate';
            if (nivel === 'preescolar') nivelIcon = 'fas fa-baby';
            if (nivel === 'primaria') nivelIcon = 'fas fa-child';
            if (nivel === 'secundaria') nivelIcon = 'fas fa-user-graduate';
            if (nivel === 'media') nivelIcon = 'fas fa-user-tie';
            
            // Crear celdas
            tr.innerHTML = `
                <td>
                    <div class="cell-with-badge">
                        <span class="nivel-pill" style="background-color: ${nivelColor}">
                            <i class="${nivelIcon}"></i>
                        </span>
                        ${gradoNombre}
                        ${esMultigrado ? '<span class="table-badge badge-info">Multigrado</span>' : ''}
                    </div>
                </td>
                <td>${materiaNombre}</td>
                <td>${sedeNombre}</td>
                <td>
                    <span>${estudiantesCalificados} de ${totalEstudiantes}</span>
                </td>
                <td>
                    <div class="table-progress">
                        <div class="table-progress-bar ${progressClass}" 
                            style="width: ${porcentaje}%">
                        </div>
                        <span class="compact-percent">${Math.round(porcentaje)}%</span>
                    </div>
                </td>
                <td>
                    <div class="table-actions">
                        ${getActionButtonHTML(grupo, esMultigrado)}
                    </div>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
        
        table.appendChild(tbody);
        gruposContainer.appendChild(table);
    }
    
    /**
     * Obtiene el HTML del botón de acción según el tipo de grupo
     */
    function getActionButtonHTML(grupo, esMultigrado) {
        const actionBtn = grupo.querySelector('.btn-action');
        if (!actionBtn) return '';
        
        const href = actionBtn.getAttribute('href') || '#';
        const icon = actionBtn.querySelector('i')?.className || 'fas fa-list-check';
        
        return `
            <a href="${href}" class="btn-action">
                <i class="${icon}"></i>
                <span>Calificar</span>
            </a>
        `;
    }
    
    /**
     * Crea el elemento para mostrar cuando no hay resultados
     */
    function createEmptyResultsElement() {
        const emptyResults = document.createElement('div');
        emptyResults.id = 'emptyResults';
        emptyResults.className = 'empty-state';
        emptyResults.style.display = 'none';
        
        emptyResults.innerHTML = `
            <i class="fas fa-search"></i>
            <h3>No se encontraron grupos</h3>
            <p>No hay grupos que coincidan con los filtros seleccionados. Intenta con diferentes criterios de búsqueda.</p>
            <button id="clearFilters" class="btn-action" style="max-width: 200px;">
                <i class="fas fa-times"></i> Limpiar filtros
            </button>
        `;
        
        // Añadir después del contenedor de grupos
        const gruposContainer = document.getElementById('gruposContainer');
        if (gruposContainer) {
            gruposContainer.parentNode.insertBefore(emptyResults, gruposContainer.nextSibling);
            
            // Añadir event listener al botón limpiar filtros
            document.getElementById('clearFilters').addEventListener('click', function() {
                const searchInput = document.getElementById('searchGrupo');
                const filterNivel = document.getElementById('filterNivel');
                const filterSede = document.getElementById('filterSede');
                
                if (searchInput) searchInput.value = '';
                if (filterNivel) filterNivel.value = '';
                if (filterSede) filterSede.value = '';
                
                // Disparar evento input para actualizar resultados
                if (searchInput) searchInput.dispatchEvent(new Event('input'));
                if (filterNivel) filterNivel.dispatchEvent(new Event('change'));
            });
        }
    }
});