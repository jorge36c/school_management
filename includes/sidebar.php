<?php
$current_page = $_SERVER['PHP_SELF'];
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Sistema Escolar</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="/school_management/admin/dashboard.php" 
                   class="<?php echo strpos($current_page, 'dashboard.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- ACADÉMICO -->
            <li class="menu-section">
                <span class="menu-title">ACADÉMICO</span>
            </li>
            <li>
                <a href="/school_management/admin/academic/list_dba.php" 
                   class="<?php echo strpos($current_page, 'academic/list_dba.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Asignaturas</span>
                </a>
            </li>
            <li>
                <a href="/school_management/admin/asignaciones/" 
                   class="<?php echo strpos($current_page, 'asignaciones') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Asignaciones</span>
                </a>
            </li>
            
            <!-- USUARIOS -->
            <li class="menu-section">
                <span class="menu-title">USUARIOS</span>
            </li>
            <li>
                <a href="/school_management/admin/users/list_teachers.php" 
                   class="<?php echo strpos($current_page, 'users/list_teachers.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Profesores</span>
                </a>
            </li>
            <li>
                <a href="/school_management/admin/users/list_students.php" 
                   class="<?php echo strpos($current_page, 'users/list_students.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Estudiantes</span>
                </a>
            </li>
            <li>
                <a href="/school_management/admin/users/list_parents.php" 
                   class="<?php echo strpos($current_page, 'users/list_parents.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Padres</span>
                </a>
            </li>

            <!-- SEDES -->
            <li class="menu-section">
                <span class="menu-title">SEDES</span>
            </li>
            <li>
                <a href="/school_management/admin/headquarters/list_headquarters.php" 
                   class="<?php echo strpos($current_page, 'headquarters/list_headquarters.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>Gestión de Sedes</span>
                </a>
            </li>

            <!-- CONFIGURACIÓN -->
            <li class="menu-section">
                <span class="menu-title">CONFIGURACIÓN</span>
            </li>
            <li>
                <a href="/school_management/admin/settings/system_settings.php" 
                   class="<?php echo strpos($current_page, 'settings/system_settings.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>
        </ul>
    </nav>
</aside> 