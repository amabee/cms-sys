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
    
    // Validate patient access
    if ($_SESSION['role'] === 'admin' && $patient_id) {
        // Admin can view any patient's appointments
    } else if ($_SESSION['role'] === 'patient') {
        $patient_id = $_SESSION['patient_id'] ?? null;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid access']);
        exit;
    }
    
    if (!$patient_id) {
        echo json_encode(['success' => false, 'message' => 'Patient ID not found']);
        exit;
    }
    
    // Build filters from query parameters
    $filters = [];
    if (!empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    if (!empty($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    }
    
    $result = $controller->getPatientAppointments($patient_id, $filters);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error in get_patient_appointments.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load appointments']);
}
?>
