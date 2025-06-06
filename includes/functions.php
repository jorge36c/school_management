<?php
// Funciones de utilidad general
function logError($message) {
    error_log($message);
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Funciones específicas para periodos
function checkAdminAuth() {
    if(!isset($_SESSION['admin_id'])) {
        header('Location: ../../auth/login.php');
        exit();
    }
} 