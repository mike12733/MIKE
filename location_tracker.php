<?php
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Handle search and filtering
$search = sanitize($_GET['search'] ?? '');
$location_filter = sanitize($_GET['location'] ?? '');

// Get all equipment with location info
$equipment_sql = "SELECT * FROM equipment WHERE 1=1";
$params = [];

if ($search) {
    $equipment_sql .= " AND (item_name LIKE ? OR barcode LIKE ? OR location LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($location_filter) {
    $equipment_sql .= " AND location = ?";
    $params[] = $location_filter;
}

$equipment_sql .= " ORDER BY location, item_name";
$equipment = $db->fetchAll($equipment_sql, $params);

// Get location statistics
$location_stats = getLocationStats();

// Get recent location changes
$recent_changes = getRecentLocationChanges(15);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Tracker - Inventory Tracking System</title>
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
        .location-card {
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .location-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,.15);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
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
            padding: 0.5rem 1rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .location-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        .no-location {
            background: #6c757d;
            color: white;
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
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
                            <a class="nav-link" href="barcode_scanner.php">
                                <i class="fas fa-qrcode me-2"></i>Barcode Scanner
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="location_tracker.php">
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
                        <i class="fas fa-map-marker-alt me-2"></i>Location Tracker
                    </h1>
                    <a href="barcode_scanner.php" class="btn btn-primary">
                        <i class="fas fa-qrcode me-2"></i>Scan Barcode
                    </a>
                </div>

                <!-- Location Statistics -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-transparent">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Equipment by Location
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($location_stats)): ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                                        <p>No location data available yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($location_stats as $stat): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="location-card card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="card-title mb-1"><?= htmlspecialchars($stat['location']) ?></h6>
                                                                <small class="text-muted">Equipment count</small>
                                                            </div>
                                                            <div class="text-end">
                                                                <h4 class="mb-0 text-primary"><?= $stat['count'] ?></h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="stats-card card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                                <h3><?= count($location_stats) ?></h3>
                                <p class="mb-0">Active Locations</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search Equipment</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Search by name, barcode, or location">
                            </div>
                            <div class="col-md-4">
                                <label for="location" class="form-label">Filter by Location</label>
                                <select class="form-select" id="location" name="location">
                                    <option value="">All Locations</option>
                                    <?php foreach ($location_stats as $stat): ?>
                                        <option value="<?= htmlspecialchars($stat['location']) ?>" 
                                                <?= $location_filter === $stat['location'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($stat['location']) ?> (<?= $stat['count'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                                <?php if ($search || $location_filter): ?>
                                    <a href="location_tracker.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Equipment List -->
                <div class="card mb-4">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Equipment Locations
                            <?php if ($search || $location_filter): ?>
                                <small class="text-muted">(Filtered results)</small>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($equipment)): ?>
                            <div class="text-center p-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No equipment found</h5>
                                <p>Try adjusting your search criteria.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Barcode</th>
                                            <th>Current Location</th>
                                            <th>Condition</th>
                                            <th>Last Scanned</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($equipment as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($item['category']) ?></small>
                                                </td>
                                                <td><code><?= htmlspecialchars($item['barcode']) ?></code></td>
                                                <td>
                                                    <?php if ($item['location']): ?>
                                                        <span class="location-badge"><?= htmlspecialchars($item['location']) ?></span>
                                                    <?php else: ?>
                                                        <span class="no-location">No location set</span>
                                                    <?php endif; ?>
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
                                                <td>
                                                    <?php if ($item['last_scanned_at']): ?>
                                                        <small class="text-muted"><?= formatDateTime($item['last_scanned_at']) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Never scanned</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="showLocationHistory(<?= $item['id'] ?>, '<?= htmlspecialchars($item['item_name']) ?>')" 
                                                                title="Location History">
                                                            <i class="fas fa-history"></i>
                                                        </button>
                                                        <a href="edit_equipment.php?id=<?= $item['id'] ?>" 
                                                           class="btn btn-sm btn-outline-secondary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
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

                <!-- Recent Location Changes -->
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>Recent Location Changes
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_changes)): ?>
                            <div class="text-center p-4">
                                <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent location changes.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Equipment</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Changed By</th>
                                            <th>When</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_changes as $change): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($change['item_name']) ?></div>
                                                    <small class="text-muted"><code><?= htmlspecialchars($change['barcode']) ?></code></small>
                                                </td>
                                                <td>
                                                    <?php if ($change['previous_location']): ?>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($change['previous_location']) ?></span>
                                                    <?php else: ?>
                                                        <small class="text-muted">Not set</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="location-badge"><?= htmlspecialchars($change['new_location']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($change['scanned_by_name']) ?></td>
                                                <td>
                                                    <small class="text-muted"><?= formatDateTime($change['created_at']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($change['notes']): ?>
                                                        <small><?= htmlspecialchars($change['notes']) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
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

    <!-- Location History Modal -->
    <div class="modal fade" id="locationHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history me-2"></i>Location History
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="locationHistoryContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLocationHistory(equipmentId, equipmentName) {
            const modal = new bootstrap.Modal(document.getElementById('locationHistoryModal'));
            const content = document.getElementById('locationHistoryContent');
            
            // Show loading
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading location history for ${equipmentName}...</p>
                </div>
            `;
            
            modal.show();
            
            // Fetch location history via AJAX
            fetch(`ajax/get_location_history.php?id=${equipmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let historyHtml = `<h6 class="mb-3">${equipmentName}</h6>`;
                        
                        if (data.history.length === 0) {
                            historyHtml += `
                                <div class="text-center text-muted">
                                    <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                                    <p>No location history available.</p>
                                </div>
                            `;
                        } else {
                            historyHtml += `
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Previous Location</th>
                                                <th>New Location</th>
                                                <th>Changed By</th>
                                                <th>Date</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            
                            data.history.forEach(record => {
                                historyHtml += `
                                    <tr>
                                        <td>${record.previous_location || '<em>Not set</em>'}</td>
                                        <td><span class="badge bg-primary">${record.new_location}</span></td>
                                        <td>${record.scanned_by_name}</td>
                                        <td><small>${record.created_at}</small></td>
                                        <td><small>${record.notes || '-'}</small></td>
                                    </tr>
                                `;
                            });
                            
                            historyHtml += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }
                        
                        content.innerHTML = historyHtml;
                    } else {
                        content.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error loading location history: ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading location history. Please try again.
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>