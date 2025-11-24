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
    $description = trim($_POST['description']);
    $achievement_date = $_POST['achievement_date'];
    $user_id = $_SESSION['user_id'];
    $achievement_id = $_POST['achievement_id'] ?? null;

    // Server-side validation
    if (strlen($title) > 200) {
        header("Location: manage-achievementsSK.php?error=Title too long");
        exit;
    }
    if (strlen($description) > 5000) {
        header("Location: manage-achievementsSK.php?error=Description too long");
        exit;
    }

    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/achievements/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        // Validate file size (5MB max)
        if ($_FILES['image']['size'] > 5242880) {
            header("Location: manage-achievementsSK.php?error=Image too large (max 5MB)");
            exit;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            header("Location: manage-achievementsSK.php?error=Invalid image type");
            exit;
        }

        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
        $image_path = 'uploads/achievements/' . $fileName;
    }

    if ($achievement_id) {
        $stmt = $pdo->prepare("UPDATE achievements SET title=?, description=?, image_path=COALESCE(?, image_path), achievement_date=?, updated_at=NOW() WHERE achievement_id=?");
        $stmt->execute([$title, $description, $image_path, $achievement_date, $achievement_id]);
        logActivity($user_id, "UPDATE", 'achievements', $achievement_id, "Updated achievement - $title");
    } else {
        if (empty($image_path)) {
            header("Location: manage-achievementsSK.php?error=Image is required for new achievements");
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO achievements (title, description, image_path, achievement_date, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $image_path, $achievement_date, $user_id]);
        $new_id = $pdo->lastInsertId();
        logActivity($user_id, "CREATE", 'achievements', $new_id, "Created achievement - $title");
    }

    header("Location: manage-achievementsSK.php?success=1");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM achievements WHERE achievement_id=?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], "DELETE", 'achievements', $id, "Deleted achievement ID $id");
    header("Location: manage-achievementsSK.php?deleted=1");
    exit;
}

// Get achievements
$stmt = $pdo->query("SELECT a.*, u.full_name FROM achievements a JOIN users u ON a.user_id = u.user_id ORDER BY a.achievement_date DESC");
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Achievements</title>
    <link rel="stylesheet" href="../admin/admin-style.css">
    <link rel="stylesheet" href="../admin/manage_achievements.css">
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
                <li><a href="manage-announcementsSK.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="manage-achievementsSK.php" class="active"><i class="fas fa-trophy"></i> Achievements</a></li>
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
                <i class="fas fa-check-circle"></i> Achievement saved successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">
                <i class="fas fa-trash-alt"></i> Achievement deleted successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Error: <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h2><i class="fas fa-trophy"></i> Manage Achievements</h2>
            <button class="btn-primary" onclick="openModal()">
                <i class="fas fa-plus-circle"></i> Add Achievement
            </button>
        </div>

        <section class="dashboard-section">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Author</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($achievements)): ?>
                            <tr>
                                <td colspan="6" class="no-data">No achievements found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($achievements as $a): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($a['image_path'])): ?>
                                            <img src="../<?= htmlspecialchars($a['image_path']) ?>"
                                                alt="Achievement Image"
                                                class="achievement-thumbnail">
                                        <?php else: ?>
                                            <span class="no-image"><i class="fas fa-trophy"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['title']) ?></td>
                                    <td class="description-preview"><?= htmlspecialchars(substr($a['description'], 0, 80)) ?>...</td>
                                    <td><?= date('M d, Y', strtotime($a['achievement_date'])) ?></td>
                                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                                    <td>
                                        <button class="table-btn edit" onclick='editAchievement(<?= json_encode($a) ?>)'>
                                            <i class="fas fa-edit"></i><span>Edit</span>
                                        </button>
                                        <a href="?delete=<?= $a['achievement_id'] ?>"
                                            class="table-btn delete"
                                            onclick="return confirm('Are you sure you want to delete this achievement?')">
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
    <div id="achievementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Achievement</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="achievement_id" id="achievement_id">

                <div class="form-group">
                    <label for="title">Title: <span class="char-count" id="titleCount">0/200</span></label>
                    <input type="text"
                        name="title"
                        id="title"
                        required
                        class="form-input"
                        placeholder="Enter achievement title"
                        maxlength="200"
                        oninput="updateCharCount('title', 'titleCount', 200)">
                </div>

                <div class="form-group">
                    <label for="description">Description: <span class="char-count" id="descCount">0/5000</span></label>
                    <textarea name="description"
                        id="description"
                        rows="5"
                        required
                        class="form-input"
                        placeholder="Enter achievement description"
                        maxlength="5000"
                        oninput="updateCharCount('description', 'descCount', 5000)"></textarea>
                </div>

                <div class="form-group">
                    <label for="achievement_date">Achievement Date:</label>
                    <input type="date"
                        name="achievement_date"
                        id="achievement_date"
                        required
                        class="form-input"
                        max="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="image">Image <span id="imageRequired">(required, max 5MB)</span>:</label>
                    <input type="file"
                        name="image"
                        id="image"
                        accept="image/jpeg,image/png,image/jpg,image/gif"
                        onchange="validateImage(this)">
                    <small>Allowed: JPG, PNG, GIF. Max size: 5MB</small>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Achievement
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('achievementModal');

        function openModal() {
            document.getElementById('modalTitle').innerText = "Add Achievement";
            document.getElementById('achievement_id').value = '';
            document.getElementById('title').value = '';
            document.getElementById('description').value = '';
            document.getElementById('achievement_date').value = '';
            document.getElementById('image').value = '';
            document.getElementById('image').required = true;
            document.getElementById('imageRequired').style.display = 'inline';
            updateCharCount('title', 'titleCount', 200);
            updateCharCount('description', 'descCount', 5000);
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target == modal) {
                closeModal();
            }
        }

        function editAchievement(data) {
            document.getElementById('modalTitle').innerText = "Edit Achievement";
            document.getElementById('achievement_id').value = data.achievement_id;
            document.getElementById('title').value = data.title;
            document.getElementById('description').value = data.description;
            document.getElementById('achievement_date').value = data.achievement_date;
            document.getElementById('image').required = false;
            document.getElementById('imageRequired').style.display = 'none';
            updateCharCount('title', 'titleCount', 200);
            updateCharCount('description', 'descCount', 5000);
            modal.style.display = 'block';
        }

        function updateCharCount(fieldId, countId, maxLength) {
            const field = document.getElementById(fieldId);
            const counter = document.getElementById(countId);
            const currentLength = field.value.length;
            counter.textContent = currentLength + '/' + maxLength;

            counter.classList.remove('warning', 'danger');
            if (currentLength >= maxLength) {
                counter.classList.add('danger');
            } else if (currentLength > maxLength * 0.9) {
                counter.classList.add('danger');
            } else if (currentLength > maxLength * 0.75) {
                counter.classList.add('warning');
            }
        }

        function validateImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileSize = file.size / 1024 / 1024;
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];

                if (fileSize > 5) {
                    alert('Image size must be less than 5MB!');
                    input.value = '';
                    return false;
                }

                if (!validTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and GIF images are allowed!');
                    input.value = '';
                    return false;
                }
            }
        }

        document.getElementById('hamburger').addEventListener('click', () => {
            document.getElementById('navigation').classList.toggle('active');
            document.getElementById('hamburger').classList.toggle('active');
        });
    </script>
</body>

</html>