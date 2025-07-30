<?php
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Handle search
$search = sanitize($_GET['search'] ?? '');
$equipment = [];

if ($search) {
    $equipment = searchEquipment($search);
} else {
    $equipment = $db->fetchAll("SELECT * FROM equipment ORDER BY created_at DESC");
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get equipment details before deletion for logging
    $equipment_data = $db->fetch("SELECT * FROM equipment WHERE id = ?", [$id]);
    
    if ($equipment_data) {
        $db->query("DELETE FROM equipment WHERE id = ?", [$id]);
        
        // Log activity
        logActivity('Delete Equipment', 'equipment', $id, $equipment_data, null);
        
        $success = "Equipment '{$equipment_data['item_name']}' deleted successfully.";
        
        // Refresh equipment list
        if ($search) {
            $equipment = searchEquipment($search);
        } else {
            $equipment = $db->fetchAll("SELECT * FROM equipment ORDER BY created_at DESC");
        }
    } else {
        $error = 'Equipment not found.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment - Inventory Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,.1);
        }
        .sidebar {
            background: white;
            min-height: calc(100vh - 76px);
            box-shadow: 2px 0 5px rgba(0,0,0,.1);
        }
        .nav-link {
            color: #495057;
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge {
            font-size: 0.75em;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .search-box {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 0.5rem 1rem;
        }
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .barcode-cell {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-boxes me-2"></i>
                Inventory System
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?= htmlspecialchars($_SESSION['admin_name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="equipment.php">
                                <i class="fas fa-boxes me-2"></i>Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_equipment.php">
                                <i class="fas fa-plus me-2"></i>Add Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logs.php">
                                <i class="fas fa-history me-2"></i>Activity Logs
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Equipment Management</h1>
                        <a href="add_equipment.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Equipment
                        </a>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?= $success ?>
                        </div>
                    <?php endif; ?>

                    <!-- Search Bar -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-10">
                                        <input type="text" class="form-control search-box" name="search" 
                                               value="<?= htmlspecialchars($search) ?>" 
                                               placeholder="Search by name, description, category, barcode, or location...">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search me-2"></i>Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php if ($search): ?>
                                <div class="mt-2">
                                    <a href="equipment.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times me-1"></i>Clear Search
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Equipment Table -->
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Equipment List
                                <?php if ($search): ?>
                                    <small class="text-muted">(Search results for "<?= htmlspecialchars($search) ?>")</small>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($equipment)): ?>
                                <div class="text-center p-5">
                                    <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">
                                        <?= $search ? 'No equipment found matching your search.' : 'No equipment added yet.' ?>
                                    </h5>
                                    <?php if (!$search): ?>
                                        <a href="add_equipment.php" class="btn btn-primary mt-3">
                                            <i class="fas fa-plus me-2"></i>Add Your First Equipment
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Category</th>
                                                <th>Quantity</th>
                                                <th>Condition</th>
                                                <th>Location</th>
                                                <th>Barcode</th>
                                                <th>Added</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($equipment as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></div>
                                                        <?php if ($item['description']): ?>
                                                            <small class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 50)) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($item['category']) ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?= $item['quantity'] ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $condition_class = [
                                                            'Good' => 'success',
                                                            'Fair' => 'warning',
                                                            'Poor' => 'danger',
                                                            'Damaged' => 'danger',
                                                            'Lost' => 'dark'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?= $condition_class[$item['condition_status']] ?? 'secondary' ?>">
                                                            <?= $item['condition_status'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($item['location'] ?: '-') ?></td>
                                                    <td class="barcode-cell">
                                                        <code><?= $item['barcode'] ?></code>
                                                        <button class="btn btn-sm btn-outline-primary ms-1" 
                                                                onclick="showBarcode('<?= $item['barcode'] ?>', '<?= htmlspecialchars($item['item_name']) ?>')"
                                                                title="View Barcode">
                                                            <i class="fas fa-barcode"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted"><?= formatDate($item['created_at']) ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="edit_equipment.php?id=<?= $item['id'] ?>" 
                                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars($item['item_name']) ?>')" 
                                                                    title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barcode Modal -->
    <div class="modal fade" id="barcodeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-barcode me-2"></i>Equipment Barcode
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="barcodeContainer"></div>
                    <h6 class="mt-3" id="equipmentName"></h6>
                    <p class="text-muted mb-0" id="barcodeNumber"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printBarcode()">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                window.location.href = `equipment.php?delete=${id}<?= $search ? '&search=' . urlencode($search) : '' ?>`;
            }
        }

        function showBarcode(barcode, itemName) {
            document.getElementById('equipmentName').textContent = itemName;
            document.getElementById('barcodeNumber').textContent = barcode;
            
            // Generate barcode image (simple representation)
            const barcodeContainer = document.getElementById('barcodeContainer');
            barcodeContainer.innerHTML = `
                <div style="font-family: 'Courier New', monospace; font-size: 2em; letter-spacing: 2px; border: 2px solid #000; padding: 10px; display: inline-block;">
                    ||||| || ||| | || |||||
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('barcodeModal'));
            modal.show();
        }

        function printBarcode() {
            const printContent = document.querySelector('#barcodeModal .modal-body').innerHTML;
            const printWindow = window.open('', '', 'height=400,width=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Barcode</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                        .barcode { font-family: 'Courier New', monospace; font-size: 3em; }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>