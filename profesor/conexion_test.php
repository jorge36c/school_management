<?php
/**
 * Script para probar la conexión a la base de datos
 */

// Asegurarse de que la salida sea como texto plano
header('Content-Type: text/plain');

echo "Prueba de conexión a la base de datos\n";
echo "=====================================\n\n";

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management";

echo "Intentando conectar a MySQL...\n";
echo "Host: $servername\n";
echo "Usuario: $username\n";
echo "Base de datos: $dbname\n\n";

// Intentar conectar usando mysqli
echo "Método 1: Usando MySQLi\n";
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        echo "Error de conexión: " . $conn->connect_error . "\n";
    } else {
        echo "Conexión exitosa con MySQLi!\n";
        
        // Probar una consulta simple
        $result = $conn->query("SHOW TABLES");
        echo "Tablas en la base de datos:\n";
        while ($row = $result->fetch_row()) {
            echo "- " . $row[0] . "\n";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage() . "\n";
}

echo "\n";

// Intentar conectar usando PDO
echo "Método 2: Usando PDO\n";
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa con PDO!\n";
    
    // Probar una consulta simple
    $stmt = $conn->query("SHOW TABLES");
    echo "Tablas en la base de datos:\n";
    while ($row = $stmt->fetch()) {
        echo "- " . $row[0] . "\n";
    }
    
    // Verificar si existe la tabla asistencias
    $stmt = $conn->query("SHOW TABLES LIKE 'asistencias'");
    if ($stmt->rowCount() > 0) {
        echo "\nLa tabla 'asistencias' existe!\n";
        
        // Verificar la estructura de la tabla
        $stmt = $conn->query("DESCRIBE asistencias");
        echo "Estructura de la tabla asistencias:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")" . 
                 ($row['Key'] ? " [" . $row['Key'] . "]" : "") . "\n";
        }
    } else {
        echo "\nLa tabla 'asistencias' NO existe!\n";
    }
    
    $conn = null;
} catch(PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
?>