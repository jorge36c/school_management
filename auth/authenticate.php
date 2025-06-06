<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'admin';

    try {
        switch($role) {
            case 'estudiante':
                // Autenticación de estudiante
                $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE usuario = ? AND estado = 'activo'");
                $stmt->execute([$usuario]);
                $estudiante = $stmt->fetch();

                if ($estudiante && password_verify($password, $estudiante['password'])) {
                    // Login exitoso
                    $_SESSION['estudiante_id'] = $estudiante['id'];
                    $_SESSION['estudiante_nombre'] = $estudiante['nombre'] . ' ' . $estudiante['apellido'];
                    $_SESSION['rol'] = 'estudiante';
                    header('Location: ../estudiante/dashboard.php');
                    exit();
                } else {
                    error_log("Error de login estudiante: " . $usuario);
                    header('Location: student_login.php?error=1');
                    exit();
                }
                break;

            case 'profesor':
                // Autenticación de profesor
                $stmt = $pdo->prepare("SELECT * FROM profesores WHERE usuario = ? AND estado = 'activo'");
                $stmt->execute([$usuario]);
                $profesor = $stmt->fetch();

                if ($profesor && password_verify($password, $profesor['password'])) {
                    // Login exitoso
                    $_SESSION['profesor_id'] = $profesor['id'];
                    $_SESSION['profesor_nombre'] = $profesor['nombre'] . ' ' . $profesor['apellido'];
                    $_SESSION['rol'] = 'profesor';
                    header('Location: ../profesor/dashboard.php');
                    exit();
                } else {
                    error_log("Error de login profesor: " . $usuario);
                    header('Location: profesor_login.php?error=1');
                    exit();
                }
                break;

            default:
                // Autenticación de administrador (código original)
                $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
                $stmt->execute([$usuario]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_nombre'] = $admin['nombre'];
                    $_SESSION['rol'] = 'admin';
                    header('Location: ../admin/dashboard.php');
                    exit();
                } else {
                    error_log("Error de login admin: " . $usuario);
                    header('Location: login.php?error=1');
                    exit();
                }
                break;
        }
    } catch(PDOException $e) {
        error_log("Error de base de datos en login: " . $e->getMessage());
        header('Location: ' . ($role == 'estudiante' ? 'student_login.php' : 
              ($role == 'profesor' ? 'profesor_login.php' : 'login.php')) . '?error=2');
        exit();
    }
}

// Si alguien intenta acceder directamente a este archivo
header('Location: login.php');
exit();
?>