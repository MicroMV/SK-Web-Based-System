<?php 
function getAnnouncements() {
    global $pdo;
    $sql = "SELECT a.*, u.full_name AS posted_by
        FROM announcements AS a
        LEFT JOIN users AS u ON a.user_id = u.user_id
        WHERE a.is_active = TRUE 
        ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getAchievements() {
    global $pdo;  
    
    $sql = "SELECT * FROM achievements";
    $stmt = $pdo->prepare($sql);  
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $results;
}

// Dashboard Statistics
function getDashboardStats() {
    global $pdo;
    
    $stats = [];
    
    // Total Members
    $stmt = $pdo->query("SELECT COUNT(*) FROM kk_members");
    $stats['total_members'] = $stmt->fetchColumn();
    
    // Total Announcements
    $stmt = $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_active = 1");
    $stats['total_announcements'] = $stmt->fetchColumn();
    
    // Total Achievements
    $stmt = $pdo->query("SELECT COUNT(*) FROM achievements");
    $stats['total_achievements'] = $stmt->fetchColumn();
    
    // Total Inventory Items
    $stmt = $pdo->query("SELECT COUNT(*) FROM inventory");
    $stats['total_inventory'] = $stmt->fetchColumn();
    
    return $stats;
}

// Get Recent Announcements
function getRecentAnnouncements($limit = 5) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM announcements 
        WHERE is_active = 1 
        ORDER BY created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get Recent Achievements
function getRecentAchievements($limit = 5) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM achievements 
        ORDER BY achievement_date DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get Recent Members
function getRecentMembers($limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM kk_members 
        ORDER BY submitted_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get Low Stock Items
function getLowStockItems($limit = 5) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM inventory 
        WHERE current_stock < 10 
        ORDER BY current_stock ASC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Log Activity
function logActivity($userId, $action, $tableName, $recordId = null, $description = null) {
    global $pdo;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs 
        (user_id, action, table_name, record_id, description, ip_address) 
        VALUES (:user_id, :action, :table_name, :record_id, :description, :ip_address)
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
        ':table_name' => $tableName,
        ':record_id' => $recordId,
        ':description' => $description,
        ':ip_address' => $ipAddress
    ]);
}

// Sanitize Input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validate Email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function createAccount() {
    global $pdo; 
    $success_message = '';
    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
        // Sanitize inputs
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'];

        $errors = [];

        // Username validation
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 4) {
            $errors[] = "Username must be at least 4 characters long.";
        } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }

        // Check if username exists
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username already exists. Please choose another.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }

        // Full name validation
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        } elseif (strlen($full_name) < 3) {
            $errors[] = "Full name must be at least 3 characters long.";
        }

        // Password validation
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/", $password)) {
            $errors[] = "Password must include uppercase, lowercase, and a number.";
        }

        // Confirm password
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        // Role validation
        if (!in_array($role, ['admin', 'sk_official'])) {
            $errors[] = "Invalid role selected.";
        }

        // Proceed if no errors
        if (empty($errors)) {
            try {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert into database using 'unique_code' field
                $insert_query = "INSERT INTO users (username, unique_code, role, full_name, created_at)
                                 VALUES (:username, :unique_code, :role, :full_name, NOW())";
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->execute([
                    ':username' => $username,
                    ':unique_code' => $hashed_password,
                    ':role' => $role,
                    ':full_name' => $full_name
                ]);

                $new_user_id = $pdo->lastInsertId();

                // Log this activity
                $log_query = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address, timestamp)
                              VALUES (:user_id, :action, :table_name, :record_id, :description, :ip_address, NOW())";
                $log_stmt = $pdo->prepare($log_query);
                $log_stmt->execute([
                    ':user_id'     => $_SESSION['user_id'],
                    ':action'      => "CREATE",
                    ':table_name'  => "users",
                    ':record_id'   => $new_user_id,
                    ':description' => "Created new account with username: $username (Role: $role)",
                    ':ip_address'  => $_SERVER['REMOTE_ADDR']
                ]);

                $success_message = "Account created successfully! Username: <strong>$username</strong>";

                // Clear the form
                $_POST = [];
            } catch (PDOException $e) {
                $error_message = "Error creating account: " . $e->getMessage();
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }

    return [
        'success_message' => $success_message,
        'error_message' => $error_message
    ];
}

?>