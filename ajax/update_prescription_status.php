<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/PrescriptionController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    if (!isset($_POST['prescription_id']) || !isset($_POST['status'])) {
        throw new Exception('Prescription ID and status are required');
    }
    
    $db = getDBConnection();
    $controller = new PrescriptionController($db);
    
    $prescriptionId = $_POST['prescription_id'];
    $status = $_POST['status'];
    $reason = $_POST['reason'] ?? '';
    
    $result = $controller->updatePrescriptionStatus($prescriptionId, $status, $reason, $user_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
