<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../controllers/NotificationsController.php';

$controller = new NotificationsController();

try {
    $statistics = $controller->getNotificationStatistics();
    
    if ($statistics['success']) {
        echo json_encode([
            'success' => true,
            'data' => $statistics['data']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $statistics['message']]);
    }
    
} catch (Exception $e) {
    error_log("Error in get_notification_statistics.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve notification statistics']);
}
?>
