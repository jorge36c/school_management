/**
 * Ver Estudiantes JS - Script para la gestión de calificaciones
 * 
 * Maneja la interfaz de calificaciones, incluyendo:
 * - Cambio entre vistas de tabla y tarjetas
 * - Gestión de calificaciones
 * - Gestión de tipos de notas
 * - Interacción con el servidor
 * 
 * @version 3.0
 */

// Variables globales
let pendingChanges = new Map();
let tiposNotas = {};
let totalesPorCategoria = {};
let saveTimeout;
let toastTimeout;
let cropper = null;
let currentFileInput = null;
let currentEstudianteId = null;

// Al cargar el documento
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initViewToggles();
    initGradeInputs();
    initTiposNotasModal();
    initExpandableNames();
    initAutoSave();
    initPhotoManagement();
    initCropperModal();
});

/**
 * Inicializa los cambios de vista (tabla/tarjetas/fotos)
 */
function initViewToggles() {
    const viewMode = localStorage.getItem('calificaciones_view') || 'table';
    const viewButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Establecer vista inicial
    setView(viewMode);
    
    // Manejar cambios de vista
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            setView(view);
        });
    });
    
    function setView(view) {
        // Actualizar botones
        viewButtons.forEach(button => {
            button.classList.toggle('active', button.dataset.view === view);
        });
        
        // Actualizar contenido
        tabContents.forEach(content => {
            content.classList.toggle('active', content.id === `${view}View`);
        });
        
        // Guardar preferencia
        localStorage.setItem('calificaciones_view', view);
    }
}

/**
 * Inicializa los eventos para los inputs de calificaciones
 */
function initGradeInputs() {
    const gradeInputs = document.querySelectorAll('.grade-input, .nota-input');
    
    gradeInputs.forEach(input => {
        // Evento de cambio
        input.addEventListener('change', function(e) {
            handleGradeChange(e);
        });
        
        // Eventos de focus/blur para destacar la fila
        input.addEventListener('focus', function(e) {
            highlightRow(e.target, true);
        });
        
        input.addEventListener('blur', function(e) {
            highlightRow(e.target, false);
        });
    });
}

/**
 * Inicializa la gestión de fotografías de estudiantes
 */
function initPhotoManagement() {
    // Manejar click en botones de subir foto
    document.querySelectorAll('.photo-upload-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const fileInput = this.closest('.photo-upload-form').querySelector('.file-input');
            if (fileInput) {
                fileInput.click();
            }
        });
    });

    // Manejar cambios en los inputs de archivo
    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Verificar tipo y tamaño de archivo
            if (!file.type.match('image.*')) {
                showToast('Por favor seleccione un archivo de imagen', 'error');
                return;
            }
            
            if (file.size > 1024 * 1024 * 5) { // 5MB
                showToast('La imagen debe ser menor a 5MB', 'error');
                return;
            }
            
            currentFileInput = this;
            currentEstudianteId = this.id.split('-')[1];
            
            // Crear URL para la imagen
            const imgURL = URL.createObjectURL(file);
            
            // Mostrar el modal para recortar
            const cropperImage = document.getElementById('cropperImage');
            const cropperModal = document.getElementById('cropperModal');
            
            if (cropperImage && cropperModal) {
                cropperImage.src = imgURL;
                cropperModal.style.display = 'flex';
                
                // Inicializar el cropper después de que la imagen se haya cargado
                cropperImage.onload = function() {
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(cropperImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        minContainerWidth: 300,
                        minContainerHeight: 300,
                        guides: true,
                        center: true,
                        autoCropArea: 0.8,
                        movable: true,
                        zoomable: true,
                        background: true,
                        responsive: true,
                        cropBoxResizable: true,
                        cropBoxMovable: true
                    });
                };
            }
        });
    });

    // Manejar click en botones de eliminar foto
    document.querySelectorAll('.photo-remove-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const estudianteId = this.dataset.id;
            
            if (confirm('¿Está seguro que desea eliminar la foto?')) {
                deleteStudentPhoto(estudianteId);
            }
        });
    });
}

