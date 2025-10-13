<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/BillingController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow authorized roles
if (!in_array($user_type, ['admin', 'secretary', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$appointment_id = $_POST['appointment_id'] ?? null;

if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit();
}

try {
    $data = $_POST;
    
    $controller = new BillingController();
    $result = $controller->createFromAppointment($appointment_id, $data);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Billing record created from appointment successfully', 'billing_id' => $result['billing_id']]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log('[ajax/create_bill_from_appointment] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while creating billing record from appointment']);
}
?>
