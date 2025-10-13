<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/secure_session_handler.php';

// Only administrators can view account lockouts
requireRoleSecure(['admin']);

if (!isset($user_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $sql = "
        SELECT 
            al.id,
            al.user_id,
            al.username,
            al.ip_address,
            al.lockout_reason,
            al.locked_at,
            al.unlock_at,
            u.username as user_username,
            CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM account_lockouts al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.is_active = 1 
        AND (al.unlock_at IS NULL OR al.unlock_at > NOW())
        ORDER BY al.locked_at DESC
        LIMIT 100
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $lockouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $lockouts]);
    
} catch (Exception $e) {
    error_log('[get_account_lockouts] Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get account lockouts']);
}
?>
