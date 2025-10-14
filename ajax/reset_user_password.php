<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized (admin only)
if (!isset($user_id) || $user_type !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required parameters
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

if (!isset($_POST['new_password']) || empty($_POST['new_password'])) {
    echo json_encode(['success' => false, 'message' => 'New password is required']);
    exit();
}

$userId = intval($_POST['user_id']);
$newPassword = $_POST['new_password'];

// Validate password length
if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit();
}

try {
    $db = getDBConnection();
    
    // Check if user exists
    $checkUserQuery = "SELECT id, username FROM users WHERE id = :user_id";
    $checkUserStmt = $db->prepare($checkUserQuery);
    $checkUserStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkUserStmt->execute();
    
    $user = $checkUserStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $updateQuery = "UPDATE users SET password = :password WHERE id = :user_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindValue(':password', $hashedPassword);
    $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully',
            'username' => $user['username']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
    }
    
} catch (Exception $e) {
    error_log('[ajax/reset_user_password] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while resetting password: ' . $e->getMessage()
    ]);
}
