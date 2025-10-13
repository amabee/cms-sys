-- Security Enhancement Tables for Clinic Management System
-- Created: October 13, 2025

-- Login attempts tracking table
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(255),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username_time (username, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time)
);

-- Security logs for comprehensive audit trail
CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    session_id VARCHAR(128),
    ip_address VARCHAR(45),
    user_agent TEXT,
    event_type ENUM(
        'LOGIN', 'LOGOUT', 'LOGIN_FAILED', 'PASSWORD_CHANGE', 
        'ACCOUNT_LOCKED', 'ACCOUNT_UNLOCKED', 'PERMISSION_DENIED',
        'SUSPICIOUS_ACTIVITY', 'SESSION_EXPIRED', 'DATA_ACCESS',
        'DATA_MODIFICATION', 'SYSTEM_ACCESS'
    ) NOT NULL,
    event_details JSON,
    risk_level ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'LOW',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_event (user_id, event_type),
    INDEX idx_time_risk (created_at, risk_level),
    INDEX idx_session (session_id)
);

-- Active sessions tracking
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(128) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_expires (expires_at)
);

-- Password history to prevent reuse
CREATE TABLE IF NOT EXISTS password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, created_at)
);

-- Account lockouts
CREATE TABLE IF NOT EXISTS account_lockouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    username VARCHAR(100),
    ip_address VARCHAR(45),
    lockout_reason ENUM('FAILED_ATTEMPTS', 'SUSPICIOUS_ACTIVITY', 'ADMIN_ACTION') NOT NULL,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unlock_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    unlocked_by INT NULL,
    unlocked_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (unlocked_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_username_active (username, is_active),
    INDEX idx_ip_active (ip_address, is_active)
);

-- System security settings
CREATE TABLE IF NOT EXISTS security_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default security settings
INSERT INTO security_settings (setting_name, setting_value, description) VALUES
('max_login_attempts', '5', 'Maximum failed login attempts before account lockout'),
('lockout_duration', '900', 'Account lockout duration in seconds (15 minutes)'),
('session_timeout', '3600', 'Session timeout in seconds (1 hour)'),
('password_min_length', '8', 'Minimum password length'),
('password_require_uppercase', '1', 'Require uppercase letters in password'),
('password_require_lowercase', '1', 'Require lowercase letters in password'),
('password_require_numbers', '1', 'Require numbers in password'),
('password_require_symbols', '1', 'Require special characters in password'),
('password_history_count', '5', 'Number of previous passwords to remember'),
('force_password_change_days', '90', 'Days before forced password change (0 to disable)'),
('enable_two_factor', '0', 'Enable two-factor authentication requirement'),
('login_notification', '1', 'Send email notifications on login'),
('suspicious_activity_detection', '1', 'Enable automatic suspicious activity detection')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    description = VALUES(description);

-- Add security columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS force_password_change BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(32) NULL,
ADD COLUMN IF NOT EXISTS last_password_change TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS security_question VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS security_answer VARCHAR(255) NULL;

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_users_locked_until ON users(locked_until);
CREATE INDEX IF NOT EXISTS idx_users_failed_attempts ON users(failed_login_attempts);
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login);
