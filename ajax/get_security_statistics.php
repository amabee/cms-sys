<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/secure_session_handler.php';
require_once __DIR__ . '/../controllers/SecurityController.php';

// Only administrators can access security statistics
requireRoleSecure(['admin']);

if (!isset($user_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $securityController = new SecurityController();
    $stats = $securityController->getSecurityStats();
    
    echo json_encode(['success' => true, 'data' => $stats]);
    
} catch (Exception $e) {
    error_log('[get_security_statistics] Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get security statistics']);
}
?>
