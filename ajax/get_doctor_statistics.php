<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized
if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow doctors to view their own statistics
if ($user_type !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $db = getDBConnection();
    
    // Get doctor ID from user_id
    $stmt = $db->prepare('SELECT id FROM doctors WHERE user_id = :user_id LIMIT 1');
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $doctor_id = $stmt->fetchColumn();
    
    if (!$doctor_id) {
        echo json_encode(['success' => false, 'message' => 'Doctor profile not found']);
        exit();
    }
    
    // Today's appointments count
    $todayQuery = "SELECT COUNT(*) as today_appointments 
                   FROM appointments 
                   WHERE doctor_id = :doctor_id 
                   AND DATE(appointment_date) = CURDATE() 
                   AND status IN ('scheduled', 'confirmed', 'in_progress')";
    $todayStmt = $db->prepare($todayQuery);
    $todayStmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
    $todayStmt->execute();
    $todayAppointments = $todayStmt->fetch(PDO::FETCH_ASSOC)['today_appointments'];
    
    // Total unique patients
    $patientsQuery = "SELECT COUNT(DISTINCT patient_id) as total_patients 
                      FROM appointments 
                      WHERE doctor_id = :doctor_id";
    $patientsStmt = $db->prepare($patientsQuery);
    $patientsStmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
    $patientsStmt->execute();
    $totalPatients = $patientsStmt->fetch(PDO::FETCH_ASSOC)['total_patients'];
    
    // Pending actions (upcoming appointments not yet completed)
    $pendingQuery = "SELECT COUNT(*) as pending_actions 
                     FROM appointments 
                     WHERE doctor_id = :doctor_id 
                     AND appointment_date >= CURDATE() 
                     AND status IN ('scheduled', 'confirmed')";
    $pendingStmt = $db->prepare($pendingQuery);
    $pendingStmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
    $pendingStmt->execute();
    $pendingActions = $pendingStmt->fetch(PDO::FETCH_ASSOC)['pending_actions'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'today_appointments' => (int)$todayAppointments,
            'total_patients' => (int)$totalPatients,
            'pending_actions' => (int)$pendingActions
        ]
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_doctor_statistics] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch statistics'
    ]);
}
