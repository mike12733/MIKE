<?php
require_once 'includes/functions.php';
requireLogin();

// Handle AJAX requests for real-time updates
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'recent_scans':
            $recent_scans = $db->fetchAll("
                SELECT al.*, au.full_name, e.item_name, e.barcode, e.condition_status, e.location
                FROM admin_logs al
                JOIN admin_users au ON al.admin_id = au.id
                LEFT JOIN equipment e ON al.record_id = e.id
                WHERE al.action IN ('Track Equipment', 'Quick Scan', 'Update Equipment')
                ORDER BY al.created_at DESC
                LIMIT 10
            ");
            echo json_encode($recent_scans);
            break;
            
        case 'stats':
            $stats = getEquipmentStats();
            $active_scanners = $db->fetch("
                SELECT COUNT(DISTINCT admin_id) as count 
                FROM admin_logs 
                WHERE action IN ('Track Equipment', 'Quick Scan') 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ")['count'];
            
            $stats['active_scanners'] = $active_scanners;
            echo json_encode($stats);
            break;
            
        case 'equipment_status':
            $equipment = $db->fetchAll("
                SELECT e.*, 
                       (SELECT MAX(al.created_at) 
                        FROM admin_logs al 
                        WHERE al.record_id = e.id 
                        AND al.action IN ('Track Equipment', 'Quick Scan')
                       ) as last_scanned
                FROM equipment e
                ORDER BY last_scanned DESC
                LIMIT 20
            ");
            echo json_encode($equipment);
            break;
    }
    exit;
}

// Get initial data
$stats = getEquipmentStats();
$recent_scans = $db->fetchAll("
    SELECT al.*, au.full_name, e.item_name, e.barcode, e.condition_status, e.location
    FROM admin_logs al
    JOIN admin_users au ON al.admin_id = au.id
    LEFT JOIN equipment e ON al.record_id = e.id
    WHERE al.action IN ('Track Equipment', 'Quick Scan', 'Update Equipment')
    ORDER BY al.created_at DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Dashboard - Inventory System</title>
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
            transition: all 0.3s ease;
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
        .live-indicator {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .stat-card-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        .stat-card-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        .scan-item {
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .scan-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .scan-item.new-scan {
            animation: slideIn 0.5s ease-out;
            border-left-color: #28a745;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        .equipment-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .equipment-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        .equipment-card.recently-scanned {
            border-color: #28a745;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.3);
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-good { background-color: #28a745; }
        .status-fair { background-color: #ffc107; }
        .status-poor { background-color: #fd7e14; }
        .status-damaged { background-color: #dc3545; }
        .status-lost { background-color: #6c757d; }
        .refresh-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
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
                            <a class="nav-link" href="track_equipment.php">
                                <i class="fas fa-qrcode me-2"></i>Track Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="realtime_dashboard.php">
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
                        <h1 class="h3 mb-0">
                            <i class="fas fa-broadcast-tower me-2"></i>Real-Time Equipment Dashboard
                        </h1>
                        <div class="d-flex align-items-center">
                            <div class="badge bg-success fs-6 me-3 live-indicator">
                                <i class="fas fa-circle me-1"></i>LIVE
                            </div>
                            <button class="btn refresh-btn" onclick="forceRefresh()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Real-time Statistics -->
                    <div class="row mb-4" id="statsContainer">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-boxes fa-2x mb-2"></i>
                                    <h3 id="totalEquipment"><?= $stats['total'] ?></h3>
                                    <p class="mb-0">Total Equipment</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h3 id="goodCondition"><?= $stats['good'] ?></h3>
                                    <p class="mb-0">Good Condition</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h3 id="activeScanners">0</h3>
                                    <p class="mb-0">Active Scanners</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                    <h3 id="needsAttention"><?= $stats['damaged'] + $stats['lost'] ?></h3>
                                    <p class="mb-0">Needs Attention</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Live Activity Feed -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-rss me-2"></i>Live Activity Feed
                                    </h5>
                                    <small class="text-muted">Last updated: <span id="lastUpdate">Now</span></small>
                                </div>
                                <div class="card-body p-0">
                                    <div id="activityFeed" style="max-height: 400px; overflow-y: auto;">
                                        <!-- Activity items will be populated here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Equipment Status Map -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-map-marked-alt me-2"></i>Equipment Status Overview
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="equipmentGrid" class="equipment-grid">
                                        <!-- Equipment status cards will be populated here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2"></i>Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="track_equipment.php" class="btn btn-primary w-100">
                                                <i class="fas fa-qrcode me-2"></i>Scan Equipment
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="add_equipment.php" class="btn btn-success w-100">
                                                <i class="fas fa-plus me-2"></i>Add Equipment
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="equipment.php" class="btn btn-info w-100">
                                                <i class="fas fa-list me-2"></i>View All Equipment
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="reports.php" class="btn btn-warning w-100">
                                                <i class="fas fa-chart-bar me-2"></i>Generate Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let lastUpdateTime = Date.now();
        let refreshInterval;

        // Initialize real-time updates
        document.addEventListener('DOMContentLoaded', function() {
            startRealTimeUpdates();
            loadInitialData();
        });

        function startRealTimeUpdates() {
            // Update every 3 seconds
            refreshInterval = setInterval(() => {
                updateStats();
                updateActivityFeed();
                updateEquipmentStatus();
                updateLastUpdateTime();
            }, 3000);
        }

        function updateStats() {
            fetch('?ajax=stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalEquipment').textContent = data.total;
                    document.getElementById('goodCondition').textContent = data.good;
                    document.getElementById('activeScanners').textContent = data.active_scanners;
                    document.getElementById('needsAttention').textContent = data.damaged + data.lost;
                })
                .catch(error => console.error('Error updating stats:', error));
        }

        function updateActivityFeed() {
            fetch('?ajax=recent_scans')
                .then(response => response.json())
                .then(data => {
                    const feed = document.getElementById('activityFeed');
                    const currentItems = feed.children.length;
                    
                    // Clear and rebuild feed
                    feed.innerHTML = '';
                    
                    data.forEach((scan, index) => {
                        const isNew = index < (data.length - currentItems) && currentItems > 0;
                        const item = createActivityItem(scan, isNew);
                        feed.appendChild(item);
                    });
                })
                .catch(error => console.error('Error updating activity feed:', error));
        }

        function updateEquipmentStatus() {
            fetch('?ajax=equipment_status')
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('equipmentGrid');
                    grid.innerHTML = '';
                    
                    data.forEach(equipment => {
                        const card = createEquipmentCard(equipment);
                        grid.appendChild(card);
                    });
                })
                .catch(error => console.error('Error updating equipment status:', error));
        }

        function createActivityItem(scan, isNew = false) {
            const div = document.createElement('div');
            div.className = `scan-item p-3 border-bottom ${isNew ? 'new-scan' : ''}`;
            
            const statusClass = getStatusClass(scan.condition_status);
            const actionIcon = getActionIcon(scan.action);
            
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <i class="${actionIcon} me-2 text-primary"></i>
                            <strong>${scan.item_name || 'Equipment'}</strong>
                            ${scan.condition_status ? `<span class="status-indicator ${statusClass} ms-2"></span>` : ''}
                        </div>
                        <div class="small text-muted">
                            <code>${scan.barcode}</code> - ${scan.action} by ${scan.full_name}
                            ${scan.location ? `<br><i class="fas fa-map-marker-alt me-1"></i>${scan.location}` : ''}
                        </div>
                    </div>
                    <small class="text-muted">${formatTime(scan.created_at)}</small>
                </div>
            `;
            
            return div;
        }

        function createEquipmentCard(equipment) {
            const div = document.createElement('div');
            const isRecentlyScanned = equipment.last_scanned && 
                (Date.now() - new Date(equipment.last_scanned).getTime()) < 300000; // 5 minutes
            
            div.className = `equipment-card ${isRecentlyScanned ? 'recently-scanned' : ''}`;
            
            const statusClass = getStatusClass(equipment.condition_status);
            
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">${equipment.item_name}</h6>
                    <span class="status-indicator ${statusClass}"></span>
                </div>
                <div class="small text-muted mb-2">
                    <div><code>${equipment.barcode}</code></div>
                    <div><i class="fas fa-tag me-1"></i>${equipment.category}</div>
                    ${equipment.location ? `<div><i class="fas fa-map-marker-alt me-1"></i>${equipment.location}</div>` : ''}
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-${getStatusBadgeClass(equipment.condition_status)}">${equipment.condition_status}</span>
                    ${equipment.last_scanned ? 
                        `<small class="text-success">Scanned ${formatTimeAgo(equipment.last_scanned)}</small>` : 
                        '<small class="text-muted">Not scanned</small>'
                    }
                </div>
            `;
            
            return div;
        }

        function getStatusClass(status) {
            const classes = {
                'Good': 'status-good',
                'Fair': 'status-fair',
                'Poor': 'status-poor',
                'Damaged': 'status-damaged',
                'Lost': 'status-lost'
            };
            return classes[status] || 'status-good';
        }

        function getStatusBadgeClass(status) {
            const classes = {
                'Good': 'success',
                'Fair': 'warning',
                'Poor': 'danger',
                'Damaged': 'danger',
                'Lost': 'dark'
            };
            return classes[status] || 'secondary';
        }

        function getActionIcon(action) {
            const icons = {
                'Track Equipment': 'fas fa-search',
                'Quick Scan': 'fas fa-qrcode',
                'Update Equipment': 'fas fa-edit',
                'Add Equipment': 'fas fa-plus',
                'Delete Equipment': 'fas fa-trash'
            };
            return icons[action] || 'fas fa-circle';
        }

        function formatTime(datetime) {
            const date = new Date(datetime);
            return date.toLocaleTimeString();
        }

        function formatTimeAgo(datetime) {
            const now = new Date();
            const past = new Date(datetime);
            const diffMs = now - past;
            const diffMins = Math.floor(diffMs / 60000);
            
            if (diffMins < 1) return 'just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            
            const diffHours = Math.floor(diffMins / 60);
            if (diffHours < 24) return `${diffHours}h ago`;
            
            const diffDays = Math.floor(diffHours / 24);
            return `${diffDays}d ago`;
        }

        function updateLastUpdateTime() {
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        }

        function loadInitialData() {
            updateStats();
            updateActivityFeed();
            updateEquipmentStatus();
        }

        function forceRefresh() {
            const btn = event.target.closest('button');
            const icon = btn.querySelector('i');
            
            icon.classList.add('fa-spin');
            btn.disabled = true;
            
            loadInitialData();
            
            setTimeout(() => {
                icon.classList.remove('fa-spin');
                btn.disabled = false;
            }, 1000);
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>