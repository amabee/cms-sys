<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit();
}

$patient_id = (int)$_GET['patient_id'];

try {
    $db = getDBConnection();
    
    // Get the most recent vital signs from today's queue entry
    $sql = "
        SELECT notes, queued_at
        FROM visit_queue
        WHERE patient_id = :patient_id
        AND DATE(queued_at) = CURDATE()
        AND notes IS NOT NULL
        AND notes != ''
        ORDER BY queued_at DESC
        LIMIT 1
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'No vital signs found for today']);
        exit();
    }
    
    // Parse vital signs from notes
    $notes = $result['notes'];
    $vital_signs = [];
    
    // Extract vital signs using regex
    if (preg_match('/BP:\s*([^\n]+)/', $notes, $matches)) {
        $vital_signs['blood_pressure'] = trim($matches[1]);
    }
    if (preg_match('/Temp:\s*([0-9.]+)/', $notes, $matches)) {
        $vital_signs['temperature'] = trim($matches[1]);
    }
    if (preg_match('/Pulse:\s*([0-9]+)/', $notes, $matches)) {
        $vital_signs['pulse'] = trim($matches[1]);
    }
    if (preg_match('/Resp Rate:\s*([0-9]+)/', $notes, $matches)) {
        $vital_signs['respiratory_rate'] = trim($matches[1]);
    }
    if (preg_match('/Weight:\s*([0-9.]+)/', $notes, $matches)) {
        $vital_signs['weight'] = trim($matches[1]);
    }
    if (preg_match('/Height:\s*([0-9]+)/', $notes, $matches)) {
        $vital_signs['height'] = trim($matches[1]);
    }
    if (preg_match('/O2 Sat:\s*([0-9]+)/', $notes, $matches)) {
        $vital_signs['oxygen_saturation'] = trim($matches[1]);
    }
    if (preg_match('/Pain Level:\s*([0-9]+)/', $notes, $matches)) {
        $vital_signs['pain_level'] = trim($matches[1]);
    }
    
    echo json_encode([
        'success' => true,
        'vital_signs' => $vital_signs,
        'queued_at' => $result['queued_at']
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_patient_vital_signs] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
