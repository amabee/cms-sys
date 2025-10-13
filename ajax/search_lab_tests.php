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
    
    // Get search criteria from POST data
    $criteria = [
        'patient_search' => $_POST['patient_search'] ?? '',
        'test_search' => $_POST['test_search'] ?? '',
        'status' => $_POST['status'] ?? '',
        'date_from' => $_POST['date_from'] ?? '',
        'date_to' => $_POST['date_to'] ?? '',
        'doctor_id' => $_POST['doctor_id'] ?? '',
        'category' => $_POST['category'] ?? '',
        'critical_only' => $_POST['critical_only'] ?? ''
    ];
    
    $result = $controller->searchTests($criteria);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('[search_lab_tests] Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Search failed']);
}
?>
