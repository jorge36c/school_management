<?php
require_once '../../config/database.php';

$stmt = $pdo->query("SELECT id, nombre FROM sedes WHERE estado = 'activo' ORDER BY nombre");
while ($sede = $stmt->fetch()) {
    echo "<option value='{$sede['id']}'>" . htmlspecialchars($sede['nombre']) . "</option>";
} 