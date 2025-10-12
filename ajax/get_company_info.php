<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/CompanyController.php';

// Only allow admin users (or staff with appropriate rights)
if (!isset($user_id) || !hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $company = CompanyController::getCompanyInfo();
    echo json_encode(['success' => true, 'company' => $company]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load company info']);
}

?>
