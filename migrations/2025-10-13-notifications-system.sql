-- Notifications System Database Schema
-- Created: October 13, 2025

-- Notifications table for storing all system notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NULL,
    patient_id INT NULL,
    type ENUM(
        'appointment_reminder', 
        'appointment_confirmation', 
        'appointment_cancellation',
        'lab_result_ready', 
        'lab_critical_value',
        'bill_generated', 
        'bill_overdue', 
        'payment_received',
        'system_maintenance', 
        'system_alert',
        'general_announcement',
        'prescription_ready',
        'medical_record_update'
    ) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending',
    delivery_method SET('email', 'sms', 'in_app', 'push') NOT NULL DEFAULT 'in_app',
    
    -- Reference data (JSON format for flexibility)
    reference_data JSON NULL,
    
    -- Scheduling
    scheduled_for TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    
    -- Email specific fields
    email_to VARCHAR(255) NULL,
    email_cc VARCHAR(255) NULL,
    email_subject VARCHAR(255) NULL,
    email_template VARCHAR(100) NULL,
    
    -- SMS specific fields
    sms_to VARCHAR(20) NULL,
    sms_template VARCHAR(100) NULL,
    
    -- Delivery tracking
    delivery_attempts INT DEFAULT 0,
    last_attempt_at TIMESTAMP NULL,
    delivery_error TEXT NULL,
    
    -- Metadata
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_type_status (type, status),
    INDEX idx_scheduled_for (scheduled_for),
    INDEX idx_priority_status (priority, status),
    INDEX idx_created_at (created_at)
);

-- Notification templates for consistent messaging
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_code VARCHAR(100) UNIQUE NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    notification_type ENUM(
        'appointment_reminder', 
        'appointment_confirmation', 
        'appointment_cancellation',
        'lab_result_ready', 
        'lab_critical_value',
        'bill_generated', 
        'bill_overdue', 
        'payment_received',
        'system_maintenance', 
        'system_alert',
        'general_announcement',
        'prescription_ready',
        'medical_record_update'
    ) NOT NULL,
    delivery_method ENUM('email', 'sms', 'in_app', 'push') NOT NULL,
    
    -- Template content
    subject_template VARCHAR(255) NULL,
    message_template TEXT NOT NULL,
    
    -- Template variables (JSON array of variable names)
    available_variables JSON NULL,
    
    -- Settings
    is_active BOOLEAN DEFAULT TRUE,
    is_system_template BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_method (notification_type, delivery_method),
    INDEX idx_active (is_active)
);

-- User notification preferences
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    patient_id INT NULL,
    
    -- Notification type preferences
    appointment_reminders BOOLEAN DEFAULT TRUE,
    appointment_confirmations BOOLEAN DEFAULT TRUE,
    lab_results BOOLEAN DEFAULT TRUE,
    critical_lab_alerts BOOLEAN DEFAULT TRUE,
    billing_notifications BOOLEAN DEFAULT TRUE,
    system_alerts BOOLEAN DEFAULT TRUE,
    
    -- Delivery method preferences
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    in_app_enabled BOOLEAN DEFAULT TRUE,
    push_enabled BOOLEAN DEFAULT TRUE,
    
    -- Timing preferences
    reminder_hours_before INT DEFAULT 24,
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00',
    
    -- Contact information
    preferred_email VARCHAR(255) NULL,
    preferred_phone VARCHAR(20) NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    -- Unique constraints
    UNIQUE KEY unique_user_prefs (user_id),
    UNIQUE KEY unique_patient_prefs (patient_id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_patient_id (patient_id)
);

-- Notification delivery log for tracking and analytics
CREATE TABLE IF NOT EXISTS notification_delivery_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id VARCHAR(50) NOT NULL,
    delivery_method ENUM('email', 'sms', 'in_app', 'push') NOT NULL,
    delivery_status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced') NOT NULL,
    
    -- Delivery details
    recipient VARCHAR(255) NOT NULL,
    delivery_provider VARCHAR(100) NULL,
    provider_message_id VARCHAR(255) NULL,
    
    -- Response tracking
    delivery_response TEXT NULL,
    error_message TEXT NULL,
    
    -- Timing
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    
    -- Reference
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    
    INDEX idx_notification_id (notification_id),
    INDEX idx_status_method (delivery_status, delivery_method),
    INDEX idx_attempted_at (attempted_at)
);

