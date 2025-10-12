<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = null;
if (function_exists('getDBConnection')) {
    $db = @getDBConnection();
}

$stats = [
    'total_users' => 0,
    'total_employees' => 0,
    'total_departments' => 0,
    'pending_leaves' => 0,
    'employee_growth_percentage' => 0,
    'avg_performance_rating' => 0.0,
    'employees_with_recent_evaluations' => 0,
    'avg_basic_salary' => 0,
    'above_avg_salary_count' => 0,
    'employees_with_allowances' => 0,
    'employees_with_recent_leaves' => 0,
    'top_performing_departments' => [],
    // Clinic-specific metrics
    'total_patients' => 0,
    'appointments_today' => 0,
    'upcoming_appointments' => 0,
    'available_doctors' => 0,
    'pending_lab_tests' => 0,
    'outstanding_billing_total' => 0.00
];

$recent_activity = [];
$attendance = ['labels' => [], 'present' => [], 'absent' => [], 'late' => []];

if ($db) {
    try {
        // Total users (system users)
        $stmt = $db->query('SELECT COUNT(*) FROM users');
        $stats['total_users'] = (int) $stmt->fetchColumn();

        // Total employees: use users with role != patient (patients are separate)
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','doctor','nurse','receptionist')");
        $stats['total_employees'] = (int) $stmt->fetchColumn();

        // Total departments - if you have a departments table, use it; otherwise 0
        $hasDepartments = $db->query("SHOW TABLES LIKE 'departments'")->fetchColumn();
        if ($hasDepartments) {
            $stmt = $db->query('SELECT COUNT(*) FROM departments');
            $stats['total_departments'] = (int) $stmt->fetchColumn();
        }

        // Pending leaves (if leaves table exists)
        $hasLeaves = $db->query("SHOW TABLES LIKE 'leaves'")->fetchColumn();
        if ($hasLeaves) {
            $stmt = $db->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
            $stats['pending_leaves'] = (int) $stmt->fetchColumn();
        }

        // Basic averages using views if present
        $hasDoctorSummary = $db->query("SHOW TABLES LIKE 'doctor_summary'")->fetchColumn();
        if ($hasDoctorSummary) {
            // avg performance rating - assume performance stored in audit_log or performance table
            $hasPerformance = $db->query("SHOW TABLES LIKE 'performance'")->fetchColumn();
            if ($hasPerformance) {
                $stmt = $db->query('SELECT AVG(rating) FROM performance');
                $stats['avg_performance_rating'] = (float) $stmt->fetchColumn();
            }
        }

        // Recent activity from audit_log (last 10)
        $hasAudit = $db->query("SHOW TABLES LIKE 'audit_log'")->fetchColumn();
        if ($hasAudit) {
            $stmt = $db->prepare('SELECT al.action, al.table_name, al.record_id, al.user_id, al.created_at, u.first_name, u.last_name FROM audit_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10');
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $recent_activity[] = [
                    'title' => ($r['first_name'] || $r['last_name']) ? trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) : 'System',
                    'description' => $r['action'] . ' on ' . ($r['table_name'] ?? ''),
                    'time' => $r['created_at'],
                    'icon' => 'bx-check-circle',
                    'color' => 'primary'
                ];
            }
        }

        // Clinic-specific metrics
        // Total patients
        $hasPatients = $db->query("SHOW TABLES LIKE 'patients'")->fetchColumn();
        if ($hasPatients) {
            $stmt = $db->query('SELECT COUNT(*) FROM patients');
            $stats['total_patients'] = (int) $stmt->fetchColumn();
        }

        // Appointments today and upcoming (next 7 days)
        $hasAppointments = $db->query("SHOW TABLES LIKE 'appointments'")->fetchColumn();
        if ($hasAppointments) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
            $stmt->execute();
            $stats['appointments_today'] = (int) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date > CURDATE() AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
            $stmt->execute();
            $stats['upcoming_appointments'] = (int) $stmt->fetchColumn();
        }

        // Available doctors
        $hasDoctors = $db->query("SHOW TABLES LIKE 'doctors'")->fetchColumn();
        if ($hasDoctors) {
            $stmt = $db->query("SELECT COUNT(*) FROM doctors WHERE is_available = 1");
            $stats['available_doctors'] = (int) $stmt->fetchColumn();
        }

        // Pending lab tests
        $hasLab = $db->query("SHOW TABLES LIKE 'lab_tests'")->fetchColumn();
        if ($hasLab) {
            $stmt = $db->query("SELECT COUNT(*) FROM lab_tests WHERE status IN ('ordered','sample_collected','in_progress')");
            $stats['pending_lab_tests'] = (int) $stmt->fetchColumn();
        }

        // Outstanding billing total (sum of balance amounts)
        $hasBilling = $db->query("SHOW TABLES LIKE 'billing'")->fetchColumn();
        if ($hasBilling) {
            $stmt = $db->query("SELECT COALESCE(SUM(balance_amount),0) FROM billing WHERE payment_status != 'paid'");
            $stats['outstanding_billing_total'] = (float) $stmt->fetchColumn();
        }

        // Normalize/alias clinic metrics into legacy HR keys so the small stat cards show clinic values
        // total_employees => show total_patients (or staff count if preferred)
        if (isset($stats['total_patients'])) {
            $stats['total_employees'] = $stats['total_patients'];
        }

        // pending_leaves => use today's appointments count
        if (isset($stats['appointments_today'])) {
            $stats['pending_leaves'] = $stats['appointments_today'];
        }

        // total_departments already present as departments count
        if (isset($stats['total_departments'])) {
            // no-op, already set
        }

        // total_users => prefer explicit total_users (system users), but if you want clinic-wide users,
        // aggregate patients + available doctors
        if (empty($stats['total_users'])) {
            $stats['total_users'] = (($stats['total_patients'] ?? 0) + ($stats['available_doctors'] ?? 0));
        }

        // Attendance: simple placeholder using appointments per day as proxy if attendance table not present
        $hasAttendance = $db->query("SHOW TABLES LIKE 'attendance'")->fetchColumn();
        if ($hasAttendance) {
            // Implement if attendance table exists
            $stmt = $db->query("SELECT DATE(recorded_at) as day, SUM(status='present') as present, SUM(status='absent') as absent, SUM(status='late') as late FROM attendance GROUP BY DATE(recorded_at) ORDER BY DATE(recorded_at) DESC LIMIT 7");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rows = array_reverse($rows);
            foreach ($rows as $r) {
                $attendance['labels'][] = $r['day'];
                $attendance['present'][] = (int)$r['present'];
                $attendance['absent'][] = (int)$r['absent'];
                $attendance['late'][] = (int)$r['late'];
            }
        } else {
            // fallback: last 7 days labels, zeroed
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $attendance['labels'][] = $d;
                $attendance['present'][] = 0;
                $attendance['absent'][] = 0;
                $attendance['late'][] = 0;
            }
        }

    } catch (Exception $e) {
        // ignore and return zeros
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'stats' => $stats,
        'recent_activity' => $recent_activity,
        'attendance' => $attendance
    ]
]);

?>
