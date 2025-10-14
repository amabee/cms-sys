<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized (admin only)
if (!isset($user_id) || $user_type !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = getDBConnection();
    
    // Get users who don't have a doctor profile yet and have role 'doctor'
    $query = "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.email 
              FROM users u 
              LEFT JOIN doctors d ON u.id = d.user_id 
              WHERE d.id IS NULL 
              AND u.role = 'doctor'
              AND u.is_active = 1
              ORDER BY u.first_name, u.last_name";
    
    $stmt = $db->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_available_users_for_doctor] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch available users'
    ]);
}
