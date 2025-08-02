<?php
require_once '../config/lnhs_database.php';

class DocumentRequest {
    private $db;
    
    public function __construct() {
        $this->db = new LNHSDatabase();
    }
    
    public function generateRequestNumber() {
        $prefix = 'LNHS-' . date('Y') . '-';
        $lastRequest = $this->db->fetch(
            "SELECT request_number FROM document_requests WHERE request_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        
        if ($lastRequest) {
            $lastNumber = intval(substr($lastRequest['request_number'], -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    public function createRequest($data) {
        try {
            $this->db->beginTransaction();
            
            $requestNumber = $this->generateRequestNumber();
            
            // Insert main request
            $sql = "INSERT INTO document_requests (user_id, document_type_id, purpose, preferred_release_date, request_number) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $params = [
                $data['user_id'],
                $data['document_type_id'],
                $data['purpose'],
                $data['preferred_release_date'] ?? null,
                $requestNumber
            ];
            
            $this->db->query($sql, $params);
            $requestId = $this->db->lastInsertId();
            
            // Add to status history
            $this->addStatusHistory($requestId, null, 'pending', $data['user_id'], 'Request submitted');
            
            // Create notification for user
            $this->createNotification(
                $data['user_id'],
                $requestId,
                'Request Submitted',
                "Your document request #{$requestNumber} has been submitted successfully.",
                'success'
            );
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Request submitted successfully',
                'request_id' => $requestId,
                'request_number' => $requestNumber
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Request submission failed: ' . $e->getMessage()];
        }
    }
    
    public function uploadAttachment($requestId, $file) {
        try {
            $uploadDir = '../uploads/requests/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $sql = "INSERT INTO request_attachments (request_id, file_name, file_path, file_type, file_size) 
                        VALUES (?, ?, ?, ?, ?)";
                
                $params = [
                    $requestId,
                    $file['name'],
                    $filePath,
                    $file['type'],
                    $file['size']
                ];
                
                $this->db->query($sql, $params);
                
                return ['success' => true, 'message' => 'File uploaded successfully'];
            } else {
                return ['success' => false, 'message' => 'File upload failed'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }
    
    public function getRequestsByUser($userId) {
        $sql = "SELECT dr.*, dt.name as document_type_name, dt.processing_days 
                FROM document_requests dr 
                JOIN document_types dt ON dr.document_type_id = dt.id 
                WHERE dr.user_id = ? 
                ORDER BY dr.created_at DESC";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    public function getRequestById($requestId) {
        $sql = "SELECT dr.*, dt.name as document_type_name, dt.description as document_description,
                       dt.requirements as document_requirements, dt.processing_days,
                       u.first_name, u.last_name, u.email, u.student_id, u.contact_number
                FROM document_requests dr 
                JOIN document_types dt ON dr.document_type_id = dt.id 
                JOIN users u ON dr.user_id = u.id
                WHERE dr.id = ?";
        
        return $this->db->fetch($sql, [$requestId]);
    }
    
    public function getRequestAttachments($requestId) {
        return $this->db->fetchAll(
            "SELECT * FROM request_attachments WHERE request_id = ? ORDER BY uploaded_at",
            [$requestId]
        );
    }
    
    public function updateRequestStatus($requestId, $newStatus, $adminId, $notes = null, $rejectionReason = null) {
        try {
            $this->db->beginTransaction();
            
            // Get current request
            $request = $this->getRequestById($requestId);
            $oldStatus = $request['status'];
            
            // Update request status
            $sql = "UPDATE document_requests SET status = ?, processed_by = ?, admin_notes = ?, rejection_reason = ?";
            $params = [$newStatus, $adminId, $notes, $rejectionReason];
            
            if ($newStatus === 'completed') {
                $sql .= ", completed_at = NOW()";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $requestId;
            
            $this->db->query($sql, $params);
            
            // Add to status history
            $this->addStatusHistory($requestId, $oldStatus, $newStatus, $adminId, $notes);
            
            // Create notification for user
            $statusMessages = [
                'processing' => 'Your request is now being processed.',
                'approved' => 'Your request has been approved.',
                'denied' => 'Your request has been denied. ' . ($rejectionReason ?? ''),
                'ready_for_pickup' => 'Your document is ready for pickup.',
                'completed' => 'Your request has been completed.'
            ];
            
            $this->createNotification(
                $request['user_id'],
                $requestId,
                'Request Status Updated',
                $statusMessages[$newStatus] ?? "Request status updated to {$newStatus}.",
                $newStatus === 'denied' ? 'error' : 'info'
            );
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Status updated successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Status update failed: ' . $e->getMessage()];
        }
    }
    
    public function getAllRequests($status = null, $limit = null, $offset = 0) {
        $sql = "SELECT dr.*, dt.name as document_type_name, 
                       CONCAT(u.first_name, ' ', u.last_name) as user_name,
                       u.student_id, u.email, u.contact_number
                FROM document_requests dr 
                JOIN document_types dt ON dr.document_type_id = dt.id 
                JOIN users u ON dr.user_id = u.id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE dr.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY dr.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getRequestStatusHistory($requestId) {
        $sql = "SELECT rsh.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                FROM request_status_history rsh
                JOIN users u ON rsh.changed_by = u.id
                WHERE rsh.request_id = ?
                ORDER BY rsh.created_at ASC";
        
        return $this->db->fetchAll($sql, [$requestId]);
    }
    
    public function getDocumentTypes() {
        return $this->db->fetchAll(
            "SELECT * FROM document_types WHERE is_active = 1 ORDER BY name"
        );
    }
    
    public function getRequestStats() {
        $stats = [];
        
        // Total requests
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM document_requests")['count'];
        
        // Requests by status
        $statusCounts = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM document_requests GROUP BY status"
        );
        
        foreach ($statusCounts as $status) {
            $stats[$status['status']] = $status['count'];
        }
        
        // Recent requests (last 30 days)
        $stats['recent'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM document_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )['count'];
        
        return $stats;
    }
    
    private function addStatusHistory($requestId, $oldStatus, $newStatus, $changedBy, $notes = null) {
        $sql = "INSERT INTO request_status_history (request_id, old_status, new_status, changed_by, notes) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [$requestId, $oldStatus, $newStatus, $changedBy, $notes];
        
        $this->db->query($sql, $params);
    }
    
    private function createNotification($userId, $requestId, $title, $message, $type = 'info') {
        $sql = "INSERT INTO notifications (user_id, request_id, title, message, type) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [$userId, $requestId, $title, $message, $type];
        
        $this->db->query($sql, $params);
    }
    
    public function getUserNotifications($userId, $unreadOnly = false) {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function markNotificationAsRead($notificationId) {
        $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE id = ?",
            [$notificationId]
        );
    }
}
?>