<?php
return [
    // Configuración de la aplicación
    'debug' => env('APP_DEBUG', false),  // Asegurar que esté en false en producción
    'timezone' => env('APP_TIMEZONE', 'America/Bogota'),
    'max_execution_time' => env('APP_MAX_EXECUTION_TIME', 30),
    
    // Configuración de seguridad
    'allowed_ips' => array_filter(explode(',', env('ALLOWED_IPS', '127.0.0.1,::1'))),
    'csrf_protection' => true,
    'session_secure' => env('SESSION_SECURE', true),
    'session_httponly' => true,
    'session_lifetime' => env('SESSION_LIFETIME', 7200),
    
    // Configuración de logs
    'log_path' => __DIR__ . '/../logs',
    'log_level' => env('APP_LOG_LEVEL', 'error'), // Mantener en error en producción
    'log_max_files' => env('APP_LOG_MAX_FILES', 30),
    
    // Configuración de la aplicación
    'app_name' => env('APP_NAME', 'Sistema de Gestión Escolar'),
    'app_version' => env('APP_VERSION', '1.0.0'),
    'company_name' => env('COMPANY_NAME', 'Tu Escuela'),
    
    // Configuración de correo
    'mail_driver' => env('MAIL_DRIVER', 'smtp'),
    'mail_host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
    'mail_port' => env('MAIL_PORT', 2525),
    'mail_encryption' => env('MAIL_ENCRYPTION', 'tls'),
];

// Función helper para obtener variables de entorno
function env($key, $default = null) {
    return getenv($key) ?: $default;
} 