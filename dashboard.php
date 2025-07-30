<?php
require_once 'includes/functions.php';
requireLogin();

// Get equipment statistics
$stats = getEquipmentStats();

// Get recent equipment additions
$recent_equipment = $db->fetchAll("SELECT * FROM equipment ORDER BY created_at DESC LIMIT 5");

// Get recent admin logs
$recent_logs = $db->fetchAll("
    SELECT al.*, au.full_name 
    FROM admin_logs al 
    JOIN admin_users au ON al.admin_id = au.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Tracking System</title>
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
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
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
                            <a class="nav-link active" href="dashboard.php">
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
                        <h1 class="h3 mb-0">Dashboard</h1>
                        <small class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</small>
                    </div>

                    <!-- Statistics Cards -->
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

                    <div class="row">
                        <!-- Recent Equipment -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-clock me-2"></i>Recent Equipment
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_equipment)): ?>
                                        <p class="text-muted">No equipment added yet.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Item Name</th>
                                                        <th>Category</th>
                                                        <th>Condition</th>
                                                        <th>Barcode</th>
                                                        <th>Date Added</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_equipment as $item): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                                                            <td><?= htmlspecialchars($item['category']) ?></td>
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
                                                            <td><code><?= $item['barcode'] ?></code></td>
                                                            <td><?= formatDate($item['created_at']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history me-2"></i>Recent Activity
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_logs)): ?>
                                        <p class="text-muted">No recent activity.</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_logs as $log): ?>
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="flex-shrink-0">
                                                    <i class="fas fa-circle text-primary" style="font-size: 0.5em;"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    <div class="fw-bold"><?= htmlspecialchars($log['action']) ?></div>
                                                    <small class="text-muted">
                                                        by <?= htmlspecialchars($log['full_name']) ?><br>
                                                        <?= formatDateTime($log['created_at']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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