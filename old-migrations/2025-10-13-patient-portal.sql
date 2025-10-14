-- Patient Portal System Database Schema
-- Created: October 13, 2025
-- Description: Comprehensive patient self-service portal with appointment booking, records access, and communication

-- Patient Portal Sessions (separate from staff sessions for security)
CREATE TABLE IF NOT EXISTS patient_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_patient_sessions_patient (patient_id),
    INDEX idx_patient_sessions_token (session_token),
    INDEX idx_patient_sessions_expires (expires_at),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Patient Portal Preferences
CREATE TABLE IF NOT EXISTS patient_portal_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL UNIQUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT TRUE,
    appointment_reminders BOOLEAN DEFAULT TRUE,
    lab_result_notifications BOOLEAN DEFAULT TRUE,
    billing_notifications BOOLEAN DEFAULT TRUE,
    language_preference VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'America/New_York',
    theme_preference ENUM('light', 'dark', 'auto') DEFAULT 'light',
    dashboard_layout JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Patient Messages (Communication with providers)
CREATE TABLE IF NOT EXISTS patient_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    sender_type ENUM('patient', 'provider') NOT NULL,
    sender_id INT NOT NULL, -- patient_id or user_id based on sender_type
    recipient_type ENUM('patient', 'provider') NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('general', 'appointment', 'medical', 'billing', 'prescription') DEFAULT 'general',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    parent_message_id INT NULL, -- For message threading
    attachment_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_messages_patient (patient_id),
    INDEX idx_patient_messages_sender (sender_type, sender_id),
    INDEX idx_patient_messages_recipient (recipient_type, recipient_id),
    INDEX idx_patient_messages_thread (parent_message_id),
    INDEX idx_patient_messages_created (created_at),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES patient_messages(id) ON DELETE SET NULL
);

-- Patient Appointment Requests
CREATE TABLE IF NOT EXISTS patient_appointment_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    requested_doctor_id INT NULL,
    appointment_type ENUM('consultation', 'follow_up', 'emergency', 'routine_checkup', 'specialist') NOT NULL,
    preferred_date DATE NOT NULL,
    preferred_time_start TIME,
    preferred_time_end TIME,
    alternative_dates JSON, -- Store up to 3 alternative dates
    reason TEXT NOT NULL,
    urgency ENUM('routine', 'urgent', 'emergency') DEFAULT 'routine',
    status ENUM('pending', 'approved', 'rejected', 'scheduled', 'cancelled') DEFAULT 'pending',
    admin_notes TEXT,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    scheduled_appointment_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appointment_requests_patient (patient_id),
    INDEX idx_appointment_requests_doctor (requested_doctor_id),
    INDEX idx_appointment_requests_status (status),
    INDEX idx_appointment_requests_date (preferred_date),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (scheduled_appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- Patient Document Access Log
CREATE TABLE IF NOT EXISTS patient_document_access_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    document_type ENUM('medical_record', 'lab_result', 'prescription', 'billing', 'report') NOT NULL,
    document_id INT NOT NULL,
    action ENUM('view', 'download', 'print') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    access_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_document_access_patient (patient_id),
    INDEX idx_document_access_type (document_type, document_id),
    INDEX idx_document_access_timestamp (access_timestamp),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Patient Portal Feedback
CREATE TABLE IF NOT EXISTS patient_portal_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    category ENUM('bug_report', 'feature_request', 'usability', 'general', 'complaint', 'compliment') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    page_url VARCHAR(500),
    browser_info TEXT,
    screenshot_path VARCHAR(500),
    status ENUM('open', 'in_review', 'resolved', 'closed') DEFAULT 'open',
    admin_response TEXT,
    responded_by INT NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_portal_feedback_patient (patient_id),
    INDEX idx_portal_feedback_category (category),
    INDEX idx_portal_feedback_status (status),
    INDEX idx_portal_feedback_created (created_at),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Patient Health Goals and Tracking
CREATE TABLE IF NOT EXISTS patient_health_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    goal_type ENUM('weight_loss', 'weight_gain', 'exercise', 'medication_adherence', 'blood_pressure', 'blood_sugar', 'custom') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_value DECIMAL(10,2),
    target_unit VARCHAR(50),
    target_date DATE,
    current_value DECIMAL(10,2),
    progress_percentage DECIMAL(5,2) DEFAULT 0,
    status ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
    created_by_doctor BOOLEAN DEFAULT FALSE,
    doctor_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_health_goals_patient (patient_id),
    INDEX idx_health_goals_type (goal_type),
    INDEX idx_health_goals_status (status),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Patient Health Goal Progress Tracking
CREATE TABLE IF NOT EXISTS patient_health_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    goal_id INT NOT NULL,
    recorded_value DECIMAL(10,2) NOT NULL,
    notes TEXT,
    recorded_date DATE NOT NULL,
    recorded_time TIME,
    mood_rating INT CHECK (mood_rating >= 1 AND mood_rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_health_progress_goal (goal_id),
    INDEX idx_health_progress_date (recorded_date),
    FOREIGN KEY (goal_id) REFERENCES patient_health_goals(id) ON DELETE CASCADE
);

-- Patient Portal Activity Log
CREATE TABLE IF NOT EXISTS patient_portal_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'view_records', 'book_appointment', 'message_sent', 'document_download', 'profile_update', 'payment_made') NOT NULL,
    activity_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(128),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_portal_activity_patient (patient_id),
    INDEX idx_portal_activity_type (activity_type),
    INDEX idx_portal_activity_created (created_at),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Insert default preferences for existing patients
