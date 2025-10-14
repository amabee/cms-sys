-- System Settings Management Database Schema
-- Created: 2025-10-13
-- Purpose: Comprehensive system configuration and management

-- System Settings Table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json', 'file') DEFAULT 'string',
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    is_editable BOOLEAN DEFAULT TRUE,
    validation_rules JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_setting_key (setting_key)
);

-- User Preferences Table
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT NOT NULL,
    preference_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key),
    INDEX idx_user_id (user_id)
);

-- System Backup History
CREATE TABLE IF NOT EXISTS system_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_name VARCHAR(255) NOT NULL,
    backup_type ENUM('full', 'database', 'files', 'configuration') DEFAULT 'full',
    backup_size BIGINT,
    backup_location VARCHAR(500),
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'corrupted') DEFAULT 'pending',
    compression_type VARCHAR(50),
    created_by INT,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT,
    metadata JSON,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_backup_type (backup_type),
    INDEX idx_created_at (created_at)
);

-- System Maintenance Schedule
CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    task_type ENUM('backup', 'cleanup', 'optimization', 'update', 'custom') NOT NULL,
    schedule_type ENUM('once', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'weekly',
    schedule_value VARCHAR(100), -- Cron expression or specific values
    is_active BOOLEAN DEFAULT TRUE,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    run_count INT DEFAULT 0,
    task_configuration JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_next_run (next_run),
    INDEX idx_is_active (is_active)
);

-- System Maintenance Logs
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT,
    task_name VARCHAR(255) NOT NULL,
    status ENUM('running', 'completed', 'failed', 'cancelled') DEFAULT 'running',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    duration_seconds INT,
    result_message TEXT,
    error_details TEXT,
    resources_affected JSON,
    executed_by INT,
    FOREIGN KEY (schedule_id) REFERENCES maintenance_schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_started_at (started_at),
    INDEX idx_schedule_id (schedule_id)
);

-- System Configuration Categories
CREATE TABLE IF NOT EXISTS configuration_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    access_level ENUM('admin', 'manager', 'user') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_active (is_active)
);

-- System Activity Monitoring
CREATE TABLE IF NOT EXISTS system_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_type ENUM('setting_change', 'backup_created', 'maintenance_run', 'user_preference', 'system_update') NOT NULL,
    entity_type VARCHAR(100),
    entity_id VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);

-- Email Configuration Template
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    template_type ENUM('notification', 'reminder', 'report', 'system') DEFAULT 'notification',
    subject VARCHAR(255) NOT NULL,
    body_text TEXT,
    body_html TEXT,
    variables JSON, -- Available template variables
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active)
);

-- Insert Default System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES
-- General Settings
('clinic_name', 'Healthcare Clinic', 'string', 'general', 'Name of the healthcare clinic', TRUE),
('clinic_address', '', 'string', 'general', 'Physical address of the clinic', TRUE),
('clinic_phone', '', 'string', 'general', 'Main phone number', TRUE),
('clinic_email', '', 'string', 'general', 'Main email address', TRUE),
('clinic_logo', '', 'file', 'general', 'Clinic logo image file', TRUE),
('timezone', 'America/New_York', 'string', 'general', 'System timezone', FALSE),
('date_format', 'Y-m-d', 'string', 'general', 'Default date format', FALSE),
('time_format', 'H:i:s', 'string', 'general', 'Default time format', FALSE),

-- Security Settings
('session_timeout', '3600', 'number', 'security', 'Session timeout in seconds', FALSE),
('max_login_attempts', '5', 'number', 'security', 'Maximum failed login attempts', FALSE),
('account_lockout_duration', '1800', 'number', 'security', 'Account lockout duration in seconds', FALSE),
('password_min_length', '8', 'number', 'security', 'Minimum password length', FALSE),
('require_password_complexity', 'true', 'boolean', 'security', 'Require complex passwords', FALSE),
('enable_two_factor', 'false', 'boolean', 'security', 'Enable two-factor authentication', FALSE),

-- Appointment Settings
('appointment_duration', '30', 'number', 'appointments', 'Default appointment duration in minutes', FALSE),
('advance_booking_limit', '90', 'number', 'appointments', 'Maximum days ahead for booking', FALSE),
('cancellation_limit', '24', 'number', 'appointments', 'Minimum hours before cancellation', FALSE),
('enable_online_booking', 'true', 'boolean', 'appointments', 'Allow online appointment booking', TRUE),
('booking_confirmation', 'true', 'boolean', 'appointments', 'Send booking confirmation emails', FALSE),

