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

// Vital signs data
$vital_signs = [
    'blood_pressure' => isset($_POST['blood_pressure']) ? trim($_POST['blood_pressure']) : null,
    'temperature' => isset($_POST['temperature']) ? trim($_POST['temperature']) : null,
    'pulse' => isset($_POST['pulse']) ? trim($_POST['pulse']) : null,
    'respiratory_rate' => isset($_POST['respiratory_rate']) ? trim($_POST['respiratory_rate']) : null,
    'weight' => isset($_POST['weight']) ? trim($_POST['weight']) : null,
    'height' => isset($_POST['height']) ? trim($_POST['height']) : null,
    'oxygen_saturation' => isset($_POST['oxygen_saturation']) ? trim($_POST['oxygen_saturation']) : null,
    'pain_level' => isset($_POST['pain_level']) ? trim($_POST['pain_level']) : null
];

if ($patient_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit();
}

try {
    $controller = new QueueController();
    $result = $controller->addToQueue($patient_id, $appointment_id, $notes, $vital_signs);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('[ajax/add_to_queue] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
