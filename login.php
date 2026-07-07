<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header('Location: index.php?error=empty');
    exit;
}

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$dbname = getenv('MYSQLDATABASE');
$db_user = getenv('MYSQLUSER');
$db_pass = getenv('MYSQLPASSWORD');
try {
    // Added port=$port here!
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die($e->getMessage());

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
        header('Location: dashboard_admin.php');
    } elseif ($user['user_role'] === 'Social Worker') {
        header('Location: dashboard_mswdohead.php');
    } else {
        header('Location: dashboard_staff.php');
    }
    exit;

} else {
    header('Location: index.php?error=invalid');
    exit;
}
?>