INSERT IGNORE INTO patient_portal_preferences (patient_id)
SELECT id FROM patients WHERE is_active = 1;

-- Create triggers for automatic activity logging
DELIMITER //

DROP TRIGGER IF EXISTS after_patient_message_insert//
DROP TRIGGER IF EXISTS after_appointment_request_insert//
DROP TRIGGER IF EXISTS after_document_access_insert//

CREATE TRIGGER after_patient_message_insert
AFTER INSERT ON patient_messages
FOR EACH ROW
BEGIN
    IF NEW.sender_type = 'patient' THEN
        INSERT INTO patient_portal_activity_log (patient_id, activity_type, activity_description)
        VALUES (NEW.patient_id, 'message_sent', CONCAT('Sent message: ', LEFT(NEW.subject, 50)));
    END IF;
END//

CREATE TRIGGER after_appointment_request_insert
AFTER INSERT ON patient_appointment_requests
FOR EACH ROW
BEGIN
    INSERT INTO patient_portal_activity_log (patient_id, activity_type, activity_description)
    VALUES (NEW.patient_id, 'book_appointment', CONCAT('Requested appointment for ', NEW.preferred_date));
END//

CREATE TRIGGER after_document_access_insert
AFTER INSERT ON patient_document_access_log
FOR EACH ROW
BEGIN
    INSERT INTO patient_portal_activity_log (patient_id, activity_type, activity_description)
    VALUES (NEW.patient_id, 'document_download', CONCAT('Accessed ', NEW.document_type, ' document'));
END//

DELIMITER ;

-- Add indexes for better performance (skip if they already exist)
-- Note: These indexes may already exist, errors are ignored
-- CREATE INDEX idx_patients_email ON patients(email);
-- CREATE INDEX idx_patients_phone ON patients(phone);
-- CREATE INDEX idx_patients_active ON patients(is_active);

-- Update patients table to ensure portal access fields exist
-- Note: Columns may already exist, manual verification recommended
-- ALTER TABLE patients 
-- ADD COLUMN portal_enabled BOOLEAN DEFAULT TRUE,
-- ADD COLUMN portal_last_login TIMESTAMP NULL,
-- ADD COLUMN portal_login_attempts INT DEFAULT 0,
-- ADD COLUMN portal_locked_until TIMESTAMP NULL,
-- ADD COLUMN email_verified BOOLEAN DEFAULT FALSE,
-- ADD COLUMN email_verification_token VARCHAR(128) NULL,
-- ADD COLUMN password_reset_token VARCHAR(128) NULL,
-- ADD COLUMN password_reset_expires TIMESTAMP NULL;

-- Create indexes for new patient fields (commented out to avoid duplicates)
-- CREATE INDEX idx_patients_portal_enabled ON patients(portal_enabled);
-- CREATE INDEX idx_patients_email_verified ON patients(email_verified);
-- CREATE INDEX idx_patients_verification_token ON patients(email_verification_token);
-- CREATE INDEX idx_patients_reset_token ON patients(password_reset_token);

-- Sample data for development/testing
INSERT IGNORE INTO patient_messages (patient_id, sender_type, sender_id, recipient_type, recipient_id, subject, message, message_type, priority) VALUES
(1, 'patient', 1, 'provider', 1, 'Question about my recent lab results', 'I received my lab results but have some questions about the cholesterol levels. Could you please explain what this means for my health?', 'medical', 'normal'),
(2, 'provider', 1, 'patient', 2, 'Follow-up appointment reminder', 'This is a reminder that you have a follow-up appointment scheduled for next week. Please bring your current medications list.', 'appointment', 'normal');

-- Sample appointment requests
INSERT IGNORE INTO patient_appointment_requests (patient_id, requested_doctor_id, appointment_type, preferred_date, preferred_time_start, reason, urgency) VALUES
(1, 1, 'follow_up', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '09:00:00', 'Follow-up for recent blood work results and medication adjustment', 'routine'),
(2, 1, 'consultation', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', 'New patient consultation for ongoing back pain issues', 'urgent');

-- Sample health goals
INSERT IGNORE INTO patient_health_goals (patient_id, goal_type, title, description, target_value, target_unit, target_date, current_value, doctor_id) VALUES
(1, 'weight_loss', 'Lose 20 pounds', 'Doctor recommended weight loss for better health', 180.00, 'lbs', DATE_ADD(CURDATE(), INTERVAL 90 DAY), 200.00, 1),
(1, 'blood_pressure', 'Lower Blood Pressure', 'Maintain blood pressure below 130/80', 130.00, 'mmHg', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 145.00, 1),
(2, 'exercise', 'Daily Walking', 'Walk at least 30 minutes daily', 30.00, 'minutes', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 15.00, NULL);
