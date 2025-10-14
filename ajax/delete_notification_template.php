<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!in_array($_SESSION['user_type'], ['admin'])) {
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
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid template id']);
        exit;
    }

    $result = $controller->deleteTemplate($id);
    echo json_encode($result);
} catch (Exception $e) {
    error_log('Error in delete_notification_template.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete template']);
}

?>
