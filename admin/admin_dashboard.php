<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funtions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/signin.php');
    exit();
}

// Fetch dashboard statistics
$stats = getDashboardStats();
$recentAnnouncements = getRecentAnnouncements(5);
$recentAchievements = getRecentAchievements(5);
$recentMembers = getRecentMembers(10);
$lowStockItems = getLowStockItems(5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin-style.css">
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
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <nav>
            <ul class="navigation" id="navigation">
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage-announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="manage-achievements.php"><i class="fas fa-trophy"></i> Achievements</a></li>
                <li><a href="manage-files.php"><i class="fas fa-folder"></i> Files</a></li>
                <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="view-profiles.php"><i class="fas fa-users"></i> KK Profiles</a></li>
                <li><a href="generate-codes.php"><i class="fas fa-ticket-alt"></i> Generate Codes</a></li>
                <li><a href="suggestions_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="create_sk_account.php"><i class="fas fa-user-plus"></i> Create Account</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="../public/logout.php" id="LogoutButton"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>


    </header>

    <main class="admin-main">
        <section class="dashboard-stats">
            <h2>Dashboard Overview</h2>
            <div class="stats-grid">
                <div class="stats-grid">
                    <a href="view-profiles.php" style="text-decoration: none;">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #007b83;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['total_members']); ?></h3>
                                <p>Total KK Members</p>
                            </div>
                        </div>
                    </a>
                    <a href="manage-announcements.php" style="text-decoration: none;">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #28a745;">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['total_announcements']); ?></h3>
                                <p>Announcements</p>
                            </div>
                        </div>
                    </a>
                    <a href="manage-achievements.php" style="text-decoration: none;">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #ffc107;">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['total_achievements']); ?></h3>
                                <p>Achievements</p>
                            </div>
                        </div>
                    </a>
                    <a href="inventory.php" style="text-decoration: none;">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #dc3545;">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['total_inventory']); ?></h3>
                                <p>Inventory Items</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="manage-announcements.php?action=create" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Announcement</span>
                </a>
                <a href="manage-achievements.php?action=create" class="action-btn">
                    <i class="fas fa-award"></i>
                    <span>Add Achievement</span>
                </a>
                <a href="inventory.php?action=create" class="action-btn">
                    <i class="fas fa-box"></i>
                    <span>Add Inventory</span>
                </a>
                <a href="manage-files.php" class="action-btn">
                    <i class="fas fa-folder"></i>
                    <span>Upload Files</span>
                </a>
            </div>
        </section>

        <!-- Two Column Layout -->
        <div class="dashboard-grid">
            <!-- Recent Announcements -->
            <section class="dashboard-section">
                <h2><i class="fas fa-bullhorn"></i> Recent Announcements</h2>
                <div class="items-list">
                    <?php if (!empty($recentAnnouncements)): ?>
                        <?php foreach ($recentAnnouncements as $announcement): ?>
                            <div class="list-item">
                                <?php if (!empty($announcement['image_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($announcement['image_path']); ?>" alt="Announcement">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                    <p><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></p>
                                </div>
                                <a href="manage-announcements.php?id=<?php echo $announcement['announcement_id']; ?>" class="item-action">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">No announcements yet.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recent Achievements -->
            <section class="dashboard-section">
                <h2><i class="fas fa-trophy"></i> Recent Achievements</h2>
                <div class="items-list">
                    <?php if (!empty($recentAchievements)): ?>
                        <?php foreach ($recentAchievements as $achievement): ?>
                            <div class="list-item">
                                <?php if (!empty($achievement['image_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($achievement['image_path']); ?>" alt="Achievement">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($achievement['title']); ?></h4>
                                    <p><?php echo date('M d, Y', strtotime($achievement['achievement_date'])); ?></p>
                                </div>
                                <a href="manage-achievements.php?id=<?php echo $achievement['achievement_id']; ?>" class="item-action">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">No achievements yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Low Stock Alert -->
        <?php if (!empty($lowStockItems)): ?>
            <section class="dashboard-section alert-section">
                <h2><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h2>
                <div class="items-list">
                    <?php foreach ($lowStockItems as $item): ?>
                        <div class="list-item alert-item">
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                <p>Current Stock: <strong><?php echo $item['current_stock']; ?> <?php echo htmlspecialchars($item['unit']); ?></strong></p>
                            </div>
                            <a href="inventory.php?id=<?php echo $item['inventory_id']; ?>" class="item-action">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        <p>&copy; <?php echo date('Y'); ?> SK Cawit Portal. All rights reserved.</p>
    </footer>

    <script src="../includes/admin.js"></script>
</body>

</html>