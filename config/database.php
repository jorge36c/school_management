<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=school_management;charset=utf8mb4",
        "root",  // Cambiar a usuario específico
        "",      // Añadir contraseña segura
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}