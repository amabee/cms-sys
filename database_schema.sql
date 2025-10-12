-- Clinic Management System Database Schema
-- Created: October 2025
-- This is a comprehensive database schema for the clinic management system

-- Create database
CREATE DATABASE IF NOT EXISTS clinic_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinic_cms;

-- Users table (for system users - doctors, staff, admin)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'doctor', 'nurse', 'receptionist') NOT NULL DEFAULT 'receptionist',
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Patients table
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    blood_group VARCHAR(5),
    allergies TEXT,
    medical_history TEXT,
    insurance_info TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctors table
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    doctor_id VARCHAR(20) UNIQUE NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(50),
    qualification TEXT,
    experience_years INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    is_available BOOLEAN DEFAULT TRUE,
    profile_image VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Appointments table
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id VARCHAR(20) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    reason TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Medical records table
CREATE TABLE medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_id VARCHAR(20) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    visit_date DATE NOT NULL,
    chief_complaint TEXT,
    diagnosis TEXT,
    treatment TEXT,
    prescription TEXT,
    vital_signs JSON,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- Lab tests table
CREATE TABLE lab_tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_id VARCHAR(20) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_category VARCHAR(50),
    test_date DATE NOT NULL,
    sample_collected_date DATETIME,
    results TEXT,
    normal_range VARCHAR(100),
    status ENUM('ordered', 'sample_collected', 'in_progress', 'completed', 'cancelled') DEFAULT 'ordered',
    lab_technician VARCHAR(100),
    report_file VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Medications table
CREATE TABLE medications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    medication_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    generic_name VARCHAR(100),
    strength VARCHAR(50),
    form VARCHAR(50), -- tablet, capsule, syrup, injection, etc.
    manufacturer VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0.00,
    stock_quantity INT DEFAULT 0,
    minimum_stock INT DEFAULT 10,
    expiry_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Prescriptions table
CREATE TABLE prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id VARCHAR(20) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    medication_id INT NOT NULL,
    dosage VARCHAR(100),
    frequency VARCHAR(100),
    duration VARCHAR(100),
    instructions TEXT,
    quantity INT DEFAULT 1,
    status ENUM('active', 'completed', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE
);

-- Billing table
CREATE TABLE billing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id VARCHAR(20) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    appointment_id INT,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    lab_charges DECIMAL(10,2) DEFAULT 0.00,
    medication_charges DECIMAL(10,2) DEFAULT 0.00,
    other_charges DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'insurance', 'online', 'cheque') DEFAULT 'cash',
    bill_date DATE NOT NULL,
    due_date DATE,
    payment_date DATE NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Patient portal credentials (separate from main users table for security)
CREATE TABLE patient_portal_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- System audit log
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- System settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctor schedules table
CREATE TABLE doctor_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    max_patients INT DEFAULT 20,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, first_name, last_name, role) 
VALUES ('admin', 'admin@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin');

