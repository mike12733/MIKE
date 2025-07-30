<?php
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';
$equipment_info = null;

// Handle barcode scan and location update
if ($_POST) {
    $barcode = sanitize($_POST['barcode'] ?? '');
    $new_location = sanitize($_POST['new_location'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (empty($barcode)) {
        $error = 'Barcode is required.';
    } elseif (empty($new_location)) {
        $error = 'New location is required.';
    } else {
        $result = updateEquipmentLocation($barcode, $new_location, $notes);
        
        if ($result['success']) {
            $success = $result['message'];
            $equipment_info = $result;
        } else {
            $error = $result['message'];
        }
    }
}

// Get recent location changes for display
$recent_changes = getRecentLocationChanges(10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Scanner - Inventory Tracking System</title>
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
        .scanner-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 2rem;
            color: white;
            text-align: center;
        }
        .scanner-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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
        .equipment-info {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 15px;
            padding: 1.5rem;
            color: white;
            margin-top: 1rem;
        }
        .barcode-input {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            text-align: center;
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
                        <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
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
                            <a class="nav-link" href="add_equipment.php">
                                <i class="fas fa-plus me-2"></i>Add Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="barcode_scanner.php">
                                <i class="fas fa-qrcode me-2"></i>Barcode Scanner
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="location_tracker.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Location Tracker
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
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-qrcode me-2"></i>Barcode Scanner
                    </h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Scanner Interface -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="scanner-container">
                                    <div class="scanner-icon">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <h4>Scan Equipment Barcode</h4>
                                    <p class="mb-0">Scan or enter barcode to update equipment location</p>
                                </div>

                                <form method="POST" class="mt-4">
                                    <div class="mb-3">
                                        <label for="barcode" class="form-label">Equipment Barcode</label>
                                        <input type="text" class="form-control barcode-input" id="barcode" name="barcode" 
                                               placeholder="EQ202400001" required autofocus>
                                        <div class="form-text">Scan barcode or type manually</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_location" class="form-label">New Location</label>
                                        <input type="text" class="form-control" id="new_location" name="new_location" 
                                               placeholder="e.g., Building A - Room 101" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                                  placeholder="Additional notes about the location change"></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-map-marker-alt me-2"></i>Update Location
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Equipment Info -->
                    <div class="col-lg-6 mb-4">
                        <?php if ($equipment_info): ?>
                            <div class="equipment-info">
                                <h5><i class="fas fa-check-circle me-2"></i>Location Updated Successfully</h5>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <strong>Equipment:</strong><br>
                                        <?= htmlspecialchars($equipment_info['equipment']['item_name']) ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Barcode:</strong><br>
                                        <code><?= htmlspecialchars($equipment_info['equipment']['barcode']) ?></code>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <strong>Previous Location:</strong><br>
                                        <?= htmlspecialchars($equipment_info['previous_location'] ?: 'Not set') ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>New Location:</strong><br>
                                        <?= htmlspecialchars($equipment_info['new_location']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center justify-content-center">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-search fa-3x mb-3"></i>
                                        <h5>Equipment Information</h5>
                                        <p>Scan a barcode to see equipment details</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Location Changes -->
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Location Changes
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_changes)): ?>
                            <div class="text-center p-4">
                                <i class="fas fa-map-marker-alt fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No location changes recorded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Barcode</th>
                                            <th>Previous Location</th>
                                            <th>New Location</th>
                                            <th>Scanned By</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_changes as $change): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($change['item_name']) ?></td>
                                                <td><code><?= htmlspecialchars($change['barcode']) ?></code></td>
                                                <td><?= htmlspecialchars($change['previous_location'] ?: '-') ?></td>
                                                <td><?= htmlspecialchars($change['new_location']) ?></td>
                                                <td><?= htmlspecialchars($change['scanned_by_name']) ?></td>
                                                <td>
                                                    <small class="text-muted"><?= formatDateTime($change['created_at']) ?></small>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on barcode input and clear after submission
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.getElementById('barcode');
            const form = document.querySelector('form');
            
            // Focus on barcode input
            barcodeInput.focus();
            
            // Clear form after successful submission
            <?php if ($success): ?>
                form.reset();
                barcodeInput.focus();
            <?php endif; ?>
            
            // Auto-submit on barcode scan (when Enter is pressed in barcode field)
            barcodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const locationInput = document.getElementById('new_location');
                    if (locationInput.value.trim() === '') {
                        locationInput.focus();
                    } else {
                        form.submit();
                    }
                }
            });
        });
    </script>
</body>
</html>