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

$billing_id = $_GET['id'] ?? null;

if (!$billing_id) {
    echo json_encode(['success' => false, 'message' => 'Billing ID is required']);
    exit();
}

try {
    $controller = new BillingController();
    $result = $controller->getById($billing_id);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'data' => $result['data']]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log('[ajax/get_billing_details] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving billing record']);
}
?>
