<?php
// Verificar datos necesarios
if (!isset($sede) || !isset($niveles)) {
    die('Error: No se han proporcionado los datos necesarios');
}

// Definir colores por tipo de nivel
$coloresPorNivel = [
    'preescolar' => [
        'bg' => '#60a5fa',
        'light' => '#dbeafe',
        'icon' => 'fa-shapes'
    ],
    'primaria' => [
        'bg' => '#4ade80',
        'light' => '#dcfce7',
        'icon' => 'fa-book-reader'
    ],
    'secundaria' => [
        'bg' => '#f59e0b',
        'light' => '#fef3c7',
        'icon' => 'fa-graduation-cap'
    ]
];
?>

<div class="niveles-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-layer-group"></i>
            Niveles Educativos
        </h2>
        <button class="btn btn-primary" onclick="SedeManager.showCreateModal(<?php echo $sede['id']; ?>)">
            <i class="fas fa-plus"></i> Crear Nivel
        </button>
    </div>

    <?php if (empty($niveles)): ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-school"></i>
        </div>
        <h3>No hay niveles educativos configurados</h3>
        <p>Comienza agregando el primer nivel educativo para esta sede.</p>
        <button class="btn btn-primary" onclick="SedeManager.showCreateModal(<?php echo $sede['id']; ?>)">
            <i class="fas fa-plus"></i>
            Agregar Nivel
        </button>
    </div>
    <?php else: ?>
    <div class="niveles-grid">
        <?php foreach ($niveles as $nivel): 
            $color = $coloresPorNivel[$nivel['nombre']] ?? [
                'bg' => '#64748b',
                'light' => '#f1f5f9',
                'icon' => 'fa-bookmark'
            ];
        ?>
        <div class="nivel-card">
            <div class="nivel-header" style="background-color: <?php echo $color['bg']; ?>">
                <div class="nivel-icon">
                    <i class="fas <?php echo $color['icon']; ?>"></i>
                </div>
                <div class="nivel-title">
                    <?php echo ucfirst(htmlspecialchars($nivel['nombre'])); ?>
                </div>
                <div class="nivel-menu">
                    <button class="btn-icon" onclick="mostrarMenuNivel(this)">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="javascript:void(0)" onclick="gestionarGrados(<?php echo $nivel['id']; ?>)">
                            <i class="fas fa-list"></i> Gestionar Grados
                        </a>
                        <a href="javascript:void(0)" onclick="deshabilitarNivel(<?php echo $nivel['id']; ?>)" 
                           class="text-warning">
                            <i class="fas fa-ban"></i> Deshabilitar
                        </a>
                        <a href="javascript:void(0)" onclick="eliminarNivel(<?php echo $nivel['id']; ?>)" 
                           class="text-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
                </div>
            </div>

            <div class="nivel-content">
                <div class="nivel-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $nivel['total_grados']; ?></div>
                        <div class="stat-label">Grados</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php echo isset($nivel['total_estudiantes']) ? $nivel['total_estudiantes'] : 0; ?>
                        </div>
                        <div class="stat-label">Estudiantes</div>
                    </div>
                </div>

                <div class="nivel-grados">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM grados WHERE nivel_id = ? AND estado = 'activo' ORDER BY orden");
                    $stmt->execute([$nivel['id']]);
                    $grados = $stmt->fetchAll();
                    
                    if (!empty($grados)):
                        foreach ($grados as $grado):
                    ?>
                    <div class="grado-item" style="background-color: <?php echo $color['light']; ?>">
                        <span class="grado-nombre">
                            <?php echo htmlspecialchars($grado['nombre']); ?>
                        </span>
                        <div class="grado-actions">
                            <button class="btn-icon" onclick="verGrupos(<?php echo $grado['id']; ?>)" title="Ver grupos">
                                <i class="fas fa-users"></i>
                            </button>
                            <button class="btn-icon" onclick="editarGrado(<?php echo $grado['id']; ?>)" title="Editar grado">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                    
                    <button class="add-grado-btn" onclick="agregarGrado(<?php echo $nivel['id']; ?>)">
                        <i class="fas fa-plus"></i>
                        Agregar Grado
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.niveles-section {
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
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.empty-icon {
    font-size: 3rem;
    color: var(--border-color);
    margin-bottom: 1rem;
}

.niveles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.nivel-card {
    border-radius: 1rem;
    overflow: hidden;
    background: white;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.nivel-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.nivel-header {
    padding: 1.25rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
}

.nivel-icon {
    width: 40px;
    height: 40px;
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.nivel-title {
    font-size: 1.125rem;
    font-weight: 600;
    flex: 1;
}

.nivel-menu {
    position: relative;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    cursor: pointer;
    transition: var(--transition);
}

.btn-icon:hover {
    background: rgba(255, 255, 255, 0.3);
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 0.5rem;
    box-shadow: var(--shadow-md);
    min-width: 160px;
    z-index: 100;
    margin-top: 0.5rem;
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: var(--transition);
}

.dropdown-menu a:hover {
    background: var(--hover-bg);
}

.dropdown-menu a.text-danger {
    color: var(--danger-color);
}

.dropdown-menu a.text-warning {
    color: var(--warning-color);
}

.nivel-content {
    padding: 1.25rem;
}

.nivel-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--border-color);
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.nivel-grados {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.grado-item {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.grado-nombre {
    font-weight: 500;
}

.grado-actions {
    display: flex;
    gap: 0.5rem;
}

.grado-actions .btn-icon {
    width: 28px;
    height: 28px;
    background: white;
    color: var(--text-secondary);
}

.add-grado-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 2px dashed var(--border-color);
    border-radius: 0.5rem;
    color: var(--text-secondary);
    background: none;
    cursor: pointer;
    transition: var(--transition);
}

.add-grado-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: var(--hover-bg);
}

@media (max-width: 768px) {
    .header-actions {
        flex-direction: column;
        gap: 0.5rem;
    }

    .nivel-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function mostrarMenuNivel(btn) {
    // Cerrar todos los menús abiertos
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
    });
    
    const menu = btn.nextElementSibling;
    menu.style.display = 'block';

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function closeMenu(e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
            document.removeEventListener('click', closeMenu);
        }
    });
}

