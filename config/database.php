<?php
$host = 'localhost';
$db = 'sk_system';
$user = 'root'; 
$pass = '';    
$charset = 'utf8mb4';
//$host = 'sql305.infinityfree.com';
//$db = 'if0_40236787_sk_system';
//$user = 'if0_40236787'; 
//$pass = 'oixm49CHSBD4k';    
//$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