-- Email queue for batch processing
CREATE TABLE IF NOT EXISTS email_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id VARCHAR(50) NOT NULL,
    
    -- Email details
    to_email VARCHAR(255) NOT NULL,
    cc_email VARCHAR(255) NULL,
    bcc_email VARCHAR(255) NULL,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT NULL,
    
    -- Attachments (JSON array of file paths)
    attachments JSON NULL,
    
    -- Priority and scheduling
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    scheduled_for TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Processing status
    status ENUM('queued', 'processing', 'sent', 'failed') DEFAULT 'queued',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    last_attempt_at TIMESTAMP NULL,
    error_message TEXT NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Reference
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    
    INDEX idx_status_scheduled (status, scheduled_for),
    INDEX idx_priority (priority),
    INDEX idx_notification_id (notification_id)
);

-- SMS queue for batch processing
CREATE TABLE IF NOT EXISTS sms_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id VARCHAR(50) NOT NULL,
    
    -- SMS details
    to_phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    
    -- Priority and scheduling
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    scheduled_for TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Processing status
    status ENUM('queued', 'processing', 'sent', 'failed') DEFAULT 'queued',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    last_attempt_at TIMESTAMP NULL,
    error_message TEXT NULL,
    
    -- Provider details
    provider VARCHAR(100) NULL,
    provider_message_id VARCHAR(255) NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Reference
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    
    INDEX idx_status_scheduled (status, scheduled_for),
    INDEX idx_priority (priority),
    INDEX idx_notification_id (notification_id)
);

-- Insert default notification templates
INSERT INTO notification_templates (template_code, template_name, notification_type, delivery_method, subject_template, message_template, available_variables, is_system_template) VALUES
-- Appointment reminders
('appointment_reminder_email', 'Appointment Reminder - Email', 'appointment_reminder', 'email', 
 'Appointment Reminder - {appointment_date} at {appointment_time}',
 'Dear {patient_name},<br><br>This is a friendly reminder that you have an appointment scheduled with Dr. {doctor_name} on {appointment_date} at {appointment_time}.<br><br>Appointment Details:<br>- Date: {appointment_date}<br>- Time: {appointment_time}<br>- Doctor: Dr. {doctor_name}<br>- Reason: {appointment_reason}<br><br>If you need to reschedule or cancel, please contact us at {clinic_phone}.<br><br>Thank you,<br>{clinic_name}',
 '["patient_name", "doctor_name", "appointment_date", "appointment_time", "appointment_reason", "clinic_name", "clinic_phone"]', TRUE),

('appointment_reminder_sms', 'Appointment Reminder - SMS', 'appointment_reminder', 'sms',
 NULL,
 'Hi {patient_name}, reminder: You have an appointment with Dr. {doctor_name} on {appointment_date} at {appointment_time}. Contact {clinic_phone} to reschedule. -{clinic_name}',
 '["patient_name", "doctor_name", "appointment_date", "appointment_time", "clinic_name", "clinic_phone"]', TRUE),

-- Lab result notifications
('lab_result_ready_email', 'Lab Results Ready - Email', 'lab_result_ready', 'email',
 'Your Lab Results are Ready',
 'Dear {patient_name},<br><br>Your lab test results for {test_name} are now available.<br><br>Test Details:<br>- Test: {test_name}<br>- Date Collected: {collection_date}<br>- Status: {test_status}<br><br>Please log in to your patient portal or contact our office to review your results.<br><br>If you have any questions, please don\'t hesitate to contact us at {clinic_phone}.<br><br>Best regards,<br>{clinic_name}',
 '["patient_name", "test_name", "collection_date", "test_status", "clinic_name", "clinic_phone"]', TRUE),

('lab_critical_value_email', 'CRITICAL Lab Result Alert - Email', 'lab_critical_value', 'email',
 'URGENT: Critical Lab Result - Immediate Attention Required',
 'Dear {patient_name},<br><br><strong>URGENT NOTICE:</strong> Your recent lab test has returned a critical value that requires immediate medical attention.<br><br>Test Details:<br>- Test: {test_name}<br>- Critical Value: {test_result}<br>- Normal Range: {normal_range}<br><br><strong>PLEASE CONTACT OUR OFFICE IMMEDIATELY</strong> at {clinic_phone} or visit our emergency contact.<br><br>This is time-sensitive and requires prompt medical evaluation.<br><br>{clinic_name}<br>Emergency Contact: {emergency_phone}',
 '["patient_name", "test_name", "test_result", "normal_range", "clinic_name", "clinic_phone", "emergency_phone"]', TRUE),

