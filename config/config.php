<?php
// Configuración de rutas
define('BASE_URL', '/school_management');

// Función para generar URLs
function url($path) {
    return BASE_URL . $path;
}

// Función para redireccionar
function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit();
}
?>