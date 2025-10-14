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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../controllers/UsersController.php';
$controller = new UsersController();

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : false;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    $result = $controller->toggleUserStatus($id, $is_active);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Error in toggle_user_status.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to toggle user status']);
}
?>
