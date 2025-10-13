<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/MedicalRecordsController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow authorized roles
if (!in_array($user_type, ['admin', 'doctor', 'patient', 'secretary'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$patientId = $_GET['patient_id'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

if (!$patientId) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit();
}

// If user is a patient, they can only access their own records
if ($user_type === 'patient') {
    // Get patient record for this user
    require_once __DIR__ . '/../shared/config.php';
    $db = getDBConnection();
    $stmt = $db->prepare('SELECT id FROM patients WHERE user_id = :user_id');
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $userPatientId = $stmt->fetchColumn();
    
    if (!$userPatientId || $userPatientId != $patientId) {
        echo json_encode(['success' => false, 'message' => 'Access denied - can only view own records']);
        exit();
    }
}

try {
    $controller = new MedicalRecordsController();
    $result = $controller->getByPatient($patientId, $limit);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('[ajax/get_patient_medical_records] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving patient medical records']);
}
?>
