<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/secure_session_handler.php';

// Only administrators can view active sessions
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
            us.id,
            us.session_id,
            us.user_id,
            us.ip_address,
            us.login_time,
            us.last_activity,
            u.username,
            CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM user_sessions us
        LEFT JOIN users u ON us.user_id = u.id
        WHERE us.is_active = 1 
        AND us.expires_at > NOW()
        ORDER BY us.last_activity DESC
        LIMIT 100
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $sessions]);
    
} catch (Exception $e) {
    error_log('[get_active_sessions] Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get active sessions']);
}
?>
