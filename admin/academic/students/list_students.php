<table>
    <thead>
        <tr>
            <th>Apellidos</th>
            <th>Nombres</th>
            <th>Documento</th>
            <th>Sede</th>
            <th>Nivel</th>
            <th>Grupo</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT e.*, s.nombre as sede_nombre, g.nombre as grupo_nombre 
                FROM estudiantes e 
                LEFT JOIN sedes s ON e.sede_id = s.id 
                LEFT JOIN grupos g ON e.grupo_id = g.id 
                ORDER BY e.apellidos, e.nombres";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $estudiantes = $stmt->fetchAll();
        
        foreach($estudiantes as $estudiante): ?>
            <tr>
                <td><?php echo htmlspecialchars($estudiante['apellidos']); ?></td>
                <td><?php echo htmlspecialchars($estudiante['nombres']); ?></td>
                <td><?php echo htmlspecialchars($estudiante['documento']); ?></td>
                <td><?php echo htmlspecialchars($estudiante['sede_nombre']); ?></td>
                <td><?php echo $estudiante['nivel'] ? ucfirst($estudiante['nivel']) : '-'; ?></td>
                <td><?php echo $estudiante['grupo_nombre'] ?? '-'; ?></td>
                <td>
                    <span class="estado-badge <?php echo $estudiante['estado']; ?>">
                        <?php echo ucfirst($estudiante['estado']); ?>
                    </span>
                </td>
                <td class="actions">
                    <!-- ... otros botones ... -->
                    <a href="../academic/students/delete_student.php?id=<?php echo $estudiante['id']; ?>" 
                       onclick="return confirm('¿Está seguro que desea eliminar este estudiante?')" 
                       class="btn-delete">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table> 