-- Email Settings
('smtp_host', '', 'string', 'email', 'SMTP server host', FALSE),
('smtp_port', '587', 'number', 'email', 'SMTP server port', FALSE),
('smtp_username', '', 'string', 'email', 'SMTP username', FALSE),
('smtp_password', '', 'string', 'email', 'SMTP password', FALSE),
('smtp_encryption', 'tls', 'string', 'email', 'SMTP encryption method', FALSE),
('from_email', '', 'string', 'email', 'Default from email address', FALSE),
('from_name', '', 'string', 'email', 'Default from name', FALSE),

-- Notification Settings
('enable_email_notifications', 'true', 'boolean', 'notifications', 'Enable email notifications', FALSE),
('enable_sms_notifications', 'false', 'boolean', 'notifications', 'Enable SMS notifications', FALSE),
('reminder_advance_time', '24', 'number', 'notifications', 'Hours before appointment to send reminder', FALSE),
('notification_retry_attempts', '3', 'number', 'notifications', 'Number of retry attempts for failed notifications', FALSE),

-- Backup Settings
('auto_backup_enabled', 'true', 'boolean', 'backup', 'Enable automatic backups', FALSE),
('backup_frequency', 'daily', 'string', 'backup', 'Backup frequency (daily, weekly, monthly)', FALSE),
('backup_retention_days', '30', 'number', 'backup', 'Number of days to retain backups', FALSE),
('backup_location', '/backups', 'string', 'backup', 'Backup storage location', FALSE),
('backup_compression', 'true', 'boolean', 'backup', 'Enable backup compression', FALSE),

-- System Maintenance
('maintenance_window_start', '02:00', 'string', 'maintenance', 'Maintenance window start time', FALSE),
('maintenance_window_end', '04:00', 'string', 'maintenance', 'Maintenance window end time', FALSE),
('auto_cleanup_logs', 'true', 'boolean', 'maintenance', 'Automatically cleanup old logs', FALSE),
('log_retention_days', '90', 'number', 'maintenance', 'Number of days to retain logs', FALSE)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert Default Configuration Categories
INSERT INTO configuration_categories (category_name, display_name, description, icon, sort_order) VALUES
('general', 'General Settings', 'Basic clinic information and general configuration', 'fas fa-cog', 1),
('security', 'Security Settings', 'Security and authentication configuration', 'fas fa-shield-alt', 2),
('appointments', 'Appointment Settings', 'Appointment booking and scheduling configuration', 'fas fa-calendar-alt', 3),
('email', 'Email Configuration', 'Email server and notification settings', 'fas fa-envelope', 4),
('notifications', 'Notification Settings', 'Notification preferences and delivery settings', 'fas fa-bell', 5),
('backup', 'Backup Settings', 'Backup and recovery configuration', 'fas fa-database', 6),
('maintenance', 'Maintenance Settings', 'System maintenance and cleanup settings', 'fas fa-tools', 7),
('reports', 'Report Settings', 'Reporting and analytics configuration', 'fas fa-chart-bar', 8)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

-- Insert Default Email Templates
INSERT INTO email_templates (template_name, template_type, subject, body_text, body_html, variables) VALUES
('appointment_confirmation', 'notification', 'Appointment Confirmation - {{clinic_name}}', 
'Dear {{patient_name}},\n\nYour appointment has been confirmed:\n\nDate: {{appointment_date}}\nTime: {{appointment_time}}\nDoctor: {{doctor_name}}\n\nPlease arrive 15 minutes early.\n\nBest regards,\n{{clinic_name}}',
'<p>Dear {{patient_name}},</p><p>Your appointment has been confirmed:</p><ul><li><strong>Date:</strong> {{appointment_date}}</li><li><strong>Time:</strong> {{appointment_time}}</li><li><strong>Doctor:</strong> {{doctor_name}}</li></ul><p>Please arrive 15 minutes early.</p><p>Best regards,<br>{{clinic_name}}</p>',
'["patient_name", "appointment_date", "appointment_time", "doctor_name", "clinic_name"]'),

