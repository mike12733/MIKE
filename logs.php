<?php
require_once 'includes/functions.php';
requireLogin();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Filters
$filter_admin = (int)($_GET['admin'] ?? 0);
$filter_action = sanitize($_GET['action'] ?? '');
$filter_date = sanitize($_GET['date'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if ($filter_admin) {
    $where_conditions[] = "al.admin_id = ?";
    $params[] = $filter_admin;
}

if ($filter_action) {
    $where_conditions[] = "al.action LIKE ?";
    $params[] = "%{$filter_action}%";
}

if ($filter_date) {
    $where_conditions[] = "DATE(al.created_at) = ?";
    $params[] = $filter_date;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM admin_logs al JOIN admin_users au ON al.admin_id = au.id {$where_clause}";
$total_logs = $db->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_logs / $per_page);

// Get logs
$sql = "SELECT al.*, au.full_name, au.email 
        FROM admin_logs al 
        JOIN admin_users au ON al.admin_id = au.id 
        {$where_clause}
        ORDER BY al.created_at DESC 
        LIMIT {$per_page} OFFSET {$offset}";

$logs = $db->fetchAll($sql, $params);

// Get all admins for filter dropdown
$admins = $db->fetchAll("SELECT id, full_name, email FROM admin_users ORDER BY full_name");

// Get unique actions for filter dropdown
$actions = $db->fetchAll("SELECT DISTINCT action FROM admin_logs ORDER BY action");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Inventory Tracking System</title>
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
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge {
            font-size: 0.75em;
        }
        .log-details {
            font-size: 0.9em;
            color: #6c757d;
        }
        .json-display {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.8em;
            max-height: 200px;
            overflow-y: auto;
        }
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-control, .form-select {
            border-radius: 10px;
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
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="logs.php">
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
                        <h1 class="h3 mb-0">Activity Logs</h1>
                        <div class="text-muted">
                            <small>Total: <?= number_format($total_logs) ?> logs</small>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="admin" class="form-label">Admin</label>
                                        <select class="form-select" id="admin" name="admin">
                                            <option value="">All Admins</option>
                                            <?php foreach ($admins as $admin): ?>
                                                <option value="<?= $admin['id'] ?>" <?= $filter_admin == $admin['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($admin['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="action" class="form-label">Action</label>
                                        <select class="form-select" id="action" name="action">
                                            <option value="">All Actions</option>
                                            <?php foreach ($actions as $action): ?>
                                                <option value="<?= htmlspecialchars($action['action']) ?>" <?= $filter_action == $action['action'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($action['action']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="btn-group w-100">
                                            <button type="submit" class="btn btn-light">
                                                <i class="fas fa-filter me-2"></i>Filter
                                            </button>
                                            <a href="logs.php" class="btn btn-outline-light">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Logs Table -->
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>System Activity
                                <?php if ($filter_admin || $filter_action || $filter_date): ?>
                                    <small class="text-muted">(Filtered)</small>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($logs)): ?>
                                <div class="text-center p-5">
                                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No activity logs found</h5>
                                    <p class="text-muted">No activities match your current filters.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Admin</th>
                                                <th>Action</th>
                                                <th>Details</th>
                                                <th>IP Address</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($logs as $log): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?= formatDateTime($log['created_at']) ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($log['full_name']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($log['email']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $action_class = [
                                                            'Login' => 'success',
                                                            'Logout' => 'secondary',
                                                            'Add Equipment' => 'primary',
                                                            'Update Equipment' => 'warning',
                                                            'Delete Equipment' => 'danger'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?= $action_class[$log['action']] ?? 'info' ?>">
                                                            <?= htmlspecialchars($log['action']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($log['table_name']): ?>
                                                            <div class="log-details">
                                                                <strong>Table:</strong> <?= htmlspecialchars($log['table_name']) ?><br>
                                                                <?php if ($log['record_id']): ?>
                                                                    <strong>Record ID:</strong> <?= $log['record_id'] ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <code><?= htmlspecialchars($log['ip_address']) ?></code>
                                                    </td>
                                                    <td>
                                                        <?php if ($log['old_values'] || $log['new_values']): ?>
                                                            <button class="btn btn-sm btn-outline-info" 
                                                                    onclick="showLogDetails(<?= htmlspecialchars(json_encode($log)) ?>)"
                                                                    title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="card-footer bg-transparent">
                                        <nav aria-label="Logs pagination">
                                            <ul class="pagination justify-content-center mb-0">
                                                <?php
                                                $query_params = http_build_query(array_filter([
                                                    'admin' => $filter_admin,
                                                    'action' => $filter_action,
                                                    'date' => $filter_date
                                                ]));
                                                $query_string = $query_params ? '&' . $query_params : '';
                                                ?>
                                                
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $query_string ?>">Previous</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $i ?><?= $query_string ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>

                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $query_string ?>">Next</a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Activity Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="logDetailsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLogDetails(log) {
            let content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2"></i>Admin Information</h6>
                        <p><strong>Name:</strong> ${log.full_name}<br>
                        <strong>Email:</strong> ${log.email}<br>
                        <strong>IP Address:</strong> ${log.ip_address}</p>
                        
                        <h6><i class="fas fa-clock me-2"></i>Activity Information</h6>
                        <p><strong>Action:</strong> ${log.action}<br>
                        <strong>Date & Time:</strong> ${new Date(log.created_at).toLocaleString()}<br>
                        <strong>Table:</strong> ${log.table_name || 'N/A'}<br>
                        <strong>Record ID:</strong> ${log.record_id || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-browser me-2"></i>User Agent</h6>
                        <p style="word-break: break-all; font-size: 0.9em;">${log.user_agent}</p>
                    </div>
                </div>
            `;

            if (log.old_values) {
                content += `
                    <h6><i class="fas fa-history me-2"></i>Old Values</h6>
                    <div class="json-display">${JSON.stringify(JSON.parse(log.old_values), null, 2)}</div>
                `;
            }

            if (log.new_values) {
                content += `
                    <h6><i class="fas fa-edit me-2"></i>New Values</h6>
                    <div class="json-display">${JSON.stringify(JSON.parse(log.new_values), null, 2)}</div>
                `;
            }

            document.getElementById('logDetailsContent').innerHTML = content;
            
            const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
            modal.show();
        }
    </script>
</body>
</html>