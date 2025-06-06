<?php
/**
 * Vista de gestión de fotos de estudiantes
 * Incluido desde ver_estudiantes.php
 */
?>
<div class="photo-management-container">
    <div class="fotos-header">
        <h3 class="fotos-title">Gestión de Fotos de Estudiantes</h3>
    </div>

    <div class="photo-upload-grid">
        <?php foreach ($estudiantes as $estudiante): ?>
            <div class="photo-upload-card" data-student-id="<?php echo $estudiante['id']; ?>">                <div class="photo-upload-header">
                    <?php echo htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']); ?>
                </div>
                <div class="photo-upload-body">
                    <div class="photo-preview">
                        <?php if (!empty($estudiante['foto_url'])): ?>
                            <img src="<?php echo htmlspecialchars($estudiante['foto_url']); ?>?v=<?php echo time(); ?>" alt="Foto de <?php echo htmlspecialchars($estudiante['nombres']); ?>" id="preview-<?php echo $estudiante['id']; ?>">
                        <?php else: ?>
                            <div class="photo-iniciales">
                                <?php echo strtoupper(substr($estudiante['nombres'], 0, 1) . substr($estudiante['apellidos'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="photo-upload-form">
                        <input type="file" class="file-input" id="foto-<?php echo $estudiante['id']; ?>" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-sm btn-primary photo-upload-btn" onclick="document.getElementById('foto-<?php echo $estudiante['id']; ?>').click()">
                            <i class="fas fa-upload"></i> Subir foto
                        </button>
                        <button type="button" class="btn btn-sm btn-danger photo-remove-btn" <?php echo empty($estudiante['foto_url']) ? 'style="display:none;"' : ''; ?> data-id="<?php echo $estudiante['id']; ?>">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para recortar imagen -->
<div class="modal" id="modalCropperFoto">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Recortar Foto</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="cropper-container">
                <img id="imagenParaRecortar" src="" alt="Imagen a recortar">
            </div>
            <div class="cropper-info">
                <p>Ajusta el área de recorte para crear una foto cuadrada.</p>
            </div>
            <!-- Controles simples para manipular la imagen -->
            <div class="cropper-controls">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRotateLeft">
                    <i class="fas fa-undo"></i> Rotar izquierda
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRotateRight">
                    <i class="fas fa-redo"></i> Rotar derecha
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnZoomIn">
                    <i class="fas fa-search-plus"></i> Acercar
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnZoomOut">
                    <i class="fas fa-search-minus"></i> Alejar
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="btnCancelarCrop">Cancelar</button>
            <button class="btn btn-primary" id="btnAplicarCrop">Aplicar Recorte</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si Cropper.js está cargado
    if (typeof Cropper === 'undefined') {
        console.error('Cropper.js no está cargado. Por favor, incluya la librería.');
        
        // Cargar Cropper.js dinámicamente
        var cropperCSS = document.createElement('link');
        cropperCSS.rel = 'stylesheet';
        cropperCSS.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css';
        document.head.appendChild(cropperCSS);
        
        var cropperJS = document.createElement('script');
        cropperJS.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js';
        document.head.appendChild(cropperJS);
        
        // Esperar a que se cargue
        cropperJS.onload = function() {
            console.log('Cropper.js cargado dinámicamente');
            inicializarCropperFotos();
        };
    } else {
        inicializarCropperFotos();
    }
    
    function inicializarCropperFotos() {
        // Variables globales
        let estudianteActual = null;
        let cropper = null;
        
        // Referencias a elementos DOM
        const modalCropperFoto = document.getElementById('modalCropperFoto');
        const imagenParaRecortar = document.getElementById('imagenParaRecortar');
        const btnCancelarCrop = document.getElementById('btnCancelarCrop');
        const btnAplicarCrop = document.getElementById('btnAplicarCrop');
        const btnRotateLeft = document.getElementById('btnRotateLeft');
        const btnRotateRight = document.getElementById('btnRotateRight');
        const btnZoomIn = document.getElementById('btnZoomIn');
        const btnZoomOut = document.getElementById('btnZoomOut');
        
        // Inicializar listeners de subida de archivos
        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const estudianteId = this.id.replace('foto-', '');
                    estudianteActual = estudianteId;
                    
                    // Validar tamaño máximo (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Tamaño máximo: 5MB');
                        this.value = '';
                        return;
                    }
                    
                    // Validar tipo de archivo
                    if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                        alert('Formato no válido. Use JPG, PNG o GIF');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        // Mostrar imagen en el cropper
                        imagenParaRecortar.src = event.target.result;
                        // Abrir modal
                        modalCropperFoto.style.display = 'flex';
                        
                        // Inicializar cropper después de cargar la imagen
                        imagenParaRecortar.onload = function() {
                            if (cropper) {
                                cropper.destroy();
                            }
                            
                            cropper = new Cropper(imagenParaRecortar, {
                                aspectRatio: 1, // Relación de aspecto cuadrada
                                viewMode: 1,    // Restringir el área de recorte
                                guides: true,   // Mostrar guías de recorte
                                center: true,   // Mostrar indicador de centro
                                minContainerWidth: 300,
                                minContainerHeight: 300,
                                dragMode: 'move' // Permitir mover la imagen
                            });
                        };
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
        
        // Botones para eliminar fotos
        document.querySelectorAll('.photo-remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('¿Está seguro de eliminar esta foto?')) {
                    const estudianteId = this.getAttribute('data-id');
                    eliminarFoto(estudianteId);
                }
            });
        });
        
        // Botones de manipulación de imagen
        btnRotateLeft.addEventListener('click', function() {
            if (cropper) cropper.rotate(-90);
        });
        
        btnRotateRight.addEventListener('click', function() {
            if (cropper) cropper.rotate(90);
        });
        
        btnZoomIn.addEventListener('click', function() {
            if (cropper) cropper.zoom(0.1);
        });
        
        btnZoomOut.addEventListener('click', function() {
            if (cropper) cropper.zoom(-0.1);
        });
        
        // Botón cancelar recorte
        btnCancelarCrop.addEventListener('click', function() {
            cerrarModalCropper();
        });
        
        // Botón aplicar recorte
        btnAplicarCrop.addEventListener('click', function() {
            if (!cropper) return;
            
            // Obtener canvas con imagen recortada
            const canvas = cropper.getCroppedCanvas({
                width: 300,   // Ancho deseado
                height: 300,  // Alto deseado
                fillColor: '#fff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            // Convertir a Blob
            canvas.toBlob(function(blob) {
                subirFotoRecortada(blob);
            }, 'image/jpeg', 0.9); // Calidad de JPEG
        });
        
        // Cerrar modal al hacer clic en X
        modalCropperFoto.querySelector('.close').addEventListener('click', function() {
            cerrarModalCropper();
        });
        
        // Cerrar modal de cropper
        function cerrarModalCropper() {
            modalCropperFoto.style.display = 'none';
            // Resetear input para permitir seleccionar el mismo archivo de nuevo
            if (estudianteActual) {
                document.getElementById('foto-' + estudianteActual).value = '';
            }
            // Destruir cropper si existe
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }
        
        // Subir foto recortada
        function subirFotoRecortada(blob) {
            if (!estudianteActual || !blob) return;
            
            // Mostrar loading
            if (typeof showLoading === 'function') {
                showLoading(true);
            }
            
            // Crear FormData para enviar archivo
            const formData = new FormData();
            formData.append('foto', blob, 'foto_estudiante.jpg');
            formData.append('estudiante_id', estudianteActual);
            formData.append('asignacion_id', document.getElementById('asignacion_id')?.value || '0');
            
            // Enviar al servidor
            fetch('../api/calificaciones/subir_foto_estudiante.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Ocultar loading
                if (typeof showLoading === 'function') {
                    showLoading(false);
                }
                
                // Mostrar mensaje de éxito o error
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Foto actualizada correctamente');
                    } else {
                        alert('Foto actualizada correctamente');
                    }
                    
                    // Actualizar la foto en la interfaz
                    const previewImg = document.getElementById('preview-' + estudianteActual);
                    const photoPreview = document.querySelector('.photo-upload-card[data-student-id="' + estudianteActual + '"] .photo-preview');
                    
                    if (previewImg) {
                        // Añadir timestamp para evitar caché
                        previewImg.src = data.foto_url + '?v=' + Date.now();
                    } else if (photoPreview) {
                        photoPreview.innerHTML = '<img src="' + data.foto_url + '?v=' + Date.now() + '" alt="Foto de estudiante" id="preview-' + estudianteActual + '">';
                    }
                    
                    // Mostrar botón de eliminar
                    const btnEliminar = document.querySelector('.photo-upload-card[data-student-id="' + estudianteActual + '"] .photo-remove-btn');
                    if (btnEliminar) {
                        btnEliminar.style.display = '';
                    }
                    
                    // Cerrar modal
                    cerrarModalCropper();
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Error al subir la foto', true);
                    } else {
                        alert(data.message || 'Error al subir la foto');
                    }
                }
            })
            .catch(error => {
                // Ocultar loading
                if (typeof showLoading === 'function') {
                    showLoading(false);
                }
                
                // Mostrar error
                if (typeof showToast === 'function') {
                    showToast('Error de conexión', true);
                } else {
                    alert('Error de conexión');
                }
                console.error('Error:', error);
            });
        }
        
        // Eliminar foto
        function eliminarFoto(estudianteId) {
            if (!estudianteId) return;
            
            // Mostrar loading
            if (typeof showLoading === 'function') {
                showLoading(true);
            }
            
            // Enviar solicitud al servidor
            fetch('../api/calificaciones/eliminar_foto_estudiante.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    estudiante_id: estudianteId
                })
            })
            .then(response => response.json())
            .then(data => {
                // Ocultar loading
                if (typeof showLoading === 'function') {
                    showLoading(false);
                }
                
                // Mostrar mensaje de éxito o error
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Foto eliminada correctamente');
                    } else {
                        alert('Foto eliminada correctamente');
                    }
                    
                    // Actualizar la interfaz
                    const photoPreview = document.querySelector('.photo-upload-card[data-student-id="' + estudianteId + '"] .photo-preview');
                    const nombre = document.querySelector('.photo-upload-card[data-student-id="' + estudianteId + '"] .photo-upload-header').textContent.trim();
                    const iniciales = nombre.charAt(0) + (nombre.includes(',') ? nombre.split(',')[1].trim().charAt(0) : '');
                    
                    if (photoPreview) {
                        photoPreview.innerHTML = '<div class="photo-iniciales">' + iniciales.toUpperCase() + '</div>';
                    }
                    
                    // Ocultar botón de eliminar
                    const btnEliminar = document.querySelector('.photo-upload-card[data-student-id="' + estudianteId + '"] .photo-remove-btn');
                    if (btnEliminar) {
                        btnEliminar.style.display = 'none';
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Error al eliminar la foto', true);
                    } else {
                        alert(data.message || 'Error al eliminar la foto');
                    }
                }
            })
            .catch(error => {
                // Ocultar loading
                if (typeof showLoading === 'function') {
                    showLoading(false);
                }
                
                // Mostrar error
                if (typeof showToast === 'function') {
                    showToast('Error de conexión', true);
                } else {
                    alert('Error de conexión');
                }
                console.error('Error:', error);
            });
        }
    }
});
</script>

