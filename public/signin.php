<?php
session_start();
require_once('../config/database.php');
require_once('../includes/funtions.php');

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/admin_dashboard.php");
    } else {
        header("Location: ../Sk officials/dashboard.php");
    }
    exit();
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; 
    
    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no validation errors, check credentials
    if (empty($errors)) {
        global $pdo;
        
        try {
            // Get user from database
            $stmt = $pdo->prepare("SELECT user_id, username, unique_code, role, full_name FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['unique_code'])) {
                // Password is correct - create session
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['login_time'] = time();
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                
                // Log the activity
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, table_name, description, ip_address) 
                    VALUES (?, 'login', 'users', 'User logged in', ?)
                ");
                $stmt->execute([$user['user_id'], $_SERVER['REMOTE_ADDR']]);

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/admin_dashboard.php");
                } else {
                    header("Location: ../Sk officials/dashboard.php");
                }
                exit();

                
            } else {
                $errors['general'] = 'Invalid username or password';
                
                
                if ($user) {
                    $stmt = $pdo->prepare("
                        INSERT INTO activity_logs (user_id, action, table_name, description, ip_address) 
                        VALUES (?, 'failed_login', 'users', 'Failed login attempt', ?)
                    ");
                    $stmt->execute([$user['user_id'], $_SERVER['REMOTE_ADDR']]);
                }
            }
            
        } catch (PDOException $e) {
            $errors['general'] = 'Login failed. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Assets/Picture2.png">
    <title>Sign In</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="signin.css">
</head>
<body>
    <header>
        <div class="align">
            <img src="../Assets/Picture1.png" class="logo" />
            <img src="../Assets/Picture2.png" class="logo" />
            <a href="index.php" class="title-link">
                <h1>SK CAWIT PORTAL</h1>
            </a>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
      
        <ul class="navigation" id="navigation">
            <li><a href="kk_registration.php">KK Profile</a></li>
            <li><a href="suggestion.php">Suggestion</a></li>
            <li><a href="signin.php" id="SignInButton">Sign In</a></li>
        </ul>
    </header>

    <div class="signin-container">
        <div class="signin-card">
            <div class="signin-header">
                <div class="icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h2>SK Official Sign In</h2>
                <p class="subtitle">Access your portal dashboard</p>
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="error-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?= $errors['general'] ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="signin-form">
                <div class="form-group">
                    <label for="username">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required 
                        autofocus
                    >
                    <?php if (isset($errors['username'])): ?>
                        <div class="error-message"><?= $errors['username'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Password
                    </label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                        >
                        <button type="button" class="toggle-password" id="togglePassword">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error-message"><?= $errors['password'] ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="signin-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Sign In
                </button>
            </form>

            <div class="signin-footer">
                <p class="info-text">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    For SK officials and administrators only
                </p>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> SK Cawit Portal. All rights reserved.</p>
    </footer>

    <script src="../includes/signin.js"></script>
</body>
</html>
