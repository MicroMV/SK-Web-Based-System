<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/admin_dashboard.php");
    } else {
        header("Location: ../Sk officials/dashboard.php");
    }
    exit();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funtions.php';

$announcements = getAnnouncements();
$achievements = getAchievements();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SK Cawit Portal</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

</head>

<body>
    <header>
        <div class="align">
            <img src="../Assets/Picture1.png" class="logo" />
            <img src="../Assets/Picture2.png" class="logo" />
            <h1>SK CAWIT PORTAL</h1>
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
    <main style="flex: 1;">
        <!-- ANNOUNCEMENTS SECTION -->
        <section class="announcements">
            <h2 id="announcementName">LATEST ANNOUNCEMENTS</h2>
            <div class="ann-grid">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="AnnContent"
                            data-title="<?= htmlspecialchars($announcement['title']) ?>"
                            data-content="<?= htmlspecialchars($announcement['content']) ?>"
                            data-user="<?= htmlspecialchars($announcement['posted_by'] ?? 'Unknown') ?>"
                            data-date="<?= htmlspecialchars($announcement['created_at']) ?>"
                            data-image="../<?= htmlspecialchars($announcement['image_path'] ?? '') ?>">

                            <?php if (!empty($announcement['image_path'])): ?>
                                <img src="../<?= htmlspecialchars($announcement['image_path']) ?>" alt="Announcement" class="AnnImage">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                            <?php endif; ?>


                            <h3 class="title"><?= htmlspecialchars($announcement['title']) ?></h3>
                            <p><?= htmlspecialchars($announcement['content']) ?></p>
                            <span class="meta"><?= htmlspecialchars($announcement['posted_by'] ?? 'Unknown') ?> | <?= htmlspecialchars($announcement['created_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <h3>No Announcements Available</h3>
                <?php endif; ?>
            </div>
        </section>

        <!-- Modal structure -->
        <div id="announcementModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle"></h2>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <img id="modalImage" class="modal-image" src="" alt="Announcement image">
                    <p id="modalContent"></p>
                    <p class="modal-user" id="modalUser"></p>
                    <p class="modal-date" id="modalDate"></p>
                </div>
            </div>
        </div>

        <section class="Achievements">
            <h2 class="AchievementName">OUR ACHIEVEMENTS</h2>
            <?php if (!empty($achievements)): ?>
                <div class="swiper achievements-carousel">
                    <div class="swiper-wrapper">
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="swiper-slide ach-card">
                                <?php if (!empty($achievement['image_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($achievement['image_path']); ?>" alt="Achievement" class="AchImage">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="ach-text">
                                    <h3><?= htmlspecialchars($achievement['title']) ?></h3>
                                    <p><?= nl2br(htmlspecialchars($achievement['description'])) ?></p>
                                    <span class="meta"><?= htmlspecialchars($achievement['achievement_date']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            <?php else: ?>
                <h3>No Achievements Yet</h3>
            <?php endif; ?>
        </section>
    </main>


    <footer>
        <p>&copy; <?= date('Y') ?> SK Cawit Portal. All rights reserved.</p>
    </footer>
    <script src="../includes/main.js"></script>

</body>

</html>