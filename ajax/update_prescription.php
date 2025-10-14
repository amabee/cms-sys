<?php
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('Prescription ID is required');
    }
    
    $prescriptionId = $_POST['id'];
    $patientId = $_POST['patient_id'] ?? null;
    $doctorId = $_POST['doctor_id'] ?? null;
    $medicationId = $_POST['medication_id'] ?? null;
    $dosage = $_POST['dosage'] ?? null;
    $frequency = $_POST['frequency'] ?? null;
    $duration = $_POST['duration'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;
    $instructions = $_POST['instructions'] ?? null;
    $status = $_POST['status'] ?? 'active';
    $appointmentId = $_POST['appointment_id'] ?? null;
    
    // Validate required fields
    if (empty($patientId)) {
        throw new Exception('Patient is required');
    }
    if (empty($doctorId)) {
        throw new Exception('Doctor is required');
    }
    if (empty($medicationId)) {
        throw new Exception('Medication is required');
    }
    if (empty($dosage)) {
        throw new Exception('Dosage is required');
    }
    if (empty($frequency)) {
        throw new Exception('Frequency is required');
    }
    
    $db = getDBConnection();
    
    // Check if prescription exists
    $checkStmt = $db->prepare("SELECT id FROM prescriptions WHERE id = ? LIMIT 1");
    $checkStmt->execute([$prescriptionId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Prescription not found');
    }
    
    // Update prescription
    $query = "UPDATE prescriptions SET
                patient_id = :patient_id,
                doctor_id = :doctor_id,
                medication_id = :medication_id,
                dosage = :dosage,
                frequency = :frequency,
                duration = :duration,
                quantity = :quantity,
                instructions = :instructions,
                status = :status,
                appointment_id = :appointment_id,
                updated_at = NOW()
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':patient_id', $patientId, PDO::PARAM_INT);
    $stmt->bindParam(':doctor_id', $doctorId, PDO::PARAM_INT);
    $stmt->bindParam(':medication_id', $medicationId, PDO::PARAM_INT);
    $stmt->bindParam(':dosage', $dosage);
    $stmt->bindParam(':frequency', $frequency);
    $stmt->bindParam(':duration', $duration);
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindParam(':instructions', $instructions);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
    $stmt->bindParam(':id', $prescriptionId, PDO::PARAM_INT);
    
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Prescription updated successfully',
        'prescription_id' => $prescriptionId
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in update_prescription.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in update_prescription.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