/**
 * Inicializa el modal para recortar fotos
 */
function initCropperModal() {
    const cropperModal = document.getElementById('cropperModal');
    const btnCropImage = document.getElementById('btnCropImage');
    
    if (!cropperModal || !btnCropImage) return;
    
    // Cerrar modal
    cropperModal.querySelector('.close').addEventListener('click', function() {
        cropperModal.style.display = 'none';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    });
    
    // También cerrar al hacer clic fuera
    cropperModal.addEventListener('click', function(e) {
        if (e.target === cropperModal) {
            cropperModal.style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }
    });
    
    // Manejar el recorte y envío de la imagen
    btnCropImage.addEventListener('click', function() {
        if (!cropper) return;
        
        // Obtener la imagen recortada como blob
        cropper.getCroppedCanvas({
            width: 300,
            height: 300,
            fillColor: '#fff'
        }).toBlob((blob) => {
            // Crear un archivo a partir del blob
            const file = new File([blob], `estudiante_${currentEstudianteId}_photo.jpg`, { type: 'image/jpeg' });
            
            // Crear FormData y subir imagen
            const formData = new FormData();
            formData.append('estudiante_id', currentEstudianteId);
            formData.append('foto', file);
            
            showLoading(true);
            
            fetch('subir_foto_estudiante.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                
                if (data.success) {
                    // Cerrar el modal del cropper
                    cropperModal.style.display = 'none';
                    cropper.destroy();
                    cropper = null;
                    
                    // Actualizar la vista previa de la imagen
                    updateStudentPhoto(currentEstudianteId, data.foto_url);
                    
                    showToast('Foto subida correctamente');
                } else {
                    showToast(data.message || 'Error al subir la foto', 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            });
        }, 'image/jpeg', 0.95); // 95% de calidad
    });
}

/**
 * Actualiza la foto de un estudiante en todas las vistas
 */
function updateStudentPhoto(estudianteId, fotoUrl) {
    const timestamp = new Date().getTime();
    
    // Actualizar en la pestaña de fotos
    const photoContainer = document.querySelector(`.photo-upload-card[data-student-id="${estudianteId}"] .photo-preview`);
    
    if (photoContainer) {
        // Verificar si ya existe una imagen
        let imgElement = photoContainer.querySelector('img');
        if (!imgElement) {
            // Si no existe, limpiar el contenido (iniciales) y crear una imagen
            photoContainer.innerHTML = '';
            imgElement = document.createElement('img');
            imgElement.id = `preview-${estudianteId}`;
            imgElement.alt = 'Foto de estudiante';
            photoContainer.appendChild(imgElement);
        }
        
        // Actualizar la imagen con la nueva URL
        imgElement.src = fotoUrl + '?t=' + timestamp;
        
        // Mostrar botón de eliminar
        const removeBtn = document.querySelector(`.photo-upload-card[data-student-id="${estudianteId}"] .photo-remove-btn`);
        if (removeBtn) {
            removeBtn.style.display = 'inline-block';
        }
    }
    
    // Actualizar en la vista de tabla
    const tableCellPhoto = document.querySelector(`tr[data-student-id="${estudianteId}"] .student-photo`);
    if (tableCellPhoto) {
        tableCellPhoto.innerHTML = `<img src="${fotoUrl}?t=${timestamp}" alt="Foto de estudiante">`;
    }
    
    // Actualizar en la vista de tarjetas
    const cardPhoto = document.querySelector(`.student-card[data-student-id="${estudianteId}"] .student-photo`);
    if (cardPhoto) {
        cardPhoto.innerHTML = `<img src="${fotoUrl}?t=${timestamp}" alt="Foto de estudiante">`;
    }
}

/**
 * Elimina la foto de un estudiante
 */
function deleteStudentPhoto(estudianteId) {
    showLoading(true);
    
    fetch('eliminar_foto_estudiante.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ estudiante_id: estudianteId })
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        
        if (data.success) {
            // Obtener las iniciales del estudiante
            const nombreCompleto = document.querySelector(`.photo-upload-card[data-student-id="${estudianteId}"] .photo-upload-header`).textContent.trim();
            const iniciales = extraerIniciales(nombreCompleto);
            
            // Actualizar todas las vistas para mostrar iniciales en lugar de foto
            resetStudentPhoto(estudianteId, iniciales);
            
            // Ocultar botón de eliminar
            const removeBtn = document.querySelector(`.photo-upload-card[data-student-id="${estudianteId}"] .photo-remove-btn`);
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
            
            showToast('Foto eliminada correctamente');
        } else {
            showToast(data.message || 'Error al eliminar la foto', 'error');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    });
}

/**
 * Resetea la foto de un estudiante a iniciales en todas las vistas
 */
function resetStudentPhoto(estudianteId, iniciales) {
    // Actualizar en la pestaña de fotos
    const photoContainer = document.querySelector(`.photo-upload-card[data-student-id="${estudianteId}"] .photo-preview`);
    if (photoContainer) {
        photoContainer.innerHTML = `<div class="photo-iniciales">${iniciales}</div>`;
    }
    
    // Actualizar en la vista de tabla
    const tableCellPhoto = document.querySelector(`tr[data-student-id="${estudianteId}"] .student-photo`);
    if (tableCellPhoto) {
        tableCellPhoto.textContent = iniciales;
    }
    
    // Actualizar en la vista de tarjetas
    const cardPhoto = document.querySelector(`.student-card[data-student-id="${estudianteId}"] .student-photo`);
    if (cardPhoto) {
        cardPhoto.textContent = iniciales;
    }
}

/**
 * Extrae las iniciales de un nombre completo
 */
function extraerIniciales(nombre) {
    const partes = nombre.split(',');
    if (partes.length > 1) {
        const apellido = partes[0].trim();
        const nombre = partes[1].trim();
        return (apellido.charAt(0) + nombre.charAt(0)).toUpperCase();
    } else {
        const palabras = nombre.split(' ');
        return palabras.length > 1 
            ? (palabras[0].charAt(0) + palabras[1].charAt(0)).toUpperCase()
            : palabras[0].charAt(0).toUpperCase();
    }
}

/**
 * Inicializa la funcionalidad de expansión de nombres largos
 */
function initExpandableNames() {
    // Evitar tooltips automáticos en nombres de estudiantes
    document.querySelectorAll('.student-name').forEach(el => {
        if (el.title) {
            // Almacenar el título como un atributo de datos en lugar de title
            el.setAttribute('data-nombre-completo', el.title);
            el.removeAttribute('title');
        }
    });
    
    // Manejar hover en nombres para expandir sólo con la clase correcta
    document.querySelectorAll('.student-name').forEach(nameEl => {
        nameEl.addEventListener('mouseenter', function() {
            // Mostrar tooltip personalizado sólo si tiene clase .student-name
            if (this.classList.contains('student-name')) {
                this.classList.add('expanded');
            }
        });
        
        nameEl.addEventListener('mouseleave', function() {
            this.classList.remove('expanded');
        });
    });
    
    // Verificar nombres largos y marcarlos
    document.querySelectorAll('.student-name').forEach(nameEl => {
        // Si el contenido es más largo que el contenedor, marcarlo como nombre largo
        if (nameEl.scrollWidth > nameEl.clientWidth) {
            nameEl.classList.add('long-name');
        }
    });
}

/**
 * Configura el guardado automático de calificaciones
 */
function initAutoSave() {
    // Guardar automáticamente cada 30 segundos si hay cambios pendientes
    setInterval(() => {
        if (pendingChanges.size > 0) {
            saveChanges();
        }
    }, 30000);
}

/**
 * Maneja el cambio en un input de calificación
 */
function handleGradeChange(event) {
    const input = event.target;
    const value = parseFloat(input.value);
    
    // Validar el valor
    if (isNaN(value) || value < 0 || value > 5) {
        showToast('La calificación debe estar entre 0 y 5', 'error');
        input.value = '';
        return;
    }
    
    // Obtener información necesaria
    const estudianteId = input.dataset.estudianteId;
    const tipoNotaId = input.dataset.tipoNotaId;
    
    // Sincronizar el valor entre vistas de tabla y tarjetas
    document.querySelectorAll(`input[data-estudiante-id="${estudianteId}"][data-tipo-nota-id="${tipoNotaId}"]`).forEach(otherInput => {
        if (otherInput !== input) {
            otherInput.value = value;
        }
    });
    
    // Agregar a cambios pendientes
    pendingChanges.set(`${estudianteId}-${tipoNotaId}`, {
        estudiante_id: estudianteId,
        tipo_nota_id: tipoNotaId,
        valor: value
    });
    
    // Actualizar definitiva del estudiante
    updateStudentStatus(estudianteId);
    
    // Programar guardado con pequeño retraso
    if (saveTimeout) {
        clearTimeout(saveTimeout);
    }
    
    saveTimeout = setTimeout(() => {
        saveChanges();
    }, 3000);
}

/**
 * Resalta la fila cuando se edita
 */
function highlightRow(input, highlight) {
    const row = input.closest('tr, .student-card');
    if (row) {
        row.classList.toggle('editing', highlight);
    }
}

/**
 * Actualiza el estado visual de completitud y definitiva para un estudiante
 */
function updateStudentStatus(estudianteId) {
    // Encontrar todas las instancias del estudiante (en tabla y tarjetas)
    const elements = document.querySelectorAll(`[data-student-id="${estudianteId}"]`);
    
    // Recopilar calificaciones
    const calificaciones = {};
    elements.forEach(element => {
        const inputs = element.querySelectorAll('.grade-input, .nota-input');
        inputs.forEach(input => {
            if (input.value !== '') {
                calificaciones[input.dataset.tipoNotaId] = parseFloat(input.value);
            }
        });
    });
    
    // Calcular definitiva y estado de completitud
    const { definitiva, completo, porcentaje } = calculateStudentGrade(calificaciones);
    
    // Actualizar elementos UI
    elements.forEach(element => {
        // Actualizar definitiva
        const definitivaEl = element.querySelector('.final-grade, .card-grade');
        if (definitivaEl) {
            definitivaEl.textContent = definitiva;
            
            // Actualizar clase de color
            const colorClass = getGradeColorClass(parseFloat(definitiva));
            if (definitivaEl.classList.contains('final-grade')) {
                definitivaEl.className = 'final-grade ' + colorClass;
            } else if (definitivaEl.classList.contains('card-grade')) {
                definitivaEl.className = 'card-grade';
                // Para .card-grade, usar color de fondo basado en la clase
                if (colorClass === 'nota-excelente') definitivaEl.style.backgroundColor = '#10b981';
                else if (colorClass === 'nota-buena') definitivaEl.style.backgroundColor = '#3b82f6';
                else if (colorClass === 'nota-aceptable') definitivaEl.style.backgroundColor = '#4ade80';
                else if (colorClass === 'nota-baja') definitivaEl.style.backgroundColor = '#f59e0b';
                else if (colorClass === 'nota-reprobada') definitivaEl.style.backgroundColor = '#ef4444';
            }
        }
        
        // Actualizar estado de completitud
        const statusBadge = element.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.className = 'status-badge ' + (completo ? 'status-complete' : 'status-incomplete');
            statusBadge.innerHTML = `
                <i class="fas fa-${completo ? 'check-circle' : 'clock'}"></i>
                ${completo ? 'Completo' : 'Pendiente'}
            `;
        }
        
        // Actualizar barra de progreso en vista de tarjetas
        const progressFill = element.querySelector('.progress-fill');
        if (progressFill) {
            progressFill.style.width = `${porcentaje}%`;
        }
    });
}

