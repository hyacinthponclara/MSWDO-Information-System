<?php
// authentication

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_role'], $_SESSION['user_id'])) {
        header('Location: index.html?error=unauthorized');
        exit;
    }

    // Re-check the account is still active on every protected page load —
    // catches the case where an admin disables this user while they're
    // already logged in.
    require_once 'db_connect.php';
    $stmt = $pdo->prepare("SELECT user_isactive FROM MSWDO_USER WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $isActive = $stmt->fetchColumn();

    if ($isActive === false || (int) $isActive === 0) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: index.html?error=disabled');
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