<?php
// Pull the credentials securely from Railway's environment variables
$host = getenv('mysql.railway.internal');
$port = getenv('19886');
$dbname = getenv('railway'); 
$db_user = getenv('root');
$db_pass = getenv('zvDiFeHtRYptIXBTEIwIpKpPxTJTUCri'); 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: index.php?error=server');
    exit;
}
?>