/**
 * Calcula la definitiva y completitud para un estudiante
 */
function calculateStudentGrade(calificaciones) {
    // Obtener tipos de notas del DOM
    const tiposNotasDOM = {};
    
    // Para cada categoría, obtener los tipos de notas
    document.querySelectorAll('.nota-header').forEach(header => {
        const categoriaClass = Array.from(header.classList).find(c => 
            ['tareas', 'evaluaciones', 'autoevaluacion'].includes(c)
        );
        
        if (!categoriaClass) return;
        
        const categoria = categoriaClass.toUpperCase();
        const tipoId = header.querySelector('input') ? 
            header.querySelector('input').dataset.tipoNotaId : null;
        
        if (!tipoId) return;
        
        const tipoNota = {
            id: tipoId,
            nombre: header.querySelector('span').textContent.trim(),
            porcentaje: parseFloat(header.querySelector('small').textContent),
            categoria: categoria
        };
        
        if (!tiposNotasDOM[categoria]) {
            tiposNotasDOM[categoria] = [];
        }
        tiposNotasDOM[categoria].push(tipoNota);
    });
    
    // Cálculo para definitiva
    const resultados = {
        'TAREAS': { nota: 0, total: 0, porcentaje: 0 },
        'EVALUACIONES': { nota: 0, total: 0, porcentaje: 0 },
        'AUTOEVALUACION': { nota: 0, total: 0, porcentaje: 0 }
    };
    
    // Pesos de cada categoría
    const pesos = {
        'TAREAS': 0.4,
        'EVALUACIONES': 0.5,
        'AUTOEVALUACION': 0.1
    };
    
    // Contar tipos totales y con calificación
    let tiposTotales = 0;
    let tiposCalificados = 0;
    
    // Procesar cada categoría
    for (const categoria in tiposNotasDOM) {
        const tipos = tiposNotasDOM[categoria];
        if (!tipos || tipos.length === 0) continue;
        
        let sumaNotas = 0;
        let cantidadNotas = 0;
        
        tiposTotales += tipos.length;
        
        // Procesar cada tipo de nota
        tipos.forEach(tipo => {
            if (calificaciones[tipo.id]) {
                sumaNotas += calificaciones[tipo.id];
                cantidadNotas++;
                tiposCalificados++;
            }
        });
        
        // Calcular promedio de la categoría
        if (cantidadNotas > 0) {
            resultados[categoria].nota = sumaNotas / cantidadNotas;
            resultados[categoria].total = cantidadNotas;
            resultados[categoria].porcentaje = cantidadNotas / tipos.length;
        }
    }
    
    // Calcular definitiva
    let definitiva = 0;
    let pesoAplicado = 0;
    
    for (const categoria in resultados) {
        if (resultados[categoria].total > 0) {
            definitiva += resultados[categoria].nota * pesos[categoria];
            pesoAplicado += pesos[categoria];
        }
    }
    
    if (pesoAplicado > 0) {
        definitiva = definitiva / pesoAplicado;
    }
    
    return {
        definitiva: definitiva.toFixed(1),
        completo: tiposTotales > 0 && tiposCalificados === tiposTotales,
        porcentaje: tiposTotales > 0 ? (tiposCalificados / tiposTotales) * 100 : 0
    };
}

