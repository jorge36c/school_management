<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/layouts.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-info">
                    <div class="page-title">
                        <i class="fas fa-cog"></i>
                        Configuración
                    </div>
                    <div class="page-subtitle">Ajustes del Sistema</div>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Configuración actualizada exitosamente
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Error: <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <!-- Configuración General -->
                <div class="settings-section">
                    <h2><i class="fas fa-globe"></i> Configuración General</h2>
                    <div class="settings-grid">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <i class="fas fa-school"></i>
                                <h3>Información Institucional</h3>
                            </div>
                            <div class="settings-card-content">
                                <form action="update_settings.php" method="POST">
                                    <div class="form-group">
                                        <label>Nombre de la Institución</label>
                                        <input type="text" name="school_name" value="<?php echo htmlspecialchars($config['school_name'] ?? ''); ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Dirección</label>
                                        <input type="text" name="address" value="<?php echo htmlspecialchars($config['address'] ?? ''); ?>" class="form-control">
                                    </div>
                                    <button type="submit" class="btn-save">Guardar Cambios</button>
                                </form>
                            </div>
                        </div>

                        <div class="settings-card">
                            <div class="settings-card-header">
                                <i class="fas fa-calendar"></i>
                                <h3>Año Escolar</h3>
                            </div>
                            <div class="settings-card-content">
                                <form action="update_settings.php" method="POST">
                                    <div class="form-group">
                                        <label>Año Actual</label>
                                        <input type="number" name="current_year" value="<?php echo date('Y'); ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Estado del Año</label>
                                        <select name="year_status" class="form-control">
                                            <option value="activo">Activo</option>
                                            <option value="planificacion">En Planificación</option>
                                            <option value="cerrado">Cerrado</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn-save">Guardar Cambios</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Sistema -->
                <div class="settings-section">
                    <h2><i class="fas fa-sliders-h"></i> Configuración del Sistema</h2>
                    <div class="settings-grid">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <i class="fas fa-envelope"></i>
                                <h3>Configuración de Correo</h3>
                            </div>
                            <div class="settings-card-content">
                                <form action="update_settings.php" method="POST">
                                    <div class="form-group">
                                        <label>Servidor SMTP</label>
                                        <input type="text" name="smtp_host" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Puerto SMTP</label>
                                        <input type="number" name="smtp_port" class="form-control">
                                    </div>
                                    <button type="submit" class="btn-save">Guardar Cambios</button>
                                </form>
                            </div>
                        </div>

                        <div class="settings-card">
                            <div class="settings-card-header">
                                <i class="fas fa-shield-alt"></i>
                                <h3>Seguridad</h3>
                            </div>
                            <div class="settings-card-content">
                                <form action="update_settings.php" method="POST">
                                    <div class="form-group">
                                        <label>Tiempo de Sesión (minutos)</label>
                                        <input type="number" name="session_timeout" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Intentos de Login Permitidos</label>
                                        <input type="number" name="max_login_attempts" class="form-control">
                                    </div>
                                    <button type="submit" class="btn-save">Guardar Cambios</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .settings-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .settings-section {
        margin-bottom: 2rem;
    }

    .settings-section h2 {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--text-dark);
        font-size: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .settings-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .settings-card-header {
        background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
        padding: 1.25rem;
        color: white;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .settings-card-header i {
        font-size: 1.25rem;
    }

    .settings-card-header h3 {
        margin: 0;
        font-size: 1.1rem;
    }

    .settings-card-content {
        padding: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--text-dark);
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.1);
    }

    .btn-save {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background 0.3s ease;
    }

    .btn-save:hover {
        background: var(--primary-dark);
    }

    @media (max-width: 768px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }

        .settings-card-header {
            padding: 1rem;
        }

        .settings-card-content {
            padding: 1rem;
        }
    }

    .alert {
        margin: 1rem 2rem;
        padding: 1rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
    }
    </style>
</body>
</html> 