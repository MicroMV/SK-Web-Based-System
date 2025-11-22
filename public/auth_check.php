<?php

if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$timeout_duration = 1800; 

if (isset($_SESSION['login_time'])) {
    $elapsed_time = time() - $_SESSION['login_time'];
    
    if ($elapsed_time > $timeout_duration) {
        session_destroy();
        header("Location: signin.php?timeout=1");
        exit();
    }
}

// Update last activity time
$_SESSION['login_time'] = time();

function getCurrentUser() {
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name']
    ];
}
?>
