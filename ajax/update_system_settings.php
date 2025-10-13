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
    
    $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
    
    if (empty($settings)) {
        echo json_encode(['success' => false, 'message' => 'No settings provided']);
        exit();
    }
    
    $results = [];
    $errors = [];
    
    // Update each setting
    foreach ($settings as $key => $value) {
        $result = $controller->updateSetting($key, $value, $_SESSION['user_id']);
        if (!$result['success']) {
            $errors[] = "Failed to update {$key}: " . $result['message'];
        }
    }
    
    if (empty($errors)) {
        echo json_encode(['success' => true, 'message' => 'All settings updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    }
    
} catch (Exception $e) {
    error_log("Update Settings Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
