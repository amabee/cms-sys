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
    $result = $controller->getTemplates();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => $result['templates']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log("Error in get_notification_templates.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve templates']);
}
?>
