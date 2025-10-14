<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/SystemLogger.php';
require_once __DIR__ . '/../controllers/PrescriptionController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Accept both JSON and POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fall back to POST data
        $input = $_POST;
    }
    
    if (empty($input)) {
        throw new Exception('Invalid input data');
    }
    
    // Validate required fields
    if (empty($input['patient_id'])) {
        throw new Exception('Patient is required');
    }
    if (empty($input['doctor_id'])) {
        throw new Exception('Doctor is required');
    }
    if (empty($input['medication_id'])) {
        throw new Exception('Medication is required');
    }
    if (empty($input['dosage'])) {
        throw new Exception('Dosage is required');
    }
    if (empty($input['frequency'])) {
        throw new Exception('Frequency is required');
    }
    
    $db = getDBConnection();
    
    // Generate unique prescription_id
    function generatePrescriptionId($db) {
        $stmt = $db->prepare("SELECT MAX(CAST(SUBSTRING(prescription_id, 3) AS UNSIGNED)) as max_num FROM prescriptions WHERE prescription_id LIKE 'RX%'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = ($result && $result['max_num']) ? $result['max_num'] + 1 : 1;
        return 'RX' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    $prescriptionId = generatePrescriptionId($db);
    
    // Insert prescription
    $query = "INSERT INTO prescriptions (
                prescription_id, patient_id, doctor_id, medication_id, 
                dosage, frequency, duration, quantity, instructions, 
                status, appointment_id, created_at, updated_at
              ) VALUES (
                :prescription_id, :patient_id, :doctor_id, :medication_id,
                :dosage, :frequency, :duration, :quantity, :instructions,
                :status, :appointment_id, NOW(), NOW()
              )";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':prescription_id', $prescriptionId);
    $stmt->bindParam(':patient_id', $input['patient_id'], PDO::PARAM_INT);
    $stmt->bindParam(':doctor_id', $input['doctor_id'], PDO::PARAM_INT);
    $stmt->bindParam(':medication_id', $input['medication_id'], PDO::PARAM_INT);
    $stmt->bindParam(':dosage', $input['dosage']);
    $stmt->bindParam(':frequency', $input['frequency']);
    $stmt->bindParam(':duration', $input['duration']);
    $stmt->bindParam(':quantity', $input['quantity'], PDO::PARAM_INT);
    $stmt->bindParam(':instructions', $input['instructions']);
    $stmt->bindParam(':status', $input['status']);
    $stmt->bindParam(':appointment_id', $input['appointment_id'], PDO::PARAM_INT);
    
    $stmt->execute();
    $insertedId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Prescription created successfully',
        'prescription_id' => $prescriptionId,
        'id' => $insertedId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
