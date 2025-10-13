<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/QueueController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow doctors and staff to get next patient
if (!in_array($user_type, ['admin', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $controller = new QueueController();
    // For doctors, only show their patients
    $doctor_id = ($user_type === 'doctor') ? $user_id : null;
    $result = $controller->getNextPatient($doctor_id);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('[ajax/get_next_patient] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
