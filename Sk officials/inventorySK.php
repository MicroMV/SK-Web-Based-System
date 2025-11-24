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
    $item_name = trim($_POST['item_name']);
    $quantity_purchased = intval($_POST['quantity_purchased']);
    $current_stock = intval($_POST['current_stock']);
    $unit = trim($_POST['unit']);
    $vendor = trim($_POST['vendor']);
    $purchase_date = $_POST['purchase_date'];
    $unit_price = floatval($_POST['unit_price']);
    $total_price = floatval($_POST['total_price']);
    $user_id = $_SESSION['user_id'];
    $inventory_id = $_POST['inventory_id'] ?? null;

    // Validation
    if (strlen($item_name) > 200) {
        header("Location: inventorySK.php?error=Item name too long");
        exit;
    }

    if ($inventory_id) {
        $stmt = $pdo->prepare("UPDATE inventory SET item_name=?, quantity_purchased=?, current_stock=?, unit=?, vendor=?, purchase_date=?, unit_price=?, total_price=?, last_updated=NOW() WHERE inventory_id=?");
        $stmt->execute([$item_name, $quantity_purchased, $current_stock, $unit, $vendor, $purchase_date, $unit_price, $total_price, $inventory_id]);
        logActivity($user_id, "UPDATE", 'inventory', $inventory_id, "Updated inventory - $item_name");
    } else {
        $stmt = $pdo->prepare("INSERT INTO inventory (item_name, quantity_purchased, current_stock, unit, vendor, purchase_date, unit_price, total_price, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$item_name, $quantity_purchased, $current_stock, $unit, $vendor, $purchase_date, $unit_price, $total_price, $user_id]);
        logActivity($user_id, "CREATE", 'inventory', $new_id, "Added inventory - $item_name");
    }

    header("Location: inventorySK.php?success=1");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE inventory_id=?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], "DELETE", 'inventory', $id, "Deleted inventory item ID $id");
    header("Location: inventorySK.php?deleted=1");
    exit;
}

