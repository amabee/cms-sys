<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/secure_session_handler.php';

// Only administrators can view security logs
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
    
    // Build query with filters
    $where = ['1=1'];
    $params = [];
    
    if (!empty($_POST['event_type'])) {
        $where[] = 'sl.event_type = ?';
        $params[] = $_POST['event_type'];
    }
    
    if (!empty($_POST['risk_level'])) {
        $where[] = 'sl.risk_level = ?';
        $params[] = $_POST['risk_level'];
    }
    
    if (!empty($_POST['date_from'])) {
        $where[] = 'DATE(sl.created_at) >= ?';
        $params[] = $_POST['date_from'];
    }
    
    if (!empty($_POST['date_to'])) {
        $where[] = 'DATE(sl.created_at) <= ?';
        $params[] = $_POST['date_to'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    $sql = "
        SELECT 
            sl.id,
            sl.user_id,
            sl.session_id,
            sl.ip_address,
            sl.event_type,
            sl.event_details,
            sl.risk_level,
            sl.created_at,
            u.username,
            CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM security_logs sl
        LEFT JOIN users u ON sl.user_id = u.id
        WHERE {$whereClause}
        ORDER BY sl.created_at DESC
        LIMIT 500
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $logs]);
    
} catch (Exception $e) {
    error_log('[get_security_logs] Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get security logs']);
}
?>
