<?php
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Handle form submission
if ($_POST) {
    $item_name = sanitize($_POST['item_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $condition_status = $_POST['condition_status'] ?? 'Good';
    $location = sanitize($_POST['location'] ?? '');
    $purchase_date = $_POST['purchase_date'] ?? null;
    $purchase_price = (float)($_POST['purchase_price'] ?? 0);
    
    // Validation
    if (empty($item_name)) {
        $error = 'Item name is required.';
    } elseif (empty($category)) {
        $error = 'Category is required.';
    } elseif ($quantity < 1) {
        $error = 'Quantity must be at least 1.';
    } else {
        try {
            // Generate unique barcode
            do {
                $barcode = generateBarcode();
                $existing = $db->fetch("SELECT id FROM equipment WHERE barcode = ?", [$barcode]);
            } while ($existing);
            
            // Insert equipment
            $sql = "INSERT INTO equipment (item_name, description, category, quantity, condition_status, location, barcode, purchase_date, purchase_price, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$item_name, $description, $category, $quantity, $condition_status, $location, $barcode, $purchase_date, $purchase_price, $_SESSION['admin_id']];
            
            $db->query($sql, $params);
            $equipment_id = $db->lastInsertId();
            
            // Log activity
            logActivity('Add Equipment', 'equipment', $equipment_id, null, [
                'item_name' => $item_name,
                'category' => $category,
                'quantity' => $quantity,
                'barcode' => $barcode
            ]);
            
            $success = "Equipment '{$item_name}' added successfully with barcode: {$barcode}";
            
            // Clear form
            $item_name = $description = $category = $location = '';
            $quantity = $purchase_price = 0;
            $condition_status = 'Good';
            $purchase_date = null;
            
        } catch (Exception $e) {
            $error = 'Failed to add equipment: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment - Inventory Tracking System</title>
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
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
                            <a class="nav-link" href="equipment.php">
                                <i class="fas fa-boxes me-2"></i>Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="add_equipment.php">
                                <i class="fas fa-plus me-2"></i>Add Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="track_equipment.php">
                                <i class="fas fa-qrcode me-2"></i>Track Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="realtime_dashboard.php">
                                <i class="fas fa-broadcast-tower me-2"></i>Live Dashboard
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
                        <h1 class="h3 mb-0">Add New Equipment</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Add Equipment</li>
                            </ol>
                        </nav>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-plus-circle me-2"></i>Equipment Information
                                    </h5>
                                </div>
                                <div class="card-body">
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

                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="item_name" class="form-label">Item Name *</label>
                                                <input type="text" class="form-control" id="item_name" name="item_name" 
                                                       value="<?= htmlspecialchars($item_name ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="category" class="form-label">Category *</label>
                                                <input type="text" class="form-control" id="category" name="category" 
                                                       value="<?= htmlspecialchars($category ?? '') ?>" 
                                                       placeholder="e.g., Electronics, Furniture, Tools" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3" 
                                                      placeholder="Enter detailed description of the equipment"><?= htmlspecialchars($description ?? '') ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="quantity" class="form-label">Quantity *</label>
                                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                                       value="<?= htmlspecialchars($quantity ?? '1') ?>" min="1" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="condition_status" class="form-label">Condition</label>
                                                <select class="form-select" id="condition_status" name="condition_status">
                                                    <option value="Good" <?= ($condition_status ?? 'Good') == 'Good' ? 'selected' : '' ?>>Good</option>
                                                    <option value="Fair" <?= ($condition_status ?? '') == 'Fair' ? 'selected' : '' ?>>Fair</option>
                                                    <option value="Poor" <?= ($condition_status ?? '') == 'Poor' ? 'selected' : '' ?>>Poor</option>
                                                    <option value="Damaged" <?= ($condition_status ?? '') == 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="location" class="form-label">Location</label>
                                                <input type="text" class="form-control" id="location" name="location" 
                                                       value="<?= htmlspecialchars($location ?? '') ?>" 
                                                       placeholder="e.g., Room 101, Warehouse A">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="purchase_date" class="form-label">Purchase Date</label>
                                                <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                                       value="<?= htmlspecialchars($purchase_date ?? '') ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="purchase_price" class="form-label">Purchase Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="number" class="form-control" id="purchase_price" name="purchase_price" 
                                                           value="<?= htmlspecialchars($purchase_price ?? '') ?>" step="0.01" min="0">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <a href="equipment.php" class="btn btn-secondary me-md-2">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Add Equipment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>