<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password original: admin123<br>";
echo "Hash generado: " . $hash . "<br>";

// Verificar el hash
if (password_verify($password, $hash)) {
    echo "Verificación exitosa - El hash es válido";
} else {
    echo "Error en la verificación";
}
?>