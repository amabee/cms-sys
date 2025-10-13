<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/QueueController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow receptionist and staff to add to queue
if (!in_array($user_type, ['admin', 'receptionist', 'secretary'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($patient_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit();
}

try {
    $controller = new QueueController();
    $result = $controller->addToQueue($patient_id, $appointment_id, $notes);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('[ajax/add_to_queue] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
