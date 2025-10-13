<?php
session_start();
require_once '../controllers/SystemSettingsController.php';
require_once '../shared/db_connection.php';

// Check authentication and authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $controller = new SystemSettingsController($conn);
    
    $templateType = isset($_GET['template_type']) ? $_GET['template_type'] : null;
    $result = $controller->getEmailTemplates($templateType);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Email Templates Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
