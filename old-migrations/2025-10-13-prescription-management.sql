-- Prescription Management Database Schema
-- Created: 2025-10-13
-- Purpose: Comprehensive prescription and medication management system

-- Medication Database
CREATE TABLE IF NOT EXISTS medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication_name VARCHAR(255) NOT NULL,
    generic_name VARCHAR(255),
    brand_name VARCHAR(255),
    strength VARCHAR(100),
    dosage_form ENUM('tablet', 'capsule', 'liquid', 'injection', 'cream', 'ointment', 'inhaler', 'drops', 'patch', 'spray') NOT NULL,
    unit_of_measure VARCHAR(50),
    manufacturer VARCHAR(255),
    ndc_number VARCHAR(20), -- National Drug Code
    drug_class VARCHAR(100),
    therapeutic_class VARCHAR(100),
    controlled_substance_schedule ENUM('I', 'II', 'III', 'IV', 'V', 'Non-controlled') DEFAULT 'Non-controlled',
    active_ingredients TEXT,
    contraindications TEXT,
    side_effects TEXT,
    warnings TEXT,
    storage_requirements TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    requires_prescription BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_medication_name (medication_name),
    INDEX idx_generic_name (generic_name),
    INDEX idx_drug_class (drug_class),
    INDEX idx_ndc_number (ndc_number),
    INDEX idx_is_active (is_active)
);

-- Drug Interactions Database
CREATE TABLE IF NOT EXISTS drug_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication1_id INT NOT NULL,
    medication2_id INT NOT NULL,
    interaction_type ENUM('major', 'moderate', 'minor', 'contraindicated') NOT NULL,
    severity_level INT CHECK (severity_level BETWEEN 1 AND 10),
    interaction_description TEXT NOT NULL,
    clinical_effects TEXT,
    management_recommendations TEXT,
    evidence_level ENUM('established', 'probable', 'possible', 'theoretical') DEFAULT 'probable',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medication1_id) REFERENCES medications(id) ON DELETE CASCADE,
    FOREIGN KEY (medication2_id) REFERENCES medications(id) ON DELETE CASCADE,
    UNIQUE KEY unique_interaction (medication1_id, medication2_id),
    INDEX idx_interaction_type (interaction_type),
    INDEX idx_severity_level (severity_level)
);

-- Prescription Records
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_number VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    prescription_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled', 'expired', 'discontinued') DEFAULT 'active',
    total_cost DECIMAL(10,2),
    insurance_covered DECIMAL(10,2),
    patient_copay DECIMAL(10,2),
    pharmacy_id INT,
    notes TEXT,
    allergies_checked BOOLEAN DEFAULT FALSE,
    interactions_checked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_prescription_number (prescription_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_prescription_date (prescription_date),
    INDEX idx_status (status)
);

-- Prescription Items (Individual medications in a prescription)
CREATE TABLE IF NOT EXISTS prescription_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    medication_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    dosage_instruction TEXT NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration_days INT,
    refills_allowed INT DEFAULT 0,
    refills_remaining INT DEFAULT 0,
    item_cost DECIMAL(8,2),
    generic_substitution_allowed BOOLEAN DEFAULT TRUE,
    special_instructions TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'completed', 'discontinued', 'on_hold') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE RESTRICT,
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_medication_id (medication_id),
    INDEX idx_status (status)
);

-- Prescription Refills
CREATE TABLE IF NOT EXISTS prescription_refills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_item_id INT NOT NULL,
    refill_number INT NOT NULL,
    refill_date DATE NOT NULL,
    quantity_dispensed DECIMAL(10,2) NOT NULL,
    pharmacist_id INT,
    pharmacy_id INT,
    refill_cost DECIMAL(8,2),
    insurance_claim_number VARCHAR(100),
    patient_copay DECIMAL(8,2),
    notes TEXT,
    dispensed_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_item_id) REFERENCES prescription_items(id) ON DELETE CASCADE,
    FOREIGN KEY (pharmacist_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_prescription_item_id (prescription_item_id),
    INDEX idx_refill_date (refill_date),
    INDEX idx_refill_number (refill_number)
);

