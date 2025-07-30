-- Inventory Tracking and Equipment Management System Database
-- Create database
CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Equipment/Items table (enhanced with barcode sequence)
CREATE TABLE IF NOT EXISTS equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    quantity INT NOT NULL DEFAULT 0,
    condition_status ENUM('Good', 'Fair', 'Poor', 'Damaged', 'Lost') DEFAULT 'Good',
    location VARCHAR(255),
    barcode VARCHAR(255) UNIQUE NOT NULL,
    barcode_sequence INT UNIQUE NOT NULL AUTO_INCREMENT,
    purchase_date DATE,
    purchase_price DECIMAL(10,2),
    last_scanned_at TIMESTAMP NULL,
    last_scanned_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES admin_users(id),
    FOREIGN KEY (last_scanned_by) REFERENCES admin_users(id)
);

-- Equipment tracking/movement history table
CREATE TABLE IF NOT EXISTS equipment_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    previous_location VARCHAR(255),
    new_location VARCHAR(255) NOT NULL,
    previous_condition ENUM('Good', 'Fair', 'Poor', 'Damaged', 'Lost'),
    new_condition ENUM('Good', 'Fair', 'Poor', 'Damaged', 'Lost') NOT NULL,
    scan_type ENUM('location_update', 'condition_update', 'checkout', 'checkin', 'maintenance') NOT NULL,
    notes TEXT,
    scanned_by INT NOT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (scanned_by) REFERENCES admin_users(id)
);

-- Real-time equipment status table
CREATE TABLE IF NOT EXISTS equipment_realtime_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT UNIQUE NOT NULL,
    current_location VARCHAR(255),
    current_condition ENUM('Good', 'Fair', 'Poor', 'Damaged', 'Lost') NOT NULL,
    is_checked_out BOOLEAN DEFAULT FALSE,
    checked_out_to VARCHAR(255) NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_scanned_by INT,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (last_scanned_by) REFERENCES admin_users(id)
);

-- Barcode sequence counter for unique barcode generation
CREATE TABLE IF NOT EXISTS barcode_sequence (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year INT NOT NULL,
    sequence_number INT NOT NULL DEFAULT 0,
    UNIQUE KEY unique_year (year)
);

-- Admin activity logs table
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (email, password, full_name) VALUES 
('admin@inventory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

-- Initialize barcode sequence for current year
INSERT INTO barcode_sequence (year, sequence_number) VALUES (YEAR(NOW()), 0) 
ON DUPLICATE KEY UPDATE sequence_number = sequence_number;

-- Create indexes for better performance
CREATE INDEX idx_equipment_barcode ON equipment(barcode);
CREATE INDEX idx_equipment_category ON equipment(category);
CREATE INDEX idx_equipment_active ON equipment(is_active);
CREATE INDEX idx_equipment_last_scanned ON equipment(last_scanned_at);
CREATE INDEX idx_tracking_equipment_id ON equipment_tracking(equipment_id);
CREATE INDEX idx_tracking_scanned_at ON equipment_tracking(scanned_at);
CREATE INDEX idx_realtime_equipment_id ON equipment_realtime_status(equipment_id);
CREATE INDEX idx_realtime_location ON equipment_realtime_status(current_location);
CREATE INDEX idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_created_at ON admin_logs(created_at);