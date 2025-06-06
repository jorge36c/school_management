<?php
// Verificar que tenemos los datos necesarios
if (!isset($sede)) {
    die('Error: No se han proporcionado los datos de la sede');
}

// Formatear datos para mostrar
$estado_clase = $sede['estado'] === 'activo' ? 'badge-success' : 'badge-danger';
?>
<div class="sede-card">
    <!-- Encabezado de la sede -->
    <div class="sede-header">
        <div class="sede-icon">
            <i class="fas fa-building"></i>
        </div>
        <div class="sede-details">
            <div class="sede-main">
                <h1 class="sede-title"><?php echo htmlspecialchars($sede['nombre']); ?></h1>
                <span class="badge <?php echo $estado_clase; ?>">
                    <i class="fas fa-circle"></i>
                    <?php echo ucfirst($sede['estado']); ?>
                </span>
            </div>
            <div class="sede-meta-inline">
                <?php if (!empty($sede['direccion'])): ?>
                <div class="meta-item-inline">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($sede['direccion']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($sede['codigo_dane'])): ?>
                <div class="meta-item-inline">
                    <i class="fas fa-fingerprint"></i>
                    <span>Código DANE: <?php echo htmlspecialchars($sede['codigo_dane']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos -->
<style>
.sede-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.sede-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sede-icon {
    background: var(--primary-color);
    width: 50px;
    height: 50px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.sede-details {
    flex: 1;
}

.sede-main {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sede-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    background: #dcfce7;
    color: #166534;
}

.sede-meta-inline {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.meta-item-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meta-item-inline i {
    color: var(--primary-color);
    font-size: 1rem;
}
</style>
