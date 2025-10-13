<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../shared/secure_session_handler.php';
require_once __DIR__ . '/../controllers/ReportsController.php';

// Check authentication
requireRoleSecure(['admin', 'doctor']);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $reportType = $_POST['report_type'] ?? '';
    $reportData = json_decode($_POST['report_data'] ?? '{}', true);
    
    if (!$reportType || !$reportData) {
        throw new Exception('Missing report data');
    }
    
    $reportsController = new ReportsController();
    $reportsController->exportToCsv($reportType, $reportData);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error exporting report: ' . $e->getMessage()
    ]);
}
?>
