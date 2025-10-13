<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = getDBConnection();
    
    // Simple query for pharmacies
    $query = "SELECT id, pharmacy_name, address, phone_number, email FROM pharmacies WHERE status = 'active' ORDER BY pharmacy_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $pharmacies
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