function agregarNivel(sedeId) {
    SedeManager.showCreateModal(sedeId);
}

function agregarGrado(nivelId) {
    window.location.href = `/school_management/admin/academic/headquarters/create_grade.php?nivel_id=${nivelId}`;
}

function editarGrado(gradoId) {
    window.location.href = `/school_management/admin/academic/headquarters/edit_grade.php?id=${gradoId}`;
}

function verGrupos(gradoId) {
    window.location.href = `/school_management/admin/academic/headquarters/list_groups.php?grado_id=${gradoId}`;
}

function gestionarGrados(nivelId) {
    window.location.href = `/school_management/admin/academic/headquarters/list_grades.php?nivel_id=${nivelId}`;
}

function eliminarNivel(nivelId) {
    if (confirm('¿Estás seguro de que deseas eliminar este nivel? Esta acción no se puede deshacer.')) {
        fetch(`/school_management/admin/academic/headquarters/delete_level.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ nivel_id: nivelId })
        })
        .then(response => {
            console.log('Respuesta recibida:', response); // Agregado para depuración
            return response.json();
        })
        .then(data => {
            console.log('Datos del servidor:', data); // Agregado para depuración
            if (data.success) {
                alert('Nivel eliminado exitosamente');
                location.reload();
            } else {
                alert(data.message || 'Error al eliminar el nivel');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    }
}

function deshabilitarNivel(nivelId) {
    if (confirm('¿Estás seguro de que deseas deshabilitar este nivel?')) {
        fetch(`/school_management/admin/academic/headquarters/disable_level.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ nivel_id: nivelId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Nivel deshabilitado exitosamente');
                location.reload();
            } else {
                alert(data.message || 'Error al deshabilitar el nivel');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    }
}
</script>
