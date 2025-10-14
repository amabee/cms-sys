<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized
if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = getDBConnection();
    
    $query = "SELECT DISTINCT specialization 
              FROM doctors 
              WHERE specialization IS NOT NULL 
              AND specialization != '' 
              ORDER BY specialization ASC";
    
    $stmt = $db->query($query);
    $specializations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'data' => $specializations
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_specializations] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch specializations'
    ]);
}
