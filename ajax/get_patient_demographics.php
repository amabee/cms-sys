<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../shared/secure_session_handler.php';
require_once __DIR__ . '/../controllers/ReportsController.php';

header('Content-Type: application/json');

// Check authentication
requireRoleSecure(['admin', 'doctor']);

try {
    $reportsController = new ReportsController();
    
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    $result = $reportsController->getPatientDemographicsReport($startDate, $endDate);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving patient demographics: ' . $e->getMessage()
    ]);
}
?>
