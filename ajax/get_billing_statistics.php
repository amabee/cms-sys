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

try {
    $controller = new BillingController();
    $stats = $controller->getStatistics();
    
    // Transform the stats into the expected format
    $data = [
        'total_revenue' => isset($stats['total_billed']) ? floatval($stats['total_billed']) : 0,
        'paid_amount' => isset($stats['total_paid']) ? floatval($stats['total_paid']) : 0,
        'pending_amount' => isset($stats['total_outstanding']) ? floatval($stats['total_outstanding']) : 0,
        'total_bills' => isset($stats['total_bills']) ? intval($stats['total_bills']) : 0,
        'paid_bills' => isset($stats['paid_bills']) ? intval($stats['paid_bills']) : 0,
        'pending_bills' => isset($stats['pending_bills']) ? intval($stats['pending_bills']) : 0,
        'overdue_bills' => isset($stats['overdue_bills']) ? intval($stats['overdue_bills']) : 0
    ];
    
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    error_log('[ajax/get_billing_statistics] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving billing statistics']);
}
?>
