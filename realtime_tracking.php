<?php
require_once 'includes/functions.php';
requireLogin();

// Get filter parameters
$location_filter = sanitize($_GET['location'] ?? '');
$condition_filter = sanitize($_GET['condition'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');
$category_filter = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['search'] ?? '');

// Build the query with filters
$where_conditions = ['e.is_active = 1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.item_name LIKE ? OR e.barcode LIKE ? OR e.category LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($location_filter)) {
    $where_conditions[] = "ers.current_location LIKE ?";
    $params[] = "%{$location_filter}%";
}

if (!empty($condition_filter)) {
    $where_conditions[] = "ers.current_condition = ?";
    $params[] = $condition_filter;
}

if (!empty($status_filter)) {
    if ($status_filter === 'checked_out') {
        $where_conditions[] = "ers.is_checked_out = 1";
    } elseif ($status_filter === 'available') {
        $where_conditions[] = "(ers.is_checked_out = 0 OR ers.is_checked_out IS NULL)";
    }
}

if (!empty($category_filter)) {
    $where_conditions[] = "e.category = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get equipment with real-time status
$equipment_sql = "SELECT e.id, e.item_name, e.barcode, e.category, e.location as original_location,
                         e.condition_status as original_condition, e.quantity, e.last_scanned_at,
                         ers.current_location, ers.current_condition, ers.is_checked_out, 
                         ers.checked_out_to, ers.last_activity, 
                         au.full_name as last_scanned_by_name
                  FROM equipment e 
                  LEFT JOIN equipment_realtime_status ers ON e.id = ers.equipment_id 
                  LEFT JOIN admin_users au ON ers.last_scanned_by = au.id 
                  WHERE {$where_clause}
                  ORDER BY ers.last_activity DESC, e.created_at DESC";

$equipment_list = $db->fetchAll($equipment_sql, $params);

// Get unique values for filters
$locations = $db->fetchAll("SELECT DISTINCT current_location FROM equipment_realtime_status WHERE current_location IS NOT NULL ORDER BY current_location");
$categories = $db->fetchAll("SELECT DISTINCT category FROM equipment WHERE category IS NOT NULL AND is_active = 1 ORDER BY category");

// Get statistics
$stats = getEquipmentStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-time Equipment Tracking - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tracking-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .tracking-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        .stats-card {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .equipment-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            background: white;
        }
        .equipment-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-available { background-color: #28a745; }
        .status-checked-out { background-color: #ffc107; }
        .status-maintenance { background-color: #6c757d; }
        .status-damaged { background-color: #dc3545; }
        .status-lost { background-color: #343a40; }
        
        .condition-good { color: #28a745; }
        .condition-fair { color: #ffc107; }
        .condition-poor { color: #fd7e14; }
        .condition-damaged { color: #dc3545; }
        .condition-lost { color: #343a40; }
        
        .filter-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .last-activity {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .equipment-grid {
            max-height: 70vh;
            overflow-y: auto;
        }
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: linear-gradient(45deg, #007bff, #6610f2);
            border: none;
            color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }
        .auto-refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="tracking-container">
        <div class="container-fluid">
            <!-- Auto-refresh indicator -->
            <div class="auto-refresh-indicator" id="refreshIndicator" style="display: none;">
                <i class="fas fa-sync-alt fa-spin me-2"></i>Auto-refreshing...
            </div>

            <!-- Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="dashboard.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Real-time Equipment Tracking
                            </a>
                            <div class="navbar-nav ms-auto">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-home me-1"></i>Dashboard
                                </a>
                                <a class="nav-link" href="barcode_scanner.php">
                                    <i class="fas fa-qrcode me-1"></i>Scanner
                                </a>
                                <a class="nav-link" href="equipment.php">
                                    <i class="fas fa-boxes me-1"></i>Equipment
                                </a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stats-card text-center">
                        <h3><?php echo $stats['total']; ?></h3>
                        <small>Total Equipment</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stats-card text-center bg-success">
                        <h3><?php echo $stats['good']; ?></h3>
                        <small>Good Condition</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stats-card text-center bg-warning">
                        <h3><?php echo $stats['checked_out']; ?></h3>
                        <small>Checked Out</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stats-card text-center bg-info">
                        <h3><?php echo $stats['fair']; ?></h3>
                        <small>Fair Condition</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stats-card text-center bg-danger">
                        <h3><?php echo $stats['damaged']; ?></h3>
                        <small>Damaged</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stats-card text-center bg-dark">
                        <h3><?php echo $stats['lost']; ?></h3>
                        <small>Lost</small>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name, barcode, category...">
                    </div>
                    <div class="col-md-2">
                        <label for="location" class="form-label">Location</label>
                        <select class="form-select" id="location" name="location">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc['current_location']); ?>" 
                                        <?php echo ($location_filter === $loc['current_location']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc['current_location']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                        <?php echo ($category_filter === $cat['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="condition" class="form-label">Condition</label>
                        <select class="form-select" id="condition" name="condition">
                            <option value="">All Conditions</option>
                            <option value="Good" <?php echo ($condition_filter === 'Good') ? 'selected' : ''; ?>>Good</option>
                            <option value="Fair" <?php echo ($condition_filter === 'Fair') ? 'selected' : ''; ?>>Fair</option>
                            <option value="Poor" <?php echo ($condition_filter === 'Poor') ? 'selected' : ''; ?>>Poor</option>
                            <option value="Damaged" <?php echo ($condition_filter === 'Damaged') ? 'selected' : ''; ?>>Damaged</option>
                            <option value="Lost" <?php echo ($condition_filter === 'Lost') ? 'selected' : ''; ?>>Lost</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="available" <?php echo ($status_filter === 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="checked_out" <?php echo ($status_filter === 'checked_out') ? 'selected' : ''; ?>>Checked Out</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <small class="text-muted">
                            Showing <?php echo count($equipment_list); ?> equipment items
                            <?php if (!empty($search) || !empty($location_filter) || !empty($condition_filter) || !empty($status_filter) || !empty($category_filter)): ?>
                                | <a href="realtime_tracking.php" class="text-decoration-none">Clear Filters</a>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Equipment Grid -->
            <div class="equipment-grid">
                <div class="row" id="equipmentGrid">
                    <?php if (empty($equipment_list)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No equipment found</h5>
                                <p class="text-muted">Try adjusting your filters or search terms.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($equipment_list as $equipment): ?>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="equipment-card p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">
                                            <a href="barcode_scanner.php?barcode=<?php echo urlencode($equipment['barcode']); ?>" 
                                               class="text-decoration-none text-primary">
                                                <?php echo htmlspecialchars($equipment['item_name']); ?>
                                            </a>
                                        </h6>
                                        <div class="text-end">
                                            <?php
                                            $status_class = 'status-available';
                                            $status_text = 'Available';
                                            
                                            if ($equipment['is_checked_out']) {
                                                $status_class = 'status-checked-out';
                                                $status_text = 'Checked Out';
                                            } elseif ($equipment['current_condition'] === 'Damaged') {
                                                $status_class = 'status-damaged';
                                                $status_text = 'Damaged';
                                            } elseif ($equipment['current_condition'] === 'Lost') {
                                                $status_class = 'status-lost';
                                                $status_text = 'Lost';
                                            }
                                            ?>
                                            <span class="status-indicator <?php echo $status_class; ?>"></span>
                                            <small class="text-muted"><?php echo $status_text; ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <small class="text-muted">Barcode:</small><br>
                                            <code class="small"><?php echo $equipment['barcode']; ?></code>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Category:</small><br>
                                            <small><?php echo htmlspecialchars($equipment['category'] ?? 'N/A'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <small class="text-muted">Location:</small><br>
                                            <span class="badge bg-info">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($equipment['current_location'] ?? $equipment['original_location'] ?? 'Unknown'); ?>
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Condition:</small><br>
                                            <?php
                                            $condition = $equipment['current_condition'] ?? $equipment['original_condition'];
                                            $condition_class = 'condition-' . strtolower($condition);
                                            ?>
                                            <span class="fw-bold <?php echo $condition_class; ?>">
                                                <?php echo $condition; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($equipment['is_checked_out'] && $equipment['checked_out_to']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Checked out to:</small><br>
                                            <small class="fw-bold"><?php echo htmlspecialchars($equipment['checked_out_to']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="last-activity">
                                            <?php if ($equipment['last_activity']): ?>
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo formatDateTime($equipment['last_activity']); ?>
                                                <?php if ($equipment['last_scanned_by_name']): ?>
                                                    <br><small>by <?php echo htmlspecialchars($equipment['last_scanned_by_name']); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No recent activity</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="barcode_scanner.php?barcode=<?php echo urlencode($equipment['barcode']); ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Scan/Update">
                                                <i class="fas fa-qrcode"></i>
                                            </a>
                                            <a href="equipment.php?id=<?php echo $equipment['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button type="button" class="btn refresh-btn" onclick="refreshData()" title="Refresh Data">
        <i class="fas fa-sync-alt" id="refreshIcon"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let autoRefresh = true;
        let refreshInterval;

        // Auto-refresh functionality
        function startAutoRefresh() {
            refreshInterval = setInterval(function() {
                if (autoRefresh) {
                    refreshData(true);
                }
            }, 30000); // Refresh every 30 seconds
        }

        function refreshData(isAutoRefresh = false) {
            const refreshIcon = document.getElementById('refreshIcon');
            const refreshIndicator = document.getElementById('refreshIndicator');
            
            refreshIcon.classList.add('fa-spin');
            
            if (isAutoRefresh) {
                refreshIndicator.style.display = 'block';
                setTimeout(() => {
                    refreshIndicator.style.display = 'none';
                }, 2000);
            }
            
            // Preserve current filters and reload page
            window.location.reload();
        }

        // Toggle auto-refresh
        document.addEventListener('keydown', function(e) {
            if (e.key === 'r' && e.ctrlKey) {
                e.preventDefault();
                refreshData();
            }
        });

        // Start auto-refresh when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startAutoRefresh();
            
            // Add visual indicators for real-time updates
            const equipmentCards = document.querySelectorAll('.equipment-card');
            equipmentCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.borderColor = '#007bff';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.borderColor = '#dee2e6';
                });
            });
        });

        // Handle filter form submission with loading state
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            submitBtn.disabled = true;
        });

        // Show last update time
        const lastUpdate = new Date().toLocaleTimeString();
        console.log('Equipment data last updated at:', lastUpdate);
    </script>
</body>
</html>