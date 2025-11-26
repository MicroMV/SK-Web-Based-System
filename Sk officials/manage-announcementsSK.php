<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funtions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sk_official') {
    header("Location: ../public/signin.php");
    exit;
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $announcement_id = $_POST['announcement_id'] ?? null;

    // Server-side validation
    if (strlen($title) > 200) {
        header("Location: manage-announcementsSK.php?error=Content too long");
        exit;
    }

    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/announcements/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        // Validate file size (5MB max)
        if ($_FILES['image']['size'] > 5242880) {
            header("Location: manage-announcementsSK.php?error=Image too large (max 5MB)");
            exit;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            header("Location: manage-announcementsSK.php?error=Invalid image type");
            exit;
        }

        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
        $image_path = 'uploads/announcements/' . $fileName;
    }

    if ($announcement_id) {
        $stmt = $pdo->prepare("UPDATE announcements SET title=?, content=?, image_path=COALESCE(?, image_path), updated_at=NOW() WHERE announcement_id=?");
        $stmt->execute([$title, $content, $image_path, $announcement_id]);
        logActivity($user_id, "UPDATE", 'announcements', $announcement_id, "Updated announcement - $title");
    } else {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, image_path, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $image_path, $user_id]);
        $new_id = $pdo->lastInsertId();
        logActivity($user_id, "CREATE", 'announcements', $new_id, "Created announcement - $title");
    }

    header("Location: manage-announcementsSK.php?success=1");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id=?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], "DELETE", 'announcements', $id, "Deleted announcement ID $id");
    header("Location: manage-announcementsSK.php?deleted=1");
    exit;
}

// Get announcements
$stmt = $pdo->query("SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Assets/Picture2.png">
    <title>Manage Announcements</title>
    <link rel="stylesheet" href="../admin/admin-style.css">
    <link rel="stylesheet" href="../admin/manage_announcements.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <header class="admin-header">
        <div class="align">
            <div class="left-header">
                <img src="../Assets/Picture1.png" class="logo" alt="Logo 1">
                <img src="../Assets/Picture2.png" class="logo" alt="Logo 2">
                <h1>SK CAWIT PORTAL - SK Officials</h1>
            </div>
            <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
        <nav>
            <ul class="navigation" id="navigation">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage-announcementsSK.php" class="active"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="manage-achievementsSK.php"><i class="fas fa-trophy"></i> Achievements</a></li>
                <li><a href="manage-filesSK.php"><i class="fas fa-folder"></i> Files</a></li>
                <li><a href="inventorySK.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="view-profilesSK.php"><i class="fas fa-users"></i> KK Profiles</a></li>
                <li><a href="generate-codesSK.php"><i class="fas fa-ticket-alt"></i> Generate Codes</a></li>
                <li><a href="suggestions_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="../public/logout.php" id="LogoutButton"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

    </header>

    <main class="admin-main">
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Announcement saved successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">
                <i class="fas fa-trash-alt"></i> Announcement deleted successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Error: <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h2><i class="fas fa-bullhorn"></i> Manage Announcements</h2>
            <button class="btn-primary" onclick="openModal()">
                <i class="fas fa-plus-circle"></i> Add Announcement
            </button>
        </div>

        <section class="dashboard-section">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Content</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($announcements)): ?>
                            <tr>
                                <td colspan="6" class="no-data">No announcements found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($announcements as $a): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($a['image_path'])): ?>
                                            <img src="../<?= htmlspecialchars($a['image_path']) ?>"
                                                alt="Announcement Image"
                                                class="announcement-thumbnail">
                                        <?php else: ?>
                                            <span class="no-image"><i class="fas fa-image"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['title']) ?></td>
                                    <td class="content-preview"><?= htmlspecialchars(substr($a['content'], 0, 80)) ?>...</td>
                                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                                    <td>
                                        <button class="table-btn edit" onclick='editAnnouncement(<?= json_encode($a) ?>)'>
                                            <i class="fas fa-edit"></i><span>Edit</span>
                                        </button>
                                        <a href="?delete=<?= $a['announcement_id'] ?>"
                                            class="table-btn delete"
                                            onclick="return confirm('Are you sure you want to delete this announcement?')">
                                            <i class="fas fa-trash"></i><span>Delete</span>
                                        </a>
                                    </td>
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

    <!-- Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Announcement</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="announcementForm">
                <input type="hidden" name="announcement_id" id="announcement_id">

                <div class="form-group">
                    <label for="title">Title: <span class="char-count" id="titleCount">0/200</span></label>
                    <input type="text"
                        name="title"
                        id="title"
                        required
                        class="form-input"
                        placeholder="Enter announcement title"
                        maxlength="200"
                        oninput="updateCharCount('title', 'titleCount', 200)">
                </div>

                <div class="form-group">
                    <label for="content">Content: <span class="char-count" id="contentCount">0/5000</span></label>
                    <textarea name="content"
                        id="content"
                        rows="5"
                        required
                        class="form-input"
                        placeholder="Enter announcement content"
                        maxlength="5000"
                        oninput="updateCharCount('content', 'contentCount', 5000)"></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Image (optional, max 5MB):</label>
                    <input type="file"
                        name="image"
                        id="image"
                        accept="image/jpeg,image/png,image/jpg,image/gif"
                        onchange="validateImage(this)">
                    <small>Allowed: JPG, PNG, GIF. Max size: 5MB</small>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Announcement
                </button>
            </form>
        </div>
    </div>

    <script src="../includes/manage_announcements.js"></script>
</body>

</html>