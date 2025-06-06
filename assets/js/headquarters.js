document.getElementById('sidebar-toggle').addEventListener('click', function() {
    document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
    const mainContent = document.querySelector('.main-content');
    mainContent.style.marginLeft = mainContent.style.marginLeft === '0px' ? '260px' : '0px';
});

// Funciones para agregar nivel y grado
function agregarNivel() {
    window.location.href = `/school_management/admin/academic/headquarters/create_level.php?sede_id=${sedeId}`;
}

function agregarGrado(nivelId) {
    window.location.href = `/school_management/admin/academic/headquarters/create_grade.php?nivel_id=${nivelId}`;
}

function verGrupos(gradoId) {
    window.location.href = `/school_management/admin/academic/headquarters/list_groups.php?grado_id=${gradoId}`;
}

function editarGrado(gradoId) {
    window.location.href = `/school_management/admin/academic/headquarters/edit_grade.php?id=${gradoId}`;
}

// Función para guardar el tipo de enseñanza seleccionado
document.querySelectorAll('.teaching-option').forEach(option => {
    option.addEventListener('click', function(event) {
        document.querySelectorAll('.teaching-option').forEach(opt => opt.classList.remove('active'));
        event.currentTarget.classList.add('active');
        tipoEnsenanzaSeleccionado = event.currentTarget.dataset.tipo;
    });
});

function guardarTipoEnsenanza() {
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
            alert('Tipo de enseñanza actualizado correctamente');
            location.reload();
        } else {
            alert('Error al actualizar el tipo de enseñanza');
        }
    });
}