<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/SystemLogger.php';

class ReportsController {
    private $pdo;
    private $logger;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->logger = new SystemLogger();
    }
    
    /**
     * Get dashboard statistics for reports overview
     */
    public function getDashboardStatistics() {
        try {
            $stats = [];
            
            // Patient Statistics
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_patients,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_patients,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_patients_today,
                    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_patients_week
                FROM patients
            ");
            $stats['patients'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Appointment Statistics
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_appointments,
                    COUNT(CASE WHEN appointment_date = CURDATE() THEN 1 END) as today_appointments,
                    COUNT(CASE WHEN appointment_date >= CURDATE() AND status = 'scheduled' THEN 1 END) as upcoming_appointments,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_appointments,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_appointments,
                    COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_appointments
                FROM appointments
                WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stats['appointments'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Revenue Statistics
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(paid_amount), 0) as collected_revenue,
                    COALESCE(SUM(balance_amount), 0) as pending_revenue,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_bills,
                    COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_bills,
                    COUNT(CASE WHEN payment_status = 'overdue' THEN 1 END) as overdue_bills
                FROM billing
                WHERE bill_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Lab Tests Statistics
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_tests,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tests,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as pending_tests,
                    COUNT(CASE WHEN test_date = CURDATE() THEN 1 END) as today_tests
                FROM lab_tests
                WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stats['lab_tests'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Medical Records Statistics
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_records,
                    COUNT(CASE WHEN visit_date = CURDATE() THEN 1 END) as today_records,
                    COUNT(CASE WHEN visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_records
                FROM medical_records
                WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stats['medical_records'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get dashboard statistics: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics'
            ];
        }
    }
    
    /**
     * Get patient demographics report
     */
    public function getPatientDemographicsReport($startDate = null, $endDate = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $whereClause .= " AND created_at >= :start_date";
                $params['start_date'] = $startDate;
            }
            
            if ($endDate) {
                $whereClause .= " AND created_at <= :end_date";
                $params['end_date'] = $endDate . ' 23:59:59';
            }
            
            // Gender Distribution
            $stmt = $this->pdo->prepare("
                SELECT 
                    gender,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM patients $whereClause), 2) as percentage
                FROM patients 
                $whereClause AND gender IS NOT NULL
                GROUP BY gender
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $genderDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Age Groups
            $stmt = $this->pdo->prepare("
                SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 'Under 18'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50 THEN '31-50'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 65 THEN '51-65'
                        ELSE 'Over 65'
                    END as age_group,
                    COUNT(*) as count
                FROM patients 
                $whereClause AND date_of_birth IS NOT NULL
                GROUP BY age_group
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $ageDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Registration Trends (Last 12 months)
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as new_patients
                FROM patients 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute();
            $registrationTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'gender_distribution' => $genderDistribution,
                    'age_distribution' => $ageDistribution,
                    'registration_trends' => $registrationTrends
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get patient demographics report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate patient demographics report'
            ];
        }
    }
    
    /**
     * Get appointment analytics report
     */
    public function getAppointmentAnalyticsReport($startDate = null, $endDate = null, $doctorId = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $whereClause .= " AND appointment_date >= :start_date";
                $params['start_date'] = $startDate;
            }
            
            if ($endDate) {
                $whereClause .= " AND appointment_date <= :end_date";
                $params['end_date'] = $endDate;
            }
            
            if ($doctorId) {
                $whereClause .= " AND doctor_id = :doctor_id";
                $params['doctor_id'] = $doctorId;
            }
            
            // Appointment Status Distribution
            $stmt = $this->pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM appointments a2 $whereClause), 2) as percentage
                FROM appointments a
                $whereClause
                GROUP BY status
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $statusDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Daily Appointment Trends
            $stmt = $this->pdo->prepare("
                SELECT 
                    appointment_date,
                    COUNT(*) as total_appointments,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show
                FROM appointments 
                $whereClause
                GROUP BY appointment_date
                ORDER BY appointment_date ASC
            ");
            $stmt->execute($params);
            $dailyTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Doctor Performance
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    d.specialization,
                    COUNT(*) as total_appointments,
                    COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments,
                    ROUND(COUNT(CASE WHEN a.status = 'completed' THEN 1 END) * 100.0 / COUNT(*), 2) as completion_rate,
                    COUNT(CASE WHEN a.status = 'no_show' THEN 1 END) as no_shows
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                $whereClause
                GROUP BY a.doctor_id, doctor_name, d.specialization
                ORDER BY total_appointments DESC
            ");
            $stmt->execute($params);
            $doctorPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Peak Hours Analysis
            $stmt = $this->pdo->prepare("
                SELECT 
                    HOUR(appointment_time) as hour,
                    COUNT(*) as appointment_count
                FROM appointments 
                $whereClause
                GROUP BY HOUR(appointment_time)
                ORDER BY hour ASC
            ");
            $stmt->execute($params);
            $peakHours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'status_distribution' => $statusDistribution,
                    'daily_trends' => $dailyTrends,
                    'doctor_performance' => $doctorPerformance,
                    'peak_hours' => $peakHours
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get appointment analytics report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate appointment analytics report'
            ];
        }
    }
    
    /**
     * Get financial report
     */
    public function getFinancialReport($startDate = null, $endDate = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $whereClause .= " AND bill_date >= :start_date";
                $params['start_date'] = $startDate;
            }
            
            if ($endDate) {
                $whereClause .= " AND bill_date <= :end_date";
                $params['end_date'] = $endDate;
            }
            
            // Revenue Summary
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_bills,
                    COALESCE(SUM(consultation_fee), 0) as consultation_revenue,
                    COALESCE(SUM(lab_charges), 0) as lab_revenue,
                    COALESCE(SUM(medication_charges), 0) as medication_revenue,
                    COALESCE(SUM(other_charges), 0) as other_revenue,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(discount_amount), 0) as total_discounts,
                    COALESCE(SUM(paid_amount), 0) as total_collected,
                    COALESCE(SUM(balance_amount), 0) as total_outstanding
                FROM billing 
                $whereClause
            ");
            $stmt->execute($params);
            $revenueSummary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Payment Status Distribution
            $stmt = $this->pdo->prepare("
                SELECT 
                    payment_status,
                    COUNT(*) as count,
                    COALESCE(SUM(total_amount), 0) as total_amount,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM billing b2 $whereClause), 2) as percentage
                FROM billing 
                $whereClause
                GROUP BY payment_status
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $paymentStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Payment Methods Distribution
            $stmt = $this->pdo->prepare("
                SELECT 
                    payment_method,
                    COUNT(*) as count,
                    COALESCE(SUM(paid_amount), 0) as total_amount
                FROM billing 
                $whereClause AND payment_status IN ('paid', 'partial')
                GROUP BY payment_method
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Monthly Revenue Trends
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE_FORMAT(bill_date, '%Y-%m') as month,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(paid_amount), 0) as collected_revenue,
                    COUNT(*) as total_bills
                FROM billing 
                WHERE bill_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(bill_date, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute();
            $monthlyTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Outstanding Bills Analysis
            $stmt = $this->pdo->prepare("
                SELECT 
                    CASE 
                        WHEN DATEDIFF(CURDATE(), due_date) <= 0 THEN 'Not Due'
                        WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 1 AND 30 THEN '1-30 Days'
                        WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN '31-60 Days'
                        WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN '61-90 Days'
                        ELSE 'Over 90 Days'
                    END as aging_period,
                    COUNT(*) as count,
                    COALESCE(SUM(balance_amount), 0) as total_outstanding
                FROM billing 
                WHERE payment_status IN ('pending', 'partial', 'overdue') AND balance_amount > 0
                GROUP BY aging_period
                ORDER BY total_outstanding DESC
            ");
            $stmt->execute();
            $agingAnalysis = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'revenue_summary' => $revenueSummary,
                    'payment_status' => $paymentStatus,
                    'payment_methods' => $paymentMethods,
                    'monthly_trends' => $monthlyTrends,
                    'aging_analysis' => $agingAnalysis
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get financial report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate financial report'
            ];
        }
    }
    
    /**
     * Get lab tests report
     */
    public function getLabTestsReport($startDate = null, $endDate = null, $category = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $whereClause .= " AND test_date >= :start_date";
                $params['start_date'] = $startDate;
            }
            
            if ($endDate) {
                $whereClause .= " AND test_date <= :end_date";
                $params['end_date'] = $endDate;
            }
            
            if ($category) {
                $whereClause .= " AND test_category = :category";
                $params['category'] = $category;
            }
            
            // Test Status Summary
            $stmt = $this->pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM lab_tests lt2 $whereClause), 2) as percentage
                FROM lab_tests 
                $whereClause
                GROUP BY status
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $statusSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Category-wise Distribution
            $stmt = $this->pdo->prepare("
                SELECT 
                    test_category,
                    COUNT(*) as count,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tests
                FROM lab_tests 
                $whereClause
                GROUP BY test_category
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $categoryDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Most Ordered Tests
            $stmt = $this->pdo->prepare("
                SELECT 
                    test_name,
                    test_category,
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
                FROM lab_tests 
                $whereClause
                GROUP BY test_name, test_category
                ORDER BY total_orders DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $popularTests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Daily Test Volume
            $stmt = $this->pdo->prepare("
                SELECT 
                    test_date,
                    COUNT(*) as total_tests,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tests
                FROM lab_tests 
                $whereClause
                GROUP BY test_date
                ORDER BY test_date ASC
            ");
            $stmt->execute($params);
            $dailyVolume = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Turnaround Time Analysis
            $stmt = $this->pdo->prepare("
                SELECT 
                    test_category,
                    AVG(DATEDIFF(
                        CASE 
                            WHEN status = 'completed' THEN updated_at 
                            ELSE CURDATE() 
                        END, 
                        test_date
                    )) as avg_turnaround_days
                FROM lab_tests 
                $whereClause AND status IN ('completed', 'in_progress')
                GROUP BY test_category
                ORDER BY avg_turnaround_days ASC
            ");
            $stmt->execute($params);
            $turnaroundTime = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'status_summary' => $statusSummary,
                    'category_distribution' => $categoryDistribution,
                    'popular_tests' => $popularTests,
                    'daily_volume' => $dailyVolume,
                    'turnaround_time' => $turnaroundTime
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get lab tests report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate lab tests report'
            ];
        }
    }
    
    /**
     * Get medical records report
     */
    public function getMedicalRecordsReport($startDate = null, $endDate = null, $doctorId = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $whereClause .= " AND visit_date >= :start_date";
                $params['start_date'] = $startDate;
            }
            
            if ($endDate) {
                $whereClause .= " AND visit_date <= :end_date";
                $params['end_date'] = $endDate;
            }
            
            if ($doctorId) {
                $whereClause .= " AND doctor_id = :doctor_id";
                $params['doctor_id'] = $doctorId;
            }
            
            // Records Summary
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_records,
                    COUNT(DISTINCT patient_id) as unique_patients,
                    COUNT(DISTINCT doctor_id) as active_doctors,
                    AVG(
                        CASE 
                            WHEN JSON_LENGTH(vital_signs) > 0 THEN 1 
                            ELSE 0 
                        END
                    ) * 100 as vital_signs_completion_rate
                FROM medical_records 
                $whereClause
            ");
            $stmt->execute($params);
            $recordsSummary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Common Diagnoses
            $stmt = $this->pdo->prepare("
                SELECT 
                    diagnosis,
                    COUNT(*) as frequency
                FROM medical_records 
                $whereClause AND diagnosis IS NOT NULL AND diagnosis != ''
                GROUP BY diagnosis
                ORDER BY frequency DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $commonDiagnoses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Daily Records Volume
            $stmt = $this->pdo->prepare("
                SELECT 
                    visit_date,
                    COUNT(*) as total_records,
                    COUNT(DISTINCT patient_id) as unique_patients
                FROM medical_records 
                $whereClause
                GROUP BY visit_date
                ORDER BY visit_date ASC
            ");
            $stmt->execute($params);
            $dailyVolume = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Doctor Activity
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    d.specialization,
                    COUNT(*) as total_records,
                    COUNT(DISTINCT mr.patient_id) as unique_patients,
                    AVG(
                        CASE 
                            WHEN JSON_LENGTH(mr.vital_signs) > 0 THEN 1 
                            ELSE 0 
                        END
                    ) * 100 as vital_signs_rate
                FROM medical_records mr
                JOIN doctors d ON mr.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                $whereClause
                GROUP BY mr.doctor_id, doctor_name, d.specialization
                ORDER BY total_records DESC
            ");
            $stmt->execute($params);
            $doctorActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'records_summary' => $recordsSummary,
                    'common_diagnoses' => $commonDiagnoses,
                    'daily_volume' => $dailyVolume,
                    'doctor_activity' => $doctorActivity
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get medical records report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate medical records report'
            ];
        }
    }
    
    /**
     * Export report data to CSV format
     */
    public function exportToCsv($reportType, $data, $filename = null) {
        try {
            if (!$filename) {
                $filename = $reportType . '_report_' . date('Y-m-d') . '.csv';
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fwrite($output, "\xEF\xBB\xBF");
            
            switch ($reportType) {
                case 'patient_demographics':
                    $this->exportPatientDemographicsCsv($output, $data);
                    break;
                case 'appointment_analytics':
                    $this->exportAppointmentAnalyticsCsv($output, $data);
                    break;
                case 'financial':
                    $this->exportFinancialCsv($output, $data);
                    break;
                case 'lab_tests':
                    $this->exportLabTestsCsv($output, $data);
                    break;
                case 'medical_records':
                    $this->exportMedicalRecordsCsv($output, $data);
                    break;
                default:
                    throw new Exception('Unknown report type');
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to export report to CSV: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to export report'
            ];
        }
    }
    
    /**
     * Export patient demographics to CSV
     */
    private function exportPatientDemographicsCsv($output, $data) {
        // Gender Distribution
        fputcsv($output, ['Patient Demographics Report - Gender Distribution']);
        fputcsv($output, ['Gender', 'Count', 'Percentage']);
        foreach ($data['gender_distribution'] as $row) {
            fputcsv($output, [$row['gender'], $row['count'], $row['percentage'] . '%']);
        }
        
        fputcsv($output, ['']); // Empty row
        
        // Age Distribution
        fputcsv($output, ['Age Distribution']);
        fputcsv($output, ['Age Group', 'Count']);
        foreach ($data['age_distribution'] as $row) {
            fputcsv($output, [$row['age_group'], $row['count']]);
        }
        
        fputcsv($output, ['']); // Empty row
        
        // Registration Trends
        fputcsv($output, ['Registration Trends']);
        fputcsv($output, ['Month', 'New Patients']);
        foreach ($data['registration_trends'] as $row) {
            fputcsv($output, [$row['month'], $row['new_patients']]);
        }
    }
    
    /**
     * Export appointment analytics to CSV
     */
    private function exportAppointmentAnalyticsCsv($output, $data) {
        // Status Distribution
        fputcsv($output, ['Appointment Analytics Report - Status Distribution']);
        fputcsv($output, ['Status', 'Count', 'Percentage']);
        foreach ($data['status_distribution'] as $row) {
            fputcsv($output, [$row['status'], $row['count'], $row['percentage'] . '%']);
        }
        
        fputcsv($output, ['']); // Empty row
        
        // Doctor Performance
        fputcsv($output, ['Doctor Performance']);
        fputcsv($output, ['Doctor Name', 'Specialization', 'Total Appointments', 'Completed', 'Completion Rate', 'No Shows']);
        foreach ($data['doctor_performance'] as $row) {
            fputcsv($output, [
                $row['doctor_name'],
                $row['specialization'],
                $row['total_appointments'],
                $row['completed_appointments'],
                $row['completion_rate'] . '%',
                $row['no_shows']
            ]);
        }
    }
    
    /**
     * Export financial report to CSV
     */
    private function exportFinancialCsv($output, $data) {
        // Revenue Summary
        fputcsv($output, ['Financial Report - Revenue Summary']);
        $summary = $data['revenue_summary'];
        fputcsv($output, ['Metric', 'Amount']);
        fputcsv($output, ['Total Bills', $summary['total_bills']]);
        fputcsv($output, ['Consultation Revenue', '$' . number_format($summary['consultation_revenue'], 2)]);
        fputcsv($output, ['Lab Revenue', '$' . number_format($summary['lab_revenue'], 2)]);
        fputcsv($output, ['Medication Revenue', '$' . number_format($summary['medication_revenue'], 2)]);
        fputcsv($output, ['Other Revenue', '$' . number_format($summary['other_revenue'], 2)]);
        fputcsv($output, ['Total Revenue', '$' . number_format($summary['total_revenue'], 2)]);
        fputcsv($output, ['Total Collected', '$' . number_format($summary['total_collected'], 2)]);
        fputcsv($output, ['Total Outstanding', '$' . number_format($summary['total_outstanding'], 2)]);
        
        fputcsv($output, ['']); // Empty row
        
        // Monthly Trends
        fputcsv($output, ['Monthly Revenue Trends']);
        fputcsv($output, ['Month', 'Total Revenue', 'Collected Revenue', 'Total Bills']);
        foreach ($data['monthly_trends'] as $row) {
            fputcsv($output, [
                $row['month'],
                '$' . number_format($row['total_revenue'], 2),
                '$' . number_format($row['collected_revenue'], 2),
                $row['total_bills']
            ]);
        }
    }
    
    /**
     * Export lab tests report to CSV
     */
    private function exportLabTestsCsv($output, $data) {
        // Popular Tests
        fputcsv($output, ['Lab Tests Report - Most Ordered Tests']);
        fputcsv($output, ['Test Name', 'Category', 'Total Orders', 'Completed Orders']);
        foreach ($data['popular_tests'] as $row) {
            fputcsv($output, [
                $row['test_name'],
                $row['test_category'],
                $row['total_orders'],
                $row['completed_orders']
            ]);
        }
        
        fputcsv($output, ['']); // Empty row
        
        // Category Distribution
        fputcsv($output, ['Category Distribution']);
        fputcsv($output, ['Category', 'Total Tests', 'Completed Tests']);
        foreach ($data['category_distribution'] as $row) {
            fputcsv($output, [
                $row['test_category'],
                $row['count'],
                $row['completed_tests']
            ]);
        }
    }
    
    /**
     * Export medical records report to CSV
     */
    private function exportMedicalRecordsCsv($output, $data) {
        // Common Diagnoses
        fputcsv($output, ['Medical Records Report - Common Diagnoses']);
        fputcsv($output, ['Diagnosis', 'Frequency']);
        foreach ($data['common_diagnoses'] as $row) {
            fputcsv($output, [$row['diagnosis'], $row['frequency']]);
        }
        
        fputcsv($output, ['']); // Empty row
        
        // Doctor Activity
        fputcsv($output, ['Doctor Activity']);
        fputcsv($output, ['Doctor Name', 'Specialization', 'Total Records', 'Unique Patients', 'Vital Signs Rate']);
        foreach ($data['doctor_activity'] as $row) {
            fputcsv($output, [
                $row['doctor_name'],
                $row['specialization'],
                $row['total_records'],
                $row['unique_patients'],
                round($row['vital_signs_rate'], 2) . '%'
            ]);
        }
    }
}
