<?php
session_start();
header('Content-Type: application/json');

// Check authentication and authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user has permission to retry notifications
if (!in_array($_SESSION['user_type'], ['admin', 'doctor'])) {
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
    if (empty($_POST['notification_id'])) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        exit;
    }
    
    $notification_id = intval($_POST['notification_id']);
    
    $result = $controller->retryNotification($notification_id);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification queued for retry'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log("Error in retry_notification.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to retry notification']);
}
?>
