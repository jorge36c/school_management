/**
 * estudiantes_notas.js
 * Gestiona la interacción y guardado de calificaciones de estudiantes
 * 
 * Ruta: assets/js/calificaciones/estudiantes_notas.js
 */

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos DOM
    const notaInputs = document.querySelectorAll('.nota-input');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const toast = document.getElementById('toast');
    
    // Variables para almacenar el estado
    const esMultigrado = document.getElementById('es_multigrado')?.value === '1';
    
    // Función para mostrar el toast
    function showToast(message, isSuccess = true) {
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = toast.querySelector('i');
        
        toastMessage.textContent = message;
        
        if (isSuccess) {
            toast.classList.remove('toast-error');
            toast.classList.add('toast-success');
            toastIcon.className = 'fas fa-check-circle';
        } else {
            toast.classList.remove('toast-success');
            toast.classList.add('toast-error');
            toastIcon.className = 'fas fa-exclamation-circle';
        }
        
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // Función para mostrar el overlay de carga
    function toggleLoading(show) {
        if (show) {
            loadingOverlay.style.display = 'flex';
        } else {
            loadingOverlay.style.display = 'none';
        }
    }
    
    // Event listeners para guardar las notas
    notaInputs.forEach(input => {
        input.addEventListener('change', function() {
            guardarNota(this);
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur(); // Quitar el foco para activar el evento change
            }
        });
    });
    
    // Función para guardar una nota
    function guardarNota(input) {
        const valor = parseFloat(input.value);
        
        // Validar el rango
        if (valor < 0 || valor > 5) {
            showToast('La nota debe estar entre 0 y 5', false);
            return;
        }
        
        const estudianteId = input.dataset.estudiante;
        const tipoNotaId = input.dataset.tipo;
        
        // Preparar los datos
        const formData = new FormData();
        formData.append('estudiante_id', estudianteId);
        formData.append('tipo_nota_id', tipoNotaId);
        formData.append('valor', valor);
        
        // Si es multigrado, añadir los parámetros adicionales
        if (esMultigrado) {
            formData.append('es_multigrado', '1');
            formData.append('nivel', document.getElementById('nivel').value);
            formData.append('sede_id', document.getElementById('sede_id').value);
            formData.append('materia_id', document.getElementById('materia_id').value);
        }
        
        // Mostrar el indicador de carga
        toggleLoading(true);
        
        // Realizar la petición AJAX
        fetch('guardar_nota.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            toggleLoading(false);
            
            if (data.success) {
                showToast('Nota guardada correctamente');
                // Si hay una actualización de la nota definitiva, actualizar en la UI
                if (data.definitiva) {
                    actualizarNotaDefinitiva(estudianteId, data.definitiva);
                }
            } else {
                showToast('Error al guardar la nota: ' + data.message, false);
            }
        })
        .catch(error => {
            toggleLoading(false);
            showToast('Error de conexión: ' + error, false);
            console.error('Error:', error);
        });
    }
    
    // Función para actualizar la nota definitiva en la UI
    function actualizarNotaDefinitiva(estudianteId, definitivaData) {
        const definitivaEl = document.querySelector(`.estudiante-row [data-estudiante="${estudianteId}"]`)
            ?.closest('.estudiante-row')
            ?.querySelector('.nota-definitiva');
        
        if (definitivaEl) {
            // Actualizar el valor
            definitivaEl.textContent = definitivaData.valor;
            
            // Actualizar la clase según el valor
            definitivaEl.className = 'nota-definitiva';
            if (definitivaData.valor >= 4.6) {
                definitivaEl.classList.add('nota-excelente');
            } else if (definitivaData.valor >= 4.0) {
                definitivaEl.classList.add('nota-buena');
            } else if (definitivaData.valor >= 3.0) {
                definitivaEl.classList.add('nota-aceptable');
            } else if (definitivaData.valor > 0) {
                definitivaEl.classList.add('nota-baja');
            }
        }
    }
});