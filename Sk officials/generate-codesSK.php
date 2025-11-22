<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funtions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sk_official') {
    header("Location: ../public/signin.php");
    exit;
}

// Handle AJAX request for unused codes (for printing)
if (isset($_GET['get_unused_codes'])) {
    header('Content-Type: application/json');

    $stmt = $pdo->prepare("SELECT plain_code FROM one_time_codes WHERE is_used = 0 ORDER BY created_at DESC LIMIT 100");
    $stmt->execute();
    $unused_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($unused_codes);
    exit;
}

// Handle CSV Export
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="registration_codes_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Code Number', 'Registration Code', 'Status', 'Generated Date', 'Instructions']);

    $stmt = $pdo->prepare("SELECT plain_code, created_at FROM one_time_codes WHERE is_used = 0 ORDER BY created_at DESC");
    $stmt->execute();
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counter = 1;
    foreach ($codes as $code) {
        fputcsv($output, [
            $counter++,
            $code['plain_code'],
            'UNUSED',
            date('M d, Y', strtotime($code['created_at'])),
            'Use at your registration portal'
        ]);
    }

    fclose($output);
    exit;
}

// Handle Generate Codes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $quantity = intval($_POST['quantity']);

    if ($quantity > 0 && $quantity <= 100) {
        $generated = 0;
        for ($i = 0; $i < $quantity; $i++) {
            // Generate unique 8-character code
            $plain_code = 'KK' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $hashed_code = password_hash($plain_code, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO one_time_codes (code, plain_code, generated_by) VALUES (?, ?, ?)");
                $stmt->execute([$hashed_code, $plain_code, $_SESSION['user_id']]);
                $generated++;
            } catch (PDOException $e) {
                continue; // Skip duplicates
            }
        }

        logActivity($_SESSION['user_id'], "CREATE", 'one_time_codes', null, "Generated $generated registration codes");
        header("Location: generate-codesSK.php?success=$generated");
        exit;
    }
}

// Handle Delete Code
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM one_time_codes WHERE code_id=?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], "DELETE", 'one_time_codes', $id, "Deleted registration code");
    header("Location: generate-codesSK.php?deleted=1");
    exit;
}

// Get filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT otc.*, u.full_name as generator_name, m.full_name as user_name 
          FROM one_time_codes otc 
          LEFT JOIN users u ON otc.generated_by = u.user_id 
          LEFT JOIN kk_members m ON otc.used_by_member_id = m.member_id 
          WHERE 1=1";
$params = [];

if ($filter_status === 'unused') {
    $query .= " AND otc.is_used = 0";
} elseif ($filter_status === 'used') {
    $query .= " AND otc.is_used = 1";
}

$query .= " ORDER BY otc.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_used = 0 THEN 1 ELSE 0 END) as unused,
    SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as used
    FROM one_time_codes";
