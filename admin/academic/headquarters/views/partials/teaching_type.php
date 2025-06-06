<?php
// Verificar datos necesarios
if (!isset($sede)) {
    die('Error: No se han proporcionado los datos de la sede');
}

// Establecer el tipo de enseñanza actual
$tipo_actual = $sede['tipo_ensenanza'];
?>

<div class="teaching-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-chalkboard-teacher"></i>
            Tipo de Enseñanza
        </h2>
        <div class="header-actions">
            <div class="last-update">
                <i class="fas fa-clock"></i>
                <span>Última actualización: <?php echo date('d/m/Y H:i', strtotime($sede['fecha_registro'])); ?></span>
            </div>
        </div>
    </div>
    
    <div class="teaching-options">
        <!-- Opción Unigrado -->
        <div class="teaching-option <?php echo $tipo_actual === 'unigrado' ? 'active' : ''; ?>"
             onclick="seleccionarTipo('unigrado')"
             data-tipo="unigrado">
            <div class="teaching-icon">
                <i class="fas fa-chalkboard"></i>
            </div>
            <div class="teaching-content">
                <h3 class="teaching-label">Unigrado</h3>
                <p class="teaching-description">
                    Sistema tradicional donde cada grupo corresponde a un solo grado académico.
                    Ideal para sedes con alta población estudiantil.
                </p>
                <div class="teaching-features">
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        Un grado por grupo
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        Enseñanza tradicional
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        Mayor especificidad
                    </div>
                </div>
            </div>
            <div class="check-indicator">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Opción Multigrado -->
        <div class="teaching-option <?php echo $tipo_actual === 'multigrado' ? 'active' : ''; ?>"
             onclick="seleccionarTipo('multigrado')"
             data-tipo="multigrado">
            <div class="teaching-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="teaching-content">
                <h3 class="teaching-label">Multigrado</h3>
                <p class="teaching-description">
                    Sistema flexible donde un grupo puede contener estudiantes de diferentes grados.
                    Ideal para sedes rurales o con población estudiantil reducida.
                </p>
                <div class="teaching-features">
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        Múltiples grados por grupo
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        Aprendizaje colaborativo
                    </div>
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        Mayor flexibilidad
                    </div>
                </div>
            </div>
            <div class="check-indicator">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Cambio</h3>
                <button class="close-btn" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas cambiar el tipo de enseñanza?</p>
                <p class="modal-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Este cambio afectará la configuración de grupos y la distribución de estudiantes.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmarCambio()">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<style>
.teaching-section {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-primary);
    margin: 0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.last-update {
    font-size: 0.875rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.teaching-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.teaching-option {
    border: 2px solid var(--border-color);
    border-radius: 1rem;
    padding: 1.5rem;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    display: flex;
    gap: 1.5rem;
}

.teaching-option:hover:not(.active) {
    border-color: var(--primary-light);
    background: var(--hover-bg);
}

.teaching-option.active {
    border-color: var(--primary-color);
    background: #f0f9ff;
}

.teaching-icon {
    width: 48px;
    height: 48px;
    background: var(--primary-color);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.teaching-content {
    flex: 1;
}

.teaching-label {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
}

.teaching-description {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin: 0 0 1rem 0;
    line-height: 1.5;
}

.teaching-features {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.feature {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.feature i {
    color: var(--success-color);
}

.check-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: var(--primary-color);
    font-size: 1.25rem;
    opacity: 0;
    transition: var(--transition);
}

.teaching-option.active .check-indicator {
    opacity: 1;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 1rem;
    width: 90%;
    max-width: 500px;
    box-shadow: var(--shadow-md);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1.5rem;
}

.modal-warning {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--warning-color);
    background: #fef3c7;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

@media (max-width: 768px) {
    .teaching-options {
        grid-template-columns: 1fr;
    }

    .header-actions {
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
    }
}
</style>

<script>
let tipoEnsenanzaSeleccionado = '<?php echo $tipo_actual; ?>';
const sedeId = <?php echo $sede['id']; ?>;

function seleccionarTipo(tipo) {
    if (tipo === tipoEnsenanzaSeleccionado) return;
    
    document.querySelectorAll('.teaching-option').forEach(option => {
        option.classList.remove('active');
    });
    
    document.querySelector(`[data-tipo="${tipo}"]`).classList.add('active');
    tipoEnsenanzaSeleccionado = tipo;
    
    // Habilitar botón de guardar
    document.getElementById('btnGuardar').removeAttribute('disabled');
}

function guardarTipoEnsenanza() {
    if (tipoEnsenanzaSeleccionado === '<?php echo $tipo_actual; ?>') return;
    
    // Mostrar modal de confirmación
    document.getElementById('confirmModal').style.display = 'flex';
}

function confirmarCambio() {
    // Mostrar indicador de carga
    const btnGuardar = document.getElementById('btnGuardar');
    const btnTextoOriginal = btnGuardar.innerHTML;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btnGuardar.disabled = true;

    fetch('/school_management/admin/academic/headquarters/save_teaching_type.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            sede_id: sedeId,
            tipo_ensenanza: tipoEnsenanzaSeleccionado
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('Tipo de enseñanza actualizado correctamente', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            mostrarNotificacion(data.message || 'Error al actualizar el tipo de enseñanza', 'error');
            btnGuardar.innerHTML = btnTextoOriginal;
            btnGuardar.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al procesar la solicitud', 'error');
        btnGuardar.innerHTML = btnTextoOriginal;
        btnGuardar.disabled = false;
    });

    cerrarModal();
}

function cerrarModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function mostrarNotificacion(mensaje, tipo) {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion ${tipo}`;
    notificacion.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${mensaje}
    `;
    
    document.body.appendChild(notificacion);
    
    // Animar entrada
    setTimeout(() => notificacion.classList.add('show'), 100);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notificacion.classList.remove('show');
        setTimeout(() => notificacion.remove(), 300);
    }, 3000);
}

// Event Listeners
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModal();
    }
});

// Prevenir cierre de modal al hacer clic dentro
document.querySelector('.modal-content')?.addEventListener('click', function(event) {
    event.stopPropagation();
});
</script>