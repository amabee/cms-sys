<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/secure_session_handler.php';
require_once __DIR__ . '/../controllers/SecurityController.php';

// Users must be authenticated to change password
requireUserAuthSecure();

if (!isset($user_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate CSRF token
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All password fields are required']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit;
}

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $securityController = new SecurityController();
    
    // Verify current password
    $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $currentHash = $stmt->fetchColumn();
    
    if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
        // Log failed password change attempt
        logSecurityEvent('PASSWORD_CHANGE_FAILED', [
            'reason' => 'INVALID_CURRENT_PASSWORD',
            'user_id' => $user_id
        ], 'MEDIUM');
        
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }
    
    // Validate new password strength
    $passwordErrors = $securityController->validatePassword($newPassword);
    if (!empty($passwordErrors)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Password does not meet security requirements',
            'errors' => $passwordErrors
        ]);
        exit;
    }
    
    // Check if password was used recently
    if ($securityController->isPasswordReused($user_id, $newPassword)) {
        echo json_encode([
            'success' => false, 
            'message' => 'This password has been used recently. Please choose a different password.'
        ]);
        exit;
    }
    
    // Update password
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $db->beginTransaction();
    
    // Update user password
    $stmt = $db->prepare('
        UPDATE users 
        SET password = ?, last_password_change = NOW(), password_changed_at = NOW(), force_password_change = 0 
        WHERE id = ?
    ');
    $stmt->execute([$newHash, $user_id]);
    
    // Store password in history
    $securityController->storePasswordHistory($user_id, $newHash);
    
    $db->commit();
    
    // Log successful password change
    logSecurityEvent('PASSWORD_CHANGE', [
        'user_id' => $user_id,
        'changed_by_user' => true
    ], 'LOW');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Password changed successfully. Please log in again with your new password.'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log('[change_password] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred while changing password']);
}
?>
