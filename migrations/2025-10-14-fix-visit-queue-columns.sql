-- Migration: Fix visit_queue table columns
-- Date: 2025-10-14
-- Description: Rename user_id to patient_id and ensure doctor_id column exists

-- First, check if we need to rename user_id to patient_id
-- Drop foreign key constraint on user_id if it exists
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'visit_queue' 
    AND COLUMN_NAME = 'user_id' 
    AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @drop_fk_sql = IF(@constraint_name IS NOT NULL, 
    CONCAT('ALTER TABLE visit_queue DROP FOREIGN KEY ', @constraint_name),
    'SELECT "No foreign key to drop" AS message'
);

PREPARE stmt FROM @drop_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Rename user_id to patient_id if the column exists
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'visit_queue' 
    AND COLUMN_NAME = 'user_id'
);

SET @rename_sql = IF(@col_exists > 0,
    'ALTER TABLE visit_queue CHANGE COLUMN user_id patient_id INT NOT NULL',
    'SELECT "Column user_id does not exist" AS message'
);

PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add doctor_id column if it doesn't exist
SET @doctor_col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'visit_queue' 
    AND COLUMN_NAME = 'doctor_id'
);

SET @add_doctor_sql = IF(@doctor_col_exists = 0,
    'ALTER TABLE visit_queue ADD COLUMN doctor_id INT NULL AFTER appointment_id',
    'SELECT "Column doctor_id already exists" AS message'
);

PREPARE stmt FROM @add_doctor_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraints back (if they don't exist)
SET @fk_patient_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'visit_queue' 
    AND COLUMN_NAME = 'patient_id' 
    AND REFERENCED_TABLE_NAME = 'patients'
);

SET @add_patient_fk_sql = IF(@fk_patient_exists = 0,
    'ALTER TABLE visit_queue ADD CONSTRAINT fk_visit_queue_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE',
    'SELECT "Foreign key fk_visit_queue_patient already exists" AS message'
);

PREPARE stmt FROM @add_patient_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add doctor_id foreign key if doctors table exists and FK doesn't exist
SET @fk_doctor_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'visit_queue' 
    AND COLUMN_NAME = 'doctor_id' 
    AND REFERENCED_TABLE_NAME = 'doctors'
);

SET @doctors_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'doctors'
);

SET @add_doctor_fk_sql = IF(@doctors_table_exists > 0 AND @fk_doctor_exists = 0,
    'ALTER TABLE visit_queue ADD CONSTRAINT fk_visit_queue_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_visit_queue_doctor already exists or doctors table does not exist" AS message'
);

PREPARE stmt FROM @add_doctor_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes if they don't exist (with proper error handling)
SET @index1_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'visit_queue' 
    AND INDEX_NAME = 'idx_patient_queue'
);

SET @create_index1 = IF(@index1_exists = 0,
    'CREATE INDEX idx_patient_queue ON visit_queue(patient_id, status)',
    'SELECT "Index idx_patient_queue already exists" AS message'
);

PREPARE stmt FROM @create_index1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index2_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'visit_queue' 
    AND INDEX_NAME = 'idx_doctor_queue'
);

SET @create_index2 = IF(@index2_exists = 0,
    'CREATE INDEX idx_doctor_queue ON visit_queue(doctor_id, status)',
    'SELECT "Index idx_doctor_queue already exists" AS message'
);

PREPARE stmt FROM @create_index2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index3_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'visit_queue' 
    AND INDEX_NAME = 'idx_status_queued'
);

SET @create_index3 = IF(@index3_exists = 0,
    'CREATE INDEX idx_status_queued ON visit_queue(status, queued_at)',
    'SELECT "Index idx_status_queued already exists" AS message'
);

PREPARE stmt FROM @create_index3;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Log the migration
SELECT 'Migration 2025-10-14-fix-visit-queue-columns.sql completed successfully' AS status;
