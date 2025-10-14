<?php
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('Prescription ID is required');
    }
    
    $prescriptionId = $_POST['id'];
    $db = getDBConnection();
    
    // Check if prescription exists
    $checkStmt = $db->prepare("SELECT id FROM prescriptions WHERE id = ? LIMIT 1");
    $checkStmt->execute([$prescriptionId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Prescription not found');
    }
    
    // Delete prescription
    $stmt = $db->prepare("DELETE FROM prescriptions WHERE id = ?");
    $stmt->execute([$prescriptionId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Prescription deleted successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in delete_prescription.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in delete_prescription.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
