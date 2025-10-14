<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized
if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID is required']);
    exit();
}

$doctorId = intval($_GET['id']);

try {
    $db = getDBConnection();
    
    $query = "SELECT d.*, u.username, u.email, u.phone, u.first_name, u.last_name, u.is_active,
              CONCAT(u.first_name, ' ', u.last_name) as full_name
              FROM doctors d
              LEFT JOIN users u ON d.user_id = u.id
              WHERE d.id = :doctor_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
    $stmt->execute();
    
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($doctor) {
        echo json_encode([
            'success' => true,
            'data' => $doctor
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Doctor not found'
        ]);
    }
    
} catch (Exception $e) {
    error_log('[ajax/get_doctor_details] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching doctor details'
    ]);
}
