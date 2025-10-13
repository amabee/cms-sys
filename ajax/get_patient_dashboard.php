<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../controllers/PatientPortalController.php';

$controller = new PatientPortalController();

try {
    $patient_id = $_GET['patient_id'] ?? null;
    
    // If user is admin, they can view any patient's dashboard
    if ($_SESSION['role'] === 'admin' && $patient_id) {
        // Use provided patient_id
    } else if ($_SESSION['role'] === 'patient') {
        // Use session patient_id
        $patient_id = $_SESSION['patient_id'] ?? null;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid access']);
        exit;
    }
    
    if (!$patient_id) {
        echo json_encode(['success' => false, 'message' => 'Patient ID not found']);
        exit;
    }
    
    $result = $controller->getDashboardData($patient_id);
    
    if ($result['success']) {
        // Add patient info to response
        $stmt = getDBConnection()->prepare("
            SELECT first_name, last_name, email, phone, portal_last_login
            FROM patients 
            WHERE id = :patient_id
        ");
        $stmt->execute(['patient_id' => $patient_id]);
        $patient_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $result['data']['patient_info'] = $patient_info;
        
        echo json_encode($result);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Error in get_patient_dashboard.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load dashboard data']);
}
?>
