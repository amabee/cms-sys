<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/MedicalRecordsController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow authorized roles
if (!in_array($user_type, ['admin', 'doctor', 'secretary'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$searchTerm = $_GET['q'] ?? '';
$filters = [];

// Optional filters
if (isset($_GET['patient_id']) && !empty($_GET['patient_id'])) {
    $filters['patient_id'] = $_GET['patient_id'];
}

if (isset($_GET['doctor_id']) && !empty($_GET['doctor_id'])) {
    $filters['doctor_id'] = $_GET['doctor_id'];
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

if (empty($searchTerm)) {
    echo json_encode(['success' => false, 'message' => 'Search term is required']);
    exit();
}

try {
    $controller = new MedicalRecordsController();
    $result = $controller->searchRecords($searchTerm, $filters);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('[ajax/search_medical_records] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while searching medical records']);
}
?>
