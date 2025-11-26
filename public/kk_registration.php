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
require_once('../config/database.php');
require_once('../includes/funtions.php');

$errors = [];
$success = '';
$form_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $full_name = sanitize($_POST['full_name'] ?? '');
    $age = filter_var($_POST['age'] ?? '', FILTER_VALIDATE_INT);
    $birthdate = sanitize($_POST['birthdate'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $purok_number = trim($_POST['purok_number'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $youth_class = sanitize($_POST['youth_class'] ?? '');
    $civil_status = sanitize($_POST['civil_status'] ?? '');
    $highest_educ_attained = sanitize($_POST['highest_educ_attained'] ?? '');
    $work_status = sanitize($_POST['work_status'] ?? '');
    $registered_voter = sanitize($_POST['registered_voter'] ?? '');
    $voted_last_election = sanitize($_POST['voted_last_election'] ?? '');
    $attend_kk_assembly = sanitize($_POST['attend_kk_assembly'] ?? '');
    $num_times_attended = filter_var($_POST['num_times_attended'] ?? 0, FILTER_VALIDATE_INT);
    $submission_code = sanitize($_POST['submission_code'] ?? '');

    // Validation
    if (empty($full_name) || !preg_match("/^[a-zA-Z\s.'-]+$/", $full_name)) {
        $errors['full_name'] = 'Please enter a valid full name';
    }

    if (!$age || $age < 15 || $age > 30) {
        $errors['age'] = 'Age must be between 15 and 30 years old';
    }

    if (empty($birthdate)) {
        $errors['birthdate'] = 'Birthdate is required';
    } else {
        $birth_date_obj = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$birth_date_obj) {
            $errors['birthdate'] = 'Invalid birthdate format';
        }
    }

    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }
    if (empty($purok_number)) {
        $errors['purok_number'] = 'Purok number is required';
    } elseif (!in_array($purok_number, ['1', '2', '3', '4'])) {
        $errors['purok_number'] = 'Please select a valid Purok number';
    }

    if (empty($contact_number) || !preg_match("/^[0-9+\-() ]+$/", $contact_number)) {
        $errors['contact_number'] = 'Please enter a valid contact number';
    }

    if (empty($contact_number) || !preg_match("/^[0-9+\-() ]+$/", $contact_number)) {
        $errors['contact_number'] = 'Please enter a valid contact number';
    }

    if (!empty($email) && !isValidEmail($email)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($gender)) {
        $errors['gender'] = 'Please select your gender';
    }

    if (empty($youth_class)) {
        $errors['youth_class'] = 'Please select your youth classification';
    }

    if (empty($civil_status)) {
        $errors['civil_status'] = 'Please select your civil status';
    }

    if (empty($highest_educ_attained)) {
        $errors['highest_educ_attained'] = 'Please select your educational attainment';
    }

    if (empty($work_status)) {
        $errors['work_status'] = 'Please select your work status';
    }

    if (empty($registered_voter)) {
        $errors['registered_voter'] = 'Please indicate if you are a registered voter';
    }

    if (empty($voted_last_election)) {
        $errors['voted_last_election'] = 'Please indicate if you voted in the last election';
    }

    if (empty($attend_kk_assembly)) {
        $errors['attend_kk_assembly'] = 'Please indicate if you attend KK assemblies';
    }

    if (empty($submission_code)) {
        $errors['submission_code'] = 'One-time code is required';
    } else {
        // Verify one-time code using PDO
        global $pdo;
        $stmt = $pdo->prepare("SELECT code_id, is_used FROM one_time_codes WHERE plain_code = ? AND is_used = FALSE");
        $stmt->execute([$submission_code]);
        $code_result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$code_result) {
            $errors['submission_code'] = 'Invalid or already used code';
        }
    }
    // Check for duplicate registration after all field validations
    if (empty($errors)) {
        global $pdo;

        // Check for exact duplicate: same name + same birthdate + same gender
        $stmt = $pdo->prepare("
        SELECT member_id, full_name 
        FROM kk_members 
        WHERE full_name = ? AND birthdate = ? AND gender = ?
    ");
        $stmt->execute([$full_name, $birthdate, $gender]);

        if ($stmt->fetch()) {
            $errors['general'] = 'This member is already registered (same name, birthdate, and gender found)';
        }
    }


    // If no errors, insert into database
    if (empty($errors)) {
        global $pdo;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
    INSERT INTO kk_members (
        full_name, age, birthdate, address, purok_number, contact_number, email,
        gender, youth_class, civil_status, highest_educ_attained,
        work_status, registered_voter, voted_last_election,
        attend_kk_assembly, num_times_attended, submission_code
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

            $stmt->execute([
                $full_name,
                $age,
                $birthdate,
                $address,
                $purok_number,
                $contact_number,
                $email,
                $gender,
                $youth_class,
                $civil_status,
                $highest_educ_attained,
                $work_status,
                $registered_voter,
                $voted_last_election,
                $attend_kk_assembly,
                $num_times_attended,
                $submission_code
            ]);


            $member_id = $pdo->lastInsertId();

            // Mark code as used
            $stmt = $pdo->prepare("
                UPDATE one_time_codes 
                SET is_used = TRUE, used_at = NOW(), used_by_member_id = ? 
                WHERE plain_code = ?
            ");
            $stmt->execute([$member_id, $submission_code]);

            $pdo->commit();

            $success = 'Registration successful! Thank you for registering.';

            // Clear form data
            $form_data = [];
        } catch (PDOException $e) {
            $pdo->rollback();
            $errors['general'] = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    } else {
        // Store form data to repopulate
        $form_data = $_POST;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Assets/Picture2.png">
    <title>KK Member Registration</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="kk_registration.css">
</head>

<body>
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


    <div class="registration-container">
        <h2>KK Member Registration Form</h2>

        <div class="info-text">
            Please fill out all required fields marked with <span class="required">*</span>. You will need a valid one-time registration code to complete your registration.
        </div>

        <?php if ($success): ?>
            <div class="success-message"><?= $success ?></div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="general-error"><?= $errors['general'] ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Personal Information -->
            <div class="form-section">
                <h3>Personal Information</h3>

                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($form_data['full_name'] ?? '') ?>" required>
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="error-message"><?= $errors['full_name'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="age">Age <span class="required">*</span></label>
                    <input type="number" id="age" name="age" min="15" max="30" value="<?= htmlspecialchars($form_data['age'] ?? '') ?>" required>
                    <?php if (isset($errors['age'])): ?>
                        <div class="error-message"><?= $errors['age'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="birthdate">Birthdate <span class="required">*</span></label>
                    <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($form_data['birthdate'] ?? '') ?>" required>
                    <?php if (isset($errors['birthdate'])): ?>
                        <div class="error-message"><?= $errors['birthdate'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="gender">Gender <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="gender" value="Male" <?= ($form_data['gender'] ?? '') === 'Male' ? 'checked' : '' ?> required>
                            Male
                        </label>
                        <label>
                            <input type="radio" name="gender" value="Female" <?= ($form_data['gender'] ?? '') === 'Female' ? 'checked' : '' ?>>
                            Female
                        </label>
                        <label>
                            <input type="radio" name="gender" value="Other" <?= ($form_data['gender'] ?? '') === 'Other' ? 'checked' : '' ?>>
                            Other
                        </label>
                    </div>
                    <?php if (isset($errors['gender'])): ?>
                        <div class="error-message"><?= $errors['gender'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="form-section">
                <h3>Contact Information</h3>

                <div class="form-group">
                    <label for="address">Address <span class="required">*</span></label>
                    <textarea id="address" name="address" required><?= htmlspecialchars($form_data['address'] ?? '') ?></textarea>
                    <?php if (isset($errors['address'])): ?>
                        <div class="error-message"><?= $errors['address'] ?></div>
                    <?php endif; ?>
                </div>
               
                <div class="form-group">
                    <label for="purok_number">Purok Number <span class="required">*</span></label>
                    <select id="purok_number" name="purok_number" required>
                        <option value="">-- Select Purok --</option>
                        <option value="1" <?= isset($form_data['purok_number']) && $form_data['purok_number'] == '1' ? 'selected' : '' ?>>Purok 1</option>
                        <option value="2" <?= isset($form_data['purok_number']) && $form_data['purok_number'] == '2' ? 'selected' : '' ?>>Purok 2</option>
                        <option value="3" <?= isset($form_data['purok_number']) && $form_data['purok_number'] == '3' ? 'selected' : '' ?>>Purok 3</option>
                        <option value="4" <?= isset($form_data['purok_number']) && $form_data['purok_number'] == '4' ? 'selected' : '' ?>>Purok 4</option>
                    </select>
                    <?php if (isset($errors['purok_number'])): ?>
                        <span class="error-message"><?= $errors['purok_number'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number <span class="required">*</span></label>
                    <input type="text" id="contact_number" name="contact_number" value="<?= htmlspecialchars($form_data['contact_number'] ?? '') ?>" required>
                    <?php if (isset($errors['contact_number'])): ?>
                        <div class="error-message"><?= $errors['contact_number'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-message"><?= $errors['email'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Classification and Status -->
            <div class="form-section">
                <h3>Classification and Status</h3>

                <div class="form-group">
                    <label for="youth_class">Youth Classification <span class="required">*</span></label>
                    <select id="youth_class" name="youth_class" required>
                        <option value="">-- Select Classification --</option>
                        <option value="ISY" <?= ($form_data['youth_class'] ?? '') === 'ISY' ? 'selected' : '' ?>>In-School Youth (ISY)</option>
                        <option value="OSY" <?= ($form_data['youth_class'] ?? '') === 'OSY' ? 'selected' : '' ?>>Out-of-School Youth (OSY)</option>
                        <option value="WY" <?= ($form_data['youth_class'] ?? '') === 'WY' ? 'selected' : '' ?>>Working Youth (WY)</option>
                        <option value="YSN" <?= ($form_data['youth_class'] ?? '') === 'YSN' ? 'selected' : '' ?>>Youth with Special Needs (YSN)</option>
                    </select>
                    <?php if (isset($errors['youth_class'])): ?>
                        <div class="error-message"><?= $errors['youth_class'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="civil_status">Civil Status <span class="required">*</span></label>
                    <select id="civil_status" name="civil_status" required>
                        <option value="">-- Select Civil Status --</option>
                        <option value="Single" <?= ($form_data['civil_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                        <option value="Married" <?= ($form_data['civil_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                        <option value="Widowed" <?= ($form_data['civil_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                        <option value="Divorced" <?= ($form_data['civil_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                        <option value="Separated" <?= ($form_data['civil_status'] ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
                        <option value="Annulled" <?= ($form_data['civil_status'] ?? '') === 'Annulled' ? 'selected' : '' ?>>Annulled</option>
                        <option value="Live-in" <?= ($form_data['civil_status'] ?? '') === 'Live-in' ? 'selected' : '' ?>>Live-in</option>
                        <option value="Unknown" <?= ($form_data['civil_status'] ?? '') === 'Unknown' ? 'selected' : '' ?>>Unknown</option>
                    </select>
                    <?php if (isset($errors['civil_status'])): ?>
                        <div class="error-message"><?= $errors['civil_status'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Education and Employment -->
            <div class="form-section">
                <h3>Education and Employment</h3>

                <div class="form-group">
                    <label for="highest_educ_attained">Highest Educational Attainment <span class="required">*</span></label>
                    <select id="highest_educ_attained" name="highest_educ_attained" required>
                        <option value="">-- Select Educational Attainment --</option>
                        <option value="Elementary Level" <?= ($form_data['highest_educ_attained'] ?? '') === 'Elementary Level' ? 'selected' : '' ?>>Elementary Level</option>
                        <option value="Elementary Graduate" <?= ($form_data['highest_educ_attained'] ?? '') === 'Elementary Graduate' ? 'selected' : '' ?>>Elementary Graduate</option>
                        <option value="High School Level" <?= ($form_data['highest_educ_attained'] ?? '') === 'High School Level' ? 'selected' : '' ?>>High School Level</option>
                        <option value="High School Graduate" <?= ($form_data['highest_educ_attained'] ?? '') === 'High School Graduate' ? 'selected' : '' ?>>High School Graduate</option>
                        <option value="Vocational Graduate" <?= ($form_data['highest_educ_attained'] ?? '') === 'Vocational Graduate' ? 'selected' : '' ?>>Vocational Graduate</option>
                        <option value="College Level" <?= ($form_data['highest_educ_attained'] ?? '') === 'College Level' ? 'selected' : '' ?>>College Level</option>
                        <option value="College Graduate" <?= ($form_data['highest_educ_attained'] ?? '') === 'College Graduate' ? 'selected' : '' ?>>College Graduate</option>
                    </select>
                    <?php if (isset($errors['highest_educ_attained'])): ?>
                        <div class="error-message"><?= $errors['highest_educ_attained'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="work_status">Work Status <span class="required">*</span></label>
                    <select id="work_status" name="work_status" required>
                        <option value="">-- Select Work Status --</option>
                        <option value="Employed" <?= ($form_data['work_status'] ?? '') === 'Employed' ? 'selected' : '' ?>>Employed</option>
                        <option value="Unemployed" <?= ($form_data['work_status'] ?? '') === 'Unemployed' ? 'selected' : '' ?>>Unemployed</option>
                        <option value="Self-employed" <?= ($form_data['work_status'] ?? '') === 'Self-employed' ? 'selected' : '' ?>>Self-employed</option>
                        <option value="Student" <?= ($form_data['work_status'] ?? '') === 'Student' ? 'selected' : '' ?>>Student</option>
                    </select>
                    <?php if (isset($errors['work_status'])): ?>
                        <div class="error-message"><?= $errors['work_status'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Civic Participation -->
            <div class="form-section">
                <h3>Civic Participation</h3>

                <div class="form-group">
                    <label>Are you a Registered Voter? <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="registered_voter" value="Yes" <?= ($form_data['registered_voter'] ?? '') === 'Yes' ? 'checked' : '' ?> required>
                            Yes
                        </label>
                        <label>
                            <input type="radio" name="registered_voter" value="No" <?= ($form_data['registered_voter'] ?? '') === 'No' ? 'checked' : '' ?>>
                            No
                        </label>
                    </div>
                    <?php if (isset($errors['registered_voter'])): ?>
                        <div class="error-message"><?= $errors['registered_voter'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Did you vote in the last election? <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="voted_last_election" value="Yes" <?= ($form_data['voted_last_election'] ?? '') === 'Yes' ? 'checked' : '' ?> required>
                            Yes
                        </label>
                        <label>
                            <input type="radio" name="voted_last_election" value="No" <?= ($form_data['voted_last_election'] ?? '') === 'No' ? 'checked' : '' ?>>
                            No
                        </label>
                    </div>
                    <?php if (isset($errors['voted_last_election'])): ?>
                        <div class="error-message"><?= $errors['voted_last_election'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Do you attend KK assemblies? <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="attend_kk_assembly" value="Yes" <?= ($form_data['attend_kk_assembly'] ?? '') === 'Yes' ? 'checked' : '' ?> required>
                            Yes
                        </label>
                        <label>
                            <input type="radio" name="attend_kk_assembly" value="No" <?= ($form_data['attend_kk_assembly'] ?? '') === 'No' ? 'checked' : '' ?>>
                            No
                        </label>
                    </div>
                    <?php if (isset($errors['attend_kk_assembly'])): ?>
                        <div class="error-message"><?= $errors['attend_kk_assembly'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="num_times_attended">Number of times attended KK assemblies</label>
                    <input type="number" id="num_times_attended" name="num_times_attended" min="0" value="<?= htmlspecialchars($form_data['num_times_attended'] ?? 0) ?>">
                </div>
            </div>

            <!-- Registration Code -->
            <div class="form-section">
                <h3>Registration Code</h3>

                <div class="form-group">
                    <label for="submission_code">One-Time Registration Code <span class="required">*</span></label>
                    <input type="text" id="submission_code" name="submission_code" value="<?= htmlspecialchars($form_data['submission_code'] ?? '') ?>" required>
                    <small style="color: #666; display: block; margin-top: 0.3rem;">Enter the registration code provided by your SK officials</small>
                    <?php if (isset($errors['submission_code'])): ?>
                        <div class="error-message"><?= $errors['submission_code'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="submit-btn">Submit Registration</button>
        </form>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> SK Cawit Portal. All rights reserved.</p>
    </footer>

    <script src="../includes/kk_registration.js"></script>
</body>

</html>