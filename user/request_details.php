<?php
session_start();
header('Content-Type: application/json');
require_once '../classes/User.php';
require_once '../classes/DocumentRequest.php';

$user = new User();
$docRequest = new DocumentRequest();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

$requestId = $_GET['id'];
$currentUser = $user->getCurrentUser();

// Get request details
$request = $docRequest->getRequestById($requestId);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit();
}

// Check if user owns this request (or is admin)
if ($request['user_id'] != $currentUser['id'] && $currentUser['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Get attachments and status history
$attachments = $docRequest->getRequestAttachments($requestId);
$statusHistory = $docRequest->getRequestStatusHistory($requestId);

// Generate HTML content
ob_start();
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-info-circle"></i> Request Information</h6>
        <table class="table table-borderless">
            <tr>
                <td><strong>Request Number:</strong></td>
                <td><?= htmlspecialchars($request['request_number']) ?></td>
            </tr>
            <tr>
                <td><strong>Document Type:</strong></td>
                <td><?= htmlspecialchars($request['document_type_name']) ?></td>
            </tr>
            <tr>
                <td><strong>Purpose:</strong></td>
                <td><?= htmlspecialchars($request['purpose']) ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <span class="status-badge status-<?= $request['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Submitted:</strong></td>
                <td><?= date('M d, Y g:i A', strtotime($request['created_at'])) ?></td>
            </tr>
            <?php if ($request['preferred_release_date']): ?>
            <tr>
                <td><strong>Preferred Release Date:</strong></td>
                <td><?= date('M d, Y', strtotime($request['preferred_release_date'])) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($request['admin_notes']): ?>
            <tr>
                <td><strong>Admin Notes:</strong></td>
                <td><?= htmlspecialchars($request['admin_notes']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($request['rejection_reason']): ?>
            <tr>
                <td><strong>Rejection Reason:</strong></td>
                <td class="text-danger"><?= htmlspecialchars($request['rejection_reason']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6><i class="fas fa-paperclip"></i> Attachments</h6>
        <?php if (count($attachments) > 0): ?>
            <div class="list-group">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file"></i>
                            <?= htmlspecialchars($attachment['file_name']) ?>
                            <br>
                            <small class="text-muted">
                                Uploaded: <?= date('M d, Y', strtotime($attachment['uploaded_at'])) ?>
                            </small>
                        </div>
                        <a href="download_attachment.php?id=<?= $attachment['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No attachments uploaded</p>
        <?php endif; ?>
    </div>
</div>

<hr>

<h6><i class="fas fa-history"></i> Status History</h6>
<div class="timeline">
    <?php foreach ($statusHistory as $history): ?>
        <div class="timeline-item mb-3">
            <div class="d-flex">
                <div class="flex-shrink-0">
                    <span class="status-badge status-<?= $history['new_status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $history['new_status'])) ?>
                    </span>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fw-bold"><?= htmlspecialchars($history['changed_by_name']) ?></div>
                    <div class="text-muted"><?= date('M d, Y g:i A', strtotime($history['created_at'])) ?></div>
                    <?php if ($history['notes']): ?>
                        <div class="mt-1"><?= htmlspecialchars($history['notes']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
$html = ob_get_clean();

echo json_encode(['success' => true, 'html' => $html]);
?>