<?php
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';
$equipment = null;
$tracking_history = [];

// Handle barcode scan/search
if ($_POST && isset($_POST['action'])) {
    $barcode = sanitize($_POST['barcode'] ?? '');
    
    if (empty($barcode)) {
        $error = 'Please enter or scan a barcode.';
    } else {
        // Find equipment by barcode
        $equipment = $db->fetch("SELECT * FROM equipment WHERE barcode = ?", [$barcode]);
        
        if (!$equipment) {
            $error = "Equipment with barcode '{$barcode}' not found.";
        } else {
            // Handle different actions
            switch ($_POST['action']) {
                case 'track':
                    // Just display equipment info
                    $success = "Equipment found and displayed.";
                    break;
                    
                case 'update_status':
                    $new_status = $_POST['new_status'] ?? '';
                    $location = sanitize($_POST['location'] ?? '');
                    $notes = sanitize($_POST['notes'] ?? '');
                    
                    if ($new_status) {
                        // Store old values for logging
                        $old_values = $equipment;
                        
                        // Update equipment status and location
                        $update_sql = "UPDATE equipment SET condition_status = ?, location = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                        $db->query($update_sql, [$new_status, $location, $equipment['id']]);
                        
                        // Log the tracking activity
                        $new_values = [
                            'condition_status' => $new_status,
                            'location' => $location,
                            'tracking_notes' => $notes,
                            'tracked_at' => date('Y-m-d H:i:s')
                        ];
                        
                        logActivity('Track Equipment', 'equipment', $equipment['id'], $old_values, $new_values);
                        
                        // Insert into tracking history (we'll create this table)
                        $tracking_sql = "INSERT INTO equipment_tracking (equipment_id, admin_id, previous_status, new_status, location, notes, tracked_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                        $db->query($tracking_sql, [
                            $equipment['id'],
                            $_SESSION['admin_id'],
                            $equipment['condition_status'],
                            $new_status,
                            $location,
                            $notes
                        ]);
                        
                        $success = "Equipment status updated successfully and tracked.";
                        
                        // Refresh equipment data
                        $equipment = $db->fetch("SELECT * FROM equipment WHERE barcode = ?", [$barcode]);
                    }
                    break;
                    
                case 'quick_scan':
                    // Just track that equipment was scanned
                    logActivity('Quick Scan', 'equipment', $equipment['id'], null, [
                        'barcode' => $barcode,
                        'scanned_at' => date('Y-m-d H:i:s')
                    ]);
                    $success = "Equipment scanned and logged.";
                    break;
            }
        }
    }
}

