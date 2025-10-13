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
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    
    $filters = [];
    if (!empty($_GET['type'])) {
        $filters['type'] = $_GET['type'];
    }
    if (!empty($_GET['priority'])) {
        $filters['priority'] = $_GET['priority'];
    }
    if (!empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    if (!empty($_GET['user_id'])) {
        $filters['user_id'] = $_GET['user_id'];
    }
    if (!empty($_GET['patient_id'])) {
        $filters['patient_id'] = $_GET['patient_id'];
    }
    
    $result = $controller->getNotifications($filters, $page, $limit);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => [
                'notifications' => $result['notifications'],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $result['total_pages'],
                    'total_records' => $result['total_count'],
                    'per_page' => $limit
                ]
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log("Error in get_notifications.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve notifications']);
}
?>
