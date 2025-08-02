<?php
session_start();
require_once '../classes/User.php';
require_once '../classes/DocumentRequest.php';

$user = new User();
$docRequest = new DocumentRequest();

// Check if user is logged in and is admin
if (!$user->isLoggedIn()) {
    header('Location: ../lnhs_index.php');
    exit();
}

$currentUser = $user->getCurrentUser();

if ($currentUser['user_type'] !== 'admin') {
    header('Location: ../user/dashboard.php');
    exit();
}

// Get statistics and data
$stats = $docRequest->getRequestStats();
$allRequests = $docRequest->getAllRequests(null, 20); // Latest 20 requests
$documentTypes = $docRequest->getDocumentTypes();

// Get requests by status for tabs
$pendingRequests = $docRequest->getAllRequests('pending');
$processingRequests = $docRequest->getAllRequests('processing');
$recentUsers = $user->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LNHS Documents Request Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .main-content {
            padding: 30px 0;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .stats-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending { background: #ffeaa7; color: #2d3436; }
        .status-processing { background: #74b9ff; color: white; }
        .status-approved { background: #00b894; color: white; }
        .status-denied { background: #e17055; color: white; }
        .status-ready_for_pickup { background: #a29bfe; color: white; }
        .status-completed { background: #00cec9; color: white; }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .nav-tabs {
            border: none;
            margin-bottom: 20px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-radius: 10px;
            margin-right: 10px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link.active {
            background: #667eea;
            color: white;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-graduation-cap text-primary"></i>
                LNHS Admin Portal
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield"></i>
                        <?= htmlspecialchars($currentUser['first_name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="fas fa-user-edit"></i> Profile
                        </a></li>
                        <li><a class="dropdown-item" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $stats['total'] ?? 0 ?></div>
                    <div><i class="fas fa-file-alt"></i> Total Requests</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $stats['pending'] ?? 0 ?></div>
                    <div><i class="fas fa-clock"></i> Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $stats['processing'] ?? 0 ?></div>
                    <div><i class="fas fa-cog"></i> Processing</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $stats['recent'] ?? 0 ?></div>
                    <div><i class="fas fa-calendar"></i> This Month</div>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="adminTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                            <i class="fas fa-clock"></i> Pending Requests (<?= count($pendingRequests) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="processing-tab" data-bs-toggle="tab" data-bs-target="#processing" type="button" role="tab">
                            <i class="fas fa-cog"></i> Processing (<?= count($processingRequests) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-requests-tab" data-bs-toggle="tab" data-bs-target="#all-requests" type="button" role="tab">
                            <i class="fas fa-list"></i> All Requests
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                            <i class="fas fa-users"></i> Users
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="adminTabsContent">
                    <!-- Pending Requests Tab -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <?php if (count($pendingRequests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
                                            <th>Student</th>
                                            <th>Document Type</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingRequests as $request): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($request['request_number']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($request['user_name']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($request['student_id']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($request['document_type_name']) ?></td>
                                                <td><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $request['id'] ?>, 'processing')">
                                                        <i class="fas fa-play"></i> Process
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>No Pending Requests</h5>
                                <p class="text-muted">All requests have been processed!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Processing Requests Tab -->
                    <div class="tab-pane fade" id="processing" role="tabpanel">
                        <?php if (count($processingRequests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
                                            <th>Student</th>
                                            <th>Document Type</th>
                                            <th>Processing Since</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($processingRequests as $request): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($request['request_number']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($request['user_name']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($request['student_id']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($request['document_type_name']) ?></td>
                                                <td><?= date('M d, Y', strtotime($request['updated_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $request['id'] ?>, 'approved')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="updateStatus(<?= $request['id'] ?>, 'denied')">
                                                            <i class="fas fa-times"></i> Deny
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-cog fa-3x text-muted mb-3"></i>
                                <h5>No Requests Being Processed</h5>
                                <p class="text-muted">Check the pending tab for new requests to process.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- All Requests Tab -->
                    <div class="tab-pane fade" id="all-requests" role="tabpanel">
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="searchRequests" placeholder="Search requests...">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-control" id="filterStatus">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="approved">Approved</option>
                                        <option value="denied">Denied</option>
                                        <option value="ready_for_pickup">Ready for Pickup</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-primary" onclick="exportRequests()">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Student</th>
                                        <th>Document Type</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="requestsTableBody">
                                    <?php foreach ($allRequests as $request): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($request['request_number']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($request['user_name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($request['student_id']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($request['document_type_name']) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $request['status'] ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewRequest(<?= $request['id'] ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <?php if ($request['status'] !== 'completed'): ?>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="manageRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-edit"></i> Manage
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Users Tab -->
                    <div class="tab-pane fade" id="users" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Student ID</th>
                                        <th>Type</th>
                                        <th>Registered</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $userData): ?>
                                        <?php if ($userData['user_type'] !== 'admin'): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($userData['email']) ?></td>
                                                <td><?= htmlspecialchars($userData['student_id']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $userData['user_type'] === 'student' ? 'primary' : 'success' ?>">
                                                        <?= ucfirst($userData['user_type']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($userData['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $userData['is_active'] ? 'success' : 'danger' ?>">
                                                        <?= $userData['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div class="modal fade" id="requestDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-alt"></i> Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="requestDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Update Request Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="statusUpdateForm">
                    <div class="modal-body">
                        <input type="hidden" id="updateRequestId" name="request_id">
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">New Status</label>
                            <select class="form-control" id="newStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="approved">Approved</option>
                                <option value="denied">Denied</option>
                                <option value="ready_for_pickup">Ready for Pickup</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="adminNotes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="adminNotes" name="notes" rows="3" 
                                      placeholder="Add any notes about this status change..."></textarea>
                        </div>
                        <div class="mb-3" id="rejectionReasonDiv" style="display: none;">
                            <label for="rejectionReason" class="form-label">Rejection Reason</label>
                            <textarea class="form-control" id="rejectionReason" name="rejection_reason" rows="2" 
                                      placeholder="Please specify the reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide rejection reason field based on status
        document.getElementById('newStatus').addEventListener('change', function() {
            const rejectionDiv = document.getElementById('rejectionReasonDiv');
            if (this.value === 'denied') {
                rejectionDiv.style.display = 'block';
                document.getElementById('rejectionReason').required = true;
            } else {
                rejectionDiv.style.display = 'none';
                document.getElementById('rejectionReason').required = false;
            }
        });

        // View request details
        async function viewRequest(requestId) {
            try {
                const response = await fetch(`../user/request_details.php?id=${requestId}`);
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('requestDetailsContent').innerHTML = result.html;
                    new bootstrap.Modal(document.getElementById('requestDetailsModal')).show();
                } else {
                    alert('Error loading request details');
                }
            } catch (error) {
                alert('Failed to load request details');
            }
        }

        // Quick status update
        async function updateStatus(requestId, status) {
            if (confirm(`Are you sure you want to change status to "${status.replace('_', ' ')}"?`)) {
                try {
                    const response = await fetch('update_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            request_id: requestId,
                            status: status
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Status update failed. Please try again.');
                }
            }
        }

        // Manage request (detailed status update)
        function manageRequest(requestId) {
            document.getElementById('updateRequestId').value = requestId;
            new bootstrap.Modal(document.getElementById('statusUpdateModal')).show();
        }

        // Status update form submission
        document.getElementById('statusUpdateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Status update failed. Please try again.');
            }
        });

        // Export requests
        function exportRequests() {
            window.open('export_requests.php', '_blank');
        }

        // Search and filter functionality
        document.getElementById('searchRequests').addEventListener('input', filterRequests);
        document.getElementById('filterStatus').addEventListener('change', filterRequests);

        function filterRequests() {
            const searchTerm = document.getElementById('searchRequests').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#requestsTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const statusBadge = row.querySelector('.status-badge');
                const status = statusBadge ? statusBadge.className.match(/status-(\w+)/)[1] : '';
                
                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }
    </script>
</body>
</html>