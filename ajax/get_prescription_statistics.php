<?php
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Total prescriptions
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM prescriptions WHERE 1=1");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active prescriptions
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM prescriptions WHERE status = 'active'");
    $stmt->execute();
    $active = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Completed prescriptions
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM prescriptions WHERE status = 'completed'");
    $stmt->execute();
    $completed = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Discontinued prescriptions
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM prescriptions WHERE status = 'discontinued'");
    $stmt->execute();
    $discontinued = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => (int)$total,
            'active' => (int)$active,
            'completed' => (int)$completed,
            'discontinued' => (int)$discontinued
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_prescription_statistics.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'data' => [
            'total' => 0,
            'active' => 0,
            'completed' => 0,
            'discontinued' => 0
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in get_prescription_statistics.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'data' => [
            'total' => 0,
            'active' => 0,
            'completed' => 0,
            'discontinued' => 0
        ]
    ]);
}
?>
