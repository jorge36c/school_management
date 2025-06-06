<?php
session_start();
if(!isset($_SESSION['admin_id'])) { header('Location: ../../../auth/login.php'); exit(); }

require_once '../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar y obtener datos del formulario
        $usuario = $_POST['usuario'];
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        $nombres = $_POST['nombres'];
        $apellidos = $_POST['apellidos'];
        $tipo_documento = $_POST['tipo_documento'];
        $documento = $_POST['documento'];
        $genero = $_POST['genero'];
        $direccion = $_POST['direccion'];
        $sede_id = $_POST['sede_id'] ?? null;
        $nivel = $_POST['nivel'] ?? null;
        $grupo_id = !empty($_POST['grupo_id']) ? $_POST['grupo_id'] : null;

        // Insertar en la tabla estudiantes
        $sql = "INSERT INTO estudiantes (
                    usuario, contrasena, email, nombres, apellidos, 
                    tipo_documento, documento, genero, direccion, 
                    sede_id, nivel, grupo_id, estado
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, 'activo'
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $usuario, $contrasena, $email, $nombres, $apellidos,
            $tipo_documento, $documento, $genero, $direccion,
            $sede_id, $nivel, $grupo_id
        ]);

        header('Location: list_students.php?success=1');
        exit();
    } catch(PDOException $e) {
        header('Location: create_student.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

header('Location: create_student.php');
exit(); 