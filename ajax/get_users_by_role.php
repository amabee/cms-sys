<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized
if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required parameter
if (!isset($_GET['role']) || empty($_GET['role'])) {
    echo json_encode(['success' => false, 'message' => 'Role is required']);
    exit();
}

$role = $_GET['role'];
$validRoles = ['receptionist', 'secretary', 'nurse'];

if (!in_array($role, $validRoles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

try {
    $db = getDBConnection();
    
    // DataTables parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    
    // Base query
    $whereClause = "WHERE role = :role";
    $params = [':role' => $role];
    
    // Search
    if (!empty($searchValue)) {
        $whereClause .= " AND (first_name LIKE :search OR last_name LIKE :search OR username LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }
    
    // Count total records
    $countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get filtered data
    $dataQuery = "SELECT id, username, email, phone, first_name, last_name, is_active, created_at
                  FROM users 
                  $whereClause
                  ORDER BY id DESC
                  LIMIT :start, :length";
    
    $dataStmt = $db->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $dataStmt->bindValue($key, $value);
    }
    $dataStmt->bindValue(':start', $start, PDO::PARAM_INT);
    $dataStmt->bindValue(':length', $length, PDO::PARAM_INT);
    $dataStmt->execute();
    
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log('[ajax/get_users_by_role] ' . $e->getMessage());
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'An error occurred while fetching users'
    ]);
}
