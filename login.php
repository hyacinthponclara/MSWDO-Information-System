<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header('Location: index.html?error=empty');
    exit;
}

$host    = 'localhost';
$dbname  = 'CAPSTONE_DRAFT';
$db_user = 'root';
$db_pass = '';  // your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: index.html?error=server');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM MSWDO_USER WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['user_password'])) {

    $_SESSION['user_id']        = $user['user_id'];
    $_SESSION['user_role']      = $user['user_role'];
    $_SESSION['username']       = $user['username'];
    $_SESSION['user_firstname'] = $user['user_firstname'];
    $_SESSION['user_lastname']  = $user['user_lastname'];

    if ($user['user_role'] === 'Admin') {
        header('Location: admindashboard.php');
    } elseif ($user['user_role'] === 'Social Worker') {
        header('Location: mswdohead.php');
    } else {
        header('Location: staffdashboard.php');
    }
    exit;

} else {
    header('Location: index.html?error=invalid');
    exit;
}
?>