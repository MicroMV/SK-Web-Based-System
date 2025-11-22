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
require_once '../config/database.php';

// Email validation function
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Initialize variables
$errors = [];
$success = '';
$form_data = [];

// Fetch recent submissions with admin responses (only reviewed/resolved, limited to 5)
try {
    $stmt = $pdo->prepare("
        SELECT 
            feedback_id,
            message,
            category,
            status,
            admin_response,
            submitted_at,
            reviewed_at
        FROM suggestions_feedback
        WHERE status IN ('reviewed', 'resolved')
        ORDER BY submitted_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_submissions = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_submissions = [];
    error_log("Error fetching submissions: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $category = $_POST['category'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $is_anonymous = isset($_POST['anonymous']);

    // Validation
    if (empty($message)) {
        $errors['message'] = 'Please enter your message';
    } elseif (strlen($message) < 10) {
        $errors['message'] = 'Message is too short (minimum 10 characters)';
    } elseif (strlen($message) > 1000) {
        $errors['message'] = 'Message is too long (maximum 1000 characters)';
    }

    if (empty($category)) {
        $errors['category'] = 'Please select a category';
    }

    if (!$is_anonymous) {
        if (empty($name)) {
            $errors['name'] = 'Please enter your name or check "Submit Anonymously"';
        }
        if (!empty($email) && !isValidEmail($email)) {
            $errors['email'] = 'Please enter a valid email address';
        }
    }

    if (empty($errors)) {
        try {
            $stored_message = $message;
            if (!$is_anonymous && !empty($name)) {
                $stored_message .= "\n\n[Submitted by: " . $name;
                if (!empty($email)) {
                    $stored_message .= " | Email: " . $email;
                }
                $stored_message .= "]";
            }

            $stmt = $pdo->prepare("
                INSERT INTO suggestions_feedback (message, category, status)
                VALUES (?, ?, 'pending')
            ");
            $stmt->execute([$stored_message, $category]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        } catch (PDOException $e) {
            $errors['general'] = 'Submission failed. Please try again later.';
            error_log("Suggestion submission error: " . $e->getMessage());
        }
    } else {
        $form_data = $_POST;
    }
}

if (isset($_GET['success'])) {
    $success = 'Thank you for your submission! Your feedback has been received and will be reviewed by our SK officials.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggestions & Feedback</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="suggestion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Header -->
    <header>
        <div class="align">
            <a href="index.php" class="logo-link">
                <img src="../Assets/Picture1.png" class="logo" alt="Logo 1" />
            </a>
            <a href="index.php" class="logo-link">
                <img src="../Assets/Picture2.png" class="logo" alt="Logo 2" />
            </a>
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

    <!-- Main Content -->
    <main class="suggestion-main">
        <div class="suggestion-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="icon-wrapper">
                    <i class="fas fa-comments"></i>
                </div>
                <h1>Suggestions & Feedback</h1>
                <p class="subtitle">Your voice matters! Share your ideas, concerns, and feedback with us.</p>
            </div>

            <!-- Two Column Layout -->
            <div class="two-column-layout">
                <!-- Left: Recent Responses (Smaller) -->
                <div class="responses-column">
                    <div class="responses-card">
                        <div class="card-header">
                            <h2><i class="fas fa-reply-all"></i> Recent SK Responses</h2>
                            <p>See how we've addressed community feedback</p>
                        </div>

                        <div class="responses-list">
                            <?php if (!empty($recent_submissions)): ?>
                                <?php foreach ($recent_submissions as $submission): ?>
                                    <div class="response-item">
                                        <div class="response-header">
                                            <span class="category-tag tag-<?php echo htmlspecialchars($submission['category']); ?>">
                                                <?php
                                                $icons = [
                                                    'suggestion' => '💡',
                                                    'feedback' => '📢',
                                                    'concern' => '⚠️',
                                                    'other' => '📝'
                                                ];
                                                echo $icons[$submission['category']] ?? '📝';
                                                ?>
                                                <?php echo ucfirst(htmlspecialchars($submission['category'])); ?>
                                            </span>
                                            <span class="status-tag status-<?php echo htmlspecialchars($submission['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($submission['status'])); ?>
                                            </span>
                                        </div>

                                        <div class="response-message">
                                            <?php
                                            $message = htmlspecialchars($submission['message']);
                                            $message = preg_replace('/\[Submitted by:.*?\]/s', '', $message);
                                            $message = trim($message);
                                            if (strlen($message) > 150) {
                                                echo nl2br(substr($message, 0, 150)) . '...';
                                            } else {
                                                echo nl2br($message);
                                            }
                                            ?>
                                        </div>

                                        <?php if (!empty($submission['admin_response'])): ?>
                                            <div class="sk-response">
                                                <div class="sk-response-header">
                                                    <i class="fas fa-user-shield"></i>
                                                    <strong>SK Official Response</strong>
                                                </div>
                                                <p><?php echo nl2br(htmlspecialchars($submission['admin_response'])); ?></p>
                                                <?php if ($submission['reviewed_at']): ?>
                                                    <small class="response-date">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo date('M d, Y', strtotime($submission['reviewed_at'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-responses">
                                    <i class="fas fa-inbox"></i>
                                    <p>No responses yet</p>
                                    <small>Be the first to submit feedback!</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Submission Form (Larger/Highlighted) -->
                <div class="form-column">
                    <div class="form-card">
                        <div class="card-header">
                            <h2><i class="fas fa-pen"></i> Submit Your Feedback</h2>
                            <p>Help us improve by sharing your thoughts</p>
                        </div>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo htmlspecialchars($success); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo htmlspecialchars($errors['general']); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="suggestionForm" class="suggestion-form">
                            <!-- Category -->
                            <div class="form-group">
                                <label for="category">
                                    Category <span class="required">*</span>
                                </label>
                                <select name="category" id="category" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="suggestion" <?php echo (($form_data['category'] ?? '') === 'suggestion') ? 'selected' : ''; ?>>
                                        💡 Suggestion
                                    </option>
                                    <option value="feedback" <?php echo (($form_data['category'] ?? '') === 'feedback') ? 'selected' : ''; ?>>
                                        📢 Feedback
                                    </option>
                                    <option value="concern" <?php echo (($form_data['category'] ?? '') === 'concern') ? 'selected' : ''; ?>>
                                        ⚠️ Concern
                                    </option>
                                    <option value="other" <?php echo (($form_data['category'] ?? '') === 'other') ? 'selected' : ''; ?>>
                                        📝 Other
                                    </option>
                                </select>
                                <?php if (!empty($errors['category'])): ?>
                                    <span class="error-message"><?php echo htmlspecialchars($errors['category']); ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Message -->
                            <div class="form-group">
                                <label for="message">
                                    Your Message <span class="required">*</span>
                                </label>
                                <textarea
                                    name="message"
                                    id="message"
                                    placeholder="Share your thoughts, ideas, or concerns..."
                                    required
                                    maxlength="1000"
                                    rows="6"><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
                                <div class="char-counter">
                                    <span id="charCount">0</span> / 1000 characters
                                </div>
                                <?php if (!empty($errors['message'])): ?>
                                    <span class="error-message"><?php echo htmlspecialchars($errors['message']); ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Anonymous Toggle -->
                            <div class="anonymous-section">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="anonymous" id="anonymous">
                                    <span class="checkmark"></span>
                                    <div class="checkbox-label">
                                        <strong>Submit Anonymously</strong>
                                        <small>Your identity will remain confidential</small>
                                    </div>
                                </label>
                            </div>

                            <!-- Identity Fields -->
                            <div class="identity-fields" id="identityFields">
                                <div class="form-group">
                                    <label for="name">
                                        Your Name <span class="required">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="name"
                                        id="name"
                                        placeholder="Jessica Detablan"
                                        value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>">
                                    <?php if (!empty($errors['name'])): ?>
                                        <span class="error-message"><?php echo htmlspecialchars($errors['name']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email (Optional)</label>
                                    <input
                                        type="email"
                                        name="email"
                                        id="email"
                                        placeholder="Detablan@example.com"
                                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                                    <small class="help-text">We'll use this to follow up if needed</small>
                                    <?php if (!empty($errors['email'])): ?>
                                        <span class="error-message"><?php echo htmlspecialchars($errors['email']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-paper-plane"></i>
                                Submit Feedback
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> SK Cawit Portal. All rights reserved.</p>
    </footer>

    <script src="../includes/suggestion.js"></script>
</body>

</html>