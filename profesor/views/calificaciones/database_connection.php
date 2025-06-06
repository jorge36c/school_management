<?php
/**
 * Archivo para establecer conexión a la base de datos
 * 
 * Este archivo se debería colocar en la misma ubicación que ver_estudiantes.php
 * C:\xampp\htdocs\school_management\profesor\views\calificaciones\database_connection.php
 */

// Intentar obtener la conexión a través del archivo database.php original
require_once __DIR__ . '/../../../config/database.php';

// Comprobar si la función getDB existe
if (function_exists('getDB')) {
    try {
        // Usar la función getDB
        $db = getDB();
    } catch (Exception $e) {
        // Si hay un error, usar la conexión directa
        useDirectConnection();
    }
} else {
    // Si la función no existe, usar la conexión directa
    useDirectConnection();
}

/**
 * Crea una conexión directa a la base de datos
 * @return PDO Conexión PDO a la base de datos
 */
function useDirectConnection() {
    global $db;
    
    try {
        // Valores predeterminados para la conexión MySQL en XAMPP
        $host = 'localhost';
        $dbname = 'school_management';
        $username = 'root';
        $password = '';
        
        // Crear conexión
        $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        return $db;
    } catch (PDOException $e) {
        die("ERROR: No se pudo conectar. " . $e->getMessage());
    }
}

// Asegurarnos de que $db esté disponible
if (!isset($db) || $db === null) {
    useDirectConnection();
}