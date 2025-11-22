<?php
session_start();
require_once('../config/database.php');

// Log the logout activity
if (isset($_SESSION['user_id'])) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, description, ip_address) 
            VALUES (?, 'logout', 'users', 'User logged out', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: index.php");
exit();
?>
