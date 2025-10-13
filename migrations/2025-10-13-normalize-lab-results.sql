-- Migration: Normalize lab_results
-- Moves any existing inline results/report data from `lab_tests` into `lab_results` and clears the columns on `lab_tests`.
-- IMPORTANT: Take a full DB backup and ensure uploads/ files are backed up before running.

START TRANSACTION;

-- 0) Add lab_technician column to lab_results if it doesn't exist
-- MySQL doesn't support IF NOT EXISTS for ADD COLUMN, so we check first
SET @col_exists = (
  SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'lab_results' 
    AND COLUMN_NAME = 'lab_technician'
);

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE lab_results ADD COLUMN lab_technician varchar(100) DEFAULT NULL', 
  'SELECT "Column already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1) Insert a lab_results row for each lab_tests row that has inline data and does not already have a lab_results entry
INSERT INTO lab_results (lab_test_id, recorded_by, recorded_at, results, normal_range, report_file, notes, lab_technician, created_at, updated_at)
SELECT
  lt.id AS lab_test_id,
  NULL AS recorded_by,
  COALESCE(lt.sample_collected_date, lt.created_at, NOW()) AS recorded_at,
  lt.results,
  lt.normal_range,
  lt.report_file,
  lt.notes,
  lt.lab_technician,
  NOW() AS created_at,
  NOW() AS updated_at
FROM lab_tests lt
WHERE (lt.results IS NOT NULL OR lt.report_file IS NOT NULL OR lt.normal_range IS NOT NULL OR lt.notes IS NOT NULL OR lt.lab_technician IS NOT NULL)
  AND NOT EXISTS (SELECT 1 FROM lab_results lr WHERE lr.lab_test_id = lt.id);

-- 2) Clear those columns on lab_tests to avoid duplication
UPDATE lab_tests lt
SET results = NULL,
    normal_range = NULL,
    report_file = NULL,
    notes = NULL,
    lab_technician = NULL
WHERE lt.id IN (
  SELECT lab_test_id FROM (
    SELECT DISTINCT lab_test_id FROM lab_results
  ) AS x
);

-- 3) Drop the redundant columns from lab_tests (normalize the schema)
ALTER TABLE lab_tests 
  DROP COLUMN results,
  DROP COLUMN normal_range,
  DROP COLUMN report_file,
  DROP COLUMN notes,
  DROP COLUMN lab_technician;

COMMIT;