-- Insert sample doctors
INSERT INTO users (username, email, password, first_name, last_name, role, phone) VALUES
('dr.smith', 'dr.smith@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'doctor', '(555) 123-1001'),
('dr.johnson', 'dr.johnson@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 'doctor', '(555) 123-1002'),
('dr.brown', 'dr.brown@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Brown', 'doctor', '(555) 123-1003');

INSERT INTO doctors (user_id, doctor_id, specialization, license_number, qualification, experience_years, consultation_fee) VALUES
(2, 'DOC001', 'General Medicine', 'MD123456', 'MBBS, MD', 10, 150.00),
(3, 'DOC002', 'Pediatrics', 'MD123457', 'MBBS, MD Pediatrics', 8, 200.00),
(4, 'DOC003', 'Cardiology', 'MD123458', 'MBBS, DM Cardiology', 12, 300.00);

-- Insert sample medications
INSERT INTO medications (medication_code, name, generic_name, strength, form, manufacturer, price, stock_quantity) VALUES
('MED001', 'Paracetamol', 'Acetaminophen', '500mg', 'Tablet', 'Generic Pharma', 0.50, 1000),
('MED002', 'Ibuprofen', 'Ibuprofen', '400mg', 'Tablet', 'Pain Relief Inc', 0.75, 500),
('MED003', 'Amoxicillin', 'Amoxicillin', '500mg', 'Capsule', 'Antibiotic Corp', 2.00, 200),
('MED004', 'Cetirizine', 'Cetirizine HCl', '10mg', 'Tablet', 'Allergy Solutions', 1.25, 300);

-- Insert sample patients
INSERT INTO patients (patient_id, first_name, last_name, email, phone, date_of_birth, gender, address) VALUES
('PAT001', 'John', 'Doe', 'john.doe@email.com', '(555) 234-5678', '1985-06-15', 'male', '123 Main Street, City, State 12345'),
('PAT002', 'Jane', 'Smith', 'jane.smith@email.com', '(555) 234-5679', '1990-03-22', 'female', '456 Oak Avenue, City, State 12345'),
('PAT003', 'Mike', 'Wilson', 'mike.wilson@email.com', '(555) 234-5680', '1978-11-08', 'male', '789 Pine Road, City, State 12345');

-- Insert default system settings
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('clinic_name', 'HealthCare Clinic', 'string', 'Name of the clinic', TRUE),
('clinic_address', '123 Health Street, Medical District, City, State 12345', 'string', 'Clinic address', TRUE),
('clinic_phone', '(555) 123-4567', 'string', 'Main clinic phone number', TRUE),
('clinic_email', 'info@healthcareclinic.com', 'string', 'Clinic email address', TRUE),
('appointment_duration', '30', 'number', 'Default appointment duration in minutes', FALSE),
('working_hours_start', '08:00', 'string', 'Clinic opening time', TRUE),
('working_hours_end', '20:00', 'string', 'Clinic closing time', TRUE),
('working_days', '["monday","tuesday","wednesday","thursday","friday","saturday"]', 'json', 'Working days of the week', TRUE),
('max_appointments_per_day', '50', 'number', 'Maximum appointments per day', FALSE),
('enable_patient_portal', 'true', 'boolean', 'Enable patient portal access', TRUE);

-- Insert doctor schedules
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, max_patients) VALUES
(1, 'monday', '09:00:00', '17:00:00', 16),
(1, 'tuesday', '09:00:00', '17:00:00', 16),
(1, 'wednesday', '09:00:00', '17:00:00', 16),
(1, 'thursday', '09:00:00', '17:00:00', 16),
(1, 'friday', '09:00:00', '17:00:00', 16),
(1, 'saturday', '09:00:00', '13:00:00', 8),

(2, 'monday', '10:00:00', '18:00:00', 16),
(2, 'tuesday', '10:00:00', '18:00:00', 16),
(2, 'wednesday', '10:00:00', '18:00:00', 16),
(2, 'thursday', '10:00:00', '18:00:00', 16),
(2, 'friday', '10:00:00', '18:00:00', 16),

(3, 'tuesday', '08:00:00', '16:00:00', 16),
(3, 'wednesday', '08:00:00', '16:00:00', 16),
(3, 'thursday', '08:00:00', '16:00:00', 16),
(3, 'friday', '08:00:00', '16:00:00', 16),
(3, 'saturday', '08:00:00', '12:00:00', 8);

-- Create indexes for better performance
CREATE INDEX idx_patients_patient_id ON patients(patient_id);
CREATE INDEX idx_patients_email ON patients(email);
CREATE INDEX idx_patients_phone ON patients(phone);
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);
CREATE INDEX idx_appointments_doctor ON appointments(doctor_id);
CREATE INDEX idx_medical_records_patient ON medical_records(patient_id);
CREATE INDEX idx_medical_records_doctor ON medical_records(doctor_id);
CREATE INDEX idx_medical_records_date ON medical_records(visit_date);
CREATE INDEX idx_lab_tests_patient ON lab_tests(patient_id);
CREATE INDEX idx_lab_tests_status ON lab_tests(status);
CREATE INDEX idx_prescriptions_patient ON prescriptions(patient_id);
CREATE INDEX idx_prescriptions_doctor ON prescriptions(doctor_id);
CREATE INDEX idx_billing_patient ON billing(patient_id);
CREATE INDEX idx_billing_status ON billing(payment_status);
CREATE INDEX idx_billing_date ON billing(bill_date);
CREATE INDEX idx_audit_log_user ON audit_log(user_id);
CREATE INDEX idx_audit_log_action ON audit_log(action);
CREATE INDEX idx_audit_log_created ON audit_log(created_at);