/**
 * Obtiene la clase de color basada en el valor de la nota
 */
function getGradeColorClass(nota) {
    if (nota >= 4.6) return 'nota-excelente';
    if (nota >= 4.0) return 'nota-buena';
    if (nota >= 3.0) return 'nota-aceptable';
    if (nota >= 1.0) return 'nota-baja';
    return 'nota-reprobada';
}

/**
 * Guarda los cambios pendientes en el servidor
 */
function saveChanges() {
    if (pendingChanges.size === 0) return;
    
    showLoading(true);
    
    const notas = Array.from(pendingChanges.values());
    
    fetch('/school_management/profesor/api/calificaciones/guardar_notas_multiple.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ notas: notas })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            pendingChanges.clear();
            showToast('Calificaciones guardadas correctamente');
        } else {
            showToast(data.message || 'Error al guardar las calificaciones', 'error');
        }
    })
    .catch(error => {
        console.error('Error al guardar calificaciones:', error);
        showToast('Error de conexión al guardar calificaciones', 'error');
    })
    .finally(() => {
        showLoading(false);
    });
}

/**
 * Inicializa la gestión de tipos de notas
 */
function initTiposNotasModal() {
    const btnGestionarTiposNotas = document.getElementById('btnGestionarTiposNotas');
    
    if (!btnGestionarTiposNotas) {
        console.error('No se encontró el botón de Gestionar Tipos de Notas');
        return;
    }
    
    // Cargar tipos de notas al abrir el modal
    btnGestionarTiposNotas.addEventListener('click', function() {
        cargarTiposNotas();
        $('#modalTiposNotas').modal('show');
    });
    
    // Event listeners para agregar un tipo de nota
    document.querySelectorAll('.btn-add').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoria = this.dataset.categoria;
            abrirModalAgregarTipoNota(categoria);
        });
    });
    
    // Event listener para guardar tipo de nota
    document.getElementById('btnGuardarTipoNota').addEventListener('click', function() {
        guardarTipoNota();
    });
    
    // Event listener para confirmar eliminación
    document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
        const tipoNotaId = document.getElementById('idTipoNotaEliminar').value;
        if (tipoNotaId) {
            eliminarTipoNota(tipoNotaId);
        }
    });
}

