<?php
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';
$equipment_data = null;

// Handle barcode scan
if (isset($_POST['scan_barcode'])) {
    $barcode = sanitize($_POST['barcode']);
    
    if (empty($barcode)) {
        $error = 'Please enter a barcode.';
    } else {
        $equipment_data = getEquipmentByBarcode($barcode);
        if (!$equipment_data) {
            $error = 'Equipment not found with barcode: ' . $barcode;
        }
    }
}

// Handle equipment update
if (isset($_POST['update_equipment'])) {
    $equipment_id = (int)$_POST['equipment_id'];
    $new_location = sanitize($_POST['new_location']);
    $new_condition = sanitize($_POST['new_condition']);
    $scan_type = sanitize($_POST['scan_type']);
    $notes = sanitize($_POST['notes']);
    
    if (trackEquipmentScan($equipment_id, $new_location, $new_condition, $scan_type, $notes)) {
        $success = 'Equipment updated successfully!';
        // Refresh equipment data
        $equipment_data = $db->fetch("SELECT e.*, ers.current_location as realtime_location, ers.is_checked_out, ers.checked_out_to, ers.last_activity 
                                     FROM equipment e 
                                     LEFT JOIN equipment_realtime_status ers ON e.id = ers.equipment_id 
                                     WHERE e.id = ?", [$equipment_id]);
    } else {
        $error = 'Failed to update equipment.';
    }
}

// Handle checkout
if (isset($_POST['checkout_equipment'])) {
    $equipment_id = (int)$_POST['equipment_id'];
    $checked_out_to = sanitize($_POST['checked_out_to']);
    $notes = sanitize($_POST['checkout_notes']);
    
    if (checkoutEquipment($equipment_id, $checked_out_to, $notes)) {
        $success = 'Equipment checked out successfully!';
        // Refresh equipment data
        $equipment_data = $db->fetch("SELECT e.*, ers.current_location as realtime_location, ers.is_checked_out, ers.checked_out_to, ers.last_activity 
                                     FROM equipment e 
                                     LEFT JOIN equipment_realtime_status ers ON e.id = ers.equipment_id 
                                     WHERE e.id = ?", [$equipment_id]);
    } else {
        $error = 'Failed to checkout equipment.';
    }
}

// Handle checkin
if (isset($_POST['checkin_equipment'])) {
    $equipment_id = (int)$_POST['equipment_id'];
    $new_location = sanitize($_POST['checkin_location']);
    $new_condition = sanitize($_POST['checkin_condition']);
    $notes = sanitize($_POST['checkin_notes']);
    
    if (checkinEquipment($equipment_id, $new_location, $new_condition, $notes)) {
        $success = 'Equipment checked in successfully!';
        // Refresh equipment data
        $equipment_data = $db->fetch("SELECT e.*, ers.current_location as realtime_location, ers.is_checked_out, ers.checked_out_to, ers.last_activity 
                                     FROM equipment e 
                                     LEFT JOIN equipment_realtime_status ers ON e.id = ers.equipment_id 
                                     WHERE e.id = ?", [$equipment_id]);
    } else {
        $error = 'Failed to checkin equipment.';
    }
}

// Get tracking history if equipment is loaded
$tracking_history = [];
if ($equipment_data) {
    $tracking_history = getEquipmentTrackingHistory($equipment_data['id'], 10);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Scanner - Real-time Equipment Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .scanner-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .scanner-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        .barcode-input {
            font-size: 1.2rem;
            padding: 15px;
            border: 2px solid #007bff;
            border-radius: 10px;
        }
        .scan-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 10px;
            color: white;
            transition: all 0.3s ease;
        }
        .scan-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .equipment-card {
            border: 2px solid #007bff;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        .tracking-history {
            max-height: 300px;
            overflow-y: auto;
        }
        .camera-btn {
            background: linear-gradient(45deg, #6f42c1, #e83e8c);
            border: none;
            color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            margin-left: 10px;
        }
        .action-buttons .btn {
            margin: 5px;
            border-radius: 8px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="scanner-container">
        <div class="container">
            <!-- Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="dashboard.php">
                                <i class="fas fa-qrcode me-2"></i>Equipment Scanner
                            </a>
                            <div class="navbar-nav ms-auto">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-home me-1"></i>Dashboard
                                </a>
                                <a class="nav-link" href="equipment.php">
                                    <i class="fas fa-boxes me-1"></i>Equipment
                                </a>
                                <a class="nav-link" href="realtime_tracking.php">
                                    <i class="fas fa-map-marker-alt me-1"></i>Live Tracking
                                </a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Scanner Section -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="scanner-card p-4 mb-4">
                        <div class="text-center mb-4">
                            <h2 class="text-primary">
                                <i class="fas fa-barcode me-2"></i>Barcode Scanner
                            </h2>
                            <p class="text-muted">Scan or enter equipment barcode for real-time tracking</p>
                        </div>

                        <!-- Alert Messages -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Barcode Input Form -->
                        <form method="POST" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-md-8">
                                    <label for="barcode" class="form-label fw-bold">Equipment Barcode</label>
                                    <input type="text" 
                                           class="form-control barcode-input" 
                                           id="barcode" 
                                           name="barcode" 
                                           placeholder="Scan or type barcode (e.g., EQ2024000001)"
                                           value="<?php echo $equipment_data ? $equipment_data['barcode'] : ''; ?>"
                                           autofocus>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="scan_barcode" class="btn scan-btn w-100">
                                        <i class="fas fa-search me-2"></i>Scan Equipment
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Camera Scanner Button -->
                        <div class="text-center mb-4">
                            <button type="button" class="btn camera-btn" onclick="startCameraScanner()" title="Use Camera Scanner">
                                <i class="fas fa-camera"></i>
                            </button>
                            <small class="d-block text-muted mt-2">Click to use camera for barcode scanning</small>
                        </div>
                    </div>

                    <!-- Equipment Details -->
                    <?php if ($equipment_data): ?>
                        <div class="equipment-card p-4 mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="text-primary mb-3">
                                        <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($equipment_data['item_name']); ?>
                                    </h4>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-6">
                                            <strong>Barcode:</strong><br>
                                            <code class="fs-6"><?php echo $equipment_data['barcode']; ?></code>
                                        </div>
                                        <div class="col-sm-6">
                                            <strong>Category:</strong><br>
                                            <?php echo htmlspecialchars($equipment_data['category'] ?? 'N/A'); ?>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-sm-6">
                                            <strong>Current Location:</strong><br>
                                            <span class="badge bg-info status-badge">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($equipment_data['realtime_location'] ?? $equipment_data['location'] ?? 'Unknown'); ?>
                                            </span>
                                        </div>
                                        <div class="col-sm-6">
                                            <strong>Condition:</strong><br>
                                            <?php
                                            $condition = $equipment_data['condition_status'];
                                            $badge_class = match($condition) {
                                                'Good' => 'bg-success',
                                                'Fair' => 'bg-warning',
                                                'Poor' => 'bg-orange',
                                                'Damaged' => 'bg-danger',
                                                'Lost' => 'bg-dark',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> status-badge">
                                                <?php echo $condition; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-sm-6">
                                            <strong>Status:</strong><br>
                                            <?php if ($equipment_data['is_checked_out']): ?>
                                                <span class="badge bg-warning status-badge">
                                                    <i class="fas fa-sign-out-alt me-1"></i>Checked Out
                                                </span>
                                                <br><small class="text-muted">To: <?php echo htmlspecialchars($equipment_data['checked_out_to']); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-success status-badge">
                                                    <i class="fas fa-check me-1"></i>Available
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-sm-6">
                                            <strong>Last Activity:</strong><br>
                                            <small class="text-muted">
                                                <?php echo $equipment_data['last_activity'] ? formatDateTime($equipment_data['last_activity']) : 'Never'; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <?php if ($equipment_data['description']): ?>
                                        <div class="mb-3">
                                            <strong>Description:</strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($equipment_data['description']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4 text-center">
                                    <div class="mb-3">
                                        <img src="<?php echo generateBarcodeImage($equipment_data['barcode']); ?>" 
                                             alt="Barcode" class="img-fluid" style="max-width: 200px;">
                                    </div>
                                    
                                    <!-- Quick Action Buttons -->
                                    <div class="action-buttons">
                                        <?php if (!$equipment_data['is_checked_out']): ?>
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                                                <i class="fas fa-sign-out-alt me-1"></i>Check Out
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#checkinModal">
                                                <i class="fas fa-sign-in-alt me-1"></i>Check In
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateModal">
                                            <i class="fas fa-edit me-1"></i>Update
                                        </button>
                                        
                                        <a href="equipment.php?id=<?php echo $equipment_data['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tracking History -->
                        <?php if (!empty($tracking_history)): ?>
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>Recent Tracking History
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="tracking-history">
                                        <?php foreach ($tracking_history as $track): ?>
                                            <div class="border-bottom p-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-3">
                                                        <small class="text-muted"><?php echo formatDateTime($track['scanned_at']); ?></small>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <?php
                                                        $scan_badge = match($track['scan_type']) {
                                                            'checkout' => 'bg-warning',
                                                            'checkin' => 'bg-success',
                                                            'location_update' => 'bg-info',
                                                            'condition_update' => 'bg-primary',
                                                            'maintenance' => 'bg-secondary',
                                                            default => 'bg-light text-dark'
                                                        };
                                                        ?>
                                                        <span class="badge <?php echo $scan_badge; ?> small">
                                                            <?php echo ucfirst(str_replace('_', ' ', $track['scan_type'])); ?>
                                                        </span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <?php if ($track['previous_location'] !== $track['new_location']): ?>
                                                            <small>
                                                                <i class="fas fa-arrow-right text-muted me-1"></i>
                                                                <?php echo htmlspecialchars($track['new_location']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <small class="text-muted">
                                                            by <?php echo htmlspecialchars($track['scanned_by_name']); ?>
                                                        </small>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <?php if ($track['notes']): ?>
                                                            <small class="text-muted" title="<?php echo htmlspecialchars($track['notes']); ?>">
                                                                <i class="fas fa-sticky-note"></i>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Equipment Modal -->
    <?php if ($equipment_data): ?>
        <div class="modal fade" id="updateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Equipment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="equipment_id" value="<?php echo $equipment_data['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="new_location" class="form-label">New Location</label>
                                <input type="text" class="form-control" id="new_location" name="new_location" 
                                       value="<?php echo htmlspecialchars($equipment_data['realtime_location'] ?? $equipment_data['location'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_condition" class="form-label">Condition</label>
                                <select class="form-select" id="new_condition" name="new_condition">
                                    <option value="Good" <?php echo ($equipment_data['condition_status'] == 'Good') ? 'selected' : ''; ?>>Good</option>
                                    <option value="Fair" <?php echo ($equipment_data['condition_status'] == 'Fair') ? 'selected' : ''; ?>>Fair</option>
                                    <option value="Poor" <?php echo ($equipment_data['condition_status'] == 'Poor') ? 'selected' : ''; ?>>Poor</option>
                                    <option value="Damaged" <?php echo ($equipment_data['condition_status'] == 'Damaged') ? 'selected' : ''; ?>>Damaged</option>
                                    <option value="Lost" <?php echo ($equipment_data['condition_status'] == 'Lost') ? 'selected' : ''; ?>>Lost</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="scan_type" class="form-label">Update Type</label>
                                <select class="form-select" id="scan_type" name="scan_type">
                                    <option value="location_update">Location Update</option>
                                    <option value="condition_update">Condition Update</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Optional notes about this update..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_equipment" class="btn btn-primary">Update Equipment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Checkout Modal -->
        <div class="modal fade" id="checkoutModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Check Out Equipment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="equipment_id" value="<?php echo $equipment_data['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="checked_out_to" class="form-label">Check Out To *</label>
                                <input type="text" class="form-control" id="checked_out_to" name="checked_out_to" 
                                       placeholder="Person/Department name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="checkout_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="checkout_notes" name="checkout_notes" rows="3" 
                                          placeholder="Purpose, expected return date, etc..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="checkout_equipment" class="btn btn-warning">Check Out</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Checkin Modal -->
        <div class="modal fade" id="checkinModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Check In Equipment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="equipment_id" value="<?php echo $equipment_data['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="checkin_location" class="form-label">Return Location</label>
                                <input type="text" class="form-control" id="checkin_location" name="checkin_location" 
                                       value="<?php echo htmlspecialchars($equipment_data['realtime_location'] ?? $equipment_data['location'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="checkin_condition" class="form-label">Condition After Use</label>
                                <select class="form-select" id="checkin_condition" name="checkin_condition">
                                    <option value="Good" <?php echo ($equipment_data['condition_status'] == 'Good') ? 'selected' : ''; ?>>Good</option>
                                    <option value="Fair" <?php echo ($equipment_data['condition_status'] == 'Fair') ? 'selected' : ''; ?>>Fair</option>
                                    <option value="Poor" <?php echo ($equipment_data['condition_status'] == 'Poor') ? 'selected' : ''; ?>>Poor</option>
                                    <option value="Damaged" <?php echo ($equipment_data['condition_status'] == 'Damaged') ? 'selected' : ''; ?>>Damaged</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="checkin_notes" class="form-label">Return Notes</label>
                                <textarea class="form-control" id="checkin_notes" name="checkin_notes" rows="3" 
                                          placeholder="Any issues, maintenance needs, etc..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="checkin_equipment" class="btn btn-success">Check In</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus barcode input
        document.getElementById('barcode').focus();
        
        // Auto-submit on barcode scan (when Enter is pressed)
        document.getElementById('barcode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('button[name="scan_barcode"]').click();
            }
        });

        // Placeholder for camera scanner functionality
        function startCameraScanner() {
            alert('Camera scanner functionality can be integrated with libraries like QuaggaJS or ZXing for real barcode scanning from camera.');
            // In a real implementation, you would integrate with a barcode scanning library
        }

        // Auto-refresh equipment data every 30 seconds if equipment is loaded
        <?php if ($equipment_data): ?>
            setInterval(function() {
                // You can implement AJAX refresh here for real-time updates
                console.log('Auto-refresh would update equipment status here');
            }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>