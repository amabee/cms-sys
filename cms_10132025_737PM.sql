-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 13, 2025 at 11:36 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int NOT NULL,
  `appointment_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled','no_show') COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `reason`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 'APT20251012001', 7, 1, '2025-10-13', '09:30:00', 'scheduled', 'PA CHECKUP SA EDOY', NULL, 1, '2025-10-12 14:28:25', '2025-10-12 14:28:25');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `generate_appointment_id` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
    IF NEW.appointment_id IS NULL OR NEW.appointment_id = '' THEN
        SET NEW.appointment_id = CONCAT('APT', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD((SELECT COUNT(*) + 1 FROM appointments WHERE DATE(created_at) = CURDATE()), 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `appointment_summary`
-- (See below for the actual view)
--
CREATE TABLE `appointment_summary` (
`id` int
,`appointment_id` varchar(20)
,`appointment_date` date
,`appointment_time` time
,`status` enum('scheduled','in_progress','completed','cancelled','no_show')
,`patient_name` varchar(101)
,`patient_id` varchar(20)
,`patient_phone` varchar(20)
,`doctor_name` varchar(101)
,`specialization` varchar(100)
,`reason` text
);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'AUTH_ERROR', NULL, NULL, NULL, '{\"message\": \"DB error during authenticate\"}', NULL, NULL, '2025-10-12 12:15:46'),
(2, NULL, 'AUTH_ERROR', NULL, NULL, NULL, '{\"message\": \"DB error during authenticate\"}', NULL, NULL, '2025-10-12 12:16:02'),
(3, NULL, 'AUTH_ERROR', NULL, NULL, NULL, '{\"message\": \"DB error during authenticate\"}', NULL, NULL, '2025-10-12 12:16:50'),
(4, NULL, 'AUTH_ERROR', NULL, NULL, NULL, '{\"message\": \"DB error during authenticate\"}', NULL, NULL, '2025-10-12 12:17:42'),
(5, NULL, 'AUTH_ERROR', NULL, NULL, NULL, '{\"message\": \"DB error during authenticate\"}', NULL, NULL, '2025-10-12 12:18:07'),
(6, 1, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-12 12:18:35'),
(7, 1, 'LOGOUT', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-12 12:21:03'),
(8, 1, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-12 12:21:20'),
(9, 1, 'create', 'lab_tests', 1, '[]', '{\"id\": \"\", \"notes\": \"For CBC Count\", \"doctor_id\": \"1\", \"test_date\": \"2025-10-30\", \"test_name\": \"CBC\", \"patient_id\": \"7\", \"test_category\": \"Blood\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-12 15:40:06'),
(10, 1, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-13 11:04:41');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int NOT NULL,
  `bill_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `appointment_id` int DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT '0.00',
  `lab_charges` decimal(10,2) DEFAULT '0.00',
  `medication_charges` decimal(10,2) DEFAULT '0.00',
  `other_charges` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `balance_amount` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('pending','partial','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_method` enum('cash','card','insurance','online','cheque') COLLATE utf8mb4_unicode_ci DEFAULT 'cash',
  `bill_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `billing`
--
DELIMITER $$
CREATE TRIGGER `calculate_bill_totals` BEFORE INSERT ON `billing` FOR EACH ROW BEGIN
    SET NEW.total_amount = NEW.consultation_fee + NEW.lab_charges + NEW.medication_charges + NEW.other_charges;
    SET NEW.total_amount = NEW.total_amount - NEW.discount_amount + NEW.tax_amount;
    SET NEW.balance_amount = NEW.total_amount - NEW.paid_amount;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_bill_totals` BEFORE UPDATE ON `billing` FOR EACH ROW BEGIN
    SET NEW.total_amount = NEW.consultation_fee + NEW.lab_charges + NEW.medication_charges + NEW.other_charges;
    SET NEW.total_amount = NEW.total_amount - NEW.discount_amount + NEW.tax_amount;
    SET NEW.balance_amount = NEW.total_amount - NEW.paid_amount;
    
    IF NEW.balance_amount <= 0 THEN
        SET NEW.payment_status = 'paid';
    ELSEIF OLD.paid_amount != NEW.paid_amount AND NEW.paid_amount > 0 THEN
        SET NEW.payment_status = 'partial';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `doctor_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualification` text COLLATE utf8mb4_unicode_ci,
  `experience_years` int DEFAULT '0',
  `consultation_fee` decimal(10,2) DEFAULT '0.00',
  `is_available` tinyint(1) DEFAULT '1',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `doctor_id`, `specialization`, `license_number`, `qualification`, `experience_years`, `consultation_fee`, `is_available`, `profile_image`, `bio`, `created_at`, `updated_at`) VALUES
