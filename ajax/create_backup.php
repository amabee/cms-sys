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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $controller = new SystemSettingsController($conn);
    
    $backupType = isset($_POST['backup_type']) ? $_POST['backup_type'] : 'full';
    
    // Validate backup type
    $validTypes = ['full', 'database', 'files', 'configuration'];
    if (!in_array($backupType, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid backup type']);
        exit();
    }
    
    $result = $controller->createBackup($backupType, $_SESSION['user_id']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Create Backup Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