-- Pharmacy Information
CREATE TABLE IF NOT EXISTS pharmacies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_name VARCHAR(255) NOT NULL,
    license_number VARCHAR(100),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(50),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'USA',
    phone_number VARCHAR(20),
    fax_number VARCHAR(20),
    email VARCHAR(255),
    contact_person VARCHAR(255),
    pharmacy_type ENUM('retail', 'hospital', 'mail_order', 'specialty', 'compounding') DEFAULT 'retail',
    npi_number VARCHAR(20), -- National Provider Identifier
    dea_number VARCHAR(20), -- Drug Enforcement Administration number
    is_active BOOLEAN DEFAULT TRUE,
    accepts_electronic_prescriptions BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pharmacy_name (pharmacy_name),
    INDEX idx_is_active (is_active),
    INDEX idx_pharmacy_type (pharmacy_type)
);

-- Patient Allergies
CREATE TABLE IF NOT EXISTS patient_allergies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    allergen_type ENUM('medication', 'food', 'environmental', 'other') NOT NULL,
    allergen_name VARCHAR(255) NOT NULL,
    medication_id INT NULL, -- Reference to specific medication if applicable
    reaction_type VARCHAR(255),
    severity ENUM('mild', 'moderate', 'severe', 'life_threatening') NOT NULL,
    symptoms TEXT,
    onset_date DATE,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    verified_by INT,
    verified_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_allergen_type (allergen_type),
    INDEX idx_severity (severity),
    INDEX idx_is_active (is_active)
);

-- Prescription History Tracking
CREATE TABLE IF NOT EXISTS prescription_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    action ENUM('created', 'modified', 'cancelled', 'completed', 'refilled', 'discontinued') NOT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    performed_by INT NOT NULL,
    old_values JSON,
    new_values JSON,
    reason TEXT,
    notes TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_action (action),
    INDEX idx_action_date (action_date)
);

-- Medication Adherence Tracking
CREATE TABLE IF NOT EXISTS medication_adherence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_item_id INT NOT NULL,
    patient_id INT NOT NULL,
    tracking_date DATE NOT NULL,
    doses_prescribed INT NOT NULL,
    doses_taken INT DEFAULT 0,
    adherence_percentage DECIMAL(5,2) GENERATED ALWAYS AS ((doses_taken / doses_prescribed) * 100) STORED,
    missed_doses INT GENERATED ALWAYS AS (doses_prescribed - doses_taken) STORED,
    reasons_for_missed TEXT,
    side_effects_reported TEXT,
    patient_reported BOOLEAN DEFAULT FALSE,
    verified_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_item_id) REFERENCES prescription_items(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_prescription_item_id (prescription_item_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_tracking_date (tracking_date),
    INDEX idx_adherence_percentage (adherence_percentage)
);

-- Prescription Templates (for common prescriptions)
CREATE TABLE IF NOT EXISTS prescription_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    doctor_id INT NOT NULL,
    description TEXT,
    condition_treated VARCHAR(255),
    template_data JSON NOT NULL, -- Stores medication details, dosages, etc.
    usage_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    is_public BOOLEAN DEFAULT FALSE, -- If other doctors can use this template
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_template_name (template_name),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_condition_treated (condition_treated),
    INDEX idx_is_active (is_active)
);

-- Electronic Prescription Logs
CREATE TABLE IF NOT EXISTS e_prescription_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    transmission_id VARCHAR(100),
    pharmacy_id INT NOT NULL,
    transmission_method ENUM('direct', 'surescripts', 'fax', 'phone') DEFAULT 'direct',
    transmission_status ENUM('pending', 'sent', 'delivered', 'failed', 'cancelled') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    failure_reason TEXT,
    retry_count INT DEFAULT 0,
    response_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE RESTRICT,
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_transmission_status (transmission_status),
    INDEX idx_sent_at (sent_at)
);

