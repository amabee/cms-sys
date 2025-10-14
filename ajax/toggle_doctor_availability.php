<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized (admin only)
if (!isset($user_id) || $user_type !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate input
if (!isset($_POST['doctor_id']) || !isset($_POST['is_available'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$doctorId = intval($_POST['doctor_id']);
$isAvailable = intval($_POST['is_available']);

try {
    $db = getDBConnection();
    
    // Update doctor availability
    $query = "UPDATE doctors SET is_available = :is_available WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':is_available', $isAvailable, PDO::PARAM_INT);
    $stmt->bindValue(':id', $doctorId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Doctor availability updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update availability'
        ]);
    }
    
} catch (Exception $e) {
    error_log('[ajax/toggle_doctor_availability] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating availability'
    ]);
}
