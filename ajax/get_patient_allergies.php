<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/PrescriptionController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    if (!isset($_GET['patient_id'])) {
        throw new Exception('Patient ID is required');
    }
    
    $db = getDBConnection();
    $controller = new PrescriptionController($db);
    
    $patientId = $_GET['patient_id'];
    
    // Get patient allergies directly from database
    $query = "SELECT pa.*, pa.allergen_name, pa.severity, pa.symptoms 
              FROM patient_allergies pa 
              WHERE pa.patient_id = ? AND pa.is_active = 1 
              ORDER BY pa.severity DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$patientId]);
    $allergies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $allergies
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