-- Insert sample medications (skipped - table structure mismatch with existing medications table)
-- The existing medications table uses different column names (name instead of medication_name)
-- Manual data migration recommended if needed

-- Insert sample pharmacies
INSERT INTO pharmacies (pharmacy_name, address_line1, city, state, postal_code, phone_number, email, pharmacy_type, is_active) VALUES
('HealthMart Pharmacy', '123 Main St', 'Springfield', 'IL', '62701', '(217) 555-0123', 'info@healthmart.com', 'retail', TRUE),
('Central Hospital Pharmacy', '456 Hospital Dr', 'Springfield', 'IL', '62702', '(217) 555-0456', 'pharmacy@centralhospital.com', 'hospital', TRUE),
('Express Scripts Mail Order', '789 Distribution Blvd', 'St. Louis', 'MO', '63101', '(314) 555-0789', 'orders@expressscripts.com', 'mail_order', TRUE),
('Specialty Care Pharmacy', '321 Medical Plaza', 'Springfield', 'IL', '62703', '(217) 555-0321', 'info@specialtycare.com', 'specialty', TRUE),
('Community Compounding', '654 Pharmacy Way', 'Springfield', 'IL', '62704', '(217) 555-0654', 'compound@community.com', 'compounding', TRUE)
ON DUPLICATE KEY UPDATE pharmacy_name = VALUES(pharmacy_name);

-- Insert common drug interactions (skipped - requires medication IDs to exist first)
-- INSERT INTO drug_interactions (medication1_id, medication2_id, interaction_type, severity_level, interaction_description, clinical_effects, management_recommendations) VALUES
-- (1, 8, 'moderate', 6, 'Amoxicillin may reduce the effectiveness of Azithromycin', 'Potential reduction in antibiotic efficacy', 'Monitor patient response and consider alternative antibiotics if necessary'),
-- (6, 9, 'major', 8, 'Hydrocodone and Prednisone may increase CNS depression', 'Increased risk of respiratory depression and sedation', 'Use with extreme caution. Monitor respiratory function closely'),
-- (2, 5, 'minor', 3, 'Lisinopril and Atorvastatin may have additive hypotensive effects', 'Possible enhanced blood pressure lowering', 'Monitor blood pressure regularly during concurrent use')
-- ON DUPLICATE KEY UPDATE interaction_description = VALUES(interaction_description);

-- Insert sample prescription templates (skipped - requires doctor IDs and medication IDs to exist first)
-- INSERT INTO prescription_templates (template_name, doctor_id, description, condition_treated, template_data, is_public) VALUES
-- ('Standard Antibiotic Course', 1, 'Standard 7-day antibiotic treatment for bacterial infections', 'Bacterial Infection', 
-- '{"medications": [{"medication_id": 1, "quantity": 21, "unit": "capsules", "dosage": "500mg three times daily", "frequency": "TID", "duration_days": 7}]}', TRUE),
-- ('Hypertension Starter', 1, 'Initial treatment for newly diagnosed hypertension', 'Hypertension', 
-- '{"medications": [{"medication_id": 2, "quantity": 30, "unit": "tablets", "dosage": "10mg once daily", "frequency": "QD", "duration_days": 30}]}', TRUE),
-- ('Diabetes Management', 1, 'Standard metformin therapy for Type 2 diabetes', 'Type 2 Diabetes', 
-- '{"medications": [{"medication_id": 3, "quantity": 60, "unit": "tablets", "dosage": "500mg twice daily", "frequency": "BID", "duration_days": 30}]}', TRUE)
-- ON DUPLICATE KEY UPDATE template_name = VALUES(template_name);

