<?php
session_start();

// Include database connection
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../public/signin.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,}$/', $username)) {
        $errors[] = "Username must be at least 4 characters and contain only letters, numbers, and underscores.";
    }
    
    // Check if username already exists for another user
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = "Username already exists.";
    }
    
    // Validate full name
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    // Validate role
    if (!in_array($role, ['admin', 'sk_official'])) {
        $errors[] = "Invalid role selected.";
    }
    
    // Validate role change for last admin
    if ($role !== 'admin') {
        // Check if this is the last admin
        $adminCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = $adminCountStmt->fetchColumn();
        
        // Get current user's role
        $currentRoleStmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $currentRoleStmt->execute([$user_id]);
        $currentRole = $currentRoleStmt->fetchColumn();
        
        if ($currentRole === 'admin' && $adminCount == 1) {
            $errors[] = "Cannot change role. This is the only admin account in the system.";
        }
    }
    
    // Validate password if provided
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }
    
    // If no errors, update the account
    if (empty($errors)) {
        try {
            // Get the old role before updating
            $oldRoleStmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
            $oldRoleStmt->execute([$user_id]);
            $oldRole = $oldRoleStmt->fetchColumn();
            
            // If password is provided, update with password
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, role = ?, unique_code = ? WHERE user_id = ?");
                $stmt->execute([$username, $full_name, $role, $hashed_password, $user_id]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, role = ? WHERE user_id = ?");
                $stmt->execute([$username, $full_name, $role, $user_id]);
            }
            
            // Log the activity
            $action = "update";
            $table_name = "users";
            $description = "Updated account for user: " . $username;
            $ip_address = $_SERVER['REMOTE_ADDR'];
            
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $log_stmt->execute([$_SESSION['user_id'], $action, $table_name, $user_id, $description, $ip_address]);
            
            // Check if the edited user is the currently logged-in user and role changed
            if ($user_id == $_SESSION['user_id'] && $oldRole !== $role) {
                // Update session role
                $_SESSION['role'] = $role;
                
                // Redirect based on new role
                if ($role === 'admin') {
                    $_SESSION['success_message'] = "Your role has been changed to Admin. Redirecting to admin dashboard...";
                    header("Location: ../admin/admin_dashboard.php");
                    exit();
                } else if ($role === 'sk_official') {
                    $_SESSION['success_message'] = "Your role has been changed to SK Official. Redirecting to SK dashboard...";
                    header("Location: ../Sk officials/dashboard.php");
                    exit();
                }
            }
            
            $_SESSION['success_message'] = "Account updated successfully!";
            header("Location: create_sk_account.php");
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating account: " . $e->getMessage();
            header("Location: create_sk_account.php");
            exit();
        }
    } else {
        // Store errors in session
        $_SESSION['error_message'] = implode("<br>", $errors);
        header("Location: create_sk_account.php");
        exit();
    }
    
} else {
    // If not POST request, redirect back
    header("Location: create_sk_account.php");
    exit();
}
?>
