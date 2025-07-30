<?php
require_once 'includes/functions.php';
requireLogin();

// Get comprehensive statistics
$stats = getEquipmentStats();

// Get equipment by category
$category_stats = $db->fetchAll("
    SELECT category, 
           COUNT(*) as item_count, 
           SUM(quantity) as total_quantity,
           AVG(purchase_price) as avg_price
    FROM equipment 
    GROUP BY category 
    ORDER BY total_quantity DESC
");

// Get equipment by condition
$condition_stats = $db->fetchAll("
    SELECT condition_status, 
           COUNT(*) as item_count, 
           SUM(quantity) as total_quantity
    FROM equipment 
    GROUP BY condition_status 
    ORDER BY total_quantity DESC
");

// Get monthly equipment additions
$monthly_additions = $db->fetchAll("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           COUNT(*) as items_added,
           SUM(quantity) as total_quantity
    FROM equipment 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

// Get top expensive equipment
$expensive_equipment = $db->fetchAll("
    SELECT item_name, category, purchase_price, quantity, barcode
    FROM equipment 
    WHERE purchase_price > 0
    ORDER BY purchase_price DESC 
    LIMIT 10
");

// Get equipment by location
$location_stats = $db->fetchAll("
    SELECT location, 
           COUNT(*) as item_count, 
           SUM(quantity) as total_quantity
    FROM equipment 
    WHERE location IS NOT NULL AND location != ''
    GROUP BY location 
    ORDER BY total_quantity DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Inventory Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            transform: translateY(-5px);
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
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }
        .stat-card-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge {
            font-size: 0.75em;
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
                            <a class="nav-link" href="realtime_dashboard.php">
                                <i class="fas fa-broadcast-tower me-2"></i>Live Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="reports.php">
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
                        <h1 class="h3 mb-0">Reports & Analytics</h1>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Report
                        </button>
                    </div>

                    <!-- Summary Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-boxes fa-2x mb-2"></i>
                                    <h3><?= $stats['total'] ?></h3>
                                    <p class="mb-0">Total Equipment</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h3><?= $stats['good'] ?></h3>
                                    <p class="mb-0">Good Condition</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                    <h3><?= $stats['damaged'] ?></h3>
                                    <p class="mb-0">Damaged</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <h3><?= $stats['lost'] ?></h3>
                                    <p class="mb-0">Lost</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <!-- Equipment by Category Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>Equipment by Category
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Equipment by Condition Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-doughnut me-2"></i>Equipment by Condition
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="conditionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Additions Chart -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Monthly Equipment Additions (Last 12 Months)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="monthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Category Statistics -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tags me-2"></i>Category Statistics
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($category_stats)): ?>
                                        <div class="text-center p-4">
                                            <p class="text-muted">No categories found.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>Items</th>
                                                        <th>Total Qty</th>
                                                        <th>Avg Price</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($category_stats as $cat): ?>
                                                        <tr>
                                                            <td class="fw-bold"><?= htmlspecialchars($cat['category']) ?></td>
                                                            <td><span class="badge bg-info"><?= $cat['item_count'] ?></span></td>
                                                            <td><span class="badge bg-primary"><?= $cat['total_quantity'] ?></span></td>
                                                            <td>₱<?= number_format($cat['avg_price'] ?? 0, 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Most Expensive Equipment -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-dollar-sign me-2"></i>Most Expensive Equipment
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($expensive_equipment)): ?>
                                        <div class="text-center p-4">
                                            <p class="text-muted">No equipment with prices found.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item Name</th>
                                                        <th>Category</th>
                                                        <th>Price</th>
                                                        <th>Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($expensive_equipment as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></div>
                                                                <small class="text-muted"><?= $item['barcode'] ?></small>
                                                            </td>
                                                            <td><?= htmlspecialchars($item['category']) ?></td>
                                                            <td class="fw-bold text-success">₱<?= number_format($item['purchase_price'], 2) ?></td>
                                                            <td><span class="badge bg-info"><?= $item['quantity'] ?></span></td>
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

                    <!-- Location Statistics -->
                    <?php if (!empty($location_stats)): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>Equipment by Location
                                        </h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Location</th>
                                                        <th>Number of Items</th>
                                                        <th>Total Quantity</th>
                                                        <th>Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($location_stats as $location): ?>
                                                        <tr>
                                                            <td class="fw-bold"><?= htmlspecialchars($location['location']) ?></td>
                                                            <td><span class="badge bg-info"><?= $location['item_count'] ?></span></td>
                                                            <td><span class="badge bg-primary"><?= $location['total_quantity'] ?></span></td>
                                                            <td>
                                                                <?php $percentage = ($location['total_quantity'] / $stats['total']) * 100; ?>
                                                                <div class="progress" style="height: 20px;">
                                                                    <div class="progress-bar" role="progressbar" 
                                                                         style="width: <?= $percentage ?>%" 
                                                                         aria-valuenow="<?= $percentage ?>" 
                                                                         aria-valuemin="0" aria-valuemax="100">
                                                                        <?= number_format($percentage, 1) ?>%
                                                                    </div>
                                                                </div>
                                                            </td>
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($category_stats, 'category')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($category_stats, 'total_quantity')) ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Condition Chart
        const conditionCtx = document.getElementById('conditionChart').getContext('2d');
        new Chart(conditionCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($condition_stats, 'condition_status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($condition_stats, 'total_quantity')) ?>,
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d', '#17a2b8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_reverse(array_column($monthly_additions, 'month'))) ?>,
                datasets: [{
                    label: 'Items Added',
                    data: <?= json_encode(array_reverse(array_column($monthly_additions, 'items_added'))) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>