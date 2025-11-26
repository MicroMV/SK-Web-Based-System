<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $feedback_id = intval($_POST['feedback_id']);
        $new_status = $_POST['status'];
        $admin_response = $_POST['admin_response'];

        $stmt = $pdo->prepare("UPDATE suggestions_feedback SET status = ?, admin_response = ?, responded_by = ?, reviewed_at = NOW() WHERE feedback_id = ?");
        $stmt->execute([$new_status, $admin_response, $user_id, $feedback_id]);

        // Log activity
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, 'UPDATE', 'suggestions_feedback', ?, 'Updated feedback status')");
        $log_stmt->execute([$user_id, $feedback_id]);

        $success_message = "Feedback updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating feedback: " . $e->getMessage();
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_feedback'])) {
    try {
        $feedback_id = intval($_POST['feedback_id']);

        $stmt = $pdo->prepare("DELETE FROM suggestions_feedback WHERE feedback_id = ?");
        $stmt->execute([$feedback_id]);

        // Log activity
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, 'DELETE', 'suggestions_feedback', ?, 'Deleted feedback entry')");
        $log_stmt->execute([$user_id, $feedback_id]);

        $success_message = "Feedback deleted successfully!";
    } catch (PDOException $e) {
        $error_message = "Error deleting feedback: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT sf.*, u.username as responder_name 
          FROM suggestions_feedback sf 
          LEFT JOIN users u ON sf.responded_by = u.user_id 
          WHERE 1=1";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND sf.status = ?";
    $params[] = $status_filter;
}

if ($category_filter !== 'all') {
    $query .= " AND sf.category = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $query .= " AND sf.message LIKE ?";
    $params[] = "%$search%";
}

$query .= " ORDER BY sf.submitted_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $feedbacks = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching feedback: " . $e->getMessage();
    $feedbacks = [];
}

// Get statistics
try {
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN category = 'suggestion' THEN 1 ELSE 0 END) as suggestions,
        SUM(CASE WHEN category = 'feedback' THEN 1 ELSE 0 END) as feedbacks,
        SUM(CASE WHEN category = 'concern' THEN 1 ELSE 0 END) as concerns
        FROM suggestions_feedback";
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'reviewed' => 0,
        'resolved' => 0,
        'suggestions' => 0,
        'feedbacks' => 0,
        'concerns' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Assets/Picture2.png">
    <title>Suggestions & Feedback</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="suggestion.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="suggestions_feedback.php" class="active"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="create_sk_account.php"><i class="fas fa-user-plus"></i> Create Account</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="../public/logout.php" id="LogoutButton"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>


    </header>


    <!-- Main Content -->
    <main class="admin-main">
        <h2 style="color: #213555; margin-bottom: 1.5rem;">
            <i class="fas fa-comments"></i> Suggestions & Feedback
        </h2>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #213555;">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Submissions</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #ffc107;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #213555;">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['reviewed']; ?></h3>
                    <p>Reviewed</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #28a745;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['resolved']; ?></h3>
                    <p>Resolved</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-group">
                    <div>
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>

                    <div>
                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <option value="suggestion" <?php echo $category_filter === 'suggestion' ? 'selected' : ''; ?>>Suggestion</option>
                            <option value="feedback" <?php echo $category_filter === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                            <option value="concern" <?php echo $category_filter === 'concern' ? 'selected' : ''; ?>>Concern</option>
                            <option value="other" <?php echo $category_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="search">Search Message</label>
                        <input type="text" name="search" id="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="suggestions_feedback.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Feedback List -->
        <div class="feedback-list">
            <?php if (count($feedbacks) > 0): ?>
                <?php foreach ($feedbacks as $feedback): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div class="feedback-meta">
                                <span class="badge badge-<?php echo htmlspecialchars($feedback['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($feedback['status'])); ?>
                                </span>
                                <span class="badge badge-<?php echo htmlspecialchars($feedback['category']); ?>">
                                    <i class="fas fa-tag"></i> <?php echo ucfirst(htmlspecialchars($feedback['category'])); ?>
                                </span>
                            </div>
                            <div class="feedback-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('M d, Y h:i A', strtotime($feedback['submitted_at'])); ?>
                            </div>
                        </div>

                        <div class="feedback-message">
                            <p><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                        </div>

                        <?php if (!empty($feedback['admin_response'])): ?>
                            <div class="feedback-response">
                                <h4>
                                    <i class="fas fa-reply"></i> Admin Response
                                </h4>
                                <p><?php echo nl2br(htmlspecialchars($feedback['admin_response'])); ?></p>
                                <div class="feedback-date" style="margin-top: 0.5rem;">
                                    <i class="fas fa-user"></i>
                                    Responded by: <?php echo htmlspecialchars($feedback['responder_name'] ?? 'Unknown'); ?>
                                    <?php if ($feedback['reviewed_at']): ?>
                                        on <?php echo date('M d, Y', strtotime($feedback['reviewed_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="feedback-actions">
                            <button class="btn btn-primary" onclick="openModal(<?php echo $feedback['feedback_id']; ?>, '<?php echo htmlspecialchars($feedback['status']); ?>', `<?php echo htmlspecialchars($feedback['admin_response'] ?? '', ENT_QUOTES); ?>`)">
                                <i class="fas fa-edit"></i> Respond / Update
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                <button type="submit" name="delete_feedback" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>No feedback found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> SK Cawit Portal. All rights reserved.</p>
    </footer>

    <!-- Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-reply"></i> Respond to Feedback</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="feedback_id" id="modal_feedback_id">

                <div class="form-group">
                    <label for="modal_status">Status</label>
                    <select name="status" id="modal_status" required>
                        <option value="pending">Pending</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="modal_response">Admin Response</label>
                    <textarea name="admin_response" id="modal_response" placeholder="Enter your response here..."></textarea>
                </div>

                <div class="filter-actions">
                    <button type="submit" name="update_status" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Response
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Hamburger menu toggle
        const hamburger = document.getElementById('hamburger');
        const navigation = document.getElementById('navigation');

        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navigation.classList.toggle('active');
        });

        // Modal functions
        function openModal(feedbackId, status, response) {
            document.getElementById('modal_feedback_id').value = feedbackId;
            document.getElementById('modal_status').value = status;
            document.getElementById('modal_response').value = response;
            document.getElementById('updateModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('updateModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>