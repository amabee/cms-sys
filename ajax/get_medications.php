<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/SystemLogger.php';
require_once __DIR__ . '/../controllers/PrescriptionController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = getDBConnection();
    $controller = new PrescriptionController($db);
    $medications = $controller->getMedications($_GET);
    
    echo json_encode([
        'success' => true,
        'data' => $medications
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