$stats_stmt = $pdo->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Codes</title>
    <link rel="stylesheet" href="../admin/admin-style.css">
    <link rel="stylesheet" href="../admin/generate-codes.css">
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
            <button class="hamburger" id="hamburger">
                <span></span><span></span><span></span>
            </button>
        </div>
        <nav>
            <ul class="navigation" id="navigation">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage-announcementsSK.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="manage-achievementsSK.php"><i class="fas fa-trophy"></i> Achievements</a></li>
                <li><a href="manage-filesSK.php"><i class="fas fa-folder"></i> Files</a></li>
                <li><a href="inventorySK.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="view-profilesSK.php"><i class="fas fa-users"></i> KK Profiles</a></li>
                <li><a href="generate-codesSK.php" class="active"><i class="fas fa-ticket-alt"></i> Generate Codes</a></li>
                <li><a href="suggestions_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="../public/logout.php" id="LogoutButton"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

    </header>

    <main class="admin-main">
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Successfully generated <?= intval($_GET['success']) ?> registration code(s)!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">
                <i class="fas fa-trash-alt"></i> Code deleted successfully!
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h2><i class="fas fa-ticket-alt"></i> Registration Codes</h2>
            <div class="header-actions">
                <button class="add-btn print" onclick="printCodes()">
                    <i class="fas fa-print"></i> Print Unused
                </button>
                <a href="?export_csv=1" class="add-btn csv">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
                <button class="add-btn" onclick="openModal()">
                    <i class="fas fa-plus-circle"></i> Generate
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Codes</h4>
                <div class="number"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-card">
                <h4>Unused Codes</h4>
                <div class="number"><?= $stats['unused'] ?></div>
            </div>
            <div class="stat-card">
                <h4>Used Codes</h4>
                <div class="number"><?= $stats['used'] ?></div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-section">
            <form method="GET">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="status">Filter by Status:</label>
                        <select name="status" id="status" class="form-input">
                            <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="unused" <?= $filter_status == 'unused' ? 'selected' : '' ?>>Unused</option>
                            <option value="used" <?= $filter_status == 'used' ? 'selected' : '' ?>>Used</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Codes Table -->
        <section class="dashboard-section">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Generated By</th>
                            <th>Used By</th>
                            <th>Generated Date</th>
                            <th>Used Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($codes)): ?>
                            <tr>
                                <td colspan="7" class="no-data">No codes found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($codes as $code): ?>
                                <tr>
                                    <td><span class="code-display"><?= htmlspecialchars($code['plain_code']) ?></span></td>
                                    <td>
                                        <span class="status-badge status-<?= $code['is_used'] ? 'used' : 'unused' ?>">
                                            <?= $code['is_used'] ? 'USED' : 'UNUSED' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($code['generator_name'] ?? 'System') ?></td>
                                    <td><?= $code['user_name'] ? htmlspecialchars($code['user_name']) : '-' ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($code['created_at'])) ?></td>
                                    <td><?= $code['used_at'] ? date('M d, Y h:i A', strtotime($code['used_at'])) : '-' ?></td>
                                    <td>
                                        <button class="table-btn copy" onclick="copyCode('<?= $code['plain_code'] ?>')">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                        <a href="?delete=<?= $code['code_id'] ?>"
                                            class="table-btn delete"
                                            onclick="return confirm('Delete this code?')">
                                            <i class="fas fa-trash"></i>
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

    <!-- Generate Modal -->
    <div id="codeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Generate Codes</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="quantity">Number of Codes:</label>
                    <input type="number" name="quantity" id="quantity" required class="form-input" min="1" max="100" value="10">
                    <small style="color: #666;">Max: 100 codes per batch</small>
                </div>

                <button type="submit" name="generate" class="btn-primary">
                    <i class="fas fa-magic"></i> Generate Codes
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('codeModal');

        function openModal() {
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }

        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                alert('Code copied: ' + code);
            });
        }

        function printCodes() {
            // Get unused codes via AJAX
            fetch('?get_unused_codes=1')
                .then(response => response.json())
                .then(codes => {
                    if (codes.length === 0) {
                        alert('No unused codes available to print!');
                        return;
                    }

                    // Create print content with logos
                    let printHTML = `
                <div id="printArea">
                    <div class="print-header">
                        <div class="logo-container">
                            <img src="../Assets/Picture1.png" alt="Logo 1" class="print-logo">
                            <div class="header-text">
                                <h1>SK CAWIT PORTAL</h1>
                                <h2>Katipunan ng Kabataan Registration Codes</h2>
                                <p>Republic of the Philippines - Region V</p>
                                <p>Province of Sorsogon - Municipality of Casiguran</p>
                                <p>Barangay Cawit</p>
                            </div>
                            <img src="../Assets/Picture2.png" alt="Logo 2" class="print-logo">
                        </div>
                        <div class="print-info">
                            <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                            <p><strong>Total Codes:</strong> ${codes.length}</p>
                        </div>
                    </div>
                    <div class="codes-grid">
            `;

                    codes.forEach((code, index) => {
                        printHTML += `
                    <div class="code-card">
                        <div class="code-number">Code #${index + 1}</div>
                        <div class="code-value">${code.plain_code}</div>
                        <div class="code-instructions">
                            <strong>INSTRUCTIONS:</strong><br>
                            1. Keep this code secure<br>
                            2. Use only once during registration<br>
                            3. Visit: <strong>SK Cawit Portal</strong>
                        </div>
                    </div>
                `;
                    });

                    printHTML += `
                    </div>
                    <div class="print-footer">
                        <p>Office of the Sangguniang Kabataan - Barangay Cawit</p>
                        <p>For inquiries, contact your SK Officials</p>
                    </div>
                </div>
            `;

                    // Open new window for printing
                    const printWindow = window.open('', '', 'width=1000,height=700');
                    printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Print Registration Codes</title>
                    <style>
                        @page {
                            margin: 1cm;
                        }
                        
                        body {
                            font-family: Arial, sans-serif;
                            padding: 20px;
                        }
                        
                        .print-header {
                            text-align: center;
                            margin-bottom: 2rem;
                            border-bottom: 3px solid #007b83;
                            padding-bottom: 1.5rem;
                        }
                        
                        .logo-container {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 2rem;
                            margin-bottom: 1rem;
                        }
                        
                        .print-logo {
                            width: 80px;
                            height: 80px;
                            object-fit: contain;
                        }
                        
                        .header-text {
                            flex: 1;
                            max-width: 500px;
                        }
                        
                        .print-header h1 {
                            color: #007b83;
                            margin: 0.5rem 0;
                            font-size: 1.8rem;
                        }
                        
                        .print-header h2 {
                            color: #333;
                            margin: 0.3rem 0;
                            font-size: 1.2rem;
                            font-weight: normal;
                        }
                        
                        .print-header p {
                            margin: 0.2rem 0;
                            font-size: 0.85rem;
                            color: #666;
                        }
                        
                        .print-info {
                            display: flex;
                            justify-content: center;
                            gap: 2rem;
                            margin-top: 1rem;
                            padding-top: 1rem;
                            border-top: 1px solid #ddd;
                        }
                        
                        .print-info p {
                            margin: 0;
                            font-size: 0.9rem;
                        }
                        
                        .codes-grid {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr);
                            gap: 1.5rem;
                            margin-top: 2rem;
                        }
                        
                        .code-card {
                            border: 2px dashed #007b83;
                            padding: 1.5rem;
                            text-align: center;
                            border-radius: 8px;
                            background: #f9f9f9;
                            page-break-inside: avoid;
                        }
                        
                        .code-number {
                            font-size: 0.9rem;
                            color: #666;
                            margin-bottom: 0.5rem;
                            font-weight: bold;
                        }
                        
                        .code-value {
                            font-family: 'Courier New', monospace;
                            font-size: 1.8rem;
                            font-weight: bold;
                            color: #007b83;
                            letter-spacing: 3px;
                            margin: 1rem 0;
                            padding: 1rem;
                            background: white;
                            border-radius: 6px;
                            border: 1px solid #007b83;
                        }
                        
                        .code-instructions {
                            font-size: 0.75rem;
                            color: #666;
                            margin-top: 1rem;
                            border-top: 1px solid #ddd;
                            padding-top: 1rem;
                            text-align: left;
                            line-height: 1.6;
                        }
                        
                        .print-footer {
                            text-align: center;
                            margin-top: 3rem;
                            padding-top: 1rem;
                            border-top: 2px solid #007b83;
                            font-size: 0.85rem;
                            color: #666;
                        }
                        
                        .print-footer p {
                            margin: 0.3rem 0;
                        }
                        
                        @media print {
                            .code-card {
                                page-break-inside: avoid;
                            }
                            
                            @page {
                                margin: 1.5cm;
                            }
                        }
                    </style>
                </head>
                <body>
                    ${printHTML}
                </body>
                </html>
            `);
                    printWindow.document.close();

                    // Wait for images to load then print
                    printWindow.onload = function() {
                        setTimeout(() => {
                            printWindow.print();
                            printWindow.close();
                        }, 500);
                    };
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load codes for printing');
                });
        }


        document.getElementById('hamburger').addEventListener('click', () => {
            document.getElementById('navigation').classList.toggle('active');
            document.getElementById('hamburger').classList.toggle('active');
        });
    </script>
</body>

</html>