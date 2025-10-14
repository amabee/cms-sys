<?php
session_start();
header('Content-Type: application/json');

require_once '../shared/config.php';

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("SELECT * FROM notification_templates ORDER BY id");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => count($templates),
        'data' => $templates
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
