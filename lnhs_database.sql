-- LNHS Documents Request Portal Database
-- Create database
CREATE DATABASE IF NOT EXISTS lnhs_documents_portal;
USE lnhs_documents_portal;

-- Users table (students, alumni, and admin)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('student', 'alumni', 'admin') NOT NULL,
    student_id VARCHAR(50) UNIQUE,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    contact_number VARCHAR(20),
    address TEXT,
    year_graduated YEAR NULL,
    course VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Document types table
CREATE TABLE IF NOT EXISTS document_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    requirements TEXT,
    processing_days INT DEFAULT 3,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Document requests table
CREATE TABLE IF NOT EXISTS document_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type_id INT NOT NULL,
    purpose TEXT NOT NULL,
    preferred_release_date DATE,
    status ENUM('pending', 'processing', 'approved', 'denied', 'ready_for_pickup', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    rejection_reason TEXT,
    request_number VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_by INT NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Request attachments table (for uploaded IDs and requirements)
CREATE TABLE IF NOT EXISTS request_attachments (
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
CREATE TABLE IF NOT EXISTS request_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    request_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    sent_via_email BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE SET NULL
);

-- Insert default document types
INSERT INTO document_types (name, description, requirements, processing_days) VALUES 
('Certificate of Enrollment', 'Official certificate showing current enrollment status', 'Valid ID, Student ID', 3),
('Good Moral Certificate', 'Certificate of good moral character', 'Valid ID, Student ID, Clearance (if applicable)', 5),
('Transcript of Records', 'Official academic transcript', 'Valid ID, Student ID, Request Letter', 7),
('Diploma Copy', 'Certified true copy of diploma', 'Valid ID, Original Diploma (for verification)', 5),
('Certificate of Graduation', 'Official graduation certificate', 'Valid ID, Student ID', 3);

-- Insert default admin user (password: admin123)
INSERT INTO users (user_type, email, password, first_name, last_name, contact_number) VALUES 
('admin', 'admin@lnhs.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', '09123456789');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_student_id ON users(student_id);
CREATE INDEX idx_users_user_type ON users(user_type);
CREATE INDEX idx_document_requests_user_id ON document_requests(user_id);
CREATE INDEX idx_document_requests_status ON document_requests(status);
CREATE INDEX idx_document_requests_request_number ON document_requests(request_number);
CREATE INDEX idx_request_attachments_request_id ON request_attachments(request_id);
CREATE INDEX idx_request_status_history_request_id ON request_status_history(request_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);