<?php
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_role'])) {
        header('Location: index.html?error=unauthorized');
        exit;
    }
}

function requireRole($allowedRoles) {
    requireLogin();

    $allowed = (array) $allowedRoles;

    if (!in_array($_SESSION['user_role'], $allowed)) {
        header('Location: unauthorized.php');
        exit;
    }
}
?>