-- Create indexes for performance optimization (commented out - table structure mismatch)
-- CREATE INDEX idx_prescriptions_patient_date ON prescriptions(patient_id, prescription_date);
-- CREATE INDEX idx_prescription_items_medication_status ON prescription_items(medication_id, status);
-- CREATE INDEX idx_patient_allergies_patient_active ON patient_allergies(patient_id, is_active);
-- CREATE INDEX idx_medication_adherence_patient_date ON medication_adherence(patient_id, tracking_date);
-- CREATE INDEX idx_prescription_history_prescription_date ON prescription_history(prescription_id, action_date);

-- Create triggers for automatic prescription number generation (commented out - table structure mismatch)
-- DELIMITER //
--
-- CREATE TRIGGER generate_prescription_number
-- BEFORE INSERT ON prescriptions
-- FOR EACH ROW
-- BEGIN
--     DECLARE next_number INT;
--     DECLARE formatted_number VARCHAR(50);
--     
--     SELECT COALESCE(MAX(CAST(SUBSTRING(prescription_number, 3) AS UNSIGNED)), 0) + 1 
--     INTO next_number 
--     FROM prescriptions 
--     WHERE prescription_number LIKE 'RX%';
--     
--     SET formatted_number = CONCAT('RX', LPAD(next_number, 8, '0'));
--     SET NEW.prescription_number = formatted_number;
-- END//
--
-- CREATE TRIGGER update_refills_remaining
-- AFTER INSERT ON prescription_refills
-- FOR EACH ROW
-- BEGIN
--     UPDATE prescription_items 
--     SET refills_remaining = refills_remaining - 1
--     WHERE id = NEW.prescription_item_id AND refills_remaining > 0;
-- END//
--
-- CREATE TRIGGER prescription_audit_log
-- AFTER UPDATE ON prescriptions
-- FOR EACH ROW
-- BEGIN
--     INSERT INTO prescription_history (
--         prescription_id, action, performed_by, old_values, new_values, reason
--     ) VALUES (
--         NEW.id, 'modified', NEW.created_by,
--         JSON_OBJECT('status', OLD.status, 'updated_at', OLD.updated_at),
--         JSON_OBJECT('status', NEW.status, 'updated_at', NEW.updated_at),
--         'Prescription updated'
--     );
-- END//
--
-- DELIMITER ;

-- Create views for commonly accessed data (commented out - table structure mismatch)
-- CREATE VIEW active_prescriptions AS
-- SELECT 
--     p.id,
--     p.prescription_number,
--     p.prescription_date,
--     p.status,
--     CONCAT(pt.first_name, ' ', pt.last_name) AS patient_name,
--     CONCAT(u.first_name, ' ', u.last_name) AS doctor_name,
--     COUNT(pi.id) AS item_count,
--     p.total_cost
-- FROM prescriptions p
-- JOIN patients pt ON p.patient_id = pt.id
-- JOIN users u ON p.doctor_id = u.id
-- LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
-- WHERE p.status = 'active'
-- GROUP BY p.id;
--
-- CREATE VIEW prescription_summary AS
-- SELECT 
--     p.id AS prescription_id,
--     p.prescription_number,
--     p.prescription_date,
--     p.status AS prescription_status,
--     CONCAT(pt.first_name, ' ', pt.last_name) AS patient_name,
--     CONCAT(u.first_name, ' ', u.last_name) AS doctor_name,
--     pi.id AS item_id,
--     m.medication_name,
--     m.strength,
--     pi.quantity,
--     pi.dosage_instruction,
--     pi.refills_remaining,
--     pi.status AS item_status
-- FROM prescriptions p
-- JOIN patients pt ON p.patient_id = pt.id
-- JOIN users u ON p.doctor_id = u.id
-- JOIN prescription_items pi ON p.id = pi.prescription_id
-- JOIN medications m ON pi.medication_id = m.id
-- WHERE p.status IN ('active', 'completed')
-- ORDER BY p.prescription_date DESC;