<style>
/* Estilos para gestión de fotos */
.photo-management-container {
    padding: 1rem;
}

.fotos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.fotos-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.photo-upload-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.photo-upload-card {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.photo-upload-header {
    padding: 0.5rem;
    background-color: var(--primary-light);
    color: var(--text-primary);
    font-weight: 600;
    font-size: 0.75rem;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.photo-upload-body {
    padding: 0.5rem;
}

.photo-preview {
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f1f5f9;
    margin-bottom: 0.5rem;
    border-radius: var(--border-radius);
    overflow: hidden;
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-iniciales {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-light);
    color: var(--primary-color);
    font-size: 1.5rem;
    font-weight: 600;
    border-radius: 50%;
}

.photo-upload-form {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Estilos para modal de cropper */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.close {
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 1rem;
}

.cropper-container {
    max-height: 400px;
    margin-bottom: 1rem;
}

#imagenParaRecortar {
    display: block;
    max-width: 100%;
}

.cropper-info {
    margin-bottom: 1rem;
    padding: 0.5rem;
    background-color: #f1f5f9;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
}

.cropper-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
    justify-content: center;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Responsivo */
@media (max-width: 768px) {
    .photo-upload-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .photo-preview {
        height: 120px;
    }
    
    .cropper-controls {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 480px) {
    .photo-upload-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .photo-preview {
        height: 100px;
    }
    
    .photo-iniciales {
        width: 60px;
        height: 60px;
        font-size: 1.25rem;
    }
    
    .photo-upload-form {
        flex-direction: column;
    }
}
</style>