(1, 2, 'DOC001', 'General Medicine', 'MD123456', 'MBBS, MD', 10, 150.00, 1, NULL, NULL, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(2, 3, 'DOC002', 'Pediatrics', 'MD123457', 'MBBS, MD Pediatrics', 8, 200.00, 1, NULL, NULL, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(3, 4, 'DOC003', 'Cardiology', 'MD123458', 'MBBS, DM Cardiology', 12, 300.00, 1, NULL, NULL, '2025-10-12 05:59:07', '2025-10-12 05:59:07');

--
-- Triggers `doctors`
--
DELIMITER $$
CREATE TRIGGER `generate_doctor_id` BEFORE INSERT ON `doctors` FOR EACH ROW BEGIN
    IF NEW.doctor_id IS NULL OR NEW.doctor_id = '' THEN
        SET NEW.doctor_id = CONCAT('DOC', LPAD((SELECT COALESCE(MAX(CAST(SUBSTRING(doctor_id, 4) AS UNSIGNED)), 0) + 1 FROM doctors WHERE doctor_id REGEXP '^DOC[0-9]+$'), 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `max_patients` int DEFAULT '20',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `is_available`, `max_patients`, `created_at`, `updated_at`) VALUES
(1, 1, 'monday', '09:00:00', '17:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(2, 1, 'tuesday', '09:00:00', '17:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(3, 1, 'wednesday', '09:00:00', '17:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(4, 1, 'thursday', '09:00:00', '17:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(5, 1, 'friday', '09:00:00', '17:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(6, 1, 'saturday', '09:00:00', '13:00:00', 1, 8, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(7, 2, 'monday', '10:00:00', '18:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(8, 2, 'tuesday', '10:00:00', '18:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(9, 2, 'wednesday', '10:00:00', '18:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(10, 2, 'thursday', '10:00:00', '18:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(11, 2, 'friday', '10:00:00', '18:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(12, 3, 'tuesday', '08:00:00', '16:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(13, 3, 'wednesday', '08:00:00', '16:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(14, 3, 'thursday', '08:00:00', '16:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(15, 3, 'friday', '08:00:00', '16:00:00', 1, 16, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(16, 3, 'saturday', '08:00:00', '12:00:00', 1, 8, '2025-10-12 05:59:07', '2025-10-12 05:59:07');

-- --------------------------------------------------------

--
-- Stand-in structure for view `doctor_summary`
-- (See below for the actual view)
--
CREATE TABLE `doctor_summary` (
`id` int
,`doctor_id` varchar(20)
,`full_name` varchar(101)
,`specialization` varchar(100)
,`qualification` text
,`experience_years` int
,`consultation_fee` decimal(10,2)
,`is_available` tinyint(1)
,`total_appointments` bigint
,`today_appointments` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

CREATE TABLE `lab_results` (
  `id` int NOT NULL,
  `lab_test_id` int NOT NULL,
  `recorded_by` int DEFAULT NULL,
  `recorded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `results` text COLLATE utf8mb4_unicode_ci,
  `normal_range` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `id` int NOT NULL,
  `test_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `test_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `test_date` date NOT NULL,
  `sample_collected_date` datetime DEFAULT NULL,
  `results` text COLLATE utf8mb4_unicode_ci,
  `normal_range` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ordered','sample_collected','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'ordered',
  `lab_technician` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`id`, `test_id`, `patient_id`, `doctor_id`, `test_name`, `test_category`, `test_date`, `sample_collected_date`, `results`, `normal_range`, `status`, `lab_technician`, `report_file`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'TST001', 7, 1, 'CBC', 'Blood', '2025-10-30', NULL, NULL, NULL, 'ordered', NULL, NULL, 'For CBC Count', '2025-10-12 15:40:06', '2025-10-12 15:40:06');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int NOT NULL,
  `record_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_id` int DEFAULT NULL,
  `visit_date` date NOT NULL,
  `chief_complaint` text COLLATE utf8mb4_unicode_ci,
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `treatment` text COLLATE utf8mb4_unicode_ci,
  `prescription` text COLLATE utf8mb4_unicode_ci,
  `vital_signs` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_record_attachments`
--

CREATE TABLE `medical_record_attachments` (
  `id` int NOT NULL,
  `medical_record_id` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `id` int NOT NULL,
  `medication_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generic_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `strength` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `stock_quantity` int DEFAULT '0',
  `minimum_stock` int DEFAULT '10',
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medications`
--

INSERT INTO `medications` (`id`, `medication_code`, `name`, `generic_name`, `strength`, `form`, `manufacturer`, `price`, `stock_quantity`, `minimum_stock`, `expiry_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'MED001', 'Paracetamol', 'Acetaminophen', '500mg', 'Tablet', 'Generic Pharma', 0.50, 1000, 10, NULL, 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(2, 'MED002', 'Ibuprofen', 'Ibuprofen', '400mg', 'Tablet', 'Pain Relief Inc', 0.75, 500, 10, NULL, 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(3, 'MED003', 'Amoxicillin', 'Amoxicillin', '500mg', 'Capsule', 'Antibiotic Corp', 2.00, 200, 10, NULL, 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(4, 'MED004', 'Cetirizine', 'Cetirizine HCl', '10mg', 'Tablet', 'Allergy Solutions', 1.25, 300, 10, NULL, 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int NOT NULL,
  `patient_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_group` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `medical_history` text COLLATE utf8mb4_unicode_ci,
  `insurance_info` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_id`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `blood_group`, `allergies`, `medical_history`, `insurance_info`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'PAT001', 'John', 'Doe', 'john.doe@email.com', '(555) 234-5678', '1985-06-15', 'male', '123 Main Street, City, State 12345', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(2, 'PAT002', 'Jane', 'Smith', 'jane.smith@email.com', '(555) 234-5679', '1990-03-22', 'female', '456 Oak Avenue, City, State 12345', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(3, 'PAT003', 'Mike', 'Wilson', 'mike.wilson@email.com', '(555) 234-5680', '1978-11-08', 'male', '789 Pine Road, City, State 12345', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(4, 'PAT004', 'Cole', 'Mckenzie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-12 13:52:00', '2025-10-12 13:52:00'),
(5, 'PAT005', 'Harding', 'Santana', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-12 13:55:18', '2025-10-12 13:55:18'),
(6, 'PAT006', 'Harriet', 'Roberts', 'ruki@mailinator.com', '+1 (652) 533-5452', '2015-10-26', 'female', 'Expedita consequat', 'Katell Kirby', '+1 (842) 551-5585', 'A+', 'Voluptas ex debitis', NULL, 'Debitis iste repelle', 1, '2025-10-12 13:59:08', '2025-10-12 13:59:08'),
(7, 'PAT007', 'Abel', 'Mcbride', 'hizenanov@mailinator.com', '+1 (286) 704-4177', '2005-03-19', 'female', 'Deleniti exercitatio', 'Forrest Norman', '+1 (433) 988-2328', 'O+', 'Aut inventore ad del', NULL, 'Duis excepturi dolors', 1, '2025-10-12 14:02:12', '2025-10-12 14:10:38');

--
-- Triggers `patients`
--
DELIMITER $$
CREATE TRIGGER `generate_patient_id` BEFORE INSERT ON `patients` FOR EACH ROW BEGIN
    IF NEW.patient_id IS NULL OR NEW.patient_id = '' THEN
        SET NEW.patient_id = CONCAT('PAT', LPAD((SELECT COALESCE(MAX(CAST(SUBSTRING(patient_id, 4) AS UNSIGNED)), 0) + 1 FROM patients WHERE patient_id REGEXP '^PAT[0-9]+$'), 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_portal_users`
--

CREATE TABLE `patient_portal_users` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `patient_summary`
-- (See below for the actual view)
--
CREATE TABLE `patient_summary` (
`id` int
,`patient_id` varchar(20)
,`full_name` varchar(101)
,`email` varchar(100)
,`phone` varchar(20)
,`date_of_birth` date
,`gender` enum('male','female','other')
,`age` bigint
,`is_active` tinyint(1)
,`total_appointments` bigint
,`completed_appointments` bigint
,`last_visit_date` date
);

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int NOT NULL,
  `prescription_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_id` int DEFAULT NULL,
  `medication_id` int NOT NULL,
  `dosage` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `quantity` int DEFAULT '1',
  `status` enum('active','completed','discontinued') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_logs`
--

CREATE TABLE `queue_logs` (
  `id` int NOT NULL,
  `visit_queue_id` int NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','number','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'clinic_name', 'Polymedic Clinic', 'string', 'Name of the clinic', 1, '2025-10-12 05:59:07', '2025-10-12 06:05:13'),
(2, 'clinic_address', '123 Health Street, Medical District, City, State 12345', 'string', 'Clinic address', 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(3, 'clinic_phone', '(555) 123-4567', 'string', 'Main clinic phone number', 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(4, 'clinic_email', 'info@healthcareclinic.com', 'string', 'Clinic email address', 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(5, 'appointment_duration', '30', 'number', 'Default appointment duration in minutes', 0, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(6, 'working_hours_start', '08:00', 'string', 'Clinic opening time', 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(7, 'working_hours_end', '20:00', 'string', 'Clinic closing time', 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(8, 'working_days', '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', 'json', 'Working days of the week', 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(9, 'max_appointments_per_day', '50', 'number', 'Maximum appointments per day', 0, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(10, 'enable_patient_portal', 'true', 'boolean', 'Enable patient portal access', 1, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(11, 'clinic_logo', 'clinic_logo_1760273830_8bf6fd7b9ee0.jpg', 'string', 'Hospital Logo Image', 1, '2025-10-12 12:24:06', '2025-10-12 12:57:10'),
(12, 'clinic_website', 'https://www.cms-sys.dev', 'string', NULL, 0, '2025-10-12 12:56:41', '2025-10-12 12:56:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','doctor','nurse','receptionist','secretary') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'receptionist',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `role`, `phone`, `profile_image`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', NULL, 'https://avatar.iran.liara.run/public', 1, '2025-10-13 11:04:41', '2025-10-12 05:59:07', '2025-10-13 11:04:41'),
(2, 'dr.smith', 'dr.smith@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'doctor', '(555) 123-1001', 'https://avatar.iran.liara.run/public', 1, NULL, '2025-10-12 05:59:07', '2025-10-12 12:20:57'),
(3, 'dr.johnson', 'dr.johnson@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 'doctor', '(555) 123-1002', 'https://avatar.iran.liara.run/public', 1, NULL, '2025-10-12 05:59:07', '2025-10-12 12:20:58'),
(4, 'sec.brown', 'sec.brown@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Brown', 'secretary', '(555) 123-1003', 'https://avatar.iran.liara.run/public', 1, NULL, '2025-10-12 05:59:07', '2025-10-12 12:20:59');

-- --------------------------------------------------------

--
-- Table structure for table `visit_queue`
--

CREATE TABLE `visit_queue` (
  `id` int NOT NULL,
  `appointment_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `status` enum('queued','called','served','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'queued',
  `queued_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `called_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_id` (`appointment_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_appointments_date` (`appointment_date`),
  ADD KEY `idx_appointments_status` (`status`),
  ADD KEY `idx_appointments_patient` (`patient_id`),
  ADD KEY `idx_appointments_doctor` (`doctor_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_log_user` (`user_id`),
  ADD KEY `idx_audit_log_action` (`action`),
  ADD KEY `idx_audit_log_created` (`created_at`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bill_id` (`bill_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_billing_patient` (`patient_id`),
  ADD KEY `idx_billing_status` (`payment_status`),
  ADD KEY `idx_billing_date` (`bill_date`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doctor_id` (`doctor_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_doctor_day` (`doctor_id`,`day_of_week`);

--
-- Indexes for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lab_test_id` (`lab_test_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `test_id` (`test_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `idx_lab_tests_patient` (`patient_id`),
  ADD KEY `idx_lab_tests_status` (`status`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_id` (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_medical_records_patient` (`patient_id`),
  ADD KEY `idx_medical_records_doctor` (`doctor_id`),
  ADD KEY `idx_medical_records_date` (`visit_date`);

--
-- Indexes for table `medical_record_attachments`
--
ALTER TABLE `medical_record_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medical_record_id` (`medical_record_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medication_code` (`medication_code`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_id` (`patient_id`),
  ADD KEY `idx_patients_patient_id` (`patient_id`),
  ADD KEY `idx_patients_email` (`email`),
  ADD KEY `idx_patients_phone` (`phone`);

--
-- Indexes for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prescription_id` (`prescription_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `medication_id` (`medication_id`),
  ADD KEY `idx_prescriptions_patient` (`patient_id`),
  ADD KEY `idx_prescriptions_doctor` (`doctor_id`);

--
-- Indexes for table `queue_logs`
--
ALTER TABLE `queue_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visit_queue_id` (`visit_queue_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visit_queue`
--
ALTER TABLE `visit_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `lab_results`
--
ALTER TABLE `lab_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_record_attachments`
--
ALTER TABLE `medical_record_attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_logs`
--
ALTER TABLE `queue_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visit_queue`
--
ALTER TABLE `visit_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `appointment_summary`
--
DROP TABLE IF EXISTS `appointment_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `appointment_summary`  AS SELECT `a`.`id` AS `id`, `a`.`appointment_id` AS `appointment_id`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`status` AS `status`, concat(`p`.`first_name`,' ',`p`.`last_name`) AS `patient_name`, `p`.`patient_id` AS `patient_id`, `p`.`phone` AS `patient_phone`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `doctor_name`, `d`.`specialization` AS `specialization`, `a`.`reason` AS `reason` FROM (((`appointments` `a` join `patients` `p` on((`a`.`patient_id` = `p`.`id`))) join `doctors` `d` on((`a`.`doctor_id` = `d`.`id`))) join `users` `u` on((`d`.`user_id` = `u`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `doctor_summary`
--
DROP TABLE IF EXISTS `doctor_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `doctor_summary`  AS SELECT `d`.`id` AS `id`, `d`.`doctor_id` AS `doctor_id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `full_name`, `d`.`specialization` AS `specialization`, `d`.`qualification` AS `qualification`, `d`.`experience_years` AS `experience_years`, `d`.`consultation_fee` AS `consultation_fee`, `d`.`is_available` AS `is_available`, count(distinct `a`.`id`) AS `total_appointments`, count(distinct (case when (`a`.`appointment_date` = curdate()) then `a`.`id` end)) AS `today_appointments` FROM ((`doctors` `d` left join `users` `u` on((`d`.`user_id` = `u`.`id`))) left join `appointments` `a` on((`d`.`id` = `a`.`doctor_id`))) GROUP BY `d`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `patient_summary`
--
DROP TABLE IF EXISTS `patient_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `patient_summary`  AS SELECT `p`.`id` AS `id`, `p`.`patient_id` AS `patient_id`, concat(`p`.`first_name`,' ',`p`.`last_name`) AS `full_name`, `p`.`email` AS `email`, `p`.`phone` AS `phone`, `p`.`date_of_birth` AS `date_of_birth`, `p`.`gender` AS `gender`, timestampdiff(YEAR,`p`.`date_of_birth`,curdate()) AS `age`, `p`.`is_active` AS `is_active`, count(distinct `a`.`id`) AS `total_appointments`, count(distinct (case when (`a`.`status` = 'completed') then `a`.`id` end)) AS `completed_appointments`, max(`a`.`appointment_date`) AS `last_visit_date` FROM (`patients` `p` left join `appointments` `a` on((`p`.`id` = `a`.`patient_id`))) GROUP BY `p`.`id` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `billing_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD CONSTRAINT `lab_results_ibfk_1` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_results_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD CONSTRAINT `lab_tests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_tests_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medical_record_attachments`
--
ALTER TABLE `medical_record_attachments`
  ADD CONSTRAINT `medical_record_attachments_ibfk_1` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_record_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  ADD CONSTRAINT `patient_portal_users_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prescriptions_ibfk_4` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `queue_logs`
--
ALTER TABLE `queue_logs`
  ADD CONSTRAINT `queue_logs_ibfk_1` FOREIGN KEY (`visit_queue_id`) REFERENCES `visit_queue` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `queue_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `visit_queue`
--
ALTER TABLE `visit_queue`
  ADD CONSTRAINT `visit_queue_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `visit_queue_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