/**
 * Carga los tipos de notas desde el servidor
 */
function cargarTiposNotas() {
    // Mostrar indicadores de carga
    document.querySelectorAll('.tipos-grid').forEach(grid => {
        grid.innerHTML = '<div class="loading-indicator"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    });
    
    // Obtener parámetros necesarios
    const asignacionId = document.getElementById('asignacion_id')?.value;
    const esMultigrado = document.getElementById('es_multigrado')?.value === '1';
    const nivel = document.getElementById('nivel')?.value;
    const sedeId = document.getElementById('sede_id')?.value;
    const materiaId = document.getElementById('materia_id')?.value;
    
    // Construir URL de la API
    let url = '/school_management/profesor/api/calificaciones/obtener_tipos_notas.php';
    let params = [];
    
    if (esMultigrado) {
        params.push('es_multigrado=1');
        params.push(`nivel=${encodeURIComponent(nivel)}`);
        params.push(`sede_id=${sedeId}`);
        params.push(`materia_id=${materiaId}`);
    } else {
        params.push(`asignacion_id=${asignacionId}`);
    }
    
    url += '?' + params.join('&');
    
    // Realizar la petición AJAX
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar el contenido de cada categoría
                actualizarContenedoresTiposNotas(data.tipos_notas, data.totales_categoria);
            } else {
                showToast('error', data.message || 'Error al cargar los tipos de notas');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error de conexión al cargar los tipos de notas');
        });
}

/**
 * Actualiza los contenedores de tipos de notas
 * 
 * @param {Array} tiposNotas Lista de tipos de notas
 * @param {Object} totalesCategoria Totales por categoría
 */
function actualizarContenedoresTiposNotas(tiposNotas, totalesCategoria) {
    // Agrupar por categoría
    const tiposPorCategoria = {
        'TAREAS': [],
        'EVALUACIONES': [],
        'AUTOEVALUACION': []
    };
    
    tiposNotas.forEach(tipo => {
        const categoria = tipo.categoria || 'TAREAS';
        if (!tiposPorCategoria[categoria]) {
            tiposPorCategoria[categoria] = [];
        }
        tiposPorCategoria[categoria].push(tipo);
    });
    
    // Actualizar cada contenedor
    Object.keys(tiposPorCategoria).forEach(categoria => {
        actualizarContenedorCategoria(
            categoria, 
            tiposPorCategoria[categoria], 
            totalesCategoria[categoria] || 0
        );
    });
}

/**
 * Actualiza el contenedor de una categoría específica
 * 
 * @param {string} categoria Nombre de la categoría
 *