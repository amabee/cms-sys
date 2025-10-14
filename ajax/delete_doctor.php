<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized (admin only)
if (!isset($user_id) || $user_type !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required parameter
if (!isset($_POST['doctor_id']) || empty($_POST['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID is required']);
    exit();
}

$doctorId = intval($_POST['doctor_id']);

try {
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Get user_id from doctor record
    $getDoctorQuery = "SELECT user_id FROM doctors WHERE id = :id";
    $getDoctorStmt = $db->prepare($getDoctorQuery);
    $getDoctorStmt->bindValue(':id', $doctorId, PDO::PARAM_INT);
    $getDoctorStmt->execute();
    $doctorRecord = $getDoctorStmt->fetch();
    
    if (!$doctorRecord) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit();
    }
    
    $userId = $doctorRecord['user_id'];
    
    // Delete doctor record first (due to foreign key constraint)
    $deleteDoctorQuery = "DELETE FROM doctors WHERE id = :id";
    $deleteDoctorStmt = $db->prepare($deleteDoctorQuery);
    $deleteDoctorStmt->bindValue(':id', $doctorId, PDO::PARAM_INT);
    
    if (!$deleteDoctorStmt->execute()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to delete doctor record']);
        exit();
    }
    
    // Delete user account
    if ($userId) {
        $deleteUserQuery = "DELETE FROM users WHERE id = :id";
        $deleteUserStmt = $db->prepare($deleteUserQuery);
        $deleteUserStmt->bindValue(':id', $userId, PDO::PARAM_INT);
        
        if (!$deleteUserStmt->execute()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to delete user account']);
            exit();
        }
    }
    
    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Doctor and user account deleted successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[ajax/delete_doctor] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting doctor: ' . $e->getMessage()
    ]);
}
