<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../shared/secure_session_handler.php';
require_once __DIR__ . '/../controllers/ReportsController.php';

header('Content-Type: application/json');

// Check authentication
requireRoleSecure(['admin', 'doctor']);

try {
    $reportsController = new ReportsController();
    $result = $reportsController->getDashboardStatistics();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving dashboard statistics: ' . $e->getMessage()
    ]);
}
?>
