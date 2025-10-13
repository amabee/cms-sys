<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/QueueController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow doctors and staff to view queue
if (!in_array($user_type, ['admin', 'doctor', 'receptionist', 'secretary'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $controller = new QueueController();
    
    // Get doctor ID if user is a doctor
    $doctor_id = null;
    if ($user_type === 'doctor') {
        $db = getDBConnection();
        $stmt = $db->prepare('SELECT id FROM doctors WHERE user_id = :user_id LIMIT 1');
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $doctor_id = $stmt->fetchColumn();
    }
    
    // Check if requesting statistics
    if (isset($_GET['stats']) && $_GET['stats']) {
        $stats = $controller->getQueueStats($doctor_id);
        echo json_encode(['success' => true, 'stats' => $stats]);
    } else {
        $result = $controller->getWaitingQueue($doctor_id);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log('[ajax/get_queue] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