('appointment_reminder', 'reminder', 'Appointment Reminder - {{clinic_name}}', 
'Dear {{patient_name}},\n\nThis is a reminder of your upcoming appointment:\n\nDate: {{appointment_date}}\nTime: {{appointment_time}}\nDoctor: {{doctor_name}}\n\nPlease contact us if you need to reschedule.\n\nBest regards,\n{{clinic_name}}',
'<p>Dear {{patient_name}},</p><p>This is a reminder of your upcoming appointment:</p><ul><li><strong>Date:</strong> {{appointment_date}}</li><li><strong>Time:</strong> {{appointment_time}}</li><li><strong>Doctor:</strong> {{doctor_name}}</li></ul><p>Please contact us if you need to reschedule.</p><p>Best regards,<br>{{clinic_name}}</p>',
'["patient_name", "appointment_date", "appointment_time", "doctor_name", "clinic_name"]'),

('system_backup_report', 'system', 'System Backup Report - {{backup_date}}', 
'System backup completed successfully.\n\nBackup Details:\nType: {{backup_type}}\nSize: {{backup_size}}\nLocation: {{backup_location}}\nDuration: {{backup_duration}}\n\nNext scheduled backup: {{next_backup}}',
'<p>System backup completed successfully.</p><p><strong>Backup Details:</strong></p><ul><li><strong>Type:</strong> {{backup_type}}</li><li><strong>Size:</strong> {{backup_size}}</li><li><strong>Location:</strong> {{backup_location}}</li><li><strong>Duration:</strong> {{backup_duration}}</li></ul><p><strong>Next scheduled backup:</strong> {{next_backup}}</p>',
'["backup_date", "backup_type", "backup_size", "backup_location", "backup_duration", "next_backup"]')
ON DUPLICATE KEY UPDATE subject = VALUES(subject);

-- Create triggers for system activity logging
DELIMITER //

DROP TRIGGER IF EXISTS system_settings_activity_log//
DROP TRIGGER IF EXISTS user_preferences_activity_log//

CREATE TRIGGER system_settings_activity_log 
AFTER UPDATE ON system_settings
FOR EACH ROW
BEGIN
    INSERT INTO system_activity (
        activity_type, entity_type, entity_id, 
        old_value, new_value, user_id, created_at
    ) VALUES (
        'setting_change', 'system_setting', NEW.setting_key,
        OLD.setting_value, NEW.setting_value, NEW.updated_by, NOW()
    );
END//

CREATE TRIGGER user_preferences_activity_log 
AFTER UPDATE ON user_preferences
FOR EACH ROW
BEGIN
    INSERT INTO system_activity (
        activity_type, entity_type, entity_id, 
        old_value, new_value, user_id, created_at
    ) VALUES (
        'user_preference', 'user_preference', CONCAT(NEW.user_id, ':', NEW.preference_key),
        OLD.preference_value, NEW.preference_value, NEW.user_id, NOW()
    );
END//

DELIMITER ;

-- Create indexes for performance optimization (commented out to avoid duplicates)
-- CREATE INDEX idx_system_settings_category_key ON system_settings(category, setting_key);
-- CREATE INDEX idx_user_preferences_composite ON user_preferences(user_id, preference_key, updated_at);
-- CREATE INDEX idx_system_activity_composite ON system_activity(activity_type, created_at, user_id);
-- CREATE INDEX idx_maintenance_schedules_next_run ON maintenance_schedules(is_active, next_run);
-- CREATE INDEX idx_system_backups_status_date ON system_backups(status, created_at);

-- Insert sample maintenance schedules
INSERT INTO maintenance_schedules (task_name, task_type, schedule_type, schedule_value, task_configuration, created_by) VALUES
('Daily Database Backup', 'backup', 'daily', '02:00', '{"backup_type": "database", "compress": true, "encrypt": false}', 1),
('Weekly Full Backup', 'backup', 'weekly', 'sunday:03:00', '{"backup_type": "full", "compress": true, "encrypt": true}', 1),
('Monthly Log Cleanup', 'cleanup', 'monthly', '1:01:00', '{"cleanup_logs": true, "retention_days": 90, "cleanup_temp": true}', 1),
('Database Optimization', 'optimization', 'weekly', 'sunday:04:00', '{"analyze_tables": true, "optimize_tables": true, "repair_tables": false}', 1);

-- Set initial next run times for maintenance schedules
UPDATE maintenance_schedules 
SET next_run = CASE 
    WHEN schedule_type = 'daily' THEN DATE_ADD(NOW(), INTERVAL 1 DAY)
    WHEN schedule_type = 'weekly' THEN DATE_ADD(NOW(), INTERVAL 7 DAY)
    WHEN schedule_type = 'monthly' THEN DATE_ADD(NOW(), INTERVAL 30 DAY)
    ELSE DATE_ADD(NOW(), INTERVAL 1 DAY)
END 
WHERE next_run IS NULL;
