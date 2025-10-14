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
    
    // Build query based on filters
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
    ";
    
    $params = [':doctor_id' => $doctor_id];
    
    // Filter by status/type
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $today = date('Y-m-d');
    
    switch ($filter) {
        case 'today':
            $sql .= " AND DATE(a.appointment_date) = :today";
            $params[':today'] = $today;
            break;
        case 'upcoming':
            $sql .= " AND a.appointment_date >= :today AND a.status IN ('scheduled', 'confirmed')";
            $params[':today'] = $today;
            break;
        case 'completed':
            $sql .= " AND a.status = 'completed'";
            break;
        case 'cancelled':
            $sql .= " AND a.status IN ('cancelled', 'no_show')";
            break;
        // 'all' - no additional filter
    }
    
    // Date range filter
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $sql .= " AND a.appointment_date >= :start_date";
        $params[':start_date'] = $_GET['start_date'];
    }
    
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $sql .= " AND a.appointment_date <= :end_date";
        $params[':end_date'] = $_GET['end_date'];
    }
    
    // Get upcoming appointments with limit (for dashboard)
    if (isset($_GET['upcoming']) && $_GET['upcoming'] && isset($_GET['limit'])) {
        $sql .= " AND a.appointment_date >= :today AND a.status IN ('scheduled', 'confirmed')";
        $params[':today'] = $today;
        $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$_GET['limit'], PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
    }
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $appointments,
        'filter' => $filter
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_doctor_appointments] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
