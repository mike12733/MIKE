<?php
session_start();
require_once '../classes/User.php';
require_once '../classes/DocumentRequest.php';

$user = new User();
$docRequest = new DocumentRequest();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    header('Location: ../lnhs_index.php');
    exit();
}

$currentUser = $user->getCurrentUser();

// Redirect admin to admin dashboard
if ($currentUser['user_type'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit();
}

// Get user's requests
$userRequests = $docRequest->getRequestsByUser($currentUser['id']);
$documentTypes = $docRequest->getDocumentTypes();
$notifications = $docRequest->getUserNotifications($currentUser['id'], true); // Unread only
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LNHS Documents Request Portal</title>
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
        
        .notification-badge {
            background: #e17055;
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 0.7rem;
            position: absolute;
            top: -5px;
            right: -5px;
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
        
        .request-card {
            transition: transform 0.3s ease;
        }
        
        .request-card:hover {
            transform: translateY(-5px);
        }
        
        .welcome-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-graduation-cap text-primary"></i>
                LNHS Portal
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php if (count($notifications) > 0): ?>
                            <span class="notification-badge"><?= count($notifications) ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <li><a class="dropdown-item" href="#" onclick="markAsRead(<?= $notification['id'] ?>)">
                                    <strong><?= htmlspecialchars($notification['title']) ?></strong><br>
                                    <small><?= htmlspecialchars($notification['message']) ?></small>
                                </a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><span class="dropdown-item">No new notifications</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($currentUser['first_name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="fas fa-user-edit"></i> Profile
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
        <!-- Welcome Card -->
        <div class="card welcome-card">
            <div class="card-body text-center py-4">
                <h2 class="mb-3">
                    Welcome, <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>!
                </h2>
                <p class="mb-3">
                    <i class="fas fa-id-badge"></i> Student ID: <?= htmlspecialchars($currentUser['student_id']) ?>
                    <?php if ($currentUser['user_type'] === 'alumni'): ?>
                        | <i class="fas fa-graduation-cap"></i> Alumni (<?= $currentUser['year_graduated'] ?>)
                    <?php else: ?>
                        | <i class="fas fa-user-graduate"></i> Current Student
                    <?php endif; ?>
                </p>
                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#requestModal">
                    <i class="fas fa-plus"></i> New Document Request
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Request Status Summary -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Request Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $statusCounts = [];
                        foreach ($userRequests as $request) {
                            $status = $request['status'];
                            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                        }
                        ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Requests:</span>
                            <strong><?= count($userRequests) ?></strong>
                        </div>
                        <?php foreach ($statusCounts as $status => $count): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= ucfirst(str_replace('_', ' ', $status)) ?>:</span>
                                <span class="status-badge status-<?= $status ?>"><?= $count ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Requests -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-file-alt"></i> My Document Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($userRequests) > 0): ?>
                            <div class="row">
                                <?php foreach ($userRequests as $request): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card request-card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?= htmlspecialchars($request['document_type_name']) ?></h6>
                                                <p class="card-text">
                                                    <strong>Request #:</strong> <?= htmlspecialchars($request['request_number']) ?><br>
                                                    <strong>Purpose:</strong> <?= htmlspecialchars(substr($request['purpose'], 0, 50)) ?>...
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="status-badge status-<?= $request['status'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                                    </span>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </div>
                                                <small class="text-muted">
                                                    Submitted: <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <h5>No requests yet</h5>
                                <p class="text-muted">Click "New Document Request" to submit your first request.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> New Document Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="requestForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="documentType" class="form-label">Document Type *</label>
                            <select class="form-control" id="documentType" name="document_type_id" required>
                                <option value="">Select Document Type</option>
                                <?php foreach ($documentTypes as $docType): ?>
                                    <option value="<?= $docType['id'] ?>" 
                                            data-description="<?= htmlspecialchars($docType['description']) ?>"
                                            data-requirements="<?= htmlspecialchars($docType['requirements']) ?>"
                                            data-processing-days="<?= $docType['processing_days'] ?>">
                                        <?= htmlspecialchars($docType['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="documentInfo" class="alert alert-info" style="display: none;">
                            <h6>Document Information:</h6>
                            <p id="documentDescription"></p>
                            <p><strong>Requirements:</strong> <span id="documentRequirements"></span></p>
                            <p><strong>Processing Time:</strong> <span id="processingDays"></span> working days</p>
                        </div>

                        <div class="mb-3">
                            <label for="purpose" class="form-label">Purpose of Request *</label>
                            <textarea class="form-control" id="purpose" name="purpose" rows="3" required 
                                      placeholder="Please specify the purpose of your document request..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="preferredDate" class="form-label">Preferred Release Date</label>
                            <input type="date" class="form-control" id="preferredDate" name="preferred_release_date" 
                                   min="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="attachments" class="form-label">Upload Requirements (Valid ID, etc.)</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" 
                                   multiple accept=".jpg,.jpeg,.png,.pdf" 
                                   onchange="validateFiles(this)">
                            <small class="text-muted">Accepted formats: JPG, PNG, PDF. Max 5MB per file.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                </form>
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

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Update Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="profileForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="profileFirstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="profileFirstName" name="first_name" 
                                       value="<?= htmlspecialchars($currentUser['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="profileLastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="profileLastName" name="last_name" 
                                       value="<?= htmlspecialchars($currentUser['last_name']) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="profileMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="profileMiddleName" name="middle_name" 
                                   value="<?= htmlspecialchars($currentUser['middle_name']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="profileContact" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="profileContact" name="contact_number" 
                                   value="<?= htmlspecialchars($currentUser['contact_number']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="profileCourse" class="form-label">Course/Program</label>
                            <input type="text" class="form-control" id="profileCourse" name="course" 
                                   value="<?= htmlspecialchars($currentUser['course']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="profileAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="profileAddress" name="address" rows="2"><?= htmlspecialchars($currentUser['address']) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show document information when type is selected
        document.getElementById('documentType').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('documentInfo');
            
            if (option.value) {
                document.getElementById('documentDescription').textContent = option.dataset.description;
                document.getElementById('documentRequirements').textContent = option.dataset.requirements;
                document.getElementById('processingDays').textContent = option.dataset.processingDays;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });

        // Validate file uploads
        function validateFiles(input) {
            const files = input.files;
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            
            for (let file of files) {
                if (file.size > maxSize) {
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                    input.value = '';
                    return false;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert(`File ${file.name} has invalid format. Only JPG, PNG, and PDF are allowed.`);
                    input.value = '';
                    return false;
                }
            }
        }

        // Submit new request
        document.getElementById('requestForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('request_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Request submitted successfully! Request #: ' + result.request_number);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Request submission failed. Please try again.');
            }
        });

        // Update profile
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('profile_update.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Profile update failed. Please try again.');
            }
        });

        // View request details
        async function viewRequest(requestId) {
            try {
                const response = await fetch(`request_details.php?id=${requestId}`);
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

        // Mark notification as read
        async function markAsRead(notificationId) {
            try {
                await fetch('mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ notification_id: notificationId })
                });
                location.reload();
            } catch (error) {
                console.error('Failed to mark notification as read');
            }
        }
    </script>
</body>
</html>