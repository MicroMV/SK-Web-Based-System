<?php
session_start();

// Include database connection
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../public/signin.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid account ID.";
    header("Location: create_sk_account.php");
    exit();
}

$user_id = $_GET['id'];

// Prevent admin from deleting their own account
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot delete your own account.";
    header("Location: create_sk_account.php");
    exit();
}

try {
    // Get user info before deletion for logging
    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error_message'] = "Account not found.";
        header("Location: create_sk_account.php");
        exit();
    }
    
    // Delete the account
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Log the activity
    $action = "delete";
    $table_name = "users";
    $description = "Deleted account for user: " . $user['username'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $log_stmt->execute([$_SESSION['user_id'], $action, $table_name, $user_id, $description, $ip_address]);
    
    $_SESSION['success_message'] = "Account deleted successfully!";
    header("Location: create_sk_account.php");
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error deleting account: " . $e->getMessage();
    header("Location: create_sk_account.php");
    exit();
}
?>
