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

-- Equipment/Items table
CREATE TABLE IF NOT EXISTS equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    quantity INT NOT NULL DEFAULT 0,
    condition_status ENUM('Good', 'Fair', 'Poor', 'Damaged', 'Lost') DEFAULT 'Good',
    location VARCHAR(255),
    barcode VARCHAR(255) UNIQUE NOT NULL,
    purchase_date DATE,
    purchase_price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES admin_users(id)
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

-- Equipment tracking history table
CREATE TABLE IF NOT EXISTS equipment_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    admin_id INT NOT NULL,
    previous_status ENUM('Good', 'Fair', 'Poor', 'Damaged', 'Lost'),
    new_status ENUM('Good', 'Fair', 'Poor', 'Damaged', 'Lost') NOT NULL,
    location VARCHAR(255),
    notes TEXT,
    tracked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id)
);

-- Create indexes for better performance
CREATE INDEX idx_equipment_barcode ON equipment(barcode);
CREATE INDEX idx_equipment_category ON equipment(category);
CREATE INDEX idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_created_at ON admin_logs(created_at);
CREATE INDEX idx_equipment_tracking_equipment_id ON equipment_tracking(equipment_id);
CREATE INDEX idx_equipment_tracking_tracked_at ON equipment_tracking(tracked_at);