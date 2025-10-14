<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get doctor_id from users table
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Get doctor_id for this user
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit();
    }
    
    $doctor_id = $doctor['id'];
    
    // Get all distinct patients who have appointments or medical records with this doctor
    $sql = "SELECT DISTINCT p.* 
            FROM patients p
            WHERE p.id IN (
                SELECT DISTINCT patient_id FROM appointments WHERE doctor_id = ?
                UNION
                SELECT DISTINCT patient_id FROM medical_records WHERE doctor_id = ?
            )
            ORDER BY p.last_name, p.first_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$doctor_id, $doctor_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $patients,
        'recordsTotal' => count($patients),
        'recordsFiltered' => count($patients)
    ]);
    
} catch (Exception $e) {
    error_log('[get_doctor_patients] Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error fetching patients',
        'data' => []
    ]);
}
