<!-- Modal Boletines -->
<div class="modal fade" id="generarBoletinesModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generar Boletines</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Sede</label>
                    <select class="form-select" id="modalSede">
                        <option value="">Seleccione una sede</option>
                        <?php
                        $stmtSedes = $pdo->query("SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre");
                        while ($sede = $stmtSedes->fetch()) {
                            echo "<option value='{$sede['id']}'>{$sede['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nivel</label>
                    <select class="form-select" id="modalNivel" disabled>
                        <option value="">Seleccione un nivel</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Grado</label>
                    <select class="form-select" id="modalGrado" disabled>
                        <option value="">Seleccione un grado</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btnVistaPrevia" disabled>
                    <i class="fas fa-eye"></i> Vista Previa
                </button>
                <button type="button" class="btn btn-primary" id="btnGenerar" disabled>
                    <i class="fas fa-file-pdf"></i> Generar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalSede = document.getElementById('modalSede');
    const modalNivel = document.getElementById('modalNivel');
    const modalGrado = document.getElementById('modalGrado');
    const btnVistaPrevia = document.getElementById('btnVistaPrevia');
    const btnGenerar = document.getElementById('btnGenerar');

    modalSede.addEventListener('change', async function() {
        const sedeId = this.value;
        modalNivel.disabled = !sedeId;
        modalGrado.disabled = true;
        btnVistaPrevia.disabled = true;
        btnGenerar.disabled = true;

        if (!sedeId) {
            modalNivel.innerHTML = '<option value="">Primero seleccione una sede</option>';
            modalGrado.innerHTML = '<option value="">Primero seleccione un nivel</option>';
            return;
        }

        try {
            const response = await fetch(`get_niveles.php?sede_id=${sedeId}`);
            const html = await response.text();
            modalNivel.innerHTML = '<option value="">Seleccione un nivel</option>' + html;
        } catch (error) {
            console.error('Error al cargar niveles:', error);
            alert('Error al cargar los niveles educativos');
        }
    });

    modalNivel.addEventListener('change', async function() {
        const nivel = this.value;
        const sedeId = modalSede.value;
        modalGrado.disabled = !nivel;
        btnVistaPrevia.disabled = true;
        btnGenerar.disabled = true;

        if (!nivel) {
            modalGrado.innerHTML = '<option value="">Primero seleccione un nivel</option>';
            return;
        }

        try {
            const response = await fetch(`get_grados.php?sede_id=${sedeId}&nivel=${encodeURIComponent(nivel)}`);
            const html = await response.text();
            modalGrado.innerHTML = '<option value="">Seleccione un grado</option>' + html;
        } catch (error) {
            console.error('Error al cargar grados:', error);
            alert('Error al cargar los grados');
        }
    });

    modalGrado.addEventListener('change', function() {
        const habilitarBotones = this.value !== '';
        btnVistaPrevia.disabled = !habilitarBotones;
        btnGenerar.disabled = !habilitarBotones;
    });

    btnVistaPrevia.addEventListener('click', function() {
        const url = `preview_report.php?sede_id=${modalSede.value}&nivel=${encodeURIComponent(modalNivel.value)}&grado=${encodeURIComponent(modalGrado.value)}&periodo_id=preview`;
        window.location.href = url;
    });

    btnGenerar.addEventListener('click', function() {
        const url = `generate_report_cards.php?sede_id=${modalSede.value}&nivel=${encodeURIComponent(modalNivel.value)}&grado=${encodeURIComponent(modalGrado.value)}`;
        window.location.href = url;
    });
});
</script> 