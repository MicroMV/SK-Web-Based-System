<?php

require_once('../config/database.php');

$username = "admin"; 
$password = "Admin@123"; 
$full_name = "SK Administrator";
$role = "admin";

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, unique_code, role, full_name) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$username, $hashed_password, $role, $full_name]);
    
    echo "Admin user created successfully!<br>";
    echo "Username: " . $username . "<br>";
    echo "Password: " . $password . "<br>";
    echo "<br><strong>IMPORTANT: Change the password after first login and DELETE this file!</strong>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
