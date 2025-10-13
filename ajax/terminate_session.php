<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/secure_session_handler.php';
require_once __DIR__ . '/../controllers/SecurityController.php';

// Only administrators can terminate sessions
requireRoleSecure(['admin']);

if (!isset($user_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sessionToTerminate = $_POST['session_id'] ?? '';

if (empty($sessionToTerminate)) {
    echo json_encode(['success' => false, 'message' => 'Session ID is required']);
    exit;
}

try {
    $securityController = new SecurityController();
    $db = getDBConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Get session info before terminating
    $stmt = $db->prepare('
        SELECT user_id, ip_address 
        FROM user_sessions 
        WHERE session_id = ? AND is_active = 1
    ');
    $stmt->execute([$sessionToTerminate]);
    $sessionInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sessionInfo) {
        echo json_encode(['success' => false, 'message' => 'Session not found or already terminated']);
        exit;
    }
    
    // Terminate the session
    if ($securityController->endSessionTracking($sessionToTerminate, $sessionInfo['user_id'])) {
        // Log the admin action
        logSecurityEvent('SESSION_TERMINATED', [
            'terminated_session_id' => $sessionToTerminate,
            'terminated_user_id' => $sessionInfo['user_id'],
            'terminated_by' => $user_id,
            'session_ip' => $sessionInfo['ip_address']
        ], 'MEDIUM');
        
        echo json_encode(['success' => true, 'message' => 'Session terminated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to terminate session']);
    }
    
} catch (Exception $e) {
    error_log('[terminate_session] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
