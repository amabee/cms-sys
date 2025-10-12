-- Migration: add lab_results table and migrate existing data
-- Run this in the clinic_cms database

CREATE TABLE IF NOT EXISTS lab_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lab_test_id INT NOT NULL,
    recorded_by INT,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    results TEXT,
    normal_range VARCHAR(100),
    report_file VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_test_id) REFERENCES lab_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Migrate existing results from lab_tests into lab_results
INSERT INTO lab_results (lab_test_id, recorded_by, recorded_at, results, normal_range, report_file, notes, created_at, updated_at)
SELECT id, NULL, COALESCE(sample_collected_date, created_at), results, normal_range, report_file, notes, created_at, updated_at FROM lab_tests WHERE (results IS NOT NULL AND results <> '') OR (report_file IS NOT NULL AND report_file <> '');

-- Null out results/report_file/normal_range/notes in lab_tests to avoid duplication
UPDATE lab_tests SET results = NULL, normal_range = NULL, report_file = NULL, notes = NULL WHERE (results IS NOT NULL AND results <> '') OR (report_file IS NOT NULL AND report_file <> '');

SELECT 'lab_results migration completed' as message;
