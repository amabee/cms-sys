-- Migration: Create visit queue tables
-- Date: 2025-01-01
-- Description: Creates tables for patient queue management

-- Create visit_queue table for managing patient queue
CREATE TABLE IF NOT EXISTS visit_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    appointment_id INT NULL,
    doctor_id INT NULL,
    status ENUM('queued', 'called', 'served', 'cancelled') DEFAULT 'queued',
    queue_number INT,
    queued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    served_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    notes TEXT,
    priority ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
    estimated_wait_time INT DEFAULT 0, -- in minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    INDEX idx_status_queued (status, queued_at),
    INDEX idx_patient_queue (patient_id, status),
    INDEX idx_doctor_queue (doctor_id, status)
);

-- Create queue_logs table for audit trail
CREATE TABLE IF NOT EXISTS queue_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    queue_id INT NOT NULL,
    action ENUM('queued', 'called', 'served', 'cancelled', 'priority_changed') NOT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    user_id INT NULL,
    notes TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES visit_queue(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_queue_logs (queue_id, timestamp),
    INDEX idx_action_time (action, timestamp)
);

-- Add trigger to auto-generate queue numbers
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_visit_queue_number 
BEFORE INSERT ON visit_queue 
FOR EACH ROW 
BEGIN 
    IF NEW.queue_number IS NULL THEN
        SET NEW.queue_number = (
            SELECT COALESCE(MAX(queue_number), 0) + 1 
            FROM visit_queue 
            WHERE DATE(queued_at) = DATE(NOW())
        );
    END IF;
END//
DELIMITER ;

-- Insert sample data for testing (optional - remove in production)
-- INSERT INTO visit_queue (patient_id, appointment_id, notes) 
-- SELECT id, NULL, 'Sample queue entry' FROM patients LIMIT 3;
