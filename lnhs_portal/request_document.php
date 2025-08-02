<?php
require_once 'includes/auth.php';

// Require login and restrict to students/alumni
$auth->requireStudentOrAlumni();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

$error = '';
$success = '';

// Get available document types
$documentTypes = $db->fetchAll(
    "SELECT * FROM document_types WHERE is_active = 1 ORDER BY name"
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $documentTypeId = (int)$_POST['document_type_id'];
        $purpose = sanitizeInput($_POST['purpose']);
        $preferredReleaseDate = $_POST['preferred_release_date'];
        
        // Validate required fields
        if (empty($documentTypeId) || empty($purpose)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                $db->beginTransaction();
                
                // Generate request number
                $requestNumber = generateRequestNumber();
                
                // Insert document request
                $requestData = [
                    'request_number' => $requestNumber,
                    'user_id' => $user['id'],
                    'document_type_id' => $documentTypeId,
                    'purpose' => $purpose,
                    'preferred_release_date' => $preferredReleaseDate ?: null,
                    'status' => 'pending'
                ];
                
                $requestId = $db->insert('document_requests', $requestData);
                
                // Handle file uploads
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $allowedTypes = explode(',', getSystemSetting('allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx'));
                    $maxFileSize = (int)getSystemSetting('max_file_size', MAX_FILE_SIZE);
                    
                    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileName = $_FILES['attachments']['name'][$key];
                            $fileSize = $_FILES['attachments']['size'][$key];
                            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            
                            // Validate file type
                            if (!in_array($fileType, $allowedTypes)) {
                                throw new Exception("File type '$fileType' is not allowed.");
                            }
                            
                            // Validate file size
                            if ($fileSize > $maxFileSize) {
                                throw new Exception("File '$fileName' is too large. Maximum size is " . ($maxFileSize / 1024 / 1024) . "MB.");
                            }
                            
                            // Generate unique filename
                            $uniqueFileName = uniqid() . '_' . $fileName;
                            $filePath = $uploadDir . $uniqueFileName;
                            
                            if (move_uploaded_file($tmpName, $filePath)) {
                                // Insert attachment record
                                $attachmentData = [
                                    'request_id' => $requestId,
                                    'file_name' => $fileName,
                                    'file_path' => $filePath,
                                    'file_type' => $fileType,
                                    'file_size' => $fileSize
                                ];
                                
                                $db->insert('request_attachments', $attachmentData);
                            } else {
                                throw new Exception("Failed to upload file '$fileName'.");
                            }
                        }
                    }
                }
                
                // Add status history
                $statusData = [
                    'request_id' => $requestId,
                    'status' => 'pending',
                    'notes' => 'Request submitted',
                    'updated_by' => $user['id']
                ];
                
                $db->insert('request_status_history', $statusData);
                
                // Send notification
                sendNotification(
                    $user['id'],
                    'Document Request Submitted',
                    "Your request for document (Request #: $requestNumber) has been submitted successfully and is now pending review.",
                    'portal'
                );
                
                $db->commit();
                
                $success = "Document request submitted successfully! Your request number is: <strong>$requestNumber</strong>";
                
            } catch (Exception $e) {
                $db->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Document - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
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
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 20px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }
        
        .file-preview {
            margin-top: 20px;
        }
        
        .file-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .file-info {
            display: flex;
            align-items: center;
        }
        
        .file-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .remove-file {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .document-type-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .document-type-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .document-type-card.selected {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .document-type-card h6 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .document-type-card p {
            margin: 5px 0 0 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .document-type-card .fee {
            color: var(--success-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="request_document.php">
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
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Request Document</h4>
                                </div>
                                <div class="card-body">
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <?php echo $error; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($success): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <?php echo $success; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="" enctype="multipart/form-data" id="requestForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        
                                        <!-- Document Type Selection -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Select Document Type *</label>
                                            <div class="row">
                                                <?php foreach ($documentTypes as $docType): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="document-type-card" onclick="selectDocumentType(<?php echo $docType['id']; ?>)">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <h6><?php echo $docType['name']; ?></h6>
                                                                    <p><?php echo $docType['description']; ?></p>
                                                                    <p class="mb-0"><strong>Processing Time:</strong> <?php echo $docType['processing_days']; ?> days</p>
                                                                </div>
                                                                <div class="fee">₱<?php echo number_format($docType['fee'], 2); ?></div>
                                                            </div>
                                                            <?php if ($docType['requirements']): ?>
                                                                <div class="mt-2">
                                                                    <small class="text-muted">
                                                                        <strong>Requirements:</strong> <?php echo $docType['requirements']; ?>
                                                                    </small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <input type="hidden" name="document_type_id" id="document_type_id" required>
                                            <div class="invalid-feedback">Please select a document type.</div>
                                        </div>
                                        
                                        <!-- Purpose -->
                                        <div class="mb-4">
                                            <label for="purpose" class="form-label fw-bold">Purpose of Request *</label>
                                            <textarea class="form-control" id="purpose" name="purpose" rows="4" 
                                                      placeholder="Please specify the purpose for requesting this document..." required></textarea>
                                        </div>
                                        
                                        <!-- Preferred Release Date -->
                                        <div class="mb-4">
                                            <label for="preferred_release_date" class="form-label fw-bold">Preferred Release Date</label>
                                            <input type="date" class="form-control" id="preferred_release_date" name="preferred_release_date" 
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                            <div class="form-text">Leave blank if no specific date is required</div>
                                        </div>
                                        
                                        <!-- File Upload -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Upload Requirements (Optional)</label>
                                            <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                                <h5>Click to upload files</h5>
                                                <p class="text-muted mb-0">Drag and drop files here or click to browse</p>
                                                <small class="text-muted">
                                                    Allowed: JPG, PNG, PDF, DOC, DOCX (Max: 5MB each)
                                                </small>
                                            </div>
                                            <input type="file" id="fileInput" name="attachments[]" multiple 
                                                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" style="display: none;">
                                            
                                            <div class="file-preview" id="filePreview"></div>
                                        </div>
                                        
                                        <!-- Submit Button -->
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-paper-plane me-2"></i>Submit Request
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedFiles = [];
        
        function selectDocumentType(id) {
            // Remove previous selection
            document.querySelectorAll('.document-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            event.currentTarget.classList.add('selected');
            
            // Set hidden input value
            document.getElementById('document_type_id').value = id;
        }
        
        // File upload handling
        document.getElementById('fileInput').addEventListener('change', handleFileSelect);
        
        const uploadArea = document.querySelector('.file-upload-area');
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });
        
        function handleFileSelect(e) {
            const files = e.target.files;
            handleFiles(files);
        }
        
        function handleFiles(files) {
            Array.from(files).forEach(file => {
                if (file.size <= 5 * 1024 * 1024) { // 5MB limit
                    selectedFiles.push(file);
                    displayFile(file);
                } else {
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                }
            });
        }
        
        function displayFile(file) {
            const preview = document.getElementById('filePreview');
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <div class="file-info">
                    <div class="file-icon">
                        <i class="fas fa-file"></i>
                    </div>
                    <div>
                        <div class="fw-bold">${file.name}</div>
                        <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                    </div>
                </div>
                <button type="button" class="remove-file" onclick="removeFile('${file.name}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
            preview.appendChild(fileItem);
        }
        
        function removeFile(fileName) {
            selectedFiles = selectedFiles.filter(file => file.name !== fileName);
            updateFilePreview();
        }
        
        function updateFilePreview() {
            const preview = document.getElementById('filePreview');
            preview.innerHTML = '';
            selectedFiles.forEach(file => displayFile(file));
        }
        
        // Form validation
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            const documentTypeId = document.getElementById('document_type_id').value;
            const purpose = document.getElementById('purpose').value.trim();
            
            if (!documentTypeId) {
                e.preventDefault();
                alert('Please select a document type.');
                return false;
            }
            
            if (!purpose) {
                e.preventDefault();
                alert('Please specify the purpose of your request.');
                return false;
            }
        });
    </script>
</body>
</html>