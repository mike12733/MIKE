<?php
require_once 'includes/auth.php';

// Require login and restrict to students/alumni
$auth->requireStudentOrAlumni();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get user's document requests
$requests = $db->fetchAll(
    "SELECT dr.*, dt.name as document_type_name, dt.fee 
     FROM document_requests dr 
     JOIN document_types dt ON dr.document_type_id = dt.id 
     WHERE dr.user_id = ? 
     ORDER BY dr.request_date DESC 
     LIMIT 10",
    [$user['id']]
);

// Get statistics
$stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_requests,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
        SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_requests,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests
     FROM document_requests 
     WHERE user_id = ?",
    [$user['id']]
);

// Get recent notifications
$notifications = $db->fetchAll(
    "SELECT * FROM notifications 
     WHERE user_id = ? 
     ORDER BY sent_at DESC 
     LIMIT 5",
    [$user['id']]
);

// Get available document types
$documentTypes = $db->fetchAll(
    "SELECT * FROM document_types WHERE is_active = 1 ORDER BY name"
);

$unreadNotifications = getUnreadNotificationsCount($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>
                <?php echo SITE_NAME; ?>
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
                        <?php if (empty($notifications)): ?>
                            <li><span class="dropdown-item-text">No notifications</span></li>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <li>
                                    <a class="dropdown-item <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>" href="#">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo $notification['title']; ?></h6>
                                            <small><?php echo formatDateTime($notification['sent_at']); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo $notification['message']; ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo $user['full_name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
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
            <div class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="request_document.php">
                            <i class="fas fa-file-alt me-2"></i>Request Document
                        </a>
                        <a class="nav-link" href="my_requests.php">
                            <i class="fas fa-list me-2"></i>My Requests
                        </a>
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </a>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i>Profile
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
                        <p>Manage your document requests and track their status here.</p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--primary-color);">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['total_requests']; ?></div>
                                <div class="stats-label">Total Requests</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--warning-color);">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['pending_requests']; ?></div>
                                <div class="stats-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--info-color);">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['processing_requests']; ?></div>
                                <div class="stats-label">Processing</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--success-color);">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['approved_requests']; ?></div>
                                <div class="stats-label">Approved</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--primary-color);">
                                    <i class="fas fa-hand-holding"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['ready_requests']; ?></div>
                                <div class="stats-label">Ready</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: var(--secondary-color);">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['completed_requests']; ?></div>
                                <div class="stats-label">Completed</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="request_document.php" class="btn btn-primary">
                                            <i class="fas fa-file-alt me-2"></i>Request New Document
                                        </a>
                                        <a href="my_requests.php" class="btn btn-outline-primary">
                                            <i class="fas fa-list me-2"></i>View All Requests
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Available Documents</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach (array_slice($documentTypes, 0, 4) as $docType): ?>
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-alt text-primary me-2"></i>
                                                    <span class="small"><?php echo $docType['name']; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($documentTypes) > 4): ?>
                                        <div class="text-center mt-2">
                                            <a href="request_document.php" class="btn btn-sm btn-outline-primary">
                                                View All (<?php echo count($documentTypes); ?>)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Requests -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Requests</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($requests)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No requests yet</h5>
                                    <p class="text-muted">Start by requesting your first document</p>
                                    <a href="request_document.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Request Document
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request #</th>
                                                <th>Document Type</th>
                                                <th>Purpose</th>
                                                <th>Status</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $request['request_number']; ?></strong>
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
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="my_requests.php" class="btn btn-outline-primary">
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
</body>
</html>