<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/QueueController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow doctors and staff to update queue
if (!in_array($user_type, ['admin', 'doctor', 'receptionist', 'secretary'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$queue_id = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($queue_id <= 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $controller = new QueueController();
    $result = $controller->updateQueueStatus($queue_id, $status, $user_id);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('[ajax/update_queue] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
