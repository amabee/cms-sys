<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../controllers/LabTestsController.php';
require_once __DIR__ . '/../shared/session_handler.php';

// Require appropriate role
requireRole(['admin', 'doctor', 'secretary', 'receptionist']);

if (!isset($user_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $controller = new LabTestsController();
    $categories = $controller->getTestCategories();
    echo json_encode(['success' => true, 'data' => $categories]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get categories']);
}
?>
