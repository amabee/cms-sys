<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/secure_session_handler.php';
require_once __DIR__ . '/../controllers/SecurityController.php';

// Only administrators can unlock accounts
requireRoleSecure(['admin']);

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

$lockoutId = $_POST['lockout_id'] ?? '';
$userToUnlock = $_POST['user_id'] ?? '';

if (empty($userToUnlock)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    $securityController = new SecurityController();
    
    if ($securityController->unlockAccount($userToUnlock, $user_id)) {
        // Log the admin action
        logSecurityEvent('ACCOUNT_UNLOCKED', [
            'unlocked_user_id' => $userToUnlock,
            'unlocked_by' => $user_id,
            'lockout_id' => $lockoutId
        ], 'MEDIUM');
        
        echo json_encode(['success' => true, 'message' => 'Account unlocked successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unlock account']);
    }
    
} catch (Exception $e) {
    error_log('[unlock_account] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
