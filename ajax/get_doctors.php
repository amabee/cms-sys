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
    $sql = "SELECT d.id, d.doctor_id, CONCAT(u.first_name, ' ', u.last_name) as name, d.is_available, d.specialization FROM doctors d LEFT JOIN users u ON d.user_id = u.id WHERE d.is_available = 1";
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    error_log('[ajax/get_doctors] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch doctors']);
}
