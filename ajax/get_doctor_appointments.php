<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow doctors to view their own appointments
if ($user_type !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $db = getDBConnection();
    
    // Get doctor ID
    $stmt = $db->prepare('SELECT id FROM doctors WHERE user_id = :user_id LIMIT 1');
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $doctor_id = $stmt->fetchColumn();
    
    if (!$doctor_id) {
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit();
    }
    
    // Check if requesting statistics
    if (isset($_GET['stats']) && $_GET['stats']) {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        
        // Get statistics
        $stats = [];
        
        // Today's appointments
        $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND DATE(appointment_date) = :today");
        $stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        $stmt->execute();
        $stats['today'] = $stmt->fetchColumn();
        
        // This week's upcoming appointments
        $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND appointment_date BETWEEN :week_start AND :week_end AND appointment_date > :today AND status = 'scheduled'");
        $stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
        $stmt->bindValue(':week_start', $weekStart, PDO::PARAM_STR);
        $stmt->bindValue(':week_end', $weekEnd, PDO::PARAM_STR);
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        $stmt->execute();
        $stats['upcoming'] = $stmt->fetchColumn();
        
        // This month's completed appointments
        $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND appointment_date BETWEEN :month_start AND :month_end AND status = 'completed'");
        $stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
        $stmt->bindValue(':month_start', $monthStart, PDO::PARAM_STR);
        $stmt->bindValue(':month_end', $monthEnd, PDO::PARAM_STR);
        $stmt->execute();
        $stats['completed'] = $stmt->fetchColumn();
        
        // This month's cancelled appointments
        $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND appointment_date BETWEEN :month_start AND :month_end AND status IN ('cancelled', 'no_show')");
        $stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
        $stmt->bindValue(':month_start', $monthStart, PDO::PARAM_STR);
        $stmt->bindValue(':month_end', $monthEnd, PDO::PARAM_STR);
        $stmt->execute();
        $stats['cancelled'] = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit();
    }
    
    // Get date filter (default to today)
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // Get appointments for the doctor on the specified date
    $sql = "
        SELECT 
            a.id,
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.reason,
            CONCAT(p.first_name, ' ', p.last_name) as patient_name,
            p.patient_id as patient_code,
            p.id as patient_id
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = :doctor_id 
        AND DATE(a.appointment_date) = :date
        ORDER BY a.appointment_time ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
    $stmt->bindValue(':date', $date, PDO::PARAM_STR);
    $stmt->execute();
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $appointments,
        'date' => $date
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_doctor_appointments] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
