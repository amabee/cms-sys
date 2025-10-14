-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 14, 2025 at 09:49 AM
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
-- Table structure for table `account_lockouts`
--

CREATE TABLE `account_lockouts` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `lockout_reason` enum('FAILED_ATTEMPTS','SUSPICIOUS_ACTIVITY','ADMIN_ACTION') NOT NULL,
  `locked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `unlock_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `unlocked_by` int DEFAULT NULL,
  `unlocked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int NOT NULL,
  `appointment_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled','no_show') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `reason`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 'APT20251012001', 7, 1, '2025-10-13', '09:30:00', 'completed', 'PA CHECKUP SA EDOY', NULL, 1, '2025-10-12 14:28:25', '2025-10-13 12:02:51');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `create_appointment_reminder` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;
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
`appointment_date` date
,`appointment_id` varchar(20)
,`appointment_time` time
,`doctor_name` varchar(101)
,`id` int
,`patient_id` varchar(20)
,`patient_name` varchar(101)
,`patient_phone` varchar(20)
,`reason` text
,`specialization` varchar(100)
,`status` enum('scheduled','in_progress','completed','cancelled','no_show')
);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
(10, 1, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-13 11:04:41'),
(11, NULL, 'login', NULL, NULL, NULL, '{\"message\": \"Failed to update last_login\"}', NULL, NULL, '2025-10-13 23:16:13'),
(12, 1, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-13 23:16:13'),
(13, NULL, 'login', NULL, NULL, NULL, '{\"message\": \"Failed to update last_login\"}', NULL, NULL, '2025-10-14 00:17:11'),
(14, 2, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-14 00:17:11'),
(15, NULL, 'login', NULL, NULL, NULL, '{\"message\": \"Failed to update last_login\"}', NULL, NULL, '2025-10-14 00:35:54'),
(16, 2, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-14 00:35:54'),
(17, 1, 'create', 'lab_tests', 2, '[]', '{\"notes\": \"CBC Testing\", \"priority\": \"normal\", \"doctor_id\": \"1\", \"test_date\": \"2025-10-14\", \"test_name\": \"CBC\", \"patient_id\": \"1\", \"expected_date\": \"2025-10-31\", \"specimen_type\": \"blood\", \"test_category\": \"Hematology\", \"fasting_required\": \"yes\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 01:39:36'),
(18, 1, 'update', 'lab_tests', 2, '{\"id\": 2, \"notes\": \"CBC Testing\", \"status\": \"ordered\", \"results\": null, \"test_id\": \"TST002\", \"doctor_id\": 1, \"test_date\": \"2025-10-14\", \"test_name\": \"CBC\", \"created_at\": \"2025-10-14 09:39:36\", \"patient_id\": 1, \"updated_at\": \"2025-10-14 09:39:36\", \"doctor_name\": \"John Smith\", \"recorded_at\": \"2025-10-14 09:39:36\", \"report_file\": null, \"normal_range\": null, \"patient_code\": \"PAT001\", \"patient_name\": \"John Doe\", \"latest_result\": {\"id\": 2, \"notes\": \"CBC Testing\", \"results\": null, \"created_at\": \"2025-10-14 09:39:36\", \"updated_at\": \"2025-10-14 09:39:36\", \"lab_test_id\": 2, \"recorded_at\": \"2025-10-14 09:39:36\", \"recorded_by\": 1, \"report_file\": null, \"normal_range\": null, \"lab_technician\": null, \"recorded_by_name\": \"System Administrator\"}, \"test_category\": \"Hematology\", \"lab_technician\": null, \"recorded_by_name\": \"System Administrator\", \"sample_collected_date\": null}', '{\"id\": \"2\", \"notes\": \"CBC Testing\", \"doctor_id\": \"1\", \"test_date\": \"2025-10-14\", \"test_name\": \"CBC\", \"patient_id\": \"1\", \"expected_date\": \"2025-10-31\", \"specimen_type\": \"blood\", \"test_category\": \"Hematology\", \"fasting_required\": \"no\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 02:28:12'),
(19, NULL, 'login', NULL, NULL, NULL, '{\"message\": \"Failed to update last_login\"}', NULL, NULL, '2025-10-14 03:25:32'),
(20, 1, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-14 03:25:32'),
(21, NULL, 'login', NULL, NULL, NULL, '{\"message\": \"Failed to update last_login\"}', NULL, NULL, '2025-10-14 05:45:41'),
(22, 1, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-14 05:45:41'),
(23, NULL, 'login', NULL, NULL, NULL, '{\"message\": \"Failed to update last_login\"}', NULL, NULL, '2025-10-14 08:16:19'),
(24, 1, 'LOGIN', NULL, NULL, NULL, '{\"message\": \"auth_event\"}', NULL, NULL, '2025-10-14 08:16:19');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int NOT NULL,
  `bill_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `payment_status` enum('pending','partial','paid','overdue','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_method` enum('cash','card','insurance','online','cheque') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'cash',
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
CREATE TRIGGER `create_billing_notification` AFTER INSERT ON `billing` FOR EACH ROW BEGIN
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
-- Table structure for table `configuration_categories`
--

CREATE TABLE `configuration_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `description` text,
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `access_level` enum('admin','manager','user') DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `configuration_categories`
--

INSERT INTO `configuration_categories` (`id`, `category_name`, `display_name`, `description`, `icon`, `sort_order`, `is_active`, `access_level`, `created_at`) VALUES
(1, 'general', 'General Settings', 'Basic clinic information and general configuration', 'fas fa-cog', 1, 1, 'admin', '2025-10-13 23:52:59'),
(2, 'security', 'Security Settings', 'Security and authentication configuration', 'fas fa-shield-alt', 2, 1, 'admin', '2025-10-13 23:52:59'),
(3, 'appointments', 'Appointment Settings', 'Appointment booking and scheduling configuration', 'fas fa-calendar-alt', 3, 1, 'admin', '2025-10-13 23:52:59'),
(4, 'email', 'Email Configuration', 'Email server and notification settings', 'fas fa-envelope', 4, 1, 'admin', '2025-10-13 23:52:59'),
(5, 'notifications', 'Notification Settings', 'Notification preferences and delivery settings', 'fas fa-bell', 5, 1, 'admin', '2025-10-13 23:52:59'),
(6, 'backup', 'Backup Settings', 'Backup and recovery configuration', 'fas fa-database', 6, 1, 'admin', '2025-10-13 23:52:59'),
(7, 'maintenance', 'Maintenance Settings', 'System maintenance and cleanup settings', 'fas fa-tools', 7, 1, 'admin', '2025-10-13 23:52:59'),
(8, 'reports', 'Report Settings', 'Reporting and analytics configuration', 'fas fa-chart-bar', 8, 1, 'admin', '2025-10-13 23:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `doctor_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualification` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `experience_years` int DEFAULT '0',
  `consultation_fee` decimal(10,2) DEFAULT '0.00',
  `is_available` tinyint(1) DEFAULT '1',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `doctor_id`, `specialization`, `license_number`, `qualification`, `experience_years`, `consultation_fee`, `is_available`, `profile_image`, `bio`, `created_at`, `updated_at`) VALUES
(1, 2, 'DOC001', 'General Medicine', 'MD123456', 'MBBS, MD', 10, 150.00, 1, NULL, NULL, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(2, 3, 'DOC002', 'Pediatrics', 'MD123457', 'MBBS, MD Pediatrics', 8, 200.00, 1, NULL, NULL, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(3, 4, 'DOC003', 'Cardiology', 'MD123458', 'MBBS, DM Cardiology', 12, 300.00, 1, NULL, NULL, '2025-10-12 05:59:07', '2025-10-12 05:59:07'),
(4, 5, 'Dolor exercitationem', 'Voluptatem excepturi', '657', 'Eum itaque nihil qui', 1970, 71.00, 0, 'https://avatar.iran.liara.run/public/boy?username=cedop', 'Est vel ut est cum u', '2025-10-14 09:20:27', '2025-10-14 09:20:27');

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
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
`consultation_fee` decimal(10,2)
,`doctor_id` varchar(20)
,`experience_years` int
,`full_name` varchar(101)
,`id` int
,`is_available` tinyint(1)
,`qualification` text
,`specialization` varchar(100)
,`today_appointments` bigint
,`total_appointments` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `drug_interactions`
--

CREATE TABLE `drug_interactions` (
  `id` int NOT NULL,
  `medication1_id` int NOT NULL,
  `medication2_id` int NOT NULL,
  `interaction_type` enum('major','moderate','minor','contraindicated') NOT NULL,
  `severity_level` int DEFAULT NULL,
  `interaction_description` text NOT NULL,
  `clinical_effects` text,
  `management_recommendations` text,
  `evidence_level` enum('established','probable','possible','theoretical') DEFAULT 'probable',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int NOT NULL,
  `notification_id` varchar(50) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `cc_email` varchar(255) DEFAULT NULL,
  `bcc_email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` text NOT NULL,
  `body_text` text,
  `attachments` json DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `scheduled_for` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('queued','processing','sent','failed') DEFAULT 'queued',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `template_type` enum('notification','reminder','report','system') DEFAULT 'notification',
  `subject` varchar(255) NOT NULL,
  `body_text` text,
  `body_html` text,
  `variables` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `template_name`, `template_type`, `subject`, `body_text`, `body_html`, `variables`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'appointment_confirmation', 'notification', 'Appointment Confirmation - {{clinic_name}}', 'Dear {{patient_name}},\n\nYour appointment has been confirmed:\n\nDate: {{appointment_date}}\nTime: {{appointment_time}}\nDoctor: {{doctor_name}}\n\nPlease arrive 15 minutes early.\n\nBest regards,\n{{clinic_name}}', '<p>Dear {{patient_name}},</p><p>Your appointment has been confirmed:</p><ul><li><strong>Date:</strong> {{appointment_date}}</li><li><strong>Time:</strong> {{appointment_time}}</li><li><strong>Doctor:</strong> {{doctor_name}}</li></ul><p>Please arrive 15 minutes early.</p><p>Best regards,<br>{{clinic_name}}</p>', '[\"patient_name\", \"appointment_date\", \"appointment_time\", \"doctor_name\", \"clinic_name\"]', 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59'),
(2, 'appointment_reminder', 'reminder', 'Appointment Reminder - {{clinic_name}}', 'Dear {{patient_name}},\n\nThis is a reminder of your upcoming appointment:\n\nDate: {{appointment_date}}\nTime: {{appointment_time}}\nDoctor: {{doctor_name}}\n\nPlease contact us if you need to reschedule.\n\nBest regards,\n{{clinic_name}}', '<p>Dear {{patient_name}},</p><p>This is a reminder of your upcoming appointment:</p><ul><li><strong>Date:</strong> {{appointment_date}}</li><li><strong>Time:</strong> {{appointment_time}}</li><li><strong>Doctor:</strong> {{doctor_name}}</li></ul><p>Please contact us if you need to reschedule.</p><p>Best regards,<br>{{clinic_name}}</p>', '[\"patient_name\", \"appointment_date\", \"appointment_time\", \"doctor_name\", \"clinic_name\"]', 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59'),
(3, 'system_backup_report', 'system', 'System Backup Report - {{backup_date}}', 'System backup completed successfully.\n\nBackup Details:\nType: {{backup_type}}\nSize: {{backup_size}}\nLocation: {{backup_location}}\nDuration: {{backup_duration}}\n\nNext scheduled backup: {{next_backup}}', '<p>System backup completed successfully.</p><p><strong>Backup Details:</strong></p><ul><li><strong>Type:</strong> {{backup_type}}</li><li><strong>Size:</strong> {{backup_size}}</li><li><strong>Location:</strong> {{backup_location}}</li><li><strong>Duration:</strong> {{backup_duration}}</li></ul><p><strong>Next scheduled backup:</strong> {{next_backup}}</p>', '[\"backup_date\", \"backup_type\", \"backup_size\", \"backup_location\", \"backup_duration\", \"next_backup\"]', 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `e_prescription_logs`
--

CREATE TABLE `e_prescription_logs` (
  `id` int NOT NULL,
  `prescription_id` int NOT NULL,
  `transmission_id` varchar(100) DEFAULT NULL,
  `pharmacy_id` int NOT NULL,
  `transmission_method` enum('direct','surescripts','fax','phone') DEFAULT 'direct',
  `transmission_status` enum('pending','sent','delivered','failed','cancelled') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text,
  `retry_count` int DEFAULT '0',
  `response_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

CREATE TABLE `lab_results` (
  `id` int NOT NULL,
  `lab_test_id` int NOT NULL,
  `recorded_by` int DEFAULT NULL,
  `recorded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `results` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `normal_range` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lab_technician` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_results`
--

INSERT INTO `lab_results` (`id`, `lab_test_id`, `recorded_by`, `recorded_at`, `results`, `normal_range`, `report_file`, `notes`, `created_at`, `updated_at`, `lab_technician`) VALUES
(2, 2, 1, '2025-10-14 09:39:36', NULL, NULL, NULL, 'CBC Testing', '2025-10-14 01:39:36', '2025-10-14 01:39:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `id` int NOT NULL,
  `test_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `test_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `test_date` date NOT NULL,
  `sample_collected_date` datetime DEFAULT NULL,
  `status` enum('ordered','sample_collected','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'ordered',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`id`, `test_id`, `patient_id`, `doctor_id`, `test_name`, `test_category`, `test_date`, `sample_collected_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 'TST002', 1, 1, 'CBC', 'Hematology', '2025-10-14', NULL, 'ordered', '2025-10-14 01:39:36', '2025-10-14 02:28:59');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `success` tinyint(1) DEFAULT '0',
  `failure_reason` varchar(255) DEFAULT NULL,
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `user_agent`, `success`, `failure_reason`, `attempt_time`) VALUES
(1, 'dr.smith@clinic.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1, NULL, '2025-10-14 00:17:11'),
(2, 'dr.smith@clinic.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1, NULL, '2025-10-14 00:35:54'),
(3, 'admin@clinic.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1, NULL, '2025-10-14 03:25:32'),
(4, 'admin@clinic.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1, NULL, '2025-10-14 05:45:41'),
(5, 'admin@clinic.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1, NULL, '2025-10-14 08:16:19');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int NOT NULL,
  `schedule_id` int DEFAULT NULL,
  `task_name` varchar(255) NOT NULL,
  `status` enum('running','completed','failed','cancelled') DEFAULT 'running',
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` int DEFAULT NULL,
  `result_message` text,
  `error_details` text,
  `resources_affected` json DEFAULT NULL,
  `executed_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_schedules`
--

CREATE TABLE `maintenance_schedules` (
  `id` int NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_type` enum('backup','cleanup','optimization','update','custom') NOT NULL,
  `schedule_type` enum('once','daily','weekly','monthly','quarterly','yearly') DEFAULT 'weekly',
  `schedule_value` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `run_count` int DEFAULT '0',
  `task_configuration` json DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `maintenance_schedules`
--

INSERT INTO `maintenance_schedules` (`id`, `task_name`, `task_type`, `schedule_type`, `schedule_value`, `is_active`, `last_run`, `next_run`, `run_count`, `task_configuration`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Daily Database Backup', 'backup', 'daily', '02:00', 1, NULL, '2025-10-14 23:54:38', 0, '{\"encrypt\": false, \"compress\": true, \"backup_type\": \"database\"}', 1, '2025-10-13 23:52:59', '2025-10-13 23:54:38'),
(2, 'Weekly Full Backup', 'backup', 'weekly', 'sunday:03:00', 1, NULL, '2025-10-20 23:54:38', 0, '{\"encrypt\": true, \"compress\": true, \"backup_type\": \"full\"}', 1, '2025-10-13 23:52:59', '2025-10-13 23:54:38'),
(3, 'Monthly Log Cleanup', 'cleanup', 'monthly', '1:01:00', 1, NULL, '2025-11-12 23:54:38', 0, '{\"cleanup_logs\": true, \"cleanup_temp\": true, \"retention_days\": 90}', 1, '2025-10-13 23:52:59', '2025-10-13 23:54:38'),
(4, 'Database Optimization', 'optimization', 'weekly', 'sunday:04:00', 1, NULL, '2025-10-20 23:54:38', 0, '{\"repair_tables\": false, \"analyze_tables\": true, \"optimize_tables\": true}', 1, '2025-10-13 23:52:59', '2025-10-13 23:54:38'),
(5, 'Daily Database Backup', 'backup', 'daily', '02:00', 1, NULL, '2025-10-14 23:54:38', 0, '{\"encrypt\": false, \"compress\": true, \"backup_type\": \"database\"}', 1, '2025-10-13 23:54:38', '2025-10-13 23:54:38'),
(6, 'Weekly Full Backup', 'backup', 'weekly', 'sunday:03:00', 1, NULL, '2025-10-20 23:54:38', 0, '{\"encrypt\": true, \"compress\": true, \"backup_type\": \"full\"}', 1, '2025-10-13 23:54:38', '2025-10-13 23:54:38'),
(7, 'Monthly Log Cleanup', 'cleanup', 'monthly', '1:01:00', 1, NULL, '2025-11-12 23:54:38', 0, '{\"cleanup_logs\": true, \"cleanup_temp\": true, \"retention_days\": 90}', 1, '2025-10-13 23:54:38', '2025-10-13 23:54:38'),
(8, 'Database Optimization', 'optimization', 'weekly', 'sunday:04:00', 1, NULL, '2025-10-20 23:54:38', 0, '{\"repair_tables\": false, \"analyze_tables\": true, \"optimize_tables\": true}', 1, '2025-10-13 23:54:38', '2025-10-13 23:54:38');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int NOT NULL,
  `record_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_id` int DEFAULT NULL,
  `visit_date` date NOT NULL,
  `chief_complaint` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `diagnosis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `treatment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `prescription` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `vital_signs` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `record_id`, `patient_id`, `doctor_id`, `appointment_id`, `visit_date`, `chief_complaint`, `diagnosis`, `treatment`, `prescription`, `vital_signs`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'MR20251014001', 1, 1, NULL, '2025-10-14', 'Sakit daw ang edoy, tapos medyo ga beat daw ang edoy ug itlog', 'Kuan ni siya, balbautog syndrome yata', 'Vitracin para ma purga', 'Vitracin Forte 500mg 3 times a day', '{\"bmi\": \"110.7\", \"height\": \"73\", \"weight\": \"59\", \"heart_rate\": \"83\", \"pain_level\": \"7\", \"temperature\": \"66\", \"blood_pressure\": \"120/80\", \"respiratory_rate\": \"56\", \"oxygen_saturation\": \"91\"}', 'oks na dayun', '2025-10-14 01:25:50', '2025-10-14 01:25:50');

-- --------------------------------------------------------

--
-- Table structure for table `medical_record_attachments`
--

CREATE TABLE `medical_record_attachments` (
  `id` int NOT NULL,
  `medical_record_id` int NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `medication_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `generic_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `strength` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
-- Table structure for table `medication_adherence`
--

CREATE TABLE `medication_adherence` (
  `id` int NOT NULL,
  `prescription_item_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `tracking_date` date NOT NULL,
  `doses_prescribed` int NOT NULL,
  `doses_taken` int DEFAULT '0',
  `adherence_percentage` decimal(5,2) GENERATED ALWAYS AS (((`doses_taken` / `doses_prescribed`) * 100)) STORED,
  `missed_doses` int GENERATED ALWAYS AS ((`doses_prescribed` - `doses_taken`)) STORED,
  `reasons_for_missed` text,
  `side_effects_reported` text,
  `patient_reported` tinyint(1) DEFAULT '0',
  `verified_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `notification_id` varchar(50) NOT NULL,
  `user_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `type` enum('appointment_reminder','appointment_confirmation','appointment_cancellation','lab_result_ready','lab_critical_value','bill_generated','bill_overdue','payment_received','system_maintenance','system_alert','general_announcement','prescription_ready','medical_record_update') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','normal','high','critical') DEFAULT 'normal',
  `status` enum('pending','sent','delivered','read','failed') DEFAULT 'pending',
  `delivery_method` set('email','sms','in_app','push') NOT NULL DEFAULT 'in_app',
  `reference_data` json DEFAULT NULL,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `email_to` varchar(255) DEFAULT NULL,
  `email_cc` varchar(255) DEFAULT NULL,
  `email_subject` varchar(255) DEFAULT NULL,
  `email_template` varchar(100) DEFAULT NULL,
  `sms_to` varchar(20) DEFAULT NULL,
  `sms_template` varchar(100) DEFAULT NULL,
  `delivery_attempts` int DEFAULT '0',
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `delivery_error` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_delivery_log`
--

CREATE TABLE `notification_delivery_log` (
  `id` int NOT NULL,
  `notification_id` varchar(50) NOT NULL,
  `delivery_method` enum('email','sms','in_app','push') NOT NULL,
  `delivery_status` enum('pending','sent','delivered','failed','bounced') NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `delivery_provider` varchar(100) DEFAULT NULL,
  `provider_message_id` varchar(255) DEFAULT NULL,
  `delivery_response` text,
  `error_message` text,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `patient_id` int DEFAULT NULL,
  `appointment_reminders` tinyint(1) DEFAULT '1',
  `appointment_confirmations` tinyint(1) DEFAULT '1',
  `lab_results` tinyint(1) DEFAULT '1',
  `critical_lab_alerts` tinyint(1) DEFAULT '1',
  `billing_notifications` tinyint(1) DEFAULT '1',
  `system_alerts` tinyint(1) DEFAULT '1',
  `email_enabled` tinyint(1) DEFAULT '1',
  `sms_enabled` tinyint(1) DEFAULT '0',
  `in_app_enabled` tinyint(1) DEFAULT '1',
  `push_enabled` tinyint(1) DEFAULT '1',
  `reminder_hours_before` int DEFAULT '24',
  `quiet_hours_start` time DEFAULT '22:00:00',
  `quiet_hours_end` time DEFAULT '08:00:00',
  `preferred_email` varchar(255) DEFAULT NULL,
  `preferred_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`id`, `user_id`, `patient_id`, `appointment_reminders`, `appointment_confirmations`, `lab_results`, `critical_lab_alerts`, `billing_notifications`, `system_alerts`, `email_enabled`, `sms_enabled`, `in_app_enabled`, `push_enabled`, `reminder_hours_before`, `quiet_hours_start`, `quiet_hours_end`, `preferred_email`, `preferred_phone`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 24, '22:00:00', '08:00:00', NULL, NULL, '2025-10-13 23:36:52', '2025-10-13 23:36:52'),
(2, 3, NULL, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 24, '22:00:00', '08:00:00', NULL, NULL, '2025-10-13 23:36:52', '2025-10-13 23:36:52'),
(3, 2, NULL, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 24, '22:00:00', '08:00:00', NULL, NULL, '2025-10-13 23:36:52', '2025-10-13 23:36:52'),
(4, 4, NULL, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 24, '22:00:00', '08:00:00', NULL, NULL, '2025-10-13 23:36:52', '2025-10-13 23:36:52');

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int NOT NULL,
  `template_code` varchar(100) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `notification_type` enum('appointment_reminder','appointment_confirmation','appointment_cancellation','lab_result_ready','lab_critical_value','bill_generated','bill_overdue','payment_received','system_maintenance','system_alert','general_announcement','prescription_ready','medical_record_update') NOT NULL,
  `delivery_method` enum('email','sms','in_app','push') NOT NULL,
  `subject_template` varchar(255) DEFAULT NULL,
  `message_template` text NOT NULL,
  `available_variables` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_system_template` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `template_code`, `template_name`, `notification_type`, `delivery_method`, `subject_template`, `message_template`, `available_variables`, `is_active`, `is_system_template`, `created_at`, `updated_at`) VALUES
(1, 'appointment_reminder_email', 'Appointment Reminder - Email', 'appointment_reminder', 'email', 'Appointment Reminder - {appointment_date} at {appointment_time}', 'Dear {patient_name},<br><br>This is a friendly reminder that you have an appointment scheduled with Dr. {doctor_name} on {appointment_date} at {appointment_time}.<br><br>Appointment Details:<br>- Date: {appointment_date}<br>- Time: {appointment_time}<br>- Doctor: Dr. {doctor_name}<br>- Reason: {appointment_reason}<br><br>If you need to reschedule or cancel, please contact us at {clinic_phone}.<br><br>Thank you,<br>{clinic_name}AAA', '[\"patient_name\", \"doctor_name\", \"appointment_date\", \"appointment_time\", \"appointment_reason\", \"clinic_name\", \"clinic_phone\"]', 1, 1, '2025-10-13 23:36:52', '2025-10-14 05:34:42'),
(2, 'appointment_reminder_sms', 'Appointment Reminder - SMS', 'appointment_reminder', 'sms', NULL, 'Hi {patient_name}, reminder: You have an appointment with Dr. {doctor_name} on {appointment_date} at {appointment_time}. Contact {clinic_phone} to reschedule. -{clinic_name}', '[\"patient_name\", \"doctor_name\", \"appointment_date\", \"appointment_time\", \"clinic_name\", \"clinic_phone\"]', 1, 1, '2025-10-13 23:36:52', '2025-10-13 23:36:52'),
(3, 'lab_result_ready_email', 'Lab Results Ready - Email', 'lab_result_ready', 'email', 'Your Lab Results are Ready', 'Dear {patient_name},<br><br>Your lab test results for {test_name} are now available.<br><br>Test Details:<br>- Test: {test_name}<br>- Date Collected: {collection_date}<br>- Status: {test_status}<br><br>Please log in to your patient portal or contact our office to review your results.<br><br>If you have any questions, please don\'t hesitate to contact us at {clinic_phone}.<br><br>Best regards,<br>{clinic_name}', '[\"patient_name\", \"test_name\", \"collection_date\", \"test_status\", \"clinic_name\", \"clinic_phone\"]', 1, 1, '2025-10-13 23:36:52', '2025-10-13 23:36:52'),
(4, 'lab_critical_value_email', 'CRITICAL Lab Result Alert - Email', 'lab_critical_value', 'email', 'URGENT: Critical Lab Result - Immediate Attention Required', 'Dear {patient_name},<br><br><strong>URGENT NOTICE:</strong> Your recent lab test has returned a critical value that requires immediate medical attention.<br><br>Test Details:<br>- Test: {test_name}<br>- Critical Value: {test_result}<br>- Normal Range: {normal_range}<br><br><strong>PLEASE CONTACT OUR OFFICE IMMEDIATELY</strong> at {clinic_phone} or visit our emergency contact.<br><br>This is time-sensitive and requires prompt medical evaluation.<br><br>{clinic_name}<br>Emergency Contact: {emergency_phone}', '[\"patient_name\", \"test_name\", \"test_result\", \"normal_range\", \"clinic_name\", \"clinic_phone\", \"emergency_phone\"]', 1, 1, '2025-10-13 23:36:52', '2025-10-13 23:36:52'),
(5, 'bill_generated_email', 'New Bill Generated - Email', 'bill_generated', 'email', 'New Medical Bill - Invoice #{bill_id}', 'Dear {patient_name},<br><br>A new medical bill has been generated for your recent visit.<br><br>Bill Details:<br>- Invoice #: {bill_id}<br>- Date: {bill_date}<br>- Amount: ${total_amount}<br>- Due Date: {due_date}<br><br>You can view and pay your bill online through our patient portal or visit our office.<br><br>Payment Options:<br>- Online: {portal_link}<br>- Phone: {clinic_phone}<br>- In Person: {clinic_address}<br><br>Thank you,<br>{clinic_name}', '[\"patient_name\", \"bill_id\", \"bill_date\", \"total_amount\", \"due_date\", \"portal_link\", \"clinic_name\", \"clinic_phone\", \"clinic_address\"]', 1, 1, '2025-10-13 23:36:52', '2025-10-13 23:36:52'),
(6, 'bill_overdue_email', 'Overdue Payment Reminder - Email', 'bill_overdue', 'email', 'Payment Overdue - Invoice #{bill_id}', 'Dear {patient_name},<br><br>Our records show that payment for Invoice #{bill_id} is now overdue.<br><br>Bill Details:<br>- Invoice #: {bill_id}<br>- Original Due Date: {due_date}<br>- Amount Due: ${balance_amount}<br>- Days Overdue: {days_overdue}<br><br>Please make payment as soon as possible to avoid any service interruption.<br><br>Payment Options:<br>- Online: {portal_link}<br>- Phone: {clinic_phone}<br>- In Person: {clinic_address}<br><br>If you have any questions about this bill, please contact our billing department at {billing_phone}.<br><br>{clinic_name}', '[\"patient_name\", \"bill_id\", \"due_date\", \"balance_amount\", \"days_overdue\", \"portal_link\", \"clinic_name\", \"clinic_phone\", \"clinic_address\", \"billing_phone\"]', 1, 1, '2025-10-13 23:36:52', '2025-10-13 23:36:52'),
(36, 'AppRem-Email', 'Appointment Reminder Email', 'appointment_reminder', 'email', 'Appointment Reminder', 'Dear {patient_name},\n\nThis is to confirm your appointment with [Clinic Name].\n\n📅 **Date:** {appointment_date}\n👨‍⚕️ **Doctor:** {doctor_name}\n🏥 **Location:** {clinic_name}\n\nPlease arrive at least 5 minutes before your scheduled time for check-in and preparation.  \nIf you wish to reschedule or cancel your appointment, kindly contact us at {clinic_phone} or reply to this email.\n\nThank you for choosing {clinic_name}. We look forward to seeing you soon!\n\nWarm regards,  \n{clinic_name}\n[Clinic Email] | [Clinic Phone] | [Clinic Website]\n', '[\"patient_name\", \"doctor_name\", \"appointment_date\", \"appointment_time\", \"appointment_reason\", \"clinic_name\", \"clinic_phone\"]', 1, 0, '2025-10-14 03:58:10', '2025-10-14 03:58:39');

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int NOT NULL,
  `patient_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `emergency_contact_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_group` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `medical_history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `insurance_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
-- Table structure for table `patient_allergies`
--

CREATE TABLE `patient_allergies` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `allergen_type` enum('medication','food','environmental','other') NOT NULL,
  `allergen_name` varchar(255) NOT NULL,
  `medication_id` int DEFAULT NULL,
  `reaction_type` varchar(255) DEFAULT NULL,
  `severity` enum('mild','moderate','severe','life_threatening') NOT NULL,
  `symptoms` text,
  `onset_date` date DEFAULT NULL,
  `notes` text,
  `is_active` tinyint(1) DEFAULT '1',
  `verified_by` int DEFAULT NULL,
  `verified_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_appointment_requests`
--

CREATE TABLE `patient_appointment_requests` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `requested_doctor_id` int DEFAULT NULL,
  `appointment_type` enum('consultation','follow_up','emergency','routine_checkup','specialist') NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time_start` time DEFAULT NULL,
  `preferred_time_end` time DEFAULT NULL,
  `alternative_dates` json DEFAULT NULL,
  `reason` text NOT NULL,
  `urgency` enum('routine','urgent','emergency') DEFAULT 'routine',
  `status` enum('pending','approved','rejected','scheduled','cancelled') DEFAULT 'pending',
  `admin_notes` text,
  `processed_by` int DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `scheduled_appointment_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_appointment_requests`
--

INSERT INTO `patient_appointment_requests` (`id`, `patient_id`, `requested_doctor_id`, `appointment_type`, `preferred_date`, `preferred_time_start`, `preferred_time_end`, `alternative_dates`, `reason`, `urgency`, `status`, `admin_notes`, `processed_by`, `processed_at`, `scheduled_appointment_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'follow_up', '2025-10-21', '09:00:00', NULL, NULL, 'Follow-up for recent blood work results and medication adjustment', 'routine', 'pending', NULL, NULL, NULL, NULL, '2025-10-13 23:45:40', '2025-10-13 23:45:40'),
(2, 2, 1, 'consultation', '2025-10-17', '14:00:00', NULL, NULL, 'New patient consultation for ongoing back pain issues', 'urgent', 'pending', NULL, NULL, NULL, NULL, '2025-10-13 23:45:40', '2025-10-13 23:45:40');

--
-- Triggers `patient_appointment_requests`
--
DELIMITER $$
CREATE TRIGGER `after_appointment_request_insert` AFTER INSERT ON `patient_appointment_requests` FOR EACH ROW BEGIN
    INSERT INTO patient_portal_activity_log (patient_id, activity_type, activity_description)
    VALUES (NEW.patient_id, 'book_appointment', CONCAT('Requested appointment for ', NEW.preferred_date));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_document_access_log`
--

CREATE TABLE `patient_document_access_log` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `document_type` enum('medical_record','lab_result','prescription','billing','report') NOT NULL,
  `document_id` int NOT NULL,
  `action` enum('view','download','print') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `access_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `patient_document_access_log`
--
DELIMITER $$
CREATE TRIGGER `after_document_access_insert` AFTER INSERT ON `patient_document_access_log` FOR EACH ROW BEGIN
    INSERT INTO patient_portal_activity_log (patient_id, activity_type, activity_description)
    VALUES (NEW.patient_id, 'document_download', CONCAT('Accessed ', NEW.document_type, ' document'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_health_goals`
--

CREATE TABLE `patient_health_goals` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `goal_type` enum('weight_loss','weight_gain','exercise','medication_adherence','blood_pressure','blood_sugar','custom') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `target_value` decimal(10,2) DEFAULT NULL,
  `target_unit` varchar(50) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT '0.00',
  `status` enum('active','completed','paused','cancelled') DEFAULT 'active',
  `created_by_doctor` tinyint(1) DEFAULT '0',
  `doctor_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_health_goals`
--

INSERT INTO `patient_health_goals` (`id`, `patient_id`, `goal_type`, `title`, `description`, `target_value`, `target_unit`, `target_date`, `current_value`, `progress_percentage`, `status`, `created_by_doctor`, `doctor_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'weight_loss', 'Lose 20 pounds', 'Doctor recommended weight loss for better health', 180.00, 'lbs', '2026-01-12', 200.00, 0.00, 'active', 0, 1, '2025-10-13 23:45:40', '2025-10-13 23:45:40'),
(2, 1, 'blood_pressure', 'Lower Blood Pressure', 'Maintain blood pressure below 130/80', 130.00, 'mmHg', '2025-12-13', 145.00, 0.00, 'active', 0, 1, '2025-10-13 23:45:40', '2025-10-13 23:45:40'),
(3, 2, 'exercise', 'Daily Walking', 'Walk at least 30 minutes daily', 30.00, 'minutes', '2025-11-13', 15.00, 0.00, 'active', 0, NULL, '2025-10-13 23:45:40', '2025-10-13 23:45:40');

-- --------------------------------------------------------

--
-- Table structure for table `patient_health_progress`
--

CREATE TABLE `patient_health_progress` (
  `id` int NOT NULL,
  `goal_id` int NOT NULL,
  `recorded_value` decimal(10,2) NOT NULL,
  `notes` text,
  `recorded_date` date NOT NULL,
  `recorded_time` time DEFAULT NULL,
  `mood_rating` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_messages`
--

CREATE TABLE `patient_messages` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `sender_type` enum('patient','provider') NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_type` enum('patient','provider') NOT NULL,
  `recipient_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('general','appointment','medical','billing','prescription') DEFAULT 'general',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `parent_message_id` int DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_messages`
--

INSERT INTO `patient_messages` (`id`, `patient_id`, `sender_type`, `sender_id`, `recipient_type`, `recipient_id`, `subject`, `message`, `message_type`, `priority`, `is_read`, `read_at`, `parent_message_id`, `attachment_path`, `created_at`, `updated_at`) VALUES
(1, 1, 'patient', 1, 'provider', 1, 'Question about my recent lab results', 'I received my lab results but have some questions about the cholesterol levels. Could you please explain what this means for my health?', 'medical', 'normal', 0, NULL, NULL, NULL, '2025-10-13 23:45:40', '2025-10-13 23:45:40'),
(2, 2, 'provider', 1, 'patient', 2, 'Follow-up appointment reminder', 'This is a reminder that you have a follow-up appointment scheduled for next week. Please bring your current medications list.', 'appointment', 'normal', 0, NULL, NULL, NULL, '2025-10-13 23:45:40', '2025-10-13 23:45:40');

--
-- Triggers `patient_messages`
--
DELIMITER $$
CREATE TRIGGER `after_patient_message_insert` AFTER INSERT ON `patient_messages` FOR EACH ROW BEGIN
    IF NEW.sender_type = 'patient' THEN
        INSERT INTO patient_portal_activity_log (patient_id, activity_type, activity_description)
        VALUES (NEW.patient_id, 'message_sent', CONCAT('Sent message: ', LEFT(NEW.subject, 50)));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_portal_activity_log`
--

CREATE TABLE `patient_portal_activity_log` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `activity_type` enum('login','logout','view_records','book_appointment','message_sent','document_download','profile_update','payment_made') NOT NULL,
  `activity_description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `session_id` varchar(128) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_portal_activity_log`
--

INSERT INTO `patient_portal_activity_log` (`id`, `patient_id`, `activity_type`, `activity_description`, `ip_address`, `user_agent`, `session_id`, `created_at`) VALUES
(1, 1, 'message_sent', 'Sent message: Question about my recent lab results', NULL, NULL, NULL, '2025-10-13 23:45:40'),
(2, 1, 'book_appointment', 'Requested appointment for 2025-10-21', NULL, NULL, NULL, '2025-10-13 23:45:40'),
(3, 2, 'book_appointment', 'Requested appointment for 2025-10-17', NULL, NULL, NULL, '2025-10-13 23:45:40');

-- --------------------------------------------------------

--
-- Table structure for table `patient_portal_feedback`
--

CREATE TABLE `patient_portal_feedback` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `category` enum('bug_report','feature_request','usability','general','complaint','compliment') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `rating` int DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `browser_info` text,
  `screenshot_path` varchar(500) DEFAULT NULL,
  `status` enum('open','in_review','resolved','closed') DEFAULT 'open',
  `admin_response` text,
  `responded_by` int DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_portal_preferences`
--

CREATE TABLE `patient_portal_preferences` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `email_notifications` tinyint(1) DEFAULT '1',
  `sms_notifications` tinyint(1) DEFAULT '1',
  `appointment_reminders` tinyint(1) DEFAULT '1',
  `lab_result_notifications` tinyint(1) DEFAULT '1',
  `billing_notifications` tinyint(1) DEFAULT '1',
  `language_preference` varchar(10) DEFAULT 'en',
  `timezone` varchar(50) DEFAULT 'America/New_York',
  `theme_preference` enum('light','dark','auto') DEFAULT 'light',
  `dashboard_layout` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_portal_preferences`
--

INSERT INTO `patient_portal_preferences` (`id`, `patient_id`, `email_notifications`, `sms_notifications`, `appointment_reminders`, `lab_result_notifications`, `billing_notifications`, `language_preference`, `timezone`, `theme_preference`, `dashboard_layout`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 1, 'en', 'America/New_York', 'light', NULL, '2025-10-13 23:40:46', '2025-10-13 23:40:46'),
(2, 2, 1, 1, 1, 1, 1, 'en', 'America/New_York', 'light', NULL, '2025-10-13 23:40:46', '2025-10-13 23:40:46'),
(3, 3, 1, 1, 1, 1, 1, 'en', 'America/New_York', 'light', NULL, '2025-10-13 23:40:46', '2025-10-13 23:40:46'),
(4, 4, 1, 1, 1, 1, 1, 'en', 'America/New_York', 'light', NULL, '2025-10-13 23:40:46', '2025-10-13 23:40:46'),
(5, 5, 1, 1, 1, 1, 1, 'en', 'America/New_York', 'light', NULL, '2025-10-13 23:40:46', '2025-10-13 23:40:46'),
(6, 6, 1, 1, 1, 1, 1, 'en', 'America/New_York', 'light', NULL, '2025-10-13 23:40:46', '2025-10-13 23:40:46'),
(7, 7, 1, 1, 1, 1, 1, 'en', 'America/New_York', 'light', NULL, '2025-10-13 23:40:46', '2025-10-13 23:40:46');

-- --------------------------------------------------------

--
-- Table structure for table `patient_portal_users`
--

CREATE TABLE `patient_portal_users` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_sessions`
--

CREATE TABLE `patient_sessions` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `patient_summary`
-- (See below for the actual view)
--
CREATE TABLE `patient_summary` (
`age` bigint
,`completed_appointments` bigint
,`date_of_birth` date
,`email` varchar(100)
,`full_name` varchar(101)
,`gender` enum('male','female','other')
,`id` int
,`is_active` tinyint(1)
,`last_visit_date` date
,`patient_id` varchar(20)
,`phone` varchar(20)
,`total_appointments` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `pharmacies`
--

CREATE TABLE `pharmacies` (
  `id` int NOT NULL,
  `pharmacy_name` varchar(255) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `phone_number` varchar(20) DEFAULT NULL,
  `fax_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `pharmacy_type` enum('retail','hospital','mail_order','specialty','compounding') DEFAULT 'retail',
  `npi_number` varchar(20) DEFAULT NULL,
  `dea_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `accepts_electronic_prescriptions` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pharmacies`
--

INSERT INTO `pharmacies` (`id`, `pharmacy_name`, `license_number`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `phone_number`, `fax_number`, `email`, `contact_person`, `pharmacy_type`, `npi_number`, `dea_number`, `is_active`, `accepts_electronic_prescriptions`, `created_at`, `updated_at`) VALUES
(1, 'HealthMart Pharmacy', NULL, '123 Main St', NULL, 'Springfield', 'IL', '62701', 'USA', '(217) 555-0123', NULL, 'info@healthmart.com', NULL, 'retail', NULL, NULL, 1, 1, '2025-10-13 23:47:53', '2025-10-13 23:47:53'),
(2, 'Central Hospital Pharmacy', NULL, '456 Hospital Dr', NULL, 'Springfield', 'IL', '62702', 'USA', '(217) 555-0456', NULL, 'pharmacy@centralhospital.com', NULL, 'hospital', NULL, NULL, 1, 1, '2025-10-13 23:47:53', '2025-10-13 23:47:53'),
(3, 'Express Scripts Mail Order', NULL, '789 Distribution Blvd', NULL, 'St. Louis', 'MO', '63101', 'USA', '(314) 555-0789', NULL, 'orders@expressscripts.com', NULL, 'mail_order', NULL, NULL, 1, 1, '2025-10-13 23:47:53', '2025-10-13 23:47:53'),
(4, 'Specialty Care Pharmacy', NULL, '321 Medical Plaza', NULL, 'Springfield', 'IL', '62703', 'USA', '(217) 555-0321', NULL, 'info@specialtycare.com', NULL, 'specialty', NULL, NULL, 1, 1, '2025-10-13 23:47:53', '2025-10-13 23:47:53'),
(5, 'Community Compounding', NULL, '654 Pharmacy Way', NULL, 'Springfield', 'IL', '62704', 'USA', '(217) 555-0654', NULL, 'compound@community.com', NULL, 'compounding', NULL, NULL, 1, 1, '2025-10-13 23:47:53', '2025-10-13 23:47:53'),
(6, 'HealthMart Pharmacy', NULL, '123 Main St', NULL, 'Springfield', 'IL', '62701', 'USA', '(217) 555-0123', NULL, 'info@healthmart.com', NULL, 'retail', NULL, NULL, 1, 1, '2025-10-13 23:48:45', '2025-10-13 23:48:45'),
(7, 'Central Hospital Pharmacy', NULL, '456 Hospital Dr', NULL, 'Springfield', 'IL', '62702', 'USA', '(217) 555-0456', NULL, 'pharmacy@centralhospital.com', NULL, 'hospital', NULL, NULL, 1, 1, '2025-10-13 23:48:45', '2025-10-13 23:48:45'),
(8, 'Express Scripts Mail Order', NULL, '789 Distribution Blvd', NULL, 'St. Louis', 'MO', '63101', 'USA', '(314) 555-0789', NULL, 'orders@expressscripts.com', NULL, 'mail_order', NULL, NULL, 1, 1, '2025-10-13 23:48:45', '2025-10-13 23:48:45'),
(9, 'Specialty Care Pharmacy', NULL, '321 Medical Plaza', NULL, 'Springfield', 'IL', '62703', 'USA', '(217) 555-0321', NULL, 'info@specialtycare.com', NULL, 'specialty', NULL, NULL, 1, 1, '2025-10-13 23:48:45', '2025-10-13 23:48:45'),
(10, 'Community Compounding', NULL, '654 Pharmacy Way', NULL, 'Springfield', 'IL', '62704', 'USA', '(217) 555-0654', NULL, 'compound@community.com', NULL, 'compounding', NULL, NULL, 1, 1, '2025-10-13 23:48:45', '2025-10-13 23:48:45'),
(11, 'HealthMart Pharmacy', NULL, '123 Main St', NULL, 'Springfield', 'IL', '62701', 'USA', '(217) 555-0123', NULL, 'info@healthmart.com', NULL, 'retail', NULL, NULL, 1, 1, '2025-10-13 23:49:30', '2025-10-13 23:49:30'),
(12, 'Central Hospital Pharmacy', NULL, '456 Hospital Dr', NULL, 'Springfield', 'IL', '62702', 'USA', '(217) 555-0456', NULL, 'pharmacy@centralhospital.com', NULL, 'hospital', NULL, NULL, 1, 1, '2025-10-13 23:49:30', '2025-10-13 23:49:30'),
(13, 'Express Scripts Mail Order', NULL, '789 Distribution Blvd', NULL, 'St. Louis', 'MO', '63101', 'USA', '(314) 555-0789', NULL, 'orders@expressscripts.com', NULL, 'mail_order', NULL, NULL, 1, 1, '2025-10-13 23:49:30', '2025-10-13 23:49:30'),
(14, 'Specialty Care Pharmacy', NULL, '321 Medical Plaza', NULL, 'Springfield', 'IL', '62703', 'USA', '(217) 555-0321', NULL, 'info@specialtycare.com', NULL, 'specialty', NULL, NULL, 1, 1, '2025-10-13 23:49:30', '2025-10-13 23:49:30'),
(15, 'Community Compounding', NULL, '654 Pharmacy Way', NULL, 'Springfield', 'IL', '62704', 'USA', '(217) 555-0654', NULL, 'compound@community.com', NULL, 'compounding', NULL, NULL, 1, 1, '2025-10-13 23:49:30', '2025-10-13 23:49:30'),
(16, 'HealthMart Pharmacy', NULL, '123 Main St', NULL, 'Springfield', 'IL', '62701', 'USA', '(217) 555-0123', NULL, 'info@healthmart.com', NULL, 'retail', NULL, NULL, 1, 1, '2025-10-13 23:49:59', '2025-10-13 23:49:59'),
(17, 'Central Hospital Pharmacy', NULL, '456 Hospital Dr', NULL, 'Springfield', 'IL', '62702', 'USA', '(217) 555-0456', NULL, 'pharmacy@centralhospital.com', NULL, 'hospital', NULL, NULL, 1, 1, '2025-10-13 23:49:59', '2025-10-13 23:49:59'),
(18, 'Express Scripts Mail Order', NULL, '789 Distribution Blvd', NULL, 'St. Louis', 'MO', '63101', 'USA', '(314) 555-0789', NULL, 'orders@expressscripts.com', NULL, 'mail_order', NULL, NULL, 1, 1, '2025-10-13 23:49:59', '2025-10-13 23:49:59'),
(19, 'Specialty Care Pharmacy', NULL, '321 Medical Plaza', NULL, 'Springfield', 'IL', '62703', 'USA', '(217) 555-0321', NULL, 'info@specialtycare.com', NULL, 'specialty', NULL, NULL, 1, 1, '2025-10-13 23:49:59', '2025-10-13 23:49:59'),
(20, 'Community Compounding', NULL, '654 Pharmacy Way', NULL, 'Springfield', 'IL', '62704', 'USA', '(217) 555-0654', NULL, 'compound@community.com', NULL, 'compounding', NULL, NULL, 1, 1, '2025-10-13 23:49:59', '2025-10-13 23:49:59'),
(21, 'HealthMart Pharmacy', NULL, '123 Main St', NULL, 'Springfield', 'IL', '62701', 'USA', '(217) 555-0123', NULL, 'info@healthmart.com', NULL, 'retail', NULL, NULL, 1, 1, '2025-10-13 23:50:45', '2025-10-13 23:50:45'),
(22, 'Central Hospital Pharmacy', NULL, '456 Hospital Dr', NULL, 'Springfield', 'IL', '62702', 'USA', '(217) 555-0456', NULL, 'pharmacy@centralhospital.com', NULL, 'hospital', NULL, NULL, 1, 1, '2025-10-13 23:50:45', '2025-10-13 23:50:45'),
(23, 'Express Scripts Mail Order', NULL, '789 Distribution Blvd', NULL, 'St. Louis', 'MO', '63101', 'USA', '(314) 555-0789', NULL, 'orders@expressscripts.com', NULL, 'mail_order', NULL, NULL, 1, 1, '2025-10-13 23:50:45', '2025-10-13 23:50:45'),
(24, 'Specialty Care Pharmacy', NULL, '321 Medical Plaza', NULL, 'Springfield', 'IL', '62703', 'USA', '(217) 555-0321', NULL, 'info@specialtycare.com', NULL, 'specialty', NULL, NULL, 1, 1, '2025-10-13 23:50:45', '2025-10-13 23:50:45'),
(25, 'Community Compounding', NULL, '654 Pharmacy Way', NULL, 'Springfield', 'IL', '62704', 'USA', '(217) 555-0654', NULL, 'compound@community.com', NULL, 'compounding', NULL, NULL, 1, 1, '2025-10-13 23:50:45', '2025-10-13 23:50:45'),
(26, 'HealthMart Pharmacy', NULL, '123 Main St', NULL, 'Springfield', 'IL', '62701', 'USA', '(217) 555-0123', NULL, 'info@healthmart.com', NULL, 'retail', NULL, NULL, 1, 1, '2025-10-13 23:51:40', '2025-10-13 23:51:40'),
(27, 'Central Hospital Pharmacy', NULL, '456 Hospital Dr', NULL, 'Springfield', 'IL', '62702', 'USA', '(217) 555-0456', NULL, 'pharmacy@centralhospital.com', NULL, 'hospital', NULL, NULL, 1, 1, '2025-10-13 23:51:40', '2025-10-13 23:51:40'),
(28, 'Express Scripts Mail Order', NULL, '789 Distribution Blvd', NULL, 'St. Louis', 'MO', '63101', 'USA', '(314) 555-0789', NULL, 'orders@expressscripts.com', NULL, 'mail_order', NULL, NULL, 1, 1, '2025-10-13 23:51:40', '2025-10-13 23:51:40'),
(29, 'Specialty Care Pharmacy', NULL, '321 Medical Plaza', NULL, 'Springfield', 'IL', '62703', 'USA', '(217) 555-0321', NULL, 'info@specialtycare.com', NULL, 'specialty', NULL, NULL, 1, 1, '2025-10-13 23:51:40', '2025-10-13 23:51:40'),
(30, 'Community Compounding', NULL, '654 Pharmacy Way', NULL, 'Springfield', 'IL', '62704', 'USA', '(217) 555-0654', NULL, 'compound@community.com', NULL, 'compounding', NULL, NULL, 1, 1, '2025-10-13 23:51:40', '2025-10-13 23:51:40');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int NOT NULL,
  `prescription_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_id` int DEFAULT NULL,
  `medication_id` int NOT NULL,
  `dosage` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `quantity` int DEFAULT '1',
  `status` enum('active','completed','discontinued') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_history`
--

CREATE TABLE `prescription_history` (
  `id` int NOT NULL,
  `prescription_id` int NOT NULL,
  `action` enum('created','modified','cancelled','completed','refilled','discontinued') NOT NULL,
  `action_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `performed_by` int NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `reason` text,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `id` int NOT NULL,
  `prescription_id` int NOT NULL,
  `medication_id` int NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `dosage_instruction` text NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `duration_days` int DEFAULT NULL,
  `refills_allowed` int DEFAULT '0',
  `refills_remaining` int DEFAULT '0',
  `item_cost` decimal(8,2) DEFAULT NULL,
  `generic_substitution_allowed` tinyint(1) DEFAULT '1',
  `special_instructions` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','discontinued','on_hold') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_refills`
--

CREATE TABLE `prescription_refills` (
  `id` int NOT NULL,
  `prescription_item_id` int NOT NULL,
  `refill_number` int NOT NULL,
  `refill_date` date NOT NULL,
  `quantity_dispensed` decimal(10,2) NOT NULL,
  `pharmacist_id` int DEFAULT NULL,
  `pharmacy_id` int DEFAULT NULL,
  `refill_cost` decimal(8,2) DEFAULT NULL,
  `insurance_claim_number` varchar(100) DEFAULT NULL,
  `patient_copay` decimal(8,2) DEFAULT NULL,
  `notes` text,
  `dispensed_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_templates`
--

CREATE TABLE `prescription_templates` (
  `id` int NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `doctor_id` int NOT NULL,
  `description` text,
  `condition_treated` varchar(255) DEFAULT NULL,
  `template_data` json NOT NULL,
  `usage_count` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_logs`
--

CREATE TABLE `queue_logs` (
  `id` int NOT NULL,
  `visit_queue_id` int NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `queue_logs`
--

INSERT INTO `queue_logs` (`id`, `visit_queue_id`, `action`, `user_id`, `details`, `created_at`) VALUES
(1, 1, 'queued', 1, 'Patient added to queue', '2025-10-13 21:01:42'),
(2, 2, 'queued', 1, 'Patient added to queue', '2025-10-14 08:03:00'),
(3, 3, 'queued', 1, 'Patient added to queue', '2025-10-14 08:03:01'),
(4, 4, 'queued', 1, 'Patient added to queue', '2025-10-14 08:24:18');

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `event_type` enum('LOGIN','LOGOUT','LOGIN_FAILED','PASSWORD_CHANGE','ACCOUNT_LOCKED','ACCOUNT_UNLOCKED','PERMISSION_DENIED','SUSPICIOUS_ACTIVITY','SESSION_EXPIRED','DATA_ACCESS','DATA_MODIFICATION','SYSTEM_ACCESS') NOT NULL,
  `event_details` json DEFAULT NULL,
  `risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'LOW',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `event_type`, `event_details`, `risk_level`, `created_at`) VALUES
(1, 2, 'g854kk2hsmojuro2i1sfgr9ej0', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGIN', '{\"username\": \"dr.smith@clinic.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36\"}', 'LOW', '2025-10-14 00:17:11'),
(2, 2, 'nl7irseocgjm1amm1jc1nmolge', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGIN', '{\"username\": \"dr.smith@clinic.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36\"}', 'LOW', '2025-10-14 00:35:54'),
(3, 1, 'meldr6hor1l02b410detcb04a6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGOUT', '{\"session_id\": \"meldr6hor1l02b410detcb04a6\"}', 'LOW', '2025-10-14 03:25:03'),
(4, 1, 'meldr6hor1l02b410detcb04a6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGOUT', '{\"session_id\": \"meldr6hor1l02b410detcb04a6\"}', 'LOW', '2025-10-14 03:25:03'),
(5, 1, 'ro4v87q7o20e3o0l6287o7orjj', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGIN', '{\"username\": \"admin@clinic.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36\"}', 'LOW', '2025-10-14 03:25:32'),
(6, 1, 'ro4v87q7o20e3o0l6287o7orjj', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGOUT', '{\"session_id\": \"ro4v87q7o20e3o0l6287o7orjj\"}', 'LOW', '2025-10-14 05:45:17'),
(7, 1, 'fjq63gtn6cqh5c2n99rvv69qea', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGIN', '{\"username\": \"admin@clinic.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36\"}', 'LOW', '2025-10-14 05:45:41'),
(8, 1, 'fjq63gtn6cqh5c2n99rvv69qea', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGOUT', '{\"session_id\": \"fjq63gtn6cqh5c2n99rvv69qea\"}', 'LOW', '2025-10-14 08:15:42'),
(9, 1, 'fjq63gtn6cqh5c2n99rvv69qea', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGOUT', '{\"session_id\": \"fjq63gtn6cqh5c2n99rvv69qea\"}', 'LOW', '2025-10-14 08:15:42'),
(10, 1, 'suj0tkk0v049u6bk123k0jnj5o', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'LOGIN', '{\"username\": \"admin@clinic.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36\"}', 'LOW', '2025-10-14 08:16:19');

-- --------------------------------------------------------

--
-- Table structure for table `security_settings`
--

CREATE TABLE `security_settings` (
  `id` int NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text,
  `description` text,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `security_settings`
--

INSERT INTO `security_settings` (`id`, `setting_name`, `setting_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'max_login_attempts', '5', 'Maximum failed login attempts before account lockout', NULL, '2025-10-13 23:51:52'),
(2, 'lockout_duration', '900', 'Account lockout duration in seconds (15 minutes)', NULL, '2025-10-13 23:51:52'),
(3, 'session_timeout', '3600', 'Session timeout in seconds (1 hour)', NULL, '2025-10-13 23:51:52'),
(4, 'password_min_length', '8', 'Minimum password length', NULL, '2025-10-13 23:51:52'),
(5, 'password_require_uppercase', '1', 'Require uppercase letters in password', NULL, '2025-10-13 23:51:52'),
(6, 'password_require_lowercase', '1', 'Require lowercase letters in password', NULL, '2025-10-13 23:51:52'),
(7, 'password_require_numbers', '1', 'Require numbers in password', NULL, '2025-10-13 23:51:52'),
(8, 'password_require_symbols', '1', 'Require special characters in password', NULL, '2025-10-13 23:51:52'),
(9, 'password_history_count', '5', 'Number of previous passwords to remember', NULL, '2025-10-13 23:51:52'),
(10, 'force_password_change_days', '90', 'Days before forced password change (0 to disable)', NULL, '2025-10-13 23:51:52'),
(11, 'enable_two_factor', '0', 'Enable two-factor authentication requirement', NULL, '2025-10-13 23:51:52'),
(12, 'login_notification', '1', 'Send email notifications on login', NULL, '2025-10-13 23:51:52'),
(13, 'suspicious_activity_detection', '1', 'Enable automatic suspicious activity detection', NULL, '2025-10-13 23:51:52');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','number','boolean','json') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
-- Table structure for table `sms_queue`
--

CREATE TABLE `sms_queue` (
  `id` int NOT NULL,
  `notification_id` varchar(50) NOT NULL,
  `to_phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `scheduled_for` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('queued','processing','sent','failed') DEFAULT 'queued',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `error_message` text,
  `provider` varchar(100) DEFAULT NULL,
  `provider_message_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_activity`
--

CREATE TABLE `system_activity` (
  `id` int NOT NULL,
  `activity_type` enum('setting_change','backup_created','maintenance_run','user_preference','system_update') NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_activity`
--

INSERT INTO `system_activity` (`id`, `activity_type`, `entity_type`, `entity_id`, `old_value`, `new_value`, `user_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'setting_change', 'system_setting', 'clinic_name', 'Healthcare Clinic', 'Healthcare Clinic', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(2, 'setting_change', 'system_setting', 'clinic_address', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(3, 'setting_change', 'system_setting', 'clinic_phone', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(4, 'setting_change', 'system_setting', 'clinic_email', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(5, 'setting_change', 'system_setting', 'clinic_logo', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(6, 'setting_change', 'system_setting', 'timezone', 'America/New_York', 'America/New_York', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(7, 'setting_change', 'system_setting', 'date_format', 'Y-m-d', 'Y-m-d', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(8, 'setting_change', 'system_setting', 'time_format', 'H:i:s', 'H:i:s', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(9, 'setting_change', 'system_setting', 'session_timeout', '3600', '3600', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(10, 'setting_change', 'system_setting', 'max_login_attempts', '5', '5', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(11, 'setting_change', 'system_setting', 'account_lockout_duration', '1800', '1800', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(12, 'setting_change', 'system_setting', 'password_min_length', '8', '8', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(13, 'setting_change', 'system_setting', 'require_password_complexity', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(14, 'setting_change', 'system_setting', 'enable_two_factor', 'false', 'false', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(15, 'setting_change', 'system_setting', 'appointment_duration', '30', '30', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(16, 'setting_change', 'system_setting', 'advance_booking_limit', '90', '90', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(17, 'setting_change', 'system_setting', 'cancellation_limit', '24', '24', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(18, 'setting_change', 'system_setting', 'enable_online_booking', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(19, 'setting_change', 'system_setting', 'booking_confirmation', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(20, 'setting_change', 'system_setting', 'smtp_host', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(21, 'setting_change', 'system_setting', 'smtp_port', '587', '587', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(22, 'setting_change', 'system_setting', 'smtp_username', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(23, 'setting_change', 'system_setting', 'smtp_password', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(24, 'setting_change', 'system_setting', 'smtp_encryption', 'tls', 'tls', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(25, 'setting_change', 'system_setting', 'from_email', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(26, 'setting_change', 'system_setting', 'from_name', '', '', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(27, 'setting_change', 'system_setting', 'enable_email_notifications', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(28, 'setting_change', 'system_setting', 'enable_sms_notifications', 'false', 'false', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(29, 'setting_change', 'system_setting', 'reminder_advance_time', '24', '24', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(30, 'setting_change', 'system_setting', 'notification_retry_attempts', '3', '3', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(31, 'setting_change', 'system_setting', 'auto_backup_enabled', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(32, 'setting_change', 'system_setting', 'backup_frequency', 'daily', 'daily', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(33, 'setting_change', 'system_setting', 'backup_retention_days', '30', '30', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(34, 'setting_change', 'system_setting', 'backup_location', '/backups', '/backups', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(35, 'setting_change', 'system_setting', 'backup_compression', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(36, 'setting_change', 'system_setting', 'maintenance_window_start', '02:00', '02:00', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(37, 'setting_change', 'system_setting', 'maintenance_window_end', '04:00', '04:00', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(38, 'setting_change', 'system_setting', 'auto_cleanup_logs', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(39, 'setting_change', 'system_setting', 'log_retention_days', '90', '90', NULL, NULL, NULL, '2025-10-13 23:53:28'),
(40, 'setting_change', 'system_setting', 'clinic_name', 'Healthcare Clinic', 'Healthcare Clinic', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(41, 'setting_change', 'system_setting', 'clinic_address', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(42, 'setting_change', 'system_setting', 'clinic_phone', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(43, 'setting_change', 'system_setting', 'clinic_email', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(44, 'setting_change', 'system_setting', 'clinic_logo', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(45, 'setting_change', 'system_setting', 'timezone', 'America/New_York', 'America/New_York', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(46, 'setting_change', 'system_setting', 'date_format', 'Y-m-d', 'Y-m-d', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(47, 'setting_change', 'system_setting', 'time_format', 'H:i:s', 'H:i:s', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(48, 'setting_change', 'system_setting', 'session_timeout', '3600', '3600', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(49, 'setting_change', 'system_setting', 'max_login_attempts', '5', '5', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(50, 'setting_change', 'system_setting', 'account_lockout_duration', '1800', '1800', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(51, 'setting_change', 'system_setting', 'password_min_length', '8', '8', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(52, 'setting_change', 'system_setting', 'require_password_complexity', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(53, 'setting_change', 'system_setting', 'enable_two_factor', 'false', 'false', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(54, 'setting_change', 'system_setting', 'appointment_duration', '30', '30', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(55, 'setting_change', 'system_setting', 'advance_booking_limit', '90', '90', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(56, 'setting_change', 'system_setting', 'cancellation_limit', '24', '24', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(57, 'setting_change', 'system_setting', 'enable_online_booking', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(58, 'setting_change', 'system_setting', 'booking_confirmation', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(59, 'setting_change', 'system_setting', 'smtp_host', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(60, 'setting_change', 'system_setting', 'smtp_port', '587', '587', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(61, 'setting_change', 'system_setting', 'smtp_username', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(62, 'setting_change', 'system_setting', 'smtp_password', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(63, 'setting_change', 'system_setting', 'smtp_encryption', 'tls', 'tls', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(64, 'setting_change', 'system_setting', 'from_email', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(65, 'setting_change', 'system_setting', 'from_name', '', '', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(66, 'setting_change', 'system_setting', 'enable_email_notifications', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(67, 'setting_change', 'system_setting', 'enable_sms_notifications', 'false', 'false', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(68, 'setting_change', 'system_setting', 'reminder_advance_time', '24', '24', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(69, 'setting_change', 'system_setting', 'notification_retry_attempts', '3', '3', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(70, 'setting_change', 'system_setting', 'auto_backup_enabled', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(71, 'setting_change', 'system_setting', 'backup_frequency', 'daily', 'daily', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(72, 'setting_change', 'system_setting', 'backup_retention_days', '30', '30', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(73, 'setting_change', 'system_setting', 'backup_location', '/backups', '/backups', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(74, 'setting_change', 'system_setting', 'backup_compression', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(75, 'setting_change', 'system_setting', 'maintenance_window_start', '02:00', '02:00', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(76, 'setting_change', 'system_setting', 'maintenance_window_end', '04:00', '04:00', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(77, 'setting_change', 'system_setting', 'auto_cleanup_logs', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(78, 'setting_change', 'system_setting', 'log_retention_days', '90', '90', NULL, NULL, NULL, '2025-10-13 23:54:12'),
(79, 'setting_change', 'system_setting', 'clinic_name', 'Healthcare Clinic', 'Healthcare Clinic', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(80, 'setting_change', 'system_setting', 'clinic_address', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(81, 'setting_change', 'system_setting', 'clinic_phone', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(82, 'setting_change', 'system_setting', 'clinic_email', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(83, 'setting_change', 'system_setting', 'clinic_logo', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(84, 'setting_change', 'system_setting', 'timezone', 'America/New_York', 'America/New_York', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(85, 'setting_change', 'system_setting', 'date_format', 'Y-m-d', 'Y-m-d', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(86, 'setting_change', 'system_setting', 'time_format', 'H:i:s', 'H:i:s', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(87, 'setting_change', 'system_setting', 'session_timeout', '3600', '3600', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(88, 'setting_change', 'system_setting', 'max_login_attempts', '5', '5', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(89, 'setting_change', 'system_setting', 'account_lockout_duration', '1800', '1800', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(90, 'setting_change', 'system_setting', 'password_min_length', '8', '8', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(91, 'setting_change', 'system_setting', 'require_password_complexity', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(92, 'setting_change', 'system_setting', 'enable_two_factor', 'false', 'false', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(93, 'setting_change', 'system_setting', 'appointment_duration', '30', '30', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(94, 'setting_change', 'system_setting', 'advance_booking_limit', '90', '90', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(95, 'setting_change', 'system_setting', 'cancellation_limit', '24', '24', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(96, 'setting_change', 'system_setting', 'enable_online_booking', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(97, 'setting_change', 'system_setting', 'booking_confirmation', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(98, 'setting_change', 'system_setting', 'smtp_host', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(99, 'setting_change', 'system_setting', 'smtp_port', '587', '587', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(100, 'setting_change', 'system_setting', 'smtp_username', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(101, 'setting_change', 'system_setting', 'smtp_password', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(102, 'setting_change', 'system_setting', 'smtp_encryption', 'tls', 'tls', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(103, 'setting_change', 'system_setting', 'from_email', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(104, 'setting_change', 'system_setting', 'from_name', '', '', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(105, 'setting_change', 'system_setting', 'enable_email_notifications', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(106, 'setting_change', 'system_setting', 'enable_sms_notifications', 'false', 'false', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(107, 'setting_change', 'system_setting', 'reminder_advance_time', '24', '24', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(108, 'setting_change', 'system_setting', 'notification_retry_attempts', '3', '3', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(109, 'setting_change', 'system_setting', 'auto_backup_enabled', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(110, 'setting_change', 'system_setting', 'backup_frequency', 'daily', 'daily', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(111, 'setting_change', 'system_setting', 'backup_retention_days', '30', '30', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(112, 'setting_change', 'system_setting', 'backup_location', '/backups', '/backups', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(113, 'setting_change', 'system_setting', 'backup_compression', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(114, 'setting_change', 'system_setting', 'maintenance_window_start', '02:00', '02:00', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(115, 'setting_change', 'system_setting', 'maintenance_window_end', '04:00', '04:00', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(116, 'setting_change', 'system_setting', 'auto_cleanup_logs', 'true', 'true', NULL, NULL, NULL, '2025-10-13 23:54:38'),
(117, 'setting_change', 'system_setting', 'log_retention_days', '90', '90', NULL, NULL, NULL, '2025-10-13 23:54:38');

-- --------------------------------------------------------

--
-- Table structure for table `system_backups`
--

CREATE TABLE `system_backups` (
  `id` int NOT NULL,
  `backup_name` varchar(255) NOT NULL,
  `backup_type` enum('full','database','files','configuration') DEFAULT 'full',
  `backup_size` bigint DEFAULT NULL,
  `backup_location` varchar(500) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed','corrupted') DEFAULT 'pending',
  `compression_type` varchar(50) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `error_message` text,
  `metadata` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','number','boolean','json','file') DEFAULT 'string',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `description` text,
  `is_public` tinyint(1) DEFAULT '0',
  `is_editable` tinyint(1) DEFAULT '1',
  `validation_rules` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`, `is_editable`, `validation_rules`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'clinic_name', 'Healthcare Clinic', 'string', 'general', 'Name of the healthcare clinic', 1, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(2, 'clinic_address', '', 'string', 'general', 'Physical address of the clinic', 1, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(3, 'clinic_phone', '', 'string', 'general', 'Main phone number', 1, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(4, 'clinic_email', '', 'string', 'general', 'Main email address', 1, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(5, 'clinic_logo', '', 'file', 'general', 'Clinic logo image file', 1, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(6, 'timezone', 'America/New_York', 'string', 'general', 'System timezone', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(7, 'date_format', 'Y-m-d', 'string', 'general', 'Default date format', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(8, 'time_format', 'H:i:s', 'string', 'general', 'Default time format', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(9, 'session_timeout', '3600', 'number', 'security', 'Session timeout in seconds', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(10, 'max_login_attempts', '5', 'number', 'security', 'Maximum failed login attempts', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(11, 'account_lockout_duration', '1800', 'number', 'security', 'Account lockout duration in seconds', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(12, 'password_min_length', '8', 'number', 'security', 'Minimum password length', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(13, 'require_password_complexity', 'true', 'boolean', 'security', 'Require complex passwords', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(14, 'enable_two_factor', 'false', 'boolean', 'security', 'Enable two-factor authentication', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(15, 'appointment_duration', '30', 'number', 'appointments', 'Default appointment duration in minutes', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(16, 'advance_booking_limit', '90', 'number', 'appointments', 'Maximum days ahead for booking', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(17, 'cancellation_limit', '24', 'number', 'appointments', 'Minimum hours before cancellation', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(18, 'enable_online_booking', 'true', 'boolean', 'appointments', 'Allow online appointment booking', 1, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(19, 'booking_confirmation', 'true', 'boolean', 'appointments', 'Send booking confirmation emails', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(20, 'smtp_host', '', 'string', 'email', 'SMTP server host', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(21, 'smtp_port', '587', 'number', 'email', 'SMTP server port', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(22, 'smtp_username', '', 'string', 'email', 'SMTP username', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(23, 'smtp_password', '', 'string', 'email', 'SMTP password', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(24, 'smtp_encryption', 'tls', 'string', 'email', 'SMTP encryption method', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(25, 'from_email', '', 'string', 'email', 'Default from email address', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(26, 'from_name', '', 'string', 'email', 'Default from name', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(27, 'enable_email_notifications', 'true', 'boolean', 'notifications', 'Enable email notifications', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(28, 'enable_sms_notifications', 'false', 'boolean', 'notifications', 'Enable SMS notifications', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(29, 'reminder_advance_time', '24', 'number', 'notifications', 'Hours before appointment to send reminder', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(30, 'notification_retry_attempts', '3', 'number', 'notifications', 'Number of retry attempts for failed notifications', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(31, 'auto_backup_enabled', 'true', 'boolean', 'backup', 'Enable automatic backups', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(32, 'backup_frequency', 'daily', 'string', 'backup', 'Backup frequency (daily, weekly, monthly)', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(33, 'backup_retention_days', '30', 'number', 'backup', 'Number of days to retain backups', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(34, 'backup_location', '/backups', 'string', 'backup', 'Backup storage location', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(35, 'backup_compression', 'true', 'boolean', 'backup', 'Enable backup compression', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(36, 'maintenance_window_start', '02:00', 'string', 'maintenance', 'Maintenance window start time', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(37, 'maintenance_window_end', '04:00', 'string', 'maintenance', 'Maintenance window end time', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(38, 'auto_cleanup_logs', 'true', 'boolean', 'maintenance', 'Automatically cleanup old logs', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL),
(39, 'log_retention_days', '90', 'number', 'maintenance', 'Number of days to retain logs', 0, 1, NULL, '2025-10-13 23:52:59', '2025-10-13 23:52:59', NULL);

--
-- Triggers `system_settings`
--
DELIMITER $$
CREATE TRIGGER `system_settings_activity_log` AFTER UPDATE ON `system_settings` FOR EACH ROW BEGIN
    INSERT INTO system_activity (
        activity_type, entity_type, entity_id, 
        old_value, new_value, user_id, created_at
    ) VALUES (
        'setting_change', 'system_setting', NEW.setting_key,
        OLD.setting_value, NEW.setting_value, NEW.updated_by, NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','doctor','nurse','receptionist','secretary') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'receptionist',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
(2, 'dr.smith', 'dr.smith@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'doctor', '(555) 123-1001', 'https://avatar.iran.liara.run/public', 1, NULL, '2025-10-12 05:59:07', '2025-10-14 06:09:02'),
(3, 'dr.johnson', 'dr.johnson@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 'doctor', '(555) 123-1002', 'https://avatar.iran.liara.run/public', 1, NULL, '2025-10-12 05:59:07', '2025-10-12 12:20:58'),
(4, 'sec.brown', 'sec.brown@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Brown', 'secretary', '(555) 123-1003', 'https://avatar.iran.liara.run/public', 1, NULL, '2025-10-12 05:59:07', '2025-10-12 12:20:59'),
(5, 'cedop', 'doci@mailinator.com', '$2y$10$8R2sKTElHKNCClMCW37wk.MtQslnylENBZ2XKAezJKPnP7i.TUQv6', 'Kylee', 'Douglas', 'doctor', '+1 (241) 705-2183', 'https://avatar.iran.liara.run/public/boy?username=cedop', 1, NULL, '2025-10-14 09:20:27', '2025-10-14 09:20:27'),
(6, 'vusoqi', 'fikof@mailinator.com', '$2y$10$cQl20CNZ0hWcafeXKIa46.WtUeyYWof6DEOTBR0iZLTRcOXLhOuWe', 'Bryar', 'Norris', 'receptionist', '+1 (538) 826-8272', NULL, 1, NULL, '2025-10-14 09:33:47', '2025-10-14 09:43:07'),
(7, 'gohycojeri', 'qoti@mailinator.com', '$2y$10$tJ3YSO43H0Wdn0Qe4V7J9em/jGuewo7UOTEZtlYURl6GTr3AS84PG', 'Aurelia', 'Jefferson', 'receptionist', '+1 (435) 529-7917', NULL, 1, NULL, '2025-10-14 09:35:41', '2025-10-14 09:35:41'),
(8, 'sypixifody', 'nykoci@mailinator.com', '$2y$10$J4hCKHrwORtu1j1Y8FhRYuC62kF.eHH51NLZfWmh9TtUAjCEC7Qie', 'Galvin', 'Chaney', 'secretary', '+1 (975) 531-2249', NULL, 1, NULL, '2025-10-14 09:48:31', '2025-10-14 09:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text NOT NULL,
  `preference_type` enum('string','number','boolean','json') DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `user_preferences`
--
DELIMITER $$
CREATE TRIGGER `user_preferences_activity_log` AFTER UPDATE ON `user_preferences` FOR EACH ROW BEGIN
    INSERT INTO system_activity (
        activity_type, entity_type, entity_id, 
        old_value, new_value, user_id, created_at
    ) VALUES (
        'user_preference', 'user_preference', CONCAT(NEW.user_id, ':', NEW.preference_key),
        OLD.preference_value, NEW.preference_value, NEW.user_id, NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `session_id`, `user_id`, `ip_address`, `user_agent`, `login_time`, `last_activity`, `is_active`, `expires_at`) VALUES
(1, 'g854kk2hsmojuro2i1sfgr9ej0', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 00:17:11', '2025-10-14 00:35:54', 0, '2025-10-14 01:17:40'),
(2, 'nl7irseocgjm1amm1jc1nmolge', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 00:35:54', '2025-10-14 00:35:54', 1, '2025-10-14 01:35:54'),
(3, 'ro4v87q7o20e3o0l6287o7orjj', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 03:25:32', '2025-10-14 05:45:17', 0, '2025-10-14 05:42:15'),
(4, 'fjq63gtn6cqh5c2n99rvv69qea', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 05:45:41', '2025-10-14 08:15:42', 0, '2025-10-14 06:46:42'),
(5, 'suj0tkk0v049u6bk123k0jnj5o', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 08:16:19', '2025-10-14 08:16:19', 1, '2025-10-14 09:16:19');

-- --------------------------------------------------------

--
-- Table structure for table `visit_queue`
--

CREATE TABLE `visit_queue` (
  `id` int NOT NULL,
  `appointment_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `status` enum('queued','called','served','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'queued',
  `queued_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `called_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visit_queue`
--

INSERT INTO `visit_queue` (`id`, `appointment_id`, `doctor_id`, `patient_id`, `status`, `queued_at`, `called_at`, `served_at`, `notes`, `created_at`) VALUES
(1, NULL, NULL, 7, 'queued', '2025-10-13 21:01:42', NULL, NULL, '', '2025-10-13 13:01:42'),
(2, NULL, NULL, 6, 'queued', '2025-10-14 08:03:00', NULL, NULL, '', '2025-10-14 00:03:00'),
(3, NULL, NULL, 5, 'queued', '2025-10-14 08:03:01', NULL, NULL, '', '2025-10-14 00:03:01'),
(4, NULL, NULL, 1, 'queued', '2025-10-14 08:24:18', NULL, NULL, 'Sakit ang edoy\n\n--- Vital Signs ---\nBP: 120/80\nTemp: 66°C\nPulse: 83 bpm\nResp Rate: 56\nWeight: 59 kg\nHeight: 73 cm\nO2 Sat: 91%\nPain Level: 7/10', '2025-10-14 00:24:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_lockouts`
--
ALTER TABLE `account_lockouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unlocked_by` (`unlocked_by`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`),
  ADD KEY `idx_username_active` (`username`,`is_active`),
  ADD KEY `idx_ip_active` (`ip_address`,`is_active`);

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
-- Indexes for table `configuration_categories`
--
ALTER TABLE `configuration_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_is_active` (`is_active`);

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
-- Indexes for table `drug_interactions`
--
ALTER TABLE `drug_interactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_interaction` (`medication1_id`,`medication2_id`),
  ADD KEY `medication2_id` (`medication2_id`),
  ADD KEY `idx_interaction_type` (`interaction_type`),
  ADD KEY `idx_severity_level` (`severity_level`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_scheduled` (`status`,`scheduled_for`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_notification_id` (`notification_id`),
  ADD KEY `idx_email_queue_processing` (`status`,`scheduled_for`,`priority`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_name` (`template_name`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_template_type` (`template_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `e_prescription_logs`
--
ALTER TABLE `e_prescription_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pharmacy_id` (`pharmacy_id`),
  ADD KEY `idx_prescription_id` (`prescription_id`),
  ADD KEY `idx_transmission_status` (`transmission_status`),
  ADD KEY `idx_sent_at` (`sent_at`);

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
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username_time` (`username`,`attempt_time`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `executed_by` (`executed_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_started_at` (`started_at`),
  ADD KEY `idx_schedule_id` (`schedule_id`);

--
-- Indexes for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_next_run` (`next_run`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_maintenance_schedules_next_run` (`is_active`,`next_run`);

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
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_medical_record_attachments_record` (`medical_record_id`),
  ADD KEY `idx_medical_record_attachments_uploaded` (`uploaded_at`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medication_code` (`medication_code`);

--
-- Indexes for table `medication_adherence`
--
ALTER TABLE `medication_adherence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_prescription_item_id` (`prescription_item_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_tracking_date` (`tracking_date`),
  ADD KEY `idx_adherence_percentage` (`adherence_percentage`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notification_id` (`notification_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_type_status` (`type`,`status`),
  ADD KEY `idx_scheduled_for` (`scheduled_for`),
  ADD KEY `idx_priority_status` (`priority`,`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_notifications_composite` (`status`,`scheduled_for`,`priority`);

--
-- Indexes for table `notification_delivery_log`
--
ALTER TABLE `notification_delivery_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_id` (`notification_id`),
  ADD KEY `idx_status_method` (`delivery_status`,`delivery_method`),
  ADD KEY `idx_attempted_at` (`attempted_at`),
  ADD KEY `idx_delivery_tracking` (`notification_id`,`delivery_status`,`attempted_at`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_prefs` (`user_id`),
  ADD UNIQUE KEY `unique_patient_prefs` (`patient_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_preferences_user` (`user_id`,`email_enabled`,`sms_enabled`,`in_app_enabled`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_code` (`template_code`),
  ADD KEY `idx_type_method` (`notification_type`,`delivery_method`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_templates_lookup` (`notification_type`,`delivery_method`,`is_active`);

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_time` (`user_id`,`created_at`);

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
-- Indexes for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medication_id` (`medication_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_allergen_type` (`allergen_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `patient_appointment_requests`
--
ALTER TABLE `patient_appointment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointment_requests_patient` (`patient_id`),
  ADD KEY `idx_appointment_requests_doctor` (`requested_doctor_id`),
  ADD KEY `idx_appointment_requests_status` (`status`),
  ADD KEY `idx_appointment_requests_date` (`preferred_date`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `scheduled_appointment_id` (`scheduled_appointment_id`);

--
-- Indexes for table `patient_document_access_log`
--
ALTER TABLE `patient_document_access_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_document_access_patient` (`patient_id`),
  ADD KEY `idx_document_access_type` (`document_type`,`document_id`),
  ADD KEY `idx_document_access_timestamp` (`access_timestamp`);

--
-- Indexes for table `patient_health_goals`
--
ALTER TABLE `patient_health_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_health_goals_patient` (`patient_id`),
  ADD KEY `idx_health_goals_type` (`goal_type`),
  ADD KEY `idx_health_goals_status` (`status`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `patient_health_progress`
--
ALTER TABLE `patient_health_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_health_progress_goal` (`goal_id`),
  ADD KEY `idx_health_progress_date` (`recorded_date`);

--
-- Indexes for table `patient_messages`
--
ALTER TABLE `patient_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_messages_patient` (`patient_id`),
  ADD KEY `idx_patient_messages_sender` (`sender_type`,`sender_id`),
  ADD KEY `idx_patient_messages_recipient` (`recipient_type`,`recipient_id`),
  ADD KEY `idx_patient_messages_thread` (`parent_message_id`),
  ADD KEY `idx_patient_messages_created` (`created_at`);

--
-- Indexes for table `patient_portal_activity_log`
--
ALTER TABLE `patient_portal_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_portal_activity_patient` (`patient_id`),
  ADD KEY `idx_portal_activity_type` (`activity_type`),
  ADD KEY `idx_portal_activity_created` (`created_at`);

--
-- Indexes for table `patient_portal_feedback`
--
ALTER TABLE `patient_portal_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_portal_feedback_patient` (`patient_id`),
  ADD KEY `idx_portal_feedback_category` (`category`),
  ADD KEY `idx_portal_feedback_status` (`status`),
  ADD KEY `idx_portal_feedback_created` (`created_at`),
  ADD KEY `responded_by` (`responded_by`);

--
-- Indexes for table `patient_portal_preferences`
--
ALTER TABLE `patient_portal_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patient_sessions`
--
ALTER TABLE `patient_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_patient_sessions_patient` (`patient_id`),
  ADD KEY `idx_patient_sessions_token` (`session_token`),
  ADD KEY `idx_patient_sessions_expires` (`expires_at`);

--
-- Indexes for table `pharmacies`
--
ALTER TABLE `pharmacies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pharmacy_name` (`pharmacy_name`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_pharmacy_type` (`pharmacy_type`);

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
-- Indexes for table `prescription_history`
--
ALTER TABLE `prescription_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `idx_prescription_id` (`prescription_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_action_date` (`action_date`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prescription_id` (`prescription_id`),
  ADD KEY `idx_medication_id` (`medication_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `prescription_refills`
--
ALTER TABLE `prescription_refills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pharmacist_id` (`pharmacist_id`),
  ADD KEY `idx_prescription_item_id` (`prescription_item_id`),
  ADD KEY `idx_refill_date` (`refill_date`),
  ADD KEY `idx_refill_number` (`refill_number`);

--
-- Indexes for table `prescription_templates`
--
ALTER TABLE `prescription_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template_name` (`template_name`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_condition_treated` (`condition_treated`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `queue_logs`
--
ALTER TABLE `queue_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visit_queue_id` (`visit_queue_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_event` (`user_id`,`event_type`),
  ADD KEY `idx_time_risk` (`created_at`,`risk_level`),
  ADD KEY `idx_session` (`session_id`);

--
-- Indexes for table `security_settings`
--
ALTER TABLE `security_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `sms_queue`
--
ALTER TABLE `sms_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_scheduled` (`status`,`scheduled_for`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_notification_id` (`notification_id`),
  ADD KEY `idx_sms_queue_processing` (`status`,`scheduled_for`,`priority`);

--
-- Indexes for table `system_activity`
--
ALTER TABLE `system_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_system_activity_composite` (`activity_type`,`created_at`,`user_id`);

--
-- Indexes for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_backup_type` (`backup_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_system_backups_status_date` (`status`,`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_setting_key` (`setting_key`),
  ADD KEY `idx_system_settings_category_key` (`category`,`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_preference` (`user_id`,`preference_key`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_preferences_composite` (`user_id`,`preference_key`,`updated_at`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `visit_queue`
--
ALTER TABLE `visit_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_patient_queue` (`patient_id`,`status`),
  ADD KEY `idx_doctor_queue` (`doctor_id`,`status`),
  ADD KEY `idx_status_queued` (`status`,`queued_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_lockouts`
--
ALTER TABLE `account_lockouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `configuration_categories`
--
ALTER TABLE `configuration_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `drug_interactions`
--
ALTER TABLE `drug_interactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `e_prescription_logs`
--
ALTER TABLE `e_prescription_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_results`
--
ALTER TABLE `lab_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `medication_adherence`
--
ALTER TABLE `medication_adherence`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_delivery_log`
--
ALTER TABLE `notification_delivery_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_appointment_requests`
--
ALTER TABLE `patient_appointment_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_document_access_log`
--
ALTER TABLE `patient_document_access_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_health_goals`
--
ALTER TABLE `patient_health_goals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient_health_progress`
--
ALTER TABLE `patient_health_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_messages`
--
ALTER TABLE `patient_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_portal_activity_log`
--
ALTER TABLE `patient_portal_activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient_portal_feedback`
--
ALTER TABLE `patient_portal_feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_portal_preferences`
--
ALTER TABLE `patient_portal_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_sessions`
--
ALTER TABLE `patient_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pharmacies`
--
ALTER TABLE `pharmacies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_history`
--
ALTER TABLE `prescription_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_refills`
--
ALTER TABLE `prescription_refills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_templates`
--
ALTER TABLE `prescription_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_logs`
--
ALTER TABLE `queue_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `security_settings`
--
ALTER TABLE `security_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sms_queue`
--
ALTER TABLE `sms_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_activity`
--
ALTER TABLE `system_activity`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `system_backups`
--
ALTER TABLE `system_backups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `visit_queue`
--
ALTER TABLE `visit_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `account_lockouts`
--
ALTER TABLE `account_lockouts`
  ADD CONSTRAINT `account_lockouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `account_lockouts_ibfk_2` FOREIGN KEY (`unlocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `drug_interactions`
--
ALTER TABLE `drug_interactions`
  ADD CONSTRAINT `drug_interactions_ibfk_1` FOREIGN KEY (`medication1_id`) REFERENCES `medications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `drug_interactions_ibfk_2` FOREIGN KEY (`medication2_id`) REFERENCES `medications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD CONSTRAINT `email_queue_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE;

--
-- Constraints for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD CONSTRAINT `email_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `e_prescription_logs`
--
ALTER TABLE `e_prescription_logs`
  ADD CONSTRAINT `e_prescription_logs_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `e_prescription_logs_ibfk_2` FOREIGN KEY (`pharmacy_id`) REFERENCES `pharmacies` (`id`) ON DELETE RESTRICT;

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
-- Constraints for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `maintenance_logs_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maintenance_logs_ibfk_2` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  ADD CONSTRAINT `maintenance_schedules_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `medication_adherence`
--
ALTER TABLE `medication_adherence`
  ADD CONSTRAINT `medication_adherence_ibfk_1` FOREIGN KEY (`prescription_item_id`) REFERENCES `prescription_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medication_adherence_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medication_adherence_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_delivery_log`
--
ALTER TABLE `notification_delivery_log`
  ADD CONSTRAINT `notification_delivery_log_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_preferences_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD CONSTRAINT `patient_allergies_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_allergies_ibfk_2` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patient_allergies_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_appointment_requests`
--
ALTER TABLE `patient_appointment_requests`
  ADD CONSTRAINT `patient_appointment_requests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_appointment_requests_ibfk_2` FOREIGN KEY (`requested_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patient_appointment_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patient_appointment_requests_ibfk_4` FOREIGN KEY (`scheduled_appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_document_access_log`
--
ALTER TABLE `patient_document_access_log`
  ADD CONSTRAINT `patient_document_access_log_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_health_goals`
--
ALTER TABLE `patient_health_goals`
  ADD CONSTRAINT `patient_health_goals_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_health_goals_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_health_progress`
--
ALTER TABLE `patient_health_progress`
  ADD CONSTRAINT `patient_health_progress_ibfk_1` FOREIGN KEY (`goal_id`) REFERENCES `patient_health_goals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_messages`
--
ALTER TABLE `patient_messages`
  ADD CONSTRAINT `patient_messages_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_messages_ibfk_2` FOREIGN KEY (`parent_message_id`) REFERENCES `patient_messages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_portal_activity_log`
--
ALTER TABLE `patient_portal_activity_log`
  ADD CONSTRAINT `patient_portal_activity_log_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_portal_feedback`
--
ALTER TABLE `patient_portal_feedback`
  ADD CONSTRAINT `patient_portal_feedback_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_portal_feedback_ibfk_2` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_portal_preferences`
--
ALTER TABLE `patient_portal_preferences`
  ADD CONSTRAINT `patient_portal_preferences_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  ADD CONSTRAINT `patient_portal_users_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_sessions`
--
ALTER TABLE `patient_sessions`
  ADD CONSTRAINT `patient_sessions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prescriptions_ibfk_4` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_history`
--
ALTER TABLE `prescription_history`
  ADD CONSTRAINT `prescription_history_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_history_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_items_ibfk_2` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `prescription_refills`
--
ALTER TABLE `prescription_refills`
  ADD CONSTRAINT `prescription_refills_ibfk_1` FOREIGN KEY (`prescription_item_id`) REFERENCES `prescription_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_refills_ibfk_2` FOREIGN KEY (`pharmacist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prescription_templates`
--
ALTER TABLE `prescription_templates`
  ADD CONSTRAINT `prescription_templates_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `queue_logs`
--
ALTER TABLE `queue_logs`
  ADD CONSTRAINT `queue_logs_ibfk_1` FOREIGN KEY (`visit_queue_id`) REFERENCES `visit_queue` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `queue_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `security_settings`
--
ALTER TABLE `security_settings`
  ADD CONSTRAINT `security_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sms_queue`
--
ALTER TABLE `sms_queue`
  ADD CONSTRAINT `sms_queue_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_activity`
--
ALTER TABLE `system_activity`
  ADD CONSTRAINT `system_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD CONSTRAINT `system_backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visit_queue`
--
ALTER TABLE `visit_queue`
  ADD CONSTRAINT `fk_visit_queue_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_visit_queue_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visit_queue_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `visit_queue_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
