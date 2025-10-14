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

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Fallback to POST data if JSON decode fails
        $data = $_POST;
    }
    
    $controller = new BillingController();
    $result = $controller->create($data);
    
    if ($result['success']) {
        // Controller may return different keys for the created id/bill_id
        $billingId = null;
        if (isset($result['billing_id'])) $billingId = $result['billing_id'];
        elseif (isset($result['bill_id'])) $billingId = $result['bill_id'];
        elseif (isset($result['id'])) $billingId = $result['id'];

        echo json_encode(['success' => true, 'message' => 'Billing record created successfully', 'billing_id' => $billingId]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log('[ajax/create_billing] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while creating billing record']);
}
?>
