<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permissions (admin only)
if (!in_array($_SESSION['user_type'], ['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

require_once '../controllers/UsersController.php';
$controller = new UsersController();

try {
    $result = $controller->getUserStatistics();
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Error in get_user_statistics.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve user statistics']);
}
?>
