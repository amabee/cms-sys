<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = getDBConnection();
    
    // Get search parameter if provided
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $query = "SELECT id, first_name, last_name, phone, email 
              FROM patients";
    
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (first_name LIKE :search OR last_name LIKE :search OR phone LIKE :search OR email LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    $query .= " ORDER BY first_name, last_name LIMIT 100";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->execute();
    
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $patients
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_patients_list] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch patients: ' . $e->getMessage()
    ]);
}
?>
