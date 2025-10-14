<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permissions (admin only)
if (!in_array($_SESSION['user_type'], ['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../controllers/UsersController.php';
$controller = new UsersController();

try {
    // Validate required fields
    $required_fields = ['username', 'email', 'password', 'first_name', 'last_name', 'role'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'role' => $_POST['role'],
        'phone' => $_POST['phone'] ?? null,
        'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1
    ];
    
    $result = $controller->createUser($data);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Error in create_user.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create user']);
}
?>
