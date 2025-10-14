<?php
session_start();
header('Content-Type: application/json');

// Check authentication and authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user has permission to create notifications
if (!in_array($_SESSION['user_type'], ['admin', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../controllers/NotificationsController.php';

$controller = new NotificationsController();

try {
    // Validate required fields
    $required_fields = ['type', 'priority', 'recipient_type', 'title', 'message', 'methods'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // Validate methods array
    $methods = $_POST['methods'];
    if (!is_array($methods) || empty($methods)) {
        echo json_encode(['success' => false, 'message' => 'At least one delivery method must be selected']);
        exit;
    }
    
    $valid_methods = ['email', 'sms', 'in_app', 'push'];
    foreach ($methods as $method) {
        if (!in_array($method, $valid_methods)) {
            echo json_encode(['success' => false, 'message' => 'Invalid delivery method: ' . $method]);
            exit;
        }
    }
    
    // Prepare notification data
    $data = [
        'type' => $_POST['type'],
        'priority' => $_POST['priority'],
        'title' => $_POST['title'],
        'message' => $_POST['message'],
        'methods' => $methods,
        'created_by' => $_SESSION['user_id']
    ];
    
    // Handle scheduling
    if (!empty($_POST['scheduled_for'])) {
        $data['scheduled_for'] = $_POST['scheduled_for'];
    }
    
    // Handle recipients based on type
    $recipient_type = $_POST['recipient_type'];
    
    switch ($recipient_type) {
        case 'patient':
            if (empty($_POST['recipient_id'])) {
                echo json_encode(['success' => false, 'message' => 'Patient must be selected']);
                exit;
            }
            $data['patient_id'] = $_POST['recipient_id'];
            break;
            
        case 'user':
            if (empty($_POST['recipient_id'])) {
                echo json_encode(['success' => false, 'message' => 'User must be selected']);
                exit;
            }
            $data['user_id'] = $_POST['recipient_id'];
            break;
            
        case 'all_patients':
            // Will be handled by creating multiple notifications
            $data['recipient_type'] = 'all_patients';
            break;
            
        case 'all_users':
            // Will be handled by creating multiple notifications
            $data['recipient_type'] = 'all_users';
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid recipient type']);
            exit;
    }
    
    $result = $controller->createNotification($data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => ['notification_id' => $result['notification_id']]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log("Error in create_notification.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create notification']);
}
?>
