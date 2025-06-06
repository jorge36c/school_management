// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Configurar eventos para abrir el modal
    const botonesCalificaciones = document.querySelectorAll('.btn-ver-calificaciones');
    botonesCalificaciones.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const asignacionId = this.getAttribute('data-asignacion');
            const materiaNombre = this.getAttribute('data-materia');
            mostrarCalificaciones(asignacionId, materiaNombre);
        });
    });
    
    // Configurar eventos para cerrar el modal
    const closeBtn = document.querySelector('.close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            document.getElementById('notasModal').style.display = 'none';
        });
    }
    
    // Cerrar el modal al hacer clic fuera de él
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('notasModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Función para mostrar calificaciones en el modal
function mostrarCalificaciones(asignacionId, materiaNombre) {
    const modal = document.getElementById('notasModal');
    const modalTitle = document.getElementById('modalTitle');
    const notasDetalle = document.getElementById('notasDetalle');
    
    // Establecer título del modal
    modalTitle.textContent = materiaNombre;
    
    // Mostrar modal con indicador de carga
    modal.style.display = 'block';
    notasDetalle.innerHTML = '<div class="loading-spinner">Cargando...</div>';
    
    // Hacer petición AJAX para obtener las notas
    fetch('../obtener_notas_estudiante.php?asignacion_id=' + asignacionId)
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            let html = '';
            
            if (data.success && data.notas && data.notas.length > 0) {
                // Organizar notas por categoría
                const notasPorCategoria = {
                    'TAREAS': [],
                    'EVALUACIONES': [],
                    'AUTOEVALUACION': []
                };
                
                data.notas.forEach(function(nota) {
                    const categoria = nota.categoria || 'TAREAS';
                    if (!notasPorCategoria[categoria]) {
                        notasPorCategoria[categoria] = [];
                    }
                    notasPorCategoria[categoria].push(nota);
                });
                
                // Mostrar notas por categoría
                if (notasPorCategoria.TAREAS.length > 0) {
                    html += '<div class="notas-categoria">';
                    html += '<h3 class="categoria-titulo">TAREAS, TRABAJOS, CUADERNOS <span class="categoria-porcentaje">(40%)</span></h3>';
                    
                    notasPorCategoria.TAREAS.forEach(function(nota) {
                        const valor = nota.valor === null ? 'N/A' : parseFloat(nota.valor).toFixed(1);
                        const valorClass = nota.valor === null ? 'neutral' : 
                                         (parseFloat(nota.valor) >= 3.0 ? 'aprobado' : 'reprobado');
                        
                        html += '<div class="nota-item">';
                        html += '<div class="nota-nombre">' + nota.nombre + ' (' + nota.porcentaje + '%)</div>';
                        html += '<div class="nota-valor ' + valorClass + '">' + valor + '</div>';
                        html += '</div>';
                    });
                    
                    if (data.promedios_categoria && data.promedios_categoria.TAREAS) {
                        const promedio = data.promedios_categoria.TAREAS.nota;
                        const promedioCat = promedio > 0 ? promedio.toFixed(1) : 'N/A';
                        const promedioClass = promedio >= 3.0 ? 'aprobado' : (promedio > 0 ? 'reprobado' : 'neutral');
                        
                        html += '<div class="promedio-categoria">';
                        html += '<span>Promedio Tareas:</span>';
                        html += '<span class="' + promedioClass + '">' + promedioCat + '</span>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                }
                
                // Evaluaciones (50%)
                if (notasPorCategoria.EVALUACIONES.length > 0) {
                    html += '<div class="notas-categoria">';
                    html += '<h3 class="categoria-titulo">EVALUACIONES <span class="categoria-porcentaje">(50%)</span></h3>';
                    
                    notasPorCategoria.EVALUACIONES.forEach(function(nota) {
                        const valor = nota.valor === null ? 'N/A' : parseFloat(nota.valor).toFixed(1);
                        const valorClass = nota.valor === null ? 'neutral' : 
                                         (parseFloat(nota.valor) >= 3.0 ? 'aprobado' : 'reprobado');
                        
                        html += '<div class="nota-item">';
                        html += '<div class="nota-nombre">' + nota.nombre + ' (' + nota.porcentaje + '%)</div>';
                        html += '<div class="nota-valor ' + valorClass + '">' + valor + '</div>';
                        html += '</div>';
                    });
                    
                    if (data.promedios_categoria && data.promedios_categoria.EVALUACIONES) {
                        const promedio = data.promedios_categoria.EVALUACIONES.nota;
                        const promedioCat = promedio > 0 ? promedio.toFixed(1) : 'N/A';
                        const promedioClass = promedio >= 3.0 ? 'aprobado' : (promedio > 0 ? 'reprobado' : 'neutral');
                        
                        html += '<div class="promedio-categoria">';
                        html += '<span>Promedio Evaluaciones:</span>';
                        html += '<span class="' + promedioClass + '">' + promedioCat + '</span>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                }
                
                // Auto Evaluación (10%)
                if (notasPorCategoria.AUTOEVALUACION.length > 0) {
                    html += '<div class="notas-categoria">';
                    html += '<h3 class="categoria-titulo">AUTO EVALUACIÓN <span class="categoria-porcentaje">(10%)</span></h3>';
                    
                    notasPorCategoria.AUTOEVALUACION.forEach(function(nota) {
                        const valor = nota.valor === null ? 'N/A' : parseFloat(nota.valor).toFixed(1);
                        const valorClass = nota.valor === null ? 'neutral' : 
                                         (parseFloat(nota.valor) >= 3.0 ? 'aprobado' : 'reprobado');
                        
                        html += '<div class="nota-item">';
                        html += '<div class="nota-nombre">' + nota.nombre + ' (' + nota.porcentaje + '%)</div>';
                        html += '<div class="nota-valor ' + valorClass + '">' + valor + '</div>';
                        html += '</div>';
                    });
                    
                    if (data.promedios_categoria && data.promedios_categoria.AUTOEVALUACION) {
                        const promedio = data.promedios_categoria.AUTOEVALUACION.nota;
                        const promedioCat = promedio > 0 ? promedio.toFixed(1) : 'N/A';
                        const promedioClass = promedio >= 3.0 ? 'aprobado' : (promedio > 0 ? 'reprobado' : 'neutral');
                        
                        html += '<div class="promedio-categoria">';
                        html += '<span>Promedio Auto Evaluación:</span>';
                        html += '<span class="' + promedioClass + '">' + promedioCat + '</span>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                }
                
                // Nota definitiva
                if (data.definitiva !== undefined && data.definitiva !== null) {
                    const notaClass = data.definitiva >= 3.0 ? 'aprobado' : 'reprobado';
                    
                    html += '<div class="resumen-definitivo">';
                    html += '<div class="resumen-titulo">Nota Final</div>';
                    html += '<div class="nota-final ' + notaClass + '">' + data.definitiva.toFixed(1) + '</div>';
                    html += '</div>';
                }
            } else {
                html = '<p class="info-message">No hay calificaciones registradas para esta materia.</p>';
            }
            
            notasDetalle.innerHTML = html;
        })
        .catch(function(error) {
            console.error('Error:', error);
            notasDetalle.innerHTML = '<p class="error-message">Error al cargar las calificaciones: ' + error.message + '</p>';
        });
}