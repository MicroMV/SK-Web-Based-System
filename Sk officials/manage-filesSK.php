<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funtions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sk_official') {
    header("Location: ../public/signin.php");
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file_name = $_FILES['file']['name'];
    $file_size = $_FILES['file']['size'];
    $file_type = $_FILES['file']['type'];
    $file_tmp = $_FILES['file']['tmp_name'];
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $user_id = $_SESSION['user_id'];

    // Validate file size (10MB max)
    if ($file_size > 10485760) {
        header("Location: manage-filesSK.php?error=File too large (max 10MB)");
        exit;
    }

    // Allowed file types
    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'image/jpg'
    ];

    if (!in_array($file_type, $allowed_types)) {
        header("Location: manage-filesSK.php?error=Invalid file type");
        exit;
    }

    // Create upload directory
    $uploadDir = '../uploads/files/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    // Generate unique filename
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_name = time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $uploadDir . $unique_name;

    // Upload file
    if (move_uploaded_file($file_tmp, $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO files (file_name, original_name, file_path, file_size, file_type, category, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$unique_name, $file_name, 'uploads/files/' . $unique_name, $file_size, $file_type, $category, $description, $user_id]);
        $new_id = $pdo->lastInsertId();
        logActivity($user_id, "UPLOAD", 'files', $new_id, "Uploaded file - $file_name");

        header("Location: manage-filesSK.php?success=1");
        exit;
    } else {
        header("Location: manage-filesSK.php?error=Upload failed");
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Get file path before deleting
    $stmt = $pdo->prepare("SELECT file_path FROM files WHERE file_id=?");
    $stmt->execute([$id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        // Delete file from server
        $full_path = '../' . $file['file_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM files WHERE file_id=?");
        $stmt->execute([$id]);
        logActivity($_SESSION['user_id'], "DELETE", 'files', $id, "Deleted file ID $id");
    }

    header("Location: manage-filesSK.php?deleted=1");
    exit;
}

// Get files grouped by category
$stmt = $pdo->query("SELECT f.*, u.full_name FROM files f JOIN users u ON f.user_id = u.user_id ORDER BY f.category, f.uploaded_at DESC");
$all_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group files by category
$files_by_category = [
    'Budget' => [],
    'Minutes' => [],
    'Purchase' => [],
    'Others' => []
];

foreach ($all_files as $file) {
    $files_by_category[$file['category']][] = $file;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Management</title>
    <link rel="stylesheet" href="../admin/admin-style.css">
    <link rel="stylesheet" href="../admin/manage-files.css">
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
                <li><a href="manage-achievementsSK.php"><i class="fas fa-trophy"></i> Achievements</a></li>
                <li><a href="manage-filesSK.php" class="active"><i class="fas fa-folder"></i> Files</a></li>
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
                <i class="fas fa-check-circle"></i> File uploaded successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">
                <i class="fas fa-trash-alt"></i> File deleted successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Error: <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h2><i class="fas fa-folder-open"></i> File Management</h2>
            <button class="add-btn" onclick="openModal()">
                <i class="fas fa-cloud-upload-alt"></i> Upload File
            </button>
        </div>

        <!-- Budget Files -->
        <div class="category-section">
            <div class="category-header">
                <i class="fas fa-money-bill-wave" style="font-size: 1.8rem; color: #007b83;"></i>
                <h3>Budget Documents</h3>
                <span class="category-badge"><?= count($files_by_category['Budget']) ?> files</span>
            </div>
            <div class="file-grid">
                <?php if (empty($files_by_category['Budget'])): ?>
                    <p class="no-files">No budget files uploaded yet.</p>
                <?php else: ?>
                    <?php foreach ($files_by_category['Budget'] as $file): ?>
                        <div class="file-card">
                            <div class="file-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="file-name"><?= htmlspecialchars($file['original_name']) ?></div>
                            <div class="file-meta"><i class="fas fa-user"></i> <?= htmlspecialchars($file['full_name']) ?></div>
                            <div class="file-meta"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($file['uploaded_at'])) ?></div>
                            <div class="file-meta"><i class="fas fa-hdd"></i> <?= number_format($file['file_size'] / 1024, 2) ?> KB</div>
                            <?php if ($file['description']): ?>
                                <div class="file-meta" style="margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($file['description']) ?></div>
                            <?php endif; ?>
                            <div class="file-actions">
                                <a href="download.php?id=<?= $file['file_id'] ?>" class="file-btn download">
                                    <i class="fas fa-download"></i> Download
                                </a>

                                <a href="?delete=<?= $file['file_id'] ?>" class="file-btn delete" onclick="return confirm('Delete this file?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Minutes Files -->
        <div class="category-section">
            <div class="category-header">
                <i class="fas fa-clipboard-list" style="font-size: 1.8rem; color: #007b83;"></i>
                <h3>Minutes of Meetings</h3>
                <span class="category-badge"><?= count($files_by_category['Minutes']) ?> files</span>
            </div>
            <div class="file-grid">
                <?php if (empty($files_by_category['Minutes'])): ?>
                    <p class="no-files">No minutes files uploaded yet.</p>
                <?php else: ?>
                    <?php foreach ($files_by_category['Minutes'] as $file): ?>
                        <div class="file-card">
                            <div class="file-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="file-name"><?= htmlspecialchars($file['original_name']) ?></div>
                            <div class="file-meta"><i class="fas fa-user"></i> <?= htmlspecialchars($file['full_name']) ?></div>
                            <div class="file-meta"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($file['uploaded_at'])) ?></div>
                            <div class="file-meta"><i class="fas fa-hdd"></i> <?= number_format($file['file_size'] / 1024, 2) ?> KB</div>
                            <?php if ($file['description']): ?>
                                <div class="file-meta" style="margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($file['description']) ?></div>
                            <?php endif; ?>
                            <div class="file-actions">
                                <a href="download.php?id=<?= $file['file_id'] ?>" class="file-btn download">
                                    <i class="fas fa-download"></i> Download
                                </a>

                                <a href="?delete=<?= $file['file_id'] ?>" class="file-btn delete" onclick="return confirm('Delete this file?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Purchase Files -->
        <div class="category-section">
            <div class="category-header">
                <i class="fas fa-shopping-cart" style="font-size: 1.8rem; color: #007b83;"></i>
                <h3>Purchase Orders & Receipts</h3>
                <span class="category-badge"><?= count($files_by_category['Purchase']) ?> files</span>
            </div>
            <div class="file-grid">
                <?php if (empty($files_by_category['Purchase'])): ?>
                    <p class="no-files">No purchase files uploaded yet.</p>
                <?php else: ?>
                    <?php foreach ($files_by_category['Purchase'] as $file): ?>
                        <div class="file-card">
                            <div class="file-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="file-name"><?= htmlspecialchars($file['original_name']) ?></div>
                            <div class="file-meta"><i class="fas fa-user"></i> <?= htmlspecialchars($file['full_name']) ?></div>
                            <div class="file-meta"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($file['uploaded_at'])) ?></div>
                            <div class="file-meta"><i class="fas fa-hdd"></i> <?= number_format($file['file_size'] / 1024, 2) ?> KB</div>
                            <?php if ($file['description']): ?>
                                <div class="file-meta" style="margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($file['description']) ?></div>
                            <?php endif; ?>
                            <div class="file-actions">
                                <a href="download.php?id=<?= $file['file_id'] ?>" class="file-btn download">
                                    <i class="fas fa-download"></i> Download
                                </a>

                                <a href="?delete=<?= $file['file_id'] ?>" class="file-btn delete" onclick="return confirm('Delete this file?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Other Files -->
        <div class="category-section">
            <div class="category-header">
                <i class="fas fa-folder" style="font-size: 1.8rem; color: #007b83;"></i>
                <h3>Other Documents</h3>
                <span class="category-badge"><?= count($files_by_category['Others']) ?> files</span>
            </div>
            <div class="file-grid">
                <?php if (empty($files_by_category['Others'])): ?>
                    <p class="no-files">No other files uploaded yet.</p>
                <?php else: ?>
                    <?php foreach ($files_by_category['Others'] as $file): ?>
                        <div class="file-card">
                            <div class="file-icon">
                                <i class="fas fa-file"></i>
                            </div>
                            <div class="file-name"><?= htmlspecialchars($file['original_name']) ?></div>
                            <div class="file-meta"><i class="fas fa-user"></i> <?= htmlspecialchars($file['full_name']) ?></div>
                            <div class="file-meta"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($file['uploaded_at'])) ?></div>
                            <div class="file-meta"><i class="fas fa-hdd"></i> <?= number_format($file['file_size'] / 1024, 2) ?> KB</div>
                            <?php if ($file['description']): ?>
                                <div class="file-meta" style="margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($file['description']) ?></div>
                            <?php endif; ?>
                            <div class="file-actions">
                                <a href="download.php?id=<?= $file['file_id'] ?>" class="file-btn download">
                                    <i class="fas fa-download"></i> Download
                                </a>

                                <a href="?delete=<?= $file['file_id'] ?>" class="file-btn delete" onclick="return confirm('Delete this file?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> SK Cawit Portal. All rights reserved.</p>
    </footer>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload File</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category">Document Category:</label>
                    <select name="category" id="category" required class="form-input">
                        <option value="">-- Select Category --</option>
                        <option value="Budget">Budget Documents</option>
                        <option value="Minutes">Minutes of Meetings</option>
                        <option value="Purchase">Purchase Orders & Receipts</option>
                        <option value="Others">Other Documents</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="file">Select File:</label>
                    <input type="file" name="file" id="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Allowed: PDF, Word, Excel, Images (Max 10MB)
                    </small>
                </div>

                <div class="form-group">
                    <label for="description">Description (optional):</label>
                    <textarea name="description" id="description" class="form-input" placeholder="Brief description of the document"></textarea>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-upload"></i> Upload File
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('uploadModal');

        function openModal() {
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

        document.getElementById('hamburger').addEventListener('click', () => {
            document.getElementById('navigation').classList.toggle('active');
            document.getElementById('hamburger').classList.toggle('active');
        });
    </script>
</body>

</html>