// Get inventory items
$stmt = $pdo->query("SELECT i.*, u.full_name FROM inventory i JOIN users u ON i.user_id = u.user_id ORDER BY i.last_updated DESC");
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="../admin/admin-style.css">
    <link rel="stylesheet" href="../admin/inventory.css">
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
                <li><a href="manage-filesSK.php"><i class="fas fa-folder"></i> Files</a></li>
                <li><a href="inventorySK.php" class="active"><i class="fas fa-boxes"></i> Inventory</a></li>
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
                <i class="fas fa-check-circle"></i> Inventory item saved successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">
                <i class="fas fa-trash-alt"></i> Inventory item deleted successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Error: <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h2><i class="fas fa-boxes"></i> Inventory Management</h2>
            <button class="btn-primary" onclick="openModal()">
                <i class="fas fa-plus-circle"></i> Add Item
            </button>
        </div>

        <section class="dashboard-section">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Supplier/Vendor</th>
                            <th>Original Qty</th>
                            <th>Available Stock</th>
                            <th>Unit</th>
                            <th>Cost Per Unit</th>
                            <th>Total Amount</th>
                            <th>Purchase Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory)): ?>
                            <tr>
                                <td colspan="9" class="no-data">No inventory items found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventory as $item): ?>
                                <?php
                                // Calculate stock percentage
                                $stockPercentage = ($item['current_stock'] / $item['quantity_purchased']) * 100;

                                // Determine color based on percentage
                                if ($stockPercentage <= 20) {
                                    $stockClass = 'stock-low';      // Red: 20% or less remaining
                                } elseif ($stockPercentage <= 50) {
                                    $stockClass = 'stock-medium';   // Orange: 21-50% remaining
                                } else {
                                    $stockClass = 'stock-good';     // Green: 51%+ remaining
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><?= htmlspecialchars($item['vendor']) ?></td>
                                    <td><?= htmlspecialchars($item['quantity_purchased']) ?></td>
                                    <td><span class="stock-badge <?= $stockClass ?>"><?= htmlspecialchars($item['current_stock']) ?></span></td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                                    <td>₱<?= number_format($item['total_price'], 2) ?></td>
                                    <td><?= date('M d, Y', strtotime($item['purchase_date'])) ?></td>
                                    <td>
                                        <button class="table-btn edit" onclick='editItem(<?= json_encode($item) ?>)'>
                                            <i class="fas fa-edit"></i><span>Edit</span>
                                        </button>
                                        <a href="?delete=<?= $item['inventory_id'] ?>"
                                            class="table-btn delete"
                                            onclick="return confirm('Are you sure you want to delete this item?')">
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
    <div id="inventoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Inventory Item</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="inventory_id" id="inventory_id">

                <div class="form-group">
                    <label for="item_name">Product/Item Name:</label>
                    <input type="text" name="item_name" id="item_name" required class="form-input" placeholder="e.g., Ballpen (Blue)">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity_purchased">Quantity Purchased:</label>
                        <input type="number" name="quantity_purchased" id="quantity_purchased" required class="form-input" min="1" oninput="calculateTotal()">
                    </div>

                    <div class="form-group">
                        <label for="current_stock">Current Stock Available:</label>
                        <input type="number" name="current_stock" id="current_stock" required class="form-input" min="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit">Unit Type/Measurement:</label>
                        <input type="text" name="unit" id="unit" class="form-input" placeholder="e.g., pcs, kg, box, ream">
                    </div>

                    <div class="form-group">
                        <label for="vendor">Vendor/Supplier Name:</label>
                        <input type="text" name="vendor" id="vendor" required class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit_price">Price Per Unit (₱):</label>
                        <input type="number" name="unit_price" id="unit_price" step="0.01" class="form-input" min="0" oninput="calculateTotal()">
                    </div>

                    <div class="form-group">
                        <label for="total_price">Total Amount Paid (₱):</label>
                        <input type="number" name="total_price" id="total_price" step="0.01" class="form-input" min="0" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label for="purchase_date">Date of Purchase:</label>
                    <input type="date" name="purchase_date" id="purchase_date" required class="form-input" max="<?= date('Y-m-d') ?>">
                </div>


                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Item
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('inventoryModal');

        function openModal() {
            document.getElementById('modalTitle').innerText = "Add Inventory Item";
            document.getElementById('inventory_id').value = '';
            document.getElementById('item_name').value = '';
            document.getElementById('quantity_purchased').value = '';
            document.getElementById('current_stock').value = '';
            document.getElementById('unit').value = '';
            document.getElementById('vendor').value = '';
            document.getElementById('unit_price').value = '';
            document.getElementById('total_price').value = '';
            document.getElementById('purchase_date').value = '';
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

        function editItem(data) {
            document.getElementById('modalTitle').innerText = "Edit Inventory Item";
            document.getElementById('inventory_id').value = data.inventory_id;
            document.getElementById('item_name').value = data.item_name;
            document.getElementById('quantity_purchased').value = data.quantity_purchased;
            document.getElementById('current_stock').value = data.current_stock;
            document.getElementById('unit').value = data.unit || '';
            document.getElementById('vendor').value = data.vendor;
            document.getElementById('unit_price').value = data.unit_price || '';
            document.getElementById('total_price').value = data.total_price || '';
            document.getElementById('purchase_date').value = data.purchase_date;
            modal.style.display = 'block';
        }

        function calculateTotal() {
            const qty = parseFloat(document.getElementById('quantity_purchased').value) || 0;
            const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
            const total = qty * unitPrice;
            document.getElementById('total_price').value = total.toFixed(2);
        }

        document.getElementById('hamburger').addEventListener('click', () => {
            document.getElementById('navigation').classList.toggle('active');
            document.getElementById('hamburger').classList.toggle('active');
        });
    </script>
</body>

</html>