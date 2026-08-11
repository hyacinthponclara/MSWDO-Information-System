<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}
require_once 'db_connect.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header('Location: index.html?error=empty');
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM mswdo_user WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['user_password'])) {

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_role'] = $user['user_role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_firstname'] = $user['user_firstname'];
    $_SESSION['user_lastname'] = $user['user_lastname'];

    if ($user['user_role'] === 'Admin') {
        header('Location: dashboard_admin.php');
    } else {
        header('Location: dashboard_staff.php');
    }
    exit;

} else {
    header('Location: index.html?error=invalid');
    exit;
}
?>