-- Billing notifications
('bill_generated_email', 'New Bill Generated - Email', 'bill_generated', 'email',
 'New Medical Bill - Invoice #{bill_id}',
 'Dear {patient_name},<br><br>A new medical bill has been generated for your recent visit.<br><br>Bill Details:<br>- Invoice #: {bill_id}<br>- Date: {bill_date}<br>- Amount: ${total_amount}<br>- Due Date: {due_date}<br><br>You can view and pay your bill online through our patient portal or visit our office.<br><br>Payment Options:<br>- Online: {portal_link}<br>- Phone: {clinic_phone}<br>- In Person: {clinic_address}<br><br>Thank you,<br>{clinic_name}',
 '["patient_name", "bill_id", "bill_date", "total_amount", "due_date", "portal_link", "clinic_name", "clinic_phone", "clinic_address"]', TRUE),

('bill_overdue_email', 'Overdue Payment Reminder - Email', 'bill_overdue', 'email',
 'Payment Overdue - Invoice #{bill_id}',
 'Dear {patient_name},<br><br>Our records show that payment for Invoice #{bill_id} is now overdue.<br><br>Bill Details:<br>- Invoice #: {bill_id}<br>- Original Due Date: {due_date}<br>- Amount Due: ${balance_amount}<br>- Days Overdue: {days_overdue}<br><br>Please make payment as soon as possible to avoid any service interruption.<br><br>Payment Options:<br>- Online: {portal_link}<br>- Phone: {clinic_phone}<br>- In Person: {clinic_address}<br><br>If you have any questions about this bill, please contact our billing department at {billing_phone}.<br><br>{clinic_name}',
 '["patient_name", "bill_id", "due_date", "balance_amount", "days_overdue", "portal_link", "clinic_name", "clinic_phone", "clinic_address", "billing_phone"]', TRUE),

-- System notifications
('system_maintenance_email', 'System Maintenance Notice - Email', 'system_maintenance', 'email',
 'System Maintenance Notification - {maintenance_date}',
 'Dear Valued User,<br><br>We will be performing scheduled system maintenance that may affect our services.<br><br>Maintenance Details:<br>- Date: {maintenance_date}<br>- Time: {maintenance_time}<br>- Duration: {maintenance_duration}<br>- Affected Services: {affected_services}<br><br>During this time, you may experience temporary service interruptions. We apologize for any inconvenience this may cause.<br><br>If you have any urgent needs, please contact us at {emergency_contact}.<br><br>Thank you for your patience,<br>{clinic_name} IT Team',
 '["maintenance_date", "maintenance_time", "maintenance_duration", "affected_services", "emergency_contact", "clinic_name"]', TRUE);

-- Insert default notification preferences for existing users
INSERT INTO notification_preferences (user_id, appointment_reminders, appointment_confirmations, lab_results, critical_lab_alerts, billing_notifications, system_alerts, email_enabled, sms_enabled, in_app_enabled, push_enabled)
SELECT id, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, TRUE
FROM users
WHERE id NOT IN (SELECT user_id FROM notification_preferences WHERE user_id IS NOT NULL);

-- Insert default notification preferences for existing patients
INSERT INTO notification_preferences (patient_id, appointment_reminders, appointment_confirmations, lab_results, critical_lab_alerts, billing_notifications, system_alerts, email_enabled, sms_enabled, in_app_enabled, push_enabled)
SELECT id, TRUE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, FALSE, FALSE, FALSE
FROM patients
WHERE id NOT IN (SELECT patient_id FROM notification_preferences WHERE patient_id IS NOT NULL);

-- Create triggers for automatic notification generation

DELIMITER //

-- Trigger for appointment reminders
CREATE TRIGGER create_appointment_reminder
    AFTER INSERT ON appointments
    FOR EACH ROW
BEGIN
    DECLARE reminder_time TIMESTAMP;
    DECLARE notification_uuid VARCHAR(50);
    
    -- Calculate reminder time (24 hours before appointment)
    SET reminder_time = TIMESTAMP(CONCAT(NEW.appointment_date, ' ', NEW.appointment_time)) - INTERVAL 24 HOUR;
    
    -- Generate unique notification ID
    SET notification_uuid = CONCAT('APT_RMD_', NEW.id, '_', UNIX_TIMESTAMP());
    
    -- Only create reminder if appointment is in the future
    IF TIMESTAMP(CONCAT(NEW.appointment_date, ' ', NEW.appointment_time)) > NOW() THEN
        INSERT INTO notifications (
            notification_id,
            patient_id,
            type,
            title,
            message,
            priority,
            delivery_method,
            scheduled_for,
            reference_data,
            created_by
        ) VALUES (
            notification_uuid,
            NEW.patient_id,
            'appointment_reminder',
            'Appointment Reminder',
            'You have an upcoming appointment scheduled.',
            'normal',
            'email,in_app',
            reminder_time,
            JSON_OBJECT(
                'appointment_id', NEW.id,
                'appointment_date', NEW.appointment_date,
                'appointment_time', NEW.appointment_time,
                'doctor_id', NEW.doctor_id,
                'reason', NEW.reason
            ),
            NEW.created_by
        );
    END IF;
