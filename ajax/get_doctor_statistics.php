<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized
if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = getDBConnection();
    
    // Total doctors
    $totalQuery = "SELECT COUNT(*) as total FROM doctors";
    $totalStmt = $db->query($totalQuery);
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Available doctors
    $availableQuery = "SELECT COUNT(*) as available FROM doctors WHERE is_available = 1";
    $availableStmt = $db->query($availableQuery);
    $available = $availableStmt->fetch(PDO::FETCH_ASSOC)['available'];
    
    // Today's appointments
    $todayQuery = "SELECT COUNT(*) as today_appointments 
                   FROM appointments 
                   WHERE appointment_date = CURDATE() 
                   AND status IN ('scheduled', 'in_progress')";
    $todayStmt = $db->query($todayQuery);
    $todayAppointments = $todayStmt->fetch(PDO::FETCH_ASSOC)['today_appointments'];
    
    // Number of specializations
    $specializationsQuery = "SELECT COUNT(DISTINCT specialization) as specializations 
                            FROM doctors 
                            WHERE specialization IS NOT NULL AND specialization != ''";
    $specializationsStmt = $db->query($specializationsQuery);
    $specializations = $specializationsStmt->fetch(PDO::FETCH_ASSOC)['specializations'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $total,
            'available' => $available,
            'today_appointments' => $todayAppointments,
            'specializations' => $specializations
        ]
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_doctor_statistics] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch statistics'
    ]);
}
