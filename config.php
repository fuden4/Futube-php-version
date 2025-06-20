<?php
$host = 'sql113.infinityfree.com';
$db   = 'if0_39161754_fuden_stream';
$user = 'if0_39161754';
$pass = '3CJpUfVRgjL';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>