-- Create views for commonly used queries
CREATE VIEW patient_summary AS
SELECT 
    p.id,
    p.patient_id,
    CONCAT(p.first_name, ' ', p.last_name) as full_name,
    p.email,
    p.phone,
    p.date_of_birth,
    p.gender,
    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
    p.is_active,
    COUNT(DISTINCT a.id) as total_appointments,
    COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed_appointments,
    MAX(a.appointment_date) as last_visit_date
FROM patients p
LEFT JOIN appointments a ON p.id = a.patient_id
GROUP BY p.id;

CREATE VIEW doctor_summary AS
SELECT 
    d.id,
    d.doctor_id,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    d.specialization,
    d.qualification,
    d.experience_years,
    d.consultation_fee,
    d.is_available,
    COUNT(DISTINCT a.id) as total_appointments,
    COUNT(DISTINCT CASE WHEN a.appointment_date = CURDATE() THEN a.id END) as today_appointments
FROM doctors d
LEFT JOIN users u ON d.user_id = u.id
LEFT JOIN appointments a ON d.id = a.doctor_id
GROUP BY d.id;

CREATE VIEW appointment_summary AS
SELECT 
    a.id,
    a.appointment_id,
    a.appointment_date,
    a.appointment_time,
    a.status,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    p.patient_id,
    p.phone as patient_phone,
    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
    d.specialization,
    a.reason
FROM appointments a
JOIN patients p ON a.patient_id = p.id
JOIN doctors d ON a.doctor_id = d.id
JOIN users u ON d.user_id = u.id;

-- Triggers for auto-generating IDs
DELIMITER //

CREATE TRIGGER generate_patient_id
    BEFORE INSERT ON patients
    FOR EACH ROW
BEGIN
    IF NEW.patient_id IS NULL OR NEW.patient_id = '' THEN
        SET NEW.patient_id = CONCAT('PAT', LPAD((SELECT COALESCE(MAX(CAST(SUBSTRING(patient_id, 4) AS UNSIGNED)), 0) + 1 FROM patients WHERE patient_id REGEXP '^PAT[0-9]+$'), 3, '0'));
    END IF;
END//

CREATE TRIGGER generate_appointment_id
    BEFORE INSERT ON appointments
    FOR EACH ROW
BEGIN
    IF NEW.appointment_id IS NULL OR NEW.appointment_id = '' THEN
        SET NEW.appointment_id = CONCAT('APT', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD((SELECT COUNT(*) + 1 FROM appointments WHERE DATE(created_at) = CURDATE()), 3, '0'));
    END IF;
END//

CREATE TRIGGER generate_doctor_id
    BEFORE INSERT ON doctors
    FOR EACH ROW
BEGIN
    IF NEW.doctor_id IS NULL OR NEW.doctor_id = '' THEN
        SET NEW.doctor_id = CONCAT('DOC', LPAD((SELECT COALESCE(MAX(CAST(SUBSTRING(doctor_id, 4) AS UNSIGNED)), 0) + 1 FROM doctors WHERE doctor_id REGEXP '^DOC[0-9]+$'), 3, '0'));
    END IF;
END//

CREATE TRIGGER calculate_bill_totals
    BEFORE INSERT ON billing
    FOR EACH ROW
BEGIN
    SET NEW.total_amount = NEW.consultation_fee + NEW.lab_charges + NEW.medication_charges + NEW.other_charges;
    SET NEW.total_amount = NEW.total_amount - NEW.discount_amount + NEW.tax_amount;
    SET NEW.balance_amount = NEW.total_amount - NEW.paid_amount;
END//

CREATE TRIGGER update_bill_totals
    BEFORE UPDATE ON billing
    FOR EACH ROW
BEGIN
    SET NEW.total_amount = NEW.consultation_fee + NEW.lab_charges + NEW.medication_charges + NEW.other_charges;
    SET NEW.total_amount = NEW.total_amount - NEW.discount_amount + NEW.tax_amount;
    SET NEW.balance_amount = NEW.total_amount - NEW.paid_amount;
    
    IF NEW.balance_amount <= 0 THEN
        SET NEW.payment_status = 'paid';
    ELSEIF OLD.paid_amount != NEW.paid_amount AND NEW.paid_amount > 0 THEN
        SET NEW.payment_status = 'partial';
    END IF;
END//

DELIMITER ;

-- Success message
SELECT 'Clinic Management System database created successfully!' as message;