// Get tracking history for found equipment
if ($equipment) {
    $tracking_history = $db->fetchAll("
        SELECT et.*, au.full_name as admin_name 
        FROM equipment_tracking et
        JOIN admin_users au ON et.admin_id = au.id
        WHERE et.equipment_id = ?
        ORDER BY et.tracked_at DESC
        LIMIT 10
    ", [$equipment['id']]);
}

// Get recent scans for dashboard
$recent_scans = $db->fetchAll("
    SELECT al.*, au.full_name, e.item_name, e.barcode
    FROM admin_logs al
    JOIN admin_users au ON al.admin_id = au.id
    LEFT JOIN equipment e ON al.record_id = e.id
    WHERE al.action IN ('Track Equipment', 'Quick Scan')
    ORDER BY al.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Tracking - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- QuaggaJS for barcode scanning -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
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
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
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
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            height: 300px;
        }
        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 100px;
            border: 2px solid #00ff00;
            border-radius: 10px;
            z-index: 10;
        }
        .equipment-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tracking-timeline {
            position: relative;
        }
        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 20px;
        }
        .timeline-dot {
            position: absolute;
            left: 11px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid #fff;
            box-shadow: 0 0 0 3px #dee2e6;
        }
        .barcode-input {
            font-family: 'Courier New', monospace;
            font-size: 1.2em;
            text-align: center;
            border: 3px solid #667eea;
            border-radius: 10px;
        }
        .scan-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }
        .scan-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        #scanner {
            width: 100%;
            height: 300px;
        }
        .status-badge {
            font-size: 0.9em;
            padding: 0.5rem 1rem;
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
                            <a class="nav-link" href="add_equipment.php">
                                <i class="fas fa-plus me-2"></i>Add Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="track_equipment.php">
                                <i class="fas fa-qrcode me-2"></i>Track Equipment
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
                        <h1 class="h3 mb-0">
                            <i class="fas fa-qrcode me-2"></i>Real-Time Equipment Tracking
                        </h1>
                        <div class="badge bg-success fs-6">
                            <i class="fas fa-circle me-1"></i>Live Tracking
                        </div>
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

                    <div class="row">
                        <!-- Scanner Section -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-camera me-2"></i>Barcode Scanner
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Manual Barcode Input -->
                                    <form method="POST" action="" id="trackingForm">
                                        <div class="mb-3">
                                            <label for="barcode" class="form-label">Enter or Scan Barcode</label>
                                            <input type="text" class="form-control barcode-input" id="barcode" name="barcode" 
                                                   placeholder="EQ2024XXXXX" value="<?= htmlspecialchars($_POST['barcode'] ?? '') ?>" 
                                                   autocomplete="off" autofocus>
                                        </div>

                                        <!-- Camera Scanner -->
                                        <div class="mb-3">
                                            <button type="button" class="btn scan-button w-100 mb-2" onclick="startScanner()">
                                                <i class="fas fa-camera me-2"></i>Start Camera Scanner
                                            </button>
                                            <div id="scanner" class="scanner-container" style="display: none;">
                                                <div class="scanner-overlay"></div>
                                            </div>
                                            <button type="button" class="btn btn-secondary w-100 mt-2" onclick="stopScanner()" style="display: none;" id="stopScanBtn">
                                                <i class="fas fa-stop me-2"></i>Stop Scanner
                                            </button>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <button type="submit" name="action" value="track" class="btn btn-primary w-100">
                                                    <i class="fas fa-search me-2"></i>Track Equipment
                                                </button>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="submit" name="action" value="quick_scan" class="btn btn-info w-100">
                                                    <i class="fas fa-qrcode me-2"></i>Quick Scan
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Scans -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history me-2"></i>Recent Scans
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_scans)): ?>
                                        <p class="text-muted text-center">No recent scans found.</p>
                                    <?php else: ?>
                                        <div class="tracking-timeline">
                                            <?php foreach ($recent_scans as $scan): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-dot"></div>
                                                    <div class="timeline-content">
                                                        <div class="fw-bold"><?= htmlspecialchars($scan['item_name'] ?? 'Equipment') ?></div>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($scan['barcode']) ?> - 
                                                            <?= htmlspecialchars($scan['action']) ?> by 
                                                            <?= htmlspecialchars($scan['full_name']) ?>
                                                        </small>
                                                        <div class="small text-muted"><?= formatDateTime($scan['created_at']) ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Equipment Details (shown when equipment is found) -->
                    <?php if ($equipment): ?>
                        <div class="row">
                            <div class="col-lg-8 mb-4">
                                <div class="card equipment-card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-box me-2"></i>Equipment Details
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h4><?= htmlspecialchars($equipment['item_name']) ?></h4>
                                                <p class="mb-2"><strong>Category:</strong> <?= htmlspecialchars($equipment['category']) ?></p>
                                                <p class="mb-2"><strong>Barcode:</strong> <code><?= $equipment['barcode'] ?></code></p>
                                                <p class="mb-2"><strong>Quantity:</strong> <?= $equipment['quantity'] ?></p>
                                                <p class="mb-2"><strong>Location:</strong> <?= htmlspecialchars($equipment['location'] ?: 'Not specified') ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <strong>Current Status:</strong>
                                                    <?php
                                                    $condition_class = [
                                                        'Good' => 'success',
                                                        'Fair' => 'warning',
                                                        'Poor' => 'danger',
                                                        'Damaged' => 'danger',
                                                        'Lost' => 'dark'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?= $condition_class[$equipment['condition_status']] ?? 'secondary' ?> status-badge">
                                                        <?= $equipment['condition_status'] ?>
                                                    </span>
                                                </p>
                                                <p class="mb-2"><strong>Added:</strong> <?= formatDate($equipment['created_at']) ?></p>
                                                <p class="mb-2"><strong>Last Updated:</strong> <?= formatDateTime($equipment['updated_at']) ?></p>
                                                <?php if ($equipment['description']): ?>
                                                    <p class="mb-0"><strong>Description:</strong><br><?= htmlspecialchars($equipment['description']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Status -->
                            <div class="col-lg-4 mb-4">
                                <div class="card">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-edit me-2"></i>Update Status
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <input type="hidden" name="barcode" value="<?= htmlspecialchars($equipment['barcode']) ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            
                                            <div class="mb-3">
                                                <label for="new_status" class="form-label">New Status</label>
                                                <select class="form-select" id="new_status" name="new_status" required>
                                                    <option value="">Select Status</option>
                                                    <option value="Good" <?= $equipment['condition_status'] == 'Good' ? 'selected' : '' ?>>Good</option>
                                                    <option value="Fair" <?= $equipment['condition_status'] == 'Fair' ? 'selected' : '' ?>>Fair</option>
                                                    <option value="Poor" <?= $equipment['condition_status'] == 'Poor' ? 'selected' : '' ?>>Poor</option>
                                                    <option value="Damaged" <?= $equipment['condition_status'] == 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                                                    <option value="Lost" <?= $equipment['condition_status'] == 'Lost' ? 'selected' : '' ?>>Lost</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="location" class="form-label">Current Location</label>
                                                <input type="text" class="form-control" id="location" name="location" 
                                                       value="<?= htmlspecialchars($equipment['location']) ?>" 
                                                       placeholder="Update location">
                                            </div>

                                            <div class="mb-3">
                                                <label for="notes" class="form-label">Tracking Notes</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                          placeholder="Add notes about this tracking update..."></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-warning w-100">
                                                <i class="fas fa-save me-2"></i>Update & Track
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tracking History -->
                        <?php if (!empty($tracking_history)): ?>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-transparent">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-route me-2"></i>Tracking History
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Date & Time</th>
                                                            <th>Admin</th>
                                                            <th>Status Change</th>
                                                            <th>Location</th>
                                                            <th>Notes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($tracking_history as $track): ?>
                                                            <tr>
                                                                <td><?= formatDateTime($track['tracked_at']) ?></td>
                                                                <td><?= htmlspecialchars($track['admin_name']) ?></td>
                                                                <td>
                                                                    <span class="badge bg-secondary"><?= $track['previous_status'] ?></span>
                                                                    <i class="fas fa-arrow-right mx-1"></i>
                                                                    <span class="badge bg-primary"><?= $track['new_status'] ?></span>
                                                                </td>
                                                                <td><?= htmlspecialchars($track['location'] ?: '-') ?></td>
                                                                <td><?= htmlspecialchars($track['notes'] ?: '-') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let scannerActive = false;

        function startScanner() {
            if (scannerActive) return;
            
            const scannerDiv = document.getElementById('scanner');
            const stopBtn = document.getElementById('stopScanBtn');
            
            scannerDiv.style.display = 'block';
            stopBtn.style.display = 'block';
            scannerActive = true;

            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#scanner'),
                    constraints: {
                        width: 480,
                        height: 320,
                        facingMode: "environment"
                    }
                },
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "i2of5_reader"
                    ]
                }
            }, function(err) {
                if (err) {
                    console.log(err);
                    alert('Error starting camera: ' + err.message);
                    stopScanner();
                    return;
                }
                Quagga.start();
            });

            Quagga.onDetected(function(data) {
                const code = data.codeResult.code;
                document.getElementById('barcode').value = code;
                stopScanner();
                
                // Auto-submit the form for quick tracking
                document.getElementById('trackingForm').submit();
            });
        }

        function stopScanner() {
            if (!scannerActive) return;
            
            Quagga.stop();
            const scannerDiv = document.getElementById('scanner');
            const stopBtn = document.getElementById('stopScanBtn');
            
            scannerDiv.style.display = 'none';
            stopBtn.style.display = 'none';
            scannerActive = false;
        }

        // Auto-focus on barcode input
        document.getElementById('barcode').focus();

        // Handle Enter key in barcode input
        document.getElementById('barcode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('button[value="track"]').click();
            }
        });

        // Real-time validation for barcode format
        document.getElementById('barcode').addEventListener('input', function(e) {
            const value = e.target.value.toUpperCase();
            const isValid = /^EQ\d{7}$/.test(value);
            
            if (value && !isValid) {
                e.target.classList.add('is-invalid');
            } else {
                e.target.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>