<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/MedicalRecordsController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow authorized roles
if (!in_array($user_type, ['admin', 'doctor', 'secretary'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $controller = new MedicalRecordsController();
    $result = $controller->getStatistics();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('[ajax/get_medical_records_statistics] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving medical records statistics']);
}
?>
