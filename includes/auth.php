<?php
session_start();

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../../login.php');
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['admin_id'] ?? null;
} 