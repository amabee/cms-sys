<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = getDBConnection();
    
    $patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    
    $query = "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
            CONCAT(p.first_name, ' ', p.last_name) as patient_name,
            CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
            d.consultation_fee as doctor_fee
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN users u ON d.user_id = u.id
        WHERE 1=1";
    
    $params = [];
    
    if ($patient_id > 0) {
        $query .= " AND a.patient_id = :patient_id";
        $params['patient_id'] = $patient_id;
    }
    
    if (!empty($status)) {
        $query .= " AND a.status = :status";
        $params['status'] = $status;
    } else {
        // By default, show only completed appointments (most relevant for billing)
        $query .= " AND a.status IN ('completed', 'confirmed', 'scheduled')";
    }
    
    $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 100";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->execute();
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $appointments
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_appointments_list] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch appointments: ' . $e->getMessage()
    ]);
}
?>
