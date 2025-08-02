-- LNHS Documents Request Portal Database Schema
-- Created for LNHS (Lipa National High School) Documents Request System

-- Create database
CREATE DATABASE IF NOT EXISTS lnhs_documents_portal;
USE lnhs_documents_portal;

-- Users table (for students, alumni, and admin)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('student', 'alumni', 'admin') NOT NULL,
    contact_number VARCHAR(20),
    address TEXT,
    graduation_year INT,
    course VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Document types table
CREATE TABLE document_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    processing_days INT DEFAULT 3,
    requirements TEXT,
    fee DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Document requests table
CREATE TABLE document_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    document_type_id INT NOT NULL,
    purpose TEXT NOT NULL,
    preferred_release_date DATE,
    status ENUM('pending', 'processing', 'approved', 'denied', 'ready_for_pickup', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL,
    completed_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE CASCADE
);

-- Request attachments table
CREATE TABLE request_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE CASCADE
);

-- Request status history table
CREATE TABLE request_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    status ENUM('pending', 'processing', 'approved', 'denied', 'ready_for_pickup', 'completed') NOT NULL,
    notes TEXT,
    updated_by INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('email', 'sms', 'portal') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin activity logs table
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_affected VARCHAR(50),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default document types
INSERT INTO document_types (name, description, processing_days, requirements, fee) VALUES
('Certificate of Enrollment', 'Official certificate confirming student enrollment status', 2, 'Valid ID, Proof of payment', 50.00),
('Good Moral Certificate', 'Certificate attesting to student\'s good moral character', 3, 'Valid ID, Recommendation letter', 75.00),
('Transcript of Records', 'Complete academic record of the student', 5, 'Valid ID, Authorization letter, Payment receipt', 150.00),
('Certificate of Graduation', 'Certificate confirming completion of studies', 3, 'Valid ID, Diploma copy', 100.00),
('Certificate of Transfer', 'Certificate for students transferring to another school', 2, 'Valid ID, Transfer form', 50.00),
('Certificate of Completion', 'Certificate for completed courses or programs', 2, 'Valid ID, Course completion form', 75.00);

-- Insert default admin user
INSERT INTO users (student_id, email, password, full_name, user_type, contact_number) VALUES
('ADMIN001', 'admin@lnhs.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', '09123456789');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('school_name', 'Lipa National High School', 'Name of the school'),
('school_address', 'Lipa City, Batangas', 'School address'),
('school_contact', '043-123-4567', 'School contact number'),
('school_email', 'info@lnhs.edu.ph', 'School email address'),
('max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx', 'Allowed file types for uploads'),
('notification_email', 'true', 'Enable email notifications'),
('notification_sms', 'false', 'Enable SMS notifications'),
('auto_approve_requests', 'false', 'Automatically approve document requests'),
('maintenance_mode', 'false', 'Enable maintenance mode');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_student_id ON users(student_id);
CREATE INDEX idx_users_user_type ON users(user_type);
CREATE INDEX idx_document_requests_user_id ON document_requests(user_id);
CREATE INDEX idx_document_requests_status ON document_requests(status);
CREATE INDEX idx_document_requests_request_date ON document_requests(request_date);
CREATE INDEX idx_request_status_history_request_id ON request_status_history(request_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_created_at ON admin_logs(created_at);