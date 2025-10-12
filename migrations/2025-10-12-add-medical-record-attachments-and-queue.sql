-- Migration: add medical_record_attachments, visit_queue and queue_logs tables
-- Run this in the clinic_cms database

CREATE TABLE IF NOT EXISTS medical_record_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    medical_record_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    uploaded_by INT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS visit_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NULL,
    patient_id INT NULL,
    status ENUM('queued','called','served','cancelled') DEFAULT 'queued',
    queued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    called_at DATETIME NULL,
    served_at DATETIME NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS queue_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_queue_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    user_id INT,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_queue_id) REFERENCES visit_queue(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

SELECT 'medical_record_attachments and queue tables created' as message;
