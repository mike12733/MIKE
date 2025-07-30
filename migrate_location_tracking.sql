-- Migration script for adding location tracking features
-- Run this script if you have an existing installation

USE inventory_system;

-- Add new columns to equipment table if they don't exist
ALTER TABLE equipment 
ADD COLUMN IF NOT EXISTS last_scanned_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS last_scanned_by INT NULL;

-- Add foreign key constraint for last_scanned_by if it doesn't exist
ALTER TABLE equipment 
ADD CONSTRAINT fk_equipment_last_scanned_by 
FOREIGN KEY (last_scanned_by) REFERENCES admin_users(id);

-- Create location_history table if it doesn't exist
CREATE TABLE IF NOT EXISTS location_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    previous_location VARCHAR(255),
    new_location VARCHAR(255) NOT NULL,
    scanned_by INT NOT NULL,
    scan_method ENUM('Manual', 'Barcode Scanner', 'QR Code') DEFAULT 'Barcode Scanner',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (scanned_by) REFERENCES admin_users(id)
);

-- Add new indexes for better performance
CREATE INDEX IF NOT EXISTS idx_equipment_location ON equipment(location);
CREATE INDEX IF NOT EXISTS idx_location_history_equipment ON location_history(equipment_id);
CREATE INDEX IF NOT EXISTS idx_location_history_created_at ON location_history(created_at);

-- Insert sample location history for existing equipment (optional)
-- This will create initial location records for equipment that already has location data
INSERT INTO location_history (equipment_id, previous_location, new_location, scanned_by, scan_method, notes)
SELECT 
    id as equipment_id,
    NULL as previous_location,
    location as new_location,
    created_by as scanned_by,
    'Manual' as scan_method,
    'Initial location record created during migration' as notes
FROM equipment 
WHERE location IS NOT NULL 
AND location != ''
AND id NOT IN (SELECT equipment_id FROM location_history);

-- Update README or create migration log
SELECT 'Location tracking migration completed successfully!' as message;
SELECT COUNT(*) as total_equipment FROM equipment;
SELECT COUNT(*) as equipment_with_location FROM equipment WHERE location IS NOT NULL AND location != '';
SELECT COUNT(*) as location_history_records FROM location_history;