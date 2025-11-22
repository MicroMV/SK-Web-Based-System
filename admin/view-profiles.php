<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funtions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../public/signin.php");
    exit;
}

// Handle CSV Export
if (isset($_GET['export_csv'])) {
    $purok_filter = isset($_GET['purok']) && $_GET['purok'] != '' ? intval($_GET['purok']) : null;

    // Build query with optional purok filter
    $query = "SELECT * FROM kk_members WHERE 1=1";
    if ($purok_filter) {
        $query .= " AND purok_number = $purok_filter";
    }
    $query .= " ORDER BY full_name ASC";

    $stmt = $pdo->query($query);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="KK_YOUTH_PROFILE_PUROK_' . ($purok_filter ?: 'ALL') . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Header Section (Rows 1-13)
    // Row 1: Empty
    fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 2: Logos and Header (centered text)
    fputcsv($output, ['', '', '', '', '', '', '', '', '', 'Republic of the Philippines', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 3: Region
    fputcsv($output, ['', '', '', '', '', '', '', '', '', 'Region V', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 4: Province
    fputcsv($output, ['', '', '', '', '', '', '', '', '', 'Province of Sorsogon', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 5: Municipality
    fputcsv($output, ['', '', '', '', '', '', '', '', '', 'Municipality of Casiguran', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 6: Office header
    fputcsv($output, ['', '', '', '', '', '', '', '', '', 'OFFICE OF THE SANGGUNIANG KABATAAN', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 7: Barangay
    fputcsv($output, ['', '', '', '', '', '', '', '', '', 'Barangay Cawit', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 8: Empty
    fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 9: Empty
    fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 10: Main Title
    fputcsv($output, ['', '', '', '', '', '', '', '', '', 'KATIPUNAN NG KABATAAN YOUTH PROFILE', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 11: Empty
    fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 12: Empty
    fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 13: Location Info Row
    fputcsv($output, [
        '',
        '',
        'Region:',
        'V',
        '',
        '',
        'Province:',
        'SORSOGON',
        '',
        '',
        'Municipality:',
        'CASIGURAN',
        '',
        '',
        'Barangay:',
        'CAWIT',
        '',
        '',
        'Purok:',
        $purok_filter ?: 'ALL'
    ]);

    // Row 14: Empty
    fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Row 15: Column Headers (with merged cells representation)
    fputcsv($output, [
        'NO.',
        'NAME',
        'AGE',
        'BIRTHDAY',
        'Month/Date/Year',
        '',
        'GENDER',
        'CIVIL STATUS',
        'YOUTH CLASSIFICATION: ISY/OSY - NEET/WY/YSN (PWD/IP)',
        'YOUTH AGE GROUP',
        'EMAIL ADD',
        '',
        'CONTACT NUMBER',
        'HIGHEST EDUCATIONAL ATTAINMENT',
        '',
        'WORK STATUS',
        'REGISTERED VOTER',
        'VOTED LAST ELECTION',
        'ATENNDED KK ASSEMBLY',
        '',
        'IF YES, HOW MANY TIMES?',
        ''
    ]);

    // Row 16: Sub-headers for birthday
    fputcsv($output, [
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        'Yes/No',
        'Yes/No',
        'Yes/No',
        '',
        '',
        ''
    ]);

    // Data rows starting from row 17
    $counter = 1;
    foreach ($members as $member) {
        // Split birthdate
        $birthdate = new DateTime($member['birthdate']);
        $month = $birthdate->format('n'); // Numeric month (1-12)
        $day = $birthdate->format('j'); // Day without leading zero
        $year = $birthdate->format('Y');

        // Determine youth age group
        $age = $member['age'];
        if ($age >= 15 && $age <= 17) {
            $youth_age_group = 'Core Youth';
        } elseif ($age >= 18 && $age <= 24) {
            $youth_age_group = 'Child Youth';
        } elseif ($age >= 25 && $age <= 30) {
            $youth_age_group = 'Core Youth';
        } else {
            $youth_age_group = '';
        }

        // Format data row
        fputcsv($output, [
            $counter++,                             // NO.
            $member['full_name'],                   // NAME
            $member['age'],                         // AGE
            $month,                                 // BIRTHDAY Month
            $day,                                   // BIRTHDAY Date
            $year,                                  // BIRTHDAY Year
            $member['gender'],                      // GENDER
            $member['civil_status'],                // CIVIL STATUS
            $member['youth_class'],                 // YOUTH CLASSIFICATION
            $youth_age_group,                       // YOUTH AGE GROUP
            $member['email'] ?: 'none',            // EMAIL
            '',                                     // Empty column
            $member['contact_number'] ?: 'none',   // CONTACT NUMBER
            $member['highest_educ_attained'],      // HIGHEST EDUCATIONAL ATTAINMENT
            '',                                     // Empty column
            $member['work_status'],                // WORK STATUS
            $member['registered_voter'],           // REGISTERED VOTER
            $member['voted_last_election'],        // VOTED LAST ELECTION
            $member['attend_kk_assembly'],         // ATTENDED KK ASSEMBLY
            '',                                     // Empty column
            $member['num_times_attended'] ?: '',   // IF YES, HOW MANY TIMES
            ''                                      // Empty column
        ]);
    }

    fclose($output);
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM kk_members WHERE member_id=?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], "DELETE", 'kk_members', $id, "Deleted KK member ID $id");
    header("Location: view-profiles.php?deleted=1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'] ?? null;
    $full_name = trim($_POST['full_name']);
    $age = intval($_POST['age']);
    $birthdate = $_POST['birthdate'];
    $address = trim($_POST['address']);
    $purok_number = intval($_POST['purok_number']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $youth_class = $_POST['youth_class'];
    $civil_status = $_POST['civil_status'];
    $highest_educ_attained = $_POST['highest_educ_attained'];
    $work_status = $_POST['work_status'];
    $registered_voter = $_POST['registered_voter'];
    $voted_last_election = $_POST['voted_last_election'];
    $attend_kk_assembly = $_POST['attend_kk_assembly'];
    $num_times_attended = intval($_POST['num_times_attended']);

    // Check for duplicate: same name + same birthdate + same gender
    if ($member_id) {
        // When editing, exclude current member from duplicate check
        $stmt = $pdo->prepare("
            SELECT member_id, full_name 
            FROM kk_members 
            WHERE full_name = ? AND birthdate = ? AND gender = ? AND member_id != ?
        ");
        $stmt->execute([$full_name, $birthdate, $gender, $member_id]);
    } else {
        // When adding new, check all members
        $stmt = $pdo->prepare("
            SELECT member_id, full_name 
            FROM kk_members 
            WHERE full_name = ? AND birthdate = ? AND gender = ?
        ");
        $stmt->execute([$full_name, $birthdate, $gender]);
    }

    if ($stmt->fetch()) {
        // Duplicate found - redirect with error
        header("Location: view-profiles.php?error=duplicate");
        exit;
    }

    if ($member_id) {
        // Update existing member
        $stmt = $pdo->prepare("UPDATE kk_members SET 
            full_name=?, age=?, birthdate=?, address=?, purok_number=?, 
            contact_number=?, email=?, gender=?, youth_class=?, civil_status=?, 
            highest_educ_attained=?, work_status=?, registered_voter=?, 
            voted_last_election=?, attend_kk_assembly=?, num_times_attended=? 
            WHERE member_id=?");
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
            $member_id
        ]);
        logActivity($_SESSION['user_id'], 'UPDATE', 'kk_members', $member_id, "Updated KK member - $full_name");
    } else {
        // Insert new member
        $submission_code = 'KK' . time();
        $stmt = $pdo->prepare("INSERT INTO kk_members 
            (full_name, age, birthdate, address, purok_number, contact_number, email, 
            gender, youth_class, civil_status, highest_educ_attained, work_status, 
            registered_voter, voted_last_election, attend_kk_assembly, 
            num_times_attended, submission_code) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
        logActivity($_SESSION['user_id'], 'CREATE', 'kk_members', $pdo->lastInsertId(), "Added KK member - $full_name");
    }

    header("Location: view-profiles.php?success=1");
    exit;
}


// Get filter values
$purok_filter = isset($_GET['purok']) && $_GET['purok'] != '' ? intval($_GET['purok']) : null;

// Build query with filters
$query = "SELECT * FROM kk_members WHERE 1=1";
$params = [];

if ($purok_filter) {
    $query .= " AND purok_number = ?";
    $params[] = $purok_filter;
}

$query .= " ORDER BY full_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts by purok
$purok_counts = [];
for ($i = 1; $i <= 4; $i++) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kk_members WHERE purok_number = ?");
    $stmt->execute([$i]);
    $purok_counts[$i] = $stmt->fetchColumn();
}
$total_members = array_sum($purok_counts);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Profiles</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="view-profiles.css">
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
                <li><a href="view-profiles.php" class="active"><i class="fas fa-users"></i> KK Profiles</a></li>
                <li><a href="generate-codes.php"><i class="fas fa-ticket-alt"></i> Generate Codes</a></li>
                <li><a href="suggestions_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="create_sk_account.php"><i class="fas fa-user-plus"></i> Create Account</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="../public/logout.php" id="LogoutButton"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

    </header>

    <main class="admin-main">
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> KK Member saved successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">
                <i class="fas fa-trash-alt"></i> KK Member deleted successfully!
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate'): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                This member is already registered (same name, birthdate, and gender found)!
            </div>
        <?php endif; ?>


        <div class="page-header">
            <h2><i class="fas fa-users"></i> KK Member Profiles</h2>
            <div class="header-actions">
                <button class="add-btn" onclick="openModal()">
                    <i class="fas fa-user-plus"></i> Add Member
                </button>
                <a href="?export_csv=1<?= $purok_filter ? '&purok=' . $purok_filter : '' ?>" class="export-btn">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Members</h4>
                <div class="number"><?= $total_members ?></div>
            </div>
            <div class="stat-card">
                <h4>Purok 1</h4>
                <div class="number"><?= $purok_counts[1] ?></div>
            </div>
            <div class="stat-card">
                <h4>Purok 2</h4>
                <div class="number"><?= $purok_counts[2] ?></div>
            </div>
            <div class="stat-card">
                <h4>Purok 3</h4>
                <div class="number"><?= $purok_counts[3] ?></div>
            </div>
            <div class="stat-card">
                <h4>Purok 4</h4>
                <div class="number"><?= $purok_counts[4] ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="purok">Filter by Purok:</label>
                        <select name="purok" id="purok" class="form-input">
                            <option value="">All Puroks</option>
                            <option value="1" <?= $purok_filter == 1 ? 'selected' : '' ?>>Purok 1</option>
                            <option value="2" <?= $purok_filter == 2 ? 'selected' : '' ?>>Purok 2</option>
                            <option value="3" <?= $purok_filter == 3 ? 'selected' : '' ?>>Purok 3</option>
                            <option value="4" <?= $purok_filter == 4 ? 'selected' : '' ?>>Purok 4</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Members Table -->
        <section class="dashboard-section">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Purok</th>
                            <th>Gender</th>
                            <th>Contact</th>
                            <th>Youth Class</th>
                            <th>Voter</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="8" class="no-data">No members found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($members as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['full_name']) ?></td>
                                    <td><?= $m['age'] ?></td>
                                    <td><span class="purok-badge">Purok <?= $m['purok_number'] ?></span></td>
                                    <td><?= htmlspecialchars($m['gender']) ?></td>
                                    <td><?= htmlspecialchars($m['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($m['youth_class']) ?></td>
                                    <td><?= $m['registered_voter'] == 'Yes' ? '✓' : '✗' ?></td>
                                    <td>
                                        <button class="table-btn edit" onclick='editMember(<?= json_encode($m) ?>)'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?delete=<?= $m['member_id'] ?>"
                                            class="table-btn delete"
                                            onclick="return confirm('Are you sure you want to delete this member?')">
                                            <i class="fas fa-trash"></i> Delete
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
    <div id="memberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add KK Member</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="member_id" id="member_id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" name="full_name" id="full_name" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="age">Age:</label>
                        <input type="number" name="age" id="age" required class="form-input" min="15" max="30">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="birthdate">Birthdate:</label>
                        <input type="date" name="birthdate" id="birthdate" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="purok_number">Purok:</label>
                        <select name="purok_number" id="purok_number" required class="form-input">
                            <option value="">Select Purok</option>
                            <option value="1">Purok 1</option>
                            <option value="2">Purok 2</option>
                            <option value="3">Purok 3</option>
                            <option value="4">Purok 4</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea name="address" id="address" required class="form-input" rows="2"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_number">Contact Number:</label>
                        <input type="text" name="contact_number" id="contact_number" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select name="gender" id="gender" required class="form-input">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="civil_status">Civil Status:</label>
                        <select name="civil_status" id="civil_status" required class="form-input">
                            <option value="">Select Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="youth_class">Youth Classification:</label>
                        <select name="youth_class" id="youth_class" required class="form-input">
                            <option value="">Select Classification</option>
                            <option value="ISY">In-School Youth (ISY)</option>
                            <option value="OSY">Out-of-School Youth (OSY)</option>
                            <option value="Working Youth">Working Youth</option>
                            <option value="PWD">Person with Disability (PWD)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="highest_educ_attained">Highest Education:</label>
                        <select name="highest_educ_attained" id="highest_educ_attained" required class="form-input">
                            <option value="">Select Education</option>
                            <option value="Elementary Level">Elementary Level</option>
                            <option value="Elementary Graduate">Elementary Graduate</option>
                            <option value="High School Level">High School Level</option>
                            <option value="High School Graduate">High School Graduate</option>
                            <option value="Vocational Graduate">Vocational Graduate</option>
                            <option value="College Level">College Level</option>
                            <option value="College Graduate">College Graduate</option>
                        </select>
                    </div>

                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="work_status">Work Status:</label>
                        <select name="work_status" id="work_status" required class="form-input">
                            <option value="">Select Status</option>
                            <option value="Employed">Employed</option>
                            <option value="Unemployed">Unemployed</option>
                            <option value="Self-Employed">Self-Employed</option>
                            <option value="Student">Student</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="registered_voter">Registered Voter:</label>
                        <select name="registered_voter" id="registered_voter" required class="form-input">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="voted_last_election">Voted Last Election:</label>
                        <select name="voted_last_election" id="voted_last_election" required class="form-input">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                            <option value="Not Applicable">Not Applicable</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="attend_kk_assembly">Attended KK Assembly:</label>
                        <select name="attend_kk_assembly" id="attend_kk_assembly" required class="form-input">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="num_times_attended">Times Attended:</label>
                    <input type="number" name="num_times_attended" id="num_times_attended" class="form-input" min="0" value="0">
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Member
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('memberModal');

        function openModal() {
            document.getElementById('modalTitle').innerText = "Add KK Member";
            document.getElementById('member_id').value = '';
            document.querySelector('form').reset();
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

        function editMember(data) {
            document.getElementById('modalTitle').innerText = "Edit KK Member";
            document.getElementById('member_id').value = data.member_id;
            document.getElementById('full_name').value = data.full_name;
            document.getElementById('age').value = data.age;
            document.getElementById('birthdate').value = data.birthdate;
            document.getElementById('address').value = data.address;
            document.getElementById('purok_number').value = data.purok_number;
            document.getElementById('contact_number').value = data.contact_number;
            document.getElementById('email').value = data.email || '';
            document.getElementById('gender').value = data.gender;
            document.getElementById('youth_class').value = data.youth_class;
            document.getElementById('civil_status').value = data.civil_status;
            document.getElementById('highest_educ_attained').value = data.highest_educ_attained;
            document.getElementById('work_status').value = data.work_status;
            document.getElementById('registered_voter').value = data.registered_voter;
            document.getElementById('voted_last_election').value = data.voted_last_election;
            document.getElementById('attend_kk_assembly').value = data.attend_kk_assembly;
            document.getElementById('num_times_attended').value = data.num_times_attended || 0;
            modal.style.display = 'block';
        }

        document.getElementById('hamburger').addEventListener('click', () => {
            document.getElementById('navigation').classList.toggle('active');
            document.getElementById('hamburger').classList.toggle('active');
        });
    </script>
</body>

</html>