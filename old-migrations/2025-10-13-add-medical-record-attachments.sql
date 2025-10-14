-- Medical Record Attachments Table Migration
-- Created: 2025-10-13
-- Purpose: Add support for file attachments to medical records

CREATE TABLE IF NOT EXISTS medical_record_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    medical_record_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT DEFAULT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add indexes for better performance
CREATE INDEX idx_medical_record_attachments_record ON medical_record_attachments(medical_record_id);
CREATE INDEX idx_medical_record_attachments_uploaded ON medical_record_attachments(uploaded_at);
