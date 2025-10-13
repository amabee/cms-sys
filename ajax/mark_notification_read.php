<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
    $user_id = $_SESSION['user_id'];
    
    $result = $controller->markNotificationAsRead($notification_id, $user_id);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log("Error in mark_notification_read.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}
?>
