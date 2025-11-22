<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database and functions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funtions.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
    $result = createAccount();
    $success_message = $result['success_message'];
    $error_message = $result['error_message'];
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create SK Account</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="create_acc.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <!-- Header -->
    <header class="admin-header">
        <div class="align">
            <div class="left-header">
                <img src="../Assets/Picture1.png" class="logo" alt="Logo 1">
                <img src="../Assets/Picture2.png" class="logo" alt="Logo 2">
                <h1>SK CAWIT PORTAL - ADMIN</h1>
            </div>
            <button class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <nav>
            <ul class="navigation" id="navigation">
                <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage-announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="manage-achievements.php"><i class="fas fa-trophy"></i> Achievements</a></li>
                <li><a href="manage-files.php"><i class="fas fa-folder"></i> Files</a></li>
                <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="view-profiles.php"><i class="fas fa-users"></i> KK Profiles</a></li>
                <li><a href="generate-codes.php"><i class="fas fa-ticket-alt"></i> Generate Codes</a></li>
                <li><a href="suggestions_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="create_sk_account.php" class="active"><i class="fas fa-user-plus"></i> Create Account</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="../public/logout.php" id="LogoutButton"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

    </header>
    <br>
    <!-- Main Content -->
    <main class="admin-content">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="create-account-section">
            <h2 class="section-title">
                <i class="fas fa-user-plus"></i>
                Create New SK Account
            </h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="createAccountForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i>
                            Username
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            placeholder="Enter username">
                        <small>Must be at least 4 characters. Only letters, numbers, and underscores allowed.</small>
                    </div>

                    <div class="form-group">
                        <label for="full_name">
                            <i class="fas fa-id-card"></i>
                            Full Name
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            required
                            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                            placeholder="Enter full name">
                        <small>Enter the full name of the user.</small>
                    </div>

                    <div class="form-group">
                        <label for="role">
                            <i class="fas fa-user-tag"></i>
                            Role
                            <span class="required">*</span>
                        </label>
                        <select id="role" name="role" required>
                            <option value="sk_official" <?php echo (isset($_POST['role']) && $_POST['role'] === 'sk_official') ? 'selected' : ''; ?>>SK Official</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        <small>Select the role for this account.</small>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                            <span class="required">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                placeholder="Enter password">
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                        <small>Must be at least 8 characters with uppercase, lowercase, and numbers.</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            Confirm Password
                            <span class="required">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                required
                                placeholder="Re-enter password">
                            <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                        </div>
                        <small>Re-enter the password to confirm.</small>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="create_account" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
    <!-- Manage Accounts Section -->
    <div class="manage-accounts-section">
        <h2 class="section-title">
            <i class="fas fa-users-cog"></i>
            Manage Accounts
        </h2>

        <div class="table-container">
            <table class="accounts-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Username</th>
                        <th><i class="fas fa-id-card"></i> Full Name</th>
                        <th><i class="fas fa-user-tag"></i> Role</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require_once '../config/database.php';

                    // Count total admins
                    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

                    $stmt = $pdo->query("SELECT user_id, username, full_name, role FROM users ORDER BY created_at DESC");
                    while ($row = $stmt->fetch()):
                        $isLastAdmin = ($row['role'] === 'admin' && $adminCount == 1);
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $row['role']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-action btn-edit"
                                    data-id="<?php echo $row['user_id']; ?>"
                                    data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                    data-fullname="<?php echo htmlspecialchars($row['full_name']); ?>"
                                    data-role="<?php echo htmlspecialchars($row['role']); ?>"
                                    data-islastadmin="<?php echo $isLastAdmin ? 'true' : 'false'; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="delete_account.php?id=<?php echo $row['user_id']; ?>"
                                    class="btn-action btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this account?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    </div>
    </main>

    <!-- Edit Account Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">
                <i class="fas fa-user-edit"></i>
                Edit Account
            </h2>
            <form id="editForm" method="POST" action="edit_account.php">
                <input type="hidden" name="user_id" id="edit_user_id" />

                <div class="form-group">
                    <label for="edit_username">
                        Username <span class="required">*</span>
                    </label>
                    <input type="text" id="edit_username" name="username" required pattern="[a-zA-Z0-9_]{4,}"><br>
                </div>

                <div class="form-group">
                    <label for="edit_full_name">
                        Full Name <span class="required">*</span>
                    </label>
                    <input type="text" id="edit_full_name" name="full_name" required><br>
                </div>

                <div class="form-group">
                    <label for="edit_role">
                        Role <span class="required">*</span>
                    </label>
                    <select id="edit_role" name="role" required>
                        <option value="sk_official">SK Official</option>
                        <option value="admin">Admin</option>
                    </select>
                    <small id="role_warning" style="color: #dc3545; display: none;">
                        Cannot change role - this is the only admin account.
                    </small>
                </div>

                <div class="form-group">
                    <br>
                    <label for="edit_password">
                        New Password (Optional)
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="edit_password" name="password" placeholder="New password">
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('edit_password')"></i>
                    </div>
                    <small>Must be at least 8 characters with uppercase, lowercase, and numbers.</small><br>
                </div>

                <div class="form-group">
                    <label for="edit_confirm_password">
                        Confirm New Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="edit_confirm_password" name="confirm_password" placeholder="Confirm new password">
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('edit_confirm_password')"></i>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="admin-footer">
        <p>&copy; <?php echo date('Y'); ?> SK Cawit Portal. All rights reserved.</p>
    </footer>

    <script src="../includes/createAcc.js">
    </script>
</body>

</html>