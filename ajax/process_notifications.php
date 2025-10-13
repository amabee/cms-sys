<?php
session_start();
header('Content-Type: application/json');

// Check authentication and authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user has permission to process notifications
if (!in_array($_SESSION['role'], ['admin', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../controllers/NotificationsController.php';

$controller = new NotificationsController();

try {
    $result = $controller->processPendingNotifications();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Notifications processed successfully',
            'data' => [
                'processed' => $result['processed_count'],
                'sent' => $result['sent_count'],
                'failed' => $result['failed_count']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log("Error in process_notifications.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to process notifications']);
}
?>