END//

-- Trigger for lab result notifications
CREATE TRIGGER create_lab_result_notification
    AFTER UPDATE ON lab_tests
    FOR EACH ROW
BEGIN
    DECLARE notification_uuid VARCHAR(50);
    DECLARE notification_priority VARCHAR(20);
    DECLARE notification_type VARCHAR(50);
    
    -- Only trigger when status changes to 'completed'
    IF OLD.status != 'completed' AND NEW.status = 'completed' THEN
        
        SET notification_uuid = CONCAT('LAB_RESULT_', NEW.id, '_', UNIX_TIMESTAMP());
        SET notification_priority = 'normal';
        SET notification_type = 'lab_result_ready';
        
        -- Check if result contains critical values (simplified check)
        IF NEW.results LIKE '%CRITICAL%' OR NEW.results LIKE '%URGENT%' OR NEW.results LIKE '%ABNORMAL%' THEN
            SET notification_priority = 'critical';
            SET notification_type = 'lab_critical_value';
        END IF;
        
        INSERT INTO notifications (
            notification_id,
            patient_id,
            type,
            title,
            message,
            priority,
            delivery_method,
            reference_data
        ) VALUES (
            notification_uuid,
            NEW.patient_id,
            notification_type,
            CASE 
                WHEN notification_type = 'lab_critical_value' THEN 'CRITICAL: Lab Results Require Immediate Attention'
                ELSE 'Lab Results Available'
            END,
            CASE 
                WHEN notification_type = 'lab_critical_value' THEN 'Your lab results show critical values that require immediate medical attention. Please contact us immediately.'
                ELSE 'Your lab test results are now available for review.'
            END,
            notification_priority,
            CASE 
                WHEN notification_type = 'lab_critical_value' THEN 'email,sms,in_app'
                ELSE 'email,in_app'
            END,
            JSON_OBJECT(
                'lab_test_id', NEW.id,
                'test_name', NEW.test_name,
                'test_category', NEW.test_category,
                'test_date', NEW.test_date,
                'results', NEW.results,
                'normal_range', NEW.normal_range
            )
        );
    END IF;
END//

-- Trigger for billing notifications
CREATE TRIGGER create_billing_notification
    AFTER INSERT ON billing
    FOR EACH ROW
BEGIN
    DECLARE notification_uuid VARCHAR(50);
    
    SET notification_uuid = CONCAT('BILL_NEW_', NEW.id, '_', UNIX_TIMESTAMP());
    
    INSERT INTO notifications (
        notification_id,
        patient_id,
        type,
        title,
        message,
        priority,
        delivery_method,
        reference_data,
        created_by
    ) VALUES (
        notification_uuid,
        NEW.patient_id,
        'bill_generated',
        'New Medical Bill Generated',
        'A new medical bill has been generated for your recent visit.',
        'normal',
        'email,in_app',
        JSON_OBJECT(
            'bill_id', NEW.bill_id,
            'total_amount', NEW.total_amount,
            'due_date', NEW.due_date,
            'bill_date', NEW.bill_date
        ),
        NEW.created_by
    );
END//

DELIMITER ;

-- Create indexes for performance
CREATE INDEX idx_notifications_composite ON notifications(status, scheduled_for, priority);
CREATE INDEX idx_templates_lookup ON notification_templates(notification_type, delivery_method, is_active);
CREATE INDEX idx_preferences_user ON notification_preferences(user_id, email_enabled, sms_enabled, in_app_enabled);
CREATE INDEX idx_delivery_tracking ON notification_delivery_log(notification_id, delivery_status, attempted_at);
CREATE INDEX idx_email_queue_processing ON email_queue(status, scheduled_for, priority);
CREATE INDEX idx_sms_queue_processing ON sms_queue(status, scheduled_for, priority);

-- Success message
SELECT 'Notifications system database schema created successfully!' as message;
