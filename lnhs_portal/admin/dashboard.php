<?php
require_once '../includes/auth.php';

// Require admin access
$auth->requireAdmin();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get statistics
$stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_requests,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
        SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_requests,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_requests
     FROM document_requests"
);

// Get user statistics
$userStats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN user_type = 'student' THEN 1 ELSE 0 END) as students,
        SUM(CASE WHEN user_type = 'alumni' THEN 1 ELSE 0 END) as alumni,
        SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admins
     FROM users WHERE is_active = 1"
);

// Get recent requests
$recentRequests = $db->fetchAll(
    "SELECT dr.*, dt.name as document_type_name, u.full_name, u.email 
     FROM document_requests dr 
     JOIN document_types dt ON dr.document_type_id = dt.id 
     JOIN users u ON dr.user_id = u.id 
     ORDER BY dr.request_date DESC 
     LIMIT 10"
);

// Get pending requests count
$pendingCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM document_requests WHERE status = 'pending'"
)['count'];

// Get recent activities
$recentActivities = $db->fetchAll(
    "SELECT al.*, u.full_name 
     FROM admin_logs al 
     JOIN users u ON al.admin_id = u.id 
     ORDER BY al.created_at DESC 
     LIMIT 10"
);

// Get monthly request trends (last 6 months)
$monthlyTrends = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(request_date, '%Y-%m') as month,
        COUNT(*) as count
     FROM document_requests 
     WHERE request_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(request_date, '%Y-%m')
     ORDER BY month DESC"
);

$unreadNotifications = getUnreadNotificationsCount($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .sidebar {
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            min-height: calc(100vh - 76px);
        }
        
        .sidebar .nav-link {
            color: #495057;
            padding: 12px 20px;
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            transform: translateX(5px);
        }
        
        .main-content {
            padding: 30px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 20px;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #6c757d;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .badge {
            border-radius: 20px;
            padding: 8px 15px;
            font-weight: 600;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .welcome-section h2 {
            margin: 0;
            font-weight: 700;
        }
        
        .welcome-section p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .activity-item {
            padding: 15px;
            border-left: 3px solid var(--primary-color);
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                <?php echo SITE_NAME; ?> - Admin
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell me-1"></i>
                        Notifications
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="notification-badge"><?php echo $unreadNotifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text">No notifications</span></li>
                    </ul>
                </div>
                
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo $user['full_name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="requests.php">
                            <i class="fas fa-file-alt me-2"></i>Manage Requests
                            <?php if ($pendingCount > 0): ?>
                                <span class="badge bg-warning ms-2"><?php echo $pendingCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a class="nav-link" href="document_types.php">
                            <i class="fas fa-file-text me-2"></i>Document Types
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history me-2"></i>Activity Logs
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Welcome Section -->
                    <div class="welcome-section">
                        <h2>Welcome back, <?php echo $user['full_name']; ?>!</h2>
                        <p>Manage document requests and monitor system activities from your admin dashboard.</p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--primary-color);">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['total_requests']; ?></div>
                                <div class="stats-label">Total Requests</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--warning-color);">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['pending_requests']; ?></div>
                                <div class="stats-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--info-color);">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['processing_requests']; ?></div>
                                <div class="stats-label">Processing</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--success-color);">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['completed_requests']; ?></div>
                                <div class="stats-label">Completed</div>
                            </div>
                        </div>
                    </div>

                    <!-- User Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--info-color);">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stats-number"><?php echo $userStats['total_users']; ?></div>
                                <div class="stats-label">Total Users</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--primary-color);">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="stats-number"><?php echo $userStats['students']; ?></div>
                                <div class="stats-label">Students</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--secondary-color);">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="stats-number"><?php echo $userStats['alumni']; ?></div>
                                <div class="stats-label">Alumni</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--danger-color);">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="stats-number"><?php echo $userStats['admins']; ?></div>
                                <div class="stats-label">Admins</div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Recent Data -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Request Trends</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="monthlyChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Activities</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recentActivities)): ?>
                                        <p class="text-muted text-center">No recent activities</p>
                                    <?php else: ?>
                                        <?php foreach ($recentActivities as $activity): ?>
                                            <div class="activity-item">
                                                <div class="fw-bold"><?php echo $activity['action']; ?></div>
                                                <div class="small"><?php echo $activity['full_name']; ?></div>
                                                <div class="activity-time"><?php echo formatDateTime($activity['created_at']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Requests -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Requests</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentRequests)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No requests yet</h5>
                                    <p class="text-muted">No document requests have been submitted</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request #</th>
                                                <th>User</th>
                                                <th>Document Type</th>
                                                <th>Purpose</th>
                                                <th>Status</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentRequests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $request['request_number']; ?></strong>
                                                    </td>
                                                    <td>
                                                        <div><?php echo $request['full_name']; ?></div>
                                                        <small class="text-muted"><?php echo $request['email']; ?></small>
                                                    </td>
                                                    <td><?php echo $request['document_type_name']; ?></td>
                                                    <td>
                                                        <?php echo strlen($request['purpose']) > 50 ? 
                                                            substr($request['purpose'], 0, 50) . '...' : 
                                                            $request['purpose']; ?>
                                                    </td>
                                                    <td>
                                                        <span class="<?php echo getStatusBadgeClass($request['status']); ?>">
                                                            <?php echo getStatusText($request['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($request['request_date']); ?></td>
                                                    <td>
                                                        <a href="view_request.php?id=<?php echo $request['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit_request.php?id=<?php echo $request['id']; ?>" 
                                                           class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="requests.php" class="btn btn-outline-primary">
                                        View All Requests
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    <script>
        // Monthly trends chart
        const monthlyData = <?php echo json_encode($monthlyTrends); ?>;
        const labels = monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }).reverse();
        const data = monthlyData.map(item => item.count).reverse();
        
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Document Requests',
                    data: data,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>