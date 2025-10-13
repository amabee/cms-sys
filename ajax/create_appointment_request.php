<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../controllers/PatientPortalController.php';

$controller = new PatientPortalController();

try {
    $patient_id = $_POST['patient_id'] ?? null;
    
    // Validate patient access
    if ($_SESSION['role'] === 'admin' && $patient_id) {
        // Admin can create requests for any patient
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
    
    // Validate required fields
    $required_fields = ['appointment_type', 'preferred_date', 'reason', 'urgency'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // Prepare appointment request data
    $data = [
        'appointment_type' => $_POST['appointment_type'],
        'preferred_date' => $_POST['preferred_date'],
        'preferred_time_start' => $_POST['preferred_time_start'] ?? null,
        'preferred_time_end' => $_POST['preferred_time_end'] ?? null,
        'requested_doctor_id' => $_POST['requested_doctor_id'] ?? null,
        'reason' => $_POST['reason'],
        'urgency' => $_POST['urgency']
    ];
    
    // Handle alternative dates if provided
    $alternative_dates = [];
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($_POST["alternative_date_$i"])) {
            $alternative_dates[] = $_POST["alternative_date_$i"];
        }
    }
    if (!empty($alternative_dates)) {
        $data['alternative_dates'] = $alternative_dates;
    }
    
    $result = $controller->createAppointmentRequest($patient_id, $data);
    
    if ($result['success']) {
        // Create notification for admin/staff about new appointment request
        require_once '../controllers/NotificationsController.php';
        $notificationsController = new NotificationsController();
        
        $notification_data = [
            'type' => 'appointment_request',
            'priority' => $data['urgency'] === 'emergency' ? 'critical' : 'normal',
            'title' => 'New Appointment Request',
            'message' => "Patient has requested a new appointment for {$data['appointment_type']}",
            'methods' => ['in_app'],
            'recipient_type' => 'all_users',
            'reference_data' => json_encode([
                'patient_id' => $patient_id,
                'request_id' => $result['request_id'],
                'appointment_type' => $data['appointment_type'],
                'urgency' => $data['urgency']
            ])
        ];
        
        $notificationsController->createNotification($notification_data);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error in create_appointment_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create appointment request']);
}
?>
