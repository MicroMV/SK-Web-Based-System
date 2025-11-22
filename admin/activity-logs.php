<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funtions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../public/signin.php");
    exit;
}

$filter_user = isset($_GET['user']) && $_GET['user'] != '' ? intval($_GET['user']) : null;
$filter_action = isset($_GET['action']) && $_GET['action'] != '' ? $_GET['action'] : null;
$filter_table = isset($_GET['table']) && $_GET['table'] != '' ? $_GET['table'] : null;
$filter_date = isset($_GET['date']) && $_GET['date'] != '' ? $_GET['date'] : null;

$query = "SELECT al.*, u.full_name, u.username 
          FROM activity_logs al 
          LEFT JOIN users u ON al.user_id = u.user_id 
          WHERE 1=1";
$params = [];

if ($filter_user) {
    $query .= " AND al.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_action) {
    $query .= " AND al.action = ?";
    $params[] = $filter_action;
}

if ($filter_table) {
    $query .= " AND al.table_name = ?";
    $params[] = $filter_table;
}

if ($filter_date) {
    $query .= " AND DATE(al.timestamp) = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY al.timestamp DESC LIMIT 500";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$users_stmt = $pdo->query("SELECT user_id, full_name, username FROM users ORDER BY full_name ASC");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

$actions = ['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE', 'UPLOAD'];
$tables = ['announcements', 'achievements', 'files', 'inventory', 'kk_members', 'users'];

$stats_query = "SELECT 
    COUNT(*) as total_activities,
    COUNT(DISTINCT user_id) as total_users,
    SUM(CASE WHEN DATE(timestamp) = CURDATE() THEN 1 ELSE 0 END) as today_activities
    FROM activity_logs";
$stats_stmt = $pdo->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="activity-logs.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <header class="admin-header">
        <div class="align">
            <div class="left-header">
                <img src="../Assets/Picture1.png" class="logo" alt="Logo 1">
                <img src="../Assets/Picture2.png" class="logo" alt="Logo 2">
                <h1>SK CAWIT PORTAL - ADMIN</h1>
            </div>
            <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
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
                <li><a href="create_sk_account.php"><i class="fas fa-user-plus"></i> Create Account</a></li>
                <li><a href="activity-logs.php" class="active"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="../public/logout.php" id="LogoutButton"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

    </header>

    <main class="admin-main">
        <div class="page-header">
            <h2><i class="fas fa-history"></i> Activity Logs</h2>
            <a href="activity-logs.php" class="btn-secondary">
                <i class="fas fa-sync-alt"></i> Clear Filters
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Activities</h4>
                <div class="number"><?= number_format($stats['total_activities']) ?></div>
            </div>
            <div class="stat-card">
                <h4>Active Users</h4>
                <div class="number"><?= number_format($stats['total_users']) ?></div>
            </div>
            <div class="stat-card">
                <h4>Today's Activities</h4>
                <div class="number"><?= number_format($stats['today_activities']) ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="user">Filter by User:</label>
                        <select name="user" id="user" class="form-input">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['user_id'] ?>" <?= $filter_user == $user['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['full_name']) ?> (@<?= htmlspecialchars($user['username']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label for="action">Filter by Action:</label>
                        <select name="action" id="action" class="form-input">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?= $action ?>" <?= $filter_action == $action ? 'selected' : '' ?>>
                                    <?= $action ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label for="table">Filter by Module:</label>
                        <select name="table" id="table" class="form-input">
                            <option value="">All Modules</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?= $table ?>" <?= $filter_table == $table ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $table)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label for="date">Filter by Date:</label>
                        <input type="date" name="date" id="date" class="form-input" value="<?= htmlspecialchars($filter_date ?? '') ?>">
                    </div>

                    <div class="filter-item">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <section class="dashboard-section">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="no-data">No activity logs found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="timestamp">
                                        <?= date('M d, Y', strtotime($log['timestamp'])) ?><br>
                                        <small><?= date('h:i A', strtotime($log['timestamp'])) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($log['full_name'] ?? 'Unknown') ?></strong><br>
                                        <small>@<?= htmlspecialchars($log['username'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <span class="action-badge action-<?= $log['action'] ?>">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $log['table_name']))) ?></td>
                                    <td><?= htmlspecialchars($log['description'] ?? $log['action']) ?></td>
                                    <td><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> SK Cawit Portal. All rights reserved.</p>
    </footer>

    <script>
        document.getElementById('hamburger').addEventListener('click', () => {
            document.getElementById('navigation').classList.toggle('active');
            document.getElementById('hamburger').classList.toggle('active');
        });
    </script>
</body>

</html>