/**
 * Módulo principal para calificaciones
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos DOM
    const formCalificaciones = document.getElementById('form-calificaciones');
    const btnGuardarTodasNotas = document.getElementById('btnGuardarTodasNotas');
    const btnGuardarNotasTarjetas = document.getElementById('btnGuardarNotasTarjetas');
    const toast = document.getElementById('toast');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Inicializar módulos
    if (typeof tiposNotasModule !== 'undefined') {
        tiposNotasModule.init();
    }
    
    // Funciones de utilidad
    function toggleLoading(show) {
        if (loadingOverlay) {
            loadingOverlay.style.display = show ? 'flex' : 'none';
        }
    }
    
    function mostrarToast(mensaje, esError = false) {
        if (!toast) return;
        
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = toast.querySelector('.toast-icon i');
        
        if (toastMessage) {
            toastMessage.textContent = mensaje;
        }
        
        if (esError) {
            toast.classList.add('toast-error');
            if (toastIcon) {
                toastIcon.className = 'fas fa-exclamation-circle';
            }
            toast.querySelector('.toast-title').textContent = 'Error';
        } else {
            toast.classList.remove('toast-error');
            if (toastIcon) {
                toastIcon.className = 'fas fa-check-circle';
            }
            toast.querySelector('.toast-title').textContent = 'Éxito';
        }
        
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // Sincronización de valores entre vistas
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('grade-input') || e.target.classList.contains('nota-input')) {
            const estudianteId = e.target.dataset.estudianteId;
            const tipoNotaId = e.target.dataset.tipoNotaId;
            const valor = e.target.value;
            
            // Actualizar el otro input correspondiente en la otra vista
            document.querySelectorAll(`input[data-estudiante-id="${estudianteId}"][data-tipo-nota-id="${tipoNotaId}"]`).forEach(input => {
                if (input !== e.target) {
                    input.value = valor;
                }
            });
        }
    });
    
    // Guardar nota individual al perder el foco
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('grade-input') || e.target.classList.contains('nota-input')) {
            const estudianteId = e.target.dataset.estudianteId;
            const tipoNotaId = e.target.dataset.tipoNotaId;
            const valor = e.target.value;
            
            if (valor) {
                guardarNota(estudianteId, tipoNotaId, valor);
            }
        }
    });
    
    // Función para guardar una nota individual
    function guardarNota(estudianteId, tipoNotaId, valor) {
        fetch('../../api/calificaciones/guardar_nota.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                estudiante_id: estudianteId,
                tipo_nota_id: tipoNotaId,
                valor: valor
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la definitiva
                const tr = document.querySelector(`tr[data-student-id="${estudianteId}"]`);
                if (tr) {
                    const tdDefinitiva = tr.querySelector('.definitiva-cell .final-grade');
                    if (tdDefinitiva) {
                        tdDefinitiva.textContent = data.definitiva;
                        
                        // Actualizar clase de color
                        tdDefinitiva.className = 'final-grade';
                        const definitivaValue = parseFloat(data.definitiva);
                        if (definitivaValue >= 4.6) {
                            tdDefinitiva.classList.add('nota-excelente');
                        } else if (definitivaValue >= 4.0) {
                            tdDefinitiva.classList.add('nota-buena');
                        } else if (definitivaValue >= 3.0) {
                            tdDefinitiva.classList.add('nota-aceptable');
                        } else {
                            tdDefinitiva.classList.add('nota-baja');
                        }
                    }
                }
                
                // Actualizar en la vista de tarjetas
                const card = document.querySelector(`.student-card[data-student-id="${estudianteId}"]`);
                if (card) {
                    const cardGrade = card.querySelector('.card-grade');
                    if (cardGrade) {
                        cardGrade.textContent = data.definitiva;
                        
                        // Actualizar clase de color
                        cardGrade.className = 'card-grade';
                        const definitivaValue = parseFloat(data.definitiva);
                        if (definitivaValue >= 4.6) {
                            cardGrade.classList.add('nota-excelente');
                        } else if (definitivaValue >= 4.0) {
                            cardGrade.classList.add('nota-buena');
                        } else if (definitivaValue >= 3.0) {
                            cardGrade.classList.add('nota-aceptable');
                        } else {
                            cardGrade.classList.add('nota-baja');
                        }
                    }
                }
            } else {
                mostrarToast(data.message || 'Error al guardar la nota', true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error de conexión', true);
        });
    }
    
    // Guardar todas las notas (desde la vista de tabla)
    if (formCalificaciones) {
        formCalificaciones.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarTodasLasNotas();
        });
    }
    
    // Guardar todas las notas (desde la vista de tarjetas)
    if (btnGuardarNotasTarjetas) {
        btnGuardarNotasTarjetas.addEventListener('click', function() {
            guardarTodasLasNotas();
        });
    }
    
    // Función unificada para guardar todas las notas
    function guardarTodasLasNotas() {
        const notasInputs = document.querySelectorAll('.grade-input, .nota-input');
        const notasParaGuardar = [];
        
        notasInputs.forEach(input => {
            if (input.value) {
                const estudianteId = input.dataset.estudianteId;
                const tipoNotaId = input.dataset.tipoNotaId;
                // Evitar duplicados
                const yaExiste = notasParaGuardar.some(nota => 
                    nota.estudiante_id === estudianteId && nota.tipo_nota_id === tipoNotaId
                );
                
                if (!yaExiste) {
                    notasParaGuardar.push({
                        estudiante_id: estudianteId,
                        tipo_nota_id: tipoNotaId,
                        valor: parseFloat(input.value)
                    });
                }
            }
        });
        
        if (notasParaGuardar.length === 0) {
            mostrarToast('No hay notas para guardar', true);
            return;
        }
        
        toggleLoading(true);
        
        fetch('../../api/calificaciones/guardar_notas_multiple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notas: notasParaGuardar })
        })
        .then(response => response.json())
        .then(data => {
            toggleLoading(false);
            
            if (data.success) {
                mostrarToast(data.message || 'Notas guardadas correctamente');
                
                // Recargar la página para actualizar las definitivas
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                mostrarToast(data.message || 'Error al guardar las notas', true);
            }
        })
        .catch(error => {
            toggleLoading(false);
            console.error('Error:', error);
            mostrarToast('Error de conexión', true);
